<?php
/**
 * 浏览量统计与热门文章排行榜
 *
 * 浏览量存在 post_meta（bang_views）：文章页首次进入时前端上报一次，服务端用
 * 「浏览器 Cookie + IP 临时令牌」双保险去重，避免刷新 / 换 IP 刷量。
 * 综合热度 = 浏览×w_view + 点赞×w_like + 评论×w_comment，权重后台可调。
 * 热门文章榜显示在文章归档页顶部，由归档模板 page-archive.php 消费这里的数据。
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 浏览量 meta 键。
 */
if ( ! defined( 'BANG_VIEW_META' ) ) {
	define( 'BANG_VIEW_META', 'bang_views' );
}

/**
 * 读取文章浏览量。
 *
 * @param int $post_id 文章 ID。
 * @return int
 */
function bang_rank_get_views( $post_id ) {
	$views = get_post_meta( (int) $post_id, BANG_VIEW_META, true );
	return max( 0, (int) $views );
}

/**
 * 站点总阅读量：所有已发布文章浏览量之和。
 *
 * 用 transient 缓存 10 分钟，避免归档页每次遍历全部文章 meta；
 * 每次前台上报浏览量成功后会删掉缓存，保证计数实时跟上。
 *
 * @return int
 */
function bang_rank_total_views() {
	$cached = get_transient( 'bang_rank_total_views' );
	if ( false !== $cached ) {
		return max( 0, (int) $cached );
	}
	$ids   = get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
			'no_found_rows'    => true,
		)
	);
	$total = 0;
	foreach ( (array) $ids as $id ) {
		$total += bang_rank_get_views( $id );
	}
	set_transient( 'bang_rank_total_views', $total, 10 * MINUTE_IN_SECONDS );
	return (int) $total;
}

/**
 * 浏览量 +1，返回最新值。
 *
 * @param int $post_id 文章 ID。
 * @return int
 */
function bang_rank_add_view( $post_id ) {
	$post_id = (int) $post_id;
	$views   = bang_rank_get_views( $post_id ) + 1;
	update_post_meta( $post_id, BANG_VIEW_META, $views );
	return $views;
}

/**
 * 客户端 IP：只信服务器直连地址，不读 X-Forwarded-For（可伪造）。
 *
 * @return string
 */
function bang_rank_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	return $ip ? substr( $ip, 0, 64 ) : 'unknown';
}

/**
 * 注册浏览量上报路由：游客也能上报（只读计数，无隐私数据），不强制 nonce。
 */
function bang_rank_rest_route() {
	register_rest_route(
		'bang/v1',
		'/view',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_rank_rest_view',
			'permission_callback' => '__return_true',
			'args'                => array(
				'post_id' => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'bang_rank_rest_route' );

/**
 * 浏览量上报处理。
 *
 * @param WP_REST_Request $request 请求。
 * @return array|WP_Error
 */
function bang_rank_rest_view( $request ) {
	$post_id = absint( $request->get_param( 'post_id' ) );
	$post    = get_post( $post_id );

	if ( ! $post || 'publish' !== $post->post_status || 'post' !== $post->post_type ) {
		return new WP_Error( 'bang_no_post', '文章不存在', array( 'status' => 404 ) );
	}

	$views = bang_rank_get_views( $post_id );
	$ck    = 'bang_rank_v_' . $post_id;

	/* 同一浏览器 24 小时内只计一次 */
	if ( ! empty( $_COOKIE[ $ck ] ) ) {
		return array(
			'count' => $views,
			'added' => false,
		);
	}

	/* 同一 IP 同一文章 5 分钟内只计一次，挡住清 Cookie 刷量 */
	$tk      = 'bang_rank_ip_' . md5( bang_rank_client_ip() . '|' . $post_id );
	$recent  = get_transient( $tk );
	if ( $recent ) {
		return array(
			'count' => $views,
			'added' => false,
		);
	}
	set_transient( $tk, 1, 5 * MINUTE_IN_SECONDS );

	$views = bang_rank_add_view( $post_id );
	/* 总阅读量缓存失效，归档页统计条下次访问即重新汇总 */
	delete_transient( 'bang_rank_total_views' );

	if ( ! headers_sent() ) {
		setcookie( $ck, '1', time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
	}

	return array(
		'count' => $views,
		'added' => true,
	);
}

/**
 * 热门文章查询。
 *
 * @param int    $limit 返回条数，0 = 用后台设置。
 * @param string $order 排序：hot / views / likes / comments。
 * @param int    $days  统计天数，0 = 全部。
 * @return array 每条含 id / views / likes / comments / score。
 */
function bang_rank_query( $limit = 0, $order = '', $days = -1 ) {
	/* 固定筛选：10 条 / 全部时间 / 综合热度（浏览×1 + 点赞×3 + 评论×2），后台不再暴露旋钮。
	   参数仍保留，供子主题或代码调用时按需覆盖。 */
	$limit = $limit > 0 ? (int) $limit : 10;
	$order = $order ? (string) $order : 'hot';
	$days  = $days >= 0 ? (int) $days : 0;

	$args = array(
		'post_type'        => 'post',
		'post_status'      => 'publish',
		'posts_per_page'   => -1,
		'fields'           => 'ids',
		'orderby'          => 'ID',
		'order'            => 'DESC',
		'suppress_filters' => true,
		'no_found_rows'    => true,
	);
	if ( $days > 0 ) {
		$args['date_query'] = array(
			array(
				'after' => gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $days * DAY_IN_SECONDS ),
			),
		);
	}

	$ids   = get_posts( $args );
	$wv    = 1;
	$wl    = 3;
	$wc    = 2;
	$rows  = array();

	foreach ( (array) $ids as $id ) {
		$id       = (int) $id;
		$views    = bang_rank_get_views( $id );
		$likes    = max( 0, (int) get_post_meta( $id, 'bang_like_count', true ) );
		$comments = max( 0, (int) get_comments_number( $id ) );
		$rows[]   = array(
			'id'       => $id,
			'views'    => $views,
			'likes'    => $likes,
			'comments' => $comments,
			'score'    => $views * $wv + $likes * $wl + $comments * $wc,
		);
	}

	$key = 'score';
	if ( 'views' === $order ) {
		$key = 'views';
	} elseif ( 'likes' === $order ) {
		$key = 'likes';
	} elseif ( 'comments' === $order ) {
		$key = 'comments';
	}

	usort(
		$rows,
		function ( $a, $b ) use ( $key ) {
			if ( $a[ $key ] === $b[ $key ] ) {
				return $b['views'] - $a['views'];
			}
			return $b[ $key ] - $a[ $key ];
		}
	);

	return array_slice( $rows, 0, $limit );
}

/**
 * 前端上报配置：注入到 bangRank。
 *
 * @return array
 */
function bang_rank_frontend_config() {
	return array(
		'on'      => (int) bang_opt( 'ranking_on' ),
		'restUrl' => esc_url_raw( rest_url( 'bang/v1/view' ) ),
		'nonce'   => wp_create_nonce( 'wp_rest' ),
	);
}

/**
 * 文章页是否展示浏览量：正文操作条上挂一个轻量计数。
 *
 * @param int $post_id 文章 ID。
 * @return string
 */
function bang_rank_views_label( $post_id ) {
	if ( ! bang_opt( 'ranking_on' ) ) {
		return '';
	}
	$views = bang_rank_get_views( $post_id );
	return '<span class="bang-views" title="' . esc_attr__( '浏览量', 'bang' ) . '">'
		. bang_icon( 'eye', 14 )
		. '<span class="bang-views-num">' . esc_html( (string) $views ) . '</span>'
		. '</span>';
}
