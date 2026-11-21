<?php
/**
 * 用户中心：登录 / 注册 / 个人资料 / 点赞与评论记录
 *
 * 不依赖任何插件，全部走 WordPress 核心能力：
 * - 登录   wp_signon()
 * - 注册   wp_create_user()（受「设置 → 常规 → 允许注册」与主题设置里的开关控制）
 * - 记录   点赞存 user_meta，点赞数存 post_meta
 *
 * 表单提交到 admin-post.php，用 nonce 校验后 302 回「用户中心」，避免后台页面跳转。
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 注册邮箱后缀限制：留空不限制。
 * 一行一个后缀，可写 @qq.com 或 qq.com；以 - 开头为拒绝列表（如 -qq.com），
 * 其余行组成允许列表——只允许列表里的后缀注册。
 *
 * @param string $email 注册邮箱。
 * @return bool 是否被拒绝。
 */
function bang_register_email_blocked( $email ) {
	$raw = trim( (string) bang_opt( 'register_email_domains' ) );
	if ( '' === $raw ) {
		return false;
	}
	$email = strtolower( trim( (string) $email ) );
	$pos   = strrpos( $email, '@' );
	if ( false === $pos ) {
		return false;
	}
	$domain = substr( $email, $pos + 1 );
	if ( '' === $domain ) {
		return false;
	}

	$allow = array();
	$deny  = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line || 0 === strpos( $line, '#' ) ) {
			continue;
		}
		$is_deny = ( 0 === strpos( $line, '-' ) );
		if ( $is_deny ) {
			$line = ltrim( substr( $line, 1 ), '-' );
		}
		$line = ltrim( strtolower( trim( str_replace( '\\', '/', $line ) ) ), '@' );
		$line = rtrim( $line, '/' );
		if ( '' === $line ) {
			continue;
		}
		if ( $is_deny ) {
			$deny[] = $line;
		} else {
			$allow[] = $line;
		}
	}

	/* 拒绝列表优先：命中即拦 */
	foreach ( $deny as $d ) {
		if ( $d === $domain || substr( $domain, -strlen( $d ) - 1 ) === '.' . $d ) {
			return true;
		}
	}

	/* 没有允许列表 = 不限制 */
	if ( ! $allow ) {
		return false;
	}
	foreach ( $allow as $a ) {
		if ( $a === $domain || substr( $domain, -strlen( $a ) - 1 ) === '.' . $a ) {
			return false;
		}
	}
	return true;
}

/**
 * 用户中心地址
 */
function bang_user_url() {
	return home_url( '/user/' );
}

/**
 * 账号设置页地址（独立页面 slug: settings）
 *
 * 页面由 bang_ensure_site_pages() 自动补齐；还没建好时退回固定路径，
 * 和 bang_site_page_url() 的兜底口径一致。
 */
function bang_settings_url() {
	return function_exists( 'bang_site_page_url' ) ? bang_site_page_url( 'settings' ) : home_url( '/settings/' );
}

/**
 * 只允许站内跳转，避免开放重定向
 *
 * @param string $fallback 兜底地址。
 */
function bang_user_redirect( $fallback = '' ) {
	$fallback = $fallback ? $fallback : bang_user_url();
	$raw      = isset( $_REQUEST['redirect_to'] ) ? wp_unslash( $_REQUEST['redirect_to'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$url      = $raw ? wp_validate_redirect( esc_url_raw( $raw ), $fallback ) : $fallback;
	return $url ? $url : $fallback;
}

/**
 * 回到用户中心并带上错误码
 *
 * @param string $code     错误标识，见 page-user.php 的 map。
 * @param string $redirect 成功后要去的地址。
 * @param string $message  可选的自定义文案（违禁词命中时带上具体词）。
 */
function bang_user_fail( $code, $redirect = '', $message = '' ) {
	$args = array( 'bang_err' => $code );
	if ( $message ) {
		$args['bang_err_msg'] = $message;
	}
	if ( $redirect ) {
		$args['redirect_to'] = $redirect;
	}
	/* add_query_arg 会 urlencode，页面再 wp_unslash 读回来，中文不会坏 */
	wp_safe_redirect( add_query_arg( $args, bang_auth_from_url() ) );
	exit;
}

/**
 * 表单是不是走 pjax 的 AJAX 提交
 *
 * 普通 POST（禁用 JS / 直接提交）仍保持 302 跳转的老行为；
 * 带 bang_ajax=1 时返回 JSON，避免前端依赖浏览器跟随 302 的行为。
 */
function bang_auth_is_ajax() {
	return isset( $_POST['bang_ajax'] ) && '1' === (string) wp_unslash( $_POST['bang_ajax'] ); // phpcs:ignore WordPress.Security.NonceVerification
}

/**
 * 认证成功出口：AJAX 返回目标地址与最新登录态，普通提交走 302
 *
 * @param string $redirect 成功后要去的地址。
 */
function bang_auth_ok( $redirect, $uid = 0 ) {
	if ( bang_auth_is_ajax() ) {
		/* wp_signon 只发 Cookie，当前请求仍是游客；切过来后生成的
		   wp_rest nonce 与点赞列表才属于登录用户 */
		if ( $uid ) {
			wp_set_current_user( $uid );
		}
		wp_send_json_success(
			array(
				'redirect' => $redirect,
				'user'     => bang_social_config(),
			)
		);
	}
	wp_safe_redirect( $redirect );
	exit;
}

/**
 * 认证失败出口：AJAX 返回错误码与带 bang_err 的地址，普通提交走 302
 *
 * @param string $code     错误标识。
 * @param string $redirect 成功后要去的地址。
 * @param string $message  可选的自定义文案（违禁词命中时带上具体词）。
 */
function bang_auth_fail( $code, $redirect = '', $message = '' ) {
	if ( ! $message ) {
		$message = bang_auth_error_text( $code );
	}
	if ( bang_auth_is_ajax() ) {
		$target = add_query_arg( 'bang_err', $code, bang_auth_from_url() );
		if ( $redirect ) {
			$target = add_query_arg( 'redirect_to', $redirect, $target );
		}
		wp_send_json_error(
			array(
				'code'     => $code,
				'message'  => $message,
				'redirect' => $target,
			)
		);
	}
	bang_user_fail( $code, $redirect, $message );
}

/* ---------- 信任设备：30 天免密自动登录 ----------
   浏览器生态差异很大（多数国产浏览器内核不支持 WebAuthn），指纹登录覆盖不了所有设备。
   这套兜底：登录成功后发一个长效随机令牌 Cookie（HttpOnly，30 天），站点只存它的哈希。
   WP 登录 Cookie 过期后由它自动恢复登录态，体验上就是「打开用户中心还是登录好的」。
   退出登录立即销毁令牌并清 Cookie；每用户最多保留 5 台设备，超出挤掉最早的。 */

const BANG_DEVICE_COOKIE = 'bang_device';
const BANG_DEVICE_DAYS   = 30;
const BANG_DEVICE_MAX    = 5;

/**
 * 令牌只存哈希，泄露数据库也拿不到可用 Cookie
 *
 * @param string $token 明文令牌。
 * @return string
 */
function bang_device_hash( $token ) {
	return wp_hash( $token );
}

/**
 * 写 / 清设备 Cookie
 *
 * @param string $token 明文令牌；空串表示删除。
 * @param int    $days  有效天数，0 表示立即过期。
 */
function bang_device_send_cookie( $token, $days = BANG_DEVICE_DAYS ) {
	if ( headers_sent() ) {
		return;
	}
	setcookie(
		BANG_DEVICE_COOKIE,
		$token,
		array(
			'expires'  => $days > 0 ? time() + $days * DAY_IN_SECONDS : 1,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
	$_COOKIE[ BANG_DEVICE_COOKIE ] = $token;
}

/**
 * 给用户签发一台信任设备（登录 / 注册 / 指纹登录成功后调用）
 *
 * @param int $user_id 用户 ID。
 */
function bang_device_issue( $user_id ) {
	$token   = wp_generate_password( 64, false, false );
	$devices = get_user_meta( $user_id, 'bang_devices', true );
	if ( ! is_array( $devices ) ) {
		$devices = array();
	}
	$devices[ bang_device_hash( $token ) ] = array(
		'time' => time(),
		'ua'   => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ), 0, 120 ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	);
	if ( count( $devices ) > BANG_DEVICE_MAX ) {
		uasort( $devices, function ( $a, $b ) {
			return $a['time'] <=> $b['time'];
		} );
		$devices = array_slice( $devices, count( $devices ) - BANG_DEVICE_MAX, null, true );
	}
	update_user_meta( $user_id, 'bang_devices', $devices );
	bang_device_send_cookie( $token );
}

/**
 * 按哈希反查用户（哈希是 64 位 hex，LIKE 查序列化 meta 足够精确）
 *
 * @param string $hash wp_hash 后的令牌哈希。
 * @return int 用户 ID，无效返回 0。
 */
function bang_device_find( $hash ) {
	$query = new WP_User_Query(
		array(
			'meta_key'     => 'bang_devices',
			'meta_compare' => 'LIKE',
			'meta_value'   => $hash,
			'number'       => 3,
		)
	);
	foreach ( $query->get_results() as $user ) {
		$devices = get_user_meta( $user->ID, 'bang_devices', true );
		if ( is_array( $devices ) && isset( $devices[ $hash ] ) ) {
			$info = $devices[ $hash ];
			if ( is_array( $info ) && (int) $info['time'] + BANG_DEVICE_DAYS * DAY_IN_SECONDS > time() ) {
				return (int) $user->ID;
			}
		}
	}
	return 0;
}

/**
 * 登录态失效时用设备令牌自动恢复（放在最早期，趁还没输出任何头）
 */
function bang_device_autologin() {
	if ( is_user_logged_in() || empty( $_COOKIE[ BANG_DEVICE_COOKIE ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		return;
	}
	$uid = bang_device_find( bang_device_hash( (string) $_COOKIE[ BANG_DEVICE_COOKIE ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( ! $uid ) {
		/* 令牌失效 / 被挤掉，Cookie 也清掉，下次不再空转 */
		bang_device_send_cookie( '', 0 );
		return;
	}
	wp_set_current_user( $uid );
	wp_set_auth_cookie( $uid, true, is_ssl() );
}
add_action( 'init', 'bang_device_autologin', 1 );

/**
 * 退出登录：销毁当前设备的令牌 + Cookie
 *
 * 注意 wp_logout 触发本钩子时当前用户已被置 0，必须用钩子参数拿用户 ID。
 *
 * @param int $user_id 退出前的用户 ID。
 */
function bang_device_forget( $user_id = 0 ) {
	$user_id = (int) ( $user_id ? $user_id : get_current_user_id() );
	if ( $user_id && ! empty( $_COOKIE[ BANG_DEVICE_COOKIE ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$hash    = bang_device_hash( (string) $_COOKIE[ BANG_DEVICE_COOKIE ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$devices = get_user_meta( $user_id, 'bang_devices', true );
		if ( is_array( $devices ) && isset( $devices[ $hash ] ) ) {
			unset( $devices[ $hash ] );
			update_user_meta( $user_id, 'bang_devices', $devices );
		}
	}
	bang_device_send_cookie( '', 0 );
}
add_action( 'wp_logout', 'bang_device_forget', 1, 1 );

/**
 * 登录失败限流：IP + 用户名双维度，挡在线爆破。
 * 15 分钟内同 IP+账号失败 5 次、或同 IP 累计失败 20 次即临时锁定；登录成功清零。
 * 计数只存 transient（站点级缓存），不落库、不记明文 IP。
 */
function bang_login_rate_key( $login ) {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	return 'bang_login_f_' . md5( $ip . '|' . strtolower( (string) $login ) . '|' . wp_salt( 'nonce' ) );
}

function bang_login_ip_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	return 'bang_login_ip_' . md5( $ip . '|' . wp_salt( 'nonce' ) );
}

function bang_login_is_locked( $login ) {
	return (int) get_transient( bang_login_rate_key( $login ) ) >= 5
		|| (int) get_transient( bang_login_ip_key() ) >= 20;
}

function bang_login_fail( $login ) {
	$k1 = bang_login_rate_key( $login );
	$k2 = bang_login_ip_key();
	set_transient( $k1, (int) get_transient( $k1 ) + 1, 15 * MINUTE_IN_SECONDS );
	set_transient( $k2, (int) get_transient( $k2 ) + 1, 15 * MINUTE_IN_SECONDS );
}

function bang_login_clear( $login ) {
	delete_transient( bang_login_rate_key( $login ) );
	delete_transient( bang_login_ip_key() );
}

/**
 * 登录
 */
function bang_handle_login() {
	check_admin_referer( 'bang_login' );

	$redirect = bang_user_redirect();
	if ( is_user_logged_in() ) {
		bang_auth_ok( $redirect );
	}

	$login = isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : '';
	$pwd   = isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '';
	if ( '' === $login || '' === $pwd ) {
		bang_auth_fail( 'empty', $redirect );
	}
	/* 允许用邮箱登录：wp_signon 只认 user_login，先换一次 */
	if ( is_email( $login ) ) {
		$by_mail = get_user_by( 'email', $login );
		if ( $by_mail ) {
			$login = $by_mail->user_login;
		}
	}
	/* 爆破防护：失败次数超阈值时直接锁定，连密码校验都不做 */
	if ( bang_login_is_locked( $login ) ) {
		bang_auth_fail( 'login_locked', $redirect );
	}

	$user = wp_signon(
		array(
			'user_login'    => $login,
			'user_password' => $pwd,
			'remember'      => ! empty( $_POST['remember'] ),
		),
		is_ssl()
	);
	if ( is_wp_error( $user ) ) {
		bang_login_fail( $login );
		bang_auth_fail( 'login', $redirect );
	}

	bang_login_clear( $login );
	bang_device_issue( $user->ID );

	bang_auth_ok( $redirect, $user->ID );
}
add_action( 'admin_post_nopriv_bang_login', 'bang_handle_login' );
add_action( 'admin_post_bang_login', 'bang_handle_login' );

/**
 * 注册：校验后直接登录，省掉「去邮箱收密码」这一步（演示站不配发信）
 */
function bang_handle_register() {
	check_admin_referer( 'bang_register' );

	if ( is_user_logged_in() ) {
		bang_auth_ok( bang_user_url() );
	}
	if ( ! get_option( 'users_can_register' ) ) {
		bang_auth_fail( 'closed' );
	}

	$login = isset( $_POST['user_login'] ) ? sanitize_user( wp_unslash( $_POST['user_login'] ) ) : '';
	$email = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
	$pwd   = isset( $_POST['user_pass'] ) ? (string) wp_unslash( $_POST['user_pass'] ) : '';
	$pwd2  = isset( $_POST['user_pass2'] ) ? (string) wp_unslash( $_POST['user_pass2'] ) : '';

	if ( ! $login || ! $email || ! $pwd ) {
		bang_auth_fail( 'fields' );
	}
	if ( ! validate_username( $login ) ) {
		bang_auth_fail( 'badname' );
	}
	if ( ! is_email( $email ) ) {
		bang_auth_fail( 'bademail' );
	}
	/* 注册邮箱后缀限制：留空不限制；一行一个后缀（@qq.com 或 qq.com），
	   以 - 开头为拒绝列表，其余行组成允许列表。见后台「安全设置」。 */
	if ( function_exists( 'bang_register_email_blocked' ) && bang_register_email_blocked( $email ) ) {
		bang_auth_fail( 'email_domain', bang_settings_url(), sprintf( '该邮箱后缀不支持注册（%s）。', esc_html( substr( (string) $email, (int) strrpos( (string) $email, '@' ) ) ) ) );
	}
	if ( strlen( $pwd ) < 6 ) {
		bang_auth_fail( 'weakpass' );
	}
	if ( $pwd !== $pwd2 ) {
		bang_auth_fail( 'mismatch' );
	}
	/* 人机校验：滑块票据（一次性挑战换来的，10 分钟内可复用几次） */
	$ticket = isset( $_POST['bang_cap'] ) ? sanitize_text_field( wp_unslash( $_POST['bang_cap'] ) ) : '';
	if ( ! bang_captcha_ok( $ticket ) ) {
		bang_auth_fail( 'captcha' );
	}
	if ( username_exists( $login ) || email_exists( $email ) ) {
		bang_auth_fail( 'exists' );
	}

	$uid = wp_create_user( $login, $pwd, $email );
	if ( is_wp_error( $uid ) ) {
		bang_auth_fail( 'register' );
	}

	wp_update_user(
		array(
			'ID'           => $uid,
			'display_name' => $login,
			'nickname'     => $login,
		)
	);
	wp_set_current_user( $uid );
	wp_set_auth_cookie( $uid, true, is_ssl() );
	bang_device_issue( $uid );

	bang_auth_ok( bang_user_url() );
}
add_action( 'admin_post_nopriv_bang_register', 'bang_handle_register' );
add_action( 'admin_post_bang_register', 'bang_handle_register' );

/**
 * 头像地址：优先账号设置上传的附件 ID（可出 2x 图），旧数据回退 URL 字段。
 * 不走 Gravatar —— 内网 / 离线环境会破图。没有自定义头像时返回空串，
 * 侧栏 / 用户中心据此决定显示真实头像还是名字首字。
 *
 * @param WP_User|int $user 用户对象或 ID。
 * @param int         $size 显示尺寸（像素），用于挑选 2x 缩略图。
 */
function bang_user_avatar_url( $user, $size = 64 ) {
	$uid = is_object( $user ) ? (int) $user->ID : (int) $user;
	if ( ! $uid ) {
		return '';
	}
	/* 0.13.6 起头像存附件 ID；旧数据仍是 URL 字段 bang_avatar */
	$aid = (int) get_the_author_meta( 'bang_avatar_id', $uid );
	if ( $aid ) {
		$url = wp_get_attachment_image_url( $aid, array( (int) $size * 2, (int) $size * 2 ) );
		if ( $url ) {
			return (string) $url;
		}
	}
	$custom = (string) get_the_author_meta( 'bang_avatar', $uid );
	return $custom ? $custom : '';
}

/**
 * 头像：有自定义头像就输出真实图片，没有才显示名字首字
 *
 * 和作者卡一致，不走 Gravatar —— 内网 / 离线环境不会破图。
 *
 * @param WP_User $user 用户。
 * @param int     $size 像素。
 */
function bang_user_avatar( $user, $size = 64 ) {
	$url = bang_user_avatar_url( $user, $size );
	if ( $url ) {
		return '<img class="user-avatar" src="' . esc_url( $url ) . '" alt="" width="' . (int) $size . '" height="' . (int) $size . '" loading="lazy" decoding="async">';
	}
	$name = is_object( $user ) ? (string) ( $user->display_name ? $user->display_name : $user->user_login ) : '';
	$char = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1, 'UTF-8' ) : substr( $name, 0, 1 );
	return '<span class="user-avatar user-avatar-letter" aria-hidden="true">' . esc_html( $char ) . '</span>';
}

/**
 * 手机端动作条位置的可选项
 *
 * follow：跟随重力感应（默认），倾斜手机自动左右换边；
 * left / right：用户固定在一侧，不再跟随。
 *
 * @return array<string,string>
 */
function bang_dock_sides() {
	return array(
		'follow' => '跟随倾斜',
		'left'   => '固定在左',
		'right'  => '固定在右',
	);
}

/**
 * 当前用户的动作条位置偏好
 *
 * 未登录 / 没设置过都回落到 follow。未登录时的偏好由前端存 localStorage，
 * 这里只负责登录用户（跨设备同步）。
 */
function bang_dock_side() {
	$side = is_user_logged_in() ? (string) get_user_meta( get_current_user_id(), 'bang_dock_side', true ) : '';
	return array_key_exists( $side, bang_dock_sides() ) ? $side : 'follow';
}

/**
 * 保存偏好：POST /wp-json/bang/v1/prefs { dock_side }
 *
 * 回调直接返回数组，响应体是 {dockSide}，和点赞接口保持一致。
 *
 * @param WP_REST_Request $request 请求。
 * @return array|WP_Error
 */
function bang_prefs_save( $request ) {
	$side = sanitize_key( (string) $request->get_param( 'dock_side' ) );
	if ( ! array_key_exists( $side, bang_dock_sides() ) ) {
		return new WP_Error( 'bang_bad_side', '位置参数不对', array( 'status' => 400 ) );
	}
	update_user_meta( get_current_user_id(), 'bang_dock_side', $side );
	return array( 'dockSide' => $side );
}

function bang_prefs_rest_route() {
	register_rest_route(
		'bang/v1',
		'/prefs',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_prefs_save',
			'permission_callback' => function () {
				return is_user_logged_in();
			},
			'args'                => array(
				'dock_side' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_key',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'bang_prefs_rest_route' );

/**
 * 用户中心用到的统计
 *
 * @param int $user_id 用户 ID。
 */
function bang_user_stats( $user_id ) {
	$comments = get_comments(
		array(
			'user_id' => $user_id,
			'status'  => 'approve',
			'type'    => 'comment',
			'count'   => true,
		)
	);
	return array(
		'like'    => count( bang_social_ids( $user_id ) ),
		'comment' => (int) $comments,
	);
}

/**
 * pjax 换页后同步登录态
 *
 * Swup 只交换 #swup-wrapper，wp_localize_script 注入的 bangUser 不会重新执行。
 * 登录 / 退出 / 信任设备自动登录都会改变登录态、点赞列表与 wp_rest nonce，
 * 所以每次无刷新换页都拉一次最新配置。只读接口，不写任何数据。
 */
function bang_ajax_user_state() {
	wp_send_json( bang_social_config() );
}
add_action( 'wp_ajax_nopriv_bang_user_state', 'bang_ajax_user_state' );
add_action( 'wp_ajax_bang_user_state', 'bang_ajax_user_state' );

/* ============================================================
   0.13.6 个人中心：改昵称 / 换头像 / 注销账号
   都走 admin-post.php + nonce，普通表单提交后 302 回 /user/，
   提示语用 bang_msg（成功）/ bang_err（失败）带回，不依赖 JS。
   ============================================================ */

/* ============================================================
   邮箱验证码：改邮箱 / 改密码必须验证当前绑定邮箱
   —— 验证码发到旧邮箱（账号当前的可信渠道），证明是本人操作；
      改邮箱后 WP 原生还会发确认信到新邮箱验证所有权，双保险。
   ============================================================ */

/** 验证码有效期（秒） */
function bang_email_code_ttl() {
	return 10 * MINUTE_IN_SECONDS;
}

/** 两次发送的最小间隔（秒） */
function bang_email_code_interval() {
	return 60;
}

/** 最多错误尝试次数 */
function bang_email_code_max_tries() {
	return 5;
}

/** 验证码哈希（明文不落库） */
function bang_email_code_hash( $code, $uid ) {
	return hash( 'sha256', (string) $code . '|' . (int) $uid . '|' . wp_salt( 'auth' ) );
}

/** 清掉某用户的验证码状态 */
function bang_email_code_clear( $uid ) {
	delete_user_meta( $uid, 'bang_email_code_hash' );
	delete_user_meta( $uid, 'bang_email_code_exp' );
	delete_user_meta( $uid, 'bang_email_code_tries' );
}

/**
 * 发送验证码：POST /wp-json/bang/v1/email-code/send
 * 登录用户才能调用；验证码发到当前账号邮箱。
 */
function bang_email_code_send() {
	$uid = get_current_user_id();
	if ( ! $uid ) {
		return new WP_Error( 'not_logged_in', '请先登录', array( 'status' => 401 ) );
	}
	$user = get_userdata( $uid );
	if ( ! $user || ! is_email( $user->user_email ) ) {
		return new WP_Error( 'no_email', '当前账号没有可用邮箱，请联系站长', array( 'status' => 400 ) );
	}

	/* 重发限流 */
	$sent = (int) get_user_meta( $uid, 'bang_email_sent_at', true );
	$now  = time();
	if ( $sent && $now - $sent < bang_email_code_interval() ) {
		$wait = bang_email_code_interval() - ( $now - $sent );
		return new WP_Error( 'too_fast', '发送太频繁，请 ' . $wait . ' 秒后再试', array( 'status' => 429 ) );
	}

	$code = (string) wp_rand( 100000, 999999 );
	update_user_meta( $uid, 'bang_email_code_hash', bang_email_code_hash( $code, $uid ) );
	update_user_meta( $uid, 'bang_email_code_exp', $now + bang_email_code_ttl() );
	delete_user_meta( $uid, 'bang_email_code_tries' );
	update_user_meta( $uid, 'bang_email_sent_at', $now );

	$site    = get_bloginfo( 'name' );
	$subject = '[' . $site . '] 邮箱验证码';
	$message = "你正在修改 {$site} 的账号资料，验证码为：\n\n{$code}\n\n10 分钟内有效。若非本人操作，请忽略此邮件。\n";
	$ok      = wp_mail( $user->user_email, $subject, $message, array( 'Content-Type: text/plain; charset=UTF-8' ) );
	if ( ! $ok ) {
		/* 发信失败：清掉本次验证码，避免"发了但收不到还占着重发冷却" */
		delete_user_meta( $uid, 'bang_email_code_hash' );
		delete_user_meta( $uid, 'bang_email_code_exp' );
		delete_user_meta( $uid, 'bang_email_sent_at' );
		return new WP_Error( 'mail_failed', '验证码邮件发送失败，请检查站点邮件配置或稍后再试', array( 'status' => 500 ) );
	}
	return rest_ensure_response( array( 'ok' => 1, 'message' => '验证码已发送到 ' . $user->user_email ) );
}

/**
 * 校验验证码：成功返回 true，失败返回错误文案（供 bang_auth_fail 直接用）。
 */
function bang_email_code_check( $uid, $code ) {
	$hash = (string) get_user_meta( $uid, 'bang_email_code_hash', true );
	$exp  = (int) get_user_meta( $uid, 'bang_email_code_exp', true );
	if ( ! $hash || ! $exp ) {
		return '请先获取邮箱验证码';
	}
	if ( time() > $exp ) {
		bang_email_code_clear( $uid );
		return '验证码已过期，请重新获取';
	}
	$code = preg_replace( '/\D/', '', (string) $code );
	if ( 6 !== strlen( $code ) || ! hash_equals( $hash, bang_email_code_hash( $code, $uid ) ) ) {
		$tries = (int) get_user_meta( $uid, 'bang_email_code_tries', true ) + 1;
		update_user_meta( $uid, 'bang_email_code_tries', $tries );
		if ( $tries >= bang_email_code_max_tries() ) {
			bang_email_code_clear( $uid );
			return '验证码错误次数过多，请重新获取';
		}
		return '验证码不正确';
	}
	bang_email_code_clear( $uid );
	return true;
}

/** 注册发码 REST 路由 */
function bang_email_code_rest_route() {
	register_rest_route(
		'bang/v1',
		'/email-code/send',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_email_code_send',
			'permission_callback' => function () {
				return is_user_logged_in();
			},
		)
	);
}
add_action( 'rest_api_init', 'bang_email_code_rest_route' );

/* ============================================================
   找回密码（游客态）：账号 → 邮箱验证码 → 重置密码
   —— 独立 meta key，不与「改资料」那套共用；
      发码接口对不存在的账号也返回统一提示，防账号枚举；按 IP 限流。
   ============================================================ */

/** 找回密码：账号对应的用户（用户名或邮箱） */
function bang_lost_find_user( $account ) {
	$account = trim( (string) $account );
	if ( '' === $account ) {
		return false;
	}
	if ( is_email( $account ) ) {
		return get_user_by( 'email', $account );
	}
	return get_user_by( 'login', sanitize_user( $account ) );
}

/** 找回密码 IP 标识（取 forwarded-for 首个，非可信环境仅作限流用） */
function bang_lost_ip() {
	$ip = '';
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$parts = explode( ',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$ip    = trim( $parts[0] );
	}
	if ( ! $ip && ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = (string) $_SERVER['REMOTE_ADDR']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	}
	return $ip ? substr( md5( $ip ), 0, 16 ) : 'unknown';
}

/** 找回密码：发码 REST（公开接口，返回统一提示防枚举） */
function bang_lost_send() {
	$account = isset( $_POST['account'] ) ? (string) wp_unslash( $_POST['account'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	/* IP 限流：60 秒内只发一次（用 transient，不依赖登录） */
	$ip_key = 'bang_lost_ip_' . bang_lost_ip();
	if ( get_transient( $ip_key ) ) {
		return new WP_Error( 'too_fast', '发送太频繁，请 60 秒后再试', array( 'status' => 429 ) );
	}

	$user = bang_lost_find_user( $account );
	/* 账号不存在 / 没邮箱：也返回成功，避免泄露账号是否存在 */
	if ( $user && is_email( $user->user_email ) ) {
		$uid = (int) $user->ID;
		$now = time();

		/* 同一账号 60 秒冷却（双保险） */
		$sent = (int) get_user_meta( $uid, 'bang_lost_sent_at', true );
		if ( $sent && $now - $sent < bang_email_code_interval() ) {
			return new WP_Error( 'too_fast', '发送太频繁，请 60 秒后再试', array( 'status' => 429 ) );
		}

		$code = (string) wp_rand( 100000, 999999 );
		update_user_meta( $uid, 'bang_lost_hash', bang_email_code_hash( $code, $uid ) );
		update_user_meta( $uid, 'bang_lost_exp', $now + bang_email_code_ttl() );
		delete_user_meta( $uid, 'bang_lost_tries' );
		update_user_meta( $uid, 'bang_lost_sent_at', $now );

		$site    = get_bloginfo( 'name' );
		$subject = '[' . $site . '] 找回密码验证码';
		$message = "有人正在为 {$site} 的账号 {$user->user_login} 重置密码，验证码为：\n\n{$code}\n\n10 分钟内有效。若非本人操作，请忽略此邮件。\n";
		wp_mail( $user->user_email, $subject, $message, array( 'Content-Type: text/plain; charset=UTF-8' ) );
	}

	set_transient( $ip_key, 1, bang_email_code_interval() );
	return rest_ensure_response( array( 'ok' => 1, 'message' => '如果该账号存在，验证码已发送到其绑定邮箱，请查收。' ) );
}

/** 找回密码：校验验证码（复用通用 hash 逻辑，独立 meta） */
function bang_lost_code_check( $uid, $code ) {
	$hash = (string) get_user_meta( $uid, 'bang_lost_hash', true );
	$exp  = (int) get_user_meta( $uid, 'bang_lost_exp', true );
	if ( ! $hash || ! $exp ) {
		return '请先获取验证码';
	}
	if ( time() > $exp ) {
		delete_user_meta( $uid, 'bang_lost_hash' );
		delete_user_meta( $uid, 'bang_lost_exp' );
		return '验证码已过期，请重新获取';
	}
	$code = preg_replace( '/\D/', '', (string) $code );
	if ( 6 !== strlen( $code ) || ! hash_equals( $hash, bang_email_code_hash( $code, $uid ) ) ) {
		$tries = (int) get_user_meta( $uid, 'bang_lost_tries', true ) + 1;
		update_user_meta( $uid, 'bang_lost_tries', $tries );
		if ( $tries >= bang_email_code_max_tries() ) {
			delete_user_meta( $uid, 'bang_lost_hash' );
			delete_user_meta( $uid, 'bang_lost_exp' );
			return '验证码错误次数过多，请重新获取';
		}
		return '验证码不正确';
	}
	return true;
}

/** 找回密码：重置 REST */
function bang_lost_reset() {
	$account = isset( $_POST['account'] ) ? (string) wp_unslash( $_POST['account'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$code    = isset( $_POST['code'] ) ? trim( (string) wp_unslash( $_POST['code'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$pass1   = isset( $_POST['pass1'] ) ? (string) wp_unslash( $_POST['pass1'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$pass2   = isset( $_POST['pass2'] ) ? (string) wp_unslash( $_POST['pass2'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	$user = bang_lost_find_user( $account );
	if ( ! $user ) {
		return new WP_Error( 'bad_account', '账号不存在或验证码无效', array( 'status' => 400 ) );
	}
	if ( '' === $code ) {
		return new WP_Error( 'code_empty', '请填写验证码', array( 'status' => 400 ) );
	}
	$check = bang_lost_code_check( (int) $user->ID, $code );
	if ( true !== $check ) {
		return new WP_Error( 'code_bad', $check, array( 'status' => 400 ) );
	}
	if ( $pass1 !== $pass2 ) {
		return new WP_Error( 'pwd_match', '两次输入的新密码不一致', array( 'status' => 400 ) );
	}
	$plen = function_exists( 'mb_strlen' ) ? mb_strlen( $pass1, 'UTF-8' ) : strlen( $pass1 );
	if ( $plen < 6 ) {
		return new WP_Error( 'pwd_short', '新密码至少 6 位', array( 'status' => 400 ) );
	}

	wp_set_password( $pass1, (int) $user->ID );
	/* 重置成功：清掉验证码状态，并要求重新登录 */
	delete_user_meta( $user->ID, 'bang_lost_hash' );
	delete_user_meta( $user->ID, 'bang_lost_exp' );
	delete_user_meta( $user->ID, 'bang_lost_tries' );
	delete_user_meta( $user->ID, 'bang_lost_sent_at' );
	return rest_ensure_response( array( 'ok' => 1, 'message' => '密码已重置，请用新密码登录。' ) );
}

/** 找回密码 REST 路由（公开接口） */
function bang_lost_rest_route() {
	register_rest_route(
		'bang/v1',
		'/lost/send',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_lost_send',
			'permission_callback' => '__return_true',
			'args'                => array(
				'account' => array( 'required' => true ),
			),
		)
	);
	register_rest_route(
		'bang/v1',
		'/lost/reset',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_lost_reset',
			'permission_callback' => '__return_true',
			'args'                => array(
				'account' => array( 'required' => true ),
				'code'    => array( 'required' => true ),
				'pass1'   => array( 'required' => true ),
				'pass2'   => array( 'required' => true ),
			),
		)
	);
}
add_action( 'rest_api_init', 'bang_lost_rest_route' );

/**
 * 保存账号设置：昵称 / 邮箱 / 密码（+ 可选头像）
 *
 * 登录名 user_login 只读展示，绝不写入 —— 它和 display_name 是两回事：
 * user_login 用于登录、建号后不可改；display_name / nickname 才是站内显示名。
 */
function bang_handle_profile() {
	check_admin_referer( 'bang_profile' );

	if ( ! is_user_logged_in() ) {
		bang_auth_fail( 'login', bang_settings_url() );
	}

	$uid  = get_current_user_id();
	$user = get_userdata( $uid );
	if ( ! $user ) {
		bang_auth_fail( 'login', bang_settings_url() );
	}

	$name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
	$name = trim( preg_replace( '/\s+/u', ' ', $name ) );
	$len  = function_exists( 'mb_strlen' ) ? mb_strlen( $name, 'UTF-8' ) : strlen( $name );
	if ( $len < 2 || $len > 24 ) {
		bang_auth_fail( 'nickname', bang_settings_url() );
	}

	/* 违禁词提示（只提示不拦提交）：昵称会显示给别人看，命中就退回让用户改 */
	if ( function_exists( 'bang_moderation_handle' ) ) {
		$bang_mod = bang_moderation_handle( $name, '昵称' );
		if ( $bang_mod && ! $bang_mod['ok'] ) {
			bang_auth_fail( 'moderation', bang_settings_url(), function_exists( 'bang_moderation_hint' ) ? bang_moderation_hint( '包含违禁词，请修改后再试。', $bang_mod['words'] ) : '包含违禁词，请修改后再试。' );
		}
	}

	$args = array(
		'ID'           => $uid,
		'display_name' => $name,
		'nickname'     => $name,
	);

	/* 邮箱：填了才校验，且和当前一致时不写，避免无意义地触发 WP 邮件 */
	$email = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
	if ( '' !== $email ) {
		if ( ! is_email( $email ) ) {
			bang_auth_fail( 'email_invalid', bang_settings_url() );
		}
		$owner = email_exists( $email );
		if ( $owner && (int) $owner !== $uid ) {
			bang_auth_fail( 'email_used', bang_settings_url() );
		}
		if ( 0 !== strcasecmp( $email, (string) $user->user_email ) ) {
			$args['user_email'] = $email;
		}
	}

	/* 密码：三个框全空 = 不改；只要动了就校验当前密码 + 两次一致 + 长度 */
	$cur = isset( $_POST['current_password'] ) ? (string) wp_unslash( $_POST['current_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$new = isset( $_POST['new_password'] ) ? (string) wp_unslash( $_POST['new_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$rep = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$pass_changed = false;
	if ( '' !== $cur || '' !== $new || '' !== $rep ) {
		if ( ! wp_check_password( $cur, $user->user_pass, $uid ) ) {
			bang_auth_fail( 'pwd_current', bang_settings_url() );
		}
		if ( $new !== $rep ) {
			bang_auth_fail( 'pwd_match', bang_settings_url() );
		}
		$plen = function_exists( 'mb_strlen' ) ? mb_strlen( $new, 'UTF-8' ) : strlen( $new );
		if ( $plen < 6 ) {
			bang_auth_fail( 'pwd_short', bang_settings_url() );
		}
		$args['user_pass'] = $new;
		$pass_changed      = true;
	}

	/* 邮箱 / 密码任一要改，必须先通过邮箱验证码（先把改动识别出来，再统一校验） */
	$email_changed = isset( $args['user_email'] );
	if ( $email_changed || $pass_changed ) {
		$code = isset( $_POST['bang_email_code'] ) ? trim( (string) wp_unslash( $_POST['bang_email_code'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( '' === $code ) {
			bang_auth_fail( 'email_code_empty', bang_settings_url() );
		}
		$check = bang_email_code_check( $uid, $code );
		if ( true !== $check ) {
			bang_auth_fail( 'email_code_bad', bang_settings_url(), $check );
		}
	}

	/* 违禁词提示（只提示不拦提交）：昵称会显示给别人看，命中就退回让用户改 */
	if ( function_exists( 'bang_moderation_handle' ) ) {
		$bang_mod = bang_moderation_handle( $name, '昵称' );
		if ( $bang_mod && ! $bang_mod['ok'] ) {
			bang_auth_fail( 'moderation', bang_settings_url(), function_exists( 'bang_moderation_hint' ) ? bang_moderation_hint( '包含违禁词，请修改后再试。', $bang_mod['words'] ) : '包含违禁词，请修改后再试。' );
		}
	}

	$file = isset( $_FILES['bang_avatar'] ) ? $_FILES['bang_avatar'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( $file && ! empty( $file['name'] ) && UPLOAD_ERR_OK === (int) $file['error'] ) {
		if ( (int) $file['size'] > 2 * MB_IN_BYTES ) {
			bang_auth_fail( 'avatar_big', bang_settings_url() );
		}
		/* 不信任浏览器给的 type，用真实图像信息判定 */
		$info = @getimagesize( $file['tmp_name'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$ok   = array( 'image/jpeg', 'image/png', 'image/webp' );
		if ( ! $info || empty( $info['mime'] ) || ! in_array( $info['mime'], $ok, true ) ) {
			bang_auth_fail( 'avatar_type', bang_settings_url() );
		}

		/* 本站前台允许登录用户换头像（订阅者默认没有 upload_files）。为不破坏功能，
		   只在本次上传调用期间临时放行该能力：不写数据库、不给后台媒体库权限。
		   表单本身有 nonce + 登录校验，不会被当成通用上传端点滥用。 */
		$grant_upload = null;
		if ( ! current_user_can( 'upload_files' ) ) {
			$grant_upload = function ( $caps ) use ( $uid ) {
				if ( get_current_user_id() === $uid ) {
					$caps['upload_files'] = true;
				}
				return $caps;
			};
			add_filter( 'user_has_cap', $grant_upload, 10, 1 );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		/* 老版本 WP 默认不放行 webp，临时加上 */
		$allow_webp = function ( $mimes ) {
			$mimes['webp'] = 'image/webp';
			return $mimes;
		};
		add_filter( 'upload_mimes', $allow_webp );

		$aid = media_handle_upload(
			'bang_avatar',
			0,
			array( 'post_author' => $uid ),
			array( 'test_form' => false )
		);
		remove_filter( 'upload_mimes', $allow_webp );
		if ( $grant_upload ) {
			remove_filter( 'user_has_cap', $grant_upload, 10 );
		}

		if ( is_wp_error( $aid ) ) {
			bang_auth_fail( 'avatar', bang_settings_url() );
		}

		/* 换头像就删掉旧附件，媒体库不至于越堆越多 */
		$old = (int) get_user_meta( $uid, 'bang_avatar_id', true );
		if ( $old && $old !== (int) $aid ) {
			wp_delete_attachment( $old, true );
		}
		update_user_meta( $uid, 'bang_avatar_id', (int) $aid );
		/* 旧字段是 URL，清掉，免得它盖住新图 */
		delete_user_meta( $uid, 'bang_avatar' );
	}

	$res = wp_update_user( $args );
	if ( is_wp_error( $res ) ) {
		bang_auth_fail( 'profile', bang_settings_url() );
	}

	/* 改密码会让旧 auth cookie 失效，当前这台重新签发，避免保存完被登出 */
	if ( $pass_changed ) {
		wp_set_auth_cookie( $uid, true );
	}

	wp_safe_redirect( add_query_arg( 'bang_msg', 'profile', bang_settings_url() ) );
	exit;
}
add_action( 'admin_post_bang_profile', 'bang_handle_profile' );

/**
 * 注销账号：密码 + 「注销」二字双确认，管理员账号站内不可注销
 */
function bang_handle_delete_account() {
	check_admin_referer( 'bang_delete_account' );

	if ( ! is_user_logged_in() ) {
		bang_auth_fail( 'login', bang_settings_url() );
	}

	$uid  = get_current_user_id();
	$user = get_userdata( $uid );
	if ( ! $user ) {
		bang_auth_fail( 'login', bang_settings_url() );
	}
	if ( user_can( $uid, 'manage_options' ) ) {
		bang_auth_fail( 'admin_protected', bang_settings_url() );
	}

	$pwd = isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '';
	if ( '' === $pwd || ! wp_check_password( $pwd, $user->user_pass, $uid ) ) {
		bang_auth_fail( 'badpass', bang_settings_url() );
	}
	$confirm = isset( $_POST['confirm'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['confirm'] ) ) ) : '';
	if ( '注销' !== $confirm ) {
		bang_auth_fail( 'confirm', bang_settings_url() );
	}

	/* 内容不跟着人消失：文章移交给管理员再删账号 */
	$reassign = 0;
	$admins   = get_users(
		array(
			'role'    => 'administrator',
			'number'  => 1,
			'fields'  => 'ID',
			'exclude' => array( $uid ),
		)
	);
	if ( $admins ) {
		$reassign = (int) $admins[0];
	}

	require_once ABSPATH . 'wp-admin/includes/user.php';
	wp_delete_user( $uid, $reassign );

	wp_logout();
	wp_safe_redirect( add_query_arg( 'bang_msg', 'deleted', home_url( '/' ) ) );
	exit;
}
add_action( 'admin_post_bang_delete_account', 'bang_handle_delete_account' );
