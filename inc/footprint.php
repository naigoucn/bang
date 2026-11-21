<?php
/**
 * 足迹（CPT）：把去过的地方作为独立内容存放，供足迹页 2D/3D 地图与图片集使用。
 *
 * - CPT bang_footprint，后台独立菜单「足迹」，前台 archive 关闭（只走 /footprint/ 站点页）
 * - 字段全部存 post_meta：地址 / 经纬度 / 省市区 / 类型 / 到访日期 / 封面 / 图片墙 / 关联文章
 * - REST bang/v1/footprints 只输出地图需要的字段，不暴露后台元信息
 *
 * 图片代理不在此文件：由 inc/footprint-proxy.php 单独实现（严格 SSRF 防护）。
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------
   CPT 注册
   ------------------------------------------------------------ */
add_action( 'init', 'bang_fp_register_cpt' );
/**
 * 注册足迹文章类型。
 */
function bang_fp_register_cpt() {
	register_post_type(
		'bang_footprint',
		array(
			'labels'              => array(
				'name'          => '足迹',
				'singular_name' => '足迹',
				'add_new'       => '添加足迹',
				'add_new_item'  => '添加新足迹',
				'edit_item'     => '编辑足迹',
				'new_item'      => '新足迹',
				'view_item'     => '查看足迹',
				'search_items'  => '搜索足迹',
				'not_found'     => '还没有足迹',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'menu_position'       => 6,
			'menu_icon'           => 'dashicons-location-alt',
			'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
		)
	);
}

/**
 * 字段清单：key => 中文标签（后台 meta box 与读取共用）。
 *
 * @return array
 */
function bang_fp_meta_fields() {
	return array(
		'bang_fp_address'    => '详细地址',
		'bang_fp_province'   => '省份',
		'bang_fp_city'       => '城市',
		'bang_fp_district'   => '区县',
		'bang_fp_lng'        => '经度',
		'bang_fp_lat'        => '纬度',
		'bang_fp_type'       => '足迹类型',
		'bang_fp_visited'    => '到访日期',
		'bang_fp_gallery'    => '图片墙',
		'bang_fp_article'    => '关联文章链接',
	);
}

/* ------------------------------------------------------------
   后台 meta box
   ------------------------------------------------------------ */
add_action( 'add_meta_boxes', 'bang_fp_box_add' );
/**
 * 注册足迹字段框。
 */
function bang_fp_box_add() {
	add_meta_box( 'bang_fp_data', '足迹信息', 'bang_fp_box_html', 'bang_footprint', 'normal', 'high' );
}

/**
 * 字段框 HTML。
 *
 * @param WP_Post $post 当前足迹。
 */
function bang_fp_box_html( $post ) {
	wp_nonce_field( 'bang_fp_save', 'bang_fp_nonce' );
	$get = function ( $key ) use ( $post ) {
		return get_post_meta( $post->ID, $key, true );
	};
	$types = bang_fp_types();
	$gallery = bang_fp_gallery_ids( $post->ID );
	?>
	<div class="bang-fp-box">
		<p class="description">地点名填在上方「标题」，描述填在正文。经纬度可在高德地图取点后粘贴；留空则地图不显示该足迹。</p>
		<div class="bang-fp-grid">
			<p><label for="bang_fp_province">省份</label><input class="widefat" type="text" id="bang_fp_province" name="bang_fp_province" value="<?php echo esc_attr( (string) $get( 'bang_fp_province' ) ); ?>" placeholder="如：浙江"></p>
			<p><label for="bang_fp_city">城市</label><input class="widefat" type="text" id="bang_fp_city" name="bang_fp_city" value="<?php echo esc_attr( (string) $get( 'bang_fp_city' ) ); ?>" placeholder="如：杭州"></p>
			<p><label for="bang_fp_district">区县</label><input class="widefat" type="text" id="bang_fp_district" name="bang_fp_district" value="<?php echo esc_attr( (string) $get( 'bang_fp_district' ) ); ?>" placeholder="如：西湖区"></p>
			<p><label for="bang_fp_type">足迹类型</label>
				<select class="widefat" id="bang_fp_type" name="bang_fp_type">
					<option value="">未分类</option>
					<?php foreach ( $types as $t ) : ?>
						<option value="<?php echo esc_attr( $t ); ?>"<?php selected( (string) $get( 'bang_fp_type' ), $t ); ?>><?php echo esc_html( $t ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p><label for="bang_fp_lng">经度</label><input class="widefat" type="text" id="bang_fp_lng" name="bang_fp_lng" value="<?php echo esc_attr( (string) $get( 'bang_fp_lng' ) ); ?>" placeholder="如：120.155"></p>
			<p><label for="bang_fp_lat">纬度</label><input class="widefat" type="text" id="bang_fp_lat" name="bang_fp_lat" value="<?php echo esc_attr( (string) $get( 'bang_fp_lat' ) ); ?>" placeholder="如：30.274"></p>
			<p><label for="bang_fp_visited">到访日期</label><input class="widefat" type="date" id="bang_fp_visited" name="bang_fp_visited" value="<?php echo esc_attr( (string) $get( 'bang_fp_visited' ) ); ?>"></p>
			<p><label for="bang_fp_address">详细地址</label><input class="widefat" type="text" id="bang_fp_address" name="bang_fp_address" value="<?php echo esc_attr( (string) $get( 'bang_fp_address' ) ); ?>" placeholder="如：浙江省杭州市西湖区龙井路 1 号"></p>
		</div>
		<p><label for="bang_fp_article">关联文章链接</label><input class="widefat" type="url" id="bang_fp_article" name="bang_fp_article" value="<?php echo esc_attr( (string) $get( 'bang_fp_article' ) ); ?>" placeholder="https://，可留空"></p>
		<div class="bang-fp-gallery-field" data-bang-fp-gallery>
			<p><strong>图片墙</strong><span class="description"> （封面取第一张；按顺序展示）</span></p>
			<input type="hidden" name="bang_fp_gallery" id="bang_fp_gallery" value="<?php echo esc_attr( implode( ',', array_map( 'intval', $gallery ) ) ); ?>" data-bang-fp-gallery-input>
			<div class="bang-fp-gallery-list" data-bang-fp-gallery-list>
				<?php
				foreach ( $gallery as $aid ) {
					$thumb = wp_get_attachment_image_src( $aid, 'thumbnail' );
					echo '<span class="bang-fp-gallery-item" data-id="' . (int) $aid . '">'
						. ( $thumb ? '<img src="' . esc_url( $thumb[0] ) . '" alt="">' : '' )
						. '<button type="button" class="bang-fp-gallery-remove" aria-label="移除">×</button></span>';
				}
				?>
			</div>
			<button type="button" class="button" data-bang-fp-gallery-pick>从媒体库选择图片</button>
			<button type="button" class="button" data-bang-fp-gallery-clear>清空</button>
		</div>
	</div>
	<script>
	(function () {
		var wrap = document.querySelector('[data-bang-fp-gallery]');
		if (!wrap || typeof wp === 'undefined' || !wp.media) { return; }
		var input = wrap.querySelector('[data-bang-fp-gallery-input]');
		var list = wrap.querySelector('[data-bang-fp-gallery-list]');
		function render(ids) {
			input.value = ids.join(',');
			list.innerHTML = '';
			ids.forEach(function (id) {
				var s = document.createElement('span');
				s.className = 'bang-fp-gallery-item';
				s.setAttribute('data-id', id);
				s.innerHTML = '<button type="button" class="bang-fp-gallery-remove" aria-label="移除">×</button>';
				list.appendChild(s);
			});
		}
		function ids() { return input.value ? input.value.split(',').map(Number).filter(Boolean) : []; }
		wrap.querySelector('[data-bang-fp-gallery-pick]').addEventListener('click', function () {
			if (!wp.media) { return; }
			var frame = wp.media({ title: '选择图片墙图片', multiple: true, library: { type: 'image' } });
			frame.on('select', function () {
				var sel = frame.state().get('selection');
				var arr = ids();
				sel.each(function (att) { var id = att.toJSON().id; if (arr.indexOf(id) < 0) { arr.push(id); } });
				render(arr);
			});
			frame.open();
		});
		wrap.querySelector('[data-bang-fp-gallery-clear]').addEventListener('click', function () { render([]); });
		list.addEventListener('click', function (e) {
			var btn = e.target.closest('.bang-fp-gallery-remove');
			if (!btn) { return; }
			var item = btn.closest('.bang-fp-gallery-item');
			render(ids().filter(function (n) { return n !== Number(item.getAttribute('data-id')); }));
		});
	})();
	</script>
	<?php
}

/**
 * 足迹类型清单。
 *
 * @return string[]
 */
function bang_fp_types() {
	$raw = trim( (string) bang_opt( 'footprint_types' ) );
	if ( '' === $raw ) {
		return array( '旅游', '美食', '购物', '住宿', '交通', '其他' );
	}
	$parts = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
	return $parts ? array_values( $parts ) : array( '其他' );
}

add_action( 'save_post_bang_footprint', 'bang_fp_save' );
/**
 * 保存足迹字段。
 *
 * @param int $post_id 足迹 ID。
 */
function bang_fp_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['bang_fp_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bang_fp_nonce'] ) ), 'bang_fp_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$text_keys = array( 'bang_fp_address', 'bang_fp_province', 'bang_fp_city', 'bang_fp_district', 'bang_fp_type', 'bang_fp_visited', 'bang_fp_article' );
	foreach ( $text_keys as $key ) {
		$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
	foreach ( array( 'bang_fp_lng', 'bang_fp_lat' ) as $key ) {
		$value = isset( $_POST[ $key ] ) ? trim( (string) wp_unslash( $_POST[ $key ] ) ) : '';
		if ( '' === $value || ! is_numeric( $value ) ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, (float) $value );
		}
	}
	$ids = isset( $_POST['bang_fp_gallery'] ) ? wp_unslash( $_POST['bang_fp_gallery'] ) : '';
	$ids = array_values( array_unique( array_filter( array_map( 'intval', explode( ',', (string) $ids ) ) ) ) );
	if ( $ids ) {
		update_post_meta( $post_id, 'bang_fp_gallery', $ids );
	} else {
		delete_post_meta( $post_id, 'bang_fp_gallery' );
	}
	/* 封面同步为图片墙第一张（特色图像） */
	if ( $ids && ! has_post_thumbnail( $post_id ) ) {
		set_post_thumbnail( $post_id, $ids[0] );
	}
}

/* ------------------------------------------------------------
   读取 helper
   ------------------------------------------------------------ */
/**
 * 图片墙附件 ID 列表。
 *
 * @param int $post_id 足迹 ID。
 * @return int[]
 */
function bang_fp_gallery_ids( $post_id ) {
	$ids = get_post_meta( (int) $post_id, 'bang_fp_gallery', true );
	if ( ! is_array( $ids ) ) {
		return array();
	}
	return array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
}

/**
 * 取足迹列表（供地图与图片集用）。
 *
 * @param int $limit 数量，0 = 全部。
 * @return array
 */
function bang_fp_query( $limit = 0 ) {
	static $cache = array();
	$limit = max( 0, (int) $limit );
	$key   = 'l' . $limit;
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	$posts = get_posts(
		array(
			'post_type'      => 'bang_footprint',
			'post_status'    => 'publish',
			'numberposts'    => $limit ? $limit : -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
			'suppress_filters' => true,
		)
	);
	$rows = array();
	foreach ( $posts as $p ) {
		$lng = (float) get_post_meta( $p->ID, 'bang_fp_lng', true );
		$lat = (float) get_post_meta( $p->ID, 'bang_fp_lat', true );
		if ( abs( $lng ) < 0.0001 && abs( $lat ) < 0.0001 ) {
			continue; /* 无坐标不进地图 */
		}
		$ids = bang_fp_gallery_ids( $p->ID );
		$article       = trim( (string) get_post_meta( $p->ID, 'bang_fp_article', true ) );
		$article_title = '';
		$article_cover = '';
		$article_date  = '';
		if ( '' !== $article ) {
			/* 固定链接为 /%post_id%/，从 URL 尾段取 ID；取不到时用 url_to_postid 兜底 */
			$article_id = 0;
			if ( preg_match( '/\/(\d+)\/?(?:[?#]|$)/', $article, $bang_m ) ) {
				$article_id = (int) $bang_m[1];
			}
			if ( ! $article_id && function_exists( 'url_to_postid' ) ) {
				$article_id = (int) url_to_postid( $article );
			}
			if ( $article_id && get_post( $article_id ) ) {
				$article_title = get_the_title( $article_id );
				$article_cover = (string) get_the_post_thumbnail_url( $article_id, 'medium' );
				$article_date  = (string) get_the_date( 'Y-m-d', $article_id );
			}
		}
		$rows[] = array(
			'id'        => (int) $p->ID,
			'name'      => get_the_title( $p ),
			'desc'      => wp_strip_all_tags( (string) $p->post_content ),
			'address'   => (string) get_post_meta( $p->ID, 'bang_fp_address', true ),
			'province'  => (string) get_post_meta( $p->ID, 'bang_fp_province', true ),
			'city'      => (string) get_post_meta( $p->ID, 'bang_fp_city', true ),
			'district'  => (string) get_post_meta( $p->ID, 'bang_fp_district', true ),
			'lng'       => $lng,
			'lat'       => $lat,
			'type'      => (string) get_post_meta( $p->ID, 'bang_fp_type', true ),
			'visited'   => (string) get_post_meta( $p->ID, 'bang_fp_visited', true ),
			'article'   => $article,
			'article_title' => $article_title,
			'article_cover' => $article_cover,
			'article_date'  => $article_date,
			'cover'     => $ids ? wp_get_attachment_image_url( $ids[0], 'medium' ) : get_the_post_thumbnail_url( $p->ID, 'medium' ),
			'gallery'   => array_map( function ( $aid ) {
				return array(
					'id'  => (int) $aid,
					'url' => (string) wp_get_attachment_image_url( $aid, 'medium_large' ),
					'full'=> (string) wp_get_attachment_image_url( $aid, 'full' ),
				);
			}, $ids ),
		);
	}
	$cache[ $key ] = $rows;
	return $rows;
}

/**
 * 足迹统计（省份数 / 城市数 / 足迹数），关于页与足迹页共用。
 *
 * @return array
 */
function bang_fp_stats() {
	$rows = bang_fp_query();
	$provs = array();
	$cities = array();
	foreach ( $rows as $r ) {
		if ( $r['province'] ) {
			$provs[ $r['province'] ] = 1;
		}
		if ( $r['city'] ) {
			$cities[ $r['city'] ] = 1;
		}
	}
	return array(
		'footprints' => count( $rows ),
		'provinces'  => count( $provs ),
		'cities'     => count( $cities ),
	);
}

/* ------------------------------------------------------------
   REST：bang/v1/footprints（只读）
   ------------------------------------------------------------ */
add_action( 'rest_api_init', 'bang_fp_rest_routes' );
/**
 * 注册足迹 REST 路由。
 */
function bang_fp_rest_routes() {
	register_rest_route(
		'bang/v1',
		'/footprints',
		array(
			'methods'             => 'GET',
			'callback'            => 'bang_fp_rest_list',
			'permission_callback' => '__return_true',
			'args'                => array(
				'limit' => array(
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'default'           => 0,
				),
			),
		)
	);
}

/**
 * REST 足迹列表。
 *
 * @param WP_REST_Request $request 请求。
 * @return WP_REST_Response
 */
function bang_fp_rest_list( $request ) {
	$limit = (int) $request->get_param( 'limit' );
	return rest_ensure_response(
		array(
			'code'  => 0,
			'count' => count( bang_fp_query( $limit ) ),
			'data'  => bang_fp_query( $limit ),
		)
	);
}
