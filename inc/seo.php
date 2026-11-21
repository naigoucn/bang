<?php
/**
 * Bang · 前台 SEO 输出
 *
 * 接管 title / description / canonical / robots / Open Graph / Twitter Card /
 * JSON-LD 与站点验证；具体值来自「Bang 设置 → SEO」。
 * WordPress 自带的 rel_canonical 只对文章生效，这里统一接管后移除，避免重复。
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

remove_action( 'wp_head', 'rel_canonical' );

function bang_seo_is_home() {
	return is_front_page() || is_home();
}

/**
 * 当前页面的 SEO 标题（用于 OG / Twitter，title 标签仍交给 WP 的 title-tag）。
 */
function bang_seo_title() {
	if ( bang_seo_is_home() ) {
		$custom = trim( (string) bang_opt( 'seo_home_title' ) );
		return $custom ? $custom : get_bloginfo( 'name' );
	}
	if ( is_singular() ) {
		return get_the_title( get_queried_object_id() );
	}
	if ( is_category() || is_tag() || is_tax() ) {
		return single_term_title( '', false );
	}
	if ( is_search() ) {
		return sprintf( '搜索：%s', get_search_query() );
	}
	if ( is_404() ) {
		return '页面未找到';
	}
	return get_bloginfo( 'name' );
}

/**
 * 当前页面的描述。文章取纯文本摘要，分类取分类描述。
 */
function bang_seo_description() {
	if ( bang_seo_is_home() ) {
		return trim( (string) bang_opt( 'seo_home_desc' ) );
	}
	if ( is_singular() ) {
		/* 0.13.20：全站锁定时 description 不能取正文，否则 meta / og / Twitter /
		   JSON-LD 会把正文开头一段原样带出去，等于从 <head> 泄露内容。 */
		if ( function_exists( 'bang_unlock_post_locked' ) && bang_unlock_post_locked( get_queried_object_id() ) ) {
			$group = bang_opt( 'unlock_group_name' ) ? bang_opt( 'unlock_group_name' ) : '交流群';
			return '本文需加入' . $group . '并完成机器人绑定后阅读。';
		}
		$excerpt = function_exists( 'bang_ai_excerpt' ) ? bang_ai_excerpt( get_queried_object_id() ) : bang_plain_excerpt( get_queried_object_id() );
		return $excerpt ? wp_trim_words( $excerpt, 40, '' ) : '';
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$desc = term_description();
		if ( $desc ) {
			return wp_trim_words( wp_strip_all_tags( $desc ), 40, '' );
		}
		$name = single_term_title( '', false );
		return $name ? sprintf( '%s 分类下的文章', $name ) : '';
	}
	return '';
}

function bang_seo_canonical_url() {
	if ( is_singular() ) {
		return get_permalink( get_queried_object_id() );
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$link = get_term_link( get_queried_object() );
		return is_wp_error( $link ) ? '' : $link;
	}
	if ( bang_seo_is_home() ) {
		return home_url( '/' );
	}
	return '';
}

function bang_seo_robots() {
	$noindex = ( is_search() && bang_opt( 'noindex_search' ) )
		|| ( is_404() && bang_opt( 'noindex_404' ) )
		|| ( is_archive() && ! is_category() && bang_opt( 'noindex_archive' ) );
	return $noindex ? 'noindex, follow' : 'index, follow';
}

function bang_seo_image() {
	if ( is_singular() && has_post_thumbnail( get_queried_object_id() ) ) {
		$image = get_the_post_thumbnail_url( get_queried_object_id(), 'large' );
		if ( $image ) {
			return $image;
		}
	}
	return esc_url_raw( (string) bang_opt( 'og_default_image' ) );
}

/**
 * 首页自定义标题 + 彻底移除副标题（与 functions.php 的兜底 filter 并存）。
 */
function bang_seo_title_parts( $parts ) {
	unset( $parts['tagline'] );
	if ( bang_seo_is_home() ) {
		$custom = trim( (string) bang_opt( 'seo_home_title' ) );
		if ( $custom ) {
			$parts['title'] = $custom;
		}
	}
	return $parts;
}
add_filter( 'document_title_parts', 'bang_seo_title_parts', 12 );

function bang_seo_title_separator( $separator ) {
	$custom = trim( (string) bang_opt( 'title_sep' ) );
	return '' !== $custom ? $custom : $separator;
}
add_filter( 'document_title_separator', 'bang_seo_title_separator' );

/**
 * JSON-LD：WebSite +（文章时）BlogPosting 与 BreadcrumbList。
 */
function bang_seo_jsonld( $image, $description ) {
	$lang  = get_bloginfo( 'language' );
	$name  = get_bloginfo( 'name' );
	$graph = array(
		array(
			'@type'       => 'WebSite',
			'@id'         => home_url( '/#website' ),
			'url'         => home_url( '/' ),
			'name'        => $name,
			'description' => $description ? $description : $name,
			'inLanguage'  => $lang,
		),
	);

	if ( is_singular() ) {
		$id        = get_queried_object_id();
		$permalink = get_permalink( $id );
		$post      = array(
			'@type'            => 'BlogPosting',
			'@id'              => $permalink . '#article',
			'headline'         => get_the_title( $id ),
			'url'              => $permalink,
			'datePublished'    => get_the_date( 'c', $id ),
			'dateModified'     => get_the_modified_date( 'c', $id ),
			'author'           => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', get_post_field( 'post_author', $id ) ),
			),
			'publisher'        => array( '@type' => 'Organization', 'name' => $name ),
			'mainEntityOfPage' => array( '@type' => 'WebPage', '@id' => $permalink ),
			'isPartOf'         => array( '@id' => home_url( '/#website' ) ),
			'inLanguage'       => $lang,
		);
		if ( $description ) {
			$post['description'] = $description;
		}
		if ( $image ) {
			$post['image'] = $image;
		}
		$section = bang_topic( $id );
		if ( $section ) {
			$post['articleSection'] = $section;
		}
		$graph[] = $post;

		$crumb = array(
			array( '@type' => 'ListItem', 'position' => 1, 'name' => $name, 'item' => home_url( '/' ) ),
		);
		$cats = get_the_category( $id );
		if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
			$crumb[] = array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => $cats[0]->name,
				'item'     => get_category_link( $cats[0]->term_id ),
			);
		}
		$crumb[] = array(
			'@type'    => 'ListItem',
			'position' => count( $crumb ) + 1,
			'name'     => get_the_title( $id ),
			'item'     => $permalink,
		);
		$graph[] = array( '@type' => 'BreadcrumbList', 'itemListElement' => $crumb );
	}

	$data  = array( '@context' => 'https://schema.org', '@graph' => $graph );
	$flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
	echo '<script type="application/ld+json">' . wp_json_encode( $data, $flags ) . "</script>\n";
}

/**
 * 主输出：挂在 wp_head 早期（title 之后、其它脚本之前）。
 */
function bang_seo_head() {
	$options     = bang_options();
	$title       = bang_seo_title();
	$description = bang_seo_description();
	$image       = bang_seo_image();
	$canonical   = bang_opt( 'seo_canonical' ) ? bang_seo_canonical_url() : '';
	$page_url    = $canonical ? $canonical : home_url( '/' );

	echo "\n<!-- Bang SEO -->\n";
	/* /login/ 的 robots 交给 wp_robots 输出（noindex, nofollow），这里不再重复一条 */
	if ( ! is_page( 'login' ) ) {
		echo '<meta name="robots" content="' . esc_attr( bang_seo_robots() ) . "\">\n";
	}
	if ( $description ) {
		echo '<meta name="description" content="' . esc_attr( $description ) . "\">\n";
	}
	$keywords = trim( (string) $options['seo_keywords'] );
	if ( $keywords ) {
		echo '<meta name="keywords" content="' . esc_attr( $keywords ) . "\">\n";
	}
	if ( $canonical ) {
		echo '<link rel="canonical" href="' . esc_url( $canonical ) . "\">\n";
	}

	$verify = array(
		'google-site-verification' => 'google_verify',
		'msvalidate.01'            => 'bing_verify',
		'baidu-site-verification'  => 'baidu_verify',
	);
	foreach ( $verify as $meta_name => $option_key ) {
		$code = trim( (string) $options[ $option_key ] );
		if ( $code ) {
			echo '<meta name="' . esc_attr( $meta_name ) . '" content="' . esc_attr( $code ) . "\">\n";
		}
	}

	if ( $options['seo_og'] ) {
		$og = array(
			'og:site_name' => get_bloginfo( 'name' ),
			'og:locale'    => str_replace( '-', '_', get_bloginfo( 'language' ) ),
			'og:type'      => is_singular() ? 'article' : 'website',
			'og:title'     => $title,
			'og:url'       => $page_url,
		);
		if ( $description ) {
			$og['og:description'] = $description;
		}
		if ( $image ) {
			$og['og:image'] = $image;
		}
		foreach ( $og as $property => $value ) {
			echo '<meta property="' . esc_attr( $property ) . '" content="' . esc_attr( $value ) . "\">\n";
		}
		if ( is_singular() ) {
			$id = get_queried_object_id();
			echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c', $id ) ) . "\">\n";
			echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c', $id ) ) . "\">\n";
			$section = bang_topic( $id );
			if ( $section ) {
				echo '<meta property="article:section" content="' . esc_attr( $section ) . "\">\n";
			}
		}
	}

	if ( $options['seo_twitter'] ) {
		$twitter = array(
			'twitter:card'  => $image ? 'summary_large_image' : 'summary',
			'twitter:title' => $title,
		);
		if ( $description ) {
			$twitter['twitter:description'] = $description;
		}
		if ( $image ) {
			$twitter['twitter:image'] = $image;
		}
		$site = trim( (string) $options['twitter_site'] );
		if ( $site ) {
			$twitter['twitter:site'] = $site;
		}
		foreach ( $twitter as $meta_name => $value ) {
			echo '<meta name="' . esc_attr( $meta_name ) . '" content="' . esc_attr( $value ) . "\">\n";
		}
	}

	if ( $options['seo_jsonld'] ) {
		bang_seo_jsonld( $image, $description );
	}
	echo "<!-- /Bang SEO -->\n";
}
add_action( 'wp_head', 'bang_seo_head', 2 );

/**
 * 自定义代码：仅管理员可保存原文，前台原样输出。
 */
function bang_seo_head_code() {
	$code = trim( (string) bang_opt( 'head_code' ) );
	if ( $code ) {
		echo "\n" . $code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
add_action( 'wp_head', 'bang_seo_head_code', 999 );

function bang_seo_footer_code() {
	$code = trim( (string) bang_opt( 'footer_code' ) );
	if ( $code ) {
		echo "\n" . $code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
add_action( 'wp_footer', 'bang_seo_footer_code', 999 );
