<?php
/**
 * 指纹 / Face ID 登录（WebAuthn 通行密钥）
 *
 * 用户的生物识别凭据由设备保管（Android 指纹 / iOS Face ID / Windows Hello / Touch ID），
 * 站点只保存公钥：注册时 navigator.credentials.create()，登录时 credentials.get()，
 * 服务端验签后直接发登录 Cookie，全程不需要密码。
 *
 * - 凭据存 user_meta bang_passkeys（id / 公钥 PEM / 签名计数 / 创建时间）；
 * - 挑战存 transient，120 秒有效：注册用用户身份定位，登录用随机 token 定位；
 * - REST 路由 POST /wp-json/bang/v1/passkey/{options|register|login|remove}。
 *
 * PHP 版库来自 lbuchs/WebAuthn（MIT），已收进 inc/lib/，只在本文件 require。
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* 库源码里用了 PHP 8 的 str_starts_with，7.4 环境先补一个 */
if ( ! function_exists( 'str_starts_with' ) ) {
	/**
	 * polyfill：PHP 7.4 没有 str_starts_with。
	 *
	 * @param string $haystack 待查串。
	 * @param string $needle   前缀。
	 * @return bool
	 */
	function str_starts_with( $haystack, $needle ) {
		return '' === $needle || 0 === strpos( $haystack, $needle );
	}
}

require_once get_template_directory() . '/inc/lib/WebAuthn.php';
require_once get_template_directory() . '/inc/lib/WebAuthnException.php';

const BANG_PASSKEY_TTL = 180; // 挑战有效期（秒）

/**
 * Relying Party 信息：rpId 必须等于站点域名
 *
 * @return array{id:string,name:string}
 */
function bang_webauthn_rp() {
	$host = (string) parse_url( home_url(), PHP_URL_HOST );
	if ( ! $host ) {
		$host = (string) ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : 'localhost' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	}
	return array(
		'id'   => preg_replace( '/^www\./i', '', strtolower( preg_replace( '/:\d+$/', '', trim( $host ) ) ) ),
		'name' => get_bloginfo( 'name' ),
	);
}

/**
 * 新建一个库实例（base64url 编码，客户端可直接转 ArrayBuffer）
 */
function bang_webauthn_instance() {
	$rp = bang_webauthn_rp();
	return new lbuchs\WebAuthn\WebAuthn( $rp['name'], $rp['id'], null, true );
}

/**
 * 某个用户已登记的通行密钥列表
 *
 * @param int $user_id 用户 ID。
 * @return array<int,array<string,mixed>>
 */
function bang_passkeys_of( $user_id ) {
	$list = get_user_meta( (int) $user_id, 'bang_passkeys', true );
	return is_array( $list ) ? array_values( $list ) : array();
}

/**
 * 写回通行密钥列表
 *
 * @param int   $user_id 用户 ID。
 * @param array $list    密钥列表。
 */
function bang_passkeys_save( $user_id, $list ) {
	update_user_meta( (int) $user_id, 'bang_passkeys', array_values( $list ) );
}

/**
 * 挑战存取：注册按用户定位，登录按一次性 token 定位。
 * 二进制挑战不能直接进 option 表（utf8mb4 会丢弃非法字节序列导致读不回），
 * 统一转 hex 再存。
 *
 * @param string|bool $challenge 二进制挑战；false 表示读取。
 * @param string      $scope     reg_{uid} 或 login_{token}。
 * @return string|false
 */
function bang_webauthn_challenge( $challenge, $scope ) {
	$key = 'bang_wc_' . $scope;
	if ( false === $challenge ) {
		$got = get_transient( $key );
		return is_string( $got ) && $got ? hex2bin( $got ) : false;
	}
	set_transient( $key, bin2hex( $challenge ), BANG_PASSKEY_TTL );
	return true;
}

/**
 * 前端配置追加：通行密钥 REST 基址 + 已登记列表（注册时用于防重复）
 *
 * @param array $config bangUser。
 * @return array
 */
function bang_webauthn_config( $config ) {
	$uid = get_current_user_id();
	$config['passkeyBase'] = esc_url_raw( rest_url( 'bang/v1/passkey' ) );
	$config['passkeys']    = $uid ? array_map(
		function ( $item ) {
			return array(
				'id'      => (string) $item['id'],
				'label'   => (string) ( isset( $item['label'] ) ? $item['label'] : '本机' ),
				'created' => (int) ( isset( $item['created'] ) ? $item['created'] : 0 ),
			);
		},
		bang_passkeys_of( $uid )
	) : array();
	return $config;
}
add_filter( 'bang_user_config', 'bang_webauthn_config' );

/**
 * 注册 REST 路由
 */
function bang_webauthn_rest_route() {
	register_rest_route(
		'bang/v1',
		'/passkey/options',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_webauthn_options',
			'permission_callback' => '__return_true',
			'args'                => array(
				'intent' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_key',
				),
			),
		)
	);
	register_rest_route(
		'bang/v1',
		'/passkey/register',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_webauthn_register',
			'permission_callback' => function () {
				return is_user_logged_in();
			},
		)
	);
	register_rest_route(
		'bang/v1',
		'/passkey/login',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_webauthn_login',
			'permission_callback' => '__return_true',
		)
	);
	register_rest_route(
		'bang/v1',
		'/passkey/remove',
		array(
			'methods'             => 'POST',
			'callback'            => 'bang_webauthn_remove',
			'permission_callback' => function () {
				return is_user_logged_in();
			},
		)
	);
}
add_action( 'rest_api_init', 'bang_webauthn_rest_route' );

/**
 * 下发挑战：intent=register（需登录）或 intent=login（游客，带一次性 token）
 *
 * @param WP_REST_Request $request 请求。
 * @return array|WP_Error
 */
function bang_webauthn_options( $request ) {
	try {
		$wa     = bang_webauthn_instance();
		$intent = (string) $request->get_param( 'intent' );

		if ( 'register' === $intent ) {
			$uid = get_current_user_id();
			if ( ! $uid ) {
				return new WP_Error( 'bang_no_login', '登录后才能添加', array( 'status' => 401 ) );
			}
			$user = wp_get_current_user();
			$ids  = array();
			foreach ( bang_passkeys_of( $uid ) as $item ) {
				$ids[] = lbuchs\WebAuthn\Binary\ByteBuffer::fromBase64Url( (string) $item['id'] );
			}
			$args  = $wa->getCreateArgs( pack( 'N', $uid ), $user->user_login, $user->display_name ? $user->display_name : $user->user_login, 30, 'required', 'preferred', null, $ids );
			bang_webauthn_challenge( $wa->getChallenge()->getBinaryString(), 'reg_' . $uid );
			return array(
				'intent'    => 'register',
				'publicKey' => $args->publicKey,
			);
		}

		if ( 'login' === $intent ) {
			$args  = $wa->getGetArgs( array(), 30, true, true, true, true, true, 'preferred' );
			$token = bin2hex( random_bytes( 16 ) );
			bang_webauthn_challenge( $wa->getChallenge()->getBinaryString(), 'login_' . $token );
			return array(
				'intent'    => 'login',
				'token'     => $token,
				'publicKey' => $args->publicKey,
			);
		}

		return new WP_Error( 'bang_bad_intent', '参数不对', array( 'status' => 400 ) );
	} catch ( Exception $e ) {
		return new WP_Error( 'bang_webauthn', '挑战生成失败：' . $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * 注册确认：验签后把公钥存进当前用户的 bang_passkeys
 *
 * @param WP_REST_Request $request 请求。
 * @return array|WP_Error
 */
function bang_webauthn_register( $request ) {
	try {
		$uid = get_current_user_id();
		if ( ! $uid ) {
			return new WP_Error( 'bang_no_login', '登录后才能添加', array( 'status' => 401 ) );
		}
		$challenge = bang_webauthn_challenge( false, 'reg_' . $uid );
		if ( ! $challenge ) {
			return new WP_Error( 'bang_no_challenge', '挑战已过期，请重新添加', array( 'status' => 400 ) );
		}

		$wa           = bang_webauthn_instance();
		$client_json  = lbuchs\WebAuthn\Binary\ByteBuffer::fromBase64Url( (string) $request->get_param( 'clientDataJSON' ) )->getBinaryString();
		$attestation  = lbuchs\WebAuthn\Binary\ByteBuffer::fromBase64Url( (string) $request->get_param( 'attestationObject' ) )->getBinaryString();
		$data         = $wa->processCreate( $client_json, $attestation, $challenge, false, true, false );

		$label = '本机';
		if ( ! empty( $data->certificateSubject ) && preg_match( '/CN=([^,;]+)/', (string) $data->certificateSubject, $m ) ) {
			$label = $m[1];
		}

		$list = bang_passkeys_of( $uid );
		$id   = lbuchs\WebAuthn\Binary\ByteBuffer::fromBase64Url( (string) $request->get_param( 'id' ) )->getHex();
		foreach ( $list as $item ) {
			if ( (string) $item['id'] === $id ) {
				return new WP_Error( 'bang_exists', '这把钥匙已经登记过了', array( 'status' => 400 ) );
			}
		}
		$list[] = array(
			'id'      => $id,
			'pem'     => (string) $data->credentialPublicKey,
			'counter' => (int) $wa->getSignatureCounter(),
			'created' => time(),
			'label'   => $label,
		);
		bang_passkeys_save( $uid, $list );
		delete_transient( 'bang_wc_reg_' . $uid );

		return array(
			'ok'    => true,
			'label' => $label,
			'count' => count( $list ),
		);
	} catch ( Exception $e ) {
		return new WP_Error( 'bang_webauthn', '添加失败：' . $e->getMessage(), array( 'status' => 400 ) );
	}
}

/**
 * 登录确认：凭据 id 反查用户 → 验签 → 发登录 Cookie
 *
 * @param WP_REST_Request $request 请求。
 * @return array|WP_Error
 */
function bang_webauthn_login( $request ) {
	try {
		$token     = (string) $request->get_param( 'token' );
		$challenge = $token ? bang_webauthn_challenge( false, 'login_' . $token ) : false;
		if ( ! $challenge ) {
			return new WP_Error( 'bang_no_challenge', '挑战已过期，请重新验证', array( 'status' => 400 ) );
		}

		$cred_hex = lbuchs\WebAuthn\Binary\ByteBuffer::fromBase64Url( (string) $request->get_param( 'id' ) )->getHex();
		$found    = bang_webauthn_find_user( $cred_hex );
		if ( ! $found ) {
			return new WP_Error( 'bang_no_credential', '这台设备还没有登记过', array( 'status' => 404 ) );
		}
		list( $user, $item ) = $found;

		$wa = bang_webauthn_instance();
		$ok = $wa->processGet(
			lbuchs\WebAuthn\Binary\ByteBuffer::fromBase64Url( (string) $request->get_param( 'clientDataJSON' ) )->getBinaryString(),
			lbuchs\WebAuthn\Binary\ByteBuffer::fromBase64Url( (string) $request->get_param( 'authenticatorData' ) )->getBinaryString(),
			lbuchs\WebAuthn\Binary\ByteBuffer::fromBase64Url( (string) $request->get_param( 'signature' ) )->getBinaryString(),
			(string) $item['pem'],
			$challenge,
			(int) $item['counter'],
			false,
			true
		);
		if ( ! $ok ) {
			throw new Exception( '签名校验未通过' );
		}

		/* 回写签名计数，防克隆检测 */
		$users = bang_passkeys_of( $user->ID );
		foreach ( $users as $k => $v ) {
			if ( (string) $v['id'] === $cred_hex ) {
				$users[ $k ]['counter'] = (int) $wa->getSignatureCounter();
			}
		}
		bang_passkeys_save( $user->ID, $users );
		delete_transient( 'bang_wc_login_' . $token );

		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true, is_ssl() );
		bang_device_issue( $user->ID );

		/* 跳转：优先客户端带来的 redirect_to，校验只允许站内地址 */
		$raw      = (string) $request->get_param( 'redirect_to' );
		$redirect = $raw ? wp_validate_redirect( esc_url_raw( $raw ), bang_user_url() ) : bang_user_redirect();

		return array(
			'loggedIn' => true,
			'name'     => $user->display_name ? $user->display_name : $user->user_login,
			'redirect' => $redirect,
		);
	} catch ( Exception $e ) {
		return new WP_Error( 'bang_webauthn', '验证失败：' . $e->getMessage(), array( 'status' => 400 ) );
	}
}

/**
 * 凭据 id 反查用户与其密钥条目
 *
 * meta 里存的是 PHP 序列化数组，id 是 base64url 字符（不含引号），
 * LIKE 查询足够安全且站点规模内够快。
 *
 * @param string $cred_hex 凭据 id（hex）。
 * @return array{0:WP_User,1:array}|null
 */
function bang_webauthn_find_user( $cred_hex ) {
	$query = new WP_User_Query(
		array(
			'meta_key'     => 'bang_passkeys',
			'meta_compare' => 'LIKE',
			'meta_value'   => $cred_hex,
			'number'       => 5,
		)
	);
	foreach ( $query->get_results() as $user ) {
		foreach ( bang_passkeys_of( $user->ID ) as $item ) {
			if ( (string) $item['id'] === $cred_hex ) {
				return array( $user, $item );
			}
		}
	}
	return null;
}

/**
 * 删除一把通行密钥（只能删自己的）
 *
 * @param WP_REST_Request $request 请求。
 * @return array|WP_Error
 */
function bang_webauthn_remove( $request ) {
	$uid    = get_current_user_id();
	$target = (string) $request->get_param( 'id' );
	$list   = bang_passkeys_of( $uid );
	$kept   = array();
	foreach ( $list as $item ) {
		if ( (string) $item['id'] !== $target ) {
			$kept[] = $item;
		}
	}
	if ( count( $kept ) === count( $list ) ) {
		return new WP_Error( 'bang_not_found', '没有这把钥匙', array( 'status' => 404 ) );
	}
	bang_passkeys_save( $uid, $kept );
	return array( 'ok' => true, 'count' => count( $kept ) );
}
