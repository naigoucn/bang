<?php
/**
 * Bang · 友情链接申请
 *
 * 前台 /links/ 表单（游客可提交）→ admin-ajax 写入待审列表 →
 * 后台「Bang 设置 → 常规 → 友链申请」通过 / 忽略。通过后用 wp_insert_link()
 * 写入 WordPress 核心链接管理器（外观 → 链接），分类落到 link_category 分类法，
 * 于是分类、卡片流、两段式弹窗全部沿用已有渲染，不需要第二套数据。
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PK_LINK_APPLY_KEY' ) ) {
	define( 'PK_LINK_APPLY_KEY', 'pk_link_applications' );
}
if ( ! defined( 'PK_LINK_APPLY_LIMIT' ) ) {
	define( 'PK_LINK_APPLY_LIMIT', 200 );
}
if ( ! defined( 'PK_LINK_APPLY_GAP' ) ) {
	define( 'PK_LINK_APPLY_GAP', 60 );
}

/**
 * 竖线是友链文本的分隔符，申请内容里一律换成空格，避免入库后错列。
 *
 * @param string $text 原文。
 * @return string
 */
function pk_link_apply_clean( $text ) {
	return trim( preg_replace( '/[|｜]/u', ' ', (string) $text ) );
}

/**
 * 读取全部申请（最多 PK_LINK_APPLY_LIMIT 条）。
 *
 * @return array
 */
function pk_link_applications() {
	$list = get_option( PK_LINK_APPLY_KEY, array() );
	return is_array( $list ) ? $list : array();
}

/**
 * 写回申请列表。autoload=false，避免每页都带上一大坨数据。
 *
 * @param array $list 列表。
 */
function pk_link_applications_save( $list ) {
	update_option( PK_LINK_APPLY_KEY, array_slice( array_values( $list ), 0, PK_LINK_APPLY_LIMIT ), false );
}

/**
 * 前台提交。nonce 复用全站 pkFront.nonce（wp_create_nonce('pk_ajax')），
 * 游客同样可验证；honeypot + 同 IP 60 秒限流挡住最基础的机器人。
 */
function pk_link_apply_submit() {
	check_ajax_referer( 'pk_ajax', 'nonce' );

	/* 蜜罐：填了就静默丢弃，返回成功，不给探测器反馈 */
	$hp = isset( $_POST['pk_hp'] ) ? trim( (string) wp_unslash( $_POST['pk_hp'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( '' !== $hp ) {
		wp_send_json_success( array( 'message' => '申请已提交，等站长审核。' ) );
	}

	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$tk = 'pk_link_apply_' . md5( (string) $ip );
	if ( '' !== $ip && get_transient( $tk ) ) {
		wp_send_json_error( '提交太频繁了，稍等一分钟再来。' );
	}

	$name  = isset( $_POST['link_name'] ) ? pk_link_apply_clean( sanitize_text_field( wp_unslash( $_POST['link_name'] ) ) ) : '';
	$url   = isset( $_POST['link_url'] ) ? pk_link_apply_clean( esc_url_raw( wp_unslash( $_POST['link_url'] ) ) ) : '';
	$desc  = isset( $_POST['link_desc'] ) ? pk_link_apply_clean( sanitize_text_field( wp_unslash( $_POST['link_desc'] ) ) ) : '';
	$group = isset( $_POST['link_group'] ) ? pk_link_apply_clean( sanitize_text_field( wp_unslash( $_POST['link_group'] ) ) ) : '';
	$email = isset( $_POST['link_email'] ) ? trim( sanitize_email( wp_unslash( $_POST['link_email'] ) ) ) : '';

	$len = function_exists( 'mb_strlen' ) ? mb_strlen( $name, 'UTF-8' ) : strlen( $name );
	if ( '' === $name || $len > 60 ) {
		wp_send_json_error( '请填写站点名称（不超过 60 字）。' );
	}
	if ( ! preg_match( '#^https?://#i', $url ) || '' === (string) wp_parse_url( $url, PHP_URL_HOST ) ) {
		wp_send_json_error( '站点地址要带 http:// 或 https://。' );
	}
	if ( '' !== $email && ! is_email( $email ) ) {
		wp_send_json_error( '联系邮箱格式不对。' );
	}

	/* 分类只认后台清单里的，防止随手塞一个不存在的分类 */
	$groups = function_exists( 'pk_link_groups' ) ? pk_link_groups() : array();
	if ( '' !== $group && ! in_array( $group, $groups, true ) ) {
		$group = '';
	}

	/* 同一个站点不重复收：已在友链里，或已有待审申请 */
	foreach ( pk_friend_links() as $item ) {
		if ( isset( $item['url'] ) && 0 === strcasecmp( rtrim( $item['url'], '/' ), rtrim( $url, '/' ) ) ) {
			wp_send_json_error( '这个站点已经在友链里啦。' );
		}
	}
	$list = pk_link_applications();
	foreach ( $list as $row ) {
		if ( 'pending' !== ( isset( $row['status'] ) ? $row['status'] : '' )
			|| empty( $row['url'] ) ) {
			continue;
		}
		if ( 0 === strcasecmp( rtrim( (string) $row['url'], '/' ), rtrim( $url, '/' ) ) ) {
			wp_send_json_error( '这个站点已经提交过申请，等站长审核就好。' );
		}
	}

	$list[] = array(
		'id'     => function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'pkla_', true ),
		'name'   => $name,
		'url'    => $url,
		'desc'   => $desc,
		'group'  => $group,
		'email'  => $email,
		'ip'     => $ip,
		'time'   => current_time( 'mysql' ),
		'status' => 'pending',
	);
	pk_link_applications_save( $list );
	if ( '' !== $ip ) {
		set_transient( $tk, 1, PK_LINK_APPLY_GAP );
	}

	/* 违禁词审核：命中时同样入库、照发通知，但把命中词标出来，方便后台一眼看到问题 */
	$pk_mod = function_exists( 'pk_moderation_handle' ) ? pk_moderation_handle( $name . ' ' . $desc, '友链申请' ) : null;
	if ( $pk_mod && $pk_mod['hold'] && ! empty( $pk_mod['words'] ) ) {
		$idx = count( $list ) - 1;
		$list[ $idx ]['flagged'] = 1;
		$list[ $idx ]['words']   = array_slice( array_unique( (array) $pk_mod['words'] ), 0, 5 );
		pk_link_applications_save( $list );
	}

	/* 通知站长：地址取后台「联系邮箱」，无效回退管理员邮箱。发信失败不影响入库 */
	$to = function_exists( 'pk_contact_email' ) ? pk_contact_email() : (string) get_option( 'admin_email' );
	if ( is_email( $to ) ) {
		$site = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );
		$body = "收到一条友链申请：\n\n"
			. '站点：' . $name . "\n"
			. '地址：' . $url . "\n"
			. '分类：' . ( '' !== $group ? $group : '未分组' ) . "\n"
			. '介绍：' . ( '' !== $desc ? $desc : '（未填写）' ) . "\n"
			. '联系：' . ( '' !== $email ? $email : '（未填写）' ) . "\n"
			. ( ( $pk_mod && ! empty( $pk_mod['words'] ) )
				? '⚠ 命中违禁词：' . implode( '、', array_slice( array_unique( (array) $pk_mod['words'] ), 0, 5 ) ) . "\n"
				: '' )
			. "\n到「Bang 设置 → 常规 → 友链申请」里通过或忽略。\n";
		wp_mail( $to, '[' . $site . '] 新的友情链接申请', $body );
	}

	wp_send_json_success( array( 'message' => '申请已提交，等站长审核。' ) );
}
add_action( 'wp_ajax_pk_link_apply', 'pk_link_apply_submit' );
add_action( 'wp_ajax_nopriv_pk_link_apply', 'pk_link_apply_submit' );

/**
 * 后台审核动作：通过 / 忽略。用 admin-post + _wpnonce，操作后回设置页。
 */
function pk_link_apply_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( '没有权限。', 'pianke' ) );
	}
	$id    = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$do    = isset( $_GET['do'] ) ? sanitize_key( wp_unslash( $_GET['do'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( ! wp_verify_nonce( $nonce, 'pk_link_apply_' . $do . '_' . $id ) ) {
		wp_die( esc_html__( '链接已过期，请刷新页面重试。', 'pianke' ) );
	}

	$list = pk_link_applications();
	$idx  = -1;
	foreach ( $list as $i => $row ) {
		if ( isset( $row['id'] ) && (string) $row['id'] === (string) $id ) {
			$idx = $i;
			break;
		}
	}
	if ( $idx < 0 ) {
		wp_die( esc_html__( '这条申请不存在。', 'pianke' ) );
	}

	if ( 'approve' === $do ) {
		$row = $list[ $idx ];
		$g   = isset( $row['group'] ) ? trim( (string) $row['group'] ) : '';
		/* 分类名 → 核心 link_category 分类：没有就先建一个，避免申请里的分类被丢掉 */
		$cat_id = 0;
		if ( '' !== $g && function_exists( 'get_term_by' ) ) {
			$term = get_term_by( 'name', $g, 'link_category' );
			if ( $term && ! is_wp_error( $term ) ) {
				$cat_id = (int) $term->term_id;
			} elseif ( function_exists( 'wp_insert_term' ) ) {
				$added = wp_insert_term( $g, 'link_category' );
				if ( ! is_wp_error( $added ) ) {
					$cat_id = (int) $added['term_id'];
				}
			}
		}
		$link = array(
			'link_name'        => isset( $row['name'] ) ? (string) $row['name'] : '',
			'link_url'         => isset( $row['url'] ) ? (string) $row['url'] : '',
			'link_description' => isset( $row['desc'] ) ? (string) $row['desc'] : '',
			'link_visible'     => 'Y',
		);
		if ( $cat_id ) {
			$link['link_category'] = $cat_id;
		}
		if ( function_exists( 'wp_insert_link' ) ) {
			wp_insert_link( $link );
		}
		$list[ $idx ]['status'] = 'approved';
		$notice                 = 'approved';
	} elseif ( 'reject' === $do ) {
		$list[ $idx ]['status'] = 'rejected';
		$notice                 = 'rejected';
	} else {
		wp_die( esc_html__( '未知操作。', 'pianke' ) );
	}

	pk_link_applications_save( $list );
	wp_safe_redirect(
		add_query_arg(
			array(
				'tab'            => 'general',
				'pk_link_apply'  => $notice,
			),
			admin_url( 'admin.php?page=pianke-settings' )
		)
	);
	exit;
}
add_action( 'admin_post_pk_link_apply_action', 'pk_link_apply_action' );

/**
 * 后台：待审申请面板（挂到「常规」tab 底部）。
 */
function pk_link_applications_panel() {
	$all     = pk_link_applications();
	$pending = array();
	foreach ( $all as $row ) {
		if ( 'pending' === ( isset( $row['status'] ) ? $row['status'] : '' ) ) {
			$pending[] = $row;
		}
	}
	$rows = array_slice( array_reverse( $pending ), 0, 30 );

	echo '<div class="pk-linkapply">';
	echo '<h3>' . esc_html__( '友链申请', 'pianke' ) . '</h3>';

	$notice = isset( $_GET['pk_link_apply'] ) ? sanitize_key( wp_unslash( $_GET['pk_link_apply'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( 'approved' === $notice ) {
		echo '<p class="description pk-linkapply-ok">已通过，站点已写入核心链接管理器（外观 → 链接），分类一并带上。</p>';
	} elseif ( 'rejected' === $notice ) {
		echo '<p class="description">已忽略这条申请。</p>';
	}
	echo '<p class="description">'
		. esc_html( sprintf( '待审核 %d 条。前台 /links/ 的申请表单会写到这里；通过即写入 WordPress 核心链接管理器并带上分类，最多保留 %d 条记录。', count( $pending ), PK_LINK_APPLY_LIMIT ) )
		. '</p>';

	if ( ! $rows ) {
		echo '<p class="description">' . esc_html__( '还没有待审核的申请。', 'pianke' ) . '</p>';
		echo '</div>';
		return;
	}

	echo '<div class="pk-linkapply-list">';
	foreach ( $rows as $row ) {
		$rid = (string) ( isset( $row['id'] ) ? $row['id'] : '' );
		$base = admin_url( 'admin-post.php?action=pk_link_apply_action&id=' . rawurlencode( $rid ) );
		$approve = wp_nonce_url( add_query_arg( 'do', 'approve', $base ), 'pk_link_apply_approve_' . $rid );
		$reject  = wp_nonce_url( add_query_arg( 'do', 'reject', $base ), 'pk_link_apply_reject_' . $rid );
		$g       = isset( $row['group'] ) ? (string) $row['group'] : '';
		$email   = isset( $row['email'] ) ? (string) $row['email'] : '';
		$time    = isset( $row['time'] ) ? (string) $row['time'] : '';
		$desc    = isset( $row['desc'] ) ? (string) $row['desc'] : '';

		echo '<div class="pk-linkapply-row">';
		echo '<div class="pk-linkapply-main">';
		echo '<strong>' . esc_html( isset( $row['name'] ) ? $row['name'] : '' ) . '</strong>';
		echo ' <a href="' . esc_url( isset( $row['url'] ) ? $row['url'] : '#' ) . '" target="_blank" rel="noopener noreferrer">'
			. esc_html( function_exists( 'pk_link_host' ) ? pk_link_host( isset( $row['url'] ) ? $row['url'] : '' ) : '' ) . '</a>';
		echo '<span class="pk-linkapply-meta">'
			. esc_html( '' !== $g ? $g : '未分组' ) . ' · ' . esc_html( $time )
			. ( '' !== $email ? ' · ' . esc_html( $email ) : '' )
			. '</span>';
		if ( '' !== $desc ) {
			echo '<p class="pk-linkapply-desc">' . esc_html( $desc ) . '</p>';
		}
		/* 违禁词：单独一行标出来，别混在描述里看不出来 */
		$flag = ! empty( $row['flagged'] ) && ! empty( $row['words'] ) ? (array) $row['words'] : array();
		if ( $flag ) {
			echo '<p class="pk-linkapply-warn">'
				. esc_html( '⚠ 命中违禁词：' . implode( '、', array_slice( array_unique( $flag ), 0, 5 ) ) )
				. '</p>';
		}
		echo '</div>';
		echo '<div class="pk-linkapply-ops">'
			. '<a class="button is-primary" href="' . esc_url( $approve ) . '">' . esc_html__( '通过', 'pianke' ) . '</a> '
			. '<a class="button" href="' . esc_url( $reject ) . '">' . esc_html__( '忽略', 'pianke' ) . '</a>'
			. '</div>';
		echo '</div>';
	}
	echo '</div>';
	if ( count( $pending ) > count( $rows ) ) {
		echo '<p class="description">' . esc_html( sprintf( '只显示最新 %d 条。', count( $rows ) ) ) . '</p>';
	}
	echo '</div>';
}
