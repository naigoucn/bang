<?php
/**
 * 首页公告栏（滚动条）
 *
 * 幻灯片下面一条窄带，内容逐条横向无缝滚动，鼠标悬停暂停。
 * 内容**只来自后台「常规 → 公告栏内容」**（一行一条，可带链接与新窗口标记），
 * 不再自动抓取文章标题；留空时用主题内置文案，避免条带忽然消失。
 * 也可以用 bang_ticker_items 过滤器整段接管。
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 内置公告文案。后台留空时的兜底，只讲站点本身，不涉及文章。
 *
 * @return string
 */
function bang_ticker_default_text() {
	return implode(
		"\n",
		array(
			'欢迎来到 Bang，这里收录文章、短动态与图片 | /about/',
			'内容区块展厅已开放：提示、折叠、代码块都能直接用 | /blocks/',
			'遇到问题先看帮助中心，常见问题都在里面 | /help/',
		)
	);
}

/**
 * 公告条目。每项 text / url（url 为空则渲染成纯文本）/ blank（新窗口）。
 *
 * @return array[]
 */
function bang_ticker_items() {
	$items = array();
	$raw   = trim( (string) bang_opt( 'ticker_text' ) );

	/* 后台没填就退到内置文案：绝不回退到文章，条带也不会无故消失 */
	if ( '' === $raw ) {
		$raw = bang_ticker_default_text();
	}

	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', preg_split( '/\s*[|｜]\s*/u', $line ) );
		$text  = isset( $parts[0] ) ? $parts[0] : '';
		$url   = isset( $parts[1] ) ? $parts[1] : '';
		$flag  = isset( $parts[2] ) ? strtolower( $parts[2] ) : '';
		if ( '' === $text ) {
			continue;
		}

		/* 站内路径补成绝对地址；# 锚点、//协议相对、http 开头原样保留 */
		if ( '' !== $url && '#' !== substr( $url, 0, 1 ) && ! preg_match( '#^(https?:)?//#i', $url ) ) {
			if ( '/' !== substr( $url, 0, 1 ) ) {
				$url = '/' . $url;
			}
			$url = home_url( $url );
		}

		$items[] = array(
			'text'  => $text,
			'url'   => $url,
			'blank' => in_array( $flag, array( 'blank', '_blank', 'new', '1' ), true ),
		);
	}

	return apply_filters( 'bang_ticker_items', $items );
}

/**
 * 公告栏 HTML。关闭或无条目时返回空串，不留空档。
 */
function bang_ticker_html() {
	if ( ! bang_opt( 'ticker_on' ) ) {
		return '';
	}
	$items = bang_ticker_items();
	if ( empty( $items ) ) {
		return '';
	}

	$tag = trim( (string) bang_opt( 'ticker_tag' ) );

	$row = '';
	foreach ( $items as $item ) {
		if ( ! empty( $item['url'] ) ) {
			$target = ! empty( $item['blank'] ) ? ' target="_blank" rel="noopener"' : '';
			$row   .= '<a class="bang-ticker-item" href="' . esc_url( $item['url'] ) . '"' . $target . '>' . esc_html( $item['text'] ) . '</a>';
		} else {
			$row .= '<span class="bang-ticker-item">' . esc_html( $item['text'] ) . '</span>';
		}
	}

	$html  = '<div class="bang-ticker' . ( '' === $tag ? ' is-plain' : '' ) . '" id="bang-ticker">';
	if ( '' !== $tag ) {
		$html .= '<span class="bang-ticker-tag">' . esc_html( $tag ) . '</span>';
	}
	$html .= '<div class="bang-ticker-view"><div class="bang-ticker-track" data-ticker-track>';
	$html .= '<div class="bang-ticker-group">' . $row . '</div>';
	/* 复制一份垫在后面：动画走完 -50% 正好接回开头，看不出断点；副本对屏读隐藏 */
	$html .= '<div class="bang-ticker-group" aria-hidden="true">' . $row . '</div>';
	$html .= '</div></div></div>';

	return $html;
}
