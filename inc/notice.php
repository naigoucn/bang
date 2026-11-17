<?php
/**
 * 全站公告弹窗
 *
 * 访客首次打开任意页面时居中弹一次：
 * - 「确认」写 localStorage（key 存内容 hash），之后不再打扰；
 * - 「我记住啦」/ 关闭 / ESC / 点遮罩只写 sessionStorage，本次访问内不再弹。
 * 内容 hash 变了（站主改了标题或正文）会重新弹一次，方便推新公告。
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 公告文案与版本指纹。
 *
 * @return array
 */
function pk_notice_data() {
	$title = trim( (string) pk_opt( 'notice_title' ) );
	$text  = trim( (string) pk_opt( 'notice_text' ) );
	$later = trim( (string) pk_opt( 'notice_later' ) );
	$ok    = trim( (string) pk_opt( 'notice_ok' ) );

	return array(
		'title' => '' !== $title ? $title : '网站公告',
		'text'  => $text,
		'later' => '' !== $later ? $later : '我记住啦~',
		'ok'    => '' !== $ok ? $ok : '确认',
		'hash'  => substr( md5( $title . '|' . $text ), 0, 8 ),
	);
}

/**
 * 输出公告弹窗。未启用或正文为空时返回空串。
 */
function pk_notice_html() {
	if ( ! pk_opt( 'notice_on' ) ) {
		return '';
	}

	$data = pk_notice_data();
	if ( '' === $data['text'] ) {
		return '';
	}

	$html  = '<dialog class="pk-notice" id="pk-notice" aria-labelledby="pk-notice-title" data-hash="' . esc_attr( $data['hash'] ) . '">';
	$html .= '<div class="pk-notice-head">';
	$html .= '<h2 class="pk-notice-title" id="pk-notice-title">'
		. pk_icon( 'bell', 18 ) . '<span>' . esc_html( $data['title'] ) . '</span></h2>';
	$html .= '<button class="pk-notice-close" type="button" data-pk-notice-close aria-label="关闭公告">'
		. pk_icon( 'close', 18 ) . '</button>';
	$html .= '</div>';
	/* 纯文本入 options，先转义再 wpautop 分段，站主不需要懂 HTML 也不会破版 */
	$html .= '<div class="pk-notice-body">' . wpautop( esc_html( $data['text'] ) ) . '</div>';
	$html .= '<div class="pk-notice-foot">';
	$html .= '<button class="pk-notice-later" type="button" data-pk-notice-later>' . esc_html( $data['later'] ) . '</button>';
	$html .= '<button class="pk-notice-ok" type="button" data-pk-notice-ok>' . esc_html( $data['ok'] ) . '</button>';
	$html .= '</div>';
	$html .= '</dialog>';

	return apply_filters( 'pk_notice_html', $html );
}
