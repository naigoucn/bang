<?php
/**
 * 点赞
 *
 * 收藏在 0.11.1 合并进点赞：两者语义重复，只留一个。
 * 登录用户的点赞存 user_meta（bang_likes，值为文章 ID 数组），
 * 文章点赞总数存 post_meta（bang_like_count），用于操作条图标下的计数。
 *
 * 前端通过 REST（POST /wp-json/bang/v1/social）切换，nonce 为 wp_rest；
 * 未登录一律 401，由前端提示并引导去用户中心。
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 读取某个用户的点赞文章 ID 列表
 *
 * @param int $user_id 用户 ID。
 * @return int[]
 */
function bang_social_ids( $user_id ) {
	$list = get_user_meta( (int) $user_id, 'bang_likes', true );
	if ( ! is_array( $list ) ) {
		return array();
	}
	$list = array_map( 'intval', $list );
	return array_values( array_unique( array_filter( $list ) ) );
}

/**
 * 写回点赞列表
 *
 * @param int   $user_id 用户 ID。
 * @param int[] $ids     文章 ID 列表。
 */
function bang_social_save( $user_id, $ids ) {
	$ids = array_values( array_unique( array_filter( array_map( 'intval', (array) $ids ) ) ) );
	update_user_meta( (int) $user_id, 'bang_likes', $ids );
}

/**
 * 注册 REST 路由
 */
function bang_social_rest_route() {
	register_rest_route(
		'bang/v1',
		'/social',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_social_toggle',
			'permission_callback' => function () {
				return is_user_logged_in();
			},
			'args'                => array(
				'post_id' => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'bang_social_rest_route' );

/**
 * 切换点赞
 *
 * @param WP_REST_Request $request 请求。
 * @return array|WP_Error
 */
function bang_social_toggle( $request ) {
	$post_id = absint( $request->get_param( 'post_id' ) );
	$post    = get_post( $post_id );

	/* 文章 / 说说 / 图片集共用同一套点赞；说说与图片集还要靠 post_meta 反查「谁点了」 */
	if ( ! $post || 'publish' !== $post->post_status || ! in_array( $post->post_type, array( 'post', 'bang_say', 'bang_gallery' ), true ) ) {
		return new WP_Error( 'bang_no_post', '内容不存在', array( 'status' => 404 ) );
	}

	$uid = get_current_user_id();
	$ids = bang_social_ids( $uid );
	$at  = array_search( $post_id, $ids, true );
	$on  = false === $at;
	if ( $on ) {
		$ids[] = $post_id;
	} else {
		unset( $ids[ $at ] );
	}
	bang_social_save( $uid, $ids );

	$count = (int) get_post_meta( $post_id, 'bang_like_count', true ) + ( $on ? 1 : -1 );
	$count = max( 0, $count );
	update_post_meta( $post_id, 'bang_like_count', $count );

	/* 反向索引：点赞人 ID 列表，仍同步维护（点赞接口返回 likersHtml 用） */
	$likers = get_post_meta( $post_id, 'bang_say_like_users', true );
	$likers = is_array( $likers ) ? array_values( array_unique( array_filter( array_map( 'intval', $likers ) ) ) ) : array();
	if ( $on ) {
		$likers[] = $uid;
	} else {
		$likers = array_values( array_diff( $likers, array( $uid ) ) );
	}
	update_post_meta( $post_id, 'bang_say_like_users', $likers );

	return array(
		'on'         => $on,
		'count'      => $count,
		'likersHtml' => function_exists( 'bang_say_likers_html' ) ? bang_say_likers_html( $post_id ) : '',
	);
}

/**
 * 注入前端配置：登录态 + 已点赞的 ID + 动作条位置偏好
 */
function bang_social_config() {
	$uid = get_current_user_id();
	$config = array(
		'loggedIn'  => is_user_logged_in(),
		/* 侧栏底部登录块：pjax 登录 / 退出后要换文案，昵称随配置一起下发 */
		'name'      => $uid ? get_the_author_meta( 'display_name', $uid ) : '',
		/* 侧栏头像圈（0.13.13）：下发真实头像地址，pjax 登录 / 换页后即时换图 */
		'avatar'    => $uid ? bang_user_avatar_url( $uid, 30 ) : '',
		'likes'     => $uid ? bang_social_ids( $uid ) : array(),
		'restUrl'       => esc_url_raw( rest_url( 'bang/v1/social' ) ),
		'restNonce'     => wp_create_nonce( 'wp_rest' ),
		'userUrl'   => bang_user_url(),
		'authUrl'   => bang_auth_url(),
		/* 手机端动作条：follow / left / right。
		   只在用户明确保存过时下发原值；从未选择时给空串，
		   前端回退到本机 localStorage——否则服务端兜底的 follow
		   会盖掉用户在本机存的偏好（保存失败 / 缓存旧页时表现为
		   「设了固定却还是跟着倾斜换边」）。 */
		'dockSide'  => is_user_logged_in() ? (string) get_user_meta( get_current_user_id(), 'bang_dock_side', true ) : '',
		'prefsUrl'  => esc_url_raw( rest_url( 'bang/v1/prefs' ) ),
		/* 违禁词提示（只提示不拦提交）：词表与动作都在服务端，
		   前端仅用它在输入命中时弹一次提示；审核没开就给空数组，前端完全不显示。
		   词表是 array(词, 权重)，这里只下发纯词，权重由服务端判定。 */
		'words'     => ( function_exists( 'bang_moderation_words' ) && bang_opt( 'moderation_on' ) )
			? array_values( function_exists( 'bang_moderation_word_list' ) ? bang_moderation_word_list() : bang_moderation_words() )
			: array(),
	);
	/* 通行密钥等模块往 bangUser 里追加自己的字段 */
	return apply_filters( 'bang_user_config', $config );
}

