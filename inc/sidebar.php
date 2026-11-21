<?php
/**
 * 文章侧栏小工具
 *
 * 侧栏（.read-rail）不再写死「作者卡 + 目录」，改由后台配置的
 * 小工具列表驱动：作者卡 / 文章目录 / 打赏卡片 / 最新发布 / 热门文章 / 自定义 HTML。
 * 后台「内容与互动 → 文章侧栏小工具」可勾选启用并拖拽排序。
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 可用小工具清单：id => 名称。
 * 新增小工具时这里加一项，并在 bang_sidebar_widget_html() 补一个 case。
 *
 * @return array
 */
function bang_sidebar_widget_defs() {
	return array(
		'author' => __( '作者卡', 'bang' ),
		'toc'    => __( '文章目录', 'bang' ),
		'reward' => __( '打赏卡片', 'bang' ),
		'latest' => __( '最新发布', 'bang' ),
		'hot'    => __( '热门文章', 'bang' ),
		'html'   => __( '自定义 HTML', 'bang' ),
	);
}

/**
 * 当前启用的小工具 id（按后台配置的顺序）。
 *
 * @return array
 */
function bang_sidebar_widget_ids() {
	$defs = bang_sidebar_widget_defs();
	$raw  = (string) bang_opt( 'sidebar_widgets' );
	$out  = array();
	foreach ( explode( ',', $raw ) as $id ) {
		$id = trim( $id );
		if ( isset( $defs[ $id ] ) && ! in_array( $id, $out, true ) ) {
			$out[] = $id;
		}
	}
	return $out;
}

/**
 * 打赏卡片：从作者卡拆出来的独立小工具。
 * 有二维码就展示可扫码大图，有链接就给外链按钮；都留空时按钮指向打赏页。
 *
 * @return string
 */
function bang_sidebar_reward_card_html() {
	if ( ! bang_opt( 'reward_on' ) ) {
		return '';
	}
	$title = trim( (string) bang_opt( 'reward_title' ) );
	if ( '' === $title ) {
		$title = __( '打赏', 'bang' );
	}
	$desc = trim( (string) bang_opt( 'reward_desc' ) );
	$qr   = bang_safe_comment_site( trim( (string) bang_opt( 'reward_qr' ) ) );
	$link = bang_safe_comment_site( trim( (string) bang_opt( 'reward_link' ) ) );

	$html  = '<div class="bang-side-reward">';
	$html .= '<h2 class="bang-side-reward-title">' . bang_icon( 'sparkles', 17 ) . '<span>' . esc_html( $title ) . '</span></h2>';
	if ( '' !== $desc ) {
		$html .= '<p class="bang-side-reward-desc">' . esc_html( $desc ) . '</p>';
	}
	if ( '' !== $qr ) {
		$html .= '<img class="bang-side-reward-qr" src="' . esc_url( $qr ) . '" alt="' . esc_attr__( '打赏二维码', 'bang' ) . '" loading="lazy" decoding="async">';
	}
	if ( '' !== $link ) {
		$btn = trim( (string) bang_opt( 'reward_btn_text' ) );
		if ( '' === $btn ) {
			$btn = __( '前往打赏', 'bang' );
		}
		$html .= '<a class="bang-side-reward-btn" href="' . esc_url( $link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $btn ) . '</a>';
	} else {
		$html .= '<a class="bang-side-reward-btn" href="' . esc_url( bang_site_page_url( 'reward' ) ) . '">' . esc_html__( '打赏', 'bang' ) . '</a>';
	}
	$html .= '</div>';
	return $html;
}

/**
 * 最新发布：最近 5 篇已发布文章。
 *
 * @return string
 */
function bang_sidebar_latest_html() {
	$posts = get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'posts_per_page'   => 5,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => true,
			'no_found_rows'    => true,
		)
	);
	if ( empty( $posts ) ) {
		return '';
	}
	$html  = '<div class="bang-side-list">';
	$html .= '<h2 class="bang-side-list-title">' . esc_html__( '最新发布', 'bang' ) . '</h2>';
	$html .= '<ol class="bang-side-posts">';
	foreach ( $posts as $p ) {
		$html .= '<li class="bang-side-post">'
			. '<a class="bang-side-post-link" href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a>'
			. '<time class="bang-side-post-date">' . esc_html( get_the_date( 'm.d', $p ) ) . '</time>'
			. '</li>';
	}
	$html .= '</ol></div>';
	return $html;
}

/**
 * 热门文章：复用排行榜查询，取综合热度前 5 篇。
 *
 * @return string
 */
function bang_sidebar_hot_html() {
	if ( ! bang_opt( 'ranking_on' ) || ! function_exists( 'bang_rank_query' ) ) {
		return '';
	}
	$rows = bang_rank_query( 5, 'hot', 0 );
	if ( empty( $rows ) ) {
		return '';
	}
	$html  = '<div class="bang-side-list">';
	$html .= '<h2 class="bang-side-list-title">' . esc_html__( '热门文章', 'bang' ) . '</h2>';
	$html .= '<ol class="bang-side-posts bang-side-posts--hot">';
	foreach ( $rows as $i => $row ) {
		$id = (int) $row['id'];
		if ( ! $id ) {
			continue;
		}
		$html .= '<li class="bang-side-post">'
			. '<span class="bang-side-post-no" aria-hidden="true">' . esc_html( (string) ( $i + 1 ) ) . '</span>'
			. '<a class="bang-side-post-link" href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a>'
			. '<span class="bang-side-post-hits">' . esc_html( (string) (int) $row['views'] ) . '</span>'
			. '</li>';
	}
	$html .= '</ol></div>';
	return $html;
}

/**
 * 单个小工具渲染。
 *
 * @param string $id      小工具 id。
 * @param int    $post_id 当前文章 ID（作者卡 / 目录需要）。
 * @return string
 */
function bang_sidebar_widget_html( $id, $post_id = 0 ) {
	switch ( $id ) {
		case 'author':
			return function_exists( 'bang_author_card_html' ) ? bang_author_card_html( $post_id ) : '';
		case 'toc':
			return function_exists( 'bang_toc_html' ) ? bang_toc_html( $post_id ) : '';
		case 'reward':
			return bang_sidebar_reward_card_html();
		case 'latest':
			return bang_sidebar_latest_html();
		case 'hot':
			return bang_sidebar_hot_html();
		case 'html':
			/* 只有具备 unfiltered_html 的管理员能编辑此字段，保存时已按权限清洗 */
			return (string) bang_opt( 'sidebar_widget_html' );
	}
	return '';
}

/**
 * 按后台配置顺序渲染整条侧栏。
 *
 * @param int $post_id 当前文章 ID。
 * @return string
 */
function bang_sidebar_widgets_html( $post_id = 0 ) {
	$ids = bang_sidebar_widget_ids();
	if ( empty( $ids ) ) {
		return '';
	}
	$out = '';
	foreach ( $ids as $id ) {
		$body = bang_sidebar_widget_html( $id, $post_id );
		if ( '' === trim( (string) $body ) ) {
			continue;
		}
		$out .= '<section class="bang-side-widget bang-side-widget--' . esc_attr( $id ) . '">' . $body . '</section>';
	}
	return $out;
}
