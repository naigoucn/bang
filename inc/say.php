<?php
/**
 * 「说说」文章类型：轻博客卡片
 *
 * - CPT bang_say，归档页 /say/（响应式卡片流），后台独立菜单「写说说」
 * - 侧栏「说说」入口指向归档页
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'bang_register_say_post_type' );
/**
 * 注册说说文章类型。
 */
function bang_register_say_post_type() {
	register_post_type(
		'bang_say',
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

/**
 * 说说归档重写规则自愈：切换固定链接 / 规则缓存重建后，/say/ 偶尔会回退到首页
 * （此时只有 /?post_type=bang_say 能进归档）。每次 init 检测，缺失才 flush 一次。
 * CPT 在 init 10 注册，这里挂 20 保证 flush 时规则已完整。
 */
add_action( 'init', 'bang_say_rewrite_self_heal', 20 );
function bang_say_rewrite_self_heal() {
	$rules = get_option( 'rewrite_rules' );
	if ( is_array( $rules ) ) {
		foreach ( $rules as $pattern => $query ) {
			if ( 0 === strpos( (string) $pattern, 'say' ) && false !== strpos( (string) $query, 'bang_say' ) ) {
				return;
			}
		}
	}
	flush_rewrite_rules( false );
}

add_filter( 'manage_bang_say_posts_columns', 'bang_say_columns' );
/**
 * 后台列表：用内容摘要替代标题列。
 *
 * @param array $columns 列。
 * @return array
 */
function bang_say_columns( $columns ) {
	if ( isset( $columns['title'] ) ) {
		$columns['title'] = '内容';
	}
	return $columns;
}

add_filter( 'the_title', 'bang_say_auto_title', 10, 2 );
/**
 * 说说无标题时自动用内容前若干字占位，保证后台可辨、SEO 标签不空。
 *
 * @param string $title 标题。
 * @param int    $id    文章 ID。
 * @return string
 */
function bang_say_auto_title( $title, $id ) {
	if ( $title || 'bang_say' !== get_post_type( $id ) || is_admin() && defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST ) {
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
   片刻位置：发布时可选一个结构化地区（省 / 市 / 区 + 详细地点），
   前台卡片显示定位标签。只存文本，不做 IP 归属查询，隐私更干净。
   同时支持说说多图：图片进入图片集页，灯箱复用全站 PhotoSwipe。
   ------------------------------------------------------------ */
/**
 * 常用省份 / 直辖市清单（省份下拉用；允许留空）。
 *
 * @return string[]
 */
function bang_say_provinces() {
	return array(
		'北京', '天津', '上海', '重庆', '河北', '山西', '辽宁', '吉林', '黑龙江',
		'江苏', '浙江', '安徽', '福建', '江西', '山东', '河南', '湖北', '湖南',
		'广东', '海南', '四川', '贵州', '云南', '陕西', '甘肃', '青海',
		'内蒙古', '广西', '西藏', '宁夏', '新疆', '香港', '澳门', '台湾', '海外',
	);
}

add_action( 'add_meta_boxes', 'bang_say_location_box_add' );
/**
 * 注册「位置与图片」侧栏框。
 */
function bang_say_location_box_add() {
	add_meta_box(
		'bang_say_location',
		'位置与图片',
		'bang_say_location_box_html',
		'bang_say',
		'side',
		'high'
	);
}

/**
 * 地区 + 图片输入框。
 *
 * @param WP_Post $post 当前说说。
 */
function bang_say_location_box_html( $post ) {
	wp_nonce_field( 'bang_say_location_save', 'bang_say_location_nonce' );
	$get = function ( $key ) use ( $post ) {
		return (string) get_post_meta( $post->ID, $key, true );
	};
	echo '<p class="description">地区可选。选择省份、填写城市区县，前台显示定位标签；图片可选，同步进图片集页。</p>';
	echo '<p><label for="bang_say_province"><strong>省份</strong></label>'
		. '<select class="widefat" id="bang_say_province" name="bang_say_province">'
		. '<option value="">未选择</option>';
	foreach ( bang_say_provinces() as $p ) {
		echo '<option value="' . esc_attr( $p ) . '"' . selected( $get( 'bang_say_province' ), $p, false ) . '>' . esc_html( $p ) . '</option>';
	}
	echo '</select></p>';
	echo '<p><label for="bang_say_city"><strong>城市</strong></label><input class="widefat" type="text" id="bang_say_city" name="bang_say_city" maxlength="40" value="' . esc_attr( $get( 'bang_say_city' ) ) . '" placeholder="如：杭州"></p>';
	echo '<p><label for="bang_say_district"><strong>区县 / 地点</strong></label><input class="widefat" type="text" id="bang_say_district" name="bang_say_district" maxlength="60" value="' . esc_attr( $get( 'bang_say_district' ) ) . '" placeholder="如：西湖区 · 龙井路"></p>';
	echo '<p><label for="bang_say_location"><strong>自定义地点文本</strong></label><input class="widefat" type="text" id="bang_say_location" name="bang_say_location" maxlength="80" value="' . esc_attr( $get( 'bang_say_location' ) ) . '" placeholder="留空则用上面的省 / 市 / 区拼接"></p>';

	$ids = bang_say_gallery_ids( $post->ID );
	echo '<div class="bang-say-gallery-field" data-bang-say-gallery>';
	echo '<p><strong>说说图片</strong><span class="description"> （点开进灯箱，同步进图片集页）</span></p>';
	echo '<input type="hidden" name="bang_say_gallery" id="bang_say_gallery" value="' . esc_attr( implode( ',', array_map( 'intval', $ids ) ) ) . '" data-bang-say-gallery-input>';
	echo '<div class="bang-say-gallery-list" data-bang-say-gallery-list>';
	foreach ( $ids as $aid ) {
		$thumb = wp_get_attachment_image_src( $aid, 'thumbnail' );
		echo '<span class="bang-say-gallery-item" data-id="' . (int) $aid . '">'
			. ( $thumb ? '<img src="' . esc_url( $thumb[0] ) . '" alt="">' : '' )
			. '<button type="button" class="bang-say-gallery-remove" aria-label="移除">×</button></span>';
	}
	echo '</div>';
	echo '<button type="button" class="button" data-bang-say-gallery-pick>从媒体库选择图片</button>';
	echo '<button type="button" class="button" data-bang-say-gallery-clear>清空</button>';
	echo '</div>';
	?>
	<script>
	(function () {
		var wrap = document.querySelector('[data-bang-say-gallery]');
		if (!wrap || typeof wp === 'undefined' || !wp.media) { return; }
		var input = wrap.querySelector('[data-bang-say-gallery-input]');
		var list = wrap.querySelector('[data-bang-say-gallery-list]');
		function ids() { return input.value ? input.value.split(',').map(Number).filter(Boolean) : []; }
		function render(arr) {
			arr = arr.filter(function (v, i, a) { return a.indexOf(v) === i; });
			input.value = arr.join(',');
			list.innerHTML = '';
			arr.forEach(function (id) {
				var s = document.createElement('span');
				s.className = 'bang-say-gallery-item';
				s.setAttribute('data-id', id);
				s.innerHTML = '<button type="button" class="bang-say-gallery-remove" aria-label="移除">×</button>';
				list.appendChild(s);
			});
		}
		wrap.querySelector('[data-bang-say-gallery-pick]').addEventListener('click', function () {
			var frame = wp.media({ title: '选择说说图片', multiple: true, library: { type: 'image' } });
			frame.on('select', function () {
				var arr = ids();
				frame.state().get('selection').each(function (att) { arr.push(att.toJSON().id); });
				render(arr);
			});
			frame.open();
		});
		wrap.querySelector('[data-bang-say-gallery-clear]').addEventListener('click', function () { render([]); });
		list.addEventListener('click', function (e) {
			var btn = e.target.closest('.bang-say-gallery-remove');
			if (!btn) { return; }
			var item = btn.closest('.bang-say-gallery-item');
			render(ids().filter(function (n) { return n !== Number(item.getAttribute('data-id')); }));
		});
	})();
	</script>
	<?php
}

add_action( 'save_post_bang_say', 'bang_say_location_save' );
/**
 * 保存地区 / 图片字段。
 *
 * @param int $post_id 说说 ID。
 */
function bang_say_location_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['bang_say_location_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bang_say_location_nonce'] ) ), 'bang_say_location_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array( 'bang_say_province', 'bang_say_city', 'bang_say_district', 'bang_say_location' ) as $key ) {
		$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
	$ids = isset( $_POST['bang_say_gallery'] ) ? wp_unslash( $_POST['bang_say_gallery'] ) : '';
	$ids = array_values( array_unique( array_filter( array_map( 'intval', explode( ',', (string) $ids ) ) ) ) );
	if ( $ids ) {
		update_post_meta( $post_id, 'bang_say_gallery', $ids );
	} else {
		delete_post_meta( $post_id, 'bang_say_gallery' );
	}
}

/**
 * 说说图片附件 ID 列表。
 *
 * @param int $post_id 说说 ID。
 * @return int[]
 */
function bang_say_gallery_ids( $post_id ) {
	$ids = get_post_meta( (int) $post_id, 'bang_say_gallery', true );
	if ( ! is_array( $ids ) ) {
		return array();
	}
	return array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
}

/**
 * 前台位置标签 HTML：优先结构化省 / 市 / 区，回退旧文本。
 *
 * @param int $post_id 说说 ID。
 * @return string
 */
function bang_say_location_html( $post_id ) {
	$post_id = (int) $post_id;
	$custom  = trim( (string) get_post_meta( $post_id, 'bang_say_location', true ) );
	if ( '' !== $custom ) {
		$place = $custom;
	} else {
		$parts = array();
		foreach ( array( 'bang_say_province', 'bang_say_city', 'bang_say_district' ) as $key ) {
			$v = trim( (string) get_post_meta( $post_id, $key, true ) );
			if ( '' !== $v ) {
				$parts[] = $v;
			}
		}
		$place = implode( ' · ', $parts );
	}
	if ( '' === $place ) {
		return '';
	}
	/* 点击地点跳足迹页并定位：只带省/市/区（结构化字段），自定义文本回退用整串 */
	$fp_url = function_exists( 'bang_site_page_url' ) ? bang_site_page_url( 'footprint' ) : home_url( '/footprint/' );
	$fp_key = implode( ' ', array_filter( array(
		trim( (string) get_post_meta( $post_id, 'bang_say_city', true ) ),
		trim( (string) get_post_meta( $post_id, 'bang_say_province', true ) ),
	) ) );
	if ( '' === $fp_key ) {
		$fp_key = $place;
	}
	$fp_link = add_query_arg( 'bang_loc', rawurlencode( $fp_key ), $fp_url );
	return '<a class="say-loc say-loc-link" href="' . esc_url( $fp_link ) . '">' . bang_kit_icon( 'pin', 13 ) . esc_html( $place ) . '</a>';
}

/**
 * 说说图片网格 HTML（复用全站 PhotoSwipe 灯箱）。
 *
 * @param int $post_id 说说 ID。
 * @return string
 */
function bang_say_gallery_html( $post_id ) {
	$ids = bang_say_gallery_ids( $post_id );
	if ( ! $ids ) {
		return '';
	}
	$html = '<div class="say-gallery" data-bang-gallery data-caption="' . esc_attr( get_the_title( $post_id ) ) . '">';
	foreach ( $ids as $i => $aid ) {
		$full  = (string) wp_get_attachment_image_url( $aid, 'full' );
		$thumb = (string) wp_get_attachment_image_url( $aid, 'medium_large' );
		if ( ! $full ) {
			continue;
		}
		$html .= '<a class="say-gallery-item" data-bang-lightbox data-index="' . (int) $i . '" href="' . esc_url( $full ) . '" aria-label="查看大图">'
			. '<img src="' . esc_url( $thumb ? $thumb : $full ) . '" alt="" loading="lazy" decoding="async">'
			. '</a>';
	}
	$html .= '</div>';
	return $html;
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
function bang_say_owner_identity( $size = 40 ) {
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

	/* 头像统一用账号头像（与作者卡 / 评论 / 个人中心同源），后台「作者头像」设置已移除 */
	$url = ( $owner && function_exists( 'bang_user_avatar_url' ) ) ? bang_user_avatar_url( $owner, $size ) : '';
	if ( ! $url && function_exists( 'bang_safe_comment_site' ) ) {
		$url = bang_safe_comment_site( (string) bang_opt( 'logo_url' ) );
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
 * 头像与昵称统一用 bang_say_owner_identity()（站长真实身份，与文章作者卡同口径）；
 * 时间用带 datetime 的 <time>，正文仍保持原模板的 wpautop + wp_kses_post 输出口径，
 * 不套 the_content 过滤器以免触发阅读解锁等逻辑。
 *
 * @param int    $post_id 说说 ID，0 表示当前全局文章。
 * @param string $context archive（归档，带详情入口、整卡可点）/ single（单条页放大态）。
 * @return string
 */
function bang_say_card_html( $post_id = 0, $context = 'archive' ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	/* 头像 / 昵称统一用站长的真实身份（与文章作者卡同口径），不再读说说的 post_author
	   占位资料；演示说说的作者往往没有真实昵称和头像。 */
	$owner  = bang_say_owner_identity( 40 );
	$name   = $owner['name'];
	$avatar = $owner['avatar'];

	$date_c = (string) get_the_date( 'c', $post_id );
	$date_h = (string) get_the_date( 'Y.m.d H:i', $post_id );
	$link   = (string) get_permalink( $post_id );
	$place  = bang_say_location_html( $post_id );
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
	$html .= bang_say_gallery_html( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 内部字段均已转义
	/* 卡片底部：点赞按钮在左，紧跟点赞人头像（最多 3 枚 + 省略号），有位置时靠右 */
	$html .= '<footer class="say-foot">';
	$html .= bang_say_like_button_html( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 内部字段均已转义
	$html .= '<span class="say-likers" data-bang-say-likers>' . bang_say_likers_html( $post_id ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 内部字段均已转义
	if ( $place ) {
		$html .= $place; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bang_say_location_html 内部已转义
	}
	$html .= '</footer>';
	$html .= '</article>';
	return $html;
}

/* ------------------------------------------------------------
   卡片点赞：按钮常驻卡片底部，走 bang/v1/social（与文章同一套点赞）。
   点赞人反向索引 bang_say_like_users 仍由 inc/social.php 同步维护，供点赞接口返回
   likersHtml；说说评论功能已整体移除，不再有评论列表 / 表单 / 互动面板。
   ------------------------------------------------------------ */

/**
 * 卡片上的点赞按钮 HTML（带计数，常驻可见）
 *
 * @param int $post_id 说说 ID。
 * @return string
 */
function bang_say_like_button_html( $post_id ) {
	$post_id = (int) $post_id;
	$uid     = get_current_user_id();
	$liked   = $uid && in_array( $post_id, bang_social_ids( $uid ), true );
	$count   = (int) get_post_meta( $post_id, 'bang_like_count', true );
	$html    = '<button class="say-like-btn' . ( $liked ? ' is-on' : '' ) . '" type="button"'
		. ' data-bang-say-like="' . esc_attr( (string) $post_id ) . '"'
		. ' aria-pressed="' . ( $liked ? 'true' : 'false' ) . '" aria-label="点赞">'
		. bang_icon( 'heart', 15 )
		. '<span class="say-like-count" data-bang-say-like-count>' . (int) $count . '</span>'
		. '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bang_icon 输出已净化
	return $html;
}

/**
 * 点赞人 ID 列表
 *
 * @param int $post_id 说说 ID。
 * @return int[]
 */
function bang_say_like_users( $post_id ) {
	$ids = get_post_meta( (int) $post_id, 'bang_say_like_users', true );
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
function bang_say_avatar_html( $uid, $name, $size = 24 ) {
	$u = $uid ? get_userdata( (int) $uid ) : null;
	if ( $u ) {
		return bang_user_avatar( $u, $size );
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
function bang_say_likers_html( $post_id ) {
	$ids = bang_say_like_users( $post_id );
	if ( ! $ids ) {
		return '<span class="bang-say-likers-empty">还没有人点赞</span>';
	}
	$total = count( $ids );
	$html  = '';
	foreach ( array_slice( $ids, 0, 3 ) as $uid ) {
		$u    = get_userdata( $uid );
		$name = $u ? (string) ( $u->display_name ? $u->display_name : $u->user_login ) : '访客';
		$html .= '<span class="bang-say-liker" title="' . esc_attr( $name ) . '">' . bang_say_avatar_html( $uid, $name, 24 ) . '</span>';
	}
	if ( $total > 3 ) {
		$html .= '<span class="bang-say-likers-more" title="共 ' . (int) $total . ' 人点赞" aria-label="还有 ' . (int) ( $total - 3 ) . ' 人">…</span>';
	}
	return $html;
}

