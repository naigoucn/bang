<?php
/**
 * 首页幻灯片（PC 端）
 *
 * 一屏一卡：左侧标题 / 摘要 / 行动按钮，右侧整幅封面。
 * 数据默认从已发布文章里随机取若干篇；后台「常规」可切到手动指定 ID，
 * 也可以用 bang_slide_ids / bang_slides 两个过滤器整段接管（自定义内容）。
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 幻灯片要展示的文章 ID。
 *
 * @return int[]
 */
function bang_slide_ids() {
	$mode = (string) bang_opt( 'slides_mode' );
	if ( ! in_array( $mode, array( 'random', 'manual', 'off' ), true ) ) {
		$mode = 'random';
	}
	if ( 'off' === $mode ) {
		return array();
	}

	$ids = array();
	if ( 'manual' === $mode ) {
		$raw = (string) bang_opt( 'slides_posts' );
		foreach ( preg_split( '/[\s,，]+/u', $raw ) as $chunk ) {
			$id = absint( $chunk );
			if ( $id && ! in_array( $id, $ids, true ) ) {
				$ids[] = $id;
			}
		}
		/* 手动列表里可能混着草稿 / 已删除的 ID，只留真正发布的文章 */
		$ids = array_values(
			array_filter(
				$ids,
				function ( $id ) {
					$post = get_post( $id );
					return $post && 'publish' === $post->post_status && 'post' === $post->post_type;
				}
			)
		);
	}

	/* 随机模式，或手动列表全无效时兜底：随机取几篇，首页永远有内容可放 */
	if ( ! $ids ) {
		$count = (int) bang_opt( 'slides_count' );
		$count = max( 1, min( 8, $count > 0 ? $count : 5 ) );
		$ids   = get_posts(
			array(
				'numberposts'         => $count,
				'post_status'         => 'publish',
				'post_type'           => 'post',
				'orderby'             => 'rand',
				'fields'              => 'ids',
				'suppress_filters'    => true,
				'ignore_sticky_posts' => true,
			)
		);
	}

	return apply_filters( 'bang_slide_ids', array_map( 'intval', $ids ) );
}

/**
 * 幻灯片摘要：比卡片略长，超长补省略号。
 */
function bang_slide_excerpt( $post_id ) {
	$text = get_post_field( 'post_excerpt', $post_id );
	if ( ! $text ) {
		$text = wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $post_id ) ) );
	}
	$text = trim( preg_replace( '/\s+/u', ' ', $text ) );
	if ( mb_strlen( $text, 'UTF-8' ) > 92 ) {
		$text = mb_substr( $text, 0, 92, 'UTF-8' ) . '…';
	}
	return $text;
}

/**
 * 幻灯片封面：真实图片优先，没有图片就用主题内联插画。
 */
function bang_slide_media( $post_id, $eager = false ) {
	$ids = bang_post_images( $post_id );
	if ( $ids ) {
		return bang_image_html( $ids[0], 'large', $post_id, $eager );
	}
	return bang_art_for( $post_id );
}

/**
 * 幻灯片数据。过滤器 bang_slides 可直接传入自定义条目数组。
 *
 * @return array[]
 */
function bang_slides() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	$items = array();
	$ids   = bang_slide_ids();
	foreach ( $ids as $index => $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status || 'post' !== $post->post_type ) {
			continue;
		}

		$title = get_the_title( $post_id );
		$tags  = get_the_tags( $post_id );
		$topic = bang_topic( $post_id );
		$tag   = ( $tags && ! is_wp_error( $tags ) ) ? $tags[0]->name : '';
		/* 分类与标签同名时只留一个，避免出现「日常 · 日常」 */
		if ( $tag && mb_strtolower( $tag, 'UTF-8' ) === mb_strtolower( $topic, 'UTF-8' ) ) {
			$tag = '';
		}

		$items[] = array(
			'id'      => (int) $post_id,
			'url'     => get_permalink( $post_id ),
			'title'   => $title ? $title : '无标题',
			'excerpt' => bang_slide_excerpt( $post_id ),
			'topic'   => $topic,
			'tag'     => $tag,
			'media'   => bang_slide_media( $post_id, 0 === $index ),
		);
	}

	$cache = apply_filters( 'bang_slides', $items );
	return $cache;
}

/**
 * 输出轮播 HTML。没有条目时返回空串，首页直接不留空档。
 */
function bang_slides_html() {
	$items = bang_slides();
	if ( empty( $items ) ) {
		return '';
	}

	$total = count( $items );
	$html  = '<section class="bang-slides" id="bang-slides" data-interval="6000" aria-roledescription="carousel" aria-label="精选文章">';
	$html .= '<div class="bang-slides-track">';

	foreach ( $items as $i => $item ) {
		$active = ( 0 === $i );
		$label  = ( $i + 1 ) . ' / ' . $total;

		$html .= '<article class="bang-slide' . ( $active ? ' is-active' : '' ) . '" data-id="' . esc_attr( $item['id'] ) . '"'
			. ' role="group" aria-roledescription="slide" aria-label="' . esc_attr( $label ) . '"'
			. ( $active ? '' : ' aria-hidden="true" inert' ) . '>';

		/* 左：分类 / 标题 / 摘要 / 按钮 + 箭头（作者头像与昵称已去掉） */
		$html .= '<div class="bang-slide-body">';

		$html .= '<p class="bang-slide-kicker"><span>' . esc_html( $item['topic'] ) . '</span>';
		if ( $item['tag'] ) {
			$html .= '<span class="dot"></span><span>' . esc_html( $item['tag'] ) . '</span>';
		}
		$html .= '</p>';

		$html .= '<h2 class="bang-slide-title"><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['title'] ) . '</a></h2>';
		if ( $item['excerpt'] ) {
			$html .= '<p class="bang-slide-desc">' . esc_html( $item['excerpt'] ) . '</p>';
		}

		$html .= '<div class="bang-slide-bottom">';
		$html .= '<a class="bang-slide-more" href="' . esc_url( $item['url'] ) . '"><span>阅读更多</span>'
			. bang_icon( 'arrow-right', 16 ) . '</a>';
		$html .= '<div class="bang-slide-ctrl">';
		$html .= '<button class="bang-slide-arrow" type="button" data-slide="prev" aria-label="上一张">'
			. bang_icon( 'prev', 18 ) . '</button>';
		$html .= '<button class="bang-slide-arrow" type="button" data-slide="next" aria-label="下一张">'
			. bang_icon( 'next', 18 ) . '</button>';
		$html .= '</div></div></div>';

		/* 右：整幅封面 */
		$html .= '<a class="bang-slide-media" href="' . esc_url( $item['url'] ) . '" tabindex="-1" aria-hidden="true">';
		$html .= $item['media']; // phpcs:ignore WordPress.Security.EscapeOutput -- 已由 bang_image_html / bang_art_for 输出安全标记
		$html .= '</a>';

		$html .= '</article>';
	}

	$html .= '</div>';

	/* 站点 logo：补舞台左上角留白，固定不随切张移动；手机端幻灯片是图上浮层，CSS 隐藏 */
	$bang_slide_logo = bang_logo_html( 'bang-slide-logo', 44, get_bloginfo( 'name' ) );
	if ( $bang_slide_logo ) {
		$html .= '<a class="bang-slide-brand" href="' . esc_url( home_url( '/' ) ) . '" aria-label="' . esc_attr__( '返回首页', 'bang' ) . '">' . $bang_slide_logo . '</a>';
	}

	/* 屏读播报：切张时更新，视觉上不显示 */
	$html .= '<p class="bang-slides-live" aria-live="polite" data-slide-live></p>';
	$html .= '</section>';

	return $html;
}
