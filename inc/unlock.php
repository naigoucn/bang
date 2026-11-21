<?php
/**
 * Bang · 阅读解锁
 *
 * 每个锁定区块只能五选一，作者选定后读者只能用这一种方式解锁：
 *   1) 登录可见 —— 登录后即可阅读（默认）；
 *   2) 点赞可见 —— 给本文点个赞；
 *   3) 回复可见 —— 在本文发表一条已审核评论；
 *   4) 密码可见 —— 输入解锁密码（游客也可用，签名 cookie 记 30 天），可在区块上设专属密码。
 *   5) 点赞+评论可见 —— 叠加解锁：点赞 AND 已审核评论两步都完成；两者本身都要求登录态，密码不参与叠加。
 * 入群可见已屏蔽：旧内容里的 group 安全回退为「登录可见」，不会输出入群卡片、也不放行。
 * 其它方式既不会出现在卡片上，也不能放行；判定只按区块声明的 method 做。
 *
 * 用法：
 *   · 部分内容：区块「阅读解锁」侧栏选方式，或 [unlock method="like"]…[/unlock]；
 *   · 整篇文章：自定义字段 bang_unlock_post = 1，并用 bang_unlock_method 指定方式。
 * 五种方式中登录 / 点赞 / 回复 / 叠加不需要额外配置；密码可见可用后台站点密码或区块专属密码。
 *
 * 安全约定：未解锁时服务端绝不输出受保护内容，也不放进首页 #bang-data 内联层、
 * 核心 REST /wp-json/wp/v2/posts、feed 或 SEO 描述。前端只负责展示与提交，不参与鉴权。
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ============================================================
   设置与基础判定
   ============================================================ */
/**
 * 0.13.22：功能恒定启用，后台不再提供总开关。保留函数以兼容旧调用与回归脚本。
 *
 * @return bool
 */
function bang_unlock_enabled() {
	return true;
}

/**
 * 机器人回调共享密钥。
 *
 * @return string
 */
function bang_unlock_secret() {
	return trim( (string) bang_opt( 'unlock_secret' ) );
}

/**
 * 群内心跳有效期（秒）。
 *
 * @return int
 */
function bang_unlock_ttl_seconds() {
	$hours = (int) bang_opt( 'unlock_ttl' );
	if ( $hours < 1 ) {
		$hours = 72;
	}
	return $hours * HOUR_IN_SECONDS;
}

/**
 * 区块可以五选一的全部方式（不是「同时开启」，只是可选值白名单）。
 * 入群已屏蔽，不再出现在任何选项里；密码可用。
 * combo 是叠加解锁：点赞 + 评论两步都要完成，两步都要求登录态。
 *
 * @return string[]
 */
function bang_unlock_ways() {
	return array( 'login', 'like', 'comment', 'password', 'combo' );
}

/**
 * 五种方式的显示名。
 *
 * @return array
 */
function bang_unlock_method_labels() {
	return array(
		'login'    => '登录可见',
		'like'     => '点赞可见',
		'comment'  => '回复可见',
		'password' => '密码可见',
		'combo'    => '点赞+评论可见',
	);
}

/**
 * 方式白名单。非法值（含已屏蔽的 group）一律回退到「登录可见」。
 *
 * @param string $method 方式。
 * @return string
 */
function bang_unlock_normalize_method( $method ) {
	$method = sanitize_key( (string) $method );
	return array_key_exists( $method, bang_unlock_method_labels() ) ? $method : 'login';
}

/**
 * 整篇锁文章使用的方式：自定义字段 bang_unlock_method，默认登录可见。
 *
 * @param int $post_id 文章 ID。
 * @return string
 */
function bang_unlock_post_method( $post_id ) {
	$m = trim( (string) get_post_meta( (int) $post_id, 'bang_unlock_method', true ) );
	return '' !== $m ? bang_unlock_normalize_method( $m ) : 'login';
}

/**
 * 卡片底部说明。五种方式各一句，不再有入群状态原因。
 *
 * @param string $method 方式。
 * @param string $reason 兼容旧参数，已忽略。
 * @return string
 */
function bang_unlock_way_note( $method, $reason = '' ) {
	$map = array(
		'login'    => '登录后即可阅读。',
		'like'     => '点赞后页面会自动刷新。',
		'comment'  => '发表一条评论后页面会自动刷新（评论需通过审核）。',
		'password' => '密码正确后 30 天内免重输。',
		'combo'    => '点赞并发表一条评论，两步都完成后自动刷新（评论需通过审核）。',
	);
	return isset( $map[ $method ] ) ? $map[ $method ] : '';
}

/**
 * 当前正在渲染的文章 ID。首页内联阅读层一次渲染多篇文章，
 * bang_heading_data() 会临时把 ID 放进 $GLOBALS，避免误用主查询的 get_the_ID()。
 *
 * @return int
 */
function bang_unlock_current_post() {
	if ( isset( $GLOBALS['bang_unlock_render_post'] ) ) {
		return (int) $GLOBALS['bang_unlock_render_post'];
	}
	$id = get_the_ID();
	return $id ? (int) $id : (int) get_queried_object_id();
}

/**
 * 图标。与 bang_icon() 同一套线性描边，黑白体系、无 emoji。
 *
 * @param string $name 名称。
 * @param int    $size 尺寸。
 * @return string
 */
function bang_unlock_icon( $name, $size = 18 ) {
	$paths = array(
		'lock'    => '<path d="M8 22H16C18.8284 22 20.2426 22 21.1213 21.1213C22 20.2426 22 18.8284 22 16C22 13.1716 22 11.7574 21.1213 10.8787C20.2426 10 18.8284 10 16 10H8C5.17157 10 3.75736 10 2.87868 10.8787C2 11.7574 2 13.1716 2 16C2 18.8284 2 20.2426 2.87868 21.1213C3.75736 22 5.17157 22 8 22Z" stroke-linecap="round" stroke="currentColor"/> <path d="M6 10V8C6 4.68629 8.68629 2 12 2C15.3137 2 18 4.68629 18 8V10" stroke-linecap="round" stroke="currentColor"/>',
		'heart'   => '<path d="M8.96173 18.9108C6.01943 16.5913 2 14 2 9.13704C2 4.2741 7.50016 0.825403 12 5.50057C16.4998 0.825403 22 4.2741 22 9.13704C22 14 17.9806 16.5913 15.0383 18.9108C14 19.7293 13 20.4999 12 20.4999C11 20.4999 10 19.7293 8.96173 18.9108Z" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>',
		'message' => '<path d="M22 12C22 17.5228 17.5228 22 12 22C10.4003 22 8.88837 21.6244 7.54753 20.9565C7.19121 20.7791 6.78393 20.72 6.39939 20.8229L4.17335 21.4185C3.20701 21.677 2.32295 20.793 2.58151 19.8267L3.17712 17.6006C3.28001 17.2161 3.22094 16.8088 3.04346 16.4525C2.37562 15.1116 2 13.5997 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12Z" stroke-linecap="round" stroke="currentColor"/>',
		'key'     => '<path d="M22 8.29344C22 11.7692 19.1708 14.5869 15.6807 14.5869C15.0439 14.5869 13.5939 14.4405 12.8885 13.8551L12.0067 14.7333C11.272 15.465 11.8598 15.465 12.1537 16.0505C12.1537 16.0505 12.8885 17.075 12.1537 18.0995C11.7128 18.6849 10.4783 19.5045 9.06754 18.0995L8.77362 18.3922C8.77362 18.3922 9.65538 19.4167 8.92058 20.4412C8.4797 21.0267 7.30403 21.6121 6.27531 20.5876L5.2466 21.6121C4.54119 22.3146 3.67905 21.9048 3.33616 21.6121L2.45441 20.7339C1.63143 19.9143 2.1115 19.0264 2.45441 18.6849L10.0963 11.0743C10.0963 11.0743 9.3615 9.90338 9.3615 8.29344C9.3615 4.81767 12.1907 2 15.6807 2C19.1708 2 22 4.81767 22 8.29344Z" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/> <path d="M17.8853 8.29353C17.8853 9.50601 16.8984 10.4889 15.681 10.4889C14.4635 10.4889 13.4766 9.50601 13.4766 8.29353C13.4766 7.08105 14.4635 6.09814 15.681 6.09814C16.8984 6.09814 17.8853 7.08105 17.8853 8.29353Z" stroke-linecap="round" stroke="currentColor"/>',
		'users'   => '<circle cx="9" cy="6" r="4" stroke-linecap="round" stroke="currentColor"/> <path d="M15 9C16.6569 9 18 7.65685 18 6C18 4.34315 16.6569 3 15 3" stroke-linecap="round" stroke="currentColor"/> <ellipse cx="9" cy="17" rx="7" ry="4" stroke-linecap="round" stroke="currentColor"/> <path d="M18 14C19.7542 14.3847 21 15.3589 21 16.5C21 17.5293 19.9863 18.4229 18.5 18.8704" stroke-linecap="round" stroke="currentColor"/>',
		'copy'    => '<path d="M12 22H15C17.8284 22 19.2426 22 20.1213 21.1213C21 20.2426 21 18.8284 21 16V11C21 8.17157 21 6.75736 20.1213 5.87868C19.2426 5 17.8284 5 15 5H12C9.17157 5 7.75736 5 6.87868 5.87868C6 6.75736 6 8.17157 6 11V16C6 18.8284 6 20.2426 6.87868 21.1213C7.75736 22 9.17157 22 12 22Z" stroke-linecap="round" stroke="currentColor"/> <path d="M6 19C4.34315 19 3 17.6569 3 16V10C3 6.22876 3 4.34315 4.17157 3.17157C5.34315 2 7.22876 2 11 2H15C16.6569 2 18 3.34315 18 5" stroke-linecap="round" stroke="currentColor"/>',
		'check'   => '<path d="M5 13L9 17L19 7" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="bang-unlock-ico" viewBox="0 0 24 24" width="' . (int) $size . '" height="' . (int) $size . '"'
		. ' fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
		. $paths[ $name ] . '</svg>';
}

/* ============================================================
   密码可见：站点密码 + 签名 cookie 令牌
   ============================================================ */
/**
 * 本文生效的解锁密码：文章自定义字段 > 后台站点密码 > 稳定的站点派生密码。
 *
 * @param int $post_id 文章 ID。
 * @return string
 */
function bang_unlock_password_for( $post_id ) {
	$post_id = (int) $post_id;
	$pw      = trim( (string) get_post_meta( $post_id, 'bang_unlock_password', true ) );
	if ( '' !== $pw ) {
		return $pw;
	}
	$pw = trim( (string) bang_opt( 'unlock_password' ) );
	if ( '' !== $pw ) {
		return $pw;
	}
	/* 后台没填时给一串稳定随机密码，管理员可在面板里只读查看 */
	return substr( wp_hash( 'bang_unlock_site_password' ), 0, 10 );
}

/**
 * 0.13.23：区块专属密码的不透明标识。只把 HMAC 下发到前台，
 * 明文密码永远留在服务端（文章内容里），REST 兑换时按这个标识找回密码。
 *
 * @param int    $post_id  文章 ID。
 * @param string $password 区块上设置的密码。
 * @return string 24 位小写十六进制。
 */
function bang_unlock_lock_token( $post_id, $password ) {
	return substr(
		hash_hmac( 'sha256', 'bang_unlock_lock|' . (int) $post_id . '|' . (string) $password, wp_salt( 'auth' ) ),
		0,
		24
	);
}

/**
 * 解析文章里所有「带专属密码」的锁，返回 token => {method,password}。
 * 密码为空的锁不进表：它们回退到站点 / 文章密码，保持 0.13.22 行为。
 *
 * @param int $post_id 文章 ID。
 * @return array
 */
function bang_unlock_locks_for( $post_id ) {
	$post_id = (int) $post_id;
	$locks   = array();
	if ( ! $post_id ) {
		return $locks;
	}
	$content = (string) get_post_field( 'post_content', $post_id );
	$add     = function ( $method, $password ) use ( $post_id, &$locks ) {
		$password = trim( (string) $password );
		if ( '' === $password ) {
			return;
		}
		$token           = bang_unlock_lock_token( $post_id, $password );
		$locks[ $token ] = array(
			'method'   => bang_unlock_normalize_method( $method ),
			'password' => $password,
		);
	};
	/* 动态区块：<!-- wp:bang/unlock {"method":"password","password":"…"} /--> */
	if ( preg_match_all( '/<!--\s*wp:bang\/unlock\s+(\{.*?\})\s*\/?-->/s', $content, $bm ) ) {
		foreach ( $bm[1] as $json ) {
			$attrs = json_decode( $json, true );
			if ( ! is_array( $attrs ) ) {
				continue;
			}
			$add(
				isset( $attrs['method'] ) ? $attrs['method'] : 'group',
				isset( $attrs['password'] ) ? $attrs['password'] : ''
			);
		}
	}
	/* 简码：[unlock method="password" password="…"]…[/unlock] */
	if ( preg_match_all( '/\[unlock([^\]]*)\]/i', $content, $sm ) ) {
		foreach ( $sm[1] as $raw ) {
			$attrs = shortcode_parse_atts( trim( $raw ) );
			if ( ! is_array( $attrs ) ) {
				continue;
			}
			$add(
				isset( $attrs['method'] ) ? $attrs['method'] : 'group',
				isset( $attrs['password'] ) ? $attrs['password'] : ''
			);
		}
	}
	return $locks;
}

/**
 * 查一个锁标识对应的密码信息。
 *
 * @param int    $post_id 文章 ID。
 * @param string $lock    前台回传的锁标识。
 * @return array|false
 */
function bang_unlock_lock_info( $post_id, $lock ) {
	$lock  = (string) $lock;
	$locks = bang_unlock_locks_for( $post_id );
	return '' !== $lock && isset( $locks[ $lock ] ) ? $locks[ $lock ] : false;
}

/**
 * 由区块 / 简码的密码属性算出锁标识；非密码方式或留空时返回空串。
 *
 * @param int    $post_id  文章 ID。
 * @param string $method   解锁方式。
 * @param string $password 区块密码。
 * @return string
 */
function bang_unlock_lock_for( $post_id, $method, $password ) {
	if ( 'password' !== bang_unlock_normalize_method( $method ) ) {
		return '';
	}
	$password = trim( (string) $password );
	return '' !== $password ? bang_unlock_lock_token( $post_id, $password ) : '';
}

/**
 * 本文是否存在「用站点 / 文章密码」的密码锁。
 * 整篇锁、以及没填专属密码的密码区块都属于这一类；用于阻止不带 lock 的兑换
 * 误用站点密码去碰只有专属密码的区块。
 *
 * @param int $post_id 文章 ID。
 * @return bool
 */
function bang_unlock_has_plain_password_lock( $post_id ) {
	$post_id = (int) $post_id;
	if ( get_post_meta( $post_id, 'bang_unlock_post', true ) && 'password' === bang_unlock_post_method( $post_id ) ) {
		return true;
	}
	$content = (string) get_post_field( 'post_content', $post_id );
	if ( preg_match_all( '/<!--\s*wp:bang\/unlock\s+(\{.*?\})\s*\/?-->/s', $content, $bm ) ) {
		foreach ( $bm[1] as $json ) {
			$attrs = json_decode( $json, true );
			if ( is_array( $attrs ) && isset( $attrs['method'] ) && 'password' === $attrs['method']
				&& ( ! isset( $attrs['password'] ) || '' === trim( (string) $attrs['password'] ) ) ) {
				return true;
			}
		}
	}
	if ( preg_match_all( '/\[unlock([^\]]*)\]/i', $content, $sm ) ) {
		foreach ( $sm[1] as $raw ) {
			$attrs = shortcode_parse_atts( trim( $raw ) );
			if ( is_array( $attrs ) && isset( $attrs['method'] ) && 'password' === $attrs['method']
				&& ( ! isset( $attrs['password'] ) || '' === trim( (string) $attrs['password'] ) ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * 密码通过令牌。只把 HMAC 写进 cookie，绝不把明文密码下发到前台。
 * $lock 非空时按「区块专属密码」隔离：不同密码的锁不会互相放行。
 *
 * @param int    $post_id  文章 ID。
 * @param string $password 生效密码。
 * @param string $lock     可选，区块锁标识。
 * @return string
 */
function bang_unlock_pass_token( $post_id, $password, $lock = '' ) {
	$key = 'bang_unlock_pass|' . (int) $post_id . '|' . (string) $password;
	if ( '' !== $lock ) {
		$key .= '|' . (string) $lock;
	}
	return substr( hash_hmac( 'sha256', $key, wp_salt( 'auth' ) ), 0, 32 );
}

/**
 * 密码 cookie 名。带锁标识时按锁隔离，避免一个区块的密码解开另一个区块。
 *
 * @param int    $post_id 文章 ID。
 * @param string $lock    可选，区块锁标识。
 * @return string
 */
function bang_unlock_pass_cookie_name( $post_id, $lock = '' ) {
	if ( '' !== $lock ) {
		return 'bang_unlock_p_' . substr( preg_replace( '/[^a-f0-9]/', '', (string) $lock ), 0, 16 );
	}
	return 'bang_unlock_p_' . (int) $post_id;
}

/**
 * 游客也能用：校验签名 cookie 是否等于本文 / 本锁密码的令牌。改密码后旧 cookie 立即失效。
 *
 * @param int    $post_id 文章 ID。
 * @param string $lock    可选，区块锁标识。
 * @return bool
 */
function bang_unlock_pass_ok( $post_id, $lock = '' ) {
	$name = bang_unlock_pass_cookie_name( $post_id, $lock );
	$got  = isset( $_COOKIE[ $name ] ) ? (string) wp_unslash( $_COOKIE[ $name ] ) : '';
	if ( ! $got ) {
		return false;
	}
	if ( '' !== $lock ) {
		$info = bang_unlock_lock_info( $post_id, $lock );
		if ( ! $info || 'password' !== $info['method'] ) {
			return false;
		}
		$want = bang_unlock_pass_token( $post_id, $info['password'], $lock );
	} else {
		$want = bang_unlock_pass_token( $post_id, bang_unlock_password_for( $post_id ) );
	}
	return hash_equals( $want, $got );
}

/**
 * 写入 30 天有效的密码令牌。
 *
 * @param int    $post_id 文章 ID。
 * @param string $lock    可选，区块锁标识。
 */
function bang_unlock_set_pass_cookie( $post_id, $lock = '' ) {
	if ( '' !== $lock ) {
		$info = bang_unlock_lock_info( $post_id, $lock );
		if ( ! $info || 'password' !== $info['method'] ) {
			return;
		}
		$password = $info['password'];
	} else {
		$password = bang_unlock_password_for( $post_id );
	}
	$token = bang_unlock_pass_token( $post_id, $password, $lock );
	$name  = bang_unlock_pass_cookie_name( $post_id, $lock );
	setcookie(
		$name,
		$token,
		array(
			'expires'  => time() + 30 * DAY_IN_SECONDS,
			'path'     => '/',
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
	$_COOKIE[ $name ] = $token;
}

/* ============================================================
   绑定码与入群状态（全部落在 user_meta，不新建表）
   ============================================================ */
/**
 * 取（或首次生成）某用户的固定绑定码。8 位大写十六进制，碰撞会自动换盐。
 *
 * @param int $uid 用户 ID。
 * @return string
 */
function bang_unlock_code( $uid ) {
	$uid = (int) $uid;
	if ( ! $uid ) {
		return '';
	}
	$code = (string) get_user_meta( $uid, 'bang_unlock_code', true );
	if ( $code ) {
		return $code;
	}
	for ( $i = 0; $i < 8; $i++ ) {
		$try    = strtoupper( substr( wp_hash( 'bang_unlock_code|' . $uid . '|' . $i ), 0, 8 ) );
		$exists = get_users(
			array(
				'meta_key'   => 'bang_unlock_code',
				'meta_value' => $try,
				'number'     => 1,
				'fields'     => 'ids',
			)
		);
		if ( ! $exists ) {
			$code = $try;
			break;
		}
	}
	if ( ! $code ) {
		$code = strtoupper( substr( wp_hash( 'bang_unlock_code|' . $uid . '|' . microtime( true ) ), 0, 8 ) );
	}
	update_user_meta( $uid, 'bang_unlock_code', $code );
	return $code;
}

/**
 * 绑定码 → 用户 ID。
 *
 * @param string $code 绑定码。
 * @return int
 */
function bang_unlock_user_by_code( $code ) {
	$code = strtoupper( preg_replace( '/[^A-Z0-9]/', '', (string) $code ) );
	if ( strlen( $code ) !== 8 ) {
		return 0;
	}
	$ids = get_users(
		array(
			'meta_key'   => 'bang_unlock_code',
			'meta_value' => $code,
			'number'     => 1,
			'fields'     => 'ids',
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * 机器人侧用户 ID → 本站用户 ID。
 *
 * @param string $bot_uid 机器人 user_id。
 * @return int
 */
function bang_unlock_user_by_bot_uid( $bot_uid ) {
	$bot_uid = trim( (string) $bot_uid );
	if ( '' === $bot_uid ) {
		return 0;
	}
	$ids = get_users(
		array(
			'meta_key'   => 'bang_unlock_bot_uid',
			'meta_value' => $bot_uid,
			'number'     => 1,
			'fields'     => 'ids',
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * 「入群可见」的状态原因。仅用于前台文案与轮询，不代表整体是否解锁。
 *
 * @param int $uid 用户 ID，0 表示当前登录用户。
 * @return string ok | guest | left | unbound | expired
 */
function bang_unlock_reason( $uid = 0 ) {
	$uid = $uid ? (int) $uid : get_current_user_id();
	if ( ! $uid ) {
		return 'guest';
	}
	/* 0.13.22：不再有「管理员免解锁」，任何人都要走五种方式之一 */
	$state = (string) get_user_meta( $uid, 'bang_unlock_state', true );
	if ( 'left' === $state ) {
		return 'left';
	}
	if ( 'active' !== $state ) {
		return 'unbound';
	}
	$seen = (int) get_user_meta( $uid, 'bang_unlock_seen_at', true );
	if ( ! $seen || ( time() - $seen ) > bang_unlock_ttl_seconds() ) {
		return 'expired';
	}
	return 'ok';
}

/**
 * 入群方式是否已满足。
 *
 * @param int $uid 用户 ID。
 * @return bool
 */
function bang_unlock_group_ok( $uid ) {
	return 'ok' === bang_unlock_reason( $uid );
}

/**
 * 该用户是否在本文发表过已审核评论。
 *
 * @param int $uid     用户 ID。
 * @param int $post_id 文章 ID。
 * @return bool
 */
function bang_unlock_user_commented( $uid, $post_id ) {
	$uid     = (int) $uid;
	$post_id = (int) $post_id;
	if ( ! $uid || ! $post_id ) {
		return false;
	}
	$n = get_comments(
		array(
			'user_id' => $uid,
			'post_id' => $post_id,
			'status'  => 'approve',
			'type'    => 'comment',
			'count'   => true,
			'number'  => 1,
		)
	);
	return (int) $n > 0;
}

/**
 * 区块五选一，只认选定的那一种方式，绝不「任一满足」。
 * combo 是唯一的叠加方式：点赞与评论两步都要完成，密码不参与叠加。
 * 传空 $method 时回退到「登录可见」，不放大权限。
 * 鉴权只在这里做，前端拿不到任何受保护内容。
 *
 * @param int    $post_id 文章 ID，0 表示当前渲染的文章。
 * @param string $method  login | like | comment | password | combo。
 * @param string $lock    密码方式下的区块锁标识（专属密码）。
 * @return bool
 */
function bang_unlock_can_read( $post_id = 0, $method = '', $lock = '' ) {
	$post_id = $post_id ? (int) $post_id : bang_unlock_current_post();
	if ( ! $post_id ) {
		return false;
	}
	$uid = get_current_user_id();

	switch ( bang_unlock_normalize_method( $method ) ) {
		case 'password':
			return bang_unlock_pass_ok( $post_id, (string) $lock );
		case 'combo':
			/* 叠加解锁：点赞 AND 已审核评论。两步本身都要求登录态 */
			if ( ! $uid ) {
				return false;
			}
			$likes = get_user_meta( $uid, 'bang_likes', true );
			$liked = is_array( $likes ) && in_array( $post_id, array_map( 'intval', $likes ), true );
			return $liked && bang_unlock_user_commented( $uid, $post_id );
		case 'like':
			if ( ! $uid ) {
				return false;
			}
			/* 直接读 user_meta，不依赖 inc/social.php 的加载顺序 */
			$likes = get_user_meta( $uid, 'bang_likes', true );
			return is_array( $likes ) && in_array( $post_id, array_map( 'intval', $likes ), true );
		case 'comment':
			return (bool) $uid && bang_unlock_user_commented( $uid, $post_id );
		case 'login':
		default:
			return (bool) $uid;
	}
}

/**
 * 文章里实际声明了哪些解锁方式。用于 /redeem 拒绝「本文根本没用这种方式」的兑换，
 * 避免读者给 A 文章刷点赞、密码，再去尝试影响别的判定。
 *
 * 只看静态源码：区块注释里的 method、简码的 method、整篇锁的 bang_unlock_method。
 * 解析不到任何锁时返回空数组（此时不做额外限制，兼容动态插入的内容）。
 *
 * @param int $post_id 文章 ID。
 * @return string[]
 */
function bang_unlock_post_methods( $post_id ) {
	$post_id = (int) $post_id;
	$methods = array();
	if ( get_post_meta( $post_id, 'bang_unlock_post', true ) ) {
		$methods[] = bang_unlock_post_method( $post_id );
	}
	$content = (string) get_post_field( 'post_content', $post_id );
	if ( preg_match_all( '/<!--\s*wp:bang\/(?:unlock|download)\s+(\{.*?\})\s*\/?-->/s', $content, $bm ) ) {
		foreach ( $bm[1] as $json ) {
			$attrs = json_decode( $json, true );
			$methods[] = bang_unlock_normalize_method( is_array( $attrs ) && isset( $attrs['method'] ) ? $attrs['method'] : 'login' );
		}
	}
	if ( preg_match_all( '/\[(?:unlock|download)([^\]]*)\]/i', $content, $sm ) ) {
		foreach ( $sm[1] as $raw ) {
			if ( preg_match( '/\bmethod\s*=\s*["\']?([a-z]+)/i', $raw, $mm ) ) {
				$methods[] = bang_unlock_normalize_method( $mm[1] );
			} else {
				$methods[] = 'login';
			}
		}
	}
	return array_values( array_unique( $methods ) );
}

/**
 * 前台状态查询用的完整数据。五种方式各自给出独立布尔值，
 * 卡片只认自己 data-bang-unlock-method 对应的那一个，不串味。
 *
 * @param int    $post_id   文章 ID。
 * @param string $method    可选，指定查询某一种方式；不传则取文章声明的第一种方式。
 * @param string $locks_raw 逗号分隔的区块专属密码锁标识，按锁单独判定。
 * @return array
 */
function bang_unlock_status_data( $post_id = 0, $method = '', $locks_raw = '' ) {
	$uid      = get_current_user_id();
	$post_id  = $post_id ? (int) $post_id : bang_unlock_current_post();
	$declared = $post_id ? bang_unlock_post_methods( $post_id ) : array();
	if ( '' !== $method ) {
		$method = bang_unlock_normalize_method( $method );
	} elseif ( $declared ) {
		$method = $declared[0];
	} else {
		/* 没有文章上下文（外部直接调 /status）时只反映登录状态 */
		$method = 'login';
	}
	$by = array();
	foreach ( bang_unlock_ways() as $m ) {
		$by[ $m ] = $post_id ? bang_unlock_can_read( $post_id, $m ) : ( 'login' === $m && (bool) $uid );
	}
	/* 区块专属密码按锁标识逐个判定，不同密码的锁互不串味 */
	$byLock = array();
	if ( $post_id && '' !== $locks_raw ) {
		$locks = array_unique( array_filter( array_map( 'sanitize_key', explode( ',', (string) $locks_raw ) ) ) );
		foreach ( $locks as $l ) {
			$info         = bang_unlock_lock_info( $post_id, $l );
			$byLock[ $l ] = ( $info && 'password' === $info['method'] ) ? bang_unlock_pass_ok( $post_id, $l ) : false;
		}
	}
	return array(
		'active'         => isset( $by[ $method ] ) ? $by[ $method ] : false,
		'activeByMethod' => $by,
		'activeByLock'   => $byLock,
		'method'         => $method,
		'declared'       => $declared,
		'logged'         => (bool) $uid,
		'ways'           => bang_unlock_ways(),
		'postId'         => (int) $post_id,
		'time'           => time(),
	);
}

/* ============================================================
   0.13.24：群绑定资料（个人中心 /status 共用）
   ============================================================ */
/**
 * 当前用户的机器人绑定资料。只读自己那份，绝不输出回调密钥。
 *
 * joined_at 优先用平台回传的入群时间；平台没给时回退到首次绑定时间，
 * 并用 joined_from 标明来源，避免把「绑定时间」当成真实进群时间展示。
 *
 * @param int $uid 用户 ID，0 表示当前登录用户。
 * @return array
 */
function bang_unlock_binding_data( $uid = 0 ) {
	$uid = $uid ? (int) $uid : get_current_user_id();
	if ( ! $uid ) {
		return array(
			'bot_uid'     => '',
			'nickname'    => '',
			'group_id'    => '',
			'bound_at'    => 0,
			'joined_at'   => 0,
			'joined_from' => '',
			'seen_at'     => 0,
			'state'       => '',
			'reason'      => 'guest',
			'code'        => '',
		);
	}
	$joined_at = (int) get_user_meta( $uid, 'bang_unlock_joined_at', true );
	$bound_at  = (int) get_user_meta( $uid, 'bang_unlock_bound_at', true );
	return array(
		'bot_uid'     => (string) get_user_meta( $uid, 'bang_unlock_bot_uid', true ),
		'nickname'    => (string) get_user_meta( $uid, 'bang_unlock_nickname', true ),
		'group_id'    => (string) get_user_meta( $uid, 'bang_unlock_group_id', true ),
		'bound_at'    => $bound_at,
		'joined_at'   => $joined_at ? $joined_at : $bound_at,
		'joined_from' => $joined_at ? 'platform' : ( $bound_at ? 'bind' : '' ),
		'seen_at'     => (int) get_user_meta( $uid, 'bang_unlock_seen_at', true ),
		'state'       => (string) get_user_meta( $uid, 'bang_unlock_state', true ),
		'reason'      => bang_unlock_reason( $uid ),
		'code'        => bang_unlock_code( $uid ),
	);
}

/**
 * 个人中心「群绑定」卡片：QQ / 机器人 ID、群内昵称、进群时间、最近群内活跃。
 * 未绑定时给绑定码与进群入口，让读者可以在这里完成绑定。
 *
 * @param int $uid 用户 ID。
 * @return string
 */
function bang_unlock_account_card_html( $uid = 0 ) {
	$uid = $uid ? (int) $uid : get_current_user_id();
	if ( ! $uid ) {
		return '';
	}
	/* 和快捷登录一样：后台没配机器人回调密钥时绑定链路走不通（回调 503），不显示群绑定卡片 */
	if ( ! bang_unlock_secret() ) {
		return '';
	}
	$b     = bang_unlock_binding_data( $uid );
	$group = trim( (string) bang_opt( 'unlock_group_name' ) );
	$group = '' !== $group ? $group : '交流群';
	$url   = trim( (string) bang_opt( 'unlock_group_url' ) );
	$bot   = trim( (string) bang_opt( 'unlock_bot_name' ) );
	$bot   = '' !== $bot ? $bot : '机器人';
	$state = array(
		'ok'      => '已入群',
		'left'    => '已退群',
		'expired' => '状态已过期',
		'unbound' => '待绑定',
		'guest'   => '未登录',
	);
	$reason = isset( $state[ $b['reason'] ] ) ? $b['reason'] : 'unbound';

	$date = function ( $ts ) {
		return $ts ? date_i18n( 'Y.m.d H:i', (int) $ts ) : '—';
	};
	$joined = $date( $b['joined_at'] );
	if ( $b['joined_at'] && 'bind' === $b['joined_from'] ) {
		$joined .= '<small class="user-unlock-src">按首次绑定记录</small>';
	}
	$desc = 'left' === $reason
		? '检测到你已不在' . $group . '内，重新进群并在群里发言即可恢复解锁。'
		: ( 'ok' === $reason
			? '绑定生效中，站内「入群可见」的内容会自动解锁。'
			: '在' . $group . '里把下面的绑定码发给' . $bot . '，绑定成功后即可解锁「入群可见」的内容。' );

	$html  = '<section class="user-unlock" aria-labelledby="bang-unlock-account-title">';
	$html .= '<div class="user-unlock-head">'
		. '<h2 class="user-unlock-title" id="bang-unlock-account-title">'
		. bang_unlock_icon( 'users', 16 ) . '群绑定</h2>'
		. '<span class="user-unlock-state is-' . esc_attr( $reason ) . '">' . esc_html( $state[ $reason ] ) . '</span>'
		. '</div>';
	$html .= '<p class="user-unlock-desc">' . esc_html( $desc ) . '</p>';
	$html .= '<dl class="user-unlock-grid">';
	$rows  = array(
		'QQ / 机器人 ID' => $b['bot_uid'] ? esc_html( $b['bot_uid'] ) : '—',
		'群内昵称'       => $b['nickname'] ? esc_html( $b['nickname'] ) : '未获取',
		'进群时间'       => $joined,
		'最近群内活跃'   => esc_html( $date( $b['seen_at'] ) ),
	);
	foreach ( $rows as $k => $v ) {
		$html .= '<div class="user-unlock-item"><dt>' . esc_html( $k ) . '</dt><dd>' . $v . '</dd></div>';
	}
	$html .= '</dl>';
	$html .= '<div class="user-unlock-code">'
		. '<span class="user-unlock-code-label">我的绑定码</span>'
		. '<code class="user-unlock-code-val">' . esc_html( $b['code'] ) . '</code>'
		. '<a class="user-unlock-copy" href="#" data-bang-unlock-copy="' . esc_attr( $b['code'] ) . '">复制</a>'
		. '</div>';
	if ( $url ) {
		$html .= '<a class="user-unlock-join" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">'
			. esc_html( '加入' . $group ) . '</a>';
	}
	$html .= '</section>';
	return $html;
}

/* ============================================================
   整篇锁：文章手动标记 bang_unlock_post = 1
   ============================================================ */
/**
 * 判断一篇文章是否需要整篇上锁。0.13.22 起不再有「全站锁所有文章」开关，
 * 只认文章自定义字段，避免误伤站点里其它内容。
 *
 * @param int $post_id 文章 ID。
 * @return bool
 */
function bang_unlock_post_locked( $post_id ) {
	$post_id = (int) $post_id;
	if ( ! $post_id || 'post' !== get_post_type( $post_id ) ) {
		return false;
	}
	if ( bang_unlock_can_read( $post_id, bang_unlock_post_method( $post_id ) ) ) {
		return false;
	}
	return (bool) get_post_meta( $post_id, 'bang_unlock_post', true );
}

/**
 * 卡片底部按入群状态给出的补充说明。
 *
 * @param string $reason 状态。
 * @return string
 */
function bang_unlock_reason_text( $reason ) {
	$group = trim( (string) bang_opt( 'unlock_group_name' ) );
	$group = '' !== $group ? $group : '交流群';
	$map   = array(
		'guest'   => '先登录获取绑定码，再进群把绑定码发给机器人。',
		'unbound' => '把绑定码发给机器人完成绑定后阅读。',
		'left'    => '入群状态：检测到你已不在' . $group . '，请重新进群。',
		'expired' => '入群状态已过期，请回到' . $group . '发一条消息，或直接重新进群。',
		'ok'      => '入群状态有效。',
	);
	return isset( $map[ $reason ] ) ? $map[ $reason ] : $map['unbound'];
}

/**
 * 锁定文章在 REST / feed / 摘要里的替换文案。按文章选定的方式给，
 * 五选一之后没有其它方式可以替代。
 *
 * @param int $post_id 文章 ID。
 * @return string
 */
function bang_unlock_lock_tip( $post_id ) {
	$declared = bang_unlock_post_methods( $post_id );
	$method   = $declared ? bang_unlock_normalize_method( $declared[0] ) : 'login';
	$map      = array(
		'login'    => '本文需登录后阅读。',
		'like'     => '本文需给文章点赞后阅读。',
		'comment'  => '本文需发表一条评论后阅读。',
		'password' => '本文需输入解锁密码后阅读。',
	);
	return isset( $map[ $method ] ) ? $map[ $method ] : $map['login'];
}

/* ============================================================
   锁定卡片：只渲染作者选定的那一种方式
   ============================================================ */
/**
 * 锁定卡片 HTML。前台所有「未解锁」出口都复用这一份。
 * 五选一：卡片只输出一种方式的图标、说明与按钮，并把方式写进
 * data-bang-unlock-method，服务端判定与前台兑换都只认这个值。
 *
 * 交互元素一律用 <a> 与 <input>：正文会经过 wp_kses_post()，<button>/<form> 会被剥掉，
 * 已在 bang_unlock_allowed_html() 里放行 input 与 data-* 属性。
 *
 * @param array $args {title,desc,teaser,reason,compact,post_id,method,lock}。
 * @return string
 */
function bang_unlock_card_html( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'title'   => '',
			'desc'    => '',
			'teaser'  => '',
			'reason'  => '',
			'compact' => false,
			'post_id' => bang_unlock_current_post(),
			'method'  => 'login',
			'lock'    => '',
		)
	);
	$post_id = (int) $args['post_id'];
	$method  = bang_unlock_normalize_method( $args['method'] );
	$labels  = bang_unlock_method_labels();
	$label   = $labels[ $method ];
	$reason  = (string) $args['reason'];
	$lock    = '' !== trim( (string) $args['lock'] ) ? sanitize_key( (string) $args['lock'] ) : '';
	$title   = trim( (string) $args['title'] );
	$desc    = trim( (string) $args['desc'] );
	$hint    = trim( (string) bang_opt( 'unlock_hint' ) );
	$pw_text = trim( (string) bang_opt( 'unlock_password_text' ) );
	$teaser  = trim( (string) $args['teaser'] );

	$html = '<div class="bang-unlock bang-unlock--' . esc_attr( $method )
		. ( $args['compact'] ? ' bang-unlock--compact' : '' ) . '"'
		. ' data-bang-unlock="1" data-bang-unlock-poll="1" data-bang-unlock-post="' . esc_attr( $post_id ) . '"'
		. ' data-bang-unlock-method="' . esc_attr( $method ) . '"'
		. ( $lock ? ' data-bang-unlock-lock="' . esc_attr( $lock ) . '"' : '' )
		. ' data-bang-unlock-reason="' . esc_attr( $reason ) . '">';

	if ( $teaser ) {
		$html .= '<div class="bang-unlock-teaser">' . wp_kses_post( $teaser ) . '</div>';
	}

	$html .= '<div class="bang-unlock-head">'
		. '<span class="bang-unlock-badge">' . bang_unlock_icon( 'lock', 18 ) . '</span>'
		. '<div class="bang-unlock-titles">'
		. '<strong class="bang-unlock-title">' . esc_html( '' !== $title ? $title : '内容已锁定' ) . '</strong>'
		. '<span class="bang-unlock-sub">' . esc_html( '' !== $desc ? $desc : '使用「' . $label . '」解锁后继续阅读' ) . '</span>'
		. '</div></div>';

	if ( $hint ) {
		$html .= '<p class="bang-unlock-hint">' . esc_html( $hint ) . '</p>';
	}

	$html .= '<div class="bang-unlock-ways">';

	$icon = array(
		'login'    => 'users',
		'like'     => 'heart',
		'comment'  => 'message',
		'password' => 'key',
		'combo'    => 'check',
	);
	$html .= '<div class="bang-unlock-way bang-unlock-way--' . esc_attr( $method ) . '">'
		. '<span class="bang-unlock-way-ico">' . bang_unlock_icon( $icon[ $method ], 17 ) . '</span>'
		. '<div class="bang-unlock-way-body">'
		. '<strong class="bang-unlock-way-t">' . esc_html( $label ) . '</strong>';

	if ( 'login' === $method ) {
		$html .= '<span class="bang-unlock-way-d">登录后即可阅读这段内容</span>'
			. '</div>'
			. '<a class="bang-unlock-way-btn bang-unlock-way-btn--primary" href="#" data-bang-unlock-way="login">去登录</a>';
	} elseif ( 'like' === $method ) {
		$html .= '<span class="bang-unlock-way-d">给这篇文章点个赞，立即解锁</span>'
			. '</div>'
			. '<a class="bang-unlock-way-btn bang-unlock-way-btn--primary" href="#" data-bang-unlock-way="like">点赞解锁</a>';
	} elseif ( 'password' === $method ) {
		$html .= '<span class="bang-unlock-way-d">' . esc_html( $lock ? '输入本文设置的解锁密码' : '输入站长提供的解锁密码' ) . '</span>';
		if ( '' !== $pw_text ) {
			$html .= '<span class="bang-unlock-pass-custom">' . nl2br( esc_html( $pw_text ) ) . '</span>';
		}
		$html .= '<span class="bang-unlock-pass-row">'
			. '<input type="password" class="bang-unlock-pass-input" data-bang-unlock-pass-input'
			. ' placeholder="解锁密码" maxlength="64" autocomplete="off" aria-label="解锁密码">'
			. '<a class="bang-unlock-way-btn bang-unlock-way-btn--primary" href="#" data-bang-unlock-way="password">解锁</a>'
			. '</span>'
			. '</div>';
	} elseif ( 'combo' === $method ) {
		/* 叠加解锁：点赞 + 评论两个按钮，两步都要完成；不提供密码叠加 */
		$html .= '<span class="bang-unlock-way-d">先点赞，再发表一条评论，两步都完成后自动解锁</span>'
			. '<span class="bang-unlock-combo-row">'
			. '<a class="bang-unlock-way-btn" href="#" data-bang-unlock-way="combo" data-bang-unlock-combo="like">去点赞</a>'
			. '<a class="bang-unlock-way-btn bang-unlock-way-btn--primary" href="#" data-bang-unlock-way="combo" data-bang-unlock-combo="comment">去评论</a>'
			. '</span>'
			. '</div>';
	} else {
		$html .= '<span class="bang-unlock-way-d">发表一条评论后自动解锁</span>'
			. '</div>'
			. '<a class="bang-unlock-way-btn bang-unlock-way-btn--primary" href="#" data-bang-unlock-way="comment">去评论</a>';
	}
	$html .= '</div>';

	$html .= '</div>';
	$html .= '<p class="bang-unlock-note" data-bang-unlock-note>' . esc_html( bang_unlock_way_note( $method, $reason ) ) . '</p>';
	$html .= '</div>';
	return $html;
}

/* ============================================================
   REST：机器人回调 + 五种方式兑换 + 前台状态轮询
   ============================================================ */
/**
 * 生成回调签名。机器人插件必须用完全相同的拼接顺序。
 *
 * @param string $action   bind | heartbeat | leave。
 * @param string $code     绑定码（heartbeat/leave 可空）。
 * @param string $bot_uid  机器人侧用户 ID。
 * @param string $group_id 群 ID。
 * @param int    $ts       时间戳。
 * @return string
 */
function bang_unlock_sign( $action, $code, $bot_uid, $group_id, $ts, $nickname = '', $joined_at = 0 ) {
	$parts     = array( (string) $action, (string) $code, (string) $bot_uid, (string) $group_id, (int) $ts );
	$joined_at = (int) $joined_at;
	/* 0.13.24：带群资料的回调走扩展签名（末尾追加昵称与入群时间），
	   不带的仍按 0.13.20 五段签名，保证旧版 WeBot 插件无需升级也能继续绑定。 */
	if ( '' !== (string) $nickname || $joined_at ) {
		$parts[] = (string) $nickname;
		$parts[] = $joined_at;
	}
	return hash_hmac( 'sha256', implode( '|', $parts ), bang_unlock_secret() );
}

/**
 * 机器人给的时间可能是秒、毫秒或日期字符串，统一成 2000~2100 之间的 Unix 秒。
 *
 * @param mixed $ts 原始值。
 * @return int
 */
function bang_unlock_normalize_ts( $ts ) {
	if ( is_string( $ts ) && ! ctype_digit( $ts ) ) {
		$parsed = strtotime( $ts );
		$ts     = $parsed ? $parsed : 0;
	}
	$ts = (int) $ts;
	if ( $ts > 2000000000 ) {
		$ts = (int) floor( $ts / 1000 ); /* 毫秒 */
	}
	return ( $ts >= 946684800 && $ts <= 4102444800 ) ? $ts : 0;
}

/**
 * 校验机器人回调：密钥、时间窗、签名。
 *
 * @param WP_REST_Request $request 请求。
 * @param string          $action  动作。
 * @return true|WP_Error
 */
function bang_unlock_verify( $request, $action ) {
	if ( ! bang_unlock_secret() ) {
		return new WP_Error( 'bang_unlock_no_secret', '站点未配置回调密钥', array( 'status' => 503 ) );
	}
	$ts = (int) $request->get_param( 'ts' );
	if ( ! $ts || abs( time() - $ts ) > 300 ) {
		return new WP_Error( 'bang_unlock_stale', '请求已过期', array( 'status' => 403 ) );
	}
	$code     = strtoupper( sanitize_text_field( (string) $request->get_param( 'code' ) ) );
	$bot_uid  = sanitize_text_field( (string) $request->get_param( 'bot_uid' ) );
	$group_id = sanitize_text_field( (string) $request->get_param( 'group_id' ) );
	$sign       = (string) $request->get_param( 'sign' );
	$nickname   = sanitize_text_field( (string) $request->get_param( 'nickname' ) );
	/* 签名必须覆盖「实际传输的值」：毫秒 / 日期字符串原样参与验签，
	   归一化只在落库时做。否则传毫秒的调用方签名会与服务端算出来的不一致。 */
	$joined_raw = (string) $request->get_param( 'joined_at' );
	/* 0.13.24：只要请求带了群资料，就必须用扩展签名，防止合法请求被重放后
	   展示字段遭篡改；不带资料的旧插件继续走五段签名。 */
	$expect = ( '' !== $nickname || '' !== $joined_raw )
		? bang_unlock_sign( $action, $code, $bot_uid, $group_id, $ts, $nickname, $joined_raw )
		: bang_unlock_sign( $action, $code, $bot_uid, $group_id, $ts );
	if ( ! hash_equals( $expect, $sign ) ) {
		return new WP_Error( 'bang_unlock_sign', '签名校验失败', array( 'status' => 403 ) );
	}
	return true;
}

/**
 * 注册路由。
 */
function bang_unlock_rest_routes() {
	$ns = 'bang/v1/unlock';

	register_rest_route(
		'bang/v1',
		'/unlock/ping',
		array(
			'methods'             => 'GET',
			'callback'            => function () {
				return array(
					'ok'      => true,
					'version' => BANG_VERSION,
					'enabled' => bang_unlock_enabled(),
				);
			},
			'permission_callback' => '__return_true',
		)
	);

	foreach ( array( 'bind', 'heartbeat', 'leave' ) as $action ) {
		register_rest_route(
			'bang/v1',
			'/unlock/' . $action,
			array(
				'methods'             => 'POST',
				'callback'            => 'bang_unlock_rest_callback',
				'permission_callback' => '__return_true',
				'args'                => array(
					'action' => array( 'default' => $action ),
				),
			)
		);
	}

	register_rest_route(
		'bang/v1',
		'/unlock/redeem',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_unlock_rest_redeem',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'bang/v1',
		'/unlock/status',
		array(
			'methods'             => 'GET',
			'callback'            => function ( $request ) {
				return bang_unlock_status_data(
					(int) $request->get_param( 'post_id' ),
					(string) $request->get_param( 'method' ),
					(string) $request->get_param( 'locks' )
				);
			},
			/* 未登录也返回结构化的 guest 状态，前端据此显示登录引导 */
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'bang_unlock_rest_routes' );

/**
 * 机器人回调统一入口。
 *
 * @param WP_REST_Request $request 请求。
 * @return array|WP_Error
 */
function bang_unlock_rest_callback( $request ) {
	$route  = (string) $request->get_route();
	$action = '';
	foreach ( array( 'bind', 'heartbeat', 'leave' ) as $a ) {
		if ( false !== strpos( $route, '/unlock/' . $a ) ) {
			$action = $a;
			break;
		}
	}
	if ( ! $action ) {
		return new WP_Error( 'bang_unlock_route', '未知动作', array( 'status' => 404 ) );
	}

	$verified = bang_unlock_verify( $request, $action );
	if ( is_wp_error( $verified ) ) {
		return $verified;
	}

	$code     = strtoupper( sanitize_text_field( (string) $request->get_param( 'code' ) ) );
	$bot_uid  = sanitize_text_field( (string) $request->get_param( 'bot_uid' ) );
	$group_id  = sanitize_text_field( (string) $request->get_param( 'group_id' ) );
	/* 0.13.24：机器人顺带回传的群内昵称与进群时间，只用于个人中心展示，不参与解锁判定 */
	$nickname  = sanitize_text_field( (string) $request->get_param( 'nickname' ) );
	$joined_at = bang_unlock_normalize_ts( $request->get_param( 'joined_at' ) );

	if ( 'bind' === $action ) {
		$uid = bang_unlock_user_by_code( $code );
		if ( ! $uid ) {
			return new WP_Error( 'bang_unlock_bad_code', '绑定码无效，请到网站重新获取', array( 'status' => 404 ) );
		}
		if ( '' === $bot_uid ) {
			return new WP_Error( 'bang_unlock_no_uid', '缺少机器人用户 ID', array( 'status' => 400 ) );
		}
		/* 同一个机器人账号改绑到新站点账号时，旧账号立即失效，避免一号多人共用 */
		$other = bang_unlock_user_by_bot_uid( $bot_uid );
		if ( $other && $other !== $uid ) {
			update_user_meta( $other, 'bang_unlock_state', 'left' );
			delete_user_meta( $other, 'bang_unlock_bot_uid' );
		}
		update_user_meta( $uid, 'bang_unlock_bot_uid', $bot_uid );
		update_user_meta( $uid, 'bang_unlock_group_id', $group_id );
		update_user_meta( $uid, 'bang_unlock_bound_at', time() );
		if ( '' !== $nickname ) {
			update_user_meta( $uid, 'bang_unlock_nickname', $nickname );
		}
		if ( $joined_at ) {
			update_user_meta( $uid, 'bang_unlock_joined_at', $joined_at );
		}
		/* 绑定动作本身就发生在群里，直接记一次心跳 */
		update_user_meta( $uid, 'bang_unlock_seen_at', time() );
		update_user_meta( $uid, 'bang_unlock_state', 'active' );
		return array( 'ok' => true, 'uid' => $uid, 'code' => $code );
	}

	$uid = bang_unlock_user_by_bot_uid( $bot_uid );
	if ( ! $uid ) {
		/* 群里绝大多数成员没绑定过，静默忽略即可，不打扰 */
		return array( 'ok' => true, 'ignored' => true );
	}

	if ( 'leave' === $action ) {
		update_user_meta( $uid, 'bang_unlock_state', 'left' );
		delete_user_meta( $uid, 'bang_unlock_seen_at' );
		return array( 'ok' => true, 'uid' => $uid, 'state' => 'left' );
	}

	/* heartbeat：群内活跃即续期；曾被标记退群的人回群发言会自动恢复 */
	update_user_meta( $uid, 'bang_unlock_seen_at', time() );
	update_user_meta( $uid, 'bang_unlock_state', 'active' );
	/* 群名片可能改过，每次心跳用最新昵称刷新；入群时间只在首次拿到时写入 */
	if ( '' !== $nickname ) {
		update_user_meta( $uid, 'bang_unlock_nickname', $nickname );
	}
	if ( $joined_at && ! get_user_meta( $uid, 'bang_unlock_joined_at', true ) ) {
		update_user_meta( $uid, 'bang_unlock_joined_at', $joined_at );
	}
	if ( $group_id ) {
		update_user_meta( $uid, 'bang_unlock_group_id', $group_id );
	}
	return array( 'ok' => true, 'uid' => $uid, 'state' => 'active' );
}

/**
 * 五种方式的兑换入口。成功只返回标记，受保护内容仍由服务端整页重新渲染输出。
 * 密码可见允许游客兑换（签名 cookie 记 30 天），必须放在登录检查之前；
 * 登录可见不需要兑换（已登录即放行），仅保留空成功分支兼容前端。
 *
 * @param WP_REST_Request $request 请求。
 * @return array|WP_Error
 */
function bang_unlock_rest_redeem( $request ) {
	/* 前台带 X-WP-Nonce；显式校验一次，不依赖 REST 的自动认证顺序 */
	$nonce = (string) $request->get_header( 'X-WP-Nonce' );
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		return new WP_Error( 'bang_unlock_nonce', '页面已过期，刷新后再试', array( 'status' => 403 ) );
	}
	$method  = sanitize_key( (string) $request->get_param( 'method' ) );
	$post_id = (int) $request->get_param( 'post_id' );
	if ( ! in_array( $method, bang_unlock_ways(), true ) ) {
		return new WP_Error( 'bang_unlock_method', '未知解锁方式', array( 'status' => 400 ) );
	}
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type ) {
		return new WP_Error( 'bang_unlock_post', '文章不存在', array( 'status' => 404 ) );
	}
	/* 五选一：本文没声明这种方式，就不允许兑换，避免用 A 方式去碰 B 方式的锁 */
	$declared = bang_unlock_post_methods( $post_id );
	if ( $declared && ! in_array( $method, $declared, true ) ) {
		return new WP_Error( 'bang_unlock_method_mismatch', '本文未使用这种解锁方式', array( 'status' => 400 ) );
	}

	if ( 'password' === $method ) {
		/* 文章里只有「区块专属密码」时，站点密码不能越权兑换 */
		$lock = sanitize_key( (string) $request->get_param( 'lock' ) );
		if ( '' === $lock && bang_unlock_locks_for( $post_id ) && ! bang_unlock_has_plain_password_lock( $post_id ) ) {
			return new WP_Error( 'bang_unlock_lock_required', '请通过文章里的密码框解锁', array( 'status' => 400 ) );
		}
		/* 简单防爆破：同一 IP 5 分钟内最多 10 次错误 */
		$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key  = 'bang_unlock_fail_' . substr( wp_hash( 'bang_unlock_fail|' . $ip ), 0, 12 );
		$fail = (int) get_transient( $key );
		if ( $fail >= 10 ) {
			return new WP_Error( 'bang_unlock_locked', '尝试次数过多，请稍后再试', array( 'status' => 429 ) );
		}
		$given = (string) $request->get_param( 'password' );
		if ( '' !== $lock ) {
			/* 区块专属密码：锁标识必须在本文的锁表里，且该锁确实是密码方式 */
			$info = bang_unlock_lock_info( $post_id, $lock );
			if ( ! $info || 'password' !== $info['method'] ) {
				return new WP_Error( 'bang_unlock_lock', '这个密码锁不存在', array( 'status' => 400 ) );
			}
			$want = $info['password'];
		} else {
			/* 区块没填专属密码时用站点 / 文章密码 */
			$want = bang_unlock_password_for( $post_id );
		}
		if ( '' === $given || ! hash_equals( $want, $given ) ) {
			set_transient( $key, $fail + 1, 5 * MINUTE_IN_SECONDS );
			return new WP_Error( 'bang_unlock_bad_password', '解锁密码不正确', array( 'status' => 403 ) );
		}
		delete_transient( $key );
		bang_unlock_set_pass_cookie( $post_id, $lock );
		return array( 'ok' => true, 'unlocked' => true, 'method' => 'password', 'lock' => $lock );
	}

	$uid = get_current_user_id();
	if ( ! $uid ) {
		return new WP_Error( 'bang_unlock_login', '请先登录后再解锁', array( 'status' => 401 ) );
	}

	/* combo：叠加解锁，两步都要完成；点赞由前台按钮代做，评论必须先发且已审核。
	   密码不参与叠加，comment 子动作也不会代发评论，避免刷屏。 */
	if ( 'combo' === $method ) {
		$sub = sanitize_key( (string) $request->get_param( 'combo' ) );
		if ( 'like' === $sub ) {
			$likes = get_user_meta( $uid, 'bang_likes', true );
			if ( ! is_array( $likes ) ) {
				$likes = array();
			}
			$likes = array_values( array_unique( array_filter( array_map( 'intval', $likes ) ) ) );
			if ( ! in_array( $post_id, $likes, true ) ) {
				$likes[] = $post_id;
				update_user_meta( $uid, 'bang_likes', $likes );
				$count = max( 0, (int) get_post_meta( $post_id, 'bang_like_count', true ) + 1 );
				update_post_meta( $post_id, 'bang_like_count', $count );
			}
			if ( bang_unlock_user_commented( $uid, $post_id ) ) {
				return array( 'ok' => true, 'unlocked' => true, 'method' => 'combo' );
			}
			return array( 'ok' => false, 'need' => 'comment', 'message' => '已点赞，再发表一条评论即可解锁' );
		}
		if ( bang_unlock_user_commented( $uid, $post_id ) ) {
			return array( 'ok' => true, 'unlocked' => true, 'method' => 'combo' );
		}
		return array( 'ok' => false, 'need' => 'comment', 'message' => '先在本文发表一条评论，发布后会自动解锁' );
	}

	/* login：已登录即放行，回到前台直接刷新即可 */
	if ( 'login' === $method ) {
		return array( 'ok' => true, 'unlocked' => true, 'method' => 'login' );
	}

	if ( 'like' === $method ) {
		$likes = get_user_meta( $uid, 'bang_likes', true );
		if ( ! is_array( $likes ) ) {
			$likes = array();
		}
		$likes = array_values( array_unique( array_filter( array_map( 'intval', $likes ) ) ) );
		if ( ! in_array( $post_id, $likes, true ) ) {
			$likes[] = $post_id;
			update_user_meta( $uid, 'bang_likes', $likes );
			$count = max( 0, (int) get_post_meta( $post_id, 'bang_like_count', true ) + 1 );
			update_post_meta( $post_id, 'bang_like_count', $count );
		}
		return array( 'ok' => true, 'unlocked' => true, 'method' => 'like' );
	}

	/* comment：不代发评论，避免刷屏；已有已审核评论即放行，否则引导去评论区 */
	if ( bang_unlock_user_commented( $uid, $post_id ) ) {
		return array( 'ok' => true, 'unlocked' => true, 'method' => 'comment' );
	}
	return array( 'ok' => false, 'need' => 'comment', 'message' => '先在本文发表一条评论，发布后会自动解锁' );
}

/* ============================================================
   防泄露：REST / feed / 首页内联 payload 都不许带受保护正文
   ============================================================ */
/**
 * 核心 REST 的文章输出，锁定时把 content / excerpt 换成提示。
 *
 * @param WP_REST_Response $response 响应。
 * @param WP_Post          $post     文章。
 * @param WP_REST_Request  $request  请求。
 * @return WP_REST_Response
 */
function bang_unlock_rest_prepare_post( $response, $post, $request ) {
	/* 后台编辑器 context=edit 必须拿原文，否则无法编辑 */
	if ( 'edit' === $request->get_param( 'context' ) ) {
		return $response;
	}
	if ( ! bang_unlock_post_locked( $post->ID ) ) {
		return $response;
	}
	$data             = $response->get_data();
	$tip              = bang_unlock_lock_tip( $post->ID );
	$data['content']  = isset( $data['content'] ) && is_array( $data['content'] )
		? array_merge( $data['content'], array( 'rendered' => '<p>' . esc_html( $tip ) . '</p>', 'protected' => true ) )
		: array( 'rendered' => '<p>' . esc_html( $tip ) . '</p>', 'protected' => true );
	$data['excerpt']  = isset( $data['excerpt'] ) && is_array( $data['excerpt'] )
		? array_merge( $data['excerpt'], array( 'rendered' => '<p>' . esc_html( $tip ) . '</p>' ) )
		: array( 'rendered' => '<p>' . esc_html( $tip ) . '</p>' );
	$response->set_data( $data );
	return $response;
}
add_filter( 'rest_prepare_post', 'bang_unlock_rest_prepare_post', 12, 3 );

/**
 * RSS / Atom 输出。
 *
 * @param string $content 正文。
 * @return string
 */
function bang_unlock_feed_content( $content ) {
	if ( bang_unlock_post_locked( get_the_ID() ) ) {
		return '<p>' . esc_html( bang_unlock_lock_tip( get_the_ID() ) ) . '</p>';
	}
	return $content;
}
add_filter( 'the_content_feed', 'bang_unlock_feed_content', 20 );

/**
 * 首页 #bang-data 内联的卡片摘要。锁定时不取正文，避免从搜索字段里捞出片段。
 *
 * @param string $excerpt 原摘要。
 * @param int    $post_id 文章 ID。
 * @return string
 */
function bang_unlock_excerpt( $excerpt, $post_id ) {
	if ( bang_unlock_post_locked( $post_id ) ) {
		return '需解锁后阅读';
	}
	return $excerpt;
}
add_filter( 'bang_post_excerpt', 'bang_unlock_excerpt', 12, 2 );

/* ============================================================
   正文出口：整篇锁在 bang_heading_data() 之前截断，原文不进 HTML
   ============================================================ */
/**
 * 整篇锁：bang_heading_data() 会先问这里。命中时直接返回锁定卡片 + 空目录，
 * 连 the_content 过滤器都不执行，简码 / 区块自然也不会把原文渲染出来。
 *
 * @param int $post_id 文章 ID。
 * @return array{html:string,toc:array}|false
 */
function bang_unlock_heading_override( $post_id ) {
	if ( ! bang_unlock_post_locked( $post_id ) ) {
		return false;
	}
	return array(
		'html' => bang_unlock_card_html(
			array(
				'post_id' => $post_id,
				'method'  => bang_unlock_post_method( $post_id ),
			)
		),
		'toc'  => array(),
	);
}

/* ============================================================
   部分内容：区块 + 简码
   ============================================================ */
/**
 * 0.13.24：已解锁内容的展示框。上面一行是「已解锁内容」标签，下面才是真正受保护的正文。
 * 只有真正通过解锁判定的内容才带这个外框；区块本身没开锁定（locked=false）时保持普通容器。
 *
 * 输出会经过 wp_kses_post()，所以只用 div / span / strong / 已放行的 SVG，不放 data-*。
 *
 * @param string $content 受保护正文（仅在已解锁时调用）。
 * @param string $method  解锁方式，用于右上角的方式标签。
 * @return string
 */
function bang_unlock_pass_html( $content, $method = '' ) {
	$labels = bang_unlock_method_labels();
	$way    = bang_unlock_normalize_method( $method );
	return '<div class="bang-unlock-pass bang-unlock-open">'
		. '<div class="bang-unlock-open-head">'
		. '<span class="bang-unlock-open-badge">' . bang_unlock_icon( 'check', 14 ) . '</span>'
		. '<strong class="bang-unlock-open-title">已解锁内容</strong>'
		. '<span class="bang-unlock-open-way">' . esc_html( $labels[ $way ] ) . '</span>'
		. '</div>'
		. '<div class="bang-unlock-open-body">' . wp_kses_post( $content ) . '</div>'
		. '</div>';
}

/**
 * 区块回调 / 简码共用的渲染。
 *
 * @param array $args {title,desc,content,teaser,locked,post_id,method,password,lock}。
 * @return string
 */
function bang_unlock_part_html( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'title'    => '',
			'desc'     => '',
			'content'  => '',
			'teaser'   => '',
			'locked'   => true,
			'post_id'  => bang_unlock_current_post(),
			'method'   => 'login',
			'password' => '',
			'lock'     => '',
		)
	);
	$post_id = (int) $args['post_id'];
	$method  = bang_unlock_normalize_method( $args['method'] );
	$content = (string) $args['content'];
	/* 密码方式：区块上填了专属密码就按这个锁单独判定；留空回退站点密码 */
	$lock = '' !== trim( (string) $args['lock'] )
		? sanitize_key( (string) $args['lock'] )
		: bang_unlock_lock_for( $post_id, $method, $args['password'] );
	if ( ! $args['locked'] ) {
		return '<div class="bang-unlock-pass">' . wp_kses_post( $content ) . '</div>';
	}
	if ( bang_unlock_can_read( $post_id, $method, $lock ) ) {
		return bang_unlock_pass_html( $content, $method );
	}
	/* 只输出 teaser 和锁定卡片；$content 绝不能拼进这里 */
	$card = bang_unlock_card_html(
		array(
			'title'   => $args['title'],
			'desc'    => $args['desc'],
			'teaser'  => $args['teaser'],
			'compact' => true,
			'post_id' => $post_id,
			'method'  => $method,
			'lock'    => $lock,
		)
	);
	return '<div class="bang-unlock-wrap" data-bang-unlock-part="1">' . $card . '</div>';
}

/**
 * 动态区块 render_callback。
 *
 * @param array $attributes 区块属性。
 * @return string
 */
function bang_render_unlock_block( $attributes ) {
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'bang-unlock-block' ) );
	return '<div ' . $wrapper . '>' . bang_unlock_part_html(
		array(
			'title'   => isset( $attributes['title'] ) ? $attributes['title'] : '',
			'desc'    => isset( $attributes['desc'] ) ? $attributes['desc'] : '',
			'content' => isset( $attributes['content'] ) ? $attributes['content'] : '',
			'teaser'  => isset( $attributes['teaser'] ) ? $attributes['teaser'] : '',
			'locked'   => ! empty( $attributes['locked'] ),
			'post_id'  => bang_unlock_current_post(),
			/* 五选一：编辑器里选的方式是唯一凭据，缺省回退登录可见 */
			'method'   => isset( $attributes['method'] ) ? $attributes['method'] : 'login',
			/* 编辑器「设置解锁密码」填的值，只作用于本区块 */
			'password' => isset( $attributes['password'] ) ? $attributes['password'] : '',
		)
	) . '</div>';
}

/**
 * [unlock method="login|like|comment|password" password="…" title="…" desc="…"]受保护内容[/unlock]
 *
 * @param array  $atts    属性。
 * @param string $content 内容。
 * @return string
 */
function bang_sc_unlock( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'title'    => '',
			'desc'     => '',
			'teaser'   => '',
			'method'   => 'login',
			'password' => '',
			'locked'   => '1',
		),
		(array) $atts,
		'bang_unlock'
	);
	$locked = ! in_array( strtolower( (string) $atts['locked'] ), array( '0', 'off', 'no', 'false' ), true );
	return bang_unlock_part_html(
		array(
			'title'    => $atts['title'],
			'desc'     => $atts['desc'],
			'teaser'   => $atts['teaser'],
			'content'  => $content,
			'locked'   => $locked,
			'post_id'  => bang_unlock_current_post(),
			'method'   => $atts['method'],
			'password' => $atts['password'],
		)
	);
}
add_shortcode( 'unlock', 'bang_sc_unlock' );

/* ============================================================
   前台配置（app.js 兑换与轮询用）
   ============================================================ */
/**
 * 注入到 bang-app 的解锁配置。
 *
 * @return array
 */
function bang_unlock_frontend_config() {
	return array(
		'enabled'   => bang_unlock_enabled(),
		'statusUrl' => esc_url_raw( rest_url( 'bang/v1/unlock/status' ) ),
		'redeemUrl' => esc_url_raw( rest_url( 'bang/v1/unlock/redeem' ) ),
		'nonce'     => wp_create_nonce( 'wp_rest' ),
		'pollMs'    => 8000,
		'text'      => array(
			'ok'          => '已解锁，正在刷新…',
			'needLogin'   => '请先登录后再解锁',
			'needComment' => '先在本文发表一条评论，发布后会自动解锁',
			'needCombo'   => '再发表一条评论即可解锁',
			'badPassword' => '解锁密码不正确',
			'noPassword'  => '请先输入解锁密码',
			'failed'      => '解锁没成功，稍后再试',
		),
	);
}

/* ============================================================
   kses：放行锁定卡片里的 SVG 图标与密码输入框
   ============================================================ */
/**
 * 正文会经过 wp_kses_post()：<button>/<form> 会被剥掉，所以卡片用 <a> 与 <input>；
 * data-* 属性由 WP 默认放行，这里补上 input 和绘图标签，不放 on* / script。
 *
 * @param array  $tags    允许的标签。
 * @param string $context 上下文。
 * @return array
 */
function bang_unlock_allowed_html( $tags, $context ) {
	if ( 'post' !== $context ) {
		return $tags;
	}
	/* 0.13.23：锁定卡片根 div 上的区块锁标识，kses 不能把它吃掉 */
	$tags['div']['data-*']              = true;
	$tags['div']['data-bang-unlock-lock'] = true;
	$tags['input'] = array(
		'type'        => true,
		'name'        => true,
		'value'       => true,
		'class'       => true,
		'id'          => true,
		'placeholder' => true,
		'maxlength'   => true,
		'autocomplete'=> true,
		'aria-label'  => true,
		'inputmode'   => true,
		/* 自定义标签的 data-* 不像 div/a 那样默认放行，必须显式登记 */
		'data-*'                    => true,
		'data-bang-unlock-pass-input' => true,
	);
	$tags['svg'] = array(
		'class'           => true,
		'viewbox'         => true,
		'width'           => true,
		'height'          => true,
		'fill'            => true,
		'stroke'          => true,
		'stroke-width'    => true,
		'stroke-linecap'  => true,
		'stroke-linejoin' => true,
		'aria-hidden'     => true,
		'focusable'       => true,
	);
	$shape = array(
		'd'      => true,
		'cx'     => true,
		'cy'     => true,
		'r'      => true,
		'x1'     => true,
		'y1'     => true,
		'x2'     => true,
		'y2'     => true,
		'x'      => true,
		'y'      => true,
		'width'  => true,
		'height' => true,
		'rx'     => true,
		'ry'     => true,
		'points' => true,
		'fill'   => true,
		'stroke' => true,
	);
	foreach ( array( 'path', 'circle', 'line', 'rect', 'polyline', 'polygon', 'ellipse' ) as $tag ) {
		$tags[ $tag ] = isset( $tags[ $tag ] ) ? array_merge( $tags[ $tag ], $shape ) : $shape;
	}
	return $tags;
}
add_filter( 'wp_kses_allowed_html', 'bang_unlock_allowed_html', 14, 2 );

/* ============================================================
   后台联调信息面板（只读，不参与保存）
   ============================================================ */
/**
 * 阅读解锁 tab 底部的接线说明。
 *
 * @return string
 */
function bang_unlock_panel() {
	$rows = array(
		'解锁方式'   => '登录可见 / 点赞可见 / 回复可见 / 密码可见 / 点赞+评论可见，作者在每个「阅读解锁」区块里五选一，读者只能用选定的那一种；其它方式不显示也不放行。',
		'整篇锁方式' => '自定义字段 bang_unlock_post = 1 时，再用 bang_unlock_method = login|like|comment|password|combo 指定方式（留空默认登录可见）',
		'简码方式'   => '[unlock method="login"]…[/unlock]，method 同上五选一，留空默认登录可见',
		'站点解锁密码' => bang_unlock_password_for( 0 ) . '（后台留空时为自动派生；可在文章自定义字段 bang_unlock_password 单独覆盖，也可在编辑器里把「阅读解锁」切成「密码可见」直接填本段专属密码）',
		'叠加解锁'   => '点赞+评论可见（combo）：点赞和评论两步都要完成才放行。两步本身都要求登录态，密码不参与叠加；「去点赞」由前台代做，「去评论」跳评论区，评论需通过审核。',
		'已屏蔽'     => '入群可见已屏蔽，不再提供配置；旧内容的 group 自动回退为「登录可见」，不会输出入群卡片。',
		'自定义说明' => trim( (string) bang_opt( 'unlock_hint' ) ) ? trim( (string) bang_opt( 'unlock_hint' ) ) : '（未填写，卡片用默认文案）',
		'密码解锁提示文案' => trim( (string) bang_opt( 'unlock_password_text' ) ) ? trim( (string) bang_opt( 'unlock_password_text' ) ) : '（未填写，密码卡片不额外显示提示）',
	);
	$out = '<h3>阅读解锁说明</h3>'
		. '<p class="bang-f-d">登录 / 点赞 / 回复只依赖本站账号体系；密码可见游客也能用，输入正确密码后签名 cookie 记 30 天：</p>';
	foreach ( $rows as $k => $v ) {
		$out .= '<p class="bang-f-d"><b>' . esc_html( $k ) . '：</b>' . esc_html( $v ) . '</p>';
	}
	$out .= '<p class="bang-f-d"><b>交互流程：</b>登录可见点「去登录」→ 登录后刷新放行；点赞可见由前台调用点赞接口并刷新；'
		. '回复可见需先发表一条已审核评论，发布后轮询自动放行；密码可见输入密码（可在区块上设专属密码）后刷新；'
		. '叠加可见点「去点赞」完成点赞、点「去评论」跳到评论区并发表，两步都完成后自动刷新。未解锁时服务端绝不输出受保护正文。</p>';
	return $out;
}
