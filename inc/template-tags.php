<?php
/**
 * 主题输出函数：插画、卡片视觉、阅读层、数据负载
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 内联几何插画。演示内容不依赖外部图片，之后上传特色图会自动覆盖。
 */
function pk_art_map() {
	return array(
		'mountain' => '<svg viewBox="0 0 800 500" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="山与落日"><rect width="800" height="500" fill="#DFE8EC"/><circle cx="578" cy="158" r="74" fill="#EBD6BB"/><path d="M0 352 L188 202 L332 328 L472 176 L800 396 L800 500 L0 500Z" fill="#ADC3CD"/><path d="M0 406 L244 282 L404 398 L592 262 L800 428 L800 500 L0 500Z" fill="#829FAD"/><path d="M0 462 L800 462 L800 500 L0 500Z" fill="#6C8898"/></svg>',
		'street'   => '<svg viewBox="0 0 800 500" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="街角的窗"><rect width="800" height="500" fill="#EEE9E0"/><rect x="60" y="128" width="228" height="372" fill="#D8D0C1"/><rect x="306" y="62" width="186" height="438" fill="#C9C0AF"/><rect x="510" y="168" width="238" height="332" fill="#DFD7C9"/><g fill="#F2EDE3"><rect x="96" y="176" width="52" height="70"/><rect x="184" y="176" width="52" height="70"/><rect x="96" y="286" width="52" height="70"/><rect x="184" y="286" width="52" height="70"/><rect x="336" y="112" width="48" height="64"/><rect x="414" y="112" width="48" height="64"/><rect x="336" y="216" width="48" height="64"/><rect x="414" y="216" width="48" height="64"/><rect x="548" y="212" width="54" height="68"/><rect x="654" y="212" width="54" height="68"/><rect x="548" y="320" width="54" height="68"/><rect x="654" y="320" width="54" height="68"/></g><path d="M0 460H800" stroke="#B9AF9E" stroke-width="3"/><path d="M272 500V128" stroke="#C4BAA9" stroke-width="2"/><path d="M492 500V168" stroke="#C4BAA9" stroke-width="2"/></svg>',
		'still'    => '<svg viewBox="0 0 800 500" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="静物"><rect width="800" height="500" fill="#EAE4D8"/><circle cx="252" cy="176" r="112" fill="#D8CAB0"/><path d="M436 424V226c0-28 18-44 44-44h40c26 0 44 16 44 44v198Z" fill="#B9A688"/><rect x="472" y="132" width="56" height="52" rx="6" fill="#A89675"/><rect x="110" y="424" width="580" height="7" rx="3" fill="#C9BCA3"/><rect x="110" y="446" width="580" height="2" fill="#D6CBB6"/></svg>',
		'wave'     => '<svg viewBox="0 0 800 500" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="海面的波纹"><rect width="800" height="500" fill="#DEE8E4"/><g fill="none" stroke="#9CBDB3" stroke-width="3"><path d="M0 170 Q200 132 400 170 T800 170"/><path d="M0 220 Q200 182 400 220 T800 220"/><path d="M0 270 Q200 232 400 270 T800 270"/><path d="M0 320 Q200 282 400 320 T800 320"/></g><path d="M0 380 Q200 342 400 380 T800 380" fill="none" stroke="#7FA79B" stroke-width="3"/><circle cx="620" cy="112" r="46" fill="#EADFC9"/></svg>',
		'dusk'     => '<svg viewBox="0 0 800 500" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="黄昏站台"><rect width="800" height="500" fill="#E9DDD2"/><circle cx="596" cy="196" r="88" fill="#D9B79A"/><rect x="0" y="330" width="800" height="170" fill="#C9B9A8"/><rect x="0" y="88" width="800" height="12" fill="#B9A694"/><path d="M92 88V330M708 88V330" stroke="#B9A694" stroke-width="8"/><path d="M0 392H800" stroke="#B3A08D" stroke-width="3"/><path d="M0 434H800" stroke="#B3A08D" stroke-width="3"/><rect x="150" y="206" width="164" height="124" rx="4" fill="#EFE6DC"/><rect x="486" y="226" width="122" height="104" rx="4" fill="#E4D6C8"/></svg>',
		'market'   => '<svg viewBox="0 0 800 500" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="市场的摊位"><rect width="800" height="500" fill="#F0EBE0"/><path d="M0 110H800V150H0Z" fill="#C9B79B"/><path d="M60 150L100 60H300L340 150Z" fill="#D8C7AC"/><path d="M460 150L500 60H700L740 150Z" fill="#DCCBB1"/><rect x="70" y="290" width="290" height="24" rx="6" fill="#B9A98D"/><rect x="440" y="290" width="290" height="24" rx="6" fill="#B9A98D"/><g fill="#A9BDA1"><circle cx="140" cy="252" r="26"/><circle cx="206" cy="252" r="26"/><circle cx="272" cy="252" r="26"/></g><g fill="#D3A98C"><circle cx="510" cy="252" r="26"/><circle cx="576" cy="252" r="26"/><circle cx="642" cy="252" r="26"/></g><path d="M0 380H800V500H0Z" fill="#E1D8C8"/><path d="M0 380H800" stroke="#C8BCA8" stroke-width="3"/></svg>',
		'rain'     => '<svg viewBox="0 0 800 500" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="雨后的巷子"><rect width="800" height="500" fill="#DCE3E5"/><rect x="0" y="60" width="240" height="440" fill="#B9C4C7"/><rect x="560" y="20" width="240" height="480" fill="#C6D0D2"/><rect x="240" y="180" width="320" height="320" fill="#AEBBBE"/><g fill="#E8EEEF"><rect x="290" y="230" width="70" height="90"/><rect x="440" y="230" width="70" height="90"/></g><g stroke="#EFF3F4" stroke-width="2" opacity=".8"><path d="M60 0L20 500M160 0L120 500M300 0L260 500M420 0L380 500M560 0L520 500M680 0L640 500M780 0L740 500"/></g><path d="M0 440H800V500H0Z" fill="#A6B4B7"/><path d="M240 460H560" stroke="#E6ECEC" stroke-width="3" opacity=".6"/></svg>',
		'type'     => '<svg viewBox="0 0 800 500" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="文字与网格"><rect width="800" height="500" fill="#EFEAE1"/><g stroke="#DCD3C4" stroke-width="1"><path d="M0 100H800M0 200H800M0 300H800M0 400H800M160 0V500M320 0V500M480 0V500M640 0V500"/></g><text x="400" y="330" text-anchor="middle" font-family="Georgia,serif" font-size="280" fill="#B9AC95">A</text><circle cx="646" cy="120" r="16" fill="#C8B79C"/></svg>',
	);
}

/**
 * 取插画：文章可用 _pk_art 指定，否则按 ID 轮转
 */
function pk_art_for( $post_id, $offset = 0 ) {
	$map  = pk_art_map();
	$keys = array_keys( $map );
	$key  = get_post_meta( $post_id, '_pk_art', true );
	if ( $offset > 0 || ! $key || ! isset( $map[ $key ] ) ) {
		$base = array_search( $key, $keys, true );
		if ( false === $base ) {
			$base = ( (int) $post_id - 1 ) % count( $keys );
		}
		$key = $keys[ ( $base + $offset ) % count( $keys ) ];
	}
	return $map[ $key ];
}

/**
 * 卡片类型（来自文章格式）
 */
function pk_format_key( $post_id ) {
	$format = get_post_format( $post_id );
	if ( ! $format ) {
		return 'standard';
	}
	if ( in_array( $format, array( 'image', 'gallery' ), true ) ) {
		return 'image';
	}
	return in_array( $format, array( 'aside', 'quote' ), true ) ? $format : 'standard';
}

function pk_kind_label( $format_key ) {
	$labels = array(
		'standard' => '文章',
		'image'    => '图集',
		'aside'    => '短动态',
		'quote'    => '随笔',
	);
	return isset( $labels[ $format_key ] ) ? $labels[ $format_key ] : '文章';
}

/**
 * 纯文本摘录
 */
function pk_plain_excerpt( $post_id ) {
	$excerpt = get_post_field( 'post_excerpt', $post_id );
	if ( ! $excerpt ) {
		$excerpt = wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $post_id ) ) );
	}
	$excerpt = trim( preg_replace( '/\s+/u', ' ', $excerpt ) );
	return mb_substr( $excerpt, 0, 58, 'UTF-8' );
}

/**
 * 估算阅读时长
 */
function pk_read_time( $post_id ) {
	$text = wp_strip_all_tags( get_post_field( 'post_content', $post_id ) );
	$len  = mb_strlen( preg_replace( '/\s/u', '', $text ), 'UTF-8' );
	return max( 1, (int) ceil( $len / 350 ) ) . ' 分钟';
}

/**
 * 首个分类名
 */
function pk_topic( $post_id ) {
	$cats = get_the_category( $post_id );
	return ! empty( $cats ) ? $cats[0]->name : '未分类';
}

/**
 * 全部分类 slug，供前端筛选
 */
function pk_topic_slugs( $post_id ) {
	$slugs = array();
	foreach ( get_the_category( $post_id ) as $cat ) {
		$slugs[] = $cat->slug;
	}
	return $slugs;
}

/**
 * 正文 HTML + 目录数据
 *
 * 正文里的 h2 / h3 统一补上 pk-h-N 锚点，侧栏「文章目录」拿同一份数据渲染，
 * 保证正文与服务端输出的侧栏目录 id 一致（也覆盖 Swup 拉取的整页 HTML）。
 * 过滤后再插 id：wp_kses_post 之后自定义属性不会被剥掉。
 *
 * @param int $post_id 文章 ID。
 * @return array{html:string,toc:array} html 为带锚点的正文，toc 为 [{id,level,text}]。
 */
function pk_heading_data( $post_id ) {
	static $cache = array();
	$post_id      = (int) $post_id;
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	/* 0.13.20 全站内容解锁：命中时整篇正文换成锁定卡片，原文的 the_content
	   过滤器根本不执行，首页 #pk-data 内联层 / 文章目录也拿不到任何片段。 */
	$locked = pk_unlock_heading_override( $post_id );
	if ( false !== $locked ) {
		$cache[ $post_id ] = $locked;
		return $cache[ $post_id ];
	}

	/* 0.13.22：首页内联阅读层一次渲染多篇文章，把当前文章 ID 传给阅读解锁的
	   区块 / 简码，避免它们误用主查询的 get_the_ID() 算错点赞、评论、密码状态。 */
	$prev_unlock_post = isset( $GLOBALS['pk_unlock_render_post'] ) ? $GLOBALS['pk_unlock_render_post'] : null;
	$GLOBALS['pk_unlock_render_post'] = $post_id;
	$content = get_post_field( 'post_content', $post_id );
	$html    = wp_kses_post( apply_filters( 'the_content', $content ) );
	if ( null === $prev_unlock_post ) {
		unset( $GLOBALS['pk_unlock_render_post'] );
	} else {
		$GLOBALS['pk_unlock_render_post'] = $prev_unlock_post;
	}
	$toc     = array();
	$seq     = 0;

	$html = preg_replace_callback(
		'/<h([23])([^>]*)>(.*?)<\/h\1>/is',
		function ( $m ) use ( &$toc, &$seq ) {
			$text = trim( wp_strip_all_tags( $m[3] ) );
			if ( '' === $text ) {
				return $m[0];
			}
			++$seq;
			$id    = 'pk-h-' . $seq;
			$attrs = preg_replace( '/\sid="[^"]*"/i', '', $m[2] );
			$toc[] = array(
				'id'    => $id,
				'level' => (int) $m[1],
				'text'  => $text,
			);
			return '<h' . $m[1] . $attrs . ' id="' . $id . '">' . $m[3] . '</h' . $m[1] . '>';
		},
		$html
	);

	$cache[ $post_id ] = array(
		'html' => $html,
		'toc'  => $toc,
	);
	return $cache[ $post_id ];
}

/**
 * 正文 HTML（走 the_content 过滤器，保留后台排版，标题带锚点）
 */
function pk_content_html( $post_id ) {
	$data = pk_heading_data( $post_id );
	return $data['html'];
}

/**
 * 作者信息卡片（文章页右侧栏）
 *
 * 传 $post_id 取本文作者；不传则回退到发文最多的作者。
 */
function pk_author_card_html( $post_id = 0 ) {
	$user_id = 0;
	if ( $post_id ) {
		$user_id = (int) get_post_field( 'post_author', $post_id );
	} elseif ( is_singular() ) {
		$user_id = (int) get_post_field( 'post_author', get_queried_object_id() );
	}
	if ( ! $user_id ) {
		$authors = get_users(
			array(
				'number'  => 1,
				'orderby' => 'post_count',
				'order'   => 'DESC',
				'who'     => 'authors',
			)
		);
		if ( ! empty( $authors ) ) {
			$user_id = (int) $authors[0]->ID;
		}
	}
	if ( ! $user_id ) {
		return '';
	}

	$name = get_the_author_meta( 'display_name', $user_id );
	if ( ! $name ) {
		$name = get_the_author_meta( 'user_login', $user_id );
	}
	$bio       = trim( wp_strip_all_tags( (string) get_the_author_meta( 'description', $user_id ) ) );
	$count     = (int) count_user_posts( $user_id, 'post', true );
	$url       = get_author_posts_url( $user_id );
	$mail      = (string) get_the_author_meta( 'user_email', $user_id );

	/* 签名来源（0.13.28）：profile=作者资料 / custom=自定义 / api=一言接口。
	   api 模式先输出兜底文案 + data 属性，由 app.js 拉取接口后替换。 */
	$bio_mode = (string) pk_opt( 'author_bio_mode' );
	$bio_api  = trim( (string) pk_opt( 'author_bio_api' ) );
	if ( 'custom' === $bio_mode ) {
		$bio = trim( (string) pk_opt( 'author_bio_custom' ) );
	} elseif ( 'api' === $bio_mode ) {
		$bio_api_fallback = trim( (string) pk_opt( 'author_bio_fallback' ) );
		if ( '' === $bio_api_fallback ) {
			$bio_api_fallback = $bio; // 接口兜底回退到作者资料简介
		}
		$bio = $bio_api_fallback;
	}

	/* 头像：用户资料里的自定义地址 → 主题 Logo（演示为 cdn.naigou.cn/logo.png）→ 名字首字徽标。
	   不用 Gravatar，内网 / 离线环境不会破图。 */
	/* 0.13.0：后台「作者头像」最优先，其次用户资料，最后站点 Logo */
	$avatar = pk_safe_comment_site( (string) pk_opt( 'author_avatar' ) );
	if ( ! $avatar ) {
		$avatar = pk_safe_comment_site( (string) get_the_author_meta( 'pk_avatar', $user_id ) );
	}
	if ( ! $avatar ) {
		$avatar = pk_safe_comment_site( (string) pk_opt( 'logo_url' ) );
	}
	$initial = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1, 'UTF-8' ) : substr( $name, 0, 1 );

	$html  = '<section class="pc-author" id="pk-author-card" aria-label="作者信息">';
	if ( $avatar ) {
		/* 头像紧邻名字，对读屏是重复信息 → 当作装饰图，alt 留空 */
		$html .= '<span class="pc-avatar"><img src="' . esc_url( $avatar ) . '" alt=""'
			. ' width="56" height="56" loading="lazy" decoding="async"></span>';
	} else {
		$html .= '<span class="pc-avatar is-text" aria-hidden="true">' . esc_html( $initial ) . '</span>';
	}
	$html .= '<a class="pc-author-name" href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
	if ( $count > 0 ) {
		$html .= '<span class="pc-author-stat">' . esc_html( $count . ' 篇文章' ) . '</span>';
	}
	if ( $bio ) {
		$bio_attrs = '';
		if ( 'api' === $bio_mode && $bio_api ) {
			$bio_attrs = ' data-pk-hitokoto="' . esc_url( $bio_api ) . '"';
		}
		$html .= '<p class="pc-author-bio"' . $bio_attrs . '>' . esc_html( $bio ) . '</p>';
	}

	/* 社交图标（0.13.28）：按后台填的链接输出，留空的平台不显示。
	   全部走 pk_icon() 本地 SVG，不引 CDN、不用 emoji。 */
	$socials = array(
		'qq'        => array( 'qq', __( 'QQ', 'pianke' ) ),
		'wechat'    => array( 'wechat', __( '微信', 'pianke' ) ),
		'douyin'    => array( 'douyin', __( '抖音', 'pianke' ) ),
		'x'         => array( 'x-social', __( 'X', 'pianke' ) ),
		'weibo'     => array( 'weibo', __( '微博', 'pianke' ) ),
		'github'    => array( 'github', __( 'GitHub', 'pianke' ) ),
		'bilibili'  => array( 'bilibili', __( '哔哩哔哩', 'pianke' ) ),
	);
	$social_html = '';
	foreach ( $socials as $social_key => $social ) {
		$link = trim( (string) pk_opt( 'social_' . $social_key ) );
		if ( '' === $link ) {
			continue;
		}
		$safe_link = pk_safe_comment_site( $link );
		if ( ! $safe_link ) {
			continue;
		}
		$social_html .= '<a class="pc-author-social" href="' . esc_url( $safe_link ) . '"'
			. ' target="_blank" rel="noopener noreferrer"'
			. ' aria-label="' . esc_attr( $social[1] ) . '" title="' . esc_attr( $social[1] ) . '">'
			. pk_icon( $social[0], 17 ) . '</a>';
	}
	if ( $social_html ) {
		$html .= '<div class="pc-author-socials">' . $social_html . '</div>';
	}

	if ( $mail ) {
		$html .= '<a class="pc-author-contact" href="mailto:' . esc_attr( antispambot( $mail ) ) . '">'
			. pk_icon( 'mail', 15 ) . '<span>联系我</span></a>';
	}
	$html .= '</section>';

	return $html;
}

/**
 * 文章目录（仅普通文章、正文含 h2/h3 时输出）
 */
function pk_toc_html( $post_id ) {
	$post_id = (int) $post_id;
	if ( ! $post_id || 'post' !== get_post_type( $post_id ) ) {
		return '';
	}
	$data = pk_heading_data( $post_id );
	if ( empty( $data['toc'] ) ) {
		return '';
	}

	$html  = '<nav class="pk-toc" aria-label="文章目录">';
	/* 用 <p> 不用标题：目录在正文 h1 之前，h2 会造成标题层级跳级 */
	$html .= '<p class="pc-pop-title">目录</p>';
	$html .= '<ul class="pk-toc-list">';
	foreach ( $data['toc'] as $item ) {
		$html .= '<li class="is-h' . (int) $item['level'] . '">'
			. '<a href="#' . esc_attr( $item['id'] ) . '" data-no-swup>' . esc_html( $item['text'] ) . '</a></li>';
	}
	$html .= '</ul></nav>';
	return $html;
}

/**
 * 文章真实图片：特色图优先，其次是挂在本文上的图片附件。
 * 只收真实附件——内联插画不算图片，只有当这里为空时才拿插画兜底。
 */
function pk_post_images( $post_id ) {
	static $cache = array();
	$post_id = (int) $post_id;
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}
	$ids   = array();
	$thumb = (int) get_post_thumbnail_id( $post_id );
	if ( $thumb ) {
		$ids[] = $thumb;
	}
	foreach ( get_attached_media( 'image', $post_id ) as $att ) {
		$id = (int) $att->ID;
		if ( ! in_array( $id, $ids, true ) ) {
			$ids[] = $id;
		}
	}
	$cache[ $post_id ] = $ids;
	return $ids;
}

/**
 * 输出真实图片标签。详情头图用大图（并提前加载，避免 LCP 延迟），
 * 卡片用中等尺寸做预览，列表不必拉原图。
 */
function pk_image_html( $attachment_id, $size, $post_id, $eager = false ) {
	$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	if ( ! $alt ) {
		$alt = get_the_title( $post_id );
	}
	$attr = array(
		'alt'      => $alt,
		'loading'  => $eager ? 'eager' : 'lazy',
		'decoding' => 'async',
	);
	if ( $eager ) {
		$attr['fetchpriority'] = 'high';
	}
	return wp_get_attachment_image( $attachment_id, $size, false, $attr );
}

/**
 * 卡片 / 详情头图。真实图片优先；一张真实图片都没有时，才退回内联插画。
 */
function pk_visual( $post_id, $detail = false ) {
	$base   = $detail ? 'detail-hero' : 'card-visual';
	$format = pk_format_key( $post_id );
	$ids    = pk_post_images( $post_id );
	$size   = $detail ? 'full' : 'medium_large';

	if ( 'quote' === $format ) {
		$quote = get_post_meta( $post_id, '_pk_quote', true );
		if ( ! $quote ) {
			$quote = pk_plain_excerpt( $post_id );
		}
		return '<div class="' . esc_attr( $base ) . ' quote"><span>「' . esc_html( $quote ) . '」</span></div>';
	}

	if ( 'aside' === $format && count( $ids ) > 1 ) {
		$frames = array();
		foreach ( array_slice( $ids, 0, 3 ) as $id ) {
			$frames[] = pk_image_html( $id, $detail ? 'full' : 'medium', $post_id, $detail );
		}

		// 卡片仍是三宫格；文章内页改为全宽封面带，自动缓慢横向滚动。
		if ( ! $detail ) {
			$inner = '';
			foreach ( $frames as $frame ) {
				$inner .= '<div class="thumb">' . $frame . '</div>';
			}
			return '<div class="' . esc_attr( $base ) . ' thumbs">' . $inner . '</div>';
		}

		// 复制一组同样的帧，配合 translateX(-50%) 做无缝循环。
		$slides = '';
		foreach ( array( false, true ) as $clone ) {
			foreach ( $frames as $frame ) {
				$slides .= '<div class="hero-slide"' . ( $clone ? ' aria-hidden="true"' : '' ) . '>' . $frame . '</div>';
			}
		}
		return '<div class="detail-hero marquee" role="img" aria-label="图集，自动缓慢滚动">'
			. '<div class="hero-track">' . $slides . '</div></div>';
	}

	$image = $ids ? pk_image_html( $ids[0], $size, $post_id, $detail ) : '';
	return '<div class="' . esc_attr( $base ) . '">' . ( $image ? $image : pk_art_for( $post_id ) ) . '</div>';
}

/**
 * 评论者个人网站：只接受 http / https，挡掉 javascript: / data: / 协议相对地址。
 * 中转页也复用本函数校验，避免 ?pk_go= 变成任意跳转。
 */
function pk_safe_comment_site( $url ) {
	$url = esc_url_raw( trim( (string) $url ) );
	if ( ! $url ) {
		return '';
	}
	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) ) {
		return '';
	}
	if ( ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ) {
		return '';
	}
	return $url;
}

/**
 * 外链中转地址：评论者网站统一走 ?pk_go= 确认页，由评论图标新标签打开。
 */
function pk_go_url( $url ) {
	/* 手动编码：目标地址本身可能带 ? / & / #，不能让它们混进外层 query */
	return home_url( '/' ) . '?pk_go=' . rawurlencode( $url );
}

/**
 * 把评论框排到所有字段之前，其余字段（完善信息弹窗）由 CSS / JS 控制显隐。
 */
function pk_comment_fields_order( $fields ) {
	if ( ! isset( $fields['comment'] ) ) {
		return $fields;
	}
	$comment = $fields['comment'];
	unset( $fields['comment'] );
	return array( 'comment' => $comment ) + $fields;
}

/**
 * 文章底部版权声明：作者 / 首发链接 / 许可协议 / 补充说明。
 * 后台「Bang 设置 → 常规」可关闭，也可换许可与附加说明。
 */
function pk_copyright_html( $post_id ) {
	if ( ! pk_opt( 'copyright_on' ) ) {
		return '';
	}
	$author_id = (int) get_post_field( 'post_author', $post_id );
	$author    = get_the_author_meta( 'display_name', $author_id );
	if ( ! $author ) {
		$author = get_bloginfo( 'name' );
	}
	$site  = get_bloginfo( 'name' );
	$home  = home_url( '/' );
	$link  = get_permalink( $post_id );
	$date  = get_the_date( 'Y.m.d', $post_id );
	$title = get_the_title( $post_id );

	$license = pk_opt( 'copyright_license' );
	if ( 'by' === $license ) {
		$license_line = '本文采用 <a href="https://creativecommons.org/licenses/by/4.0/deed.zh-hans" target="_blank" rel="noopener noreferrer nofollow">知识共享 署名 4.0 国际许可协议</a>，转载请保留作者署名与原文链接。';
	} elseif ( 'all-rights' === $license ) {
		$license_line = '版权所有，未经授权不得以任何形式转载、摘编或用于商业用途。';
	} elseif ( 'none' === $license ) {
		$license_line = '';
	} else {
		$license_line = '本文采用 <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.zh-hans" target="_blank" rel="noopener noreferrer nofollow">知识共享 署名-非商业性使用-相同方式共享 4.0 国际许可协议</a>，转载请保留作者署名与原文链接，且不得用于商业用途。';
	}

	$note    = trim( (string) pk_opt( 'copyright_note' ) );
	$tagline = trim( (string) pk_opt( 'footer_text' ) );
	$note_html = $note ? '<p class="pk-copyright-note">' . nl2br( esc_html( $note ) ) . '</p>' : '';

	return '<section class="pk-copyright" aria-label="版权声明">'
		. '<h2 class="pk-copyright-title">' . pk_icon( 'info', 15 ) . '<span>版权声明</span></h2>'
		. '<p>《' . esc_html( $title ) . '》由 <strong>' . esc_html( $author ) . '</strong> 原创，'
		. '首发于 <a href="' . esc_url( $home ) . '">' . esc_html( $site ) . '</a>'
		. '（<a href="' . esc_url( $link ) . '">原文链接</a>），发表于 ' . esc_html( $date ) . '。</p>'
		. ( $license_line ? '<p>' . $license_line . '</p>' : '' )
		. $note_html
		. '<div class="pk-copyright-meta">&copy; ' . esc_html( gmdate( 'Y' ) ) . ' ' . esc_html( $site )
		. ( $tagline ? ' &middot; ' . esc_html( $tagline ) : '' ) . '</div>'
		. '</section>';
}

/**
 * 单条评论的 <li> 片段。
 *
 * @param WP_Comment $item          评论对象。
 * @param string     $reply_to      若这是回复，父评论的作者名。
 * @param string     $children_html 子回复的 <ol> 片段，插在 .comment-main 末尾。
 */
function pk_comment_item_html( $item, $reply_to = '', $children_html = '' ) {
	$site      = pk_safe_comment_site( $item->comment_author_url );
	$site_link = '';
	if ( $site ) {
		$host      = wp_parse_url( $site, PHP_URL_HOST );
		$site_link = '<a class="comment-site" href="' . esc_url( pk_go_url( $site ) ) . '"'
			. ' target="_blank" rel="noopener noreferrer nofollow"'
			. ' aria-label="访问 ' . esc_attr( $item->comment_author ) . ' 的个人网站"'
			. ' title="' . esc_attr( $host ) . '">' . pk_icon( 'external-link', 13 ) . '</a>';
	}
	/* 楼主（文章作者）的评论靠右、实心气泡；访客靠左、浅灰气泡，像聊天记录 */
	$owner_id = (int) get_post_field( 'post_author', $item->comment_post_ID );
	$is_owner = ( (int) $item->user_id && (int) $item->user_id === $owner_id )
		|| ( ! (int) $item->user_id && $owner_id && $item->comment_author_email && $item->comment_author_email === get_the_author_meta( 'user_email', $owner_id ) );
	$initial  = mb_substr( (string) $item->comment_author, 0, 1, 'UTF-8' );
	$cid      = (int) $item->comment_ID;
	$reply_label = $reply_to
		? '<span class="comment-reply-name">回复 @' . esc_html( $reply_to ) . '</span>'
		: '';
	/* 回复入口：只写 data 属性，事件委托在 app.js，Swup 换页后无需重新绑定 */
	$actions = '<div class="comment-actions">'
		. '<button type="button" class="comment-reply-btn" data-pk-reply="' . esc_attr( $cid ) . '"'
		. ' data-pk-reply-author="' . esc_attr( $item->comment_author ) . '"'
		. ' aria-label="回复 ' . esc_attr( $item->comment_author ) . ' 的评论">'
		. pk_icon( 'comment', 12 ) . '<span>回复</span></button></div>';

	return '<li class="comment-item' . ( $is_owner ? ' is-owner' : '' ) . '"'
		. ' data-comment-id="' . esc_attr( $cid ) . '" data-comment-parent="' . esc_attr( (int) $item->comment_parent ) . '">'
		. '<span class="comment-avatar" aria-hidden="true">' . esc_html( $initial ) . '</span>'
		. '<div class="comment-main">'
		. '<div class="comment-head"><span class="comment-author"><strong>' . esc_html( $item->comment_author ) . '</strong>' . $site_link . '</span>' . $reply_label . '</div>'
		. '<div class="comment-bubble"><div class="comment-text">' . nl2br( esc_html( $item->comment_content ) ) . '</div>'
		. '<time datetime="' . esc_attr( mysql2date( 'c', $item->comment_date ) ) . '">' . esc_html( mysql2date( 'Y.m.d H:i', $item->comment_date ) ) . '</time></div>'
		. $actions
		. $children_html
		. '</div></li>';
}

/**
 * 评论树：按 comment_parent 组织上下级回复。
 * 首屏、AJAX 列表、提交成功后刷新三处共用，保证结构一致。
 * 0.13.19：最多两级（抖音式）——一级评论 + 二级回复；一级评论下的所有后代
 * （含三级、四级…）一律平铺进同一个 .comment-children，作为二级 li 的兄弟，
 * DOM 里绝不出现第三层 ol。每条二级回复保留「回复 @某人」指向被回复者。
 * 固定 2 层，不受后台 thread_comments_depth 影响；$seen 防止脏数据成环。
 */
function pk_comments_list_html( $post_id ) {
	$list = get_comments(
		array(
			'post_id' => $post_id,
			'status'  => 'approve',
			'type'    => 'comment',
			'order'   => 'ASC',
			'number'  => 200,
		)
	);
	if ( ! $list ) {
		return '';
	}

	$by_id    = array();
	$children = array();
	$roots    = array();
	$names    = array();
	foreach ( $list as $item ) {
		$cid           = (int) $item->comment_ID;
		$by_id[ $cid ] = $item;
		$names[ $cid ] = $item->comment_author;
	}
	foreach ( $list as $item ) {
		$pid = (int) $item->comment_parent;
		if ( $pid && isset( $by_id[ $pid ] ) && $pid !== (int) $item->comment_ID ) {
			$children[ $pid ][] = $item;
		} else {
			$roots[] = $item;
		}
	}
	/* 全部成环（脏数据）时兜底按平铺输出，避免评论区整块空白 */
	if ( ! $roots ) {
		$roots = $list;
	}

	/* 0.13.19：固定两级。一级评论 + 一个 .comment-children 装下全部后代回复，
	   更深的回复平铺为二级 li 的兄弟，不再往下嵌 ol。 */
	$html = '';
	$seen = array();
	foreach ( $roots as $item ) {
		$cid = (int) $item->comment_ID;
		if ( isset( $seen[ $cid ] ) ) {
			continue;
		}
		$seen[ $cid ] = 1;

		$kids    = isset( $children[ $cid ] ) ? $children[ $cid ] : array();
		$flat    = array();
		$visited = array();
		if ( $kids ) {
			pk_comment_descendants( $kids, $children, $flat, $visited );
		}

		$sub = '';
		foreach ( $flat as $kid ) {
			$kid_id = (int) $kid->comment_ID;
			if ( isset( $seen[ $kid_id ] ) ) {
				continue;
			}
			$seen[ $kid_id ] = 1;
			$pid  = (int) $kid->comment_parent;
			$name = ( $pid && isset( $names[ $pid ] ) ) ? $names[ $pid ] : '';
			/* 二级 li 统一不带子 ol：更深的回复都在这里平铺成兄弟 */
			$sub .= pk_comment_item_html( $kid, $name, '' );
		}
		if ( $sub ) {
			$sub = '<ol class="comment-children">' . $sub . '</ol>';
		}
		$html .= pk_comment_item_html( $item, '', $sub );
	}
	return $html;
}

function pk_comment_descendants( $items, $children, &$out, &$visited ) {
	foreach ( $items as $item ) {
		$cid = (int) $item->comment_ID;
		if ( isset( $visited[ $cid ] ) ) {
			continue;
		}
		$visited[ $cid ] = 1;
		$out[]           = $item;
		if ( ! empty( $children[ $cid ] ) ) {
			pk_comment_descendants( $children[ $cid ], $children, $out, $visited );
		}
	}
}

/**
 * 评论区：已审核评论列表 + 单输入框的 WordPress 原生评论表单。
 * 0.13.19：站点已有登录体系，评论身份一律由登录态提供，不再输出「完善信息 /
 * 个人信息」按钮与弹窗；未登录时只在表单内给一条登录引导，提交统一走 AJAX。
 */
function pk_comments_html( $post_id ) {
	if ( ! pk_opt( 'show_comments' ) ) {
		return '';
	}
	$count     = (int) get_comments_number( $post_id );
	$list_html = comments_open( $post_id ) ? pk_comments_list_html( $post_id ) : '';
	$is_empty  = ( '' === $list_html );
	/* 评论改为右侧抽屉：头（标题 + 关闭）/ 中（滚动气泡列表）/ 尾（输入框）三段 */
	$html  = '<section class="detail-comments' . ( $is_empty ? ' is-empty' : '' ) . '"'
		. ' id="pk-comments" data-pk-post-id="' . (int) $post_id . '"'
		. ' role="dialog" aria-modal="true" aria-labelledby="pk-comments-title" aria-label="评论">';
	$html .= '<span class="comments-grab" aria-hidden="true"></span>';
	$html .= '<div class="comments-bar">'
		. '<h2 class="comments-title" id="pk-comments-title">评论 <span class="pk-comment-count">' . esc_html( $count ) . '</span></h2>'
		. '<button type="button" class="comments-close" data-pk-comments-close aria-label="关闭评论" title="关闭">'
		. pk_icon( 'x', 18 ) . '</button>'
		. '</div>';
	$html .= '<div class="comments-scroll">';

	if ( ! comments_open( $post_id ) ) {
		return $html . '<p class="comment-empty" data-pk-empty>评论已关闭。</p></div></section>';
	}

	/* 列表始终输出（空或带条目），空状态由 .is-empty 切换；pjax 加载后会被覆盖 */
	$html .= '<ol class="comment-list">' . $list_html . '</ol>';
	$html .= '<p class="comment-empty" data-pk-empty>还没有评论，来说两句。</p>';
	$html .= '</div>';

	/* 0.13.19：站点已有登录体系，评论身份一律由登录态提供。
	   不再输出「完善信息 / 个人信息」按钮与弹窗，游客也不再自己填称呼、邮箱、网站。
	   未登录时只给一条登录引导，点它就地打开登录弹窗（app.js 的 data-pk-auth-open）；
	   data-pk-guest 供提交时判定游客态，直接拦下并引导登录。 */
	$extra = '';
	if ( ! is_user_logged_in() ) {
		$extra = '<div class="comment-login-tip">'
			. '<a class="comment-login-link" href="' . esc_url( home_url( '/login/' ) ) . '"'
			. ' data-pk-auth-open data-no-swup data-pk-guest>登录后参与评论</a>'
			. '</div>';
	}

	/* 回复条：点任意一条评论的「回复」后由 app.js 亮出，写回 comment_parent */
	$reply_bar = '<div class="comment-reply-bar" data-pk-reply-bar hidden>'
		. '<span class="comment-reply-bar-text">正在回复 <strong data-pk-reply-target></strong></span>'
		. '<button type="button" class="comment-reply-cancel" data-pk-reply-cancel>取消回复 '
		. pk_icon( 'close', 12 ) . '</button></div>';

	$comment_field = $reply_bar
		. '<div class="comment-form-comment">'
		. '<textarea id="comment" name="comment" rows="1" required placeholder="写下你的想法" aria-label="评论内容" data-pk-comment-input></textarea>'
		. '</div>'
		/* 人机校验：默认收起，点「发表评论」才展开；拖到底换票据后自动续上提交，
		   服务端 pk_captcha_ok() 再验一次。注册表单仍用常驻滑块，不受影响。 */
		. pk_captcha_html( 'pk_cap', '拖动滑块完成验证', true )
		. $extra;

	add_filter( 'comment_form_fields', 'pk_comment_fields_order', 20 );
	ob_start();
	comment_form(
		array(
			'title_reply'          => '',
			'title_reply_to'       => '',
			'cancel_reply_text'    => '取消回复',
			'comment_notes_before' => '',
			'comment_notes_after'  => '',
			'logged_in_as'         => '',
			'comment_field'        => $comment_field,
			'fields'               => array(),
			'label_submit'         => '发表评论',
			'class_submit'         => 'submit comment-submit',
		),
		$post_id
	);
	$html .= '<div class="comments-form-wrap">' . ob_get_clean() . '</div>';
	remove_filter( 'comment_form_fields', 'pk_comment_fields_order', 20 );
	return $html . '</section>';
}

/**
 * 文章操作条：返回列表 / 返回顶部 / 点赞 / 评论 / 分享，全部纯图标无文字。
 * 与阅读层一并输出，首页展开层和独立文章页共用。
 *
 * PC：底部居中的毛玻璃胶囊，横排（返回列表 / 置顶 / 点赞 / 评论 / 分享）。
 * 手机：竖排——置顶一个圆在上，点赞 / 评论 / 分享一整块竖排胶囊，返回上一页
 * 再一个圆落在最下面，三者彼此独立（见 style.css 的 ≤1023 段）。
 * 默认贴右；跟随重力感应或用户在用户中心固定后，可整条换到左侧
 * （见 assets/js/app.js 的 dock-left 类）。
 * 点赞与评论在图标下方带计数（手机端显示，PC 隐藏）。
 * 收藏已合并进点赞，不再单独提供。
 * 0.13.18：手机端默认折叠成一颗 48px 圆按钮，点 .dock-toggle 展开成完整竖排动作栏
 * （见 style.css 的 ≤1023 段与 assets/js/app.js 的 pkDockSet），PC 端不受影响。
 * data-post 供前端同步点赞状态与计数（见 assets/js/app.js）。
 */
function pk_dock_html( $post_id = 0 ) {
	$post_id   = $post_id ? (int) $post_id : (int) get_the_ID();
	$likes     = (int) get_post_meta( $post_id, 'pk_like_count', true );
	$comments  = (int) get_comments_number( $post_id );
	$permalink = get_permalink( $post_id );
	$title     = get_the_title( $post_id );

	return '<nav class="pk-dock" aria-label="文章操作" data-post="' . esc_attr( $post_id ) . '">'
		/* 0.13.18：手机端折叠开关。刻意不用 .dock-btn，避免混进「六项动作」的语义与计数；
		   两张图标按 .is-open 切换显隐，折叠态是菜单、展开态是收起 ×。 */
		. '<button class="dock-toggle" type="button" data-pk-dock-toggle'
		. ' aria-label="展开文章操作" title="展开文章操作" aria-expanded="false">'
		. pk_icon( 'menu', 20, 'i-dock-more' )
		. pk_icon( 'close', 20, 'i-dock-close' )
		. '</button>'
		. '<a class="dock-btn dock-back" data-pk-back data-no-swup href="' . esc_url( home_url( '/' ) ) . '" aria-label="返回列表" title="返回列表">'
		. pk_icon( 'back', 20 ) . '</a>'
		. '<button class="dock-btn dock-top" type="button" data-pk-action="top" aria-label="返回顶部" title="返回顶部">'
		. pk_icon( 'top', 20 ) . '</button>'
		. '<div class="dock-group">'
		. '<button class="dock-btn dock-like" type="button" data-pk-like aria-label="点赞" title="点赞" aria-pressed="false">'
		. pk_icon( 'heart', 20 )
		. '<span class="dock-count" data-dock-like>' . esc_html( $likes ) . '</span></button>'
		. '<button class="dock-btn dock-comments" type="button" data-pk-action="comments" aria-label="评论" title="评论">'
		. pk_icon( 'comment', 20 )
		. '<span class="dock-count" data-dock-comments>' . esc_html( $comments ) . '</span></button>'
		. ( pk_opt( 'ui_share_on' )
			? '<button class="dock-btn dock-share" type="button" data-pk-action="share" aria-label="分享" title="分享"'
				. ' data-url="' . esc_url( $permalink ) . '" data-title="' . esc_attr( $title ) . '">'
				. pk_icon( 'share', 20 ) . '</button>'
			: '' )
		. '</div>'
		/* 手机端：分享下面单独一颗「返回上一页」圆按钮，不并进上面的动作组。
		   data-pk-back 复用阅读层返回逻辑（history.back()，没有历史就回首页）。 */
		. '<a class="dock-btn dock-prev" data-pk-back data-no-swup href="' . esc_url( home_url( '/' ) ) . '" aria-label="返回上一页" title="返回上一页">'
		. pk_icon( 'back', 20 ) . '</a>'
		. '</nav>';
}

/**
 * 阅读层内部 HTML。首页内联数据与独立文章页共用，保证两处渲染一致。
 */
function pk_reading_html( $post_id ) {
	$format = pk_format_key( $post_id );
	$title  = get_the_title( $post_id );
	$date   = get_the_date( 'Y.m.d', $post_id );
	$kind   = pk_kind_label( $format );
	$topic  = pk_topic( $post_id );
	$read   = pk_read_time( $post_id );

	$html  = pk_visual( $post_id, true );
	/* 正文 + 评论 + 右栏包一层 .detail-grid：≥1280 整体居中收拢，头图仍在 grid 外满铺 */
	$html .= '<div class="detail-grid">';
	$html .= '<div class="detail-body">';
	$html .= '<h1 id="d-title">' . esc_html( $title ) . '</h1>';
	/* 元信息行：标题下方只留时间 / 分类 / 时长，右侧不再挂分享按钮 */
	$html .= '<div class="detail-meta-row">';
	$html .= '<div class="detail-meta"><span>' . esc_html( $kind ) . '</span><span class="dot"></span>'
		. '<span>' . esc_html( $date ) . '</span><span class="dot"></span>'
		. '<span>' . esc_html( $topic ) . '</span><span class="dot"></span>'
		. '<span>' . esc_html( $read ) . '</span></div>';
	$html .= '</div>';
	$html .= '<div class="prose">' . pk_content_html( $post_id ) . '</div>';
	$html .= pk_copyright_html( $post_id );
	$html .= '</div>';
	$html .= pk_comments_html( $post_id );

	/* 文章右侧栏：作者卡 + 目录。随阅读层一起被 Swup 交换，无需额外同步。 */
	$rail = pk_author_card_html( $post_id ) . pk_toc_html( $post_id );
	if ( $rail ) {
		$html .= '<aside class="read-rail" aria-label="文章侧栏">' . $rail . '</aside>';
	}
	$html .= '</div>';

	$html .= pk_dock_html( $post_id );

	return $html;
}

/**
 * 0.13.24：PC 文章底部「上一篇 / 下一篇」按钮翻页。
 * 桌面不再提供左右滑翻篇，这两个按钮是独立文章页的主要翻篇入口；
 * 窄屏仍保留跟手手势与 dock，所以按钮只在 ≥1024 通过 CSS 显示。
 *
 * 方向沿用 0.11.19 约定：get_previous_post() 是时间更早的一篇 → 展示为「下一篇」，
 * get_next_post() 是时间更新的一篇 → 展示为「上一篇」。
 *
 * @param string $prev_url   get_previous_post() 的链接（更早）。
 * @param string $next_url   get_next_post() 的链接（更新）。
 * @param string $prev_title 更早那篇的标题。
 * @param string $next_title 更新那篇的标题。
 * @return string
 */
function pk_post_nav_html( $prev_url, $next_url, $prev_title, $next_title ) {
	$items = array(
		array(
			'url'   => $next_url,
			'title' => $next_title,
			'label' => '上一篇',
			'dir'   => 'prev',
			'icon'  => 'prev',
		),
		array(
			'url'   => $prev_url,
			'title' => $prev_title,
			'label' => '下一篇',
			'dir'   => 'next',
			'icon'  => 'next',
		),
	);
	$html = '<nav class="pk-post-nav" aria-label="文章翻页">';
	foreach ( $items as $it ) {
		$title = trim( (string) $it['title'] );
		$title = '' !== $title ? $title : '没有更多了';
		$ico   = '<span class="pk-post-nav-ico" aria-hidden="true">' . pk_icon( $it['icon'], 16 ) . '</span>';
		$body  = '<span class="pk-post-nav-body">'
			. '<span class="pk-post-nav-label">' . esc_html( $it['label'] ) . '</span>'
			. '<span class="pk-post-nav-title">' . esc_html( $title ) . '</span>'
			. '</span>';
		$inner = 'prev' === $it['dir'] ? $ico . $body : $body . $ico;
		$align = 'next' === $it['dir'] ? ' is-next' : '';
		if ( $it['url'] ) {
			$html .= '<a class="pk-post-nav-item' . $align . '" href="' . esc_url( $it['url'] ) . '"'
				. ' data-pk-post-nav="' . esc_attr( $it['dir'] ) . '"'
				. ' aria-label="' . esc_attr( $it['label'] . '：' . $title ) . '">' . $inner . '</a>';
		} else {
			$html .= '<span class="pk-post-nav-item is-disabled' . $align . '" aria-disabled="true">' . $inner . '</span>';
		}
	}
	$html .= '</nav>';
	return $html;
}

/**
 * 统一图标输出：Lucide Static（本地 SVG，线性描边，无渐变无 emoji）
 *
 * 图标源文件位于 assets/icons/*.svg，来自 lucide-static v1.71.0 (ISC)。
 * 统一走本地文件，避免 CDN 依赖，也保证动态插入的阅读层 HTML 无需二次初始化。
 *
 * @param string $name  图标别名，见 $map。
 * @param int    $size  像素尺寸。
 * @param string $class 附加 class。
 * @return string
 */
function pk_icon( $name, $size = 18, $class = '' ) {
	static $cache = array();

	$map = array(
		'search'        => 'search',
		'moon'          => 'moon',
		'sun'           => 'sun',
		'info'          => 'info',
		'back'          => 'arrow-left',
		'chevron-down'  => 'chevron-down',
		'close'         => 'x',
		'link'          => 'link',
		'top'           => 'arrow-up',
		'comment'       => 'message-circle',
		'check'         => 'check',
		'external-link' => 'external-link',
		'user-round'    => 'user-round',
		'prev'          => 'chevron-left',
		'next'          => 'chevron-right',
		'heart'         => 'heart',
		'share'         => 'share',
		'fingerprint'   => 'fingerprint',
		'scan-face'     => 'scan-face',
		'trash'         => 'trash-2',
		'volume-2'      => 'volume-2',
		'pause'         => 'pause',
		'play'          => 'play',
		'focus'         => 'focus',
		'bell'          => 'bell',
		'sparkles'      => 'sparkles',
	);
	$file = isset( $map[ $name ] ) ? $map[ $name ] : $name;
	if ( ! preg_match( '/^[a-z0-9-]+$/', $file ) ) {
		return '';
	}

	if ( ! isset( $cache[ $file ] ) ) {
		$path = get_template_directory() . '/assets/icons/' . $file . '.svg';
		$raw  = is_readable( $path ) ? file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( $raw ) {
			// 去掉 license 注释与自带 class，统一尺寸和描边。
			$raw = preg_replace( '/<!--.*?-->/s', '', $raw );
			$raw = preg_replace( '/\sclass="[^"]*"/', '', $raw, 1 );
			$raw = str_replace( 'stroke-width="2"', 'stroke-width="1.75"', $raw );
			$raw = trim( preg_replace( '/\s+/', ' ', $raw ) );
		}
		$cache[ $file ] = $raw;
	}
	$svg = $cache[ $file ];
	if ( ! $svg ) {
		return '';
	}

	$size  = max( 8, min( 64, (int) $size ) );
	$class = trim( 'pk-icon ' . preg_replace( '/[^a-zA-Z0-9 _-]/', '', $class ) );
	$svg   = preg_replace( '/<svg\b/', '<svg class="' . esc_attr( $class ) . '" aria-hidden="true" focusable="false"', $svg, 1 );
	$svg   = str_replace( 'width="24"', 'width="' . $size . '"', $svg );
	$svg   = str_replace( 'height="24"', 'height="' . $size . '"', $svg );
	return $svg;
}

/**
 * 深浅色 Logo 地址（0.13.14）
 *
 * 后台分别设置「白色模式 Logo」(logo_url) 与「黑色模式 Logo」(logo_dark_url)。
 * 黑色模式留空时回退到白色模式那张，保证只填一个也能在两种模式下正常显示。
 *
 * @return array{light:string,dark:string}
 */
function pk_logo_urls() {
	$light = trim( (string) pk_opt( 'logo_url' ) );
	$dark  = trim( (string) pk_opt( 'logo_dark_url' ) );
	if ( '' === $dark ) {
		$dark = $light;
	}
	return array(
		'light' => $light,
		'dark'  => $dark,
	);
}

/**
 * 输出一对按深浅色切换的 <img>（0.13.14）
 *
 * 两张图始终输出，由 CSS（:root[data-theme="dark"] / prefers-color-scheme）
 * 显隐，兼容「跟随系统」与「手动切换」两种状态；两张都为空时返回空串，
 * 调用方回退到文字站名。深色图与浅色图 URL 相同时也只是同一张图显隐，不会闪。
 *
 * @param string $class 图片 class（如 pc-side-logo / logo-img）。
 * @param int    $size  width / height 的像素值。
 * @param string $alt   alt 文本，装饰图传空串。
 * @return string
 */
function pk_logo_html( $class, $size, $alt = '' ) {
	$urls = pk_logo_urls();
	if ( '' === $urls['light'] && '' === $urls['dark'] ) {
		return '';
	}
	$size = max( 8, (int) $size );
	$cls  = trim( preg_replace( '/[^a-zA-Z0-9 _-]/', '', (string) $class ) );
	$err  = " onerror=\"this.style.display='none';this.parentNode.classList.add('logo-failed')\"";
	$out  = '';
	$pair = array(
		'light' => array( 'pk-logo-light', $urls['light'] ),
		'dark'  => array( 'pk-logo-dark',  $urls['dark'] ),
	);
	foreach ( $pair as $mode => $item ) {
		if ( '' === $item[1] ) {
			continue;
		}
		/* 深色图在 HTML 里就用内联样式隐藏：即使样式表是旧缓存 / 未加载，也绝不会两张并排。
		   深色模式由 style.css 的 !important 规则覆盖这条内联样式把它显示出来。 */
		$inline = 'dark' === $mode ? ' style="display:none"' : '';
		$out .= '<img class="' . esc_attr( $cls . ' ' . $item[0] ) . '"'
			. ' src="' . esc_url( $item[1] ) . '"'
			. ' alt="' . esc_attr( $alt ) . '"'
			. ' width="' . $size . '" height="' . $size . '" decoding="async"'
			. $inline . $err . '>';
	}
	return $out;
}
