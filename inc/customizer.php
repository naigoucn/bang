<?php
/**
 * WordPress 外观 → 自定义（Customizer）里的文章侧栏小工具面板。
 *
 * 与后台「Bang 设置 → 内容与互动 → 文章侧栏小工具」共用同一份数据：
 * 直接读写 bang_options 数组里的 sidebar_widgets / sidebar_widget_html 两个 key。
 * 用 WP 原生「多维 option setting」（bang_options[xxx]）：核心负责只改对应 key、
 * 不覆盖其它设置，且预览时自动把改动注入 get_option，前台可实时预览。
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 清洗：小工具 id 串（与 bang_sanitize_options 的 widgets 分支一致）。
 *
 * @param mixed $value 原始值。
 * @return string
 */
function bang_cust_sanitize_widgets( $value ) {
	$defs = function_exists( 'bang_sidebar_widget_defs' ) ? array_keys( bang_sidebar_widget_defs() ) : array();
	$raw  = is_array( $value ) ? implode( ',', $value ) : (string) $value;
	$ids  = array_filter( array_map( 'sanitize_key', explode( ',', $raw ) ) );
	$keep = array();
	foreach ( $ids as $one ) {
		if ( in_array( $one, $defs, true ) && ! in_array( $one, $keep, true ) ) {
			$keep[] = $one;
		}
	}
	return implode( ',', $keep );
}

/**
 * 清洗：自定义 HTML（仅 unfiltered_html 用户可保留原样，其余走 wp_kses_post）。
 *
 * @param mixed $value 原始值。
 * @return string
 */
function bang_cust_sanitize_html( $value ) {
	return current_user_can( 'unfiltered_html' ) ? (string) $value : wp_kses_post( (string) $value );
}

/**
 * 小工具拖拽控件。
 */
if ( class_exists( 'WP_Customize_Control' ) ) {
	class Bang_Customize_Widgets_Control extends WP_Customize_Control {
		public $type = 'bang_widgets';

		public function render_content() {
			$wdefs = function_exists( 'bang_sidebar_widget_defs' ) ? bang_sidebar_widget_defs() : array();
			$value = (string) $this->value();
			$won   = array_filter( array_map( 'trim', explode( ',', $value ) ) );
			$order = array();
			foreach ( $won as $wid ) {
				if ( isset( $wdefs[ $wid ] ) ) {
					$order[ $wid ] = 1;
				}
			}
			foreach ( $wdefs as $wid => $wlabel ) {
				if ( ! isset( $order[ $wid ] ) ) {
					$order[ $wid ] = 0;
				}
			}
			if ( ! empty( $this->label ) ) {
				echo '<span class="customize-control-title">' . esc_html( $this->label ) . '</span>';
			}
			if ( ! empty( $this->description ) ) {
				echo '<span class="description customize-control-description">' . esc_html( $this->description ) . '</span>';
			}
			$grip = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M9 5h.01M9 12h.01M9 19h.01M15 5h.01M15 12h.01M15 19h.01"/></svg>';
			echo '<div class="bang-cust-widgets" data-bang-cust-widgets data-bang-setting="' . esc_attr( $this->id ) . '">';
			echo '<div class="bang-cust-widget-list" data-bang-widgets-list>';
			foreach ( $order as $wid => $enabled ) {
				echo '<div class="bang-cust-widget" data-bang-widget="' . esc_attr( $wid ) . '">'
					. '<span class="bang-cust-widget-grip" draggable="true" aria-label="' . esc_attr__( '拖动排序', 'bang' ) . '">' . $grip . '</span>'
					. '<span class="bang-cust-widget-name">' . esc_html( $wdefs[ $wid ] ) . '</span>'
					. '<label class="bang-cust-widget-on"><input type="checkbox" data-bang-widget-on' . ( $enabled ? ' checked' : '' ) . '><span>' . esc_html__( '启用', 'bang' ) . '</span></label>'
					. '</div>';
			}
			echo '</div>';
			echo '</div>';
		}
	}
}

/**
 * 注册面板 / 设置 / 控件。
 *
 * @param WP_Customize_Manager $wp_customize Customizer 管理器。
 */
function bang_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'bang_sidebar',
		array(
			'title'       => __( '文章侧栏小工具', 'bang' ),
			'description' => __( '与「Bang 设置 → 内容与互动」里的侧栏小工具共用同一份配置；勾选启用、拖动排序，改动后预览自动刷新。', 'bang' ),
			'priority'    => 160,
		)
	);

	/* 多维 option setting：id 里的 key 即 bang_options 数组键，核心自动合并、不覆盖其它设置 */
	$wp_customize->add_setting(
		'bang_options[sidebar_widgets]',
		array(
			'default'           => 'author,toc,reward,latest,hot',
			'type'              => 'option',
			'transport'         => 'refresh',
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'bang_cust_sanitize_widgets',
		)
	);
	if ( class_exists( 'Bang_Customize_Widgets_Control' ) ) {
		$wp_customize->add_control(
			new Bang_Customize_Widgets_Control(
				$wp_customize,
				'bang_sidebar_widgets',
				array(
					'section'     => 'bang_sidebar',
					'settings'    => 'bang_options[sidebar_widgets]',
					'label'       => __( '启用与排序', 'bang' ),
					'description' => __( '勾选要显示的小工具，按住左侧手柄拖动调整顺序。', 'bang' ),
				)
			)
		);
	}

	$wp_customize->add_setting(
		'bang_options[sidebar_widget_html]',
		array(
			'default'           => '',
			'type'              => 'option',
			'transport'         => 'refresh',
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'bang_cust_sanitize_html',
		)
	);
	$wp_customize->add_control(
		'bang_sidebar_html',
		array(
			'type'        => 'textarea',
			'section'     => 'bang_sidebar',
			'settings'    => 'bang_options[sidebar_widget_html]',
			'label'       => __( '自定义 HTML 内容', 'bang' ),
			'description' => __( '仅「自定义 HTML」小工具启用时显示；按权限过滤脚本内容。', 'bang' ),
		)
	);
}
add_action( 'customize_register', 'bang_customize_register' );

/**
 * 控件样式（Customizer 面板不加载后台 Bang 设置页的样式，单独输出）。
 */
function bang_customize_styles() {
	echo '<style id="bang-customize-css">'
		. '.bang-cust-widgets{margin-top:6px}'
		. '.bang-cust-widget-list{display:flex;flex-direction:column;gap:6px}'
		. '.bang-cust-widget{display:flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid #dcdcde;border-radius:6px;background:#fff}'
		. '.bang-cust-widget.is-dragging{opacity:.5}'
		. '.bang-cust-widget-grip{width:16px;height:16px;flex:none;cursor:grab;color:#a0a5aa}'
		. '.bang-cust-widget-grip svg{display:block;width:100%;height:100%}'
		. '.bang-cust-widget-name{flex:1;font-size:12px;color:#23282d}'
		. '.bang-cust-widget-on{display:flex;align-items:center;gap:4px;font-size:11px;color:#50575e;cursor:pointer}'
		. '.bang-cust-widget-on input{margin:0}'
		. '</style>';
}
add_action( 'customize_controls_print_styles', 'bang_customize_styles' );

/**
 * 控件脚本：勾选 / 拖拽后把「已启用且按顺序」的 id 串写回 setting。
 */
function bang_customize_scripts() {
	$js = '(function(){'
		. 'function init(){'
		. 'var root=document.querySelector("[data-bang-cust-widgets]");'
		. 'if(!root){return;}'
		. 'var list=root.querySelector("[data-bang-widgets-list]");'
		. 'var sid=root.getAttribute("data-bang-setting")||"bang_options[sidebar_widgets]";'
		. 'if(!list){return;}'
		. 'function sync(){'
		. 'var out=[];'
		. 'Array.prototype.forEach.call(list.children,function(row){'
		. 'var cb=row.querySelector("[data-bang-widget-on]");'
		. 'if(cb&&cb.checked){out.push(row.getAttribute("data-bang-widget")||"");}'
		. '});'
		. 'var val=out.filter(Boolean).join(",");'
		. 'var s=window.wp&&wp.customize?wp.customize(sid):null;'
		. 'if(s){s.set(val);}'
		. '}'
		. 'var dragging=null;'
		. 'list.addEventListener("dragstart",function(e){'
		. 'var grip=e.target.closest?e.target.closest("[draggable=\\"true\\"]"):null;'
		. 'if(!grip){return;}'
		. 'dragging=grip.parentNode;'
		. 'dragging.classList.add("is-dragging");'
		. 'if(e.dataTransfer){e.dataTransfer.effectAllowed="move";e.dataTransfer.setData("text/plain",dragging.getAttribute("data-bang-widget")||"");}'
		. '});'
		. 'list.addEventListener("dragover",function(e){'
		. 'if(!dragging){return;}'
		. 'e.preventDefault();'
		. 'var row=e.target.closest?e.target.closest(".bang-cust-widget"):null;'
		. 'if(!row||row===dragging){return;}'
		. 'var box=row.getBoundingClientRect();'
		. 'if((e.clientY-box.top)<box.height/2){list.insertBefore(dragging,row);}'
		. 'else{list.insertBefore(dragging,row.nextSibling);}'
		. '});'
		. 'list.addEventListener("drop",function(e){if(dragging){e.preventDefault();}});'
		. 'list.addEventListener("dragend",function(){if(dragging){dragging.classList.remove("is-dragging");dragging=null;sync();}});'
		. 'list.addEventListener("change",sync);'
		. '}'
		. 'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",init);}'
		. 'else{init();}'
		. '})();';
	wp_add_inline_script( 'customize-controls', $js );
}
add_action( 'customize_controls_enqueue_scripts', 'bang_customize_scripts' );
