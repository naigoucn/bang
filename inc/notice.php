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
function bang_notice_data() {
	$title = trim( (string) bang_opt( 'notice_title' ) );
	$text  = trim( (string) bang_opt( 'notice_text' ) );
	$later = trim( (string) bang_opt( 'notice_later' ) );
	$ok    = trim( (string) bang_opt( 'notice_ok' ) );
	$mode  = trim( (string) bang_opt( 'notice_mode' ) );

	return array(
		'title' => '' !== $title ? $title : '网站公告',
		'text'  => $text,
		'later' => '' !== $later ? $later : '我记住啦~',
		'ok'    => '' !== $ok ? $ok : '确认',
		/* 只认 always；其它值（含留空 / 旧数据）一律回退为 ack=确认后不再显示 */
		'mode'  => 'always' === $mode ? 'always' : 'ack',
		'hash'  => substr( md5( $title . '|' . $text ), 0, 8 ),
	);
}

/**
 * 输出公告弹窗。未启用或正文为空时返回空串。
 */
function bang_notice_html() {
	if ( ! bang_opt( 'notice_on' ) ) {
		return '';
	}

	$data = bang_notice_data();
	if ( '' === $data['text'] ) {
		return '';
	}

	$html  = '<dialog class="bang-notice" id="bang-notice" aria-labelledby="bang-notice-title" data-hash="' . esc_attr( $data['hash'] ) . '" data-mode="' . esc_attr( $data['mode'] ) . '">';
	$html .= '<div class="bang-notice-head">';
	$html .= '<h2 class="bang-notice-title" id="bang-notice-title">'
		. bang_icon( 'bell', 18 ) . '<span>' . esc_html( $data['title'] ) . '</span></h2>';
	$html .= '<button class="bang-notice-close" type="button" data-bang-notice-close aria-label="关闭公告">'
		. bang_icon( 'close', 18 ) . '</button>';
	$html .= '</div>';
	/* 纯文本入 options，先转义再 wpautop 分段，站主不需要懂 HTML 也不会破版 */
	$html .= '<div class="bang-notice-body">' . wpautop( esc_html( $data['text'] ) ) . '</div>';
	$html .= '<div class="bang-notice-foot">';
	$html .= '<button class="bang-notice-later" type="button" data-bang-notice-later>' . esc_html( $data['later'] ) . '</button>';
	$html .= '<button class="bang-notice-ok" type="button" data-bang-notice-ok>' . esc_html( $data['ok'] ) . '</button>';
	$html .= '</div>';
	$html .= '</dialog>';

	return apply_filters( 'bang_notice_html', $html );
}
