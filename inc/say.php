<?php
/**
 * 「说说」文章类型：轻博客卡片
 *
 * - CPT pk_say，归档页 /say/（响应式卡片流），后台独立菜单「写说说」
 * - 侧栏「说说」入口指向归档页
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'pk_register_say_post_type' );
/**
 * 注册说说文章类型。
 */
function pk_register_say_post_type() {
	register_post_type(
		'pk_say',
		array(
			'labels'              => array(
				'name'          => '说说',
				'singular_name' => '说说',
				'add_new'       => '写说说',
				'add_new_item'  => '写一条说说',
				'edit_item'     => '编辑说说',
				'new_item'      => '新说说',
				'view_item'     => '查看说说',
				'search_items'  => '搜索说说',
				'not_found'     => '还没有说说',
				'not_found_in_trash' => '回收站里没有说说',
			),
			'public'              => true,
			'exclude_from_search' => true,
			'show_in_rest'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-format-status',
			'supports'            => array( 'title', 'editor' ),
			'has_archive'         => true,
			'rewrite'             => array(
				'slug'       => 'say',
				'with_front' => false,
			),
		)
	);
}

add_filter( 'manage_pk_say_posts_columns', 'pk_say_columns' );
/**
 * 后台列表：用内容摘要替代标题列。
 *
 * @param array $columns 列。
 * @return array
 */
function pk_say_columns( $columns ) {
	if ( isset( $columns['title'] ) ) {
		$columns['title'] = '内容';
	}
	return $columns;
}

add_filter( 'the_title', 'pk_say_auto_title', 10, 2 );
/**
 * 说说无标题时自动用内容前若干字占位，保证后台可辨、SEO 标签不空。
 *
 * @param string $title 标题。
 * @param int    $id    文章 ID。
 * @return string
 */
function pk_say_auto_title( $title, $id ) {
	if ( $title || 'pk_say' !== get_post_type( $id ) || is_admin() && defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST ) {
		return $title;
	}
	$text = wp_strip_all_tags( get_post_field( 'post_content', $id ) );
	$text = trim( preg_replace( '/\s+/u', ' ', $text ) );
	if ( ! $text ) {
		return '（空说说）';
	}
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $text, 0, 24, 'UTF-8' ) . ( mb_strlen( $text, 'UTF-8' ) > 24 ? '…' : '' );
	}
	return substr( $text, 0, 48 ) . ( strlen( $text ) > 48 ? '…' : '' );
}

/* ------------------------------------------------------------
   0.13.0 · 片刻位置：发布时可附一个地点，前台时间轴显示小标签。
   只存文本，不做 IP 归属查询，隐私上更干净。
   ------------------------------------------------------------ */
add_action( 'add_meta_boxes', 'pk_say_location_box_add' );
/**
 * 注册「分享位置」侧栏框。
 */
function pk_say_location_box_add() {
	add_meta_box(
		'pk_say_location',
		'分享位置',
		'pk_say_location_box_html',
		'pk_say',
		'side',
		'high'
	);
}

/**
 * 位置输入框。
 *
 * @param WP_Post $post 当前说说。
 */
function pk_say_location_box_html( $post ) {
	wp_nonce_field( 'pk_say_location_save', 'pk_say_location_nonce' );
	echo '<p class="description">可选，例如「杭州 · 西湖」。留空则不显示。</p>';
	echo '<input class="widefat" type="text" name="pk_say_location" maxlength="60"'
		. ' value="' . esc_attr( (string) get_post_meta( $post->ID, 'pk_say_location', true ) ) . '"'
		. ' placeholder="在什么地方写下的">';
}

add_action( 'save_post_pk_say', 'pk_say_location_save' );
/**
 * 保存位置字段。
 *
 * @param int $post_id 说说 ID。
 */
function pk_say_location_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['pk_say_location_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pk_say_location_nonce'] ) ), 'pk_say_location_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$value = isset( $_POST['pk_say_location'] )
		? sanitize_text_field( wp_unslash( $_POST['pk_say_location'] ) )
		: '';
	if ( '' === $value ) {
		delete_post_meta( $post_id, 'pk_say_location' );
	} else {
		update_post_meta( $post_id, 'pk_say_location', $value );
	}
}

/**
 * 前台位置标签 HTML。
 *
 * @param int $post_id 说说 ID。
 * @return string
 */
function pk_say_location_html( $post_id ) {
	$place = trim( (string) get_post_meta( (int) $post_id, 'pk_say_location', true ) );
	if ( '' === $place ) {
		return '';
	}
	return '<span class="say-loc">' . pk_kit_icon( 'pin', 13 ) . esc_html( $place ) . '</span>';
}

/* ------------------------------------------------------------
   0.13.25 · /say/ 归档由时间轴改为卡片流：每条说说带作者头像、昵称、时间、
   位置与详情入口。归档页与单条页共用这一份 HTML，避免两处结构漂移。
   ------------------------------------------------------------ */
/**
 * 说说卡片统一显示的「站长」真实身份（昵称 + 头像 HTML）。
 *
 * 演示说说的 post_author 常指向无效用户，旧逻辑回退成占位的「站长」+ 首字「站」。
 * 这里对齐文章作者卡的解析：站长 = 发文最多的作者；昵称取 display_name →
 * user_login → 站点名；头像按「后台作者头像 → 用户资料头像（附件 ID 优先）→
 * 站点 Logo → 昵称首字」，不走 Gravatar。同一请求内按尺寸缓存，卡片流不重复查库。
 *
 * @param int $size 头像像素。
 * @return array{name:string, avatar:string}
 */
function pk_say_owner_identity( $size = 40 ) {
	static $cache = array();
	$size         = max( 1, (int) $size );
	if ( isset( $cache[ $size ] ) ) {
		return $cache[ $size ];
	}

	$users = get_users(
		array(
			'number'  => 1,
			'orderby' => 'post_count',
			'order'   => 'DESC',
			'who'     => 'authors',
		)
	);
	$owner = ! empty( $users ) ? $users[0] : null;

	$name = $owner ? (string) ( $owner->display_name ? $owner->display_name : $owner->user_login ) : '';
	if ( '' === $name ) {
		$name = (string) get_bloginfo( 'name' );
	}
	if ( '' === $name ) {
		$name = '站长';
	}

	$url = function_exists( 'pk_safe_comment_site' ) ? pk_safe_comment_site( (string) pk_opt( 'author_avatar' ) ) : '';
	if ( ! $url && $owner && function_exists( 'pk_user_avatar_url' ) ) {
		$url = pk_user_avatar_url( $owner, $size );
	}
	if ( ! $url && function_exists( 'pk_safe_comment_site' ) ) {
		$url = pk_safe_comment_site( (string) pk_opt( 'logo_url' ) );
	}

	if ( $url ) {
		$avatar = '<img class="user-avatar" src="' . esc_url( $url ) . '" alt="" width="' . $size . '" height="' . $size . '" loading="lazy" decoding="async">';
	} else {
		$char   = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1, 'UTF-8' ) : substr( $name, 0, 1 );
		$avatar = '<span class="user-avatar user-avatar-letter" aria-hidden="true">' . esc_html( $char ) . '</span>';
	}

	$cache[ $size ] = array(
		'name'   => $name,
		'avatar' => $avatar,
	);
	return $cache[ $size ];
}

/**
 * 单条说说卡片 HTML。
 *
 * 头像与昵称统一用 pk_say_owner_identity()（站长真实身份，与文章作者卡同口径）；
 * 时间用带 datetime 的 <time>，正文仍保持原模板的 wpautop + wp_kses_post 输出口径，
 * 不套 the_content 过滤器以免触发阅读解锁等逻辑。
 *
 * @param int    $post_id 说说 ID，0 表示当前全局文章。
 * @param string $context archive（归档，带详情入口、整卡可点）/ single（单条页放大态）。
 * @return string
 */
function pk_say_card_html( $post_id = 0, $context = 'archive' ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	/* 头像 / 昵称统一用站长的真实身份（与文章作者卡同口径），不再读说说的 post_author
	   占位资料；演示说说的作者往往没有真实昵称和头像。 */
	$owner  = pk_say_owner_identity( 40 );
	$name   = $owner['name'];
	$avatar = $owner['avatar'];

	$date_c = (string) get_the_date( 'c', $post_id );
	$date_h = (string) get_the_date( 'Y.m.d H:i', $post_id );
	$link   = (string) get_permalink( $post_id );
	$place  = pk_say_location_html( $post_id );
	$body   = wp_kses_post( wpautop( (string) get_post_field( 'post_content', $post_id ) ) );

	$is_single = ( 'single' === $context );
	$card_cls  = 'say-card' . ( $is_single ? ' say-card--open' : '' );
	/* data-say-id 给 Swup 共享元素 morph 用；data-say-url 保留单条页直访链接 */
	$html  = '<article class="' . esc_attr( $card_cls ) . '" id="say-' . esc_attr( (string) $post_id ) . '" data-say-id="' . esc_attr( (string) $post_id ) . '" data-say-url="' . esc_url( $link ) . '">';
	$html .= '<header class="say-card-head">';
	$html .= '<span class="say-avatar">' . $avatar . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 内部已转义
	$html .= '<div class="say-author">';
	$html .= '<span class="say-name">' . esc_html( $name ) . '</span>';
	$html .= '<time class="say-time" datetime="' . esc_attr( $date_c ) . '">' . esc_html( $date_h ) . '</time>';
	$html .= '</div>';
	$html .= '</header>';
	$html .= '<div class="say-content">' . $body . '</div>';
	/* 卡片底部：点赞按钮在左，紧跟点赞人头像（最多 3 枚 + 省略号），有位置时靠右 */
	$html .= '<footer class="say-foot">';
	$html .= pk_say_like_button_html( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 内部字段均已转义
	$html .= '<span class="say-likers" data-pk-say-likers>' . pk_say_likers_html( $post_id ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 内部字段均已转义
	if ( $place ) {
		$html .= $place; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pk_say_location_html 内部已转义
	}
	$html .= '</footer>';
	$html .= '</article>';
	return $html;
}

/* ------------------------------------------------------------
   卡片点赞：按钮常驻卡片底部，走 pianke/v1/social（与文章同一套点赞）。
   点赞人反向索引 pk_say_like_users 仍由 inc/social.php 同步维护，供点赞接口返回
   likersHtml；说说评论功能已整体移除，不再有评论列表 / 表单 / 互动面板。
   ------------------------------------------------------------ */

/**
 * 卡片上的点赞按钮 HTML（带计数，常驻可见）
 *
 * @param int $post_id 说说 ID。
 * @return string
 */
function pk_say_like_button_html( $post_id ) {
	$post_id = (int) $post_id;
	$uid     = get_current_user_id();
	$liked   = $uid && in_array( $post_id, pk_social_ids( $uid ), true );
	$count   = (int) get_post_meta( $post_id, 'pk_like_count', true );
	$html    = '<button class="say-like-btn' . ( $liked ? ' is-on' : '' ) . '" type="button"'
		. ' data-pk-say-like="' . esc_attr( (string) $post_id ) . '"'
		. ' aria-pressed="' . ( $liked ? 'true' : 'false' ) . '" aria-label="点赞">'
		. pk_icon( 'heart', 15 )
		. '<span class="say-like-count" data-pk-say-like-count>' . (int) $count . '</span>'
		. '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pk_icon 输出已净化
	return $html;
}

/**
 * 点赞人 ID 列表
 *
 * @param int $post_id 说说 ID。
 * @return int[]
 */
function pk_say_like_users( $post_id ) {
	$ids = get_post_meta( (int) $post_id, 'pk_say_like_users', true );
	if ( ! is_array( $ids ) ) {
		return array();
	}
	return array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
}

/**
 * 一枚头像：有自定义头像用真图，没有回退昵称首字，不走 Gravatar
 *
 * @param int    $uid  用户 ID，0 表示游客。
 * @param string $name 显示名。
 * @param int    $size 尺寸。
 * @return string
 */
function pk_say_avatar_html( $uid, $name, $size = 24 ) {
	$u = $uid ? get_userdata( (int) $uid ) : null;
	if ( $u ) {
		return pk_user_avatar( $u, $size );
	}
	$char = function_exists( 'mb_substr' ) ? mb_substr( (string) $name, 0, 1, 'UTF-8' ) : substr( (string) $name, 0, 1 );
	return '<span class="user-avatar user-avatar-letter" aria-hidden="true">' . esc_html( $char ) . '</span>';
}

/**
 * 点赞人头像行：最多 3 枚，超出的折叠成一个省略号
 *
 * @param int $post_id 说说 ID。
 * @return string
 */
function pk_say_likers_html( $post_id ) {
	$ids = pk_say_like_users( $post_id );
	if ( ! $ids ) {
		return '<span class="pk-say-likers-empty">还没有人点赞</span>';
	}
	$total = count( $ids );
	$html  = '';
	foreach ( array_slice( $ids, 0, 3 ) as $uid ) {
		$u    = get_userdata( $uid );
		$name = $u ? (string) ( $u->display_name ? $u->display_name : $u->user_login ) : '访客';
		$html .= '<span class="pk-say-liker" title="' . esc_attr( $name ) . '">' . pk_say_avatar_html( $uid, $name, 24 ) . '</span>';
	}
	if ( $total > 3 ) {
		$html .= '<span class="pk-say-likers-more" title="共 ' . (int) $total . ' 人点赞" aria-label="还有 ' . (int) ( $total - 3 ) . ' 人">…</span>';
	}
	return $html;
}

