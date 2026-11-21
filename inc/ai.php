<?php
/**
 * Bang · AI 摘要（极简 × AI 第一篇）
 *
 * 设计原则：
 * - AI 只做「看不见的减法」——发布时生成一句摘要存进 post_meta，
 *   前台零外部请求、零运行时 JS，完全不破坏极简骨架。
 * - OpenAI 兼容协议 /chat/completions（DeepSeek、通义、智谱、OpenAI 等通用）。
 * - API Key 只在服务端使用，绝不下发到浏览器。
 * - 未开启 / 未配置 / 调用失败：一律回退 bang_plain_excerpt()，主题行为与之前完全一致。
 *
 * 生成结果写入 post_meta：bang_ai_excerpt（一句中文摘要，≤58 字）。
 * 三处复用：SEO description / 分享海报 data-desc / 语义搜索索引 excerpt。
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 取文章摘要：有 AI 摘要且开关开启就用它，否则回退纯文本截取。
 *
 * @param int $post_id 文章 ID。
 * @return string
 */
function bang_ai_excerpt( $post_id ) {
	$post_id = (int) $post_id;
	if ( bang_opt( 'ai_excerpt_on' ) ) {
		$ai = get_post_meta( $post_id, 'bang_ai_excerpt', true );
		if ( is_string( $ai ) && '' !== trim( $ai ) ) {
			return wp_trim_words( trim( $ai ), 58, '' );
		}
	}
	return bang_plain_excerpt( $post_id );
}

/**
 * 是否具备调用 AI 接口的条件（开关 + Key + 地址）。
 *
 * @return bool
 */
function bang_ai_ready() {
	if ( ! bang_opt( 'ai_excerpt_on' ) ) {
		return false;
	}
	$key = trim( (string) bang_opt( 'ai_api_key' ) );
	if ( '' === $key ) {
		return false;
	}
	$base = trim( (string) bang_opt( 'ai_api_base' ) );
	return '' !== $base;
}

/**
 * 调用 OpenAI 兼容接口，为一篇文章生成摘要并写入 post_meta。
 *
 * @param int  $post_id 文章 ID。
 * @param bool $force   true = 覆盖已有摘要；false = 只在没有摘要时生成。
 * @return array{ok:bool,msg:string,excerpt:string}
 */
function bang_ai_generate_excerpt( $post_id, $force = false ) {
	$post_id = (int) $post_id;
	$post    = get_post( $post_id );
	if ( ! $post || 'publish' !== $post->post_status ) {
		return array( 'ok' => false, 'msg' => '文章不存在或未发布', 'excerpt' => '' );
	}
	if ( ! bang_ai_ready() ) {
		return array( 'ok' => false, 'msg' => '未开启或未配置 AI', 'excerpt' => '' );
	}

	if ( ! $force ) {
		$exists = get_post_meta( $post_id, 'bang_ai_excerpt', true );
		if ( is_string( $exists ) && '' !== trim( $exists ) ) {
			return array( 'ok' => true, 'msg' => '已有摘要', 'excerpt' => $exists );
		}
	}

	/* 正文取纯文本，截到 2000 字，够 AI 判断主旨又不至于 payload 过大 */
	$content = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
	$content = trim( preg_replace( '/\s+/u', ' ', $content ) );
	$content = mb_substr( $content, 0, 2000, 'UTF-8' );
	if ( '' === $content ) {
		return array( 'ok' => false, 'msg' => '正文为空', 'excerpt' => '' );
	}

	$base  = rtrim( trim( (string) bang_opt( 'ai_api_base' ) ), '/' );
	$key   = trim( (string) bang_opt( 'ai_api_key' ) );
	$model = trim( (string) bang_opt( 'ai_model' ) );
	if ( '' === $model ) {
		$model = 'gpt-4o-mini';
	}

	$system = '你是中文博客的摘要编辑。用一句简洁、克制、有信息量的中文概括文章主旨，'
		. '不超过 58 个字。只输出摘要本身，不要引号、不要解释、不要前缀。';
	$prompt = '标题：' . get_the_title( $post_id ) . "\n正文：" . $content;

	$body = wp_json_encode(
		array(
			'model'       => $model,
			'messages'    => array(
				array( 'role' => 'system', 'content' => $system ),
				array( 'role' => 'user', 'content' => $prompt ),
			),
			'temperature' => 0.3,
			'max_tokens'  => 200,
		)
	);

	$response = wp_remote_post(
		$base . '/chat/completions',
		array(
			'timeout' => 20,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $key,
			),
			'body'    => $body,
		)
	);

	if ( is_wp_error( $response ) ) {
		return array( 'ok' => false, 'msg' => '请求失败：' . $response->get_error_message(), 'excerpt' => '' );
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$raw  = wp_remote_retrieve_body( $response );
	if ( $code < 200 || $code >= 300 ) {
		$msg = '接口返回 ' . $code;
		$err = json_decode( $raw, true );
		if ( isset( $err['error']['message'] ) ) {
			$msg .= '：' . mb_substr( sanitize_text_field( $err['error']['message'] ), 0, 120, 'UTF-8' );
		}
		return array( 'ok' => false, 'msg' => $msg, 'excerpt' => '' );
	}

	$data = json_decode( $raw, true );
	$text = isset( $data['choices'][0]['message']['content'] )
		? trim( (string) $data['choices'][0]['message']['content'] )
		: '';
	if ( '' === $text ) {
		return array( 'ok' => false, 'msg' => '接口未返回内容', 'excerpt' => '' );
	}

	/* 去掉 AI 常见的引号包裹与换行，统一成一行短句 */
	$text = preg_replace( '/\s+/u', ' ', $text );
	$text = trim( $text, " \t\n\r\0\x0B\"'“”「」" );
	$text = mb_substr( $text, 0, 58, 'UTF-8' );

	update_post_meta( $post_id, 'bang_ai_excerpt', $text );
	return array( 'ok' => true, 'msg' => '已生成', 'excerpt' => $text );
}

/**
 * 发布文章时自动生成摘要（仅首次发布、无摘要时）。
 * 用 transition_post_status 而不是 save_post：避免自动保存 / 修订版本反复触发。
 */
add_action( 'transition_post_status', 'bang_ai_on_publish', 10, 3 );
function bang_ai_on_publish( $new_status, $old_status, $post ) {
	if ( 'post' !== $post->post_type ) {
		return;
	}
	if ( 'publish' !== $new_status || 'publish' === $old_status ) {
		return;
	}
	if ( ! bang_ai_ready() ) {
		return;
	}
	bang_ai_generate_excerpt( $post->ID, false );
}

/**
 * 后台手动生成 / 重建摘要：REST POST bang/v1/ai-excerpt。
 * 仅管理员可用，nonce 走 wp_rest。
 */
add_action( 'rest_api_init', 'bang_ai_register_rest' );
function bang_ai_register_rest() {
	register_rest_route(
		'bang/v1',
		'/ai-excerpt',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_ai_rest_generate',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
			'args'                => array(
				'id'    => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'force' => array(
					'type'              => 'boolean',
					'sanitize_callback' => function ( $v ) { return (bool) $v; },
				),
			),
		)
	);
}

function bang_ai_rest_generate( $request ) {
	$result = bang_ai_generate_excerpt( (int) $request->get_param( 'id' ), (bool) $request->get_param( 'force' ) );
	if ( ! $result['ok'] ) {
		return new WP_REST_Response( $result, 400 );
	}
	return new WP_REST_Response( $result, 200 );
}

/**
 * 批量补生成：REST POST bang/v1/ai-excerpt/batch。
 * 每次处理一批（默认 5 篇）尚无 AI 摘要的已发布文章，前端循环调用直到 done。
 * 只处理 post_type=post，避免误伤页面与自定义类型。
 */
add_action( 'rest_api_init', 'bang_ai_register_batch_rest' );
function bang_ai_register_batch_rest() {
	register_rest_route(
		'bang/v1',
		'/ai-excerpt/batch',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_ai_rest_batch',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
			'args'                => array(
				'limit' => array(
					'type'              => 'integer',
					'default'           => 5,
					'sanitize_callback' => function ( $v ) { return max( 1, min( 10, absint( $v ) ) ); },
				),
			),
		)
	);
}

function bang_ai_rest_batch( $request ) {
	if ( ! bang_ai_ready() ) {
		return new WP_REST_Response( array( 'ok' => false, 'msg' => '未开启或未配置 AI', 'done' => true ), 400 );
	}
	$limit = (int) $request->get_param( 'limit' );

	/* 取尚无 bang_ai_excerpt 的已发布文章；用 meta_query NOT EXISTS 精确筛选 */
	$ids = get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'posts_per_page'   => $limit,
			'fields'           => 'ids',
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => true,
			'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'bang_ai_excerpt',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);

	$generated = 0;
	$failed    = 0;
	foreach ( $ids as $id ) {
		$r = bang_ai_generate_excerpt( $id, false );
		if ( $r['ok'] ) {
			$generated++;
		} else {
			$failed++;
		}
	}

	/* 再数一次剩余，决定前端是否继续 */
	$remaining = (int) get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => true,
			'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'bang_ai_excerpt',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);

	return new WP_REST_Response(
		array(
			'ok'        => true,
			'generated' => $generated,
			'failed'    => $failed,
			'remaining' => $remaining ? 1 : 0,
			'done'      => ( 0 === $remaining ),
		),
		200
	);
}

/**
 * 后台高级 tab 的 AI 摘要批量面板（只读展示 + 按钮，不参与表单保存）。
 */
function bang_ai_panel() {
	$ready = bang_ai_ready();
	$html  = '<div class="bang-ai-panel" data-bang-ai-panel>'
		. '<h3>' . esc_html__( '批量生成摘要', 'bang' ) . '</h3>'
		. '<p class="bang-f-d">' . esc_html__( '为已发布的文章补齐 AI 摘要。保存上方配置后，新文章会自动生成；这里用于补旧文章。每批 5 篇，调用接口可能需要一点时间。', 'bang' ) . '</p>';
	if ( ! $ready ) {
		$html .= '<p class="bang-f-d">' . esc_html__( '请先开启开关并填好接口地址与 API Key，保存设置后再回来操作。', 'bang' ) . '</p>';
	}
	$html .= '<p><button type="button" class="button" data-bang-ai-batch' . ( $ready ? '' : ' disabled' ) . '>'
		. esc_html__( '开始批量生成', 'bang' ) . '</button> '
		. '<span data-bang-ai-status></span></p>'
		. '</div>';
	return $html;
}
