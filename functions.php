<?php
/**
 * Bang —— 主题引导文件
 * 只负责加载与注册，具体输出函数放在 inc/template-tags.php
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BANG_VERSION', '0.13.32' );

/**
 * 一次性迁移：旧 pianke 时代的 pk_ 前缀 option / meta key 复制成 bang_。
 * 旧 key 保留不删（回滚仍可读），新代码只读新 key；已存在的 bang_ key 不覆盖。
 * 只在引导阶段跑一次，标记 option 记录完成状态。
 */
function bang_migrate_legacy_keys() {
	if ( get_option( 'bang_legacy_keys_migrated' ) ) {
		return;
	}
	global $wpdb;
	$like = $wpdb->esc_like( 'pk_' ) . '%';

	$options = $wpdb->get_results( "SELECT option_id, option_name FROM {$wpdb->options} WHERE option_name LIKE '{$like}'" );
	foreach ( (array) $options as $row ) {
		$new = 'bang_' . substr( $row->option_name, 3 );
		$hit = $wpdb->get_var( $wpdb->prepare( "SELECT option_id FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $new ) );
		if ( ! $hit ) {
			$wpdb->update( $wpdb->options, array( 'option_name' => $new ), array( 'option_id' => $row->option_id ) );
		}
	}

	/* 自定义文章类型：post_type 是存在数据库里的字符串，旧说说/足迹/图集不迁移会全部读不到 */
	$types = $wpdb->get_col( "SELECT DISTINCT post_type FROM {$wpdb->posts} WHERE post_type LIKE '{$like}'" );
	foreach ( (array) $types as $type ) {
		$new = 'bang_' . substr( $type, 3 );
		$wpdb->update( $wpdb->posts, array( 'post_type' => $new ), array( 'post_type' => $type ) );
	}
	$taxes = $wpdb->get_col( "SELECT DISTINCT taxonomy FROM {$wpdb->term_taxonomy} WHERE taxonomy LIKE '{$like}'" );
	foreach ( (array) $taxes as $tax ) {
		$new = 'bang_' . substr( $tax, 3 );
		$wpdb->update( $wpdb->term_taxonomy, array( 'taxonomy' => $new ), array( 'taxonomy' => $tax ) );
	}

	$meta_tables = array(
		$wpdb->postmeta    => 'meta_id',
		$wpdb->usermeta    => 'umeta_id',
		$wpdb->commentmeta => 'meta_id',
	);
	foreach ( $meta_tables as $table => $idcol ) {
		$rows = $wpdb->get_results( "SELECT {$idcol} AS id, meta_key FROM {$table} WHERE meta_key LIKE '{$like}'" );
		foreach ( (array) $rows as $row ) {
			$new = 'bang_' . substr( $row->meta_key, 3 );
			$hit = $wpdb->get_var( $wpdb->prepare( "SELECT {$idcol} FROM {$table} WHERE meta_key = %s LIMIT 1", $new ) );
			if ( ! $hit ) {
				$wpdb->update( $table, array( 'meta_key' => $new ), array( $idcol => $row->id ) );
			}
		}
	}
	wp_cache_flush();
	update_option( 'bang_legacy_keys_migrated', 1 );
}
bang_migrate_legacy_keys();

require_once get_template_directory() . '/inc/template-tags.php';
require_once get_template_directory() . '/inc/captcha.php';
require_once get_template_directory() . '/inc/geetest.php';
require_once get_template_directory() . '/inc/auth.php';
require_once get_template_directory() . '/inc/slides.php';
require_once get_template_directory() . '/inc/ticker.php';
require_once get_template_directory() . '/inc/subscribe.php';
require_once get_template_directory() . '/inc/notice.php';
require_once get_template_directory() . '/inc/site-pages.php';
require_once get_template_directory() . '/inc/blocks.php';
require_once get_template_directory() . '/inc/content-blocks.php';
require_once get_template_directory() . '/inc/unlock.php';
require_once get_template_directory() . '/inc/settings.php';
require_once get_template_directory() . '/inc/ai.php';
require_once get_template_directory() . '/inc/publisher.php';
require_once get_template_directory() . '/inc/seo.php';
require_once get_template_directory() . '/inc/say.php';
require_once get_template_directory() . '/inc/author.php';
require_once get_template_directory() . '/inc/social.php';
require_once get_template_directory() . '/inc/ranking.php';
require_once get_template_directory() . '/inc/sidebar.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/footprint.php';
require_once get_template_directory() . '/inc/footprint-proxy.php';
require_once get_template_directory() . '/inc/gallery.php';
require_once get_template_directory() . '/inc/user.php';
require_once get_template_directory() . '/inc/webauthn.php';
require_once get_template_directory() . '/inc/quick-login.php';
require_once get_template_directory() . '/inc/asset-guard.php';
require_once get_template_directory() . '/inc/comment-moderation.php';
require_once get_template_directory() . '/inc/link-apply.php';
require_once get_template_directory() . '/inc/smtp.php';
require_once get_template_directory() . '/inc/music.php';
/* 【演示专用】打包开源时删除下一行（详见 inc/music-demo.php 顶部说明） */
require_once get_template_directory() . '/inc/music-demo.php';

/**
 * 主题功能注册
 */
function bang_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);
	add_theme_support( 'post-formats', array( 'aside', 'image', 'gallery', 'quote' ) );

	/* 编辑器所见即所得：区块样式前后台共用，编辑器排版单独一份 */
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/blocks.css', 'assets/css/editor.css' ) );
}
add_action( 'after_setup_theme', 'bang_setup' );

/**
 * 压缩资源寻址：只有 min 文件存在且不比源文件旧才启用。
 * 这样改完源码忘了重建时会自动回退，不会让前台用到过期样式。
 */
function bang_asset_uri( $source, $minified ) {
	if ( ! bang_opt( 'minify_assets' ) ) {
		return get_template_directory_uri() . '/' . $source;
	}
	$src = get_template_directory() . '/' . $source;
	$min = get_template_directory() . '/' . $minified;
	if ( file_exists( $min ) && file_exists( $src ) && filemtime( $min ) >= filemtime( $src ) ) {
		return get_template_directory_uri() . '/' . $minified;
	}
	return get_template_directory_uri() . '/' . $source;
}

/**
 * 本地资源版本号：取实际落地文件的修改时间。
 * 改完 CSS/JS（含压缩重建）后 URL 上的 ver 自动变化，浏览器 / CDN 立即拉新文件，
 * 不用再手动升 BANG_VERSION。判断逻辑与 bang_asset_uri() 保持一致，确保 ver 与 URI 指向同一文件。
 */
function bang_asset_ver( $source, $minified = '' ) {
	$src  = get_template_directory() . '/' . $source;
	$path = $src;
	if ( $minified && bang_opt( 'minify_assets' ) ) {
		$min = get_template_directory() . '/' . $minified;
		if ( file_exists( $min ) && file_exists( $src ) && filemtime( $min ) >= filemtime( $src ) ) {
			$path = $min;
		}
	}
	return file_exists( $path ) ? (string) filemtime( $path ) : BANG_VERSION;
}

/**
 * 前端资源。style.css 仍是主题元信息文件，前台可指向压缩副本。
 */
function bang_assets() {
	wp_enqueue_style(
		'bang-style',
		bang_asset_uri( 'style.css', 'assets/css/style.min.css' ),
		array(),
		bang_asset_ver( 'style.css', 'assets/css/style.min.css' )
	);
	/* 灯箱库：PhotoSwipe 5.4.4（MIT），UMD 版本整包放在主题内，零 CDN。
	   官方 CSS 先加载，Bang 的视觉覆盖依赖它再加载，升级时不必改库文件。 */
	wp_enqueue_style(
		'bang-photoswipe',
		get_template_directory_uri() . '/assets/vendor/photoswipe/photoswipe.css',
		array(),
		'5.4.4'
	);
	wp_enqueue_style(
		'bang-photoswipe-bang',
		get_template_directory_uri() . '/assets/css/photoswipe-bang.css',
		array( 'bang-photoswipe' ),
		bang_asset_ver( 'assets/css/photoswipe-bang.css' )
	);
	wp_enqueue_script(
		'bang-photoswipe',
		get_template_directory_uri() . '/assets/vendor/photoswipe/photoswipe.umd.min.js',
		array(),
		'5.4.4',
		true
	);
	wp_enqueue_script(
		'swup',
		get_template_directory_uri() . '/assets/js/vendor/swup.umd.js',
		array(),
		bang_asset_ver( 'assets/js/vendor/swup.umd.js' ),
		true
	);
	wp_enqueue_script(
		'bang-qrcode',
		get_template_directory_uri() . '/assets/js/vendor/qrcode.min.js',
		array(),
		'1.4.4',
		true
	);
	/* 多语言翻译：本地 translate.js（作者管雷鸣，开源仓库 xnx3/translate），零 CDN，
	   必须在 bang-app 之前加载，app.js 里用 window.translate 完成配置与执行。 */
	wp_enqueue_script(
		'bang-translate',
		get_template_directory_uri() . '/assets/js/translate.js',
		array(),
		bang_asset_ver( 'assets/js/translate.js' ),
		true
	);
	wp_enqueue_script(
		'bang-app',
		bang_asset_uri( 'assets/js/app.js', 'assets/js/app.min.js' ),
		array( 'swup', 'bang-photoswipe', 'bang-qrcode', 'bang-translate' ),
		bang_asset_ver( 'assets/js/app.js', 'assets/js/app.min.js' ),
		true
	);
		/* 前端 AJAX（搜索 / 评论列表 / 评论提交）需要的地址与令牌 */
		wp_localize_script(
			'bang-app',
			'bangFront',
			array(
				'ajaxUrl'  => wp_make_link_relative( admin_url( 'admin-ajax.php' ) ),
				'nonce'    => wp_create_nonce( 'bang_ajax' ),
				'homeUrl'  => home_url( '/' ),
				'authUrl'  => home_url( '/login/' ),
			'capUrl'   => esc_url_raw( rest_url( 'bang/v1/captcha' ) ),
			'capNonce' => bang_captcha_nonce(),
			/* 邮箱验证码：改邮箱 / 改密码时向当前绑定邮箱发码 */
			'emailCodeUrl'  => esc_url_raw( rest_url( 'bang/v1/email-code/send' ) ),
			'emailCodeNonce' => wp_create_nonce( 'wp_rest' ),
			/* 找回密码：游客发码 + 重置 */
			'lostSendUrl'  => esc_url_raw( rest_url( 'bang/v1/lost/send' ) ),
			'lostResetUrl' => esc_url_raw( rest_url( 'bang/v1/lost/reset' ) ),
		/* 人机校验：local=自研滑块；geetest=极验 v4。只下发 captchaId，Key 只留服务端 */
		'capMode'      => bang_captcha_mode(),
		'capCaptchaId' => 'geetest' === bang_captcha_mode() ? trim( (string) bang_opt( 'geetest_id' ) ) : '',
		'capGeoUrl'    => esc_url_raw( rest_url( 'bang/v1/geetest' ) ),
		/* 直接访问 /{id}/talk 时首屏自动展开评论区 */
			'talkOpen' => bang_is_talk_request() ? 1 : 0,
			/* 界面与交互开关：后台「Bang 设置 → 界面与交互」 */
			'ui'       => bang_ui_config(),
			)
		);
	/* 点赞：登录态、已点赞文章 ID、REST 地址与 nonce */
	wp_localize_script( 'bang-app', 'bangUser', bang_social_config() );
	/* 阅读解锁（0.13.20）：状态轮询地址、文案与绑定码展示所需配置 */
	wp_localize_script( 'bang-app', 'bangUnlock', bang_unlock_frontend_config() );
	/* 热门文章：浏览量上报地址与令牌 */
	wp_localize_script( 'bang-app', 'bangRank', bang_rank_frontend_config() );
	/* 足迹 / 图片集：地图 Key、默认视图与足迹 REST 地址（Key 只在需要时下发，安全密钥留服务端） */
	wp_localize_script(
		'bang-app',
		'bangMap',
		array(
			'footprintOn' => (int) bang_opt( 'footprint_on' ),
			'jsKey'       => trim( (string) bang_opt( 'footprint_js_key' ) ),
			'restUrl'     => esc_url_raw( rest_url( 'bang/v1/footprints' ) ),
		)
	);
	/* 多语言翻译：本地库配置。local=网页原文语种，languages=可选语种白名单，
	   default=后台「语言首选」（detect 跟随浏览器 / 指定语言），用户手动选择后优先并长期记住。 */
	$bang_lang_default = bang_opt( 'lang_default' );
	$bang_lang_allow   = array( 'detect', 'chinese_simplified', 'chinese_traditional', 'english', 'japanese', 'korean' );
	if ( ! in_array( $bang_lang_default, $bang_lang_allow, true ) ) {
		$bang_lang_default = 'detect';
	}
	wp_localize_script(
		'bang-app',
		'bangTranslate',
		array(
			'on'        => 1,
			'local'     => 'chinese_simplified',
			'default'   => $bang_lang_default,
			'languages' => 'chinese_simplified,chinese_traditional,english,japanese,korean',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'bang_assets' );

/**
 * 区块样式改为非阻塞加载：首页卡片本身不用区块 CSS，
 * 没必要让它在首屏白白阻塞 300ms；<noscript> 里留一份正常引用兜底。
 */
function bang_blocks_style_tag( $tag, $handle ) {
	if ( 'bang-blocks' !== $handle || ! bang_opt( 'lazy_blocks_css' ) || is_admin() ) {
		return $tag;
	}
	$lazy = str_replace(
		array( "media='all'", 'media="all"' ),
		"media=\"print\" onload=\"this.media='all'\"",
		$tag
	);
	if ( $lazy === $tag ) {
		return $tag;
	}
	return $lazy . "\n<noscript>" . $tag . '</noscript>';
}
add_filter( 'style_loader_tag', 'bang_blocks_style_tag', 10, 2 );

/**
 * 清理 WordPress 默认注入的冗余头部：emoji（约 23KB 脚本）、RSD、
 * wlwmanifest、generator、短链接、oEmbed 发现链接、REST link 等。
 * 可在「Bang 设置 → 高级」里关掉。
 */
function bang_disable_emoji_tinymce( $plugins ) {
	return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
}

function bang_cleanup_wp_head() {
	if ( ! bang_opt( 'clean_wp_head' ) ) {
		return;
	}
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'tiny_mce_plugins', 'bang_disable_emoji_tinymce' );
	add_filter( 'emoji_svg_url', '__return_false' );

	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'rest_output_link_wp_head' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );
}
add_action( 'after_setup_theme', 'bang_cleanup_wp_head', 20 );

/**
 * 站点图标（Favicon）—— 0.13.14
 *
 * 后台「Bang 设置 → 常规 → 站点图标」填了地址就以它为准：过滤核心
 * get_site_icon_url()，让 wp_site_icon() 自动输出全套 rel=icon /
 * apple-touch-icon / msapplication-TileImage，不重复、不冲突，也无需摘钩子。
 * 留空时返回核心原值，WordPress 自带的站点图标（外观 → 自定义）照常生效。
 */
function bang_filter_site_icon_url( $url, $size, $blog_id ) {
	$custom = trim( (string) bang_opt( 'site_icon_url' ) );
	if ( '' !== $custom ) {
		return esc_url_raw( $custom );
	}
	return $url;
}
add_filter( 'get_site_icon_url', 'bang_filter_site_icon_url', 10, 3 );

/**
 * Speculation Rules：让浏览器在 hover / 卡片接近视口时提前预热文章页。
 * prefetch 覆盖同源内链（排除后台 / 登录 / 用户中心 / 外链中转），
 * prerender 只给卡片流里的文章链接，避免把整站都渲染一遍。
 * 预渲染请求本身带 Sec-Purpose: prefetch;prerender，链式预渲染没有意义，直接不输出规则。
 */
function bang_speculation_rules() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	/* WP 6.8+ 在 wp_footer 自带一套通用 prefetch 规则；主题已有更精确的按需规则，
	   禁用核心版避免两套规则重复预取（返回 null 是核心约定的「完全关闭」）。 */
	add_filter( 'wp_speculation_rules_configuration', '__return_null' );
	$purpose = isset( $_SERVER['HTTP_SEC_PURPOSE'] ) ? (string) $_SERVER['HTTP_SEC_PURPOSE'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( false !== stripos( $purpose, 'prerender' ) ) {
		return;
	}
	$rules = array(
		'prefetch'  => array(
			array(
				'source'    => 'document',
				'where'     => array(
					'and' => array(
						array( 'href_matches' => '/*' ),
						array( 'not' => array( 'href_matches' => array( '/wp-admin/*', '/wp-login.php*', '/user/*', '/go/*' ) ) ),
						array( 'not' => array( 'selector_matches' => '[data-no-swup], [data-bang-logout], [download], [target="_blank"]' ) ),
					),
				),
				'eagerness' => 'moderate',
			),
		),
		'prerender' => array(
			array(
				'source'    => 'document',
				'where'     => array( 'selector_matches' => '.feed .card[href]' ),
				'eagerness' => 'conservative',
			),
		),
	);
	echo '<script type="speculationrules" id="bang-spec-base">' . wp_json_encode( $rules, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}
add_action( 'wp_head', 'bang_speculation_rules', 3 );

/**
 * 内容宽度
 */
function bang_content_width() {
	$GLOBALS['content_width'] = 760;
}
add_action( 'after_setup_theme', 'bang_content_width', 0 );

/**
 * 演示站不依赖 Gutenberg 的全站样式，避免覆盖主题排版
 */
function bang_trim_core_styles() {
	if ( ! is_admin() ) {
		wp_dequeue_style( 'wp-block-library-theme' );
	}
}
add_action( 'wp_enqueue_scripts', 'bang_trim_core_styles', 20 );

/**
 * 前台隐藏 WordPress 管理工具栏（黑条），后台 /wp-admin/ 保持原样
 */
function bang_hide_front_admin_bar( $show ) {
	return is_admin() ? $show : false;
}
add_filter( 'show_admin_bar', 'bang_hide_front_admin_bar' );

/**
 * 站点副标题不再用于浏览器标签标题
 */
function bang_document_title_parts( $parts ) {
	unset( $parts['tagline'] );
	return $parts;
}
add_filter( 'document_title_parts', 'bang_document_title_parts' );

/**
 * 分类归档复用首页模板
 *
 * 地址栏切到真实分类/标签链接（plain permalink 下为 ?cat=ID / ?tag=slug）后，刷新、分享、
 * 浏览器前进后退都要保持筛选态与卡片展开转场，所以不走简版 archive.php。
 * 分类/标签链接由 get_category_link() / get_tag_link() 输出，将来开启伪静态会自动变成
 * /category/slug/、/tag/slug/。
 */
function bang_category_template( $template ) {
	/* 分类归档回归 archive.php：说说同款居中骨架 + 顶部分类名/篇数（0.13.29 之后）。
	   标签归档仍复用首页模板，保留首页的筛选态与卡片展开转场。 */
	if ( ! is_admin() && is_tag() ) {
		$bang_front = get_template_directory() . '/front-page.php';
		if ( file_exists( $bang_front ) ) {
			return $bang_front;
		}
	}
	return $template;
}
add_filter( 'template_include', 'bang_category_template' );

/**
 * 评论者个人网站的中转确认页（?bang_go=<url>）
 *
 * 评论里的网站图标以 target="_blank" 打开本页，显示目标域名、由用户确认后再前往，
 * 目标站最终落在新标签页里。全程 no-referrer / noopener / nofollow，不把来源或
 * 窗口权限交给外部站点；bang_safe_comment_site() 只放行 http/https，挡掉伪协议。
 */
function bang_external_go_page() {
	if ( ! isset( $_GET['bang_go'] ) ) {
		return;
	}

	$url = bang_safe_comment_site( wp_unslash( $_GET['bang_go'] ) );
	if ( ! $url ) {
		wp_die( '链接无效或不被允许', esc_html( get_bloginfo( 'name' ) ), array( 'response' => 400 ) );
	}

	$host = wp_parse_url( $url, PHP_URL_HOST );
	$home = home_url( '/' );
	$back = wp_get_referer();
	if ( ! $back || wp_parse_url( $back, PHP_URL_HOST ) !== wp_parse_url( $home, PHP_URL_HOST ) ) {
		$back = $home;
	}

	nocache_headers();
	header( 'Referrer-Policy: no-referrer' );
	header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
	?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex,nofollow">
<title>即将离开 <?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
<script>try{var t=localStorage.getItem('bang-theme');if(t)document.documentElement.setAttribute('data-theme',t)}catch(e){}</script>
<style>
*{box-sizing:border-box}
:root{--bg:#FFFFFF;--ink:#000000;--line:#E5E5E5;--ink2:#666666;--ink3:#999999;--radius:12px}
@media(prefers-color-scheme:dark){:root:not([data-theme="light"]){--bg:#000000;--ink:#FFFFFF;--line:#333333;--ink2:#AAAAAA;--ink3:#777777}}
:root[data-theme="dark"]{--bg:#000000;--ink:#FFFFFF;--line:#333333;--ink2:#AAAAAA;--ink3:#777777}
html,body{height:100%}
body{margin:0;display:flex;align-items:center;justify-content:center;padding:20px;background:var(--bg);color:var(--ink);font-family:-apple-system,BlinkMacSystemFont,"PingFang SC","Microsoft YaHei",sans-serif;font-size:15px;line-height:1.75;-webkit-font-smoothing:antialiased}
.go-card{width:100%;max-width:420px;padding:30px 26px;border:1px solid var(--line);border-radius:var(--radius);text-align:center}
.go-site{display:block;margin-bottom:16px;font-size:12px;letter-spacing:.14em;color:var(--ink3)}
.go-card h1{margin:0 0 10px;font-size:20px;font-weight:600}
.go-card p{margin:0 0 20px;font-size:13px;line-height:1.8;color:var(--ink2)}
.go-host{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:10px 12px;border:1px solid var(--line);border-radius:var(--radius);font-size:13px;color:var(--ink)}
.go-btn{display:block;margin-top:18px;padding:11px 16px;border:1px solid var(--ink);border-radius:var(--radius);background:var(--ink);color:var(--bg);font-size:14px;text-decoration:none;transition:opacity .2s}
.go-btn:hover{opacity:.82}
.go-back{display:inline-block;margin-top:14px;font-size:12px;color:var(--ink3);text-decoration:none}
.go-back:hover{color:var(--ink)}
</style>
</head>
<body>
<main class="go-card">
<span class="go-site"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
<h1>即将离开本站</h1>
<p>这是评论者留下的外部链接，与本站无关，确认后再继续。</p>
<span class="go-host"><?php echo esc_html( $host ); ?></span>
<a class="go-btn" href="<?php echo esc_url( $url ); ?>" rel="noopener noreferrer nofollow" referrerpolicy="no-referrer">继续访问</a>
<a class="go-back" href="<?php echo esc_url( $back ); ?>" rel="nofollow">返回上一页</a>
</main>
</body>
</html>
	<?php
	exit;
}
add_action( 'template_redirect', 'bang_external_go_page' );

/**
 * 评论区独立地址 /{文章ID}/talk
 *
 * 固定链接是 /%post_id%/，所以文章是 /1/、评论区是 /1/talk。
 * 主查询仍是这篇文章（single.php 正常渲染阅读层），只多一个 bang_talk 标志，
 * 前端据此在首屏自动展开评论抽屉；链接可复制、可直接落地，不必先点按钮。
 */
function bang_talk_add_rules() {
	add_rewrite_rule( '^([0-9]+)/talk/?$', 'index.php?p=$matches[1]&bang_talk=1', 'top' );
}
add_action( 'init', 'bang_talk_add_rules', 5 );

function bang_talk_query_vars( $vars ) {
	$vars[] = 'bang_talk';
	return $vars;
}
add_filter( 'query_vars', 'bang_talk_query_vars' );

/* 规则只在升版本后软刷新一次：不写 .htaccess，nginx / IIS 下同样生效 */
function bang_talk_maybe_flush() {
	if ( (string) get_option( 'bang_talk_rules_v' ) !== (string) BANG_VERSION ) {
		flush_rewrite_rules( false );
		update_option( 'bang_talk_rules_v', BANG_VERSION, false );
	}
}
add_action( 'init', 'bang_talk_maybe_flush', 20 );

/* WP 默认会把带额外 query var 的 /1/talk 301 回 /1/，这里放行 */
function bang_talk_keep_url( $redirect, $requested ) {
	if ( get_query_var( 'bang_talk' ) ) {
		return false;
	}
	$path = wp_parse_url( $requested, PHP_URL_PATH );
	if ( $path && preg_match( '#/[0-9]+/talk/?$#', $path ) ) {
		return false;
	}
	return $redirect;
}
add_filter( 'redirect_canonical', 'bang_talk_keep_url', 10, 2 );

/**
 * 当前请求是不是评论区地址（供 bangFront 注入首屏标志）
 */
function bang_is_talk_request() {
	return (bool) get_query_var( 'bang_talk' );
}

/**
 * 前端 AJAX：搜索 / 评论列表 / 评论提交
 * 地址与令牌由 bang_assets() 通过 bangFront 注入；评论提交带 nonce 校验。
 */

/**
 * 站内搜索：返回匹配文章的轻量卡片数据，供文章页在当前页内联展示结果。
 * 同时匹配标题/正文与分类名，与首页客户端筛选（标题 + 摘要 + 分类）保持一致。
 */
function bang_ajax_search() {
	$q = isset( $_GET['q'] ) ? trim( wp_unslash( $_GET['q'] ) ) : '';
	$items = array();
	if ( '' !== $q ) {
		$ids = array();
		$by_title = get_posts(
			array(
				'posts_per_page'   => 20,
				'post_status'      => 'publish',
				'orderby'          => 'date',
				'order'            => 'DESC',
				's'                => $q,
				'suppress_filters' => true,
				'fields'           => 'ids',
			)
		);
		foreach ( $by_title as $id ) { $ids[] = (int) $id; }

		/* 分类名匹配：WordPress 自带 s 不搜分类，这里补上，和首页筛选对齐 */
		$cats = get_terms(
			array(
				'taxonomy'   => 'category',
				'name__like' => $q,
				'hide_empty' => true,
			)
		);
		if ( ! is_wp_error( $cats ) && $cats ) {
			$cat_ids   = wp_list_pluck( $cats, 'term_id' );
			$cat_posts = get_posts(
				array(
					'posts_per_page'   => 20,
					'post_status'      => 'publish',
					'orderby'          => 'date',
					'order'            => 'DESC',
					'post_type'        => 'post',
					'tax_query'        => array(
						array(
							'taxonomy' => 'category',
							'field'    => 'term_id',
							'terms'    => $cat_ids,
						),
					),
					'suppress_filters' => true,
					'fields'           => 'ids',
				)
			);
			foreach ( $cat_posts as $id ) { $ids[] = (int) $id; }
		}

		$ids = array_unique( $ids );
		$ids = array_slice( $ids, 0, 20 );
		foreach ( $ids as $pid ) {
			$items[] = array(
				'id'      => $pid,
				'title'   => get_the_title( $pid ),
				'link'    => get_permalink( $pid ),
				'excerpt' => function_exists( 'bang_ai_excerpt' ) ? bang_ai_excerpt( $pid ) : bang_plain_excerpt( $pid ),
				'kind'    => bang_kind_label( bang_format_key( $pid ) ),
				'date'    => get_the_date( 'Y.m.d', $pid ),
				'topic'   => bang_topic( $pid ),
			);
		}
	}
	wp_send_json_success( array( 'items' => $items ) );
}
add_action( 'wp_ajax_bang_search', 'bang_ajax_search' );
add_action( 'wp_ajax_nopriv_bang_search', 'bang_ajax_search' );

/**
 * 文章索引：给浏览器内 AI 语义搜索（transformers.js）用。
 * 只回标题 + 摘要等公开字段，向量在客户端计算与缓存，服务端零推理负担。
 * sig 是索引指纹（最新文章时间 + 数量），内容变了客户端缓存自动失效。
 */
function bang_ajax_post_index() {
	$ids = get_posts(
		array(
			'posts_per_page'   => 200,
			'post_status'      => 'publish',
			'post_type'        => 'post',
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => true,
			'fields'           => 'ids',
		)
	);
	$items = array();
	foreach ( $ids as $pid ) {
		$items[] = array(
			'id'      => (int) $pid,
			'title'   => get_the_title( $pid ),
			'link'    => get_permalink( $pid ),
			'excerpt' => function_exists( 'bang_ai_excerpt' ) ? bang_ai_excerpt( $pid ) : bang_plain_excerpt( $pid ),
			'kind'    => bang_kind_label( bang_format_key( $pid ) ),
			'date'    => get_the_date( 'Y.m.d', $pid ),
			'topic'    => bang_topic( $pid ),
			'modified' => (int) get_post_modified_time( 'U', true, $pid ),
		);
	}
	/* 指纹覆盖全部文章的修改时间：改旧文章标题 / 摘要也能让客户端向量缓存失效 */
	$sig_parts = array();
	foreach ( $items as $item ) {
		$sig_parts[] = $item['id'] . ':' . $item['modified'];
	}
	$sig = md5( implode( '|', $sig_parts ) );
	wp_send_json_success(
		array(
			'sig'   => $sig,
			'items' => $items,
		)
	);
}
add_action( 'wp_ajax_bang_post_index', 'bang_ajax_post_index' );
add_action( 'wp_ajax_nopriv_bang_post_index', 'bang_ajax_post_index' );

/**
 * 评论列表：返回某文章已审核评论的 <li> 片段，供 pjax 加载 / 刷新。
 */
function bang_ajax_comment_list() {
	$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
	$post    = get_post( $post_id );
	if ( ! $post || 'publish' !== $post->post_status ) {
		wp_send_json_error( '文章不存在' );
	}
	/* 与首屏共用树形渲染，父子层级不会因为 AJAX 刷新而丢失 */
	$html = bang_comments_list_html( $post_id );
	wp_send_json_success(
		array(
			'count' => (int) get_comments_number( $post_id ),
			'has'   => ( '' !== $html ),
			'html'  => $html,
		)
	);
}
add_action( 'wp_ajax_bang_comment_list', 'bang_ajax_comment_list' );
add_action( 'wp_ajax_nopriv_bang_comment_list', 'bang_ajax_comment_list' );

/**
 * 提交评论：复刻 wp-comments-post.php 的安全流程（wp_new_comment 内部走
 * wp_allow_comment 审批逻辑），返回新评论片段或待审核提示，供前端 pjax 插入。
 */
function bang_ajax_comment_post() {
	check_ajax_referer( 'bang_ajax', 'nonce' );

	$post_id = isset( $_POST['comment_post_ID'] ) ? (int) $_POST['comment_post_ID'] : 0;
	$post    = get_post( $post_id );
	if ( ! $post || 'publish' !== $post->post_status ) {
		wp_send_json_error( '文章不存在' );
	}
	if ( ! comments_open( $post_id ) ) {
		wp_send_json_error( '评论已关闭' );
	}
	/* 0.13.19：身份一律由登录态提供，不再接受游客手填称呼/邮箱/网站。
	   前端 submitComment 会先拦下并打开登录弹窗，这里只作兜底。 */
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( '请先登录后再评论' );
	}

	$content = isset( $_POST['comment'] ) ? trim( wp_unslash( $_POST['comment'] ) ) : '';
	if ( '' === $content ) {
		wp_send_json_error( '评论内容不能为空' );
	}

	/* 违禁词审核：见 inc/comment-moderation.php。
	   显式拦在写入前，避免 preprocess_comment 里的 wp_die 在 AJAX 场景吐出 HTML 页面。 */
	$bang_mod = function_exists( 'bang_moderation_handle' ) ? bang_moderation_handle( $content, '文章评论' ) : null;
	if ( $bang_mod && ! $bang_mod['ok'] ) {
		wp_send_json_error( $bang_mod['message'] );
	}
	if ( $bang_mod ) {
		$content = $bang_mod['content'];
	}

	/* 人机校验：滑块票据由 bang/v1/captcha 签发，注册与评论共用 */
	$ticket = isset( $_POST['bang_cap'] ) ? sanitize_text_field( wp_unslash( $_POST['bang_cap'] ) ) : '';
	if ( ! bang_captcha_ok( $ticket ) ) {
		wp_send_json_error(
			array(
				'code'    => 'captcha',
				'message' => '请先完成滑块验证',
			)
		);
	}

	/* 回复目标必须属于同一篇文章且已审核，避免把评论挂到别人的文章 / 未审核评论下 */
	$parent_id = isset( $_POST['comment_parent'] ) ? (int) $_POST['comment_parent'] : 0;
	if ( $parent_id > 0 ) {
		$parent = get_comment( $parent_id );
		/* 注意：get_comment() 里已审核是 '1'，'approve' 只是 get_comments() 的查询参数 */
		if ( ! $parent || (int) $parent->comment_post_ID !== $post_id || 1 !== (int) $parent->comment_approved ) {
			$parent_id = 0;
		}
	}

	$commentdata = array(
		'comment_post_ID'   => $post_id,
		'comment_content'   => $content,
		'comment_type'      => '',
		'comment_parent'    => $parent_id,
		'user_id'           => get_current_user_id(),
		'comment_author_IP' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		'comment_agent'     => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
	);

	$comment_id = wp_new_comment( $commentdata, true );
	if ( is_wp_error( $comment_id ) ) {
		wp_send_json_error( $comment_id->get_error_message() );
	}

	/* hold：wp_allow_comment() 可能把已登录且有批准历史的用户直接判为通过，
	   这里强制回待审核，确保「待审核」动作在文章评论链路同样生效。 */
	if ( $bang_mod && $bang_mod['hold'] ) {
		wp_set_comment_status( $comment_id, 'hold' );
	}

	$item     = get_comment( $comment_id );
	$approved = (int) $item->comment_approved;
	$count    = (int) get_comments_number( $post_id );

	if ( 1 === $approved ) {
		/* 回复可能要插进某一层的 children 里，直接回整棵树，前端一次替换即可 */
		wp_send_json_success(
			array(
				'status'  => 'approved',
				'html'    => bang_comments_list_html( $post_id ),
				'parent'  => $parent_id,
				'item_id' => (int) $comment_id,
				'count'   => $count,
			)
		);
	}
	wp_send_json_success(
		array(
			'status'  => 'pending',
			'parent'  => $parent_id,
			'count'   => $count,
			'message' => '评论已提交，待审核后显示。',
		)
	);
}
add_action( 'wp_ajax_bang_comment_post', 'bang_ajax_comment_post' );
add_action( 'wp_ajax_nopriv_bang_comment_post', 'bang_ajax_comment_post' );

/**
 * 后台登录页 Logo：用主题自带的 Bang 品牌图替换 WordPress 默认图标。
 */
function bang_login_logo_css() {
	$url = get_template_directory_uri() . '/assets/bang-logo.png';
	echo '<style>'
		. 'body.login div#login h1 a{'
		. 'background-image:url(' . esc_url( $url ) . ');'
		. 'background-size:contain;background-position:center;background-repeat:no-repeat;'
		. 'width:180px;height:180px;border-radius:14px;'
		. '}'
		. '</style>';
}
add_action( 'login_head', 'bang_login_logo_css' );

/** 登录页 Logo 点击回首页 */
function bang_login_logo_url() {
	return home_url( '/' );
}
add_filter( 'login_headerurl', 'bang_login_logo_url' );

/** 登录页 Logo 的 alt / title 用站点名 */
function bang_login_logo_title() {
	return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'bang_login_logo_title' );
