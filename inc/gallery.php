<?php
/**
 * 图片集（CPT）：把相册作为独立内容存放，一条图片集 = 一张瀑布流卡片。
 *
 * - CPT bang_gallery，后台独立菜单「图片集」，前台只走 /gallery/ 站点页
 * - 字段存 post_meta：图片墙 / 省市区 / 详细地址 / 拍摄日期 / 设备
 * - 不再聚合足迹、说说与文章特色图；数据全部来自后台手动建立的图片集相册
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------
   CPT 注册
   ------------------------------------------------------------ */
add_action( 'init', 'bang_gal_register_cpt' );
/**
 * 注册图片集文章类型。
 */
function bang_gal_register_cpt() {
	register_post_type(
		'bang_gallery',
		array(
			'labels'              => array(
				'name'          => '图片集',
				'singular_name' => '图片集',
				'add_new'       => '添加图片集',
				'add_new_item'  => '添加新图片集',
				'edit_item'     => '编辑图片集',
				'new_item'      => '新图片集',
				'view_item'     => '查看图片集',
				'search_items'  => '搜索图片集',
				'not_found'     => '还没有图片集',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'menu_position'       => 7,
			'menu_icon'           => 'dashicons-format-gallery',
			'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'comments' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
		)
	);
}

/* 已有相册在 CPT 支持 comments 之前建立，comment_status 可能仍是 closed。
   按 post type 放行前台评论；主题自己的 show_comments 开关仍由 bang_comments_html 把控。 */
add_filter( 'comments_open', 'bang_gal_comments_open', 10, 2 );
function bang_gal_comments_open( $open, $post_id ) {
	if ( $post_id && 'bang_gallery' === get_post_type( $post_id ) ) {
		return true;
	}
	return $open;
}

/**
 * 字段 key => 中文标签（后台 meta box 与读取共用）。
 *
 * @return array
 */
function bang_gal_meta_fields() {
	return array(
		'bang_gal_gallery'  => '图片',
		'bang_gal_province' => '省份',
		'bang_gal_city'     => '城市',
		'bang_gal_district' => '区县',
		'bang_gal_address'  => '详细地址',
		'bang_gal_date'     => '拍摄日期',
		'bang_gal_device'   => '拍摄设备',
	);
}

/* ------------------------------------------------------------
   后台 meta box
   ------------------------------------------------------------ */
add_action( 'add_meta_boxes', 'bang_gal_box_add' );
/**
 * 注册图片集字段框。
 */
function bang_gal_box_add() {
	add_meta_box( 'bang_gal_data', '图片集信息', 'bang_gal_box_html', 'bang_gallery', 'normal', 'high' );
}

/**
 * 字段框 HTML。
 *
 * @param WP_Post $post 当前图片集。
 */
function bang_gal_box_html( $post ) {
	wp_nonce_field( 'bang_gal_save', 'bang_gal_nonce' );
	$get     = function ( $key ) use ( $post ) {
		return get_post_meta( $post->ID, $key, true );
	};
	$gallery = bang_gal_gallery_ids( $post->ID );
	?>
	<style>
	.bang-gal-box-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 16px}
	.bang-gal-box-grid p{margin:8px 0}
	.bang-gal-box-list{display:flex;flex-wrap:wrap;gap:8px;margin:10px 0}
	.bang-gal-box-item{position:relative;display:inline-block;width:76px;height:76px;border:1px solid #ccd0d4;border-radius:4px;overflow:hidden;background:#f0f0f0}
	.bang-gal-box-item img{width:100%;height:100%;object-fit:cover;display:block}
	.bang-gal-box-remove{position:absolute;top:0;right:0;width:20px;height:20px;line-height:18px;text-align:center;border:0;background:rgba(0,0,0,.6);color:#fff;cursor:pointer;padding:0}
	</style>
	<div class="bang-gal-box">
		<p class="description">相册名称填在上方「标题」，说明填在正文。封面取第一张图，其余图片按顺序进入灯箱序列。</p>
		<div class="bang-gal-box-grid">
			<p><label for="bang_gal_province">省份</label><input class="widefat" type="text" id="bang_gal_province" name="bang_gal_province" value="<?php echo esc_attr( (string) $get( 'bang_gal_province' ) ); ?>" placeholder="如：浙江"></p>
			<p><label for="bang_gal_city">城市</label><input class="widefat" type="text" id="bang_gal_city" name="bang_gal_city" value="<?php echo esc_attr( (string) $get( 'bang_gal_city' ) ); ?>" placeholder="如：杭州"></p>
			<p><label for="bang_gal_district">区县</label><input class="widefat" type="text" id="bang_gal_district" name="bang_gal_district" value="<?php echo esc_attr( (string) $get( 'bang_gal_district' ) ); ?>" placeholder="如：西湖区"></p>
			<p><label for="bang_gal_date">拍摄日期</label><input class="widefat" type="date" id="bang_gal_date" name="bang_gal_date" value="<?php echo esc_attr( (string) $get( 'bang_gal_date' ) ); ?>"></p>
			<p><label for="bang_gal_device">拍摄设备</label><input class="widefat" type="text" id="bang_gal_device" name="bang_gal_device" value="<?php echo esc_attr( (string) $get( 'bang_gal_device' ) ); ?>" placeholder="如：Xiaomi 14 Ultra"></p>
			<p><label for="bang_gal_address">详细地址</label><input class="widefat" type="text" id="bang_gal_address" name="bang_gal_address" value="<?php echo esc_attr( (string) $get( 'bang_gal_address' ) ); ?>" placeholder="如：浙江省杭州市西湖区龙井路 1 号"></p>
		</div>
		<div class="bang-gal-box-gallery" data-bang-gal-gallery>
			<p><strong>图片</strong><span class="description"> （多选，顺序即灯箱顺序）</span></p>
			<input type="hidden" name="bang_gal_gallery" id="bang_gal_gallery" value="<?php echo esc_attr( implode( ',', array_map( 'intval', $gallery ) ) ); ?>" data-bang-gal-gallery-input>
			<div class="bang-gal-box-list" data-bang-gal-gallery-list>
				<?php
				foreach ( $gallery as $aid ) {
					$thumb = wp_get_attachment_image_src( $aid, 'thumbnail' );
					echo '<span class="bang-gal-box-item" data-id="' . (int) $aid . '">'
						. ( $thumb ? '<img src="' . esc_url( $thumb[0] ) . '" alt="">' : '' )
						. '<button type="button" class="bang-gal-box-remove" aria-label="移除">×</button></span>';
				}
				?>
			</div>
			<button type="button" class="button" data-bang-gal-gallery-pick>从媒体库选择图片</button>
			<button type="button" class="button" data-bang-gal-gallery-clear>清空</button>
		</div>
	</div>
	<script>
	(function () {
		var wrap = document.querySelector('[data-bang-gal-gallery]');
		if (!wrap || typeof wp === 'undefined' || !wp.media) { return; }
		var input = wrap.querySelector('[data-bang-gal-gallery-input]');
		var list = wrap.querySelector('[data-bang-gal-gallery-list]');
		function render(ids) {
			input.value = ids.join(',');
			list.innerHTML = '';
			ids.forEach(function (id) {
				var s = document.createElement('span');
				s.className = 'bang-gal-box-item';
				s.setAttribute('data-id', id);
				s.innerHTML = '<button type="button" class="bang-gal-box-remove" aria-label="移除">×</button>';
				list.appendChild(s);
			});
		}
		function ids() { return input.value ? input.value.split(',').map(Number).filter(Boolean) : []; }
		wrap.querySelector('[data-bang-gal-gallery-pick]').addEventListener('click', function () {
			if (!wp.media) { return; }
			var frame = wp.media({ title: '选择图片集图片', multiple: true, library: { type: 'image' } });
			frame.on('select', function () {
				var sel = frame.state().get('selection');
				var arr = ids();
				sel.each(function (att) { var id = att.toJSON().id; if (arr.indexOf(id) < 0) { arr.push(id); } });
				render(arr);
			});
			frame.open();
		});
		wrap.querySelector('[data-bang-gal-gallery-clear]').addEventListener('click', function () { render([]); });
		list.addEventListener('click', function (e) {
			var btn = e.target.closest('.bang-gal-box-remove');
			if (!btn) { return; }
			var item = btn.closest('.bang-gal-box-item');
			render(ids().filter(function (n) { return n !== Number(item.getAttribute('data-id')); }));
		});
	})();
	</script>
	<?php
}

add_action( 'save_post_bang_gallery', 'bang_gal_save' );
/**
 * 保存图片集字段。
 *
 * @param int $post_id 图片集 ID。
 */
function bang_gal_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['bang_gal_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bang_gal_nonce'] ) ), 'bang_gal_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$text_keys = array( 'bang_gal_province', 'bang_gal_city', 'bang_gal_district', 'bang_gal_address', 'bang_gal_date', 'bang_gal_device' );
	foreach ( $text_keys as $key ) {
		$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
	$ids = isset( $_POST['bang_gal_gallery'] ) ? wp_unslash( $_POST['bang_gal_gallery'] ) : '';
	$ids = array_values( array_unique( array_filter( array_map( 'intval', explode( ',', (string) $ids ) ) ) ) );
	if ( $ids ) {
		update_post_meta( $post_id, 'bang_gal_gallery', $ids );
	} else {
		delete_post_meta( $post_id, 'bang_gal_gallery' );
	}
	/* 封面同步为第一张图 */
	if ( $ids && ! has_post_thumbnail( $post_id ) ) {
		set_post_thumbnail( $post_id, $ids[0] );
	}
}

/* ------------------------------------------------------------
   读取 helper
   ------------------------------------------------------------ */
/**
 * 图片集图片附件 ID 列表。
 *
 * @param int $post_id 图片集 ID。
 * @return int[]
 */
function bang_gal_gallery_ids( $post_id ) {
	$ids = get_post_meta( (int) $post_id, 'bang_gal_gallery', true );
	if ( ! is_array( $ids ) ) {
		return array();
	}
	return array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
}

/**
 * 从附件 EXIF 读相机型号，读不到回退传入设备名。
 *
 * @param int    $aid      附件 ID。
 * @param string $fallback 回退设备名。
 * @return string
 */
function bang_gallery_camera( $aid, $fallback = '' ) {
	$aid = (int) $aid;
	if ( ! $aid ) {
		return $fallback;
	}
	$meta = wp_get_attachment_metadata( $aid );
	if ( ! empty( $meta['image_meta']['camera'] ) ) {
		$cam = trim( (string) $meta['image_meta']['camera'] );
		if ( '' !== $cam ) {
			return $cam;
		}
	}
	return $fallback;
}

/**
 * 日期统一成 Y.m.d；解析不了就原样返回。
 *
 * @param string $s 日期串。
 * @return string
 */
function bang_gallery_fmt_date( $s ) {
	$s = trim( (string) $s );
	if ( '' === $s ) {
		return '';
	}
	$ts = strtotime( $s );
	return false !== $ts ? date_i18n( 'Y.m.d', $ts ) : $s;
}

/**
 * 位置片段拼接：去空、去重。
 *
 * @param array $parts 位置片段。
 * @return string
 */
function bang_gallery_join_place( $parts ) {
	$out = array();
	foreach ( (array) $parts as $p ) {
		$p = trim( (string) $p );
		if ( '' !== $p && ! in_array( $p, $out, true ) ) {
			$out[] = $p;
		}
	}
	return implode( ' · ', $out );
}

/**
 * 兼容旧调用：图片集不再区分来源，统一返回「图片集」。
 *
 * @param string $key 来源键（忽略）。
 * @return string
 */
function bang_gallery_source_label( $key = '' ) {
	return __( '图片集', 'bang' );
}

/**
 * 聚合全部图片集相册，按拍摄日期倒序。
 *
 * 每张卡片结构：
 *   array{ id, source, source_label, title, link, place, time,
 *          images: array{ full, thumb, device, time, place, caption }, sort }
 *
 * @return array
 */
function bang_gallery_items() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$posts = get_posts(
		array(
			'post_type'        => 'bang_gallery',
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'no_found_rows'    => true,
			'suppress_filters' => true,
		)
	);
	$groups = array();
	foreach ( $posts as $p ) {
		$ids = bang_gal_gallery_ids( $p->ID );
		if ( ! $ids ) {
			continue;
		}
		$place   = bang_gallery_join_place( array(
			get_post_meta( $p->ID, 'bang_gal_province', true ),
			get_post_meta( $p->ID, 'bang_gal_city', true ),
			get_post_meta( $p->ID, 'bang_gal_district', true ),
			get_post_meta( $p->ID, 'bang_gal_address', true ),
		) );
		$when    = bang_gallery_fmt_date( get_post_meta( $p->ID, 'bang_gal_date', true ) );
		if ( '' === $when ) {
			$when = bang_gallery_fmt_date( get_the_date( 'Y-m-d', $p ) );
		}
		$device  = trim( (string) get_post_meta( $p->ID, 'bang_gal_device', true ) );
		$imgs    = array();
		foreach ( $ids as $aid ) {
			$full = (string) wp_get_attachment_image_url( $aid, 'full' );
			if ( '' === $full ) {
				continue;
			}
			$imgs[] = array(
				'full'    => $full,
				'thumb'   => (string) wp_get_attachment_image_url( $aid, 'medium_large' ),
				'device'  => bang_gallery_camera( $aid, $device ),
				'time'    => $when,
				'place'   => $place,
				'caption' => get_the_title( $p ),
			);
		}
		if ( ! $imgs ) {
			continue;
		}
		$groups[] = array(
			'id'           => 'gal-' . $p->ID,
			'post_id'      => (int) $p->ID,
			'source'       => 'gallery',
			'source_label' => bang_gallery_source_label(),
			'title'        => get_the_title( $p ),
			'link'         => '',
			'place'        => $place,
			'time'         => $when,
			'device'       => $imgs[0]['device'],
			'images'       => $imgs,
			'sort'         => (string) get_post_meta( $p->ID, 'bang_gal_date', true ) ? (string) get_post_meta( $p->ID, 'bang_gal_date', true ) : (string) get_the_date( 'Y-m-d H:i:s', $p ),
		);
	}
	/* 按拍摄日期倒序 */
	usort(
		$groups,
		function ( $a, $b ) {
			return strcmp( $b['sort'], $a['sort'] );
		}
	);
	$cache = $groups;
	return $groups;
}

/**
 * 图片集分页：按「相册卡片」分页，并给出图片总数。
 *
 * @param int $page 当前页（1 起）。
 * @return array{items:array, total:int, photo_total:int, pages:int, page:int, per:int}
 */
function bang_gallery_paginate( $page ) {
	$all         = bang_gallery_items();
	$total       = count( $all );
	$photo_total = 0;
	foreach ( $all as $g ) {
		$photo_total += count( $g['images'] );
	}
	$per   = max( 4, (int) bang_opt( 'gallery_per_page' ) );
	$pages = max( 1, (int) ceil( $total / $per ) );
	$page  = max( 1, min( $pages, (int) $page ) );
	$slice = array_slice( $all, ( $page - 1 ) * $per, $per );
	return array(
		'items'       => $slice,
		'total'       => $total,
		'photo_total' => $photo_total,
		'pages'       => $pages,
		'page'        => $page,
		'per'         => $per,
	);
}
