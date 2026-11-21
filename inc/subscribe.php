<?php
/**
 * 邮箱订阅
 *
 * 首页卡片流下方的订阅卡片：只收一个邮箱，不做确认邮件（小站没必要多一步）。
 * 订阅者存在 option（bang_subscribers 数组），后台「常规」里能看到列表并导出 CSV。
 * 前端通过 REST（POST /wp-json/bang/v1/subscribe）提交，未登录也可用，
 * 靠 IP 维度的 transient 做最基础的频率限制。
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BANG_SUBSCRIBER_LIMIT' ) ) {
	/* option 里存数组，太多会让 autoload / 读取变慢，到顶就拒收 */
	define( 'BANG_SUBSCRIBER_LIMIT', 5000 );
}
if ( ! defined( 'BANG_PUSH_LOG_LIMIT' ) ) {
	/* 推送日志最多留 50 条，够回溯又不撑爆 option */
	define( 'BANG_PUSH_LOG_LIMIT', 50 );
}

/**
 * 读取订阅者列表，最新的在前。
 *
 * @return array[]
 */
function bang_subscribers() {
	$list = get_option( 'bang_subscribers', array() );
	return is_array( $list ) ? $list : array();
}

/**
 * 邮箱是否已在列表里。
 *
 * @param string   $email 已规范化的邮箱。
 * @param array[]  $list  列表。
 * @return bool
 */
function bang_subscriber_exists( $email, $list ) {
	foreach ( $list as $row ) {
		if ( isset( $row['email'] ) && strtolower( (string) $row['email'] ) === $email ) {
			return true;
		}
	}
	return false;
}

/**
 * 注册 REST 路由
 */
function bang_subscribe_rest_route() {
	register_rest_route(
		'bang/v1',
		'/subscribe',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_subscribe_submit',
			'permission_callback' => '__return_true',
			'args'                => array(
				'email' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'from'  => array(
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'bang_subscribe_rest_route' );

/**
 * 提交订阅
 *
 * @param WP_REST_Request $request 请求。
 * @return array|WP_Error
 */
function bang_subscribe_submit( $request ) {
	$email = strtolower( trim( (string) $request->get_param( 'email' ) ) );

	if ( ! $email || mb_strlen( $email, 'UTF-8' ) > 100 || ! is_email( $email ) ) {
		return new WP_Error( 'bang_sub_bad_mail', '请输入有效的电子邮箱。', array( 'status' => 400 ) );
	}

	/* 违禁词提示（只提示不拦提交）：邮箱也会进后台列表，命中时把词标出来 */
		if ( function_exists( 'bang_moderation_handle' ) ) {
			$bang_mod = bang_moderation_handle( $email, '订阅邮箱' );
			if ( $bang_mod && ! $bang_mod['ok'] ) {
				return new WP_Error( 'bang_sub_moderation', function_exists( 'bang_moderation_hint' ) ? bang_moderation_hint( '包含违禁词，请修改后再提交。', $bang_mod['words'] ) : '包含违禁词，请修改后再提交。', array( 'status' => 400 ) );
			}
		}

	/* 同一 IP 45 秒只能提交一次，够挡住手滑连点，也不会误伤正常访客 */
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	$key = 'bang_sub_' . md5( $ip );
	if ( $ip && get_transient( $key ) ) {
		return new WP_Error( 'bang_sub_flood', '提交太频繁了，请稍后再试。', array( 'status' => 429 ) );
	}
	if ( $ip ) {
		set_transient( $key, 1, 45 );
	}

	$list = bang_subscribers();
	if ( bang_subscriber_exists( $email, $list ) ) {
		return array(
			'ok'      => true,
			'exists'  => true,
			'count'   => count( $list ),
			'message' => '这个邮箱已经在列表里了，感谢关注。',
		);
	}

	if ( count( $list ) >= BANG_SUBSCRIBER_LIMIT ) {
		return new WP_Error( 'bang_sub_full', '订阅人数已达上限，请联系站长。', array( 'status' => 503 ) );
	}

	$list[] = array(
		'email' => $email,
		'time'  => current_time( 'mysql' ),
		'from'  => esc_url_raw( (string) $request->get_param( 'from' ) ),
	);
	update_option( 'bang_subscribers', $list, false );

	do_action( 'bang_subscribed', $email );

	return array(
		'ok'      => true,
		'exists'  => false,
		'count'   => count( $list ),
		'message' => '订阅成功，感谢关注。',
	);
}

/**
 * 输出首页订阅卡片。没有启用时返回空串，首页不留空档。
 */
function bang_subscribe_html() {
	if ( ! bang_opt( 'subscribe_on' ) ) {
		return '';
	}

	$title = trim( (string) bang_opt( 'subscribe_title' ) );
	$desc  = trim( (string) bang_opt( 'subscribe_desc' ) );
	$title = '' !== $title ? $title : '订阅文章推送';
	$desc  = '' !== $desc ? $desc : '无骚扰邮件，随时可取消订阅。';

	$html  = '<section class="bang-sub" id="bang-sub" data-bang-sub aria-labelledby="bang-sub-title">';
	$html .= '<div class="bang-sub-copy">';
	$html .= '<h2 class="bang-sub-title" id="bang-sub-title">' . esc_html( $title ) . '</h2>';
	if ( '' !== $desc ) {
		$html .= '<p class="bang-sub-desc">' . esc_html( $desc ) . '</p>';
	}
	$html .= '</div>';

	$html .= '<form class="bang-sub-form" data-bang-sub-form data-rest="' . esc_attr( rest_url( 'bang/v1/subscribe' ) ) . '" novalidate>';
	$html .= '<label class="bang-sub-field"><span class="bang-sub-sr">电子邮箱</span>'
		. '<input type="email" name="email" inputmode="email" autocomplete="email" maxlength="100"'
		. ' placeholder="输入您的电子邮件…" aria-label="电子邮箱"></label>';
	$html .= '<button class="bang-sub-btn" type="submit" data-bang-sub-submit><span>提交订阅</span></button>';
	$html .= '</form>';

	$html .= '<p class="bang-sub-done" data-bang-sub-done hidden></p>';
	$html .= '<p class="bang-sub-msg" data-bang-sub-msg role="status" aria-live="polite"></p>';
	$html .= '</section>';

	return apply_filters( 'bang_subscribe_html', $html );
}

/**
 * 后台：订阅者列表（独立后台菜单页「邮件订阅」）。
 *
 * @param bool $show_title 是否输出内部小标题；独立页面已有 h1 时传 false。
 */
function bang_subscribers_panel( $show_title = true ) {
	$list  = bang_subscribers();
	$count = count( $list );
	$rows  = array_slice( array_reverse( $list ), 0, 20 );
	$logs  = bang_push_log();

	$total_ok = $total_fail = 0;
	$last_fail_reason = '';
	foreach ( $logs as $log ) {
		$total_ok   += isset( $log['ok'] ) ? (int) $log['ok'] : 0;
		$total_fail += isset( $log['failed'] ) ? (int) $log['failed'] : 0;
		if ( '' === $last_fail_reason && ! empty( $log['failed'] ) && ! empty( $log['errors'][0]['reason'] ) ) {
			$last_fail_reason = (string) $log['errors'][0]['reason'];
		}
	}
	$last     = $logs ? $logs[0] : null;
	$csv_url  = wp_nonce_url( admin_url( 'admin-post.php?action=bang_subs_export' ), 'bang_subs_export' );
	$push_url = wp_nonce_url( admin_url( 'admin-post.php?action=bang_push_now' ), 'bang_push_now' );

	$notice = isset( $_GET['bang_push'] ) ? sanitize_key( wp_unslash( $_GET['bang_push'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	echo '<div class="bang-subs">';
	if ( $show_title ) {
		echo '<h3>' . esc_html__( '邮件订阅', 'bang' ) . '</h3>';
	}
	if ( 'ok' === $notice ) {
		echo '<p class="bang-subs-notice is-ok">' . esc_html__( '推送已完成，详情见下方日志。', 'bang' ) . '</p>';
	} elseif ( 'fail' === $notice ) {
		echo '<p class="bang-subs-notice is-err">' . esc_html__( '没有可推送的文章或订阅者。', 'bang' ) . '</p>';
	}

	/* 四个数字卡：一眼看到订阅与推送概况 */
	$stats = array(
		array( $count, __( '订阅者', 'bang' ) ),
		array( count( $logs ), __( '推送次数', 'bang' ) ),
		array( $total_ok, __( '成功送达', 'bang' ) ),
		array( $total_fail, __( '推送失败', 'bang' ) ),
	);
	echo '<div class="bang-subs-stats">';
	foreach ( $stats as $stat ) {
		echo '<div class="bang-subs-stat"><strong>' . esc_html( (string) $stat[0] ) . '</strong><span>' . esc_html( $stat[1] ) . '</span></div>';
	}
	echo '</div>';

	/* 最近一次推送 + 手动推送入口 */
	echo '<section class="bang-subs-card">';
	echo '<div class="bang-subs-card-head"><h3>' . esc_html__( '最近推送', 'bang' ) . '</h3>'
		. '<a class="button" href="' . esc_url( $push_url ) . '">' . esc_html__( '推送最新文章', 'bang' ) . '</a></div>';
	if ( $last ) {
		echo '<p class="bang-subs-latest-meta">'
			. esc_html( isset( $last['time'] ) ? $last['time'] : '' )
			. ' · ' . esc_html( isset( $last['title'] ) ? $last['title'] : '' )
			. ' · ' . esc_html( sprintf( '成功 %d / 失败 %d', isset( $last['ok'] ) ? (int) $last['ok'] : 0, isset( $last['failed'] ) ? (int) $last['failed'] : 0 ) )
			. '</p>';
		if ( '' !== $last_fail_reason ) {
			echo '<p class="bang-subs-err">' . esc_html( '最近失败原因：' . $last_fail_reason ) . '</p>';
		}
	} else {
		echo '<p class="bang-subs-muted">' . esc_html__( '还没有推送记录。发布文章时会自动推送给全部订阅者，也可以点右侧按钮立即推送最新文章。', 'bang' ) . '</p>';
	}
	echo '</section>';

	/* 订阅者列表 */
	echo '<section class="bang-subs-card">';
	echo '<div class="bang-subs-card-head"><h3>' . esc_html__( '订阅者', 'bang' ) . '</h3>'
		. '<a class="button" href="' . esc_url( $csv_url ) . '">' . esc_html__( '导出 CSV', 'bang' ) . '</a></div>';
	echo '<p class="bang-subs-muted">' . esc_html( sprintf( '当前共 %d 位订阅者，最多保留 %d 位。', $count, BANG_SUBSCRIBER_LIMIT ) ) . '</p>';
	if ( $rows ) {
		echo '<table class="widefat striped bang-subs-table"><thead><tr><th>邮箱</th><th>订阅时间</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			echo '<tr><td>' . esc_html( isset( $row['email'] ) ? $row['email'] : '' ) . '</td>'
				. '<td>' . esc_html( isset( $row['time'] ) ? $row['time'] : '' ) . '</td></tr>';
		}
		echo '</tbody></table>';
		if ( $count > count( $rows ) ) {
			echo '<p class="bang-subs-muted">' . esc_html( sprintf( '只显示最新 %d 条，完整列表请导出 CSV。', count( $rows ) ) ) . '</p>';
		}
	} else {
		echo '<p class="bang-subs-muted">' . esc_html__( '还没有人订阅。', 'bang' ) . '</p>';
	}
	echo '</section>';

	/* 推送日志 */
	echo '<section class="bang-subs-card">';
	echo '<div class="bang-subs-card-head"><h3>' . esc_html__( '推送日志', 'bang' ) . '</h3></div>';
	if ( $logs ) {
		echo '<table class="widefat striped bang-subs-table"><thead><tr><th>时间</th><th>文章</th><th>成功</th><th>失败</th><th>失败原因</th></tr></thead><tbody>';
		foreach ( array_slice( $logs, 0, 20 ) as $log ) {
			$reasons = array();
			if ( ! empty( $log['errors'] ) ) {
				foreach ( array_slice( $log['errors'], 0, 2 ) as $err ) {
					$reasons[] = ( isset( $err['email'] ) ? $err['email'] : '' ) . '：' . ( isset( $err['reason'] ) ? $err['reason'] : '发送失败' );
				}
			}
			echo '<tr>'
				. '<td>' . esc_html( isset( $log['time'] ) ? $log['time'] : '' ) . '</td>'
				. '<td>' . esc_html( isset( $log['title'] ) ? $log['title'] : '' ) . '</td>'
				. '<td>' . esc_html( (string) ( isset( $log['ok'] ) ? (int) $log['ok'] : 0 ) ) . '</td>'
				. '<td>' . esc_html( (string) ( isset( $log['failed'] ) ? (int) $log['failed'] : 0 ) ) . '</td>'
				. '<td>' . esc_html( implode( '；', $reasons ) ) . '</td>'
				. '</tr>';
		}
		echo '</tbody></table>';
	} else {
		echo '<p class="bang-subs-muted">' . esc_html__( '暂无推送日志。', 'bang' ) . '</p>';
	}
	echo '</section>';

	echo '</div>';
}

/**
 * 后台：导出 CSV（带 BOM，Excel 打开不乱码）。
 */
function bang_subscribers_export() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( '没有权限。', 'bang' ) );
	}
	check_admin_referer( 'bang_subs_export' );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=bang-subscribers-' . gmdate( 'Ymd' ) . '.csv' );

	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" );
	fputcsv( $out, array( 'email', 'time' ) );
	foreach ( bang_subscribers() as $row ) {
		fputcsv( $out, array( isset( $row['email'] ) ? $row['email'] : '', isset( $row['time'] ) ? $row['time'] : '' ) );
	}
	fclose( $out );
	exit;
}
add_action( 'admin_post_bang_subs_export', 'bang_subscribers_export' );

/**
 * 把订阅接口地址挂进 bangUser，省得前端从 DOM 里猜。
 *
 * @param array $config 前端配置。
 * @return array
 */
function bang_subscribe_config( $config ) {
	$config['subscribeUrl'] = esc_url_raw( rest_url( 'bang/v1/subscribe' ) );
	return $config;
}
add_filter( 'bang_user_config', 'bang_subscribe_config' );

/**
 * 推送日志：option，最新在前，最多保留 BANG_PUSH_LOG_LIMIT 条。
 */
function bang_push_log() {
	$log = get_option( 'bang_push_log', array() );
	return is_array( $log ) ? $log : array();
}
function bang_push_log_add( $entry ) {
	$log = bang_push_log();
	array_unshift( $log, $entry );
	update_option( 'bang_push_log', array_slice( $log, 0, BANG_PUSH_LOG_LIMIT ), false );
}

/**
 * 捕获 wp_mail 的真实失败原因，写进当次推送日志。
 */
function bang_push_mail_failed( $error ) {
	$GLOBALS['bang_push_last_error'] = $error instanceof WP_Error ? $error->get_error_message() : '发送失败';
}
add_action( 'wp_mail_failed', 'bang_push_mail_failed' );

/**
 * 群发一篇文章给全部订阅者，并记一条日志（成功数 / 失败数 / 每个失败邮箱的原因）。
 *
 * @param int $post_id 文章 ID。
 * @return array|false 成功返回统计，文章或订阅者为空返回 false。
 */
function bang_push_send( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'publish' !== $post->post_status || 'post' !== $post->post_type ) {
		return false;
	}
	$subs = bang_subscribers();
	if ( ! $subs ) {
		return false;
	}

	$title   = get_the_title( $post );
	$url     = get_permalink( $post );
	$excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 80, '…' );
	$site    = get_bloginfo( 'name' );
	$subject = '[' . $site . '] ' . $title;
	/* 邮件客户端不认主题 CSS 变量，正文样式全部内联 */
	$message = '<div style="max-width:600px;margin:auto;color:#18181b;font-family:system-ui,-apple-system,Segoe UI,sans-serif;line-height:1.7">'
		. '<h2 style="margin:0 0 12px;font-size:20px">' . esc_html( $title ) . '</h2>'
		. '<p style="margin:0 0 18px;color:#6b7280;font-size:14px">' . esc_html( $excerpt ) . '</p>'
		. '<p style="margin:0 0 24px"><a href="' . esc_url( $url ) . '" style="display:inline-block;padding:10px 20px;border-radius:10px;background:#18181b;color:#fff;text-decoration:none;font-size:14px">阅读全文</a></p>'
		. '<hr style="border:0;border-top:1px solid #e5e7eb;margin:0 0 16px">'
		. '<p style="margin:0;color:#9ca3af;font-size:12px">来自 ' . esc_html( $site ) . ' · <a href="' . esc_url( home_url( '/' ) ) . '" style="color:#6b7280">访问网站</a></p>'
		. '</div>';
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );

	$ok     = 0;
	$errors = array();
	foreach ( $subs as $row ) {
		$email = isset( $row['email'] ) ? strtolower( trim( (string) $row['email'] ) ) : '';
		if ( ! is_email( $email ) ) {
			$errors[] = array( 'email' => $email, 'reason' => '邮箱格式无效' );
			continue;
		}
		$GLOBALS['bang_push_last_error'] = '';
		$sent = wp_mail( $email, $subject, $message, $headers );
		if ( $sent ) {
			$ok++;
		} else {
			$reason   = ! empty( $GLOBALS['bang_push_last_error'] ) ? $GLOBALS['bang_push_last_error'] : '邮件服务器返回错误（未给出具体原因）';
			$errors[] = array( 'email' => $email, 'reason' => $reason );
		}
	}
	$GLOBALS['bang_push_last_error'] = '';

	bang_push_log_add(
		array(
			'time'    => current_time( 'mysql' ),
			'post_id' => (int) $post_id,
			'title'   => $title,
			'total'   => count( $subs ),
			'ok'      => $ok,
			'failed'  => count( $errors ),
			'errors'  => $errors,
		)
	);

	return array( 'ok' => $ok, 'failed' => count( $errors ), 'total' => count( $subs ) );
}

/**
 * 文章首次发布时自动推送给全部订阅者（编辑已发布文章不重复推）。
 */
function bang_push_on_publish( $new_status, $old_status, $post ) {
	if ( 'publish' !== $new_status || 'publish' === $old_status || ! $post || 'post' !== $post->post_type ) {
		return;
	}
	bang_push_send( $post->ID );
}
add_action( 'transition_post_status', 'bang_push_on_publish', 20, 3 );

/**
 * 后台「推送最新文章」按钮。
 */
function bang_push_now() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( '没有权限。', 'bang' ) );
	}
	check_admin_referer( 'bang_push_now' );

	$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( ! $post_id ) {
		$latest  = get_posts( array( 'numberposts' => 1, 'post_type' => 'post', 'post_status' => 'publish', 'fields' => 'ids' ) );
		$post_id = $latest ? (int) $latest[0] : 0;
	}
	$result = $post_id ? bang_push_send( $post_id ) : false;

	wp_safe_redirect( add_query_arg( array( 'page' => 'bang-subscribers', 'bang_push' => $result ? 'ok' : 'fail' ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_bang_push_now', 'bang_push_now' );
