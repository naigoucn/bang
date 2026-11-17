<?php
/**
 * 登录 / 注册页
 *
 * 一套内容两种形态，路径统一在 /login/：
 *   - 整页：直接访问或刷新 /login/，居中一张卡片；
 *   - 弹窗：从任意页面点「登录」就地弹出，同时把地址栏 pushState 到 /login/。
 * 所以弹窗里刷新会变成整页，整页也能直接被分享；两种形态共用同一份表单 HTML
 * （pk_auth_card_html），提交都走 admin-post.php。
 *
 * 用户中心（/user/）未登录时也复用这张卡片，老链接照常可用。
 * 注册表单带自写滑块，见 inc/captcha.php。
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 登录页地址
 *
 * @param string $mode login / register。
 */
function pk_auth_url( $mode = 'login' ) {
	$url = home_url( '/login/' );
	return ( 'register' === $mode ) ? add_query_arg( 'mode', 'register', $url ) : $url;
}

/**
 * 错误码 → 文案
 */
function pk_auth_errors() {
	return array(
		'login'    => '账号或密码不对，再试一次。',
		'login_locked' => '尝试次数过多，账号已临时锁定，请 15 分钟后再试。',
		'empty'    => '请填写账号和密码。',
		'closed'   => '本站暂未开放注册，请联系站长开通。',
		'fields'   => '用户名、邮箱、密码都要填。',
		'badname'  => '用户名只能用字母、数字、下划线、点、连字符和 @。',
		'bademail' => '邮箱格式不对。',
		'email_domain' => '该邮箱后缀不支持注册。',
		'weakpass' => '密码至少 6 位。',
		'mismatch' => '两次输入的密码不一致。',
		'exists'   => '这个用户名或邮箱已经被注册了。',
		'register' => '注册失败，稍后再试或联系站长。',
		'captcha'  => '请先完成滑块验证。',
		/* 0.13.6 个人中心：资料 / 注销 */
		'nickname' => '昵称要 2 ~ 24 个字。',
		'avatar_type' => '头像只支持 JPG / PNG / WebP。',
		'avatar_big'  => '头像不能超过 2MB。',
		'avatar'      => '头像上传失败，稍后再试。',
		'profile'     => '保存失败，稍后再试。',
		'badpass'     => '密码不对。',
		'confirm'     => '确认文字不对，请输入「注销」两个字。',
		'admin_protected' => '管理员账号不能在站内注销。',
		/* 违禁词提示（只提示不拦提交）：命中时文案里带上具体词，见 inc/comment-moderation.php */
		'moderation'  => '包含违禁词，请修改后再试。',
	);
}

/**
 * 错误码 → 文案（未知码返回空串）
 *
 * @param string $code 错误码。
 */
function pk_auth_error_text( $code ) {
	$map = pk_auth_errors();
	return isset( $map[ $code ] ) ? $map[ $code ] : '';
}

/**
 * 表单提交失败后该回哪个页面
 *
 * 表单里带着 pk_auth_from（/login/ 或 /user/），只认这两个，避免变成开放跳转。
 */
function pk_auth_from_url() {
	$raw = isset( $_POST['pk_auth_from'] ) ? wp_unslash( $_POST['pk_auth_from'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$url = $raw ? wp_validate_redirect( esc_url_raw( $raw ), '' ) : '';
	$dir = $url ? trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' ) : '';
	if ( 'login' === $dir ) {
		return pk_auth_url();
	}
	/* 账号设置表单在 /settings/ 提交，错误要回设置页而不是用户中心 */
	if ( 'settings' === $dir ) {
		return function_exists( 'pk_settings_url' ) ? pk_settings_url() : home_url( '/settings/' );
	}
	return pk_user_url();
}

/**
 * 登录 / 注册卡片
 *
 * 表单元素一律不用 id：整页与弹窗可能同时存在同一份 HTML（避免 id 冲突）。
 * tab 用 <a href> 保留无 JS 的整页跳转，有 JS 时拦截下来就地换面板。
 *
 * @param array $args mode / err / redirect / dialog / base / from。
 */
function pk_auth_card_html( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'mode'     => 'login',
			'err'      => '',
			'redirect' => '',
			'dialog'   => false,
			'base'     => '',
			'from'     => '',
		)
	);

	$mode   = ( 'register' === $args['mode'] ) ? 'register' : 'login';
	$post   = esc_url( admin_url( 'admin-post.php' ) );
	$canreg = (bool) get_option( 'users_can_register' );
	$err    = pk_auth_error_text( $args['err'] );
	$base   = $args['base'] ? $args['base'] : pk_auth_url();
	$from   = $args['from'] ? $args['from'] : $base;

	$url_login = remove_query_arg( 'mode', $base );
	$url_reg   = add_query_arg( 'mode', 'register', $base );

	$titles = array(
		'login'    => array( '欢迎回来', '登录后可以点赞、评论，换设备也能同步。' ),
		'register' => array( '创建账号', '只需用户名、邮箱和密码，注册后自动登录。' ),
	);

	$html  = '<div class="auth-card' . ( $args['dialog'] ? ' is-dialog' : '' ) . '" data-pk-auth-card'
		. ' data-t-login="' . esc_attr( $titles['login'][0] ) . '" data-t-register="' . esc_attr( $titles['register'][0] ) . '"'
		. ' data-s-login="' . esc_attr( $titles['login'][1] ) . '" data-s-register="' . esc_attr( $titles['register'][1] ) . '">';
	if ( $args['dialog'] ) {
		$html .= '<button class="auth-close" type="button" data-pk-auth-close aria-label="关闭">' . pk_icon( 'close', 18 ) . '</button>';
	}
	$html .= '<header class="auth-head">';
	$html .= '<p class="auth-mark">' . esc_html( get_bloginfo( 'name' ) ) . '</p>';
	$html .= '<h1 class="auth-title" data-pk-auth-title>' . esc_html( $titles[ $mode ][0] ) . '</h1>';
	$html .= '<p class="auth-sub" data-pk-auth-sub>' . esc_html( $titles[ $mode ][1] ) . '</p>';
	$html .= '</header>';

	$html .= '<nav class="user-tabs auth-tabs" role="tablist" aria-label="登录或注册">';
	$html .= '<a class="user-tab auth-tab' . ( 'login' === $mode ? ' is-active' : '' ) . '" href="' . esc_url( $url_login ) . '"'
		. ' role="tab" aria-selected="' . ( 'login' === $mode ? 'true' : 'false' ) . '" data-pk-auth-tab="login">登录</a>';
	$html .= '<a class="user-tab auth-tab' . ( 'register' === $mode ? ' is-active' : '' ) . '" href="' . esc_url( $url_reg ) . '"'
		. ' role="tab" aria-selected="' . ( 'register' === $mode ? 'true' : 'false' ) . '" data-pk-auth-tab="register">注册</a>';
	$html .= '<span class="auth-ink" aria-hidden="true"></span>';
	$html .= '</nav>';

	$html .= '<p class="auth-err"' . ( $err ? '' : ' hidden' ) . ' data-pk-auth-err role="alert">' . esc_html( $err ) . '</p>';

	/* ---------- 登录 ---------- */
	$html .= '<div class="auth-panel" data-pk-auth-panel="login"' . ( 'login' === $mode ? '' : ' hidden' ) . '>';
	$html .= '<form class="user-form auth-form" method="post" action="' . $post . '" data-pk-auth-form="login">';
	$html .= '<input type="hidden" name="action" value="pk_login">';
	$html .= wp_nonce_field( 'pk_login', '_wpnonce', true, false );
	$html .= '<input type="hidden" name="redirect_to" value="' . esc_attr( $args['redirect'] ) . '" data-pk-auth-redirect>';
	$html .= '<input type="hidden" name="pk_auth_from" value="' . esc_attr( $from ) . '" data-pk-auth-from>';
	$html .= '<label class="auth-field"><span>用户名或邮箱</span>'
		. '<input type="text" name="log" required autocomplete="username" autocapitalize="none" placeholder="账号"></label>';
	$html .= '<label class="auth-field"><span>密码</span>'
		. '<input type="password" name="pwd" required autocomplete="current-password" placeholder="密码"></label>';
	$html .= '<label class="auth-remember"><input type="checkbox" name="remember" value="1" checked><span>记住我</span></label>';
	$html .= '<button class="user-submit auth-submit" type="submit">登录</button>';
	$html .= '</form></div>';

	/* ---------- 注册 ---------- */
	$html .= '<div class="auth-panel" data-pk-auth-panel="register"' . ( 'register' === $mode ? '' : ' hidden' ) . '>';
	if ( ! $canreg ) {
		$html .= '<p class="auth-hint">本站暂未开放注册，需要账号请联系站长。</p>';
	} else {
		$html .= '<form class="user-form auth-form" method="post" action="' . $post . '" data-pk-auth-form="register">';
		$html .= '<input type="hidden" name="action" value="pk_register">';
		$html .= wp_nonce_field( 'pk_register', '_wpnonce', true, false );
		$html .= '<input type="hidden" name="redirect_to" value="' . esc_attr( $args['redirect'] ) . '" data-pk-auth-redirect>';
		$html .= '<input type="hidden" name="pk_auth_from" value="' . esc_attr( $from ) . '" data-pk-auth-from>';
		$html .= '<label class="auth-field"><span>用户名</span>'
			. '<input type="text" name="user_login" required maxlength="60" autocomplete="username" autocapitalize="none" placeholder="字母、数字、下划线"></label>';
		$html .= '<label class="auth-field"><span>邮箱</span>'
			. '<input type="email" name="user_email" required maxlength="100" autocomplete="email" autocapitalize="none" placeholder="name@example.com"></label>';
		$html .= '<label class="auth-field"><span>密码</span>'
			. '<input type="password" name="user_pass" required minlength="6" autocomplete="new-password" placeholder="至少 6 位"></label>';
		$html .= '<label class="auth-field"><span>确认密码</span>'
			. '<input type="password" name="user_pass2" required minlength="6" autocomplete="new-password" placeholder="再输一次"></label>';
		$html .= pk_captcha_html( 'pk_cap', '拖动滑块完成验证' );
		$html .= '<button class="user-submit auth-submit" type="submit">注册并登录</button>';
		$html .= '</form>';
	}
	$html .= '</div>';

	$html .= '<p class="auth-note">登录后这台设备会被信任 30 天，期间打开用户中心自动登录；点「退出」立即解除信任。</p>';
	$html .= '</div>';

	return $html;
}

/**
 * 全站登录弹窗（footer 常驻）
 */
function pk_auth_dialog_html() {
	/* /login/ 与 /user/ 本身就是登录页，不必再挂一层弹窗 */
	if ( is_user_logged_in() || is_page( array( 'login', 'user' ) ) ) {
		return '';
	}
	$mode = isset( $_GET['mode'] ) ? sanitize_key( wp_unslash( $_GET['mode'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification
	$err  = isset( $_GET['pk_err'] ) ? sanitize_key( wp_unslash( $_GET['pk_err'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

	$html  = '<dialog class="auth-dialog" id="pk-auth" data-pk-auth aria-label="登录或注册">';
	$html .= '<div class="auth-dialog-inner">';
	$html .= pk_auth_card_html(
		array(
			'mode'   => $mode,
			'err'    => $err,
			'dialog' => true,
		)
	);
	$html .= '</div></dialog>';
	return $html;
}

/**
 * 建 /login/ 页面（只跑一次）
 */
function pk_auth_ensure_page() {
	if ( '1' === (string) get_option( 'pk_login_page_v' ) ) {
		return;
	}
	if ( ! get_page_by_path( 'login', OBJECT, 'page' ) ) {
		wp_insert_post(
			array(
				'post_title'   => '登录',
				'post_name'    => 'login',
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);
	}
	update_option( 'pk_login_page_v', '1' );
}
add_action( 'init', 'pk_auth_ensure_page', 21 );

/**
 * 登录页不该被搜索引擎收录
 *
 * @param array $robots robots 指令。
 */
function pk_auth_robots( $robots ) {
	if ( is_page( 'login' ) ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'pk_auth_robots' );
