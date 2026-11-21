<?php
/**
 * 人机校验：票据与挑战（自写滑块 / 极验 v4 共用）
 *
 * 自写滑块：没有第三方依赖，黑白极简的一条轨道 + 一枚滑块：按住往右拖到底，松手后
 * 前端把「终点比例 + 采样轨迹」交给服务端判定，通过才发一张票据。
 * 极验 v4 见 inc/geetest.php：服务端二次校验后调用 bang_captcha_issue_ticket() 发同一张票据，
 * 因此 bang_captcha_ok() 与注册 / 评论的业务入口完全不用改。
 *
 * 服务端三道关（都不是密码学级别的防护，目标是挡住批量脚本刷注册 / 刷评论）：
 *   1. 一次性挑战：challenge 签发的 token 存 transient，150 秒过期，验证即销毁；
 *   2. 轨迹合理性：终点 ≥94%、点数 6~240、耗时 260ms~15s、x 单调向右（允许 3% 抖动）；
 *   3. 按 IP 限速：10 分钟内失败 8 次直接拒绝，直到窗口过期。
 * 通过后拿到 ticket，注册 / 评论提交时带回来消费；10 分钟内最多用 5 次，
 * 表单填错重试不用重新拖，超过就作废。
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BANG_CAP_TTL', 150 );          // 挑战有效期（秒）
define( 'BANG_CAP_TICKET_TTL', 600 );   // 票据有效期（秒）
define( 'BANG_CAP_TICKET_MAX', 5 );     // 一张票据最多用几次
define( 'BANG_CAP_FAIL_MAX', 20 );      // 同一 IP 在窗口内允许的失败次数（正常人手滑几次远到不了）
define( 'BANG_CAP_FAIL_TTL', 600 );     // 失败计数窗口（秒）
define( 'BANG_CAP_CHALLENGE_MAX', 60 ); // 同一 IP 在窗口内最多签发多少次挑战（挡脚本刷挑战）
define( 'BANG_CAP_CHALLENGE_TTL', 600 );// 挑战签发计数窗口（秒）
define( 'BANG_CAP_SUCCESS_MAX', 30 );   // 同一 IP 在窗口内最多成功验证多少次（挡批量注册 / 灌水）
define( 'BANG_CAP_SUCCESS_TTL', 600 );  // 成功计数窗口（秒）
define( 'BANG_CAP_MIN_X', 0.94 );       // 滑块终点最低比例
define( 'BANG_CAP_MIN_MS', 260 );       // 轨迹最短耗时
define( 'BANG_CAP_MAX_MS', 15000 );     // 轨迹最长耗时
define( 'BANG_CAP_MIN_POINTS', 6 );     // 轨迹最少采样点
define( 'BANG_CAP_MAX_POINTS', 240 );   // 轨迹最多采样点

/**
 * 滑块挑战的轻量 nonce：刻意与登录态无关（0.13.19）。
 *
 * REST 请求只有带 X-WP-Nonce 头时 WordPress 才会按 auth cookie 认证用户；
 * 这里的 nonce 走 JSON body，所以 REST 里 get_current_user_id() 恒为 0（游客）。
 * 若沿用 wp_create_nonce('bang_captcha')（按当前登录用户生成），登录用户拿到的
 * nonce 会在 REST 里按游客校验而永远失败——0.13.19 评论强制登录后滑块直接不可用。
 * 页面被缓存（游客态 HTML 下发给登录用户）时同样会失效。
 * 故改为按站点 salt + 12 小时 tick 派生，前后两个 tick 都接受，跨边界不误杀。
 */
function bang_captcha_nonce() {
	return substr( wp_hash( 'bang_captcha|' . (int) ( time() / ( 12 * HOUR_IN_SECONDS ) ) ), -12, 10 );
}

function bang_captcha_nonce_verify( $nonce ) {
	$nonce = (string) $nonce;
	if ( '' === $nonce ) {
		return false;
	}
	$tick = (int) ( time() / ( 12 * HOUR_IN_SECONDS ) );
	for ( $i = 0; $i <= 1; $i++ ) {
		$expect = substr( wp_hash( 'bang_captcha|' . ( $tick - $i ) ), -12, 10 );
		if ( hash_equals( $expect, $nonce ) ) {
			return true;
		}
	}
	return false;
}

/**
 * IP 摘要：混 salt 后取 md5，不落明地址；挑战与票据据此绑定来源 IP。
 */
function bang_captcha_ip_hash() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	return md5( $ip . '|' . wp_salt( 'nonce' ) );
}

/**
 * 失败计数的键：按 IP，混salt 后取 md5，不落明地址
 */
function bang_captcha_ip_key() {
	return 'bang_cap_f_' . bang_captcha_ip_hash();
}

/**
 * 这个 IP 是否已被限速
 */
function bang_captcha_locked() {
	return (int) get_transient( bang_captcha_ip_key() ) >= BANG_CAP_FAIL_MAX;
}

/**
 * 记一次失败
 */
function bang_captcha_fail() {
	$key = bang_captcha_ip_key();
	$n   = (int) get_transient( $key ) + 1;
	set_transient( $key, $n, BANG_CAP_FAIL_TTL );
}

/**
 * 挑战签发限频：同一 IP 窗口内签太多就不再签发（挡脚本无限刷挑战）
 */
function bang_captcha_challenge_exceeded() {
	return (int) get_transient( 'bang_cap_ch_' . bang_captcha_ip_hash() ) >= BANG_CAP_CHALLENGE_MAX;
}

function bang_captcha_challenge_tick() {
	$key = 'bang_cap_ch_' . bang_captcha_ip_hash();
	set_transient( $key, (int) get_transient( $key ) + 1, BANG_CAP_CHALLENGE_TTL );
}

/**
 * 成功限频：同一 IP 窗口内验证成功太频繁就拒绝（挡批量注册 / 灌水）
 */
function bang_captcha_success_exceeded() {
	return (int) get_transient( 'bang_cap_s_' . bang_captcha_ip_hash() ) >= BANG_CAP_SUCCESS_MAX;
}

function bang_captcha_success_tick() {
	$key = 'bang_cap_s_' . bang_captcha_ip_hash();
	set_transient( $key, (int) get_transient( $key ) + 1, BANG_CAP_SUCCESS_TTL );
}

/**
 * 校验票据（注册 / 评论提交时调用）
 *
 * @param mixed $ticket 前端回传的票据。
 * @return bool
 */
function bang_captcha_ok( $ticket ) {
	$ticket = is_string( $ticket ) ? preg_replace( '/[^A-Za-z0-9]/', '', $ticket ) : '';
	if ( '' === $ticket ) {
		return false;
	}
	$key = 'bang_cap_ok_' . $ticket;
	$row = get_transient( $key );
	if ( ! is_array( $row ) || empty( $row['t'] ) ) {
		return false;
	}
	/* 票据绑定签发 IP：跨 IP 复用直接拒绝（旧票据无 ip 字段则放行，兼容升级窗口） */
	if ( ! empty( $row['ip'] ) && ! hash_equals( (string) $row['ip'], bang_captcha_ip_hash() ) ) {
		return false;
	}
	/* 绝对过期时间：每次消费不重置 10 分钟窗口，避免票据被低频反复使用而永不过期 */
	$exp = isset( $row['e'] ) ? (int) $row['e'] : ( (int) $row['t'] + BANG_CAP_TICKET_TTL );
	if ( time() >= $exp ) {
		delete_transient( $key );
		return false;
	}
	$used = (int) ( isset( $row['n'] ) ? $row['n'] : 0 ) + 1;
	if ( $used > BANG_CAP_TICKET_MAX ) {
		delete_transient( $key );
		return false;
	}
	set_transient(
		$key,
		array(
			'n'  => $used,
			't'  => (int) $row['t'],
			'e'  => $exp,
			'ip' => isset( $row['ip'] ) ? (string) $row['ip'] : '',
		),
		max( 1, $exp - time() )
	);
	return true;
}

/**
 * 签发一张校验票据。自写滑块与极验 v4 都走这里，保证消费端 bang_captcha_ok() 不变。
 *
 * @return array
 */
function bang_captcha_issue_ticket() {
	$ticket = wp_generate_password( 32, false, false );
	$now    = time();
	set_transient(
		'bang_cap_ok_' . $ticket,
		array(
			'n'  => 0,
			't'  => $now,
			'e'  => $now + BANG_CAP_TICKET_TTL,
			'ip' => bang_captcha_ip_hash(),
		),
		BANG_CAP_TICKET_TTL
	);
	return array(
		'ok'     => true,
		'ticket' => $ticket,
		'ttl'    => BANG_CAP_TICKET_TTL,
	);
}

/**
 * 判定一次拖动
 *
 * @param string $token  挑战 token。
 * @param float  $x      终点比例（0~1）。
 * @param array  $points 轨迹，[[x, t], …]，t 为毫秒。
 * @return string|true 通过返回 true，否则返回错误码。
 */
function bang_captcha_judge( $token, $x, $points ) {
	$key = 'bang_cap_' . preg_replace( '/[^A-Za-z0-9]/', '', (string) $token );
	$row = get_transient( $key );
	if ( ! is_array( $row ) ) {
		return 'expired';
	}
	/* 挑战绑定签发 IP：截获 token 换 IP 提交视为过期 */
	if ( ! empty( $row['ip'] ) && ! hash_equals( (string) $row['ip'], bang_captcha_ip_hash() ) ) {
		return 'expired';
	}
	/* 签发到校验至少经过最短拖动耗时，挡住脚本拿到 token 后瞬间构造轨迹 */
	if ( ! empty( $row['t'] ) && ( microtime( true ) - (float) $row['t'] ) * 1000 < BANG_CAP_MIN_MS ) {
		return 'trace';
	}

	/* 终点没拖到头：直接判负，不必再看轨迹 */
	if ( $x < BANG_CAP_MIN_X ) {
		return 'short';
	}

	if ( ! is_array( $points ) ) {
		return 'trace';
	}
	$points = array_values( $points );
	$total  = count( $points );
	if ( $total < BANG_CAP_MIN_POINTS || $total > BANG_CAP_MAX_POINTS ) {
		return 'trace';
	}

	$first = null;
	$last  = null;
	$prev  = 0;
	foreach ( $points as $point ) {
		if ( ! is_array( $point ) || count( $point ) < 2 ) {
			return 'trace';
		}
		$px = (float) $point[0];
		$pt = (float) $point[1];
		if ( $px < -0.01 || $px > 1.02 || $pt < 0 || $pt > BANG_CAP_MAX_MS * 2 ) {
			return 'trace';
		}
		/* 只许往右，手抖回退给 3% 余量 */
		if ( $px < $prev - 0.03 ) {
			return 'trace';
		}
		$prev = $px;
		if ( null === $first ) {
			$first = $pt;
		}
		$last = $pt;
	}
	if ( null === $first || null === $last ) {
		return 'trace';
	}
	$span = $last - $first;
	if ( $span < BANG_CAP_MIN_MS || $span > BANG_CAP_MAX_MS ) {
		return 'trace';
	}
	if ( $prev < BANG_CAP_MIN_X ) {
		return 'short';
	}

	/* 通过：挑战一次性，用完即删 */
	delete_transient( $key );
	return true;
}

/**
 * REST：签发挑战 / 校验拖动
 *
 * @param WP_REST_Request $request 请求。
 */
function bang_captcha_rest( $request ) {
	if ( ! bang_captcha_nonce_verify( $request->get_param( 'nonce' ) ) ) {
		return new WP_Error( 'bang_cap_nonce', '页面已过期，刷新后再试。', array( 'status' => 403 ) );
	}
	if ( bang_captcha_locked() ) {
		return new WP_Error( 'bang_cap_locked', '验证失败次数过多，请过几分钟再试。', array( 'status' => 429 ) );
	}
	if ( bang_captcha_success_exceeded() ) {
		return new WP_Error( 'bang_cap_busy', '验证过于频繁，请稍后再试。', array( 'status' => 429 ) );
	}

	$action = sanitize_key( (string) $request->get_param( 'action' ) );

	if ( 'challenge' === $action ) {
		if ( bang_captcha_challenge_exceeded() ) {
			return new WP_Error( 'bang_cap_busy', '验证请求过于频繁，请稍后再试。', array( 'status' => 429 ) );
		}
		$token = wp_generate_password( 20, false, false );
		set_transient(
			'bang_cap_' . $token,
			array(
				't'  => microtime( true ),
				'ip' => bang_captcha_ip_hash(),
			),
			BANG_CAP_TTL
		);
		bang_captcha_challenge_tick();
		return rest_ensure_response(
			array(
				'ok'    => true,
				'token' => $token,
				'ttl'   => BANG_CAP_TTL,
			)
		);
	}

	if ( 'verify' !== $action ) {
		return new WP_Error( 'bang_cap_action', '未知请求。', array( 'status' => 400 ) );
	}

	$x      = (float) $request->get_param( 'x' );
	$points = $request->get_param( 'points' );
	$result = bang_captcha_judge( (string) $request->get_param( 'token' ), $x, $points );

	if ( true !== $result ) {
		bang_captcha_fail();
		$msgs = array(
			'expired' => '验证已过期，请再拖一次。',
			'short'   => '没拖到位，请拖到最右侧再松手。',
			'trace'   => '这次滑动没被识别，请重新拖一次。',
		);
		return new WP_Error(
			'bang_cap_' . $result,
			isset( $msgs[ $result ] ) ? $msgs[ $result ] : '验证没通过，再试一次。',
			array( 'status' => 400 )
		);
	}

	bang_captcha_success_tick();
	return rest_ensure_response( bang_captcha_issue_ticket() );
}

/**
 * 注册路由
 */
function bang_captcha_rest_route() {
	register_rest_route(
		'bang/v1',
		'/captcha',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_captcha_rest',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bang_captcha_rest_route' );

/**
 * 滑块组件 HTML
 *
 * @param string $name  随表单提交的字段名。
 * @param string $label 轨道上的提示文案。
 * @param bool   $lazy  是否默认收起（评论区用：点「发表评论」后才展开，拖完自动提交）。
 */
function bang_captcha_html( $name = 'bang_cap', $label = '按住滑块，拖到最右侧', $lazy = false ) {
	$mode = function_exists( 'bang_captcha_mode' ) ? bang_captcha_mode() : 'local';
	$html  = '<div class="bang-cap" data-bang-cap data-bang-cap-mode="' . esc_attr( $mode ) . '" data-state="idle"'
		. ( $lazy ? ' data-bang-cap-lazy hidden' : '' ) . '>';
	$html .= '<div class="bang-cap-track" data-bang-cap-track>';
	$html .= '<span class="bang-cap-fill" data-bang-cap-fill aria-hidden="true"></span>';
	$html .= '<span class="bang-cap-hint" data-bang-cap-hint>' . esc_html( $label ) . '</span>';
	$html .= '<span class="bang-cap-pass" data-bang-cap-pass aria-hidden="true">' . bang_icon( 'check', 14 ) . '<span>验证通过</span></span>';
	$html .= '<button class="bang-cap-thumb" type="button" data-bang-cap-thumb aria-label="' . esc_attr( $label ) . '">';
	$html .= '<span class="bang-cap-ico bang-cap-ico-go" aria-hidden="true">' . bang_icon( 'arrow-right', 16 ) . '</span>';
	$html .= '<span class="bang-cap-ico bang-cap-ico-ok" aria-hidden="true">' . bang_icon( 'check', 16 ) . '</span>';
	$html .= '<span class="bang-cap-spin" aria-hidden="true"></span>';
	$html .= '</button>';
	$html .= '</div>';
	/* 极验 v4 的按钮挂点：两套 DOM 常驻，靠 data-bang-cap-mode 切显隐。
	   gt4.js 加载失败时前台把模式改回 local 就能就地用回上面的滑块，不会卡住表单。 */
	$html .= '<div class="bang-cap-geetest" data-bang-cap-geetest>';
	$html .= '<div class="bang-cap-geetest-mount" data-bang-cap-geetest-mount></div>';
	$html .= '<span class="bang-cap-geetest-ok" aria-hidden="true">' . bang_icon( 'check', 14 ) . '<span>验证通过</span></span>';
	$html .= '</div>';
	$html .= '<input type="hidden" name="' . esc_attr( $name ) . '" value="" data-bang-cap-input>';
	$html .= '<p class="bang-cap-msg" data-bang-cap-msg role="status" aria-live="polite"></p>';
	$html .= '</div>';
	return $html;
}
