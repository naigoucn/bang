<?php
/**
 * Bang · SMTP 发信（0.13.28）
 *
 * 后台「Bang 设置 → 邮件」里配好主机 / 端口 / 账号后打开开关，
 * phpmailer_init 接管 WordPress 的 PHPMailer：所有 wp_mail（友链申请通知等）
 * 都改走这台 SMTP 服务器。未配置或开关关闭时完全不碰 PHPMailer，
 * 行为与 WordPress 原生一致，本地预览与未配置站点零影响。
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SMTP 是否真的可用：开关打开 + 主机非空。
 *
 * @return bool
 */
function pk_smtp_enabled() {
	if ( ! pk_opt( 'smtp_on' ) ) {
		return false;
	}
	return '' !== trim( (string) pk_opt( 'smtp_host' ) );
}

/**
 * 组装 PHPMailer 配置。两端（后台测试 / 真实 wp_mail）共用，保证测的就是发的。
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer 实例。
 */
function pk_smtp_configure( $phpmailer ) {
	$host = trim( (string) pk_opt( 'smtp_host' ) );
	if ( '' === $host ) {
		return;
	}
	$port = (int) pk_opt( 'smtp_port' );
	if ( $port < 1 || $port > 65535 ) {
		$port = 465;
	}
	$enc  = (string) pk_opt( 'smtp_encryption' );
	$user = trim( (string) pk_opt( 'smtp_user' ) );
	$pass = (string) pk_opt( 'smtp_pass' );
	$from = trim( (string) pk_opt( 'smtp_from' ) );
	$name = trim( (string) pk_opt( 'smtp_from_name' ) );

	$phpmailer->isSMTP();
	$phpmailer->Host       = $host;
	$phpmailer->Port       = $port;
	$phpmailer->SMTPAuth   = ( '' !== $user );
	$phpmailer->Username   = $user;
	$phpmailer->Password   = $pass;
	$phpmailer->SMTPSecure = ( 'starttls' === $enc ) ? 'tls' : ( ( 'none' === $enc ) ? '' : 'ssl' );
	/* 465 走隐式 TLS，必须开；25 / 587 明文或 STARTTLS 时留空 */
	$phpmailer->SMTPAutoTLS = ( 'none' !== $enc );

	if ( '' !== $from ) {
		$phpmailer->setFrom( $from, '' !== $name ? $name : get_bloginfo( 'name' ), false );
	} elseif ( '' !== $user && is_email( $user ) ) {
		$phpmailer->setFrom( $user, '' !== $name ? $name : get_bloginfo( 'name' ), false );
	} elseif ( '' !== $name ) {
		$phpmailer->FromName = $name;
	}
}
add_action( 'phpmailer_init', 'pk_smtp_configure' );

/**
 * 后台「测试发送」：用当前保存的配置发一封信给自己，直接回传成败与错误信息。
 * nonce + manage_options 双重校验，管理员之外一律拒绝。
 * 收件地址取后台「测试收件地址」，留空回退管理员邮箱（测试地址单独存，
 * 不写进 pk_options，避免污染设置表单的清洗流程）。
 */
function pk_smtp_test() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( '没有权限。' );
	}
	check_ajax_referer( 'pk_ajax', 'nonce' );

	if ( ! pk_smtp_enabled() ) {
		wp_send_json_error( '先打开「启用 SMTP 发信」并填写主机，再点测试。' );
	}
	$to = trim( (string) pk_opt( 'smtp_test_to' ) );
	if ( ! is_email( $to ) ) {
		$to = sanitize_email( (string) get_option( 'admin_email' ) );
	}
	if ( ! is_email( $to ) ) {
		wp_send_json_error( '收件地址无效，请在上方「测试收件地址」填一个能收信的邮箱。' );
	}

	global $phpmailer;
	if ( isset( $phpmailer ) && is_object( $phpmailer ) ) {
		$phpmailer->clearAllRecipients();
		$phpmailer->clearAttachments();
		$phpmailer->clearCustomHeaders();
		$phpmailer->ErrorInfo = '';
	}

	$site = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );
	$body = "这是一封来自「" . $site . "」的 SMTP 测试邮件。\n\n"
		. '收到它说明主题里的 SMTP 配置生效了：站内通知（例如友链申请）会用同一套配置发信。'
		. "\n\n发送时间：" . current_time( 'Y-m-d H:i:s' ) . "\n";

	$ok = wp_mail( $to, '[' . $site . '] SMTP 测试邮件', $body );
	if ( $ok ) {
		wp_send_json_success( array( 'message' => '测试邮件已发出，请查收 ' . $to . '（含垃圾箱）。' ) );
	}

	$err = '';
	if ( isset( $phpmailer ) && is_object( $phpmailer ) && ! empty( $phpmailer->ErrorInfo ) ) {
		$err = (string) $phpmailer->ErrorInfo;
	}
	wp_send_json_error( '发送失败：' . ( '' !== $err ? $err : '服务器拒绝了这次连接，请检查主机 / 端口 / 加密方式 / 授权码。' ) );
}
add_action( 'wp_ajax_pk_smtp_test', 'pk_smtp_test' );

/**
 * 后台「邮件」tab 底部的接线说明 + 测试发送（只读 + 一个按钮，不参与保存）。
 * 按钮读表单里的「测试收件地址」，保存设置后再点测试即可生效。
 */
function pk_smtp_panel() {
	$on    = pk_smtp_enabled();
	$host  = trim( (string) pk_opt( 'smtp_host' ) );
	$port  = (int) pk_opt( 'smtp_port' );
	$enc   = (string) pk_opt( 'smtp_encryption' );
	$user  = trim( (string) pk_opt( 'smtp_user' ) );
	$enct  = 'starttls' === $enc ? 'STARTTLS' : ( ( 'none' === $enc ) ? __( '不加密', 'pianke' ) : 'SSL / TLS' );
	$statu = $on
		? sprintf( __( '当前生效：SMTP %s:%d（%s），登录账号 %s。站内所有 wp_mail 都走它。', 'pianke' ), $host, $port, $enct, $user ? $user : '—' )
		: __( '当前生效：WordPress 默认发信（未启用 SMTP）。打开上面的开关并填好主机后才会接管。', 'pianke' );

	$nonce = wp_create_nonce( 'pk_ajax' );

	return '<div class="pk-smtp-panel">'
		. '<h3>' . esc_html__( '接线说明', 'pianke' ) . '</h3>'
		. '<p class="pk-f-d">' . esc_html( $statu ) . '</p>'
		. '<p class="pk-f-d">' . esc_html__( '端口与加密方式要成对：SSL 用 465，STARTTLS 用 587，不加密用 25。多数国内邮箱 / 企业邮箱要求在服务商后台先开通 SMTP 并生成「授权码」，把授权码填进「密码 / 授权码」。', 'pianke' ) . '</p>'
		. '<p class="pk-f-d">' . esc_html__( '测试只发一封信，成功与否会立刻显示；失败信息里带有服务器返回的原因，按它调整配置即可。改了配置先点「保存设置」，再点测试。', 'pianke' ) . '</p>'
		. '<p class="pk-smtp-actions">'
		. '<button type="button" class="button is-primary" id="pk-smtp-test-btn"' . ( $on ? '' : ' disabled' ) . '>'
		. esc_html__( '发送测试邮件', 'pianke' ) . '</button>'
		. '<span class="pk-smtp-msg" id="pk-smtp-test-msg" role="status" aria-live="polite"></span>'
		. '</p>'
		. '<script>(function(){var b=document.getElementById("pk-smtp-test-btn");if(!b){return;}b.addEventListener("click",function(){'
		. 'var m=document.getElementById("pk-smtp-test-msg");var t=document.getElementById("pk-field-smtp_test_to");'
		. 'b.disabled=true;if(m){m.textContent="发送中…";m.className="pk-smtp-msg";}'
		. 'var fd=new FormData();fd.append("action","pk_smtp_test");fd.append("nonce",' . wp_json_encode( $nonce ) . ');'
		. 'fd.append("to",t?t.value:"");'
		. 'fetch(window.ajaxurl||"/wp-admin/admin-ajax.php",{method:"POST",credentials:"same-origin",body:fd})'
		. '.then(function(r){return r.json();}).then(function(res){b.disabled=false;'
		. 'if(!m){return;}if(res&&res.success){m.textContent=(res.data&&res.data.message)||"已发出";m.className="pk-smtp-msg is-ok";}'
		. 'else{m.textContent=(typeof res.data==="string"?res.data:(res.data&&res.data.message)||"发送失败");m.className="pk-smtp-msg is-err";}})'
		. '.catch(function(){b.disabled=false;if(m){m.textContent="网络异常，请重试";m.className="pk-smtp-msg is-err";}});});})();</script>'
		. '</div>';
}
