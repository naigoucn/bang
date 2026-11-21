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
function bang_art_map() {
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
function bang_art_for( $post_id, $offset = 0 ) {
	$map  = bang_art_map();
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
function bang_format_key( $post_id ) {
	$format = get_post_format( $post_id );
	if ( ! $format ) {
		return 'standard';
	}
	if ( in_array( $format, array( 'image', 'gallery' ), true ) ) {
		return 'image';
	}
	return in_array( $format, array( 'aside', 'quote' ), true ) ? $format : 'standard';
}

function bang_kind_label( $format_key ) {
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
function bang_plain_excerpt( $post_id ) {
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
function bang_read_time( $post_id ) {
	$text = wp_strip_all_tags( get_post_field( 'post_content', $post_id ) );
	$len  = mb_strlen( preg_replace( '/\s/u', '', $text ), 'UTF-8' );
	return max( 1, (int) ceil( $len / 350 ) ) . ' 分钟';
}

/**
 * 首个分类名
 */
function bang_topic( $post_id ) {
	$cats = get_the_category( $post_id );
	return ! empty( $cats ) ? $cats[0]->name : '未分类';
}

/**
 * 全部分类 slug，供前端筛选
 */
function bang_topic_slugs( $post_id ) {
	$slugs = array();
	foreach ( get_the_category( $post_id ) as $cat ) {
		$slugs[] = $cat->slug;
	}
	return $slugs;
}

/**
 * 正文 HTML + 目录数据
 *
 * 正文里的 h2 / h3 统一补上 bang-h-N 锚点，侧栏「文章目录」拿同一份数据渲染，
 * 保证正文与服务端输出的侧栏目录 id 一致（也覆盖 Swup 拉取的整页 HTML）。
 * 过滤后再插 id：wp_kses_post 之后自定义属性不会被剥掉。
 *
 * @param int $post_id 文章 ID。
 * @return array{html:string,toc:array} html 为带锚点的正文，toc 为 [{id,level,text}]。
 */
function bang_heading_data( $post_id ) {
	static $cache = array();
	$post_id      = (int) $post_id;
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	/* 0.13.20 全站内容解锁：命中时整篇正文换成锁定卡片，原文的 the_content
	   过滤器根本不执行，首页 #bang-data 内联层 / 文章目录也拿不到任何片段。 */
	$locked = bang_unlock_heading_override( $post_id );
	if ( false !== $locked ) {
		$cache[ $post_id ] = $locked;
		return $cache[ $post_id ];
	}

	/* 0.13.22：首页内联阅读层一次渲染多篇文章，把当前文章 ID 传给阅读解锁的
	   区块 / 简码，避免它们误用主查询的 get_the_ID() 算错点赞、评论、密码状态。 */
	$prev_unlock_post = isset( $GLOBALS['bang_unlock_render_post'] ) ? $GLOBALS['bang_unlock_render_post'] : null;
	$GLOBALS['bang_unlock_render_post'] = $post_id;
	$content = get_post_field( 'post_content', $post_id );
	$html    = wp_kses_post( apply_filters( 'the_content', $content ) );
	if ( null === $prev_unlock_post ) {
		unset( $GLOBALS['bang_unlock_render_post'] );
	} else {
		$GLOBALS['bang_unlock_render_post'] = $prev_unlock_post;
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
			$id    = 'bang-h-' . $seq;
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
function bang_content_html( $post_id ) {
	$data = bang_heading_data( $post_id );
	return $data['html'];
}

/**
 * 作者信息卡片（文章页右侧栏）
 *
 * 传 $post_id 取本文作者；不传则回退到发文最多的作者。
 */
function bang_author_card_html( $post_id = 0 ) {
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
	$bio_mode = (string) bang_opt( 'author_bio_mode' );
	$bio_api  = trim( (string) bang_opt( 'author_bio_api' ) );
	if ( 'custom' === $bio_mode ) {
		$bio = trim( (string) bang_opt( 'author_bio_custom' ) );
	} elseif ( 'api' === $bio_mode ) {
		$bio_api_fallback = trim( (string) bang_opt( 'author_bio_fallback' ) );
		if ( '' === $bio_api_fallback ) {
			$bio_api_fallback = $bio; // 接口兜底回退到作者资料简介
		}
		$bio = $bio_api_fallback;
	}

	/* 头像统一用账号头像（个人中心上传的 bang_avatar_id / 旧 bang_avatar），
	   与说说卡片 / 评论 / 个人中心完全一致；后台「作者头像」设置已移除。
	   回退：账号头像 → 站点 Logo → 名字首字徽标。不用 Gravatar。 */
	$avatar = '';
	if ( function_exists( 'bang_user_avatar_url' ) ) {
		$avatar = bang_safe_comment_site( bang_user_avatar_url( $user_id, 56 ) );
	}
	if ( ! $avatar ) {
		$avatar = bang_safe_comment_site( (string) bang_opt( 'logo_url' ) );
	}
	$initial = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1, 'UTF-8' ) : substr( $name, 0, 1 );

	$html  = '<section class="pc-author" id="bang-author-card" aria-label="作者信息">';
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
			$bio_attrs = ' data-bang-hitokoto="' . esc_url( $bio_api ) . '"';
		}
		$html .= '<p class="pc-author-bio"' . $bio_attrs . '>' . esc_html( $bio ) . '</p>';
	}

	/* 社交图标（0.13.28）：按后台填的链接输出，留空的平台不显示。
	   全部走 bang_icon() 本地 SVG，不引 CDN、不用 emoji。 */
	$socials = array(
		'qq'        => array( 'qq', __( 'QQ', 'bang' ) ),
		'wechat'    => array( 'wechat', __( '微信', 'bang' ) ),
		'douyin'    => array( 'douyin', __( '抖音', 'bang' ) ),
		'x'         => array( 'x-social', __( 'X', 'bang' ) ),
		'weibo'     => array( 'weibo', __( '微博', 'bang' ) ),
		'github'    => array( 'github', __( 'GitHub', 'bang' ) ),
		'bilibili'  => array( 'bilibili', __( '哔哩哔哩', 'bang' ) ),
	);
	$social_html = '';
	foreach ( $socials as $social_key => $social ) {
		$link = trim( (string) bang_opt( 'social_' . $social_key ) );
		if ( '' === $link ) {
			continue;
		}
		$safe_link = bang_safe_comment_site( $link );
		if ( ! $safe_link ) {
			continue;
		}
		$social_html .= '<a class="pc-author-social" href="' . esc_url( $safe_link ) . '"'
			. ' target="_blank" rel="noopener noreferrer"'
			. ' aria-label="' . esc_attr( $social[1] ) . '" title="' . esc_attr( $social[1] ) . '">'
			. bang_icon( $social[0], 17 ) . '</a>';
	}
	if ( $mail ) {
		$social_html .= '<a class="pc-author-social" href="mailto:' . esc_attr( antispambot( $mail ) ) . '"'
			. ' aria-label="' . esc_attr__( '联系我', 'bang' ) . '" title="' . esc_attr__( '联系我', 'bang' ) . '">'
			. bang_icon( 'mail', 17 ) . '</a>';
	}
	if ( $social_html ) {
		$html .= '<div class="pc-author-socials">' . $social_html . '</div>';
	}
	/* 打赏按钮已独立成「打赏卡片」小工具，见 inc/sidebar.php，作者卡不再内嵌。 */
	$html .= '</section>';

	return $html;
}

/**
 * 文章目录（仅普通文章、正文含 h2/h3 时输出）
 */
function bang_toc_html( $post_id ) {
	$post_id = (int) $post_id;
	if ( ! $post_id || 'post' !== get_post_type( $post_id ) ) {
		return '';
	}
	$data = bang_heading_data( $post_id );
	if ( empty( $data['toc'] ) ) {
		return '';
	}

	$html  = '<nav class="bang-toc" aria-label="文章目录">';
	/* 用 <p> 不用标题：目录在正文 h1 之前，h2 会造成标题层级跳级 */
	$html .= '<p class="pc-pop-title">目录</p>';
	$html .= '<ul class="bang-toc-list">';
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
function bang_post_images( $post_id ) {
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
function bang_image_html( $attachment_id, $size, $post_id, $eager = false ) {
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
function bang_visual( $post_id, $detail = false ) {
	$base   = $detail ? 'detail-hero' : 'card-visual';
	$format = bang_format_key( $post_id );
	$ids    = bang_post_images( $post_id );
	$size   = $detail ? 'full' : 'medium_large';

	if ( 'quote' === $format ) {
		$quote = get_post_meta( $post_id, '_pk_quote', true );
		if ( ! $quote ) {
			$quote = bang_plain_excerpt( $post_id );
		}
		return '<div class="' . esc_attr( $base ) . ' quote"><span>「' . esc_html( $quote ) . '」</span></div>';
	}

	if ( 'aside' === $format && count( $ids ) > 1 ) {
		$frames = array();
		foreach ( array_slice( $ids, 0, 3 ) as $id ) {
			$frames[] = bang_image_html( $id, $detail ? 'full' : 'medium', $post_id, $detail );
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

	$image = $ids ? bang_image_html( $ids[0], $size, $post_id, $detail ) : '';
	return '<div class="' . esc_attr( $base ) . '">' . ( $image ? $image : bang_art_for( $post_id ) ) . '</div>';
}

/**
 * 评论者个人网站：只接受 http / https，挡掉 javascript: / data: / 协议相对地址。
 * 中转页也复用本函数校验，避免 ?bang_go= 变成任意跳转。
 */
function bang_safe_comment_site( $url ) {
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
 * 外链中转地址：评论者网站统一走 ?bang_go= 确认页，由评论图标新标签打开。
 */
function bang_go_url( $url ) {
	/* 手动编码：目标地址本身可能带 ? / & / #，不能让它们混进外层 query */
	return home_url( '/' ) . '?bang_go=' . rawurlencode( $url );
}

/**
 * 把评论框排到所有字段之前，其余字段（完善信息弹窗）由 CSS / JS 控制显隐。
 */
function bang_comment_fields_order( $fields ) {
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
function bang_copyright_html( $post_id ) {
	if ( ! bang_opt( 'copyright_on' ) ) {
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

	$license = bang_opt( 'copyright_license' );
	if ( 'by' === $license ) {
		$license_line = '本文采用 <a href="https://creativecommons.org/licenses/by/4.0/deed.zh-hans" target="_blank" rel="noopener noreferrer nofollow">知识共享 署名 4.0 国际许可协议</a>，转载请保留作者署名与原文链接。';
	} elseif ( 'all-rights' === $license ) {
		$license_line = '版权所有，未经授权不得以任何形式转载、摘编或用于商业用途。';
	} elseif ( 'none' === $license ) {
		$license_line = '';
	} else {
		$license_line = '本文采用 <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.zh-hans" target="_blank" rel="noopener noreferrer nofollow">知识共享 署名-非商业性使用-相同方式共享 4.0 国际许可协议</a>，转载请保留作者署名与原文链接，且不得用于商业用途。';
	}

	$note    = trim( (string) bang_opt( 'copyright_note' ) );
	$tagline = trim( (string) bang_opt( 'footer_text' ) );
	$note_html = $note ? '<p class="bang-copyright-note">' . nl2br( esc_html( $note ) ) . '</p>' : '';

	return '<section class="bang-copyright" aria-label="版权声明">'
		. '<h2 class="bang-copyright-title">' . bang_icon( 'info', 15 ) . '<span>版权声明</span></h2>'
		. '<p>《' . esc_html( $title ) . '》由 <strong>' . esc_html( $author ) . '</strong> 原创，'
		. '首发于 <a href="' . esc_url( $home ) . '">' . esc_html( $site ) . '</a>'
		. '（<a href="' . esc_url( $link ) . '">原文链接</a>），发表于 ' . esc_html( $date ) . '。</p>'
		. ( $license_line ? '<p>' . $license_line . '</p>' : '' )
		. $note_html
		. '<div class="bang-copyright-meta">&copy; ' . esc_html( gmdate( 'Y' ) ) . ' ' . esc_html( $site )
		. ( $tagline ? ' &middot; ' . esc_html( $tagline ) : '' ) . '</div>'
		. '</section>';
}

/**
 * 相关阅读的比对文本：标题 + 摘要（没写摘要就取正文前 180 字）。
 *
 * @param WP_Post $post 文章对象。
 * @return string
 */
function bang_related_source_text( $post ) {
	if ( ! $post ) {
		return '';
	}
	$excerpt = ! empty( $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;
	$excerpt = wp_html_excerpt( wp_strip_all_tags( $excerpt ), 180, '' );
	return get_the_title( $post->ID ) . ' ' . $excerpt;
}

/**
 * 极简分词：中文按相邻 2 字成词，英文 / 数字按单词，去停用词后统计词频。
 * 不依赖分词库，也不调用外部接口；标题里反复出现的词自然权重更高。
 *
 * @param string $text 比对文本。
 * @return array 词 => 出现次数。
 */
function bang_related_terms( $text ) {
	static $stop = null;
	if ( null === $stop ) {
		$words = array(
			'的', '了', '是', '在', '和', '与', '或', '也', '都', '而', '及', '等', '这', '那', '我', '你', '他', '她', '它',
			'我们', '你们', '他们', '一个', '一种', '一些', '这个', '那个', '这里', '那里', '就是', '还是', '不是', '没有',
			'可以', '如果', '因为', '所以', '但是', '而且', '然后', '以及', '通过', '对于', '关于', '什么', '怎么', '这样',
			'那样', '已经', '正在', '可能', '应该', '需要', '自己', '其他', '更多', '时候', '之后', '之前', '之间', '一下',
			'一点', '现在', '内容', '文章', '时间', '一下', '这种', '一种', '一样', '一直', '一定',
			'the', 'a', 'an', 'and', 'or', 'of', 'to', 'in', 'on', 'for', 'with', 'is', 'are', 'was', 'were', 'be', 'been',
			'it', 'this', 'that', 'these', 'those', 'as', 'at', 'by', 'from', 'but', 'not', 'you', 'your', 'we', 'our',
			'how', 'what', 'when', 'where', 'which', 'will', 'can', 'have', 'has', 'had',
		);
		$stop = array_fill_keys( $words, true );
	}

	$text  = strtolower( (string) $text );
	$text  = wp_strip_all_tags( $text );
	$terms = array();

	if ( preg_match_all( '/[a-z0-9]{2,}/', $text, $m ) ) {
		foreach ( $m[0] as $word ) {
			if ( ! isset( $stop[ $word ] ) ) {
				$terms[] = $word;
			}
		}
	}
	if ( preg_match_all( '/[\x{4e00}-\x{9fa5}]+/u', $text, $m ) ) {
		foreach ( $m[0] as $run ) {
			$chars = preg_split( '//u', $run, -1, PREG_SPLIT_NO_EMPTY );
			$len   = count( $chars );
			for ( $i = 0; $i < $len - 1; $i++ ) {
				$gram = $chars[ $i ] . $chars[ $i + 1 ];
				if ( ! isset( $stop[ $gram ] ) ) {
					$terms[] = $gram;
				}
			}
		}
	}
	return array_count_values( $terms );
}

/**
 * 两组词的相似度：Dice 系数 + 词频命中微加权。
 *
 * @param array $a 源文章词频。
 * @param array $b 候选文章词频。
 * @return float
 */
function bang_related_similarity( $a, $b ) {
	if ( empty( $a ) || empty( $b ) ) {
		return 0;
	}
	$shared = array_intersect( array_keys( $a ), array_keys( $b ) );
	if ( empty( $shared ) ) {
		return 0;
	}
	$dice = ( 2 * count( $shared ) ) / ( count( $a ) + count( $b ) );
	$hits = 0;
	foreach ( $shared as $term ) {
		$hits += min( $a[ $term ], $b[ $term ] );
	}
	return $dice + min( 0.15, $hits * 0.01 );
}

/**
 * 文章底部「相关阅读」：同分类 / 同标签为强信号，再按标题 + 摘要的词频相似度重排，
 * 词面完全不相干时用近期文章兜底，最多 5 篇。
 * 视觉复用归档页的「DD日 + 标题」极简时间线（.bang-arch-*），保持全站一致。
 * 后台「Bang 设置 → 常规 → 相关文章」可关闭。
 *
 * @param int $post_id 当前文章 ID。
 * @return string
 */
function bang_related_posts_html( $post_id ) {
	if ( ! bang_opt( 'related_on' ) ) {
		return '';
	}
	$post_id = (int) $post_id;
	if ( ! $post_id || 'post' !== get_post_type( $post_id ) ) {
		return '';
	}

	$source = get_post( $post_id );
	if ( ! $source ) {
		return '';
	}
	$src_terms = bang_related_terms( bang_related_source_text( $source ) );
	$src_cats  = (array) wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );
	$src_tags  = (array) wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );

	/* 候选池取最近 60 篇：分类 / 标签加权 + 词频重排 + 近期微加权兜底 */
	$query = new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 60,
			'post__not_in'   => array( $post_id ),
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
	if ( empty( $query->posts ) ) {
		return '';
	}

	$total = count( $query->posts );

	/* 分类 / 标签一次性批量取出，避免逐篇查库 */
	$cats_by_post = array();
	$tags_by_post = array();
	$obj_terms    = wp_get_object_terms(
		$query->posts,
		array( 'category', 'post_tag' ),
		array( 'fields' => 'all_with_object_id' )
	);
	foreach ( (array) $obj_terms as $term ) {
		if ( is_wp_error( $term ) ) {
			continue;
		}
		$pid = (int) $term->object_id;
		if ( 'category' === $term->taxonomy ) {
			$cats_by_post[ $pid ][] = (int) $term->term_id;
		} else {
			$tags_by_post[ $pid ][] = (int) $term->term_id;
		}
	}

	$scored = array();
	foreach ( $query->posts as $i => $cid ) {
		$pid       = (int) $cid;
		$candidate = get_post( $cid );
		if ( ! $candidate ) {
			continue;
		}
		$score = bang_related_similarity( $src_terms, bang_related_terms( bang_related_source_text( $candidate ) ) );
		if ( ! empty( $src_cats ) && ! empty( $cats_by_post[ $pid ] ) && array_intersect( $cats_by_post[ $pid ], $src_cats ) ) {
			$score += 0.30;
		}
		if ( ! empty( $src_tags ) && ! empty( $tags_by_post[ $pid ] ) && array_intersect( $tags_by_post[ $pid ], $src_tags ) ) {
			$score += 0.20;
		}
		$score += 0.02 * ( 1 - $i / $total );
		$scored[] = array(
			'id'    => (int) $cid,
			'score' => $score,
		);
	}

	usort(
		$scored,
		function ( $x, $y ) {
			if ( abs( $y['score'] - $x['score'] ) < 0.00001 ) {
				return $x['id'] < $y['id'] ? -1 : 1;
			}
			return $y['score'] > $x['score'] ? -1 : 1;
		}
	);
	$picked = array_slice( array_column( $scored, 'id' ), 0, 5 );
	if ( empty( $picked ) ) {
		return '';
	}

	$html  = '<section class="bang-related" aria-label="相关阅读">';
	$html .= '<h2 class="bang-related-title">' . bang_icon( 'layers', 15 ) . '<span>相关阅读</span></h2>';
	$html .= '<ul class="bang-arch-list">';
	foreach ( $picked as $rid ) {
		$html .= '<li class="bang-arch-row">'
			. '<a class="bang-arch-item" href="' . esc_url( get_permalink( $rid ) ) . '">'
			. '<span class="bang-arch-day">' . esc_html( get_the_date( 'd', $rid ) ) . '日</span>'
			. '<span class="bang-arch-title">' . esc_html( get_the_title( $rid ) ) . '</span>'
			. '</a></li>';
	}
	$html .= '</ul></section>';

	return $html;
}

/**
 * 解析后台「页脚链接分组」配置为按列组织的数组。
 *
 * 配置格式：空行分块，每块第一行是列标题，其余行「链接名 | 链接」。
 * 站内单段路径（/about/）优先走 bang_site_page_url 解析真实 permalink，
 * 外部 http(s) 链接原样保留；命名锚点 / 多段路径退回 home_url。
 *
 * @return array array( array( 'title' => ..., 'items' => array( array( name, url, blank ), ... ) ), ... )
 */
function bang_footer_link_groups() {
	$raw = (string) bang_opt( 'footer_links' );
	if ( '' === trim( $raw ) ) {
		return array();
	}
	$blocks = preg_split( '/\n\s*\n/', $raw );
	$groups = array();
	foreach ( (array) $blocks as $block ) {
		$lines = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $block ) ), 'strlen' ) );
		if ( ! $lines ) {
			continue;
		}
		$title = array_shift( $lines );
		$items = array();
		foreach ( $lines as $line ) {
			$parts = array_map( 'trim', explode( '|', $line ) );
			$name  = isset( $parts[0] ) ? $parts[0] : '';
			$url   = isset( $parts[1] ) ? $parts[1] : '';
			if ( '' === $name || '' === $url ) {
				continue;
			}
			if ( 0 === strpos( $url, '/' ) ) {
				$slug = trim( $url, '/' );
				if ( '' !== $slug && false === strpos( $slug, '/' ) && function_exists( 'bang_site_page_url' ) ) {
					$url = bang_site_page_url( $slug );
				} else {
					$url = home_url( $url );
				}
			} elseif ( ! preg_match( '#^https?://#i', $url ) && ! preg_match( '#^(mailto:|tel:|#)#i', $url ) ) {
				$url = home_url( '/' . ltrim( $url, '/' ) );
			}
			$items[] = array( $name, esc_url_raw( $url ), false );
		}
		if ( $items ) {
			$groups[] = array( 'title' => $title, 'items' => $items );
		}
	}
	return $groups;
}

/**
 * 统一页脚内容：列表页 footer.php 与文章阅读层共用同一份结构。
 *
 * 结构：左侧「精选友链 / 联系我们」→ 右侧后台自定义链接列 → 底部版权 / 徽章 / 备案。
 * 链接列来自后台「页脚链接分组」，不再写死参考站点文案。
 * 置顶按钮不在这里输出：全站统一走 dock 的置顶，避免重复。
 *
 * @return string
 */
function bang_footer_content_html() {
	$name = get_bloginfo( 'name' );
	$icp  = trim( (string) bang_opt( 'icp' ) );
	$year = gmdate( 'Y' );

	/* 精选友链：核心链接管理器，最多 8 条，末尾「更多友链」 */
	$friend_html = '';
	$links       = function_exists( 'bang_friend_links' ) ? bang_friend_links() : array();
	foreach ( array_slice( $links, 0, 8 ) as $lk ) {
		$friend_html .= '<a class="bang-foot-chip" href="' . esc_url( $lk['url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $lk['name'] ) . '</a>';
	}
	$friend_html .= '<a class="bang-foot-chip bang-foot-chip--more" href="' . esc_url( bang_site_page_url( 'links' ) ) . '">' . esc_html__( '更多友链', 'bang' ) . '</a>';

	/* 联系我们：后台社交配置 + 联系邮箱，只输出已填的 */
	$social_defs = array(
		'wechat' => array( 'wechat', __( '微信', 'bang' ) ),
		'qq'     => array( 'qq', __( 'QQ', 'bang' ) ),
		'weibo'  => array( 'weibo', __( '微博', 'bang' ) ),
		'github' => array( 'github', __( 'GitHub', 'bang' ) ),
		'x'      => array( 'x-social', __( 'X', 'bang' ) ),
	);
	$social_html = '';
	foreach ( $social_defs as $key => $def ) {
		$url = trim( (string) bang_opt( 'social_' . $key ) );
		if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
			continue;
		}
		$social_html .= '<a class="bang-foot-social" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( $def[1] ) . '" title="' . esc_attr( $def[1] ) . '">' . bang_icon( $def[0], 16 ) . '</a>';
	}
	$mail = function_exists( 'bang_contact_email' ) ? bang_contact_email() : '';
	if ( $mail ) {
		$social_html .= '<a class="bang-foot-social" href="mailto:' . esc_attr( antispambot( $mail ) ) . '" aria-label="' . esc_attr__( '邮箱', 'bang' ) . '" title="' . esc_attr__( '邮箱', 'bang' ) . '">' . bang_icon( 'mail', 16 ) . '</a>';
	}

	/* 右侧链接列：完全来自后台「页脚链接分组」，用户自定义，不写死任何站点文案 */
	$cols_html = '';
	foreach ( bang_footer_link_groups() as $col ) {
		$cols_html .= '<div class="bang-foot-col"><h3 class="bang-foot-col-title">' . esc_html( $col['title'] ) . '</h3><ul class="bang-foot-col-list">';
		foreach ( $col['items'] as $item ) {
			$cols_html .= '<li><a href="' . esc_url( $item[1] ) . '">' . esc_html( $item[0] ) . '</a></li>';
		}
		$cols_html .= '</ul></div>';
	}

	$html  = '<div class="bang-foot-main"><div class="bang-foot-about">';
	$html .= '<section class="bang-foot-block"><h3 class="bang-foot-title">' . esc_html__( '精选友链', 'bang' ) . '</h3><div class="bang-foot-chips">' . $friend_html . '</div></section>';
	$html .= '<section class="bang-foot-block"><h3 class="bang-foot-title">' . esc_html__( '联系我们', 'bang' ) . '</h3><div class="bang-foot-socials">' . $social_html . '</div></section>';
	$html .= '</div>' . ( $cols_html ? '<div class="bang-foot-cols">' . $cols_html . '</div>' : '' ) . '</div>';
	$html .= '<div class="bang-foot-bottom">';
	/* 备案号一类紧跟版权同一行（红框位置），技术支持文案单独一行 */
	$beian  = '';
	if ( $icp ) {
		$beian .= '<a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer">' . esc_html( $icp ) . '</a>';
	}
	$police = trim( (string) bang_opt( 'police_icp' ) );
	if ( $police ) {
		$beian .= '<a href="https://beian.mps.gov.cn/" target="_blank" rel="noopener noreferrer">' . esc_html( $police ) . '</a>';
	}
	$html .= '<p class="bang-foot-copy">Copyright &copy; ' . esc_html( $year ) . ' ' . esc_html( $name ) . ( $beian ? '<span class="bang-foot-beian">' . $beian . '</span>' : '' ) . '</p>';
	$meta  = '';
	$meta .= '<span>' . esc_html( sprintf( __( '由 %s 提供技术支持 · EdgeOne 加速支持', 'bang' ), $name ) ) . '</span>';
	$html .= '<p class="bang-foot-meta">' . $meta . '</p>';
	$html .= '</div>';
	return $html;
}

/**
 * 语言切换下拉（纯文字，无图标）：供侧栏底部工具区复用。
 * 下拉结构沿用 .bang-lang，JS 的 [data-bang-lang-*] 委托在文档级，换页/多处同款都生效。
 *
 * @return string
 */
function bang_lang_switch_html() {
	$langs = array(
		'chinese_simplified'  => __( '简体中文', 'bang' ),
		'chinese_traditional' => __( '繁體中文', 'bang' ),
		'english'             => __( 'English', 'bang' ),
		'japanese'            => __( '日本語', 'bang' ),
		'korean'              => __( '한국어', 'bang' ),
	);
	/* 国旗取自 flag-icons 官方库，随主题本地存放，非自绘 */
	$flags = array(
		'chinese_simplified'  => 'cn',
		'chinese_traditional' => 'hk',
		'english'             => 'gb',
		'japanese'            => 'jp',
		'korean'              => 'kr',
	);
	$flag_base = get_template_directory_uri() . '/assets/icons/flags/';
	$lang_opts = '';
	foreach ( $langs as $code => $label ) {
		$flag = isset( $flags[ $code ] ) ? $flags[ $code ] : 'cn';
		$lang_opts .= '<button class="bang-lang-opt" type="button" data-bang-lang="' . esc_attr( $code ) . '"><img class="bang-lang-flag" src="' . esc_url( $flag_base . $flag . '.svg' ) . '" alt="" width="20" height="15"><span class="bang-lang-name">' . esc_html( $label ) . '</span></button>';
	}
	$html  = '<div class="bang-lang side-lang">';
	$html .= '<button class="bang-lang-btn" type="button" data-bang-lang-toggle aria-expanded="false" title="' . esc_attr( $langs['chinese_simplified'] ) . '" aria-label="' . esc_attr__( '切换语言', 'bang' ) . '">';
	$html .= '<span class="bang-lang-label" data-bang-lang-label>' . esc_html( $langs['chinese_simplified'] ) . '</span>';
	$html .= '<svg class="bang-lang-caret" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
	$html .= '</button>';
	$html .= '<div class="bang-lang-menu" data-bang-lang-menu hidden>' . $lang_opts . '</div>';
	$html .= '</div>';
	return $html;
}

/**
 * 文章阅读层内的页脚：文章页是 fixed overlay 内部滚动，公共页脚在 #swup-wrapper 外、
 * 永远滚不到，故在正文末尾补一份与站点页脚同构的页脚。
 * 列表页仍用 footer.php 那份，二者共用 bang_footer_content_html()。
 *
 * @return string
 */
function bang_article_footer_html() {
	$content = function_exists( 'bang_footer_content_html' ) ? bang_footer_content_html() : '';
	return '<div class="bang-article-foot"><footer class="site-foot">' . $content . '</footer></div>';
}

/**
 * 单条评论的 <li> 片段。
 *
 * @param WP_Comment $item          评论对象。
 * @param string     $reply_to      若这是回复，父评论的作者名。
 * @param string     $children_html 子回复的 <ol> 片段，插在 .comment-main 末尾。
 */
function bang_comment_item_html( $item, $reply_to = '', $children_html = '' ) {
	$site      = bang_safe_comment_site( $item->comment_author_url );
	$site_link = '';
	if ( $site ) {
		$host      = wp_parse_url( $site, PHP_URL_HOST );
		$site_link = '<a class="comment-site" href="' . esc_url( bang_go_url( $site ) ) . '"'
			. ' target="_blank" rel="noopener noreferrer nofollow"'
			. ' aria-label="访问 ' . esc_attr( $item->comment_author ) . ' 的个人网站"'
			. ' title="' . esc_attr( $host ) . '">' . bang_icon( 'external-link', 13 ) . '</a>';
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
		. '<button type="button" class="comment-reply-btn" data-bang-reply="' . esc_attr( $cid ) . '"'
		. ' data-bang-reply-author="' . esc_attr( $item->comment_author ) . '"'
		. ' aria-label="回复 ' . esc_attr( $item->comment_author ) . ' 的评论">'
		. bang_icon( 'comment', 12 ) . '<span>回复</span></button></div>';

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
function bang_comments_list_html( $post_id ) {
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
			bang_comment_descendants( $kids, $children, $flat, $visited );
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
			$sub .= bang_comment_item_html( $kid, $name, '' );
		}
		if ( $sub ) {
			$sub = '<ol class="comment-children">' . $sub . '</ol>';
		}
		$html .= bang_comment_item_html( $item, '', $sub );
	}
	return $html;
}

function bang_comment_descendants( $items, $children, &$out, &$visited ) {
	foreach ( $items as $item ) {
		$cid = (int) $item->comment_ID;
		if ( isset( $visited[ $cid ] ) ) {
			continue;
		}
		$visited[ $cid ] = 1;
		$out[]           = $item;
		if ( ! empty( $children[ $cid ] ) ) {
			bang_comment_descendants( $children[ $cid ], $children, $out, $visited );
		}
	}
}

/**
 * 评论区：已审核评论列表 + 单输入框的 WordPress 原生评论表单。
 * 0.13.19：站点已有登录体系，评论身份一律由登录态提供，不再输出「完善信息 /
 * 个人信息」按钮与弹窗；未登录时只在表单内给一条登录引导，提交统一走 AJAX。
 */
function bang_comments_html( $post_id ) {
	if ( ! bang_opt( 'show_comments' ) ) {
		return '';
	}
	$count     = (int) get_comments_number( $post_id );
	$list_html = comments_open( $post_id ) ? bang_comments_list_html( $post_id ) : '';
	$is_empty  = ( '' === $list_html );
	/* 评论改为右侧抽屉：头（标题 + 关闭）/ 中（滚动气泡列表）/ 尾（输入框）三段 */
	$html  = '<section class="detail-comments' . ( $is_empty ? ' is-empty' : '' ) . '"'
		. ' id="bang-comments" data-bang-post-id="' . (int) $post_id . '"'
		. ' role="dialog" aria-modal="true" aria-labelledby="bang-comments-title" aria-label="评论">';
	$html .= '<span class="comments-grab" aria-hidden="true"></span>';
	$html .= '<div class="comments-bar">'
		. '<h2 class="comments-title" id="bang-comments-title">评论 <span class="bang-comment-count">' . esc_html( $count ) . '</span></h2>'
		. '<button type="button" class="comments-close" data-bang-comments-close aria-label="关闭评论" title="关闭">'
		. bang_icon( 'x', 18 ) . '</button>'
		. '</div>';
	$html .= '<div class="comments-scroll">';

	if ( ! comments_open( $post_id ) ) {
		return $html . '<p class="comment-empty" data-bang-empty>评论已关闭。</p></div></section>';
	}

	/* 列表始终输出（空或带条目），空状态由 .is-empty 切换；pjax 加载后会被覆盖 */
	$html .= '<ol class="comment-list">' . $list_html . '</ol>';
	$html .= '<p class="comment-empty" data-bang-empty>还没有评论，来说两句。</p>';
	$html .= '</div>';

	/* 0.13.19：站点已有登录体系，评论身份一律由登录态提供。
	   不再输出「完善信息 / 个人信息」按钮与弹窗，游客也不再自己填称呼、邮箱、网站。
	   未登录时只给一条登录引导，点它就地打开登录弹窗（app.js 的 data-bang-auth-open）；
	   data-bang-guest 供提交时判定游客态，直接拦下并引导登录。 */
	$extra = '';
	if ( ! is_user_logged_in() ) {
		$extra = '<div class="comment-login-tip">'
			. '<a class="comment-login-link" href="' . esc_url( home_url( '/login/' ) ) . '"'
			. ' data-bang-auth-open data-no-swup data-bang-guest>登录后参与评论</a>'
			. '</div>';
	}

	/* 回复条：点任意一条评论的「回复」后由 app.js 亮出，写回 comment_parent */
	$reply_bar = '<div class="comment-reply-bar" data-bang-reply-bar hidden>'
		. '<span class="comment-reply-bar-text">正在回复 <strong data-bang-reply-target></strong></span>'
		. '<button type="button" class="comment-reply-cancel" data-bang-reply-cancel>取消回复 '
		. bang_icon( 'close', 12 ) . '</button></div>';

	$comment_field = $reply_bar
		. '<div class="comment-form-comment">'
		. '<textarea id="comment" name="comment" rows="1" required placeholder="写下你的想法" aria-label="评论内容" data-bang-comment-input></textarea>'
		. '</div>'
		/* 人机校验：默认收起，点「发表评论」才展开；拖到底换票据后自动续上提交，
		   服务端 bang_captcha_ok() 再验一次。注册表单仍用常驻滑块，不受影响。 */
		. bang_captcha_html( 'bang_cap', '拖动滑块完成验证', true )
		. $extra;

	add_filter( 'comment_form_fields', 'bang_comment_fields_order', 20 );
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
	remove_filter( 'comment_form_fields', 'bang_comment_fields_order', 20 );
	return $html . '</section>';
}

/**
 * 文章操作条：返回列表 / 返回顶部 / 点赞 / 评论 / 分享，全部纯图标无文字。
 * 与阅读层一并输出，首页展开层和独立文章页共用。
 *
 * PC：底部居中的毛玻璃胶囊，横排（返回列表 / 置顶 / 点赞 / 评论 / 分享）。
 * 手机：右侧贴边的竖排——置顶一颗圆在上，点赞 / 评论 / 分享一整块竖排胶囊，返回上一页
 * 一颗圆落在最下面，四者彼此独立（见 style.css 的 ≤1023 段）；下滑阅读自动淡出。
 * 默认贴右；跟随重力感应可整条换到左侧（见 assets/js/app.js 的 dock-left 类）。
 * 置顶沿用本条 dock 的 .dock-top，不再另外输出独立的 .bang-to-top 按钮。
 * 点赞按钮已恢复（data-bang-like + heart 图标 + bang_like_count 计数，走 bang/v1/social）；
 * 评论按钮保留——它是打开评论抽屉的唯一入口，不能删。
 * 折叠开关已移除、动作始终常驻；data-post 仍保留，供 cmdk「复制本文链接」等按文章上下文工作。
 *
 * @param int   $post_id 内容 ID。
 * @param array $args    可选覆盖：
 *   - back_url   返回链接（默认首页）。图集单页传图片集列表地址。
 *   - permalink  分享链接（默认 get_permalink；非公开 CPT 需显式传）。
 *   - force_back PC 也显示 dock-back（站点页没有 html.reading，默认会被 CSS 隐藏）。
 * @return string
 */
function bang_dock_html( $post_id = 0, $args = array() ) {
	$post_id   = $post_id ? (int) $post_id : (int) get_the_ID();
	$args      = is_array( $args ) ? $args : array();
	$comments  = (int) get_comments_number( $post_id );
	$likes     = (int) get_post_meta( $post_id, 'bang_like_count', true );
	$permalink = ! empty( $args['permalink'] ) ? $args['permalink'] : get_permalink( $post_id );
	$title     = get_the_title( $post_id );
	$excerpt   = function_exists( 'bang_ai_excerpt' ) ? bang_ai_excerpt( $post_id ) : bang_plain_excerpt( $post_id );
	$site_name = get_bloginfo( 'name' );
	$logos     = function_exists( 'bang_logo_urls' ) ? bang_logo_urls() : array( 'light' => '', 'dark' => '' );
	$poster_logo = $logos['light'] ? $logos['light'] : $logos['dark'];
	$back_url    = ! empty( $args['back_url'] ) ? $args['back_url'] : home_url( '/' );
	$force_back  = ! empty( $args['force_back'] );
	$dock_class  = $force_back ? 'bang-dock bang-dock--force-back' : 'bang-dock';

	return '<nav class="' . esc_attr( $dock_class ) . '" aria-label="文章操作" data-post="' . esc_attr( $post_id ) . '">'
		. '<a class="dock-btn dock-back" data-bang-back data-no-swup href="' . esc_url( $back_url ) . '" aria-label="返回列表" title="返回列表">'
		. bang_icon( 'back', 20 ) . '</a>'
		. '<div class="dock-group">'
			. '<button class="dock-btn dock-top" type="button" data-bang-action="top" aria-label="返回顶部" title="返回顶部">'
			. bang_icon( 'top', 20 ) . '</button>'
			. '<button class="dock-btn dock-like" type="button" data-bang-like aria-label="点赞" title="点赞" aria-pressed="false">'
			. bang_icon( 'heart', 20 )
			. '<span class="dock-count" data-dock-like>' . esc_html( $likes ) . '</span></button>'
			. '<button class="dock-btn dock-comments" type="button" data-bang-action="comments" aria-label="评论" title="评论">'
			. bang_icon( 'comment', 20 )
			. '<span class="dock-count" data-dock-comments>' . esc_html( $comments ) . '</span></button>'
			. ( bang_opt( 'ui_share_on' )
				? '<button class="dock-btn dock-share" type="button" data-bang-action="share" aria-label="分享" title="分享"'
					. ' data-url="' . esc_url( $permalink ) . '" data-title="' . esc_attr( $title ) . '"'
					. ' data-desc="' . esc_attr( $excerpt ) . '" data-site="' . esc_attr( $site_name ) . '"'
					. ' data-logo="' . esc_url( $poster_logo ) . '">'
					. bang_icon( 'share', 20 ) . '</button>'
				: '' )
			. '</div>'
		/* 手机端：分享下面单独一颗「返回上一页」圆按钮，不并进上面的动作组。
		   data-bang-back 复用阅读层返回逻辑（history.back()，没有历史就回首页）。 */
		. '<a class="dock-btn dock-prev" data-bang-back data-no-swup href="' . esc_url( $back_url ) . '" aria-label="返回上一页" title="返回上一页">'
		. bang_icon( 'back', 20 ) . '</a>'
		. '</nav>';
}

/**
 * 阅读层内部 HTML。首页内联数据与独立文章页共用，保证两处渲染一致。
 */
function bang_reading_html( $post_id ) {
	$format = bang_format_key( $post_id );
	$title  = get_the_title( $post_id );
	$date   = get_the_date( 'Y.m.d', $post_id );
	$kind   = bang_kind_label( $format );
	$topic  = bang_topic( $post_id );
	$read   = bang_read_time( $post_id );

	$html  = bang_visual( $post_id, true );
	/* 正文 + 评论 + 右栏包一层 .detail-grid：≥1280 整体居中收拢，头图仍在 grid 外满铺 */
	$html .= '<div class="detail-grid">';
	$html .= '<div class="detail-body">';
	$html .= '<h1 id="d-title">' . esc_html( $title ) . '</h1>';
	/* 元信息行：标题下方只留时间 / 分类 / 时长，右侧不再挂分享按钮 */
	$html .= '<div class="detail-meta-row">';
	$html .= '<div class="detail-meta"><span>' . esc_html( $kind ) . '</span><span class="dot"></span>'
		. '<span>' . esc_html( $date ) . '</span><span class="dot"></span>'
		. '<span>' . esc_html( $topic ) . '</span><span class="dot"></span>'
		. '<span>' . esc_html( $read ) . '</span>'
		. ( function_exists( 'bang_rank_views_label' ) ? bang_rank_views_label( $post_id ) : '' )
		. '</div>';
	$html .= '</div>';
	$html .= '<div class="prose">' . bang_content_html( $post_id ) . '</div>';
	$html .= bang_copyright_html( $post_id );
	$html .= '</div>';
	$html .= bang_comments_html( $post_id );

	/* 文章右侧栏：由后台「文章侧栏小工具」配置驱动（作者卡 / 目录 / 打赏 / 最新 / 热门 / 自定义 HTML）。
	   随阅读层一起被 Swup 交换，无需额外同步；sidebar.php 缺失时回退旧的作者卡 + 目录。 */
	$rail = function_exists( 'bang_sidebar_widgets_html' )
		? bang_sidebar_widgets_html( $post_id )
		: ( bang_author_card_html( $post_id ) . bang_toc_html( $post_id ) );
	if ( $rail ) {
		$html .= '<aside class="read-rail" aria-label="文章侧栏">' . $rail . '</aside>';
	}
	$html .= '</div>';

	$html .= bang_dock_html( $post_id );

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
function bang_post_nav_html( $prev_url, $next_url, $prev_title, $next_title ) {
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
	$html = '<nav class="bang-post-nav" aria-label="文章翻页">';
	foreach ( $items as $it ) {
		$title = trim( (string) $it['title'] );
		$title = '' !== $title ? $title : '没有更多了';
		$ico   = '<span class="bang-post-nav-ico" aria-hidden="true">' . bang_icon( $it['icon'], 16 ) . '</span>';
		$body  = '<span class="bang-post-nav-body">'
			. '<span class="bang-post-nav-label">' . esc_html( $it['label'] ) . '</span>'
			. '<span class="bang-post-nav-title">' . esc_html( $title ) . '</span>'
			. '</span>';
		$inner = 'prev' === $it['dir'] ? $ico . $body : $body . $ico;
		$align = 'next' === $it['dir'] ? ' is-next' : '';
		if ( $it['url'] ) {
			$html .= '<a class="bang-post-nav-item' . $align . '" href="' . esc_url( $it['url'] ) . '"'
				. ' data-bang-post-nav="' . esc_attr( $it['dir'] ) . '"'
				. ' aria-label="' . esc_attr( $it['label'] . '：' . $title ) . '">' . $inner . '</a>';
		} else {
			$html .= '<span class="bang-post-nav-item is-disabled' . $align . '" aria-disabled="true">' . $inner . '</span>';
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
function bang_icon( $name, $size = 18, $class = '' ) {
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
			// Solar Linear 图标：去注释/自带 class，统一描边到主题 1.75 视觉重量。
			$raw = preg_replace( '/<!--.*?-->/s', '', $raw );
			$raw = preg_replace( '/\sclass="[^"]*"/', '', $raw, 1 );
			$raw = preg_replace( '/stroke-width="[^"]*"/', 'stroke-width="1.75"', $raw, 1 );
			$raw = trim( preg_replace( '/\s+/', ' ', $raw ) );
		}
		$cache[ $file ] = $raw;
	}
	$svg = $cache[ $file ];
	if ( ! $svg ) {
		return '';
	}

	$size  = max( 8, min( 64, (int) $size ) );
	$class = trim( 'bang-icon ' . preg_replace( '/[^a-zA-Z0-9 _-]/', '', $class ) );
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
function bang_logo_urls() {
	$light = trim( (string) bang_opt( 'logo_url' ) );
	$dark  = trim( (string) bang_opt( 'logo_dark_url' ) );
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
function bang_logo_html( $class, $size, $alt = '' ) {
	$urls = bang_logo_urls();
	if ( '' === $urls['light'] && '' === $urls['dark'] ) {
		return '';
	}
	$size = max( 8, (int) $size );
	$cls  = trim( preg_replace( '/[^a-zA-Z0-9 _-]/', '', (string) $class ) );
	$err  = " onerror=\"this.style.display='none';this.parentNode.classList.add('logo-failed')\"";
	$out  = '';
	$pair = array(
		'light' => array( 'bang-logo-light', $urls['light'] ),
		'dark'  => array( 'bang-logo-dark',  $urls['dark'] ),
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
