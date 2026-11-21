<?php
/**
 * 快捷登录：QQ 官方 OAuth2 + 彩虹聚合登录（接口地址后台可自定义）
 *
 * 两条通道共用同一套 state / 回调 / 建号逻辑：
 *  - qq      ：直连 connect.qq.com，用 qq_appid / qq_appkey
 *  - rainbow ：走后台 rainbow_api 指定的接口（留空回退 https://u.naigou.cn/connect.php），
 *              用 rainbow_appid / rainbow_appkey，platform 取彩虹的 type 值（qq/wx/sina/douyin/...）
 *
 * 密钥只在服务端使用，绝不下发前端（同 geetest_key / smtp_pass 约定）。
 * 回调地址统一为 REST：/wp-json/bang/v1/quick-login/callback
 * 需在 QQ 互联后台 / 彩虹聚合后台把该地址登记为回调域名。
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 回调地址（QQ 官方与彩虹共用；两处平台后台都要填这个）
 */
function bang_ql_callback_url() {
	return rest_url( 'bang/v1/quick-login/callback' );
}

/**
 * 彩虹聚合登录接口地址（后台 rainbow_api 自定义，留空回退官方默认）。
 * 自建彩虹 / 第三方兼容服务只要实现同样的 act=login / act=callback 协议即可接入。
 */
function bang_ql_rainbow_api() {
	$api = trim( (string) bang_opt( 'rainbow_api' ) );
	if ( '' === $api ) {
		$api = 'https://u.naigou.cn/connect.php';
	}
	return untrailingslashit( esc_url_raw( $api ) );
}

/**
 * 彩虹聚合支持的平台白名单：type => array( 中文名, 图标文件名 )
 * 图标留空时前端只显示文字。
 */
function bang_ql_platforms() {
	return array(
		'qq'        => array( 'QQ', 'qq' ),
		'wx'        => array( '微信', 'wechat' ),
		'sina'      => array( '微博', 'weibo' ),
		'douyin'    => array( '抖音', 'douyin' ),
		'github'    => array( 'GitHub', 'github' ),
		'bilibili'  => array( '哔哩哔哩', 'bilibili' ),
		'alipay'    => array( '支付宝', '' ),
		'baidu'     => array( '百度', '' ),
		'huawei'    => array( '华为', '' ),
		'xiaomi'    => array( '小米', '' ),
		'google'    => array( 'Google', '' ),
		'microsoft' => array( '微软', '' ),
		'facebook'  => array( 'Facebook', '' ),
		'twitter'   => array( 'Twitter', '' ),
		'dingtalk'  => array( '钉钉', '' ),
		'gitee'     => array( 'Gitee', '' ),
	);
}

/**
 * 平台勾选框的 choices：type => 中文名（供后台 checks 字段用）。
 */
function bang_ql_platform_choices() {
	$out = array();
	foreach ( bang_ql_platforms() as $type => $info ) {
		$out[ $type ] = $info[0];
	}
	return $out;
}

/**
 * 彩虹后台启用的平台（逗号分隔 → 数组，按白名单过滤）
 */
function bang_ql_rainbow_enabled_platforms() {
	$all  = bang_ql_platforms();
	$raw  = (string) bang_opt( 'rainbow_plats' );
	$list = array();
	foreach ( explode( ',', $raw ) as $p ) {
		$p = trim( strtolower( $p ) );
		if ( $p && isset( $all[ $p ] ) ) {
			$list[] = $p;
		}
	}
	return $list;
}

/**
 * 当前可用的快捷登录方式：返回给前端渲染按钮，也是 start 接口的白名单。
 * 每项：key（前端 data-bang-quick-login 值）/ provider / platform / label / icon。
 */
function bang_ql_methods() {
	$methods = array();
	if ( ! bang_opt( 'quicklogin_on' ) ) {
		return $methods;
	}
	// QQ 官方
	if ( bang_opt( 'qq_on' ) && bang_opt( 'qq_appid' ) && bang_opt( 'qq_appkey' ) ) {
		$methods[] = array(
			'key'      => 'qq',
			'provider' => 'qq',
			'platform' => '',
			'label'    => 'QQ 官方',
			'icon'     => 'qq',
		);
	}
	// 彩虹聚合
	if ( bang_opt( 'rainbow_on' ) && bang_opt( 'rainbow_appid' ) && bang_opt( 'rainbow_appkey' ) ) {
		$all = bang_ql_platforms();
		foreach ( bang_ql_rainbow_enabled_platforms() as $p ) {
			$methods[] = array(
				'key'      => 'rainbow:' . $p,
				'provider' => 'rainbow',
				'platform' => $p,
				'label'    => $all[ $p ][0],
				'icon'     => $all[ $p ][1],
			);
		}
	}
	return $methods;
}

/**
 * 前端配置注入：只下发可用按钮与 REST 地址，不含任何密钥。
 */
function bang_ql_config( $config ) {
	$methods = bang_ql_methods();
	$config['quickLogin'] = array(
		'on'        => ! empty( $methods ) ? 1 : 0,
		'methods'   => $methods,
		'startBase' => rest_url( 'bang/v1/quick-login/start' ),
	);
	return $config;
}
add_filter( 'bang_user_config', 'bang_ql_config' );

/**
 * 注册 REST 路由：start 拿跳转地址，callback 处理第三方回跳。
 */
function bang_ql_rest_route() {
	register_rest_route(
		'bang/v1',
		'/quick-login/start',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_ql_start',
			'permission_callback' => '__return_true',
			'args'                => array(
				'key' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
	register_rest_route(
		'bang/v1',
		'/quick-login/callback',
		array(
			'methods'             => array( 'GET', 'POST' ),
			'callback'            => 'bang_ql_callback',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bang_ql_rest_route' );

/**
 * 生成并保存一次性的 state（transient + HttpOnly Cookie 双存）。
 */
function bang_ql_issue_state( $provider, $platform, $redirect ) {
	$state = wp_generate_password( 32, false, false );
	$data  = array(
		'provider' => $provider,
		'platform' => $platform,
		'redirect' => $redirect,
		// start 是带 X-WP-Nonce 的 fetch，能拿到真实登录态；回调是顶层跳转、
		// WP 会把当前用户重置为 0，所以绑定判断必须在这里先存下来。
		'uid'      => get_current_user_id(),
		'time'     => time(),
	);
	set_transient( 'bang_ql_s_' . $state, $data, 10 * MINUTE_IN_SECONDS );
	if ( ! headers_sent() ) {
		$secure = function_exists( 'is_ssl' ) && is_ssl();
		if ( PHP_VERSION_ID >= 70300 ) {
			setcookie(
				'bang_ql_state',
				$state,
				array(
					'expires'  => time() + 600,
					'path'     => '/',
					'secure'   => $secure,
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		} else {
			setcookie( 'bang_ql_state', $state, time() + 600, '/', '', $secure, true );
		}
	}
	return $state;
}

/**
 * start：按 key 返回第三方授权跳转地址。
 */
function bang_ql_start( $request ) {
	$key = (string) $request->get_param( 'key' );
	$hit = null;
	foreach ( bang_ql_methods() as $m ) {
		if ( $m['key'] === $key ) {
			$hit = $m;
			break;
		}
	}
	if ( ! $hit ) {
		return new WP_Error( 'bang_ql_off', '该快捷登录方式未开启。', array( 'status' => 400 ) );
	}

	$raw      = (string) $request->get_param( 'redirect_to' );
	$redirect = $raw ? wp_validate_redirect( esc_url_raw( $raw ), bang_user_url() ) : bang_user_redirect();
	$state    = bang_ql_issue_state( $hit['provider'], $hit['platform'], $redirect );
	$cb       = bang_ql_callback_url();

	if ( 'qq' === $hit['provider'] ) {
		// QQ 官方 OAuth2：授权页带 state，回调原样带回。
		$url = add_query_arg(
			array(
				'response_type' => 'code',
				'client_id'     => bang_opt( 'qq_appid' ),
				'redirect_uri'  => $cb,
				'state'         => $state,
				'scope'         => 'get_user_info',
				'display'       => 'pc',
			),
			'https://connect.qq.com/oauth2.0/authorize'
		);
	} else {
		// 彩虹聚合：Step1 服务端换跳转地址（appkey 只在服务端使用）。
		$login = add_query_arg(
			array(
				'act'          => 'login',
				'appid'        => bang_opt( 'rainbow_appid' ),
				'appkey'       => bang_opt( 'rainbow_appkey' ),
				'type'         => $hit['platform'],
				'redirect_uri' => $cb,
			),
			bang_ql_rainbow_api()
		);
		$body = bang_ql_http_get( $login );
		$json = $body ? json_decode( $body, true ) : null;
		if ( ! is_array( $json ) || empty( $json['code'] ) || 0 !== (int) $json['code'] || empty( $json['url'] ) ) {
			return new WP_Error( 'bang_ql_step1', '获取彩虹登录地址失败，请稍后再试。', array( 'status' => 502 ) );
		}
		$url = (string) $json['url'];
	}

	return array( 'url' => esc_url_raw( $url ) );
}

/**
 * 简单的 GET 请求封装（超时 12s）。
 */
function bang_ql_http_get( $url ) {
	$res = wp_remote_get(
		$url,
		array(
			'timeout' => 12,
			'headers' => array( 'Accept' => 'application/json' ),
		)
	);
	if ( is_wp_error( $res ) ) {
		return null;
	}
	$code = (int) wp_remote_retrieve_response_code( $res );
	if ( $code < 200 || $code >= 300 ) {
		return null;
	}
	return wp_remote_retrieve_body( $res );
}

/**
 * callback：校验 state → 用 code 换身份 → 找/建用户 → 建会话 → 回跳。
 */
function bang_ql_callback( $request ) {
	$state = (string) $request->get_param( 'state' );
	if ( '' === $state && isset( $_COOKIE['bang_ql_state'] ) ) {
		$state = sanitize_text_field( wp_unslash( $_COOKIE['bang_ql_state'] ) );
	}
	$state = sanitize_text_field( $state );
	$saved = $state ? get_transient( 'bang_ql_s_' . $state ) : false;
	// 校验失败或已消费（用完即删，防重放）
	if ( ! is_array( $saved ) || empty( $saved['provider'] ) ) {
		bang_ql_redirect_error( 'ql_state' );
	}
	delete_transient( 'bang_ql_s_' . $state );
	if ( ! headers_sent() ) {
		setcookie( 'bang_ql_state', '', time() - 3600, '/', '', function_exists( 'is_ssl' ) && is_ssl(), true );
	}

	$code     = sanitize_text_field( (string) $request->get_param( 'code' ) );
	$provider = $saved['provider'];
	$platform = isset( $saved['platform'] ) ? $saved['platform'] : '';

	if ( '' === $code ) {
		bang_ql_redirect_error( 'ql_code' );
	}

	$identity = ( 'qq' === $provider ) ? bang_ql_identity_qq( $code ) : bang_ql_identity_rainbow( $code, $platform );
	if ( ! is_array( $identity ) || empty( $identity['uid'] ) ) {
		bang_ql_redirect_error( 'ql_info' );
	}

	$meta_key = 'qq' === $provider ? 'bang_ql_qq_uid' : 'bang_ql_rb_' . sanitize_key( $platform ) . '_uid';
	$third_uid = (string) $identity['uid'];
	$user      = bang_ql_find_user( $meta_key, $third_uid );

	$start_uid = isset( $saved['uid'] ) ? (int) $saved['uid'] : 0;
	if ( $start_uid ) {
		// 发起登录时已登录：把该第三方账号绑定到当前账号，不切换登录态。
		/* 该 UID 已属于别的账号时拒绝绑定，避免同一个第三方账号被绑到多个 WP 账号 */
		if ( $user && (int) $user->ID !== $start_uid ) {
			bang_ql_redirect_error( 'ql_bound' );
		}
		update_user_meta( $start_uid, $meta_key, $third_uid );
		$uid = $start_uid;
	} elseif ( $user ) {
		$uid = $user->ID;
	} else {
		// 未注册：自动建号，昵称/头像取第三方资料。
		$base  = 'ql_' . substr( md5( $meta_key . '|' . $third_uid ), 0, 12 );
		$login = $base;
		$i     = 1;
		while ( username_exists( $login ) ) {
			$login = $base . '_' . $i;
			++$i;
		}
		$nick = isset( $identity['nickname'] ) ? sanitize_text_field( wp_strip_all_tags( $identity['nickname'] ) ) : '';
		if ( '' === $nick ) {
			$nick = '用户' . substr( $third_uid, 0, 6 );
		}
		$uid = wp_create_user( $login, wp_generate_password( 32, true, true ), $login . '@quick.local' );
		if ( is_wp_error( $uid ) ) {
			bang_ql_redirect_error( 'ql_user' );
		}
		wp_update_user(
			array(
				'ID'           => $uid,
				'display_name' => $nick,
				'nickname'     => $nick,
			)
		);
		update_user_meta( $uid, $meta_key, $third_uid );
		if ( ! empty( $identity['avatar'] ) ) {
			$avatar = bang_safe_comment_site( $identity['avatar'] );
			if ( $avatar ) {
				update_user_meta( $uid, 'bang_avatar', esc_url_raw( $avatar ) );
			}
		}
	}

	wp_set_current_user( $uid );
	// 回调是第三方顶层跳转、不带 REST nonce，is_user_logged_in() 在此不可靠；
	// 无论「已登录用户绑定」还是「新用户首次登录」都主动发一次登录票据。
	// 绑定场景 uid 就是当前账号，重发同用户票据不会切换登录态。
	wp_set_auth_cookie( $uid, true, is_ssl() );
	if ( function_exists( 'bang_device_issue' ) ) {
		bang_device_issue( $uid );
	}

	$raw      = isset( $saved['redirect'] ) ? (string) $saved['redirect'] : '';
	$redirect = $raw ? wp_validate_redirect( esc_url_raw( $raw ), bang_user_url() ) : bang_user_redirect();
	wp_safe_redirect( $redirect );
	exit;
}

/**
 * QQ 官方：code → access_token → openid → 用户资料
 */
function bang_ql_identity_qq( $code ) {
	$appid  = bang_opt( 'qq_appid' );
	$appkey = bang_opt( 'qq_appkey' );
	$cb     = bang_ql_callback_url();

	$token_body = bang_ql_http_get(
		add_query_arg(
			array(
				'grant_type'    => 'authorization_code',
				'client_id'     => $appid,
				'client_secret' => $appkey,
				'code'          => $code,
				'redirect_uri'  => $cb,
			),
			'https://graph.qq.com/oauth2.0/token'
		)
	);
	if ( ! $token_body ) {
		return null;
	}
	parse_str( $token_body, $token );
	if ( empty( $token['access_token'] ) ) {
		return null;
	}
	$access = $token['access_token'];

	$me_body = bang_ql_http_get(
		add_query_arg( array( 'access_token' => $access, 'fmt' => 'json' ), 'https://graph.qq.com/oauth2.0/me' )
	);
	if ( ! $me_body ) {
		return null;
	}
	$me = json_decode( $me_body, true );
	if ( ! is_array( $me ) ) {
		// 兼容 callback( {"openid":"..."} ); 的旧格式
		if ( preg_match( '/\{(.*?)\}/s', $me_body, $m ) ) {
			$me = json_decode( '{' . $m[1] . '}', true );
		}
	}
	if ( ! is_array( $me ) || empty( $me['openid'] ) ) {
		return null;
	}

	$info_body = bang_ql_http_get(
		add_query_arg(
			array(
				'access_token'        => $access,
				'oauth_consumer_key'  => $appid,
				'openid'              => $me['openid'],
			),
			'https://graph.qq.com/user/get_user_info'
		)
	);
	if ( ! $info_body ) {
		return null;
	}
	$info = json_decode( $info_body, true );
	if ( ! is_array( $info ) || ( isset( $info['ret'] ) && 0 !== (int) $info['ret'] ) ) {
		return null;
	}
	$avatar = '';
	if ( ! empty( $info['figureurl_qq_2'] ) ) {
		$avatar = $info['figureurl_qq_2'];
	} elseif ( ! empty( $info['figureurl_qq_1'] ) ) {
		$avatar = $info['figureurl_qq_1'];
	}
	return array(
		'uid'      => $me['openid'],
		'nickname' => isset( $info['nickname'] ) ? $info['nickname'] : '',
		'avatar'   => $avatar,
	);
}

/**
 * 彩虹聚合：Step4 用 code 换用户信息。
 */
function bang_ql_identity_rainbow( $code, $platform ) {
	$body = bang_ql_http_get(
		add_query_arg(
			array(
				'act'    => 'callback',
				'appid'  => bang_opt( 'rainbow_appid' ),
				'appkey' => bang_opt( 'rainbow_appkey' ),
				'type'   => $platform,
				'code'   => $code,
			),
			bang_ql_rainbow_api()
		)
	);
	if ( ! $body ) {
		return null;
	}
	$json = json_decode( $body, true );
	if ( ! is_array( $json ) || empty( $json['code'] ) || 0 !== (int) $json['code'] || empty( $json['social_uid'] ) ) {
		return null;
	}
	return array(
		'uid'      => $json['social_uid'],
		'nickname' => isset( $json['nickname'] ) ? $json['nickname'] : '',
		'avatar'   => isset( $json['faceimg'] ) ? $json['faceimg'] : '',
	);
}

/**
 * 按第三方 uid 反查用户：meta 存的是明文，用精确匹配避免子串误命中后重复建号。
 */
function bang_ql_find_user( $meta_key, $uid ) {
	$query = new WP_User_Query(
		array(
			'meta_key'     => $meta_key,
			'meta_value'   => $uid,
			'meta_compare' => '=',
			'number'       => 5,
		)
	);
	foreach ( $query->get_results() as $user ) {
		if ( get_user_meta( $user->ID, $meta_key, true ) === $uid ) {
			return $user;
		}
	}
	return null;
}

/**
 * 快捷登录失败：带错误码回登录页，由登录卡片显示。
 */
function bang_ql_redirect_error( $code ) {
	// 用 bang_err：与登录/注册卡片的错误码通道一致（page-login / page-user 都读它）
	$url = add_query_arg( 'bang_err', sanitize_key( $code ), bang_auth_url() );
	wp_safe_redirect( $url );
	exit;
}

/**
 * 登录卡片渲染快捷登录按钮（无 id，整页与弹窗可共存）。
 */
function bang_ql_buttons_html() {
	$methods = bang_ql_methods();
	if ( ! $methods ) {
		return '';
	}
	$html  = '<div class="auth-quick">';
	$html .= '<p class="auth-quick-sep"><span>或使用快捷登录</span></p>';
	$html .= '<div class="auth-quick-list">';
	foreach ( $methods as $m ) {
		$tip = 'qq' === $m['provider'] ? 'QQ 官方登录' : ( $m['label'] . '快捷登录（彩虹聚合）' );
		$html .= '<button type="button" class="auth-quick-btn" data-bang-quick-login="' . esc_attr( $m['key'] ) . '"'
			. ' aria-label="' . esc_attr( $tip ) . '" title="' . esc_attr( $tip ) . '">';
		$html .= $m['icon'] ? bang_icon( $m['icon'], 18 ) : '<span class="auth-quick-txt">' . esc_html( substr( $m['label'], 0, 1 ) ) . '</span>';
		$html .= '<span>' . esc_html( $m['label'] ) . '</span></button>';
	}
	$html .= '</div></div>';
	return $html;
}
