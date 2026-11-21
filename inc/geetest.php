<?php
/**
 * 人机校验：极验行为验证 v4（可选接入）
 *
 * 设计要点：
 *  · 后台「人机校验」里选「极验 v4」且填好 Captcha ID / Key 才启用；没配齐
 *    （或本地预览没外网）自动回退到 inc/captcha.php 的自研滑块。
 *  · 只把 captchaId 下发前台，captcha_key 只留服务端（官方文档明确禁止下发）。
 *  · 前端 onSuccess 拿到 getValidate() 的四个字段，交给本模块的 REST 做二次校验；
 *    校验通过才签发和自研滑块完全一样的 bang_cap_ok_* 票据，因此注册 / 评论
 *    两个业务入口与 bang_captcha_ok() 一行都不用改。
 *  · 极验脚本加载失败 / 服务不可用时，前台自动切回自研滑块，不静默放行、也不堵死业务。
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BANG_GEETEST_SCRIPT', 'https://static.geetest.com/v4/gt4.js' );
define( 'BANG_GEETEST_VALIDATE', 'https://gcaptcha4.geetest.com/validate' );

/**
 * 当前实际生效的验证方式。ID / Key 没配齐一律回退本地滑块。
 *
 * @return string 'geetest' 或 'local'
 */
function bang_captcha_mode() {
	if ( 'geetest' !== (string) bang_opt( 'captcha_mode' ) ) {
		return 'local';
	}
	$id  = trim( (string) bang_opt( 'geetest_id' ) );
	$key = trim( (string) bang_opt( 'geetest_key' ) );
	return ( '' !== $id && '' !== $key ) ? 'geetest' : 'local';
}

/**
 * 二次校验接口。留空 / 填错协议时回退官方地址，顺带避免配置误填造成 SSRF。
 */
function bang_geetest_validate_url() {
	$url = trim( (string) bang_opt( 'geetest_validate_url' ) );
	if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
		$url = BANG_GEETEST_VALIDATE;
	}
	return $url;
}

/**
 * 服务端二次校验。
 *
 * 官方：POST /validate?captcha_id=ID，body 为 x-www-form-urlencoded：
 *   lot_number / captcha_output / pass_token / gen_time / sign_token
 *   sign_token = HMAC-SHA256(lot_number, captcha_key) 的 hex
 * result === 'success' 才发票据；不通过按自研滑块同一套 IP 限速计数。
 *
 * @param WP_REST_Request $request 请求。
 * @return WP_REST_Response|WP_Error
 */
function bang_geetest_rest( $request ) {
	if ( ! bang_captcha_nonce_verify( $request->get_param( 'nonce' ) ) ) {
		return new WP_Error( 'bang_cap_nonce', '页面已过期，刷新后再试。', array( 'status' => 403 ) );
	}
	if ( bang_captcha_locked() ) {
		return new WP_Error( 'bang_cap_locked', '验证失败次数过多，请过几分钟再试。', array( 'status' => 429 ) );
	}
	if ( bang_captcha_success_exceeded() ) {
		return new WP_Error( 'bang_cap_busy', '验证过于频繁，请稍后再试。', array( 'status' => 429 ) );
	}
	if ( 'geetest' !== bang_captcha_mode() ) {
		return new WP_Error( 'bang_geetest_off', '极验未启用，已切换为滑块验证。', array( 'status' => 400 ) );
	}

	$id  = trim( (string) bang_opt( 'geetest_id' ) );
	$key = trim( (string) bang_opt( 'geetest_key' ) );
	$lot = trim( (string) $request->get_param( 'lot_number' ) );
	$out = trim( (string) $request->get_param( 'captcha_output' ) );
	$pas = trim( (string) $request->get_param( 'pass_token' ) );
	$t   = trim( (string) $request->get_param( 'gen_time' ) );

	if ( '' === $lot || '' === $out || '' === $pas || '' === $t ) {
		return new WP_Error( 'bang_geetest_params', '验证参数不完整，请重试。', array( 'status' => 400 ) );
	}
	/* 流水号只允许可见 ASCII，避免把奇怪内容带进签名 / 出站请求 */
	if ( ! preg_match( '/^[A-Za-z0-9_-]{4,128}$/', $lot ) ) {
		bang_captcha_fail();
		return new WP_Error( 'bang_geetest_params', '验证流水号不合法。', array( 'status' => 400 ) );
	}

	$url  = add_query_arg( 'captcha_id', $id, bang_geetest_validate_url() );
	$body = array(
		'lot_number'     => $lot,
		'captcha_output' => $out,
		'pass_token'     => $pas,
		'gen_time'       => $t,
		'sign_token'     => hash_hmac( 'sha256', $lot, $key ),
	);

	/* 自己拼 query string，明确 x-www-form-urlencoded，不依赖 WP_Http 对数组 body 的隐式处理 */
	$resp = wp_remote_post(
		$url,
		array(
			'timeout' => 6,
			'body'    => http_build_query( $body, '', '&' ),
			'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
		)
	);

	/* 服务不可达：让前台切回自研滑块，既不静默放行也不把用户堵死 */
	if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
		return new WP_Error( 'bang_geetest_unavailable', '极验服务暂时不可用，已切换为滑块验证。', array( 'status' => 503 ) );
	}
	$json = json_decode( wp_remote_retrieve_body( $resp ), true );
	if ( ! is_array( $json ) ) {
		return new WP_Error( 'bang_geetest_unavailable', '极验服务返回异常，已切换为滑块验证。', array( 'status' => 503 ) );
	}
	if ( ! isset( $json['result'] ) || 'success' !== $json['result'] ) {
		bang_captcha_fail();
		$reason = isset( $json['reason'] ) ? (string) $json['reason'] : '';
		return new WP_Error( 'bang_geetest_fail', '验证没通过，请再试一次。', array( 'status' => 400, 'reason' => $reason ) );
	}

	bang_captcha_success_tick();
	return rest_ensure_response( bang_captcha_issue_ticket() );
}

/**
 * 注册路由。permission_callback 开放，靠 bang_captcha_nonce_verify() + 极验二次校验把关。
 */
function bang_geetest_rest_route() {
	register_rest_route(
		'bang/v1',
		'/geetest',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_geetest_rest',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bang_geetest_rest_route' );

/**
 * 后台「安全设置」里的接线说明（只读，不参与保存）。
 */
function bang_geetest_panel() {
	$tip = 'geetest' === bang_captcha_mode()
		? '当前生效：极验行为验证 v4。ID / Key 已配齐，前台注册与评论会改用极验按钮。'
		: '当前生效：自研滑块。切到「极验 v4」并填齐 Captcha ID 与 Key 后才会启用。';
	return '<div class="bang-geetest-panel">'
		. '<h3>' . esc_html__( '接线说明', 'bang' ) . '</h3>'
		. '<p class="bang-f-d">' . esc_html( $tip ) . '</p>'
		. '<p class="bang-f-d">' . esc_html__( '在极验后台新建「行为验证 v4」应用，拿到 Captcha ID 与 Key；Key 只存服务端用于生成 sign_token，不会下发到前台。', 'bang' ) . '</p>'
		. '<p class="bang-f-d">' . esc_html__( 'ID 或 Key 留空、以及极验脚本 / 接口连不上时，前台自动回退到自研滑块，本地预览与未配置站点不会被堵死。', 'bang' ) . '</p>'
		. '<p class="bang-f-d">' . esc_html__( '注册、评论的业务提交逻辑不变：极验证成功后同样换成一张 bang_cap_ok_* 票据，沿用原有服务端校验。', 'bang' ) . '</p>'
		. '</div>';
}
