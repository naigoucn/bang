<?php
/**
 * Bang · 正文区块套件（0.13.0）
 *
 * 把成熟博客主题常见的「文章内容区块」能力补齐一整套，但全部按 Bang 的
 * 设计语言重做：白底 / 黑操作按钮 / 12px 圆角 / 线性 SVG 图标 / 无阴影无渐变 /
 * 四种状态靠形状与字重区分，不靠彩色。
 *
 * 提供三条写法，输出同一套 HTML：
 *   1) 编辑器区块：Bang 区块组里的「提示框 / 折叠内容 / 代码块 / 图集」；
 *   2) 简码：[tip] [fold] [code] [wall]，方便经典编辑器与直接粘贴；
 *   3) 核心区块增强：core/code 认 lang-xxx 类名并自动识别语言，core/details 套 Bang 外观。
 *
 * 所有 render_callback / 简码都是服务端纯 HTML，首页阅读层把正文内联进
 * #bang-data 时不需要执行脚本，复制按钮这类交互由 app.js 在浏览器端补上。
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ============================================================
   图标：统一 1.8 描边、currentColor，无 emoji
   ============================================================ */
/**
 * 区块套件内联图标。
 *
 * @param string $name 图标名。
 * @param int    $size 尺寸。
 * @return string
 */
function bang_kit_icon( $name, $size = 16 ) {
	$paths = array(
		'info'    => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12" y2="8"/>',
		'warning' => '<path d="M10.3 3.9 2.6 17.2A1.9 1.9 0 0 0 4.3 20h15.4a1.9 1.9 0 0 0 1.7-2.8L13.7 3.9a1.9 1.9 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12" y2="17"/>',
		'success' => '<circle cx="12" cy="12" r="10" stroke-linecap="round" stroke="currentColor"/> <path d="M8.5 12.5L10.5 14.5L15.5 9.5" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>',
		'danger'  => '<circle cx="12" cy="12" r="10" stroke-linecap="round" stroke="currentColor"/> <path d="M14.5 9.50002L12 12M12 12L9.5 14.5M12 12L9.49998 9.5M12 12L14.5 14.5" stroke-linecap="round" stroke="currentColor"/>',
		'chevron' => '<path d="M19 9L12 15L5 9" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>',
		'copy'    => '<path d="M12 22H15C17.8284 22 19.2426 22 20.1213 21.1213C21 20.2426 21 18.8284 21 16V11C21 8.17157 21 6.75736 20.1213 5.87868C19.2426 5 17.8284 5 15 5H12C9.17157 5 7.75736 5 6.87868 5.87868C6 6.75736 6 8.17157 6 11V16C6 18.8284 6 20.2426 6.87868 21.1213C7.75736 22 9.17157 22 12 22Z" stroke-linecap="round" stroke="currentColor"/> <path d="M6 19C4.34315 19 3 17.6569 3 16V10C3 6.22876 3 4.34315 4.17157 3.17157C5.34315 2 7.22876 2 11 2H15C16.6569 2 18 3.34315 18 5" stroke-linecap="round" stroke="currentColor"/>',
		'check'   => '<path d="M5 13L9 17L19 7" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>',
		'bell'    => '<path d="M18.7491 9.70957V9.00496C18.7491 5.13623 15.7274 2 12 2C8.27256 2 5.25087 5.13623 5.25087 9.00496V9.70957C5.25087 10.5552 5.00972 11.3818 4.5578 12.0854L3.45036 13.8095C2.43882 15.3843 3.21105 17.5249 4.97036 18.0229C9.57274 19.3257 14.4273 19.3257 19.0296 18.0229C20.789 17.5249 21.5612 15.3843 20.5496 13.8095L19.4422 12.0854C18.9903 11.3818 18.7491 10.5552 18.7491 9.70957Z" stroke-linecap="round" stroke="currentColor"/> <path d="M7.5 19C8.15503 20.7478 9.92246 22 12 22C14.0775 22 15.845 20.7478 16.5 19" stroke-linecap="round" stroke="currentColor"/>',
		'x'       => '<path d="M19.0068 5L5.00684 19" stroke-linecap="round" stroke="currentColor"/> <path d="M19 19L5 5" stroke-linecap="round" stroke="currentColor"/>',
		'arrow'   => '<path d="M4 12H20M14 18L20 12L14 6" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>',
		'pin'     => '<path d="M13.4629 21.6744C12.5343 22.1085 11.4657 22.1085 10.5371 21.6744C6.55332 19.8124 4 14.6055 4 10.1433C4 5.64588 7.58172 2 12 2C16.4183 2 20 5.64588 20 10.1433C20 14.6055 17.4467 19.8124 13.4629 21.6744Z" stroke-linecap="round" stroke="currentColor"/> <circle cx="12" cy="10" r="3" stroke-linecap="round" stroke="currentColor"/>',
		'image'   => '<path d="M12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22Z" stroke-linecap="round" stroke="currentColor"/> <circle cx="16" cy="8" r="2" stroke-linecap="round" stroke="currentColor"/> <path d="M2 12.5001L3.75159 10.9675C4.66286 10.1702 6.03628 10.2159 6.89249 11.0721L11.1822 15.3618C11.8694 16.0491 12.9512 16.1428 13.7464 15.5839L14.0446 15.3744C15.1888 14.5702 16.7369 14.6634 17.7765 15.599L21 18.5001" stroke-linecap="round" stroke="currentColor"/>',
		'link'    => '<path d="M10.0464 14C8.54044 12.4882 8.67609 9.90087 10.3494 8.22108L15.197 3.35462C16.8703 1.67483 19.4476 1.53865 20.9536 3.05046C22.4596 4.56228 22.3239 7.14956 20.6506 8.82935L18.2268 11.2626" stroke-linecap="round" stroke="currentColor"/> <path d="M13.9536 10C15.4596 11.5118 15.3239 14.0991 13.6506 15.7789L11.2268 18.2121L8.80299 20.6454C7.12969 22.3252 4.55237 22.4613 3.0464 20.9495C1.54043 19.4377 1.67609 16.8504 3.34939 15.1706L5.77323 12.7373" stroke-linecap="round" stroke="currentColor"/>',
		'download' => '<path d="M12 3V16M12 16L17 11M12 16L7 11" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/> <path d="M4 17V19C4 20.6569 5.34315 22 7 22H17C18.6569 22 20 20.6569 20 19V17" stroke-linecap="round" stroke="currentColor"/>',
		'disk'    => '<circle cx="12" cy="12" r="9" stroke-linecap="round" stroke="currentColor"/> <circle cx="12" cy="12" r="3" stroke-linecap="round" stroke="currentColor"/> <path d="M12 3V9M12 15V21" stroke-linecap="round" stroke="currentColor"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="bang-kit-ico" viewBox="0 0 24 24" width="' . (int) $size . '" height="' . (int) $size . '"'
		. ' fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
		. $paths[ $name ] . '</svg>';
}

/* ============================================================
   注册三个新增区块（动态区块，编辑器脚本统一由 build/index.js 提供）
   ============================================================ */
/**
 * 注册 Bang 区块组里的新增成员。
 */
function bang_register_content_blocks() {
	$map = array(
		'tip'      => 'bang_render_tip_block',
		'fold'     => 'bang_render_fold_block',
		'code'     => 'bang_render_code_block',
		'timeline' => 'bang_render_timeline_block',
		'postcard' => 'bang_render_postcard_block',
		'unlock'   => 'bang_render_unlock_block',
		'download' => 'bang_render_download_block',
	);
	foreach ( $map as $dir => $callback ) {
		$path = get_template_directory() . '/blocks/' . $dir;
		if ( file_exists( $path . '/block.json' ) ) {
			register_block_type( $path, array( 'render_callback' => $callback ) );
		}
	}
}
add_action( 'init', 'bang_register_content_blocks', 11 );

/* ============================================================
   提示框
   ============================================================ */
/**
 * 四种状态的语义与图标。四种状态用低饱和主色区分（图标 / 标签 / 边框 / 底色），
 * 卡片形状完全一致，不再靠左边线或图标形状。
 *
 * @return array
 */
function bang_tip_states() {
	return array(
		'info'    => array( 'label' => '提示', 'icon' => 'info' ),
		'warning' => array( 'label' => '注意', 'icon' => 'warning' ),
		'success' => array( 'label' => '搞定', 'icon' => 'success' ),
		'danger'  => array( 'label' => '警告', 'icon' => 'danger' ),
	);
}

/**
 * 提示框 HTML。
 *
 * @param string $type    状态。
 * @param string $title   自定义标题，留空则显示状态名。
 * @param string $content 正文（允许安全 HTML）。
 * @return string
 */
function bang_kit_tip_html( $type, $title, $content ) {
	$states = bang_tip_states();
	$type   = isset( $states[ $type ] ) ? $type : 'info';
	$state  = $states[ $type ];
	$title  = trim( (string) $title );

	$head  = '<div class="bang-tip-head">'
		. '<span class="bang-tip-ico">' . bang_kit_icon( $state['icon'], 16 ) . '</span>'
		. '<span class="bang-tip-label">' . esc_html( $state['label'] ) . '</span>'
		. ( '' !== $title ? '<strong class="bang-tip-title">' . esc_html( $title ) . '</strong>' : '' )
		. '</div>';
	$body = '<div class="bang-tip-body">' . wp_kses_post( $content ) . '</div>';

	return '<div class="bang-tip bang-tip--' . esc_attr( $type ) . '" data-bang-tip="' . esc_attr( $type ) . '">'
		. $head . $body . '</div>';
}

/**
 * 区块回调：提示框。
 *
 * @param array $attributes 区块属性。
 * @return string
 */
function bang_render_tip_block( $attributes ) {
	$type    = isset( $attributes['type'] ) ? (string) $attributes['type'] : 'info';
	$title   = isset( $attributes['title'] ) ? (string) $attributes['title'] : '';
	$content = isset( $attributes['content'] ) ? (string) $attributes['content'] : '';
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'bang-tip-wrap' ) );
	return '<div ' . $wrapper . '>' . bang_kit_tip_html( $type, $title, $content ) . '</div>';
}

/* ============================================================
   代码块：语言识别 + 复制（按钮由 JS 补，避免被 kses 剥掉）
   ============================================================ */
/**
 * 语言代号 → 显示名。
 *
 * @param string $lang 代号。
 * @return string
 */
function bang_code_lang_label( $lang ) {
	$map = array(
		'php'        => 'PHP',
		'js'         => 'JavaScript',
		'javascript' => 'JavaScript',
		'ts'         => 'TypeScript',
		'typescript' => 'TypeScript',
		'css'        => 'CSS',
		'scss'       => 'SCSS',
		'html'       => 'HTML',
		'xml'        => 'XML',
		'python'     => 'Python',
		'py'         => 'Python',
		'bash'       => 'Bash',
		'sh'         => 'Bash',
		'shell'      => 'Bash',
		'json'       => 'JSON',
		'sql'        => 'SQL',
		'go'         => 'Go',
		'rust'       => 'Rust',
		'java'       => 'Java',
		'c'          => 'C',
		'cpp'        => 'C++',
		'less'       => 'Less',
		'markdown'   => 'Markdown',
		'md'         => 'Markdown',
		'text'       => 'Text',
	);
	$lang = strtolower( (string) $lang );
	return isset( $map[ $lang ] ) ? $map[ $lang ] : ( $lang ? strtoupper( $lang ) : 'Text' );
}

/**
 * 极简语言嗅探：只认最明显的开头特征，识别不出就落 Text，不依赖外部库。
 *
 * @param string $code 代码文本。
 * @return string
 */
function bang_code_guess( $code ) {
	$code = ltrim( (string) $code );
	if ( '' === $code ) {
		return 'text';
	}
	if ( preg_match( '/^<\?(php|=)/i', $code ) ) {
		return 'php';
	}
	if ( preg_match( '/^\s*#include\s*[<\"]/m', $code ) ) {
		return 'cpp';
	}
	if ( preg_match( '/^\s*(def |class |from \w+ import |import \w+|print\()/m', $code ) ) {
		return 'python';
	}
	if ( preg_match( '/^\s*(SELECT|INSERT\s+INTO|UPDATE\s+\w+|CREATE\s+TABLE)\s/i', $code ) ) {
		return 'sql';
	}
	if ( preg_match( '/^\s*(#!\/(bin\/)?(ba)?sh|\$\s|npm |yarn |pnpm |git |curl )/m', $code ) ) {
		return 'bash';
	}
	if ( preg_match( '/^\s*[\[{]\s*("[^"]+"\s*:|\s*\})/m', $code ) ) {
		return 'json';
	}
	if ( preg_match( '/^\s*(function\s|const\s|let\s|var\s|export\s|import\s|console\.|=>)/m', $code ) ) {
		return 'js';
	}
	if ( preg_match( '/^\s*<(!DOCTYPE|[a-z][a-z0-9-]*[\s>])/im', $code ) ) {
		return 'html';
	}
	if ( preg_match( '/^\s*[@.#a-z][^{}]*\{\s*[a-z-]+\s*:/im', $code ) ) {
		return 'css';
	}
	return 'text';
}

/**
 * 代码块 HTML。
 *
 * @param string $lang    语言代号，留空自动识别。
 * @param string $code    代码原文。
 * @param string $caption 图注 / 文件名。
 * @return string
 */
function bang_kit_code_html( $lang, $code, $caption = '' ) {
	$lang = preg_replace( '/[^a-z0-9+#_-]/i', '', strtolower( (string) $lang ) );
	$code = html_entity_decode( (string) $code, ENT_QUOTES, 'UTF-8' );
	if ( '' === $lang ) {
		$lang = bang_code_guess( $code );
	}
	$cap = trim( (string) $caption );

	return '<div class="bang-code" data-bang-code data-lang="' . esc_attr( $lang ) . '">'
		. '<div class="bang-code-bar"><span class="bang-code-lang">' . esc_html( bang_code_lang_label( $lang ) ) . '</span>'
		. ( '' !== $cap ? '<span class="bang-code-file">' . esc_html( $cap ) . '</span>' : '' )
		. '</div>'
		. '<pre class="bang-code-pre"><code>' . esc_html( $code ) . '</code></pre>'
		. '</div>';
}

/**
 * 区块回调：代码块。
 *
 * @param array $attributes 区块属性。
 * @return string
 */
function bang_render_code_block( $attributes ) {
	$lang    = isset( $attributes['lang'] ) ? (string) $attributes['lang'] : '';
	$code    = isset( $attributes['content'] ) ? (string) $attributes['content'] : '';
	$caption = isset( $attributes['caption'] ) ? (string) $attributes['caption'] : '';
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'bang-code-wrap' ) );
	return '<div ' . $wrapper . '>' . bang_kit_code_html( $lang, $code, $caption ) . '</div>';
}

/* ============================================================
   折叠内容
   ============================================================ */
/**
 * 折叠 HTML。用原生 details/summary：无 JS 也能开合，键盘与读屏天然可用。
 *
 * @param string $title   标题。
 * @param string $content 内容（允许安全 HTML）。
 * @param bool   $open    默认展开。
 * @return string
 */
function bang_kit_fold_html( $title, $content, $open = false ) {
	$title = trim( (string) $title );
	return '<details class="bang-fold" data-bang-fold' . ( $open ? ' open' : '' ) . '>'
		. '<summary class="bang-fold-sum"><span class="bang-fold-chev">' . bang_kit_icon( 'chevron', 15 ) . '</span>'
		. '<span class="bang-fold-title">' . esc_html( '' !== $title ? $title : '点击展开' ) . '</span>'
		. '<span class="bang-fold-hint">' . esc_html__( '展开', 'bang' ) . '</span></summary>'
		. '<div class="bang-fold-body">' . wp_kses_post( $content ) . '</div>'
		. '</details>';
}

/**
 * 区块回调：折叠内容。
 *
 * @param array $attributes 区块属性。
 * @return string
 */
function bang_render_fold_block( $attributes ) {
	$title   = isset( $attributes['title'] ) ? (string) $attributes['title'] : '';
	$content = isset( $attributes['content'] ) ? (string) $attributes['content'] : '';
	$open    = ! empty( $attributes['open'] );
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'bang-fold-wrap' ) );
	return '<div ' . $wrapper . '>' . bang_kit_fold_html( $title, $content, $open ) . '</div>';
}

/* ============================================================
   画廊：紧贴的多张图 → 宫格，单张 → 大图（页面模板 / 简码共用）
   ============================================================ */
/**
 * 画廊宫格 HTML。外层 data-bang-gallery 让灯箱把这一组收成一个序列。
 *
 * @param array  $images 每项 array('full'=>,'src'=>,'alt'=>)。
 * @param string $mode   grid | single。
 * @return string
 */
function bang_kit_wall_html( $images, $mode = 'grid' ) {
	$items = array();
	foreach ( (array) $images as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$src  = isset( $item['src'] ) ? esc_url_raw( $item['src'] ) : '';
		$full = isset( $item['full'] ) && $item['full'] ? esc_url_raw( $item['full'] ) : $src;
		if ( ! $src ) {
			continue;
		}
		$items[] = array(
			'src'  => $src,
			'full' => $full,
			'alt'  => isset( $item['alt'] ) ? (string) $item['alt'] : '',
		);
	}
	if ( ! $items ) {
		return '';
	}

	$one  = 1 === count( $items );
	$cls  = $one || 'single' === $mode ? 'bang-wall bang-wall--single' : 'bang-wall bang-wall--grid bang-wall--n' . min( count( $items ), 6 );
	$html = '<div class="' . esc_attr( $cls ) . '" data-bang-gallery="1" data-caption="">';
	foreach ( $items as $i => $item ) {
		$html .= '<a class="bang-wall-cell" href="' . esc_url( $item['full'] ) . '"'
			. ' data-bang-lightbox="1" data-index="' . (int) $i . '"'
			. ' aria-label="' . esc_attr( '查看第 ' . ( $i + 1 ) . ' 张图片' ) . '">'
			. '<img src="' . esc_url( $item['src'] ) . '" alt="' . esc_attr( $item['alt'] ) . '" loading="lazy" decoding="async">'
			. '</a>';
	}
	return $html . '</div>';
}

/* ============================================================
   简码：兼容常见写法，粘过来就能用
   ============================================================ */
/**
 * [tip type="info" title="标题"]内容[/tip]
 *
 * @param array  $atts    属性。
 * @param string $content 内容。
 * @return string
 */
function bang_sc_tip( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array( 'type' => 'info', 'title' => '' ),
		(array) $atts,
		'bang_tip'
	);
	return bang_kit_tip_html( $atts['type'], $atts['title'], $content );
}
add_shortcode( 'tip', 'bang_sc_tip' );

/**
 * [fold title="点击展开" open="0"]内容[/fold]
 *
 * @param array  $atts    属性。
 * @param string $content 内容。
 * @return string
 */
function bang_sc_fold( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array( 'title' => '', 'open' => '0' ),
		(array) $atts,
		'bang_fold'
	);
	$open = in_array( (string) $atts['open'], array( '1', 'true', 'yes', 'on' ), true );
	return bang_kit_fold_html( $atts['title'], $content, $open );
}
add_shortcode( 'fold', 'bang_sc_fold' );

/**
 * [code lang="php" file="inc/x.php"]代码[/code]
 *
 * the_content 的 wpautop 已经跑过，先去掉它插进来的 br / p，再还原实体。
 *
 * @param array  $atts    属性。
 * @param string $content 内容。
 * @return string
 */
function bang_sc_code( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array( 'lang' => '', 'file' => '' ),
		(array) $atts,
		'bang_code'
	);
	$code = preg_replace( '#</?p[^>]*>|<br\s*/?>#i', "\n", (string) $content );
	$code = trim( wp_strip_all_tags( $code ) );
	return bang_kit_code_html( $atts['lang'], $code, $atts['file'] );
}
add_shortcode( 'code', 'bang_sc_code' );

/**
 * [wall]图片地址 | 说明[/wall]，一行一张，至少两张自动变宫格。
 *
 * @param array  $atts    属性。
 * @param string $content 内容。
 * @return string
 */
function bang_sc_wall( $atts, $content = '' ) {
	$images = array();
	/* wpautop 会把简码里的换行换成 <br>，先还原，否则多行图片会粘成一行 */
	$content = preg_replace( '#<br\s*/?>#i', "\n", (string) $content );
	foreach ( preg_split( '/\r\n|\r|\n/', $content ) as $line ) {
		$line = trim( wp_strip_all_tags( $line ) );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', preg_split( '/\s*[|｜]\s*/u', $line ) );
		$src   = isset( $parts[0] ) ? $parts[0] : '';
		if ( ! preg_match( '#^(https?://|/)#i', $src ) ) {
			continue;
		}
		$images[] = array(
			'src'  => $src,
			'full' => $src,
			'alt'  => isset( $parts[1] ) ? $parts[1] : '',
		);
	}
	return bang_kit_wall_html( $images );
}
add_shortcode( 'wall', 'bang_sc_wall' );

/* ============================================================
   核心区块增强：core/code 认 lang-xxx，core/details 套壳
   ============================================================ */
/**
 * 给核心代码 / 详情区块补上 Bang 的容器类名与语言标记。
 * 复制按钮仍由 app.js 生成，服务端只输出结构与 data 属性。
 *
 * @param string $content 区块 HTML。
 * @param array  $block   区块数据。
 * @return string
 */
function bang_kit_render_core_block( $content, $block ) {
	$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';

	if ( 'core/code' === $name ) {
		$class = isset( $block['attrs']['className'] ) ? (string) $block['attrs']['className'] : '';
		$lang  = '';
		if ( preg_match( '/(?:^|\s)lang-([a-z0-9+#_-]+)/i', $class, $m ) ) {
			$lang = $m[1];
		}
		$code = '';
		if ( preg_match( '/<code[^>]*>([\s\S]*?)<\/code>/i', $content, $m ) ) {
			$code = html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES, 'UTF-8' );
		}
		if ( '' === $lang ) {
			$lang = bang_code_guess( $code );
		}
		$bar = '<div class="bang-code-bar"><span class="bang-code-lang">'
			. esc_html( bang_code_lang_label( $lang ) ) . '</span></div>';
		$content = preg_replace( '/<pre([^>]*)>/i', '<pre$1 class="bang-code-pre">', $content, 1 );
		return '<div class="bang-code bang-code--core" data-bang-code data-lang="' . esc_attr( $lang ) . '">'
			. $bar . $content . '</div>';
	}

	if ( 'core/details' === $name ) {
		$content = preg_replace_callback(
			'/<details([^>]*)>/i',
			function ( $m ) {
				$attrs = $m[1];
				if ( preg_match( '/\sclass="([^"]*)"/i', $attrs ) ) {
					$attrs = preg_replace( '/\sclass="([^"]*)"/i', 'class="$1 bang-fold bang-fold--core"', $attrs, 1 );
				} else {
					$attrs .= ' class="bang-fold bang-fold--core"';
				}
				return '<details' . $attrs . ' data-bang-fold>';
			},
			$content,
			1
		);
		return preg_replace( '/<summary([^>]*)>/i', '<summary$1 class="bang-fold-sum bang-fold-sum--core">', $content, 1 );
	}

	return $content;
}
add_filter( 'render_block', 'bang_kit_render_core_block', 12, 2 );

/* ============================================================
   kses 白名单：正文会经过 wp_kses_post()，补齐新结构
   ============================================================ */
/**
 * 放行 details / summary / pre / code / div 上的类名与 data-*。
 *
 * @param array  $tags    允许的标签。
 * @param string $context 上下文。
 * @return array
 */
function bang_kit_allowed_html( $tags, $context ) {
	if ( 'post' !== $context ) {
		return $tags;
	}
	$extra = array( 'class' => true, 'data-*' => true, 'aria-label' => true, 'aria-hidden' => true, 'title' => true );
	foreach ( array( 'details', 'summary', 'pre', 'code', 'div', 'span', 'figure', 'figcaption', 'img', 'a' ) as $tag ) {
		if ( ! isset( $tags[ $tag ] ) ) {
			$tags[ $tag ] = array();
		}
		$tags[ $tag ] = array_merge( $tags[ $tag ], $extra );
	}
	$tags['details']['open'] = true;
	$tags['img']['loading']  = true;
	$tags['img']['decoding'] = true;
	return $tags;
}
add_filter( 'wp_kses_allowed_html', 'bang_kit_allowed_html', 12, 2 );

/* ============================================================
   画展：一行紧挨一行的图片 → 宫格，空行分开 → 单张大图
   ============================================================ */
/**
 * 示例图片：正文为空时从媒体库取最近上传的图，空站也能看到画廊。
 *
 * @param int $count 张数。
 * @return array
 */
function bang_kit_sample_images( $count = 6 ) {
	$ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'numberposts'    => (int) $count,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
			'suppress_filters' => true,
		)
	);
	$out = array();
	foreach ( $ids as $id ) {
		$src = wp_get_attachment_image_url( $id, 'large' );
		if ( ! $src ) {
			continue;
		}
		$full = wp_get_attachment_image_url( $id, 'full' );
		$out[] = array(
			'src'  => $src,
			'full' => $full ? $full : $src,
			'alt'  => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
		);
	}
	return $out;
}

/* ============================================================
   时间轴（区块 + 短代码共用同一套解析与 HTML）
   ============================================================ *//**
 * 把「一行一条」的文本拆成时间轴条目。
 * 写法：日期|标题|说明，后两段可省略。
 *
 * @param string $text 原始文本。
 * @return array
 */
/**
 * 时间轴日期清洗：去掉可能带的时分秒，只保留年月日。
 * 兼容旧的手填数据，如「2024-03-15 10:30」「2024-03-15T10:30:00」→「2024-03-15」；
 * 中文「2024年3月15日 10:30」→「2024年3月15日」。纯年月日 / 自定义文字不受影响。
 *
 * @param string $date 原始日期。
 * @return string
 */
function bang_kit_timeline_date( $date ) {
	$date = trim( (string) $date );
	if ( '' === $date ) {
		return '';
	}
	return trim( preg_replace( '/[\sT]+\d{1,2}:\d{2}(?::\d{2})?\s*$/u', '', $date ) );
}

function bang_kit_timeline_items( $text ) {
	$items = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( wp_strip_all_tags( $line ) );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', preg_split( '/\s*[|｜]\s*/u', $line, 3 ) );
		$date  = bang_kit_timeline_date( isset( $parts[0] ) ? $parts[0] : '' );
		$title = isset( $parts[1] ) ? $parts[1] : '';
		$desc  = isset( $parts[2] ) ? $parts[2] : '';
		if ( '' === $date && '' === $title && '' === $desc ) {
			continue;
		}
		$items[] = array(
			'date'  => $date,
			'title' => $title,
			'desc'  => $desc,
		);
	}
	return $items;
}

/**
 * 时间轴 HTML。竖线由 CSS 伪元素画，圆点在左，正文在右。
 *
 * @param string $text 原始文本。
 * @return string
 */
function bang_kit_timeline_html( $text ) {
	$items = bang_kit_timeline_items( $text );
	if ( ! $items ) {
		return '';
	}
	$html = '<div class="bang-timeline" data-bang-timeline>';
	foreach ( $items as $item ) {
		$html .= '<div class="bang-timeline-item">'
			. '<span class="bang-timeline-dot" aria-hidden="true"></span>'
			. '<div class="bang-timeline-body">'
			. ( '' !== $item['date'] ? '<time class="bang-timeline-date">' . esc_html( $item['date'] ) . '</time>' : '' )
			. ( '' !== $item['title'] ? '<p class="bang-timeline-title">' . esc_html( $item['title'] ) . '</p>' : '' )
			. ( '' !== $item['desc'] ? '<p class="bang-timeline-desc">' . esc_html( $item['desc'] ) . '</p>' : '' )
			. '</div></div>';
	}
	return $html . '</div>';
}

/**
 * 区块回调：时间轴。
 *
 * @param array $attributes 区块属性。
 * @return string
 */
function bang_render_timeline_block( $attributes ) {
	$text    = isset( $attributes['text'] ) ? (string) $attributes['text'] : '';
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'bang-timeline-wrap' ) );
	return '<div ' . $wrapper . '>' . bang_kit_timeline_html( $text ) . '</div>';
}

/**
 * [timeline items="2024-03|上线|第一版发布"] —— 对应 bang/timeline。
 *
 * @param array  $atts    属性。
 * @param string $content 包裹内容。
 * @return string
 */
function bang_sc_timeline( $atts, $content = '' ) {
	$atts = shortcode_atts( array( 'items' => '' ), (array) $atts, 'bang_timeline' );
	$text = '' !== trim( (string) $atts['items'] )
		? str_replace( array( ';', '；' ), "\n", (string) $atts['items'] )
		: preg_replace( '#<br\s*/?>#i', "\n", (string) $content );
	return bang_kit_timeline_html( $text );
}
add_shortcode( 'timeline', 'bang_sc_timeline' );

/* ============================================================
   文章卡片：正文里插一张文章卡（区块 + 短代码共用解析与 HTML）
   ============================================================ */
/**
 * 从文章 ID / 链接解析卡片数据。站内文章自动取封面 / 标题 / 摘要 / 日期，
 * 自定义 title / desc 覆盖对应字段；纯外链走中转页，不显示日期。
 *
 * @param string $target 文章 ID 或链接。
 * @param string $title  自定义标题。
 * @param string $desc   自定义摘要。
 * @return array
 */
function bang_kit_postcard_data( $target, $title = '', $desc = '' ) {
	$target = trim( (string) $target );
	if ( '' === $target ) {
		return array();
	}

	$post = null;
	if ( ctype_digit( $target ) ) {
		$post = get_post( (int) $target );
	} elseif ( preg_match( '#^https?://#i', $target ) ) {
		$id = url_to_postid( $target );
		if ( $id ) {
			$post = get_post( $id );
		}
	}

	if ( $post ) {
		if ( 'publish' !== $post->post_status ) {
			return array();
		}
		$excerpt = ! empty( $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;
		$excerpt = wp_html_excerpt( wp_strip_all_tags( $excerpt ), 90, '…' );
		return array(
			'url'   => get_permalink( $post ),
			'title' => '' !== trim( (string) $title ) ? trim( (string) $title ) : get_the_title( $post ),
			'desc'  => '' !== trim( (string) $desc ) ? trim( (string) $desc ) : $excerpt,
			'image' => get_the_post_thumbnail_url( $post, 'medium' ),
			'date'  => get_the_date( 'Y.m.d', $post ),
		);
	}

	/* 解析不出文章但确实是网址时，退化为外链卡片 */
	if ( preg_match( '#^https?://#i', $target ) ) {
		$url  = function_exists( 'bang_go_url' ) ? bang_go_url( $target ) : $target;
		$host = wp_parse_url( $target, PHP_URL_HOST );
		return array(
			'url'   => $url,
			'title' => '' !== trim( (string) $title ) ? trim( (string) $title ) : ( $host ? $host : '外部链接' ),
			'desc'  => trim( (string) $desc ),
			'image' => '',
			'date'  => '',
		);
	}
	return array();
}

/**
 * 文章卡片 HTML：左侧封面、右侧标题 / 摘要 / 日期，整卡可点。
 *
 * @param string $target 文章 ID 或链接。
 * @param string $title  自定义标题。
 * @param string $desc   自定义摘要。
 * @return string
 */
function bang_kit_postcard_html( $target, $title = '', $desc = '' ) {
	$data = bang_kit_postcard_data( $target, $title, $desc );
	if ( ! $data ) {
		return '';
	}
	$external = '' === $data['date'];
	$html = '<a class="bang-postcard" href="' . esc_url( $data['url'] ) . '"'
		. ( $external ? ' target="_blank" rel="noopener noreferrer nofollow"' : '' ) . '>';
	$html .= '<span class="bang-postcard-cover">';
	$html .= $data['image']
		? '<img src="' . esc_url( $data['image'] ) . '" alt="" loading="lazy" decoding="async">'
		: bang_kit_icon( 'image', 22 );
	$html .= '</span><span class="bang-postcard-main">';
	if ( ! $external ) {
		$html .= '<time class="bang-postcard-date">' . esc_html( $data['date'] ) . '</time>';
	}
	$html .= '<span class="bang-postcard-title">' . esc_html( $data['title'] ) . '</span>';
	if ( '' !== $data['desc'] ) {
		$html .= '<span class="bang-postcard-desc">' . esc_html( $data['desc'] ) . '</span>';
	}
	$html .= '</span></a>';
	return $html;
}

/**
 * 区块回调：文章卡片。
 *
 * @param array $attributes 区块属性。
 * @return string
 */
function bang_render_postcard_block( $attributes ) {
	$target  = isset( $attributes['target'] ) ? (string) $attributes['target'] : '';
	$title   = isset( $attributes['title'] ) ? (string) $attributes['title'] : '';
	$desc    = isset( $attributes['desc'] ) ? (string) $attributes['desc'] : '';
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'bang-postcard-wrap' ) );
	return '<div ' . $wrapper . '>' . bang_kit_postcard_html( $target, $title, $desc ) . '</div>';
}

/**
 * [postcard id="123"] 或 [postcard url="https://…" title="…" desc="…"]
 * 不写 id / url 时读包裹内容当目标。
 *
 * @param array  $atts    属性。
 * @param string $content 兜底目标。
 * @return string
 */
function bang_sc_postcard( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'id'          => '',
			'url'         => '',
			'title'       => '',
			'desc'        => '',
			'description' => '',
		),
		(array) $atts,
		'bang_postcard'
	);
	$target = '' !== trim( (string) $atts['id'] )
		? (string) $atts['id']
		: ( '' !== trim( (string) $atts['url'] ) ? (string) $atts['url'] : trim( wp_strip_all_tags( (string) $content ) ) );
	$desc   = '' !== trim( (string) $atts['desc'] ) ? (string) $atts['desc'] : (string) $atts['description'];
	return bang_kit_postcard_html( $target, (string) $atts['title'], $desc );
}
add_shortcode( 'postcard', 'bang_sc_postcard' );

/* ============================================================
   下载区块：网盘 / 直链列表，解锁方式与「阅读解锁」五选一完全一致
   未解锁只输出锁定卡片（复用 bang_unlock_card_html + 轮询刷新），
   已解锁才输出真实下载地址；鉴权全在 bang_unlock_can_read。
   ============================================================ */
/**
 * 解析下载项：每行「名称 | 链接 | 提取码」，后两段可省。
 * 链接必须是 http(s)，防空链接与 javascript:。
 *
 * @param string $text 原始多行文本。
 * @return array
 */
function bang_kit_download_items( $text ) {
	$items = array();
	foreach ( explode( "\n", (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '|', $line ) );
		$name  = isset( $parts[0] ) ? $parts[0] : '';
		$url   = isset( $parts[1] ) ? $parts[1] : '';
		$code  = isset( $parts[2] ) ? $parts[2] : '';
		if ( '' === $name && '' === $url ) {
			continue;
		}
		if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
			/* 没写合法链接的整行丢掉，避免输出不可点的空按钮 */
			continue;
		}
		$items[] = array(
			'name' => '' !== $name ? $name : '下载地址',
			'url'  => esc_url_raw( $url ),
			'code' => $code,
		);
	}
	return $items;
}

/**
 * 已解锁状态下的下载列表 HTML。
 *
 * @param array $items 解析后的下载项。
 * @return string
 */
function bang_kit_download_list_html( $items ) {
	$html = '<div class="bang-download-list">';
	foreach ( $items as $item ) {
		$html .= '<div class="bang-download-item">'
			. '<span class="bang-download-ico">' . bang_kit_icon( 'disk', 20 ) . '</span>'
			. '<span class="bang-download-main">'
			. '<strong class="bang-download-name">' . esc_html( $item['name'] ) . '</strong>';
		if ( '' !== $item['code'] ) {
			$html .= '<span class="bang-download-code">提取码：' . esc_html( $item['code'] )
				. '<a class="bang-download-copy" href="#" data-bang-download-copy="' . esc_attr( $item['code'] ) . '">复制</a></span>';
		}
		$html .= '</span>'
			. '<a class="bang-download-btn" href="' . esc_url( $item['url'] ) . '" target="_blank" rel="noopener noreferrer nofollow">'
			. bang_kit_icon( 'download', 15 ) . '<span>前往下载</span></a>'
			. '</div>';
	}
	$html .= '</div>';
	return $html;
}

/**
 * 下载区块渲染（区块 / 简码共用）。
 *
 * @param array $args {title,desc,teaser,items,locked,post_id,method,password,lock}。
 * @return string
 */
function bang_kit_download_html( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'title'    => '',
			'desc'     => '',
			'teaser'   => '',
			'items'    => '',
			'locked'   => true,
			'post_id'  => bang_unlock_current_post(),
			'method'   => 'login',
			'password' => '',
			'lock'     => '',
		)
	);
	$items   = bang_kit_download_items( $args['items'] );
	$post_id = (int) $args['post_id'];
	$method  = bang_unlock_normalize_method( $args['method'] );
	$lock    = '' !== trim( (string) $args['lock'] )
		? sanitize_key( (string) $args['lock'] )
		: bang_unlock_lock_for( $post_id, $method, $args['password'] );

	/* 没配下载地址：作者留空时不输出空卡，给一句轻提示 */
	if ( ! $items ) {
		return '<div class="bang-download-empty">还没有配置下载地址。</div>';
	}

	if ( ! $args['locked'] || bang_unlock_can_read( $post_id, $method, $lock ) ) {
		$html = '<div class="bang-download">';
		if ( '' !== trim( (string) $args['title'] ) || '' !== trim( (string) $args['desc'] ) ) {
			$html .= '<div class="bang-download-head">'
				. '<span class="bang-download-badge">' . bang_kit_icon( 'download', 18 ) . '</span>'
				. '<span class="bang-download-titles">';
			if ( '' !== trim( (string) $args['title'] ) ) {
				$html .= '<strong class="bang-download-title">' . esc_html( $args['title'] ) . '</strong>';
			}
			if ( '' !== trim( (string) $args['desc'] ) ) {
				$html .= '<span class="bang-download-desc">' . esc_html( $args['desc'] ) . '</span>';
			}
			$html .= '</span></div>';
		}
		$html .= bang_kit_download_list_html( $items ) . '</div>';
		return $html;
	}

	/* 未解锁：复用阅读锁定卡片，前端只展示、整页刷新后由服务端放行 */
	$labels = bang_unlock_method_labels();
	$card   = bang_unlock_card_html(
		array(
			'title'   => '' !== trim( (string) $args['title'] ) ? $args['title'] : '资源已锁定',
			'desc'    => '' !== trim( (string) $args['desc'] ) ? $args['desc'] : '使用「' . $labels[ $method ] . '」解锁后获取下载地址',
			'teaser'  => $args['teaser'],
			'compact' => true,
			'post_id' => $post_id,
			'method'  => $method,
			'lock'    => $lock,
		)
	);
	return '<div class="bang-download-wrap" data-bang-unlock-part="1">' . $card . '</div>';
}

/**
 * 动态区块 render_callback：下载区块。
 *
 * @param array $attributes 区块属性。
 * @return string
 */
function bang_render_download_block( $attributes ) {
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'bang-download-block' ) );
	return '<div ' . $wrapper . '>' . bang_kit_download_html(
		array(
			'title'    => isset( $attributes['title'] ) ? $attributes['title'] : '',
			'desc'     => isset( $attributes['desc'] ) ? $attributes['desc'] : '',
			'teaser'   => isset( $attributes['teaser'] ) ? $attributes['teaser'] : '',
			'items'    => isset( $attributes['items'] ) ? $attributes['items'] : '',
			'locked'   => ! empty( $attributes['locked'] ),
			'post_id'  => bang_unlock_current_post(),
			'method'   => isset( $attributes['method'] ) ? $attributes['method'] : 'login',
			'password' => isset( $attributes['password'] ) ? $attributes['password'] : '',
		)
	) . '</div>';
}

/**
 * [download method="login" password="…" title="…" desc="…"]每行一个下载地址[/download]
 * method 与阅读解锁一致：login | like | comment | password | combo。
 *
 * @param array  $atts    属性。
 * @param string $content 下载项（每行「名称|链接|提取码」）。
 * @return string
 */
function bang_sc_download( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'title'    => '',
			'desc'     => '',
			'teaser'   => '',
			'method'   => 'login',
			'password' => '',
			'locked'   => '1',
		),
		(array) $atts,
		'bang_download'
	);
	$locked = ! in_array( strtolower( (string) $atts['locked'] ), array( '0', 'off', 'no', 'false' ), true );
	return bang_kit_download_html(
		array(
			'title'    => $atts['title'],
			'desc'     => $atts['desc'],
			'teaser'   => $atts['teaser'],
			'items'    => $content,
			'locked'   => $locked,
			'post_id'  => bang_unlock_current_post(),
			'method'   => $atts['method'],
			'password' => $atts['password'],
		)
	);
}
add_shortcode( 'download', 'bang_sc_download' );

