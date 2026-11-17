<?php
/**
 * 首页幻灯片（PC 端）
 *
 * 一屏一卡：左侧标题 / 摘要 / 行动按钮，右侧整幅封面。
 * 数据默认从已发布文章里随机取若干篇；后台「常规」可切到手动指定 ID，
 * 也可以用 pk_slide_ids / pk_slides 两个过滤器整段接管（自定义内容）。
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
function pk_slide_ids() {
	$mode = (string) pk_opt( 'slides_mode' );
	if ( ! in_array( $mode, array( 'random', 'manual', 'off' ), true ) ) {
		$mode = 'random';
	}
	if ( 'off' === $mode ) {
		return array();
	}

	$ids = array();
	if ( 'manual' === $mode ) {
		$raw = (string) pk_opt( 'slides_posts' );
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
		$count = (int) pk_opt( 'slides_count' );
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

	return apply_filters( 'pk_slide_ids', array_map( 'intval', $ids ) );
}

/**
 * 幻灯片摘要：比卡片略长，超长补省略号。
 */
function pk_slide_excerpt( $post_id ) {
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
function pk_slide_media( $post_id, $eager = false ) {
	$ids = pk_post_images( $post_id );
	if ( $ids ) {
		return pk_image_html( $ids[0], 'large', $post_id, $eager );
	}
	return pk_art_for( $post_id );
}

/**
 * 幻灯片数据。过滤器 pk_slides 可直接传入自定义条目数组。
 *
 * @return array[]
 */
function pk_slides() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	$items = array();
	$ids   = pk_slide_ids();
	foreach ( $ids as $index => $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status || 'post' !== $post->post_type ) {
			continue;
		}

		$title = get_the_title( $post_id );
		$tags  = get_the_tags( $post_id );
		$topic = pk_topic( $post_id );
		$tag   = ( $tags && ! is_wp_error( $tags ) ) ? $tags[0]->name : '';
		/* 分类与标签同名时只留一个，避免出现「日常 · 日常」 */
		if ( $tag && mb_strtolower( $tag, 'UTF-8' ) === mb_strtolower( $topic, 'UTF-8' ) ) {
			$tag = '';
		}

		$items[] = array(
			'id'      => (int) $post_id,
			'url'     => get_permalink( $post_id ),
			'title'   => $title ? $title : '无标题',
			'excerpt' => pk_slide_excerpt( $post_id ),
			'topic'   => $topic,
			'tag'     => $tag,
			'media'   => pk_slide_media( $post_id, 0 === $index ),
		);
	}

	$cache = apply_filters( 'pk_slides', $items );
	return $cache;
}

/**
 * 输出轮播 HTML。没有条目时返回空串，首页直接不留空档。
 */
function pk_slides_html() {
	$items = pk_slides();
	if ( empty( $items ) ) {
		return '';
	}

	$total = count( $items );
	$html  = '<section class="pk-slides" id="pk-slides" data-interval="6000" aria-roledescription="carousel" aria-label="精选文章">';
	$html .= '<div class="pk-slides-track">';

	foreach ( $items as $i => $item ) {
		$active = ( 0 === $i );
		$label  = ( $i + 1 ) . ' / ' . $total;

		$html .= '<article class="pk-slide' . ( $active ? ' is-active' : '' ) . '" data-id="' . esc_attr( $item['id'] ) . '"'
			. ' role="group" aria-roledescription="slide" aria-label="' . esc_attr( $label ) . '"'
			. ( $active ? '' : ' aria-hidden="true" inert' ) . '>';

		/* 左：分类 / 标题 / 摘要 / 按钮 + 箭头（作者头像与昵称已去掉） */
		$html .= '<div class="pk-slide-body">';

		$html .= '<p class="pk-slide-kicker"><span>' . esc_html( $item['topic'] ) . '</span>';
		if ( $item['tag'] ) {
			$html .= '<span class="dot"></span><span>' . esc_html( $item['tag'] ) . '</span>';
		}
		$html .= '</p>';

		$html .= '<h2 class="pk-slide-title"><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['title'] ) . '</a></h2>';
		if ( $item['excerpt'] ) {
			$html .= '<p class="pk-slide-desc">' . esc_html( $item['excerpt'] ) . '</p>';
		}

		$html .= '<div class="pk-slide-bottom">';
		$html .= '<a class="pk-slide-more" href="' . esc_url( $item['url'] ) . '"><span>阅读更多</span>'
			. pk_icon( 'arrow-right', 16 ) . '</a>';
		$html .= '<div class="pk-slide-ctrl">';
		$html .= '<button class="pk-slide-arrow" type="button" data-slide="prev" aria-label="上一张">'
			. pk_icon( 'prev', 18 ) . '</button>';
		$html .= '<button class="pk-slide-arrow" type="button" data-slide="next" aria-label="下一张">'
			. pk_icon( 'next', 18 ) . '</button>';
		$html .= '</div></div></div>';

		/* 右：整幅封面 */
		$html .= '<a class="pk-slide-media" href="' . esc_url( $item['url'] ) . '" tabindex="-1" aria-hidden="true">';
		$html .= $item['media']; // phpcs:ignore WordPress.Security.EscapeOutput -- 已由 pk_image_html / pk_art_for 输出安全标记
		$html .= '</a>';

		$html .= '</article>';
	}

	$html .= '</div>';
	/* 屏读播报：切张时更新，视觉上不显示 */
	$html .= '<p class="pk-slides-live" aria-live="polite" data-slide-live></p>';
	$html .= '</section>';

	return $html;
}
