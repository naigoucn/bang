<?php
/**
 * Bang · 发布器接口（AI Agent 发帖）
 *
 * 让外部 AI Agent 通过一个独立 Token 发布文章，无需管理员账号密码、
 * 无需浏览器登录态。设计要点：
 *
 * - Token 只在服务端存 SHA-256 哈希，明文仅在生成时显示一次；重置即旧 Token 失效。
 * - 恒定时间比对（hash_equals）防时序攻击；调用走 HTTPS 时 Token 不落地查询串。
 * - 字段白名单 + 长度上限 + 分类标签自动建立 + 频率限制（transient 防刷）。
 * - 发布成功后若已开启 AI 摘要，自动生成摘要（复用 inc/ai.php）。
 *
 * REST：
 *   POST /wp-json/bang/v1/publish       发帖（X-Bang-Token 认证）
 *   POST /wp-json/bang/v1/publish/token 重置 Token（仅管理员，wp_rest nonce）
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Token 选项键（独立 option，不放 bang_options，避免被后台设置整体覆盖） */
if ( ! defined( 'BANG_PUBLISHER_TOKEN_KEY' ) ) {
	define( 'BANG_PUBLISHER_TOKEN_KEY', 'bang_publisher_token_hash' );
}

/**
 * 是否已开启发布器。
 */
function bang_publisher_enabled() {
	return (bool) bang_opt( 'publisher_on' );
}

/**
 * 生成一个新的随机 Token，明文只返回一次，库里只存哈希。
 *
 * @return string 明文 Token。
 */
function bang_publisher_new_token() {
	$token = bin2hex( random_bytes( 32 ) ); // 64 位十六进制，256 位熵
	update_option( BANG_PUBLISHER_TOKEN_KEY, hash( 'sha256', $token ) );
	return $token;
}

/**
 * 从请求里取出 Token：优先 HTTP Header X-Bang-Token，其次 Authorization Bearer。
 *
 * @param WP_REST_Request $request 请求。
 * @return string
 */
function bang_publisher_token_from_request( $request ) {
	$header = $request->get_header( 'X-Bang-Token' );
	if ( ! $header ) {
		$header = $request->get_header( 'Authorization' );
		if ( $header && 0 === stripos( $header, 'Bearer ' ) ) {
			$header = substr( $header, 7 );
		}
	}
	return is_string( $header ) ? trim( $header ) : '';
}

/**
 * 权限回调：开关 + Token 恒定时间比对。
 */
function bang_publisher_permission( $request ) {
	if ( ! bang_publisher_enabled() ) {
		return false;
	}
	$stored = (string) get_option( BANG_PUBLISHER_TOKEN_KEY, '' );
	if ( '' === $stored ) {
		return false;
	}
	$provided = bang_publisher_token_from_request( $request );
	if ( '' === $provided ) {
		return false;
	}
	return hash_equals( $stored, hash( 'sha256', $provided ) );
}

/**
 * 频率限制：同一 Token 每分钟最多 N 篇，防泄露后被批量刷。
 *
 * @param string $token_hash Token 哈希，作为限流键的一部分。
 * @return bool true = 放行
 */
function bang_publisher_rate_ok( $token_hash ) {
	$limit = (int) bang_opt( 'publisher_rate' );
	if ( $limit < 1 ) {
		$limit = 10;
	}
	$key   = 'bang_pub_rate_' . substr( $token_hash, 0, 16 );
	$count = (int) get_transient( $key );
	if ( $count >= $limit ) {
		return false;
	}
	set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
	return true;
}

/**
 * 把分类 / 标签名称数组转成 term ID 数组，不存在的自动创建。
 *
 * @param mixed  $names   名称数组或逗号串。
 * @param string $taxonomy 分类法。
 * @return int[]
 */
function bang_publisher_terms( $names, $taxonomy ) {
	$list = array();
	if ( is_string( $names ) ) {
		$names = explode( ',', $names );
	}
	if ( ! is_array( $names ) ) {
		return $list;
	}
	foreach ( $names as $name ) {
		$name = trim( (string) $name );
		if ( '' === $name || mb_strlen( $name, 'UTF-8' ) > 40 ) {
			continue;
		}
		$term = term_exists( $name, $taxonomy );
		if ( ! $term ) {
			$term = wp_insert_term( $name, $taxonomy );
		}
		if ( is_wp_error( $term ) ) {
			continue;
		}
		$list[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
	}
	return array_values( array_unique( $list ) );
}

/**
 * 发帖回调。
 *
 * @param WP_REST_Request $request 请求。
 * @return WP_REST_Response
 */
function bang_publisher_publish( $request ) {
	$stored = (string) get_option( BANG_PUBLISHER_TOKEN_KEY, '' );
	if ( ! bang_publisher_rate_ok( $stored ) ) {
		return new WP_REST_Response(
			array( 'ok' => false, 'code' => 'rate_limited', 'msg' => '发布过于频繁，请稍后再试。' ),
			429
		);
	}

	$title   = trim( (string) $request->get_param( 'title' ) );
	$content = (string) $request->get_param( 'content' );
	if ( '' === $title || '' === trim( $content ) ) {
		return new WP_REST_Response(
			array( 'ok' => false, 'code' => 'invalid', 'msg' => 'title 与 content 均不能为空。' ),
			400
		);
	}
	$title   = mb_substr( sanitize_text_field( $title ), 0, 120, 'UTF-8' );
	$content = wp_kses_post( mb_substr( $content, 0, 200000, 'UTF-8' ) );

	$status = (string) $request->get_param( 'status' );
	if ( ! in_array( $status, array( 'publish', 'draft', 'private' ), true ) ) {
		$status = 'publish';
	}

	$postarr = array(
		'post_title'   => $title,
		'post_content' => $content,
		'post_status'  => $status,
		'post_type'    => 'post',
	);

	$excerpt = trim( (string) $request->get_param( 'excerpt' ) );
	if ( '' !== $excerpt ) {
		$postarr['post_excerpt'] = mb_substr( sanitize_text_field( $excerpt ), 0, 200, 'UTF-8' );
	}

	$cats = bang_publisher_terms( $request->get_param( 'categories' ), 'category' );
	if ( $cats ) {
		$postarr['post_category'] = $cats;
	}

	$post_id = wp_insert_post( $postarr, true );
	if ( is_wp_error( $post_id ) ) {
		return new WP_REST_Response(
			array( 'ok' => false, 'code' => 'insert_failed', 'msg' => $post_id->get_error_message() ),
			500
		);
	}

	$tags = bang_publisher_terms( $request->get_param( 'tags' ), 'post_tag' );
	if ( $tags ) {
		wp_set_post_terms( $post_id, $tags, 'post_tag' );
	}

	/* 特色图：仅接受 http(s) 外链，交给 WP 侧载到媒体库 */
	$thumb = trim( (string) $request->get_param( 'featured_image' ) );
	if ( '' !== $thumb && preg_match( '/^https?:\/\//i', $thumb ) ) {
		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		$att_id = media_sideload_image( esc_url_raw( $thumb ), $post_id, $title, 'id' );
		if ( ! is_wp_error( $att_id ) ) {
			set_post_thumbnail( $post_id, (int) $att_id );
		}
	}

	/* 复用 AI 摘要：发布成功且已配置时自动生成（失败不影响发帖结果） */
	if ( 'publish' === $status && function_exists( 'bang_ai_generate_excerpt' ) && function_exists( 'bang_ai_ready' ) && bang_ai_ready() ) {
		bang_ai_generate_excerpt( $post_id, false );
	}

	return new WP_REST_Response(
		array(
			'ok'         => true,
			'id'         => (int) $post_id,
			'link'       => get_permalink( $post_id ),
			'status'     => $status,
			'title'      => $title,
			'categories' => $cats,
			'tags'       => $tags,
		),
		201
	);
}

/**
 * 重置 Token（仅管理员）。生成后立即返回明文，之后无法再取回。
 */
function bang_publisher_reset_token( $request ) {
	return new WP_REST_Response(
		array(
			'ok'    => true,
			'token' => bang_publisher_new_token(),
			'msg'   => '请立即复制保存，此 Token 只显示这一次。',
		),
		200
	);
}

/**
 * 后台高级 tab 的发布器面板：Token 管理 + 接口文档（只读，不参与表单保存）。
 */
function bang_publisher_panel() {
	$has_token = '' !== (string) get_option( BANG_PUBLISHER_TOKEN_KEY, '' );
	$endpoint  = esc_url( rest_url( 'bang/v1/publish' ) );
	$html      = '<div class="bang-pub-panel" data-bang-pub-panel>'
		. '<h3>' . esc_html__( '发布器接口（AI Agent 发帖）', 'bang' ) . '</h3>'
		. '<p class="bang-f-d">' . esc_html__( '把下面的地址和 Token 交给 AI Agent，它就能按你设定的规则发布文章。Token 只在服务端保存哈希，重置后旧 Token 立即失效。', 'bang' ) . '</p>'
		. '<p class="bang-f-d"><b>接口地址：</b><code>' . $endpoint . '</code></p>'
		. '<p class="bang-f-d"><b>当前状态：</b>'
		. ( bang_publisher_enabled() ? ( $has_token ? '已开启 · 已配置 Token' : '已开启 · 尚未生成 Token' ) : '未开启' )
		. '</p>'
		. '<p><button type="button" class="button" data-bang-pub-token>'
		. ( $has_token ? esc_html__( '重置 Token', 'bang' ) : esc_html__( '生成 Token', 'bang' ) ) . '</button>'
		. ' <span data-bang-pub-status></span></p>'
		. '<div data-bang-pub-secret hidden><p class="bang-f-d">' . esc_html__( '复制下面的 Token 发给 Agent，只显示这一次：', 'bang' ) . '</p>'
		. '<input type="text" readonly class="bang-f-input" data-bang-pub-value></div>'
		. '<details class="bang-pub-doc"><summary>' . esc_html__( '查看 Agent 调用说明', 'bang' ) . '</summary>'
		. '<pre class="bang-code">POST ' . $endpoint . "\n"
		. "X-Bang-Token: <你的 Token>\nContent-Type: application/json\n\n"
		. "{\n  \"title\": \"文章标题\",\n  \"content\": \"<p>正文 HTML</p>\",\n"
		. "  \"excerpt\": \"可选摘要\",\n  \"categories\": [\"分类名\"],\n  \"tags\": [\"标签1\", \"标签2\"],\n"
		. "  \"status\": \"publish\",\n  \"featured_image\": \"https://.../cover.jpg\"\n}\n\n"
		. "# status: publish / draft / private（默认 publish）\n"
		. "# 分类、标签不存在会自动创建" . '</pre>'
		. '</details>'
		. '</div>';
	return $html;
}

add_action( 'rest_api_init', 'bang_publisher_rest_routes' );
function bang_publisher_rest_routes() {
	register_rest_route(
		'bang/v1',
		'/publish',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_publisher_publish',
			'permission_callback' => 'bang_publisher_permission',
		)
	);
	register_rest_route(
		'bang/v1',
		'/publish/token',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_publisher_reset_token',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		)
	);
}
