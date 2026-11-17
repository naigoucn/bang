<?php
/**
 * 邮箱订阅
 *
 * 首页卡片流下方的订阅卡片：只收一个邮箱，不做确认邮件（小站没必要多一步）。
 * 订阅者存在 option（pk_subscribers 数组），后台「常规」里能看到列表并导出 CSV。
 * 前端通过 REST（POST /wp-json/pianke/v1/subscribe）提交，未登录也可用，
 * 靠 IP 维度的 transient 做最基础的频率限制。
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PK_SUBSCRIBER_LIMIT' ) ) {
	/* option 里存数组，太多会让 autoload / 读取变慢，到顶就拒收 */
	define( 'PK_SUBSCRIBER_LIMIT', 5000 );
}

/**
 * 读取订阅者列表，最新的在前。
 *
 * @return array[]
 */
function pk_subscribers() {
	$list = get_option( 'pk_subscribers', array() );
	return is_array( $list ) ? $list : array();
}

/**
 * 邮箱是否已在列表里。
 *
 * @param string   $email 已规范化的邮箱。
 * @param array[]  $list  列表。
 * @return bool
 */
function pk_subscriber_exists( $email, $list ) {
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
function pk_subscribe_rest_route() {
	register_rest_route(
		'pianke/v1',
		'/subscribe',
		array(
			'methods'             => 'POST',
			'callback'            => 'pk_subscribe_submit',
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
add_action( 'rest_api_init', 'pk_subscribe_rest_route' );

/**
 * 提交订阅
 *
 * @param WP_REST_Request $request 请求。
 * @return array|WP_Error
 */
function pk_subscribe_submit( $request ) {
	$email = strtolower( trim( (string) $request->get_param( 'email' ) ) );

	if ( ! $email || mb_strlen( $email, 'UTF-8' ) > 100 || ! is_email( $email ) ) {
		return new WP_Error( 'pk_sub_bad_mail', '请输入有效的电子邮箱。', array( 'status' => 400 ) );
	}

	/* 违禁词提示（只提示不拦提交）：邮箱也会进后台列表，命中时把词标出来 */
		if ( function_exists( 'pk_moderation_handle' ) ) {
			$pk_mod = pk_moderation_handle( $email, '订阅邮箱' );
			if ( $pk_mod && ! $pk_mod['ok'] ) {
				return new WP_Error( 'pk_sub_moderation', function_exists( 'pk_moderation_hint' ) ? pk_moderation_hint( '包含违禁词，请修改后再提交。', $pk_mod['words'] ) : '包含违禁词，请修改后再提交。', array( 'status' => 400 ) );
			}
		}

	/* 同一 IP 45 秒只能提交一次，够挡住手滑连点，也不会误伤正常访客 */
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	$key = 'pk_sub_' . md5( $ip );
	if ( $ip && get_transient( $key ) ) {
		return new WP_Error( 'pk_sub_flood', '提交太频繁了，请稍后再试。', array( 'status' => 429 ) );
	}
	if ( $ip ) {
		set_transient( $key, 1, 45 );
	}

	$list = pk_subscribers();
	if ( pk_subscriber_exists( $email, $list ) ) {
		return array(
			'ok'      => true,
			'exists'  => true,
			'count'   => count( $list ),
			'message' => '这个邮箱已经在列表里了，感谢关注。',
		);
	}

	if ( count( $list ) >= PK_SUBSCRIBER_LIMIT ) {
		return new WP_Error( 'pk_sub_full', '订阅人数已达上限，请联系站长。', array( 'status' => 503 ) );
	}

	$list[] = array(
		'email' => $email,
		'time'  => current_time( 'mysql' ),
		'from'  => esc_url_raw( (string) $request->get_param( 'from' ) ),
	);
	update_option( 'pk_subscribers', $list, false );

	do_action( 'pk_subscribed', $email );

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
function pk_subscribe_html() {
	if ( ! pk_opt( 'subscribe_on' ) ) {
		return '';
	}

	$title = trim( (string) pk_opt( 'subscribe_title' ) );
	$desc  = trim( (string) pk_opt( 'subscribe_desc' ) );
	$title = '' !== $title ? $title : '订阅文章推送';
	$desc  = '' !== $desc ? $desc : '无骚扰邮件，随时可取消订阅。';

	$html  = '<section class="pk-sub" id="pk-sub" data-pk-sub aria-labelledby="pk-sub-title">';
	$html .= '<div class="pk-sub-copy">';
	$html .= '<h2 class="pk-sub-title" id="pk-sub-title">' . esc_html( $title ) . '</h2>';
	if ( '' !== $desc ) {
		$html .= '<p class="pk-sub-desc">' . esc_html( $desc ) . '</p>';
	}
	$html .= '</div>';

	$html .= '<form class="pk-sub-form" data-pk-sub-form data-rest="' . esc_attr( rest_url( 'pianke/v1/subscribe' ) ) . '" novalidate>';
	$html .= '<label class="pk-sub-field"><span class="pk-sub-sr">电子邮箱</span>'
		. '<input type="email" name="email" inputmode="email" autocomplete="email" maxlength="100"'
		. ' placeholder="输入您的电子邮件…" aria-label="电子邮箱"></label>';
	$html .= '<button class="pk-sub-btn" type="submit" data-pk-sub-submit><span>提交订阅</span></button>';
	$html .= '</form>';

	$html .= '<p class="pk-sub-done" data-pk-sub-done hidden></p>';
	$html .= '<p class="pk-sub-msg" data-pk-sub-msg role="status" aria-live="polite"></p>';
	$html .= '</section>';

	return apply_filters( 'pk_subscribe_html', $html );
}

/**
 * 后台：订阅者列表（设置页「常规」底部）。
 */
function pk_subscribers_panel() {
	$list  = pk_subscribers();
	$count = count( $list );
	$rows  = array_reverse( $list );
	$rows  = array_slice( $rows, 0, 20 );
	$url   = wp_nonce_url( admin_url( 'admin-post.php?action=pk_subs_export' ), 'pk_subs_export' );

	echo '<div class="pk-subs">';
	echo '<h3>' . esc_html__( '邮件订阅', 'pianke' ) . '</h3>';
	echo '<p class="description">' . esc_html( sprintf( '当前共 %d 位订阅者，最多保留 %d 位。', $count, PK_SUBSCRIBER_LIMIT ) ) . '</p>';

	if ( $rows ) {
		echo '<table class="widefat striped pk-subs-table"><thead><tr><th>邮箱</th><th>订阅时间</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			echo '<tr><td>' . esc_html( isset( $row['email'] ) ? $row['email'] : '' ) . '</td>'
				. '<td>' . esc_html( isset( $row['time'] ) ? $row['time'] : '' ) . '</td></tr>';
		}
		echo '</tbody></table>';
		if ( $count > count( $rows ) ) {
			echo '<p class="description">' . esc_html( sprintf( '只显示最新 %d 条，完整列表请导出 CSV。', count( $rows ) ) ) . '</p>';
		}
		echo '<p><a class="button" href="' . esc_url( $url ) . '">' . esc_html__( '导出 CSV', 'pianke' ) . '</a></p>';
	} else {
		echo '<p class="description">' . esc_html__( '还没有人订阅。', 'pianke' ) . '</p>';
	}
	echo '</div>';
}

/**
 * 后台：导出 CSV（带 BOM，Excel 打开不乱码）。
 */
function pk_subscribers_export() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( '没有权限。', 'pianke' ) );
	}
	check_admin_referer( 'pk_subs_export' );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=pk-subscribers-' . gmdate( 'Ymd' ) . '.csv' );

	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" );
	fputcsv( $out, array( 'email', 'time' ) );
	foreach ( pk_subscribers() as $row ) {
		fputcsv( $out, array( isset( $row['email'] ) ? $row['email'] : '', isset( $row['time'] ) ? $row['time'] : '' ) );
	}
	fclose( $out );
	exit;
}
add_action( 'admin_post_pk_subs_export', 'pk_subscribers_export' );

/**
 * 把订阅接口地址挂进 pkUser，省得前端从 DOM 里猜。
 *
 * @param array $config 前端配置。
 * @return array
 */
function pk_subscribe_config( $config ) {
	$config['subscribeUrl'] = esc_url_raw( rest_url( 'pianke/v1/subscribe' ) );
	return $config;
}
add_filter( 'pk_user_config', 'pk_subscribe_config' );
