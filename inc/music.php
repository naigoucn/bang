<?php
/**
 * 音乐播放器后端：统一代理 + 前端配置注入
 *
 * 统一协议（前端只认这一个，两种数据源后端归一）：
 *   GET /wp-json/bang/v1/music?act=song&step=0|1
 *   step：0 = 当前/随机一首，1 = 下一首
 *   返回：{ code:0, data:{ id, name, artist, cover, audio, lyric, tone } }
 *   tone：封面主色明暗 light/dark（可选，缺省时服务端取色，驱动播放器配色切换）。
 *
 * 数据源二选一：
 *   1) 后台「音乐设置 - 自定义接口地址」留空 → 演示模式，走 inc/music-demo.php（QQ 音乐）。
 *      【开源打包时移除演示】删除 inc/music-demo.php + functions.php 里它的 require，
 *      并把 music_api 设为必填即可，其余链路无需改动。
 *   2) 填了地址 → 自定义模式，把 act/step 透传给该地址，期望其返回上面的统一 JSON。
 *
 * 密钥/接口地址只走服务端，不下发浏览器（同 geetest/smtp 约定）。
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 通用 GET（带浏览器 UA，兼容网易云等需要 Referer 的接口）
 */
function bang_music_http_get( $url, $headers = array() ) {
	$res = wp_remote_get(
		$url,
		array(
			'timeout' => 12,
			'headers' => array_merge(
				array(
					'Accept'     => 'application/json, text/plain, */*',
					'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
				),
				$headers
			),
		)
	);
	if ( is_wp_error( $res ) ) {
		return null;
	}
	$code = (int) wp_remote_retrieve_response_code( $res );
	if ( $code < 200 || $code >= 300 ) {
		return null;
	}
	return wp_remote_retrieve_body( $res );
}

/**
 * 简单按 IP 限流：每 60 秒 40 次，防被当免费代理刷。
 */
function bang_music_rate_ok() {
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$key = 'bang_music_r_' . md5( $ip );
	$n   = (int) get_transient( $key );
	if ( $n >= 40 ) {
		return false;
	}
	set_transient( $key, $n + 1, MINUTE_IN_SECONDS );
	return true;
}

/**
 * 请求统一 JSON：把两种数据源归一成 { id, name, artist, cover, audio, lyric }。
 */
function bang_music_fetch( $act, $step, $idx = 0 ) {
	$api = trim( (string) bang_opt( 'music_api' ) );

	if ( '' !== $api ) {
		// 自定义模式：只透传统一协议参数，不做平台特定解析。
		$api  = esc_url_raw( $api );
		$args = array(
			'act'  => 'song',
			'step' => $step,
		);
		if ( 'list' === $act ) {
			$args['act'] = 'list';
		} elseif ( $idx > 0 ) {
			$args['idx'] = $idx;
		}
		$url  = add_query_arg( $args, $api );
		$body = bang_music_http_get( $url );
		if ( ! $body ) {
			return new WP_Error( 'bang_music_http', '音乐接口无响应。', array( 'status' => 502 ) );
		}
		$json = json_decode( $body, true );
		if ( ! is_array( $json ) || ! isset( $json['data'] ) ) {
			return new WP_Error( 'bang_music_format', '音乐接口返回格式不正确。', array( 'status' => 502 ) );
		}
		if ( 'list' === $act ) {
			/* 列表：自定义接口可选支持；格式不符时返回空数组，前端优雅降级 */
			if ( ! is_array( $json['data'] ) ) {
				return array();
			}
			$out = array();
			foreach ( $json['data'] as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$out[] = array(
					'id'     => isset( $item['id'] ) ? sanitize_text_field( (string) $item['id'] ) : '',
					'name'   => isset( $item['name'] ) ? sanitize_text_field( wp_strip_all_tags( (string) $item['name'] ) ) : '',
					'artist' => isset( $item['artist'] ) ? sanitize_text_field( wp_strip_all_tags( (string) $item['artist'] ) ) : '',
					'cover'  => isset( $item['cover'] ) ? bang_music_safe_url( $item['cover'] ) : '',
				);
			}
			return $out;
		}
		if ( empty( $json['data'] ) || ! is_array( $json['data'] ) ) {
			return new WP_Error( 'bang_music_format', '音乐接口返回格式不正确。', array( 'status' => 502 ) );
		}
		return bang_music_normalize( $json['data'] );
	}

	// 演示模式：独立文件，开源时可整体删除。
	if ( ! function_exists( 'bang_music_demo_song' ) ) {
		return new WP_Error( 'bang_music_demo', '演示数据源不可用，请在后台填写自己的音乐接口地址。', array( 'status' => 501 ) );
	}
	if ( 'list' === $act ) {
		return function_exists( 'bang_music_demo_list' ) ? bang_music_demo_list() : array();
	}
	return bang_music_demo_song( $step, $idx );
}

/**
 * 取封面主色明暗（light/dark），供前端切换播放器配色（浅底黑字 / 深底白字）。
 * 封面多为跨域图，前端 canvas 取色会被污染，故放到服务端算。
 * 依赖 GD；不可用或下载失败一律回退 dark。按封面 URL 缓存，避免每首重复下载。
 */
function bang_music_cover_tone( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return 'dark';
	}
	$key    = 'bang_m_tone_' . md5( $url );
	$cached = get_transient( $key );
	if ( 'light' === $cached || 'dark' === $cached ) {
		return $cached;
	}
	$tone = 'dark';
	if ( function_exists( 'imagecreatefromstring' ) ) {
		$bytes = bang_music_http_get( $url, array( 'Referer' => 'https://music.163.com/' ) );
		if ( $bytes ) {
			$img = @imagecreatefromstring( $bytes );
			if ( $img ) {
				/* 缩到 1x1 求平均色，再按亮度阈值判定明暗 */
				$s = imagecreatetruecolor( 1, 1 );
				imagecopyresampled( $s, $img, 0, 0, 0, 0, 1, 1, imagesx( $img ), imagesy( $img ) );
				$rgb = imagecolorat( $s, 0, 0 );
				$r   = ( $rgb >> 16 ) & 0xFF;
				$g   = ( $rgb >> 8 ) & 0xFF;
				$b   = $rgb & 0xFF;
				$lum = 0.299 * $r + 0.587 * $g + 0.114 * $b;
				$tone = $lum >= 150 ? 'light' : 'dark';
				imagedestroy( $s );
				imagedestroy( $img );
			}
		}
	}
	set_transient( $key, $tone, 7 * DAY_IN_SECONDS );
	return $tone;
}

/**
 * 归一化 + 安全过滤字段（外链一律只允许 http(s)）。
 */
function bang_music_normalize( $data ) {
	$id     = isset( $data['id'] ) ? sanitize_text_field( (string) $data['id'] ) : '';
	$name   = isset( $data['name'] ) ? sanitize_text_field( wp_strip_all_tags( (string) $data['name'] ) ) : '';
	$artist = isset( $data['artist'] ) ? sanitize_text_field( wp_strip_all_tags( (string) $data['artist'] ) ) : '';
	$cover  = isset( $data['cover'] ) ? bang_music_safe_url( $data['cover'] ) : '';
	$audio  = isset( $data['audio'] ) ? bang_music_safe_url( $data['audio'] ) : '';
	$lyric  = isset( $data['lyric'] ) ? (string) $data['lyric'] : '';
	if ( '' === $audio ) {
		return new WP_Error( 'bang_music_audio', '接口未返回可播放的音频地址。', array( 'status' => 502 ) );
	}
	if ( '' === $name ) {
		$name = '未知歌曲';
	}
	/* 封面主色明暗：数据源若自带合法 tone 则用之，否则服务端取色。 */
	$tone = isset( $data['tone'] ) && in_array( $data['tone'], array( 'light', 'dark' ), true )
		? $data['tone']
		: bang_music_cover_tone( $cover );
	return array(
		'id'     => $id,
		'name'   => $name,
		'artist' => $artist,
		'cover'  => $cover,
		'audio'  => $audio,
		'lyric'  => $lyric,
		'tone'   => $tone,
	);
}

/**
 * 只接受 http(s) 外链；网易云 http 封面自动升 https，避免混合内容被拦。
 */
function bang_music_safe_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}
	if ( 0 === strpos( $url, 'http://' ) ) {
		$url = 'https://' . substr( $url, 7 );
	}
	$url = esc_url_raw( $url );
	return preg_match( '#^https?://#i', $url ) ? $url : '';
}

/**
 * REST 路由（游客可用；限流把关）。
 */
function bang_music_rest_route() {
	register_rest_route(
		'bang/v1',
		'/music',
		array(
			'methods'             => 'GET',
			'callback'            => 'bang_music_route',
			'permission_callback' => '__return_true',
			'args'                => array(
				'act'  => array(
					'default'           => 'song',
					'sanitize_callback' => 'sanitize_key',
				),
				'step' => array(
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
				'idx'  => array(
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'bang_music_rest_route' );

/**
 * 路由回调。
 */
function bang_music_route( $request ) {
	if ( ! bang_opt( 'music_on' ) ) {
		return new WP_Error( 'bang_music_off', '音乐播放器未开启。', array( 'status' => 403 ) );
	}
	if ( ! bang_music_rate_ok() ) {
		return new WP_Error( 'bang_music_rate', '请求太频繁，稍后再试。', array( 'status' => 429 ) );
	}
	$act  = (string) $request->get_param( 'act' );
	$step = (int) $request->get_param( 'step' );
	$idx  = (int) $request->get_param( 'idx' );
	if ( ! in_array( $act, array( 'song', 'list' ), true ) ) {
		return new WP_Error( 'bang_music_act', '不支持的操作。', array( 'status' => 400 ) );
	}
	$data = bang_music_fetch( $act, $step, $idx );
	if ( is_wp_error( $data ) ) {
		return $data;
	}
	return rest_ensure_response(
		array(
			'code' => 0,
			'data' => $data,
		)
	);
}

/**
 * 侧栏迷你播放器 DOM（放 header.php 侧栏内，#swup-wrapper 外，换页不重建）。
 */
function bang_music_mini_html() {
	if ( ! bang_opt( 'music_on' ) ) {
		return;
	}
	$ico = function ( $name, $size, $cls = '' ) {
		return bang_icon( $name, $size, $cls );
	};
	echo '<div class="bang-music-mini" data-bang-music hidden aria-label="音乐播放器">';
	echo '<button class="bang-music-cover" type="button" data-bang-music-open aria-label="打开播放器" title="打开播放器">';
	echo '<img class="bang-music-cover-img" data-bang-music-cover alt="" width="40" height="40" decoding="async">';
	echo '<span class="bang-music-cover-ph" aria-hidden="true">' . $ico( 'music-2', 18, 'i-music' ) . '</span>';
	echo '<span class="bang-music-cover-Play" aria-hidden="true">' . $ico( 'play', 16, 'i-play' ) . $ico( 'pause', 16, 'i-pause' ) . '</span>';
	echo '</button>';
	echo '<div class="bang-music-mini-meta" data-bang-music-open role="button" tabindex="0">';
	echo '<span class="bang-music-name" data-bang-music-name>未播放</span>';
	echo '<span class="bang-music-artist" data-bang-music-artist>点封面开始播放</span>';
	echo '</div>';
	echo '<div class="bang-music-mini-ctl">';
	echo '<button class="bang-music-btn" type="button" data-bang-music-prev aria-label="上一首" title="上一首">' . $ico( 'prev', 17 ) . '</button>';
	echo '<button class="bang-music-btn is-play" type="button" data-bang-music-toggle aria-label="播放或暂停" title="播放/暂停">' . $ico( 'play', 18, 'i-play' ) . $ico( 'pause', 18, 'i-pause' ) . '</button>';
	echo '<button class="bang-music-btn" type="button" data-bang-music-next aria-label="下一首" title="下一首">' . $ico( 'next', 17 ) . '</button>';
	echo '</div>';
	echo '</div>';
}

/**
 * 全屏歌词播放器 DOM（放 footer，常驻、换页不重建）。
 */
function bang_music_stage_html() {
	if ( ! bang_opt( 'music_on' ) ) {
		return;
	}
	$ico = function ( $name, $size, $cls = '' ) {
		return bang_icon( $name, $size, $cls );
	};
	echo '<div class="bang-music-stage" data-bang-music-stage hidden role="dialog" aria-modal="true" aria-label="音乐播放器">';
	echo '<div class="bang-music-panel" data-bang-music-panel>';
	echo '<div class="bang-music-backdrop" data-bang-music-backdrop aria-hidden="true"></div>';
	// 顶栏：标题 / 关闭
	echo '<div class="bang-music-top">';
	echo '<span class="bang-music-top-title">正在播放</span>';
	echo '<button class="bang-music-icon" type="button" data-bang-music-close aria-label="关闭播放器" title="关闭">' . $ico( 'close-x', 22 ) . '</button>';
	echo '</div>';
	// 主体：左大封面 + 右歌词
	echo '<div class="bang-music-body">';
	echo '<div class="bang-music-art"><img data-bang-music-art-cover alt="" width="300" height="300" decoding="async"></div>';
	echo '<div class="bang-music-side">';
	echo '<div class="bang-music-info">';
	echo '<h2 class="bang-music-title" data-bang-music-title>未播放</h2>';
	echo '<p class="bang-music-sub" data-bang-music-sub>点播放开始</p>';
	echo '</div>';
	echo '<div class="bang-music-lyrics" data-bang-music-lyrics><p class="bang-music-lyric-empty">暂无歌词</p></div>';
	echo '</div>';
	echo '</div>';
	// 底部控制栏
	echo '<div class="bang-music-foot">';
	echo '<div class="bang-music-now">';
	echo '<img class="bang-music-now-cover" data-bang-music-now-cover alt="" width="46" height="46" decoding="async">';
	echo '<div class="bang-music-now-meta">';
	echo '<span class="bang-music-now-title" data-bang-music-now-title>未播放</span>';
	echo '<span class="bang-music-now-artist" data-bang-music-now-artist>未知歌手</span>';
	echo '</div>';
	echo '</div>';
	echo '<div class="bang-music-foot-center">';
	echo '<div class="bang-music-controls">';
	echo '<button class="bang-music-icon is-off" type="button" data-bang-music-shuffle aria-label="随机播放" title="随机播放">' . $ico( 'shuffle', 18 ) . '</button>';
	echo '<button class="bang-music-icon" type="button" data-bang-music-prev aria-label="上一首" title="上一首">' . $ico( 'prev', 22 ) . '</button>';
	echo '<button class="bang-music-play" type="button" data-bang-music-toggle aria-label="播放或暂停" title="播放/暂停">' . $ico( 'play', 24, 'i-play' ) . $ico( 'pause', 24, 'i-pause' ) . '</button>';
	echo '<button class="bang-music-icon" type="button" data-bang-music-next aria-label="下一首" title="下一首">' . $ico( 'next', 22 ) . '</button>';
	echo '<button class="bang-music-icon" type="button" data-bang-music-repeat aria-label="循环播放" title="循环播放">' . $ico( 'repeat', 18 ) . '</button>';
	/* 播放列表开关（列表图标） */
	echo '<button class="bang-music-icon" type="button" data-bang-music-list-toggle aria-label="播放列表" title="播放列表" aria-expanded="false">' . $ico( 'list', 20 ) . '</button>';
	echo '</div>';
	echo '<div class="bang-music-progress">';
	echo '<span class="bang-music-time" data-bang-music-cur>0:00</span>';
	echo '<input class="bang-music-seek" type="range" min="0" max="1000" value="0" step="1" data-bang-music-seek aria-label="播放进度">';
	echo '<span class="bang-music-time" data-bang-music-dur>0:00</span>';
	echo '</div>';
	echo '</div>';
	echo '</div>';
	/* 播放列表面板：浮在控制栏上方，当前曲目高亮 */
	echo '<div class="bang-music-list" data-bang-music-list hidden>';
	echo '<div class="bang-music-list-head"><span>播放列表</span><button class="bang-music-icon" type="button" data-bang-music-list-close aria-label="关闭播放列表" title="关闭">' . $ico( 'close-x', 16 ) . '</button></div>';
	echo '<div class="bang-music-list-body" data-bang-music-list-body></div>';
	echo '</div>';
	echo '</div>'; // .bang-music-panel
	// audio 元素常驻
	echo '<audio data-bang-music-audio preload="none"></audio>';
	echo '</div>'; // .bang-music-stage —— 必须闭合，否则 footer 后续常驻 DOM（分享/搜索/tabbar…）全被包进这个 hidden 容器
}

/**
 * 前端配置：只下发开关与 REST 地址，不含接口地址/密钥。
 */
function bang_music_config( $config ) {
	$config['music'] = array(
		'on'  => bang_opt( 'music_on' ) ? 1 : 0,
		'api' => esc_url_raw( rest_url( 'bang/v1/music' ) ),
	);
	return $config;
}
add_filter( 'bang_user_config', 'bang_music_config' );

/**
 * 音乐单页标记：/music/ 页且播放器开启时给 body 加 .bang-music-solo。
 * app.js 据此自动展开常驻播放器；离开该页时收起，避免空壳页露出。
 */
function bang_music_solo_body_class( $classes ) {
	if ( is_page( 'music' ) && bang_opt( 'music_on' ) ) {
		$classes[] = 'bang-music-solo';
	}
	return $classes;
}
add_filter( 'body_class', 'bang_music_solo_body_class' );
