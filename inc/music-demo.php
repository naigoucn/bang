<?php
/**
 * 【演示专用数据源】QQ 音乐（yp.nunnn.com / qq-music-api）——打包开源时整个删除本文件即可。
 *
 * 删除步骤（开源版）：
 *   1) 删除 inc/music-demo.php；
 *   2) 删掉 functions.php 里 require music-demo.php 那一行；
 *   3) 后台「音乐设置」填自己的接口地址（统一协议见 inc/music.php 顶部注释）。
 * 其余前端 / 后端代理链路无需改动。
 *
 * 数据源仅用于功能演示，歌曲版权归各权利人所有。
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* 演示 API 根地址（qq-music-api 3.3.6），仅服务端使用，不下发前端 */
if ( ! defined( 'BANG_MUSIC_QQ_API' ) ) {
	define( 'BANG_MUSIC_QQ_API', 'https://yp.nunnn.com' );
}

/**
 * 演示榜单 ID：后台填 QQ 音乐榜单 id（如 62 飙升榜 / 26 热歌榜 / 27 新歌榜）。
 * 空值或历史网易云歌单 ID 统一迁到默认飙升榜 62。
 */
function bang_music_demo_pid() {
	$pid = trim( (string) bang_opt( 'music_demo_playlist' ) );
	if ( '' === $pid || '3778678' === $pid || '374303916' === $pid || '903121725' === $pid ) {
		$pid = '62'; // QQ 音乐飙升榜
	}
	return $pid;
}

/**
 * 演示歌手名：后台填了就按这位歌手的曲目播放（演示模式专用）。
 * 走 search?type=singer 拿 singerMID，再由 singer/songs?mid= 拉该歌手曲目；
 * 留空则退回按榜单 ID 取歌。开源版删除本文件时此设置自然失效。
 */
function bang_music_demo_singer() {
	return trim( (string) bang_opt( 'music_demo_singer' ) );
}

/**
 * 演示数据源缓存 key：歌手 / 榜单两种来源分开，避免互相污染；
 * 封面 URL 前缀修正后统一加 v2，强制旧缓存失效。
 */
function bang_music_demo_cache_key() {
	$singer = bang_music_demo_singer();
	$src    = '' !== $singer ? 'singer:' . md5( $singer ) : 'pid:' . md5( bang_music_demo_pid() );
	return 'bang_music_demo_tracks_v2_' . $src;
}

/**
 * 演示 API 通用 GET：返回解码后的数组，失败返回 null。
 */
function bang_music_demo_api( $path, $params = array() ) {
	$url = rtrim( BANG_MUSIC_QQ_API, '/' ) . '/' . ltrim( $path, '/' );
	if ( ! empty( $params ) ) {
		$url = add_query_arg( $params, $url );
	}
	$body = bang_music_http_get( $url, array( 'Referer' => 'https://y.qq.com/' ) );
	if ( ! $body ) {
		return null;
	}
	$json = json_decode( $body, true );
	return is_array( $json ) ? $json : null;
}

/**
 * 拉取并缓存榜单曲目元数据（1 小时），按 step 轮转取一首。
 * 注意：播放地址 vkey 有时效，不随列表缓存，取歌时实时请求。
 * 返回结构对齐 bang_music_normalize()：id/name/artist/cover/audio/lyric。
 */
function bang_music_demo_song( $step = 0, $req_idx = 0 ) {
	/* 缓存 key 绑定榜单 ID：换榜单立即失效旧缓存，不必等 1 小时 */
	$pid       = bang_music_demo_pid();
	/* 缓存 key 区分歌手 / 榜单来源（见 bang_music_demo_cache_key） */
	$cache_key = bang_music_demo_cache_key();
	$tracks    = get_transient( $cache_key );
	if ( ! is_array( $tracks ) || empty( $tracks ) ) {
		$tracks = bang_music_demo_fetch_list();
		if ( is_wp_error( $tracks ) ) {
			return $tracks;
		}
		set_transient( $cache_key, $tracks, HOUR_IN_SECONDS );
	}

	/* 索引必须和曲目列表同源：切换榜单 / 歌手时列表 key 变了，索引也要跟着失效 */
	$idx_key     = 'bang_music_demo_idx_' . md5( $cache_key );
	$started_key = 'bang_music_demo_started_' . md5( $cache_key );
	$idx         = (int) get_transient( $idx_key );
	$count       = count( $tracks );
	if ( $count < 1 ) {
		return new WP_Error( 'bang_music_demo_empty', '演示歌单暂时为空。', array( 'status' => 502 ) );
	}

	/* 指定索引（播放列表点选）：前端传 idx 从 1 起（0 表示不指定），
	   这里要转成 0-based 数组下标；原来少减 1，点第 6 首会落到第 7 首，
	   再叠加跳过不可播曲目往后找，就变成点第 6 首播第 8 首。 */
	if ( $req_idx > 0 ) {
		$idx = ( $req_idx - 1 ) % $count;
	}

	/* 从当前索引起最多尝试 count 首：跳过版权受限（拿不到播放地址）的曲目 */
	$song = null;
	for ( $try = 0; $try < $count; $try++ ) {
		$candidate = $tracks[ ( $idx + $try ) % $count ];
		$song      = bang_music_demo_build_song( $candidate );
		if ( $song && ! empty( $song['audio'] ) ) {
			$idx = ( $idx + $try ) % $count; /* 落在真正可播的这首 */
			break;
		}
		$song = null;
	}
	if ( ! $song ) {
		return new WP_Error( 'bang_music_demo_playable', '演示歌单暂无可播放曲目。', array( 'status' => 502 ) );
	}

	/* 推进到下一首，供下次 step=1 使用 */
	$next = ( $idx + 1 ) % $count;
	set_transient( $idx_key, $next, HOUR_IN_SECONDS );
	if ( 0 === $step && 0 === (int) get_transient( $started_key ) ) {
		set_transient( $started_key, 1, HOUR_IN_SECONDS );
	}
	return bang_music_normalize( $song );
}

/**
 * 播放列表：返回榜单元数据（不含播放地址/歌词，前端据此渲染列表）。
 */
function bang_music_demo_list() {
	$pid       = bang_music_demo_pid();
	/* 缓存 key 区分歌手 / 榜单来源（见 bang_music_demo_cache_key） */
	$cache_key = bang_music_demo_cache_key();
	$tracks    = get_transient( $cache_key );
	if ( ! is_array( $tracks ) || empty( $tracks ) ) {
		$tracks = bang_music_demo_fetch_list();
		if ( is_wp_error( $tracks ) ) {
			return array();
		}
		set_transient( $cache_key, $tracks, HOUR_IN_SECONDS );
	}
	$out = array();
	foreach ( $tracks as $t ) {
		$out[] = array(
			'id'     => isset( $t['mid'] ) ? (string) $t['mid'] : '',
			'name'   => isset( $t['name'] ) ? (string) $t['name'] : '',
			'artist' => isset( $t['artist'] ) ? (string) $t['artist'] : '',
			'cover'  => isset( $t['cover'] ) ? (string) $t['cover'] : '',
		);
	}
	return $out;
}

/**
 * 由曲目元数据构造一首完整歌曲（实时取播放地址与歌词）。
 * 版权受限 / 请求失败返回 null，由上层跳到下一首。
 */
function bang_music_demo_build_song( $t ) {
	$mid = isset( $t['mid'] ) ? (string) $t['mid'] : '';
	if ( '' === $mid ) {
		return null;
	}
	$play = bang_music_demo_api( 'getMusicPlay/' . rawurlencode( $mid ) );
	$url  = '';
	if ( is_array( $play ) && isset( $play['data']['playUrl'][ $mid ] ) ) {
		$v = $play['data']['playUrl'][ $mid ];
		if ( is_array( $v ) && empty( $v['error'] ) && ! empty( $v['url'] ) ) {
			$url = (string) $v['url'];
		}
	}
	if ( '' === $url ) {
		return null;
	}
	return array(
		'id'     => $mid,
		'name'   => isset( $t['name'] ) ? (string) $t['name'] : '',
		'artist' => isset( $t['artist'] ) ? (string) $t['artist'] : '',
		'cover'  => isset( $t['cover'] ) ? (string) $t['cover'] : '',
		'audio'  => $url,
		'lyric'  => bang_music_demo_lyric( $mid ),
	);
}

/**
 * 从 QQ 音乐榜单接口解析曲目精简元数据（不含播放地址）。
 */
function bang_music_demo_fetch_list() {
	$singer = bang_music_demo_singer();
	if ( '' !== $singer ) {
		/* 按歌手取曲目：先搜歌手拿 singerMID，再用 singer/songs 拉该歌手曲目 */
		$found = bang_music_demo_api( 'search', array( 'keyword' => $singer, 'type' => 'singer' ) );
		$smid  = '';
		if ( is_array( $found ) && ! empty( $found['items'] ) && is_array( $found['items'] ) ) {
			/* 优先取名字完全一致的歌手，退而求其次取第一条 */
			$fallback = '';
			foreach ( $found['items'] as $it ) {
				$m = isset( $it['singerMID'] ) ? (string) $it['singerMID'] : '';
				if ( '' === $m ) { continue; }
				if ( '' === $fallback ) { $fallback = $m; }
				if ( isset( $it['singerName'] ) && $it['singerName'] === $singer ) {
					$smid = $m;
					break;
				}
			}
			if ( '' === $smid ) { $smid = $fallback; }
		}
		if ( '' === $smid ) {
			return new WP_Error( 'bang_music_demo_singer', '没找到这位歌手，请检查歌手名。', array( 'status' => 502 ) );
		}
		$json = bang_music_demo_api( 'singer/songs', array( 'mid' => $smid ) );
		$raw  = ( is_array( $json ) && ! empty( $json['songs'] ) && is_array( $json['songs'] ) ) ? $json['songs'] : array();
	} else {
		/* 未填歌手：走榜单 */
		$json = bang_music_demo_api( 'recommend/toplist', array( 'id' => bang_music_demo_pid() ) );
		$raw  = ( is_array( $json ) && ! empty( $json['songs'] ) && is_array( $json['songs'] ) ) ? $json['songs'] : array();
	}
	if ( empty( $raw ) ) {
		return new WP_Error( 'bang_music_demo_parse', '演示曲目解析失败。', array( 'status' => 502 ) );
	}
	$list = array();
	foreach ( $raw as $t ) {
		if ( empty( $t['mid'] ) ) {
			continue;
		}
		$artists = array();
		if ( ! empty( $t['singer'] ) && is_array( $t['singer'] ) ) {
			foreach ( $t['singer'] as $a ) {
				if ( ! empty( $a['name'] ) ) {
					$artists[] = (string) $a['name'];
				}
			}
		}
		/* 封面：QQ 音乐封面格式 = T002R300x300M000 + album.mid + .jpg。
		   album.mid 形如 004PpJGW1dmLu3（开头两位是 00），拼上 M000 后正好是 M000004Pp...。
		   注意前缀是 M000（三个 0），写成 M0000 会多一个 0 导致 404。 */
		$cover = '';
		if ( ! empty( $t['album']['mid'] ) ) {
			$cover = 'https://y.gtimg.cn/music/photo_new/T002R300x300M000' . rawurlencode( (string) $t['album']['mid'] ) . '.jpg';
		}
		$list[] = array(
			'mid'    => (string) $t['mid'],
			'name'   => isset( $t['name'] ) ? (string) $t['name'] : '',
			'artist' => implode( ' / ', $artists ),
			'cover'  => $cover,
		);
		if ( count( $list ) >= 30 ) {
			break;
		}
	}
	return $list;
}

/**
 * 取 LRC 歌词文本（失败回退空串，前端会显示「暂无歌词」）。
 * QQ 接口 response.lyric 为主歌词、response.trans 为译文，译文行以 @ 前缀合入主轨。
 */
function bang_music_demo_lyric( $mid ) {
	$json = bang_music_demo_api( 'getLyric', array( 'songmid' => $mid ) );
	if ( ! is_array( $json ) || ! isset( $json['response']['lyric'] ) || ! is_string( $json['response']['lyric'] ) ) {
		return '';
	}
	$main = $json['response']['lyric'];
	$trans = ( isset( $json['response']['trans'] ) && is_string( $json['response']['trans'] ) ) ? $json['response']['trans'] : '';
	if ( '' === trim( $trans ) ) {
		return $main;
	}
	$map = array();
	foreach ( preg_split( '/\r?\n/', $trans ) as $line ) {
		if ( preg_match( '/\[(\d{1,3}):(\d{2})(?:[.:](\d{1,3}))?\](.*)/', $line, $m ) ) {
			$t   = (int) $m[1] * 60 + (int) $m[2] + ( ! empty( $m[3] ) ? (int) $m[3] / ( strlen( $m[3] ) === 3 ? 1000 : 100 ) : 0 );
			$txt = trim( $m[4] );
			if ( '' !== $txt ) {
				$map[ sprintf( '%.2f', $t ) ] = $txt;
			}
		}
	}
	if ( empty( $map ) ) {
		return $main;
	}
	$out = array();
	foreach ( preg_split( '/\r?\n/', $main ) as $line ) {
		$out[] = $line;
		if ( preg_match( '/\[(\d{1,3}):(\d{2})(?:[.:](\d{1,3}))?\]/', $line, $mm ) ) {
			$t   = (int) $mm[1] * 60 + (int) $mm[2] + ( ! empty( $mm[3] ) ? (int) $mm[3] / ( strlen( $mm[3] ) === 3 ? 1000 : 100 ) : 0 );
			$key = sprintf( '%.2f', $t );
			$clean = trim( preg_replace( '/\[[^\]]+\]/', '', $line ) );
			if ( '' !== $clean && isset( $map[ $key ] ) ) {
				$stamp = '[' . $mm[1] . ':' . $mm[2] . ( ! empty( $mm[3] ) ? '.' . $mm[3] : '' ) . ']';
				$out[] = $stamp . '@' . $map[ $key ];
			}
		}
	}
	return implode( "\n", $out );
}
