<?php
/**
 * 足迹图片代理：把外部图片（图床）转成同源地址，供 canvas 明信片等使用。
 *
 * 严格 SSRF 防护（不照搬 Halo 插件 followRedirect 的缺陷）：
 * 1. 只允许 http/https、不允许 userinfo、不允许非标准端口
 * 2. host 必须在「足迹图片墙已保存域名」白名单内
 * 3. 解析 DNS 得到的所有 IP 逐条校验，拒绝内网/环回/链路本地/云元数据段
 * 4. 用 stream / cURL 且 CURLOPT_FOLLOWLOCATION = false，绝不跟随重定向
 * 5. 限制响应大小(10MB)与超时，校验 Content-Type 为图片，输出 nosniff
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'bang_fp_proxy_add_rule' );
/**
 * 注册 rewrite：/footprints/image-proxy → index.php?bang_fp_proxy=1
 */
function bang_fp_proxy_add_rule() {
	add_rewrite_rule( '^footprints/image-proxy/?$', 'index.php?bang_fp_proxy=1', 'top' );
}

add_action( 'init', 'bang_fp_proxy_maybe_flush', 21 );
/**
 * 独立版本闸门刷新 rewrite（不依赖 BANG_VERSION，遵循不改版本号约定）。
 * 新增 /footprints/image-proxy 规则后首次加载刷一次即可。
 */
function bang_fp_proxy_maybe_flush() {
	if ( '1' !== get_option( 'bang_fp_proxy_rules_v' ) ) {
		flush_rewrite_rules( false );
		update_option( 'bang_fp_proxy_rules_v', '1', false );
	}
}

add_filter( 'query_vars', 'bang_fp_proxy_query_var' );
/**
 * 暴露 query var。
 *
 * @param array $vars 变量。
 * @return array
 */
function bang_fp_proxy_query_var( $vars ) {
	$vars[] = 'bang_fp_proxy';
	return $vars;
}

add_action( 'template_redirect', 'bang_fp_proxy_maybe_handle' );
/**
 * 拦截代理请求。
 */
function bang_fp_proxy_maybe_handle() {
	if ( ! get_query_var( 'bang_fp_proxy' ) ) {
		return;
	}
	bang_fp_proxy_handle();
	exit;
}

/**
 * 白名单 host：来自所有足迹的图片墙与特色图域名（缓存 5 分钟）。
 *
 * @return array
 */
function bang_fp_allowed_hosts() {
	$cached = get_transient( 'bang_fp_allowed_hosts' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$hosts  = array();
	$posts  = get_posts(
		array(
			'post_type'      => 'bang_footprint',
			'post_status'    => 'publish',
			'numberposts'    => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'suppress_filters' => true,
		)
	);
	foreach ( $posts as $id ) {
		$urls = array();
		$thumb = get_the_post_thumbnail_url( $id, 'full' );
		if ( $thumb ) {
			$urls[] = $thumb;
		}
		foreach ( bang_fp_gallery_ids( $id ) as $aid ) {
			$u = wp_get_attachment_image_url( $aid, 'full' );
			if ( $u ) {
				$urls[] = $u;
			}
		}
		foreach ( $urls as $u ) {
			$host = wp_parse_url( $u, PHP_URL_HOST );
			if ( is_string( $host ) && '' !== $host ) {
				$hosts[ strtolower( $host ) ] = 1;
			}
		}
	}
	/* 也允许本站自身（上传的图片本地域） */
	$self = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( is_string( $self ) ) {
		$hosts[ strtolower( $self ) ] = 1;
	}
	$out = array_keys( $hosts );
	set_transient( 'bang_fp_allowed_hosts', $out, 5 * MINUTE_IN_SECONDS );
	return $out;
}

/**
 * 判断解析后的 IP 是否危险（内网 / 环回 / 链路本地 / 云元数据）。
 *
 * @param string $ip IP。
 * @return bool
 */
function bang_fp_ip_is_blocked( $ip ) {
	if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return true;
	}
	/* 仅 IPv4（IPv6 一律拒绝，简化防护面） */
	if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
		return true;
	}
	$parts = explode( '.', $ip );
	$a = (int) $parts[0];
	$b = (int) $parts[1];
	if ( in_array( $a, array( 0, 10, 127 ), true ) ) {
		return true;
	}
	if ( 169 === $a && 254 === $b ) {
		return true; /* 云元数据 */
	}
	if ( 172 === $a && $b >= 16 && $b <= 31 ) {
		return true;
	}
	if ( 192 === $a && 168 === $b ) {
		return true;
	}
	if ( 100 === $a && $b >= 64 && $b <= 127 ) {
		return true; /* CGNAT */
	}
	if ( 192 === $a && 0 === $b && 0 === (int) $parts[2] ) {
		return true;
	}
	if ( 198 === $a && ( 18 === $b || 19 === $b ) ) {
		return true;
	}
	return false;
}

/**
 * 校验 URL：协议 / host / 端口 / 白名单 / DNS 解析 IP。
 *
 * @param string $url 待校验 URL。
 * @return string|WP_Error 合法返回规范化 URL，否则 WP_Error。
 */
function bang_fp_proxy_validate_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url || strlen( $url ) > 2048 ) {
		return new WP_Error( 'bad_url', '图片地址不合法' );
	}
	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
		return new WP_Error( 'bad_url', '图片地址不合法' );
	}
	$scheme = strtolower( $parts['scheme'] );
	if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
		return new WP_Error( 'bad_scheme', '只支持 http/https' );
	}
	if ( isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
		return new WP_Error( 'bad_url', '不允许 userinfo' );
	}
	if ( isset( $parts['port'] ) && ! in_array( (int) $parts['port'], array( 80, 443 ), true ) ) {
		return new WP_Error( 'bad_port', '不允许非标准端口' );
	}
	$host = strtolower( $parts['host'] );
	if ( ! in_array( $host, bang_fp_allowed_hosts(), true ) ) {
		return new WP_Error( 'host_not_allowed', '不允许访问的地址' );
	}
	/* DNS 解析后逐条 IP 校验，防 DNS rebinding / 域名指向内网 */
	$ips = function_exists( 'gethostbynamel' ) ? gethostbynamel( $host ) : false;
	if ( is_array( $ips ) ) {
		if ( ! $ips ) {
			return new WP_Error( 'dns_fail', '无法解析地址' );
		}
		foreach ( $ips as $ip ) {
			if ( bang_fp_ip_is_blocked( $ip ) ) {
				return new WP_Error( 'blocked_ip', '不允许访问的地址' );
			}
		}
	} elseif ( bang_fp_ip_is_blocked( $host ) ) {
		/* 解析不到时若是字面量 IP，直接按 IP 校验 */
		return new WP_Error( 'blocked_ip', '不允许访问的地址' );
	}
	return $url;
}

/**
 * 代理处理主流程。
 */
function bang_fp_proxy_handle() {
	$url = isset( $_GET['url'] ) ? wp_unslash( $_GET['url'] ) : ''; /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 只读公开图片 */
	$checked = bang_fp_proxy_validate_url( $url );
	if ( is_wp_error( $checked ) ) {
		status_header( 403 );
		wp_die( esc_html( $checked->get_error_message() ), '图片代理', array( 'response' => 403 ) );
	}
	$url = (string) $checked;

	$response = wp_safe_remote_get(
		$url,
		array(
			'timeout'             => 15,
			'limit_response_size' => 10 * 1024 * 1024,
			'redirection'         => 0, /* 关键：不跟随重定向 */
			'headers'             => array(
				'User-Agent' => 'BangTheme/1.0 (footprint-image-proxy)',
				'Accept'     => 'image/*,*/*;q=0.8',
			),
		)
	);
	if ( is_wp_error( $response ) ) {
		status_header( 502 );
		wp_die( '代理图片失败', '图片代理', array( 'response' => 502 ) );
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		status_header( 502 );
		wp_die( '代理图片失败', '图片代理', array( 'response' => 502 ) );
	}
	$type = strtolower( (string) wp_remote_retrieve_header( $response, 'content-type' ) );
	if ( 0 !== strpos( $type, 'image/' ) ) {
		status_header( 415 );
		wp_die( '远端返回的不是图片', '图片代理', array( 'response' => 415 ) );
	}
	$body = wp_remote_retrieve_body( $response );
	if ( '' === $body || strlen( $body ) > 10 * 1024 * 1024 ) {
		status_header( 413 );
		wp_die( '图片为空或超过 10MB', '图片代理', array( 'response' => 413 ) );
	}
	nocache_headers();
	header( 'Cache-Control: public, max-age=86400' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Type: ' . sanitize_mime_type( $type ) );
	echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 二进制图片
}
