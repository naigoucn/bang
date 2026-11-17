<?php
/**
 * Bang · 阅读解锁（0.13.22）
 *
 * 每个锁定区块只能四选一，作者选定后读者只能用这一种方式解锁：
 *   1) 点赞可见 —— 给本文点个赞；
 *   2) 评论可见 —— 在本文发表一条已审核评论；
 *   3) 密码可见 —— 输入站点解锁密码（游客也可用，签名 cookie 记 30 天）；
 *   4) 入群可见 —— 加入群并把专属绑定码发给机器人，群内状态有效即可（默认）。
 * 其它方式既不会出现在卡片上，也不能放行；判定只按区块声明的 method 做。
 *
 * 用法：
 *   · 部分内容：区块「阅读解锁」侧栏选方式，或 [unlock method="like"]…[/unlock]；
 *   · 整篇文章：自定义字段 pk_unlock_post = 1，并用 pk_unlock_method 指定方式。
 * 后台「阅读解锁」只配置机器人与密码，没有任何开关。
 *
 * 安全约定：未解锁时服务端绝不输出受保护内容，也不放进首页 #pk-data 内联层、
 * 核心 REST /wp-json/wp/v2/posts、feed 或 SEO 描述。前端只负责展示与提交，不参与鉴权。
 *
 * 机器人插件（工作区 bot-plugin/bang-unlock/index.js）与本站共用 HMAC-SHA256 签名：
 * sign = hmac( action|code|bot_uid|group_id|ts, secret )，允许 ±5 分钟，避免重放。
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
function pk_unlock_enabled() {
	return true;
}

/**
 * 机器人回调共享密钥。
 *
 * @return string
 */
function pk_unlock_secret() {
	return trim( (string) pk_opt( 'unlock_secret' ) );
}

/**
 * 群内心跳有效期（秒）。
 *
 * @return int
 */
function pk_unlock_ttl_seconds() {
	$hours = (int) pk_opt( 'unlock_ttl' );
	if ( $hours < 1 ) {
		$hours = 72;
	}
	return $hours * HOUR_IN_SECONDS;
}

/**
 * 区块可以四选一的全部方式（不是「同时开启」，只是可选值白名单）。
 *
 * @return string[]
 */
function pk_unlock_ways() {
	return array( 'like', 'comment', 'password', 'group' );
}

/**
 * 四种方式的显示名。
 *
 * @return array
 */
function pk_unlock_method_labels() {
	return array(
		'like'     => '点赞可见',
		'comment'  => '评论可见',
		'password' => '密码可见',
		'group'    => '入群可见',
	);
}

/**
 * 方式白名单。非法值回退到「入群可见」（0.13.20 的默认行为）。
 *
 * @param string $method 方式。
 * @return string
 */
function pk_unlock_normalize_method( $method ) {
	$method = sanitize_key( (string) $method );
	return array_key_exists( $method, pk_unlock_method_labels() ) ? $method : 'group';
}

/**
 * 整篇锁文章使用的方式：自定义字段 pk_unlock_method，默认入群。
 *
 * @param int $post_id 文章 ID。
 * @return string
 */
function pk_unlock_post_method( $post_id ) {
	$m = trim( (string) get_post_meta( (int) $post_id, 'pk_unlock_method', true ) );
	return '' !== $m ? pk_unlock_normalize_method( $m ) : 'group';
}

/**
 * 卡片底部说明。只有「入群可见」才展示入群状态原因。
 *
 * @param string $method 方式。
 * @param string $reason 入群状态。
 * @return string
 */
function pk_unlock_way_note( $method, $reason ) {
	if ( 'group' === $method ) {
		return pk_unlock_reason_text( $reason );
	}
	$map = array(
		'like'     => '点赞后页面会自动刷新，不需要绑定机器人。',
		'comment'  => '发表一条评论后页面会自动刷新（评论需通过审核）。',
		'password' => '密码正确后 30 天内免重输。',
	);
	return isset( $map[ $method ] ) ? $map[ $method ] : '';
}

/**
 * 当前正在渲染的文章 ID。首页内联阅读层一次渲染多篇文章，
 * pk_heading_data() 会临时把 ID 放进 $GLOBALS，避免误用主查询的 get_the_ID()。
 *
 * @return int
 */
function pk_unlock_current_post() {
	if ( isset( $GLOBALS['pk_unlock_render_post'] ) ) {
		return (int) $GLOBALS['pk_unlock_render_post'];
	}
	$id = get_the_ID();
	return $id ? (int) $id : (int) get_queried_object_id();
}

/**
 * 图标。与 pk_icon() 同一套线性描边，黑白体系、无 emoji。
 *
 * @param string $name 名称。
 * @param int    $size 尺寸。
 * @return string
 */
function pk_unlock_icon( $name, $size = 18 ) {
	$paths = array(
		'lock'    => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><line x1="12" y1="14" x2="12" y2="17"/>',
		'heart'   => '<path d="M20.8 5.6a5.4 5.4 0 0 0-7.6 0L12 6.8l-1.2-1.2a5.4 5.4 0 0 0-7.6 7.6L12 21l8.8-7.8a5.4 5.4 0 0 0 0-7.6Z"/>',
		'message' => '<path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-3.6-.8L3 21l1.8-4.8a8.5 8.5 0 0 1 7.7-13.2A8.5 8.5 0 0 1 21 11.5Z"/>',
		'key'     => '<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9 2 2-2 2 1.5 1.5L20 10l-2 2-2-2L11 15"/>',
		'users'   => '<path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 20v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
		'copy'    => '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M15 5.5A1.5 1.5 0 0 0 13.5 4H5.5A1.5 1.5 0 0 0 4 5.5v8A1.5 1.5 0 0 0 5.5 15"/>',
		'check'   => '<path d="m5 12 4.5 4.5L19 7"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="pk-unlock-ico" viewBox="0 0 24 24" width="' . (int) $size . '" height="' . (int) $size . '"'
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
function pk_unlock_password_for( $post_id ) {
	$post_id = (int) $post_id;
	$pw      = trim( (string) get_post_meta( $post_id, 'pk_unlock_password', true ) );
	if ( '' !== $pw ) {
		return $pw;
	}
	$pw = trim( (string) pk_opt( 'unlock_password' ) );
	if ( '' !== $pw ) {
		return $pw;
	}
	/* 后台没填时给一串稳定随机密码，管理员可在面板里只读查看 */
	return substr( wp_hash( 'pk_unlock_site_password' ), 0, 10 );
}

/**
 * 0.13.23：区块专属密码的不透明标识。只把 HMAC 下发到前台，
 * 明文密码永远留在服务端（文章内容里），REST 兑换时按这个标识找回密码。
 *
 * @param int    $post_id  文章 ID。
 * @param string $password 区块上设置的密码。
 * @return string 24 位小写十六进制。
 */
function pk_unlock_lock_token( $post_id, $password ) {
	return substr(
		hash_hmac( 'sha256', 'pk_unlock_lock|' . (int) $post_id . '|' . (string) $password, wp_salt( 'auth' ) ),
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
function pk_unlock_locks_for( $post_id ) {
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
		$token           = pk_unlock_lock_token( $post_id, $password );
		$locks[ $token ] = array(
			'method'   => pk_unlock_normalize_method( $method ),
			'password' => $password,
		);
	};
	/* 动态区块：<!-- wp:pianke/unlock {"method":"password","password":"…"} /--> */
	if ( preg_match_all( '/<!--\s*wp:pianke\/unlock\s+(\{.*?\})\s*\/?-->/s', $content, $bm ) ) {
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
function pk_unlock_lock_info( $post_id, $lock ) {
	$lock  = (string) $lock;
	$locks = pk_unlock_locks_for( $post_id );
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
function pk_unlock_lock_for( $post_id, $method, $password ) {
	if ( 'password' !== pk_unlock_normalize_method( $method ) ) {
		return '';
	}
	$password = trim( (string) $password );
	return '' !== $password ? pk_unlock_lock_token( $post_id, $password ) : '';
}

/**
 * 本文是否存在「用站点 / 文章密码」的密码锁。
 * 整篇锁、以及没填专属密码的密码区块都属于这一类；用于阻止不带 lock 的兑换
 * 误用站点密码去碰只有专属密码的区块。
 *
 * @param int $post_id 文章 ID。
 * @return bool
 */
function pk_unlock_has_plain_password_lock( $post_id ) {
	$post_id = (int) $post_id;
	if ( get_post_meta( $post_id, 'pk_unlock_post', true ) && 'password' === pk_unlock_post_method( $post_id ) ) {
		return true;
	}
	$content = (string) get_post_field( 'post_content', $post_id );
	if ( preg_match_all( '/<!--\s*wp:pianke\/unlock\s+(\{.*?\})\s*\/?-->/s', $content, $bm ) ) {
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
function pk_unlock_pass_token( $post_id, $password, $lock = '' ) {
	$key = 'pk_unlock_pass|' . (int) $post_id . '|' . (string) $password;
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
function pk_unlock_pass_cookie_name( $post_id, $lock = '' ) {
	if ( '' !== $lock ) {
		return 'pk_unlock_p_' . substr( preg_replace( '/[^a-f0-9]/', '', (string) $lock ), 0, 16 );
	}
	return 'pk_unlock_p_' . (int) $post_id;
}

/**
 * 游客也能用：校验签名 cookie 是否等于本文 / 本锁密码的令牌。改密码后旧 cookie 立即失效。
 *
 * @param int    $post_id 文章 ID。
 * @param string $lock    可选，区块锁标识。
 * @return bool
 */
function pk_unlock_pass_ok( $post_id, $lock = '' ) {
	$name = pk_unlock_pass_cookie_name( $post_id, $lock );
	$got  = isset( $_COOKIE[ $name ] ) ? (string) wp_unslash( $_COOKIE[ $name ] ) : '';
	if ( ! $got ) {
		return false;
	}
	if ( '' !== $lock ) {
		$info = pk_unlock_lock_info( $post_id, $lock );
		if ( ! $info || 'password' !== $info['method'] ) {
			return false;
		}
		$want = pk_unlock_pass_token( $post_id, $info['password'], $lock );
	} else {
		$want = pk_unlock_pass_token( $post_id, pk_unlock_password_for( $post_id ) );
	}
	return hash_equals( $want, $got );
}

/**
 * 写入 30 天有效的密码令牌。
 *
 * @param int    $post_id 文章 ID。
 * @param string $lock    可选，区块锁标识。
 */
function pk_unlock_set_pass_cookie( $post_id, $lock = '' ) {
	if ( '' !== $lock ) {
		$info = pk_unlock_lock_info( $post_id, $lock );
		if ( ! $info || 'password' !== $info['method'] ) {
			return;
		}
		$password = $info['password'];
	} else {
		$password = pk_unlock_password_for( $post_id );
	}
	$token = pk_unlock_pass_token( $post_id, $password, $lock );
	$name  = pk_unlock_pass_cookie_name( $post_id, $lock );
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
function pk_unlock_code( $uid ) {
	$uid = (int) $uid;
	if ( ! $uid ) {
		return '';
	}
	$code = (string) get_user_meta( $uid, 'pk_unlock_code', true );
	if ( $code ) {
		return $code;
	}
	for ( $i = 0; $i < 8; $i++ ) {
		$try    = strtoupper( substr( wp_hash( 'pk_unlock_code|' . $uid . '|' . $i ), 0, 8 ) );
		$exists = get_users(
			array(
				'meta_key'   => 'pk_unlock_code',
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
		$code = strtoupper( substr( wp_hash( 'pk_unlock_code|' . $uid . '|' . microtime( true ) ), 0, 8 ) );
	}
	update_user_meta( $uid, 'pk_unlock_code', $code );
	return $code;
}

/**
 * 绑定码 → 用户 ID。
 *
 * @param string $code 绑定码。
 * @return int
 */
function pk_unlock_user_by_code( $code ) {
	$code = strtoupper( preg_replace( '/[^A-Z0-9]/', '', (string) $code ) );
	if ( strlen( $code ) !== 8 ) {
		return 0;
	}
	$ids = get_users(
		array(
			'meta_key'   => 'pk_unlock_code',
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
function pk_unlock_user_by_bot_uid( $bot_uid ) {
	$bot_uid = trim( (string) $bot_uid );
	if ( '' === $bot_uid ) {
		return 0;
	}
	$ids = get_users(
		array(
			'meta_key'   => 'pk_unlock_bot_uid',
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
function pk_unlock_reason( $uid = 0 ) {
	$uid = $uid ? (int) $uid : get_current_user_id();
	if ( ! $uid ) {
		return 'guest';
	}
	/* 0.13.22：不再有「管理员免解锁」，任何人都要走四种方式之一 */
	$state = (string) get_user_meta( $uid, 'pk_unlock_state', true );
	if ( 'left' === $state ) {
		return 'left';
	}
	if ( 'active' !== $state ) {
		return 'unbound';
	}
	$seen = (int) get_user_meta( $uid, 'pk_unlock_seen_at', true );
	if ( ! $seen || ( time() - $seen ) > pk_unlock_ttl_seconds() ) {
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
function pk_unlock_group_ok( $uid ) {
	return 'ok' === pk_unlock_reason( $uid );
}

/**
 * 该用户是否在本文发表过已审核评论。
 *
 * @param int $uid     用户 ID。
 * @param int $post_id 文章 ID。
 * @return bool
 */
function pk_unlock_user_commented( $uid, $post_id ) {
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
 * 0.13.22：区块四选一，只认选定的那一种方式，绝不「任一满足」。
 * 传空 $method 时回退到「入群可见」（0.13.20 的默认行为），不放大权限。
 * 鉴权只在这里做，前端拿不到任何受保护内容。
 *
 * @param int    $post_id 文章 ID，0 表示当前渲染的文章。
 * @param string $method  like | comment | password | group。
 * @param string $lock    可选，密码方式下的区块锁标识（专属密码）。
 * @return bool
 */
function pk_unlock_can_read( $post_id = 0, $method = '', $lock = '' ) {
	$post_id = $post_id ? (int) $post_id : pk_unlock_current_post();
	if ( ! $post_id ) {
		return false;
	}
	$uid = get_current_user_id();

	switch ( pk_unlock_normalize_method( $method ) ) {
		case 'password':
			return pk_unlock_pass_ok( $post_id, (string) $lock );
		case 'like':
			if ( ! $uid ) {
				return false;
			}
			/* 直接读 user_meta，不依赖 inc/social.php 的加载顺序 */
			$likes = get_user_meta( $uid, 'pk_likes', true );
			return is_array( $likes ) && in_array( $post_id, array_map( 'intval', $likes ), true );
		case 'comment':
			return (bool) $uid && pk_unlock_user_commented( $uid, $post_id );
		case 'group':
		default:
			return (bool) $uid && pk_unlock_group_ok( $uid );
	}
}

/**
 * 文章里实际声明了哪些解锁方式。用于 /redeem 拒绝「本文根本没用这种方式」的兑换，
 * 避免读者给 A 文章刷点赞、密码，再去尝试影响别的判定。
 *
 * 只看静态源码：区块注释里的 method、简码的 method、整篇锁的 pk_unlock_method。
 * 解析不到任何锁时返回空数组（此时不做额外限制，兼容动态插入的内容）。
 *
 * @param int $post_id 文章 ID。
 * @return string[]
 */
function pk_unlock_post_methods( $post_id ) {
	$post_id = (int) $post_id;
	$methods = array();
	if ( get_post_meta( $post_id, 'pk_unlock_post', true ) ) {
		$methods[] = pk_unlock_post_method( $post_id );
	}
	$content = (string) get_post_field( 'post_content', $post_id );
	if ( preg_match_all( '/<!--\s*wp:pianke\/unlock\s+(\{.*?\})\s*\/?-->/s', $content, $bm ) ) {
		foreach ( $bm[1] as $json ) {
			$attrs = json_decode( $json, true );
			$methods[] = pk_unlock_normalize_method( is_array( $attrs ) && isset( $attrs['method'] ) ? $attrs['method'] : 'group' );
		}
	}
	if ( preg_match_all( '/\[unlock([^\]]*)\]/i', $content, $sm ) ) {
		foreach ( $sm[1] as $raw ) {
			if ( preg_match( '/\bmethod\s*=\s*["\']?([a-z]+)/i', $raw, $mm ) ) {
				$methods[] = pk_unlock_normalize_method( $mm[1] );
			} else {
				$methods[] = 'group';
			}
		}
	}
	return array_values( array_unique( $methods ) );
}

/**
 * 前台状态查询用的完整数据。四种方式各自给出独立布尔值，
 * 卡片只认自己 data-pk-unlock-method 对应的那一个，不串味。
 *
 * @param int    $post_id 文章 ID。
 * @param string $method  可选，指定查询某一种方式；不传则取文章声明的第一种方式。
 * @return array
 */
function pk_unlock_status_data( $post_id = 0, $method = '', $locks_raw = '' ) {
	$uid      = get_current_user_id();
	$post_id  = $post_id ? (int) $post_id : pk_unlock_current_post();
	$bound    = $uid ? (bool) get_user_meta( $uid, 'pk_unlock_bot_uid', true ) : false;
	$reason   = pk_unlock_reason( $uid );
	$declared = $post_id ? pk_unlock_post_methods( $post_id ) : array();
	if ( '' !== $method ) {
		$method = pk_unlock_normalize_method( $method );
	} elseif ( $declared ) {
		$method = $declared[0];
	} else {
		/* 没有文章上下文（外部直接调 /status）时只反映入群状态 */
		$method = 'group';
	}
	$by = array();
	foreach ( pk_unlock_ways() as $m ) {
		$by[ $m ] = $post_id ? pk_unlock_can_read( $post_id, $m ) : ( 'group' === $m && 'ok' === $reason );
	}
	/* 0.13.23：区块专属密码按锁标识逐个判定，不同密码的锁互不串味 */
	$byLock = array();
	if ( $post_id && '' !== $locks_raw ) {
		$locks = array_unique( array_filter( array_map( 'sanitize_key', explode( ',', (string) $locks_raw ) ) ) );
		foreach ( $locks as $l ) {
			$info          = pk_unlock_lock_info( $post_id, $l );
			$byLock[ $l ] = ( $info && 'password' === $info['method'] ) ? pk_unlock_pass_ok( $post_id, $l ) : false;
		}
	}
	return array(
		'active'         => isset( $by[ $method ] ) ? $by[ $method ] : false,
		'activeByMethod' => $by,
		'activeByLock'   => $byLock,
		'method'         => $method,
		'declared'       => $declared,
		'groupActive'    => 'ok' === $reason,
		'bound'          => $bound,
		'state'          => $uid ? (string) get_user_meta( $uid, 'pk_unlock_state', true ) : '',
		'reason'         => $reason,
		'code'           => $uid ? pk_unlock_code( $uid ) : '',
		'seenAt'         => $uid ? (int) get_user_meta( $uid, 'pk_unlock_seen_at', true ) : 0,
		'ttl'            => pk_unlock_ttl_seconds(),
		'logged'         => (bool) $uid,
		'ways'           => pk_unlock_ways(),
		'postId'         => (int) $post_id,
		'time'           => time(),
		'binding'        => pk_unlock_binding_data( $uid ),
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
function pk_unlock_binding_data( $uid = 0 ) {
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
	$joined_at = (int) get_user_meta( $uid, 'pk_unlock_joined_at', true );
	$bound_at  = (int) get_user_meta( $uid, 'pk_unlock_bound_at', true );
	return array(
		'bot_uid'     => (string) get_user_meta( $uid, 'pk_unlock_bot_uid', true ),
		'nickname'    => (string) get_user_meta( $uid, 'pk_unlock_nickname', true ),
		'group_id'    => (string) get_user_meta( $uid, 'pk_unlock_group_id', true ),
		'bound_at'    => $bound_at,
		'joined_at'   => $joined_at ? $joined_at : $bound_at,
		'joined_from' => $joined_at ? 'platform' : ( $bound_at ? 'bind' : '' ),
		'seen_at'     => (int) get_user_meta( $uid, 'pk_unlock_seen_at', true ),
		'state'       => (string) get_user_meta( $uid, 'pk_unlock_state', true ),
		'reason'      => pk_unlock_reason( $uid ),
		'code'        => pk_unlock_code( $uid ),
	);
}

/**
 * 个人中心「群绑定」卡片：QQ / 机器人 ID、群内昵称、进群时间、最近群内活跃。
 * 未绑定时给绑定码与进群入口，让读者可以在这里完成绑定。
 *
 * @param int $uid 用户 ID。
 * @return string
 */
function pk_unlock_account_card_html( $uid = 0 ) {
	$uid = $uid ? (int) $uid : get_current_user_id();
	if ( ! $uid ) {
		return '';
	}
	$b     = pk_unlock_binding_data( $uid );
	$group = trim( (string) pk_opt( 'unlock_group_name' ) );
	$group = '' !== $group ? $group : '交流群';
	$url   = trim( (string) pk_opt( 'unlock_group_url' ) );
	$bot   = trim( (string) pk_opt( 'unlock_bot_name' ) );
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

	$html  = '<section class="user-unlock" aria-labelledby="pk-unlock-account-title">';
	$html .= '<div class="user-unlock-head">'
		. '<h2 class="user-unlock-title" id="pk-unlock-account-title">'
		. pk_unlock_icon( 'users', 16 ) . '群绑定</h2>'
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
		. '<a class="user-unlock-copy" href="#" data-pk-unlock-copy="' . esc_attr( $b['code'] ) . '">复制</a>'
		. '</div>';
	if ( $url ) {
		$html .= '<a class="user-unlock-join" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">'
			. esc_html( '加入' . $group ) . '</a>';
	}
	$html .= '</section>';
	return $html;
}

/* ============================================================
   整篇锁：文章手动标记 pk_unlock_post = 1
   ============================================================ */
/**
 * 判断一篇文章是否需要整篇上锁。0.13.22 起不再有「全站锁所有文章」开关，
 * 只认文章自定义字段，避免误伤站点里其它内容。
 *
 * @param int $post_id 文章 ID。
 * @return bool
 */
function pk_unlock_post_locked( $post_id ) {
	$post_id = (int) $post_id;
	if ( ! $post_id || 'post' !== get_post_type( $post_id ) ) {
		return false;
	}
	if ( pk_unlock_can_read( $post_id, pk_unlock_post_method( $post_id ) ) ) {
		return false;
	}
	return (bool) get_post_meta( $post_id, 'pk_unlock_post', true );
}

/**
 * 卡片底部按入群状态给出的补充说明。
 *
 * @param string $reason 状态。
 * @return string
 */
function pk_unlock_reason_text( $reason ) {
	$group = trim( (string) pk_opt( 'unlock_group_name' ) );
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
 * 不再写「任选一种」——四选一之后没有其它方式可以替代。
 *
 * @param int $post_id 文章 ID。
 * @return string
 */
function pk_unlock_lock_tip( $post_id ) {
	$declared = pk_unlock_post_methods( $post_id );
	$method   = $declared ? $declared[0] : 'group';
	$group    = trim( (string) pk_opt( 'unlock_group_name' ) );
	$group    = '' !== $group ? $group : '交流群';
	$map      = array(
		'like'     => '本文需给文章点赞后阅读。',
		'comment'  => '本文需发表一条评论后阅读。',
		'password' => '本文需输入解锁密码后阅读。',
		'group'    => '本文需加入' . $group . '并把绑定码发给机器人后阅读。',
	);
	return isset( $map[ $method ] ) ? $map[ $method ] : $map['group'];
}

/* ============================================================
   锁定卡片：只渲染作者选定的那一种方式
   ============================================================ */
/**
 * 锁定卡片 HTML。前台所有「未解锁」出口都复用这一份。
 * 四选一：卡片只输出一种方式的图标、说明与按钮，并把方式写进
 * data-pk-unlock-method，服务端判定与前台兑换都只认这个值。
 *
 * 交互元素一律用 <a> 与 <input>：正文会经过 wp_kses_post()，<button>/<form> 会被剥掉，
 * 已在 pk_unlock_allowed_html() 里放行 input 与 data-* 属性。
 *
 * @param array $args {title,desc,teaser,reason,compact,post_id,method}。
 * @return string
 */
function pk_unlock_card_html( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'title'   => '',
			'desc'    => '',
			'teaser'  => '',
			'reason'  => pk_unlock_reason(),
			'compact' => false,
			'post_id' => pk_unlock_current_post(),
			'method'  => 'group',
			'lock'    => '',
		)
	);
	$post_id = (int) $args['post_id'];
	$method  = pk_unlock_normalize_method( $args['method'] );
	$lock    = '' !== trim( (string) $args['lock'] ) ? sanitize_key( (string) $args['lock'] ) : '';
	$labels  = pk_unlock_method_labels();
	$label   = $labels[ $method ];
	$reason  = (string) $args['reason'];
	$uid     = get_current_user_id();
	$code    = $uid ? pk_unlock_code( $uid ) : '';
	$title   = trim( (string) $args['title'] );
	$desc    = trim( (string) $args['desc'] );
	$group   = trim( (string) pk_opt( 'unlock_group_name' ) );
	$group   = '' !== $group ? $group : '交流群';
	$url     = trim( (string) pk_opt( 'unlock_group_url' ) );
	$bot     = trim( (string) pk_opt( 'unlock_bot_name' ) );
	$bot     = '' !== $bot ? $bot : '机器人';
	$hint    = trim( (string) pk_opt( 'unlock_hint' ) );
	$teaser  = trim( (string) $args['teaser'] );

	$html = '<div class="pk-unlock pk-unlock--' . esc_attr( $method )
		. ( $args['compact'] ? ' pk-unlock--compact' : '' ) . '"'
		. ' data-pk-unlock="1" data-pk-unlock-poll="1" data-pk-unlock-post="' . esc_attr( $post_id ) . '"'
		. ' data-pk-unlock-method="' . esc_attr( $method ) . '"'
		. ( $lock ? ' data-pk-unlock-lock="' . esc_attr( $lock ) . '"' : '' )
		. ' data-pk-unlock-reason="' . esc_attr( $reason ) . '">';

	if ( $teaser ) {
		$html .= '<div class="pk-unlock-teaser">' . wp_kses_post( $teaser ) . '</div>';
	}

	$html .= '<div class="pk-unlock-head">'
		. '<span class="pk-unlock-badge">' . pk_unlock_icon( 'lock', 18 ) . '</span>'
		. '<div class="pk-unlock-titles">'
		. '<strong class="pk-unlock-title">' . esc_html( '' !== $title ? $title : '内容已锁定' ) . '</strong>'
		. '<span class="pk-unlock-sub">' . esc_html( '' !== $desc ? $desc : '使用「' . $label . '」解锁后继续阅读' ) . '</span>'
		. '</div></div>';

	if ( $hint ) {
		$html .= '<p class="pk-unlock-hint">' . esc_html( $hint ) . '</p>';
	}

	$html .= '<div class="pk-unlock-ways">';

	$icon = array(
		'like'     => 'heart',
		'comment'  => 'message',
		'password' => 'key',
		'group'    => 'users',
	);
	$html .= '<div class="pk-unlock-way pk-unlock-way--' . esc_attr( $method ) . '">'
		. '<span class="pk-unlock-way-ico">' . pk_unlock_icon( $icon[ $method ], 17 ) . '</span>'
		. '<div class="pk-unlock-way-body">'
		. '<strong class="pk-unlock-way-t">' . esc_html( $label ) . '</strong>';

	if ( 'like' === $method ) {
		$html .= '<span class="pk-unlock-way-d">给这篇文章点个赞，立即解锁</span>'
			. '</div>'
			. '<a class="pk-unlock-way-btn pk-unlock-way-btn--primary" href="#" data-pk-unlock-way="like">点赞解锁</a>';
	} elseif ( 'comment' === $method ) {
		$html .= '<span class="pk-unlock-way-d">发表一条评论后自动解锁</span>'
			. '</div>'
			. '<a class="pk-unlock-way-btn pk-unlock-way-btn--primary" href="#" data-pk-unlock-way="comment">去评论</a>';
	} elseif ( 'password' === $method ) {
		$html .= '<span class="pk-unlock-way-d">' . esc_html( $lock ? '输入本文设置的解锁密码' : '输入站长提供的解锁密码' ) . '</span>'
			. '<span class="pk-unlock-pass-row">'
			. '<input type="password" class="pk-unlock-pass-input" data-pk-unlock-pass-input'
			. ' placeholder="解锁密码" maxlength="64" autocomplete="off" aria-label="解锁密码">'
			. '<a class="pk-unlock-way-btn pk-unlock-way-btn--primary" href="#" data-pk-unlock-way="password">解锁</a>'
			. '</span>'
			. '</div>';
	} else {
		$html .= '<span class="pk-unlock-way-d">加入' . esc_html( $group ) . '，把绑定码发给' . esc_html( $bot ) . '</span>';
		if ( $code ) {
			$html .= '<span class="pk-unlock-code">'
				. '<span class="pk-unlock-code-label">我的绑定码</span>'
				. '<code class="pk-unlock-code-val">' . esc_html( $code ) . '</code>'
				. '<a class="pk-unlock-copy" href="#" data-pk-unlock-copy="' . esc_attr( $code ) . '"'
				. ' aria-label="复制绑定码">' . pk_unlock_icon( 'copy', 14 ) . '<span>复制</span></a>'
				. '</span>';
		}
		$html .= '<span class="pk-unlock-actions">';
		if ( $url ) {
			$html .= '<a class="pk-unlock-btn pk-unlock-btn--primary" href="' . esc_url( $url ) . '"'
				. ' target="_blank" rel="noopener noreferrer">' . pk_unlock_icon( 'users', 15 )
				. '<span>加入' . esc_html( $group ) . '</span></a>';
		}
		if ( ! $uid ) {
			$html .= '<a class="pk-unlock-btn" href="' . esc_url( home_url( '/login/' ) ) . '"'
				. ' data-pk-auth-open data-no-swup>登录获取绑定码</a>';
		}
		$html .= '</span></div>';
	}
	$html .= '</div>';

	$html .= '</div>';
	$html .= '<p class="pk-unlock-note" data-pk-unlock-note>' . esc_html( pk_unlock_way_note( $method, $reason ) ) . '</p>';
	$html .= '</div>';
	return $html;
}

/* ============================================================
   REST：机器人回调 + 四种方式兑换 + 前台状态轮询
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
function pk_unlock_sign( $action, $code, $bot_uid, $group_id, $ts, $nickname = '', $joined_at = 0 ) {
	$parts     = array( (string) $action, (string) $code, (string) $bot_uid, (string) $group_id, (int) $ts );
	$joined_at = (int) $joined_at;
	/* 0.13.24：带群资料的回调走扩展签名（末尾追加昵称与入群时间），
	   不带的仍按 0.13.20 五段签名，保证旧版 WeBot 插件无需升级也能继续绑定。 */
	if ( '' !== (string) $nickname || $joined_at ) {
		$parts[] = (string) $nickname;
		$parts[] = $joined_at;
	}
	return hash_hmac( 'sha256', implode( '|', $parts ), pk_unlock_secret() );
}

/**
 * 机器人给的时间可能是秒、毫秒或日期字符串，统一成 2000~2100 之间的 Unix 秒。
 *
 * @param mixed $ts 原始值。
 * @return int
 */
function pk_unlock_normalize_ts( $ts ) {
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
function pk_unlock_verify( $request, $action ) {
	if ( ! pk_unlock_secret() ) {
		return new WP_Error( 'pk_unlock_no_secret', '站点未配置回调密钥', array( 'status' => 503 ) );
	}
	$ts = (int) $request->get_param( 'ts' );
	if ( ! $ts || abs( time() - $ts ) > 300 ) {
		return new WP_Error( 'pk_unlock_stale', '请求已过期', array( 'status' => 403 ) );
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
		? pk_unlock_sign( $action, $code, $bot_uid, $group_id, $ts, $nickname, $joined_raw )
		: pk_unlock_sign( $action, $code, $bot_uid, $group_id, $ts );
	if ( ! hash_equals( $expect, $sign ) ) {
		return new WP_Error( 'pk_unlock_sign', '签名校验失败', array( 'status' => 403 ) );
	}
	return true;
}

/**
 * 注册路由。
 */
function pk_unlock_rest_routes() {
	$ns = 'pianke/v1/unlock';

	register_rest_route(
		'pianke/v1',
		'/unlock/ping',
		array(
			'methods'             => 'GET',
			'callback'            => function () {
				return array(
					'ok'      => true,
					'version' => PK_VERSION,
					'enabled' => pk_unlock_enabled(),
				);
			},
			'permission_callback' => '__return_true',
		)
	);

	foreach ( array( 'bind', 'heartbeat', 'leave' ) as $action ) {
		register_rest_route(
			'pianke/v1',
			'/unlock/' . $action,
			array(
				'methods'             => 'POST',
				'callback'            => 'pk_unlock_rest_callback',
				'permission_callback' => '__return_true',
				'args'                => array(
					'action' => array( 'default' => $action ),
				),
			)
		);
	}

	register_rest_route(
		'pianke/v1',
		'/unlock/redeem',
		array(
			'methods'             => 'POST',
			'callback'            => 'pk_unlock_rest_redeem',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'pianke/v1',
		'/unlock/status',
		array(
			'methods'             => 'GET',
			'callback'            => function ( $request ) {
				return pk_unlock_status_data(
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
add_action( 'rest_api_init', 'pk_unlock_rest_routes' );

/**
 * 机器人回调统一入口。
 *
 * @param WP_REST_Request $request 请求。
 * @return array|WP_Error
 */
function pk_unlock_rest_callback( $request ) {
	$route  = (string) $request->get_route();
	$action = '';
	foreach ( array( 'bind', 'heartbeat', 'leave' ) as $a ) {
		if ( false !== strpos( $route, '/unlock/' . $a ) ) {
			$action = $a;
			break;
		}
	}
	if ( ! $action ) {
		return new WP_Error( 'pk_unlock_route', '未知动作', array( 'status' => 404 ) );
	}

	$verified = pk_unlock_verify( $request, $action );
	if ( is_wp_error( $verified ) ) {
		return $verified;
	}

	$code     = strtoupper( sanitize_text_field( (string) $request->get_param( 'code' ) ) );
	$bot_uid  = sanitize_text_field( (string) $request->get_param( 'bot_uid' ) );
	$group_id  = sanitize_text_field( (string) $request->get_param( 'group_id' ) );
	/* 0.13.24：机器人顺带回传的群内昵称与进群时间，只用于个人中心展示，不参与解锁判定 */
	$nickname  = sanitize_text_field( (string) $request->get_param( 'nickname' ) );
	$joined_at = pk_unlock_normalize_ts( $request->get_param( 'joined_at' ) );

	if ( 'bind' === $action ) {
		$uid = pk_unlock_user_by_code( $code );
		if ( ! $uid ) {
			return new WP_Error( 'pk_unlock_bad_code', '绑定码无效，请到网站重新获取', array( 'status' => 404 ) );
		}
		if ( '' === $bot_uid ) {
			return new WP_Error( 'pk_unlock_no_uid', '缺少机器人用户 ID', array( 'status' => 400 ) );
		}
		/* 同一个机器人账号改绑到新站点账号时，旧账号立即失效，避免一号多人共用 */
		$other = pk_unlock_user_by_bot_uid( $bot_uid );
		if ( $other && $other !== $uid ) {
			update_user_meta( $other, 'pk_unlock_state', 'left' );
			delete_user_meta( $other, 'pk_unlock_bot_uid' );
		}
		update_user_meta( $uid, 'pk_unlock_bot_uid', $bot_uid );
		update_user_meta( $uid, 'pk_unlock_group_id', $group_id );
		update_user_meta( $uid, 'pk_unlock_bound_at', time() );
		if ( '' !== $nickname ) {
			update_user_meta( $uid, 'pk_unlock_nickname', $nickname );
		}
		if ( $joined_at ) {
			update_user_meta( $uid, 'pk_unlock_joined_at', $joined_at );
		}
		/* 绑定动作本身就发生在群里，直接记一次心跳 */
		update_user_meta( $uid, 'pk_unlock_seen_at', time() );
		update_user_meta( $uid, 'pk_unlock_state', 'active' );
		return array( 'ok' => true, 'uid' => $uid, 'code' => $code );
	}

	$uid = pk_unlock_user_by_bot_uid( $bot_uid );
	if ( ! $uid ) {
		/* 群里绝大多数成员没绑定过，静默忽略即可，不打扰 */
		return array( 'ok' => true, 'ignored' => true );
	}

	if ( 'leave' === $action ) {
		update_user_meta( $uid, 'pk_unlock_state', 'left' );
		delete_user_meta( $uid, 'pk_unlock_seen_at' );
		return array( 'ok' => true, 'uid' => $uid, 'state' => 'left' );
	}

	/* heartbeat：群内活跃即续期；曾被标记退群的人回群发言会自动恢复 */
	update_user_meta( $uid, 'pk_unlock_seen_at', time() );
	update_user_meta( $uid, 'pk_unlock_state', 'active' );
	/* 群名片可能改过，每次心跳用最新昵称刷新；入群时间只在首次拿到时写入 */
	if ( '' !== $nickname ) {
		update_user_meta( $uid, 'pk_unlock_nickname', $nickname );
	}
	if ( $joined_at && ! get_user_meta( $uid, 'pk_unlock_joined_at', true ) ) {
		update_user_meta( $uid, 'pk_unlock_joined_at', $joined_at );
	}
	if ( $group_id ) {
		update_user_meta( $uid, 'pk_unlock_group_id', $group_id );
	}
	return array( 'ok' => true, 'uid' => $uid, 'state' => 'active' );
}

/**
 * 四种方式的兑换入口。成功只返回标记，受保护内容仍由服务端整页重新渲染输出。
 *
 * @param WP_REST_Request $request 请求。
 * @return array|WP_Error
 */
function pk_unlock_rest_redeem( $request ) {
	/* 前台带 X-WP-Nonce；显式校验一次，不依赖 REST 的自动认证顺序 */
	$nonce = (string) $request->get_header( 'X-WP-Nonce' );
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		return new WP_Error( 'pk_unlock_nonce', '页面已过期，刷新后再试', array( 'status' => 403 ) );
	}
	$method  = sanitize_key( (string) $request->get_param( 'method' ) );
	$post_id = (int) $request->get_param( 'post_id' );
	/* 0.13.23：区块专属密码的锁标识，只在密码方式下有意义 */
	$lock = 'password' === $method ? sanitize_key( (string) $request->get_param( 'lock' ) ) : '';
	if ( ! in_array( $method, array( 'like', 'comment', 'password' ), true ) ) {
		return new WP_Error( 'pk_unlock_method', '未知解锁方式', array( 'status' => 400 ) );
	}
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type ) {
		return new WP_Error( 'pk_unlock_post', '文章不存在', array( 'status' => 404 ) );
	}
	/* 四选一：本文没声明这种方式，就不允许兑换，避免用 A 方式去碰 B 方式的锁 */
	$declared = pk_unlock_post_methods( $post_id );
	if ( $declared && ! in_array( $method, $declared, true ) ) {
		return new WP_Error( 'pk_unlock_method_mismatch', '本文未使用这种解锁方式', array( 'status' => 400 ) );
	}

	if ( 'password' === $method ) {
		/* 0.13.23：文章里只有「区块专属密码」时，站点密码不能越权兑换 */
		if ( '' === $lock && pk_unlock_locks_for( $post_id ) && ! pk_unlock_has_plain_password_lock( $post_id ) ) {
			return new WP_Error( 'pk_unlock_lock_required', '请通过文章里的密码框解锁', array( 'status' => 400 ) );
		}
		/* 简单防爆破：同一 IP 5 分钟内最多 10 次错误 */
		$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key  = 'pk_unlock_fail_' . substr( wp_hash( 'pk_unlock_fail|' . $ip ), 0, 12 );
		$fail = (int) get_transient( $key );
		if ( $fail >= 10 ) {
			return new WP_Error( 'pk_unlock_locked', '尝试次数过多，请稍后再试', array( 'status' => 429 ) );
		}
		$given = (string) $request->get_param( 'password' );
		if ( '' !== $lock ) {
			/* 区块专属密码：锁标识必须在本文的锁表里，且该锁确实是密码方式 */
			$info = pk_unlock_lock_info( $post_id, $lock );
			if ( ! $info || 'password' !== $info['method'] ) {
				return new WP_Error( 'pk_unlock_lock', '这个密码锁不存在', array( 'status' => 400 ) );
			}
			$want = $info['password'];
		} else {
			/* 兼容 0.13.22：区块没填专属密码时用站点 / 文章密码 */
			$want = pk_unlock_password_for( $post_id );
		}
		if ( '' === $given || ! hash_equals( $want, $given ) ) {
			set_transient( $key, $fail + 1, 5 * MINUTE_IN_SECONDS );
			return new WP_Error( 'pk_unlock_bad_password', '解锁密码不正确', array( 'status' => 403 ) );
		}
		delete_transient( $key );
		pk_unlock_set_pass_cookie( $post_id, $lock );
		return array( 'ok' => true, 'unlocked' => true, 'method' => 'password', 'lock' => $lock );
	}

	$uid = get_current_user_id();
	if ( ! $uid ) {
		return new WP_Error( 'pk_unlock_login', '请先登录后再解锁', array( 'status' => 401 ) );
	}

	if ( 'like' === $method ) {
		$likes = get_user_meta( $uid, 'pk_likes', true );
		if ( ! is_array( $likes ) ) {
			$likes = array();
		}
		$likes = array_values( array_unique( array_filter( array_map( 'intval', $likes ) ) ) );
		if ( ! in_array( $post_id, $likes, true ) ) {
			$likes[] = $post_id;
			update_user_meta( $uid, 'pk_likes', $likes );
			$count = max( 0, (int) get_post_meta( $post_id, 'pk_like_count', true ) + 1 );
			update_post_meta( $post_id, 'pk_like_count', $count );
		}
		return array( 'ok' => true, 'unlocked' => true, 'method' => 'like' );
	}

	/* comment：不代发评论，避免刷屏；已有已审核评论即放行，否则引导去评论区 */
	if ( pk_unlock_user_commented( $uid, $post_id ) ) {
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
function pk_unlock_rest_prepare_post( $response, $post, $request ) {
	/* 后台编辑器 context=edit 必须拿原文，否则无法编辑 */
	if ( 'edit' === $request->get_param( 'context' ) ) {
		return $response;
	}
	if ( ! pk_unlock_post_locked( $post->ID ) ) {
		return $response;
	}
	$data             = $response->get_data();
	$tip              = pk_unlock_lock_tip( $post->ID );
	$data['content']  = isset( $data['content'] ) && is_array( $data['content'] )
		? array_merge( $data['content'], array( 'rendered' => '<p>' . esc_html( $tip ) . '</p>', 'protected' => true ) )
		: array( 'rendered' => '<p>' . esc_html( $tip ) . '</p>', 'protected' => true );
	$data['excerpt']  = isset( $data['excerpt'] ) && is_array( $data['excerpt'] )
		? array_merge( $data['excerpt'], array( 'rendered' => '<p>' . esc_html( $tip ) . '</p>' ) )
		: array( 'rendered' => '<p>' . esc_html( $tip ) . '</p>' );
	$response->set_data( $data );
	return $response;
}
add_filter( 'rest_prepare_post', 'pk_unlock_rest_prepare_post', 12, 3 );

/**
 * RSS / Atom 输出。
 *
 * @param string $content 正文。
 * @return string
 */
function pk_unlock_feed_content( $content ) {
	if ( pk_unlock_post_locked( get_the_ID() ) ) {
		return '<p>' . esc_html( pk_unlock_lock_tip( get_the_ID() ) ) . '</p>';
	}
	return $content;
}
add_filter( 'the_content_feed', 'pk_unlock_feed_content', 20 );

/**
 * 首页 #pk-data 内联的卡片摘要。锁定时不取正文，避免从搜索字段里捞出片段。
 *
 * @param string $excerpt 原摘要。
 * @param int    $post_id 文章 ID。
 * @return string
 */
function pk_unlock_excerpt( $excerpt, $post_id ) {
	if ( pk_unlock_post_locked( $post_id ) ) {
		return '需解锁后阅读';
	}
	return $excerpt;
}
add_filter( 'pk_post_excerpt', 'pk_unlock_excerpt', 12, 2 );

/* ============================================================
   正文出口：整篇锁在 pk_heading_data() 之前截断，原文不进 HTML
   ============================================================ */
/**
 * 整篇锁：pk_heading_data() 会先问这里。命中时直接返回锁定卡片 + 空目录，
 * 连 the_content 过滤器都不执行，简码 / 区块自然也不会把原文渲染出来。
 *
 * @param int $post_id 文章 ID。
 * @return array{html:string,toc:array}|false
 */
function pk_unlock_heading_override( $post_id ) {
	if ( ! pk_unlock_post_locked( $post_id ) ) {
		return false;
	}
	return array(
		'html' => pk_unlock_card_html(
			array(
				'post_id' => $post_id,
				'method'  => pk_unlock_post_method( $post_id ),
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
function pk_unlock_pass_html( $content, $method = '' ) {
	$labels = pk_unlock_method_labels();
	$way    = pk_unlock_normalize_method( $method );
	return '<div class="pk-unlock-pass pk-unlock-open">'
		. '<div class="pk-unlock-open-head">'
		. '<span class="pk-unlock-open-badge">' . pk_unlock_icon( 'check', 14 ) . '</span>'
		. '<strong class="pk-unlock-open-title">已解锁内容</strong>'
		. '<span class="pk-unlock-open-way">' . esc_html( $labels[ $way ] ) . '</span>'
		. '</div>'
		. '<div class="pk-unlock-open-body">' . wp_kses_post( $content ) . '</div>'
		. '</div>';
}

/**
 * 区块回调 / 简码共用的渲染。
 *
 * @param array $args {title,desc,content,teaser,locked,post_id,method,password,lock}。
 * @return string
 */
function pk_unlock_part_html( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'title'    => '',
			'desc'     => '',
			'content'  => '',
			'teaser'   => '',
			'locked'   => true,
			'post_id'  => pk_unlock_current_post(),
			'method'   => 'group',
			'password' => '',
			'lock'     => '',
		)
	);
	$post_id = (int) $args['post_id'];
	$method  = pk_unlock_normalize_method( $args['method'] );
	$content = (string) $args['content'];
	/* 0.13.23：区块上填了专属密码，就按这个锁单独判定；留空回退站点密码 */
	$lock = '' !== trim( (string) $args['lock'] )
		? sanitize_key( (string) $args['lock'] )
		: pk_unlock_lock_for( $post_id, $method, $args['password'] );
	if ( ! $args['locked'] ) {
		return '<div class="pk-unlock-pass">' . wp_kses_post( $content ) . '</div>';
	}
	if ( pk_unlock_can_read( $post_id, $method, $lock ) ) {
		return pk_unlock_pass_html( $content, $method );
	}
	/* 只输出 teaser 和锁定卡片；$content 绝不能拼进这里 */
	$card = pk_unlock_card_html(
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
	return '<div class="pk-unlock-wrap" data-pk-unlock-part="1">' . $card . '</div>';
}

/**
 * 动态区块 render_callback。
 *
 * @param array $attributes 区块属性。
 * @return string
 */
function pk_render_unlock_block( $attributes ) {
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'pk-unlock-block' ) );
	return '<div ' . $wrapper . '>' . pk_unlock_part_html(
		array(
			'title'   => isset( $attributes['title'] ) ? $attributes['title'] : '',
			'desc'    => isset( $attributes['desc'] ) ? $attributes['desc'] : '',
			'content' => isset( $attributes['content'] ) ? $attributes['content'] : '',
			'teaser'  => isset( $attributes['teaser'] ) ? $attributes['teaser'] : '',
			'locked'  => ! empty( $attributes['locked'] ),
			'post_id' => pk_unlock_current_post(),
			/* 四选一：编辑器里选的方式是唯一凭据，缺省回退入群可见 */
			'method'  => isset( $attributes['method'] ) ? $attributes['method'] : 'group',
			/* 0.13.23：编辑器「设置解锁密码」填的值，只作用于本区块 */
			'password' => isset( $attributes['password'] ) ? $attributes['password'] : '',
		)
	) . '</div>';
}

/**
 * [unlock method="like|comment|password|group" password="…" title="…" desc="…"]受保护内容[/unlock]
 *
 * @param array  $atts    属性。
 * @param string $content 内容。
 * @return string
 */
function pk_sc_unlock( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'title'    => '',
			'desc'     => '',
			'teaser'   => '',
			'method'   => 'group',
			'password' => '',
			'locked'   => '1',
		),
		(array) $atts,
		'pk_unlock'
	);
	$locked = ! in_array( strtolower( (string) $atts['locked'] ), array( '0', 'off', 'no', 'false' ), true );
	return pk_unlock_part_html(
		array(
			'title'   => $atts['title'],
			'desc'    => $atts['desc'],
			'teaser'  => $atts['teaser'],
			'content' => $content,
			'locked'   => $locked,
			'post_id'  => pk_unlock_current_post(),
			'method'   => $atts['method'],
			'password' => $atts['password'],
		)
	);
}
add_shortcode( 'unlock', 'pk_sc_unlock' );

/* ============================================================
   前台配置（app.js 兑换与轮询用）
   ============================================================ */
/**
 * 注入到 pianke-app 的解锁配置。
 *
 * @return array
 */
function pk_unlock_frontend_config() {
	return array(
		'enabled'   => pk_unlock_enabled(),
		'statusUrl' => esc_url_raw( rest_url( 'pianke/v1/unlock/status' ) ),
		'redeemUrl' => esc_url_raw( rest_url( 'pianke/v1/unlock/redeem' ) ),
		'nonce'     => wp_create_nonce( 'wp_rest' ),
		'pollMs'    => 8000,
		'text'      => array(
			'left'        => pk_unlock_reason_text( 'left' ),
			'expired'     => pk_unlock_reason_text( 'expired' ),
			'ok'          => '已解锁，正在刷新…',
			'copied'      => '绑定码已复制，发给机器人即可',
			'needLogin'   => '请先登录后再用这种方式解锁',
			'needComment' => '先在本文发表一条评论，发布后会自动解锁',
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
function pk_unlock_allowed_html( $tags, $context ) {
	if ( 'post' !== $context ) {
		return $tags;
	}
	/* 0.13.23：锁定卡片根 div 上的区块锁标识，kses 不能把它吃掉 */
	$tags['div']['data-*']              = true;
	$tags['div']['data-pk-unlock-lock'] = true;
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
		'data-pk-unlock-pass-input' => true,
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
add_filter( 'wp_kses_allowed_html', 'pk_unlock_allowed_html', 14, 2 );

/* ============================================================
   后台联调信息面板（只读，不参与保存）
   ============================================================ */
/**
 * 阅读解锁 tab 底部的接线说明。
 *
 * @return string
 */
function pk_unlock_panel() {
	$base = esc_url_raw( rest_url( 'pianke/v1/unlock' ) );
	$st   = pk_unlock_status_data();
	$rows = array(
		'解锁方式'     => '点赞可见 / 评论可见 / 密码可见 / 入群可见，作者在每个「阅读解锁」区块里四选一，读者只能用选定的那一种；其它方式不显示也不放行',
		'整篇锁方式'   => '自定义字段 pk_unlock_post = 1 时，再用 pk_unlock_method = like|comment|password|group 指定方式（留空默认入群可见）',
		'简码方式'     => '[unlock method="like" password="…"]…[/unlock]，method 同上四选一，留空默认入群可见',
		'区块专属密码' => '把「阅读解锁」切成「密码可见」后，区块上会出现「设置解锁密码」输入框与「生成密码」按钮；填了就只认这串密码，留空回退下面的站点密码。密码只存在文章里，前台 HTML 不含明文',
		'站点解锁密码' => pk_unlock_password_for( 0 ) . '（后台留空时为自动派生；可在文章自定义字段 pk_unlock_password 单独覆盖，也可在区块上单独设置）',
		'群资料回传'   => '插件 v1.2.0 起在 bind / heartbeat 附带 nickname 与 joined_at，个人中心会显示群内昵称与进群时间；平台没给入群时间时按首次绑定记录并标注来源',
		'回调密钥'     => pk_unlock_secret() ? '已配置' : '未配置（仅「入群可见」的机器人回调会返回 503）',
		'状态有效期'   => round( pk_unlock_ttl_seconds() / HOUR_IN_SECONDS ) . ' 小时',
		'当前账号'     => $st['logged'] ? '已登录，绑定码 ' . $st['code'] . '，入群状态 ' . $st['reason'] : '未登录',
	);
	$out = '<h3>机器人接线说明</h3>'
		. '<p class="pk-f-d">在奶狗 WeBot 安装插件 <code>bang-unlock</code>（主题包外的独立文件），'
		. '把插件设置里的 <code>wp_base</code> 填成站点地址、<code>wp_secret</code> 填成与上方完全相同的密钥。'
		. '插件会把绑定与群内心跳用 HMAC-SHA256 签名回调到下面的接口。</p>';
	foreach ( $rows as $k => $v ) {
		$out .= '<p class="pk-f-d"><b>' . esc_html( $k ) . '：</b>' . esc_html( $v ) . '</p>';
	}
	$out .= '<pre class="pk-code">' . esc_html(
		'! wp_base    = ' . home_url( '/' ) . "\n"
		. '! wp_secret = （与上方「机器人回调密钥」一致）' . "\n"
		. '! group_id  = （目标群 ID，留空表示接受任意群）' . "\n\n"
		. 'GET  ' . $base . '/ping       连通测试' . "\n"
		. 'GET  ' . $base . '/status     前台轮询自己的解锁状态' . "\n"
		. 'POST ' . $base . '/redeem     点赞 / 评论 / 密码 兑换（method 必须与区块一致）' . "\n"
		. 'POST ' . $base . '/bind       私聊/群聊发绑定码时回调' . "\n"
		. 'POST ' . $base . '/heartbeat  群内消息续期' . "\n"
		. 'POST ' . $base . '/leave      确认退群时立即上锁'
	) . '</pre>';
	$out .= '<p class="pk-f-d">回调签名为 <code>hmac_sha256(action|code|bot_uid|group_id|ts, secret)</code>，'
		. '请求时间允许 ±5 分钟。带群资料的 bind / heartbeat 用扩展签名 '
		. '<code>hmac_sha256(action|code|bot_uid|group_id|ts|nickname|joined_at, secret)</code>（旧插件不带资料仍用五段签名）。'
		. '群内心跳有效期是兜底：退群后不再续期，到期自动重新上锁并提示进群。</p>';
	$out .= '<p class="pk-f-d"><b>退群即时上锁（平台已有退群用户 ID 统计，任选其一）：</b>'
		. '① 插件订阅平台退群事件（设置项 <code>leave_event</code>，payload 带 <code>userId</code>/<code>groupId</code>）；'
		. '② 插件定时拉取退群名单接口（<code>member_check_api</code> + 模式「退群名单」，返回 <code>left</code>/<code>leaves</code>/<code>records</code> 等 ID 列表，支持 <code>?since=</code> 增量）；'
		. '③ 平台直接把退群事件 POST 到 <code>' . esc_html( $base ) . '/leave</code>，'
		. 'body 为 <code>{"bot_uid":"…","group_id":"…","ts":秒级时间戳,"sign":"…"}</code>，'
		. '其中 <code>sign = hmac_sha256("leave||" + bot_uid + "|" + group_id + "|" + ts, secret)</code>（action=leave、code 留空）。'
		. '三种方式都调用同一个幂等的 <code>/leave</code>，可并存；站内会把状态置 <code>left</code>，'
		. '前台 8 秒内轮询到即换成「请重新进群」提示。</p>';
	$out .= '<p class="pk-f-d"><b>整篇上锁：</b>默认只锁插入了「阅读解锁」区块或 <code>[unlock]</code> 简码的段落；'
		. '要给某篇文章整篇上锁，在文章编辑页添加自定义字段 <code>pk_unlock_post = 1</code>，'
		. '并用 <code>pk_unlock_method</code> 指定四选一的方式即可（后台不再提供开关）。'
		. '测试绑定码是否可用：在群里把上方「当前账号」的绑定码发给机器人，成功后刷新本页会显示入群状态有效。'
		. '注意站点必须是机器人服务器能访问到的公网地址，本地内网地址无法回调。</p>';
	return $out;
}
