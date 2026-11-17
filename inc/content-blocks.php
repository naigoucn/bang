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
 * #pk-data 时不需要执行脚本，复制按钮这类交互由 app.js 在浏览器端补上。
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
function pk_kit_icon( $name, $size = 16 ) {
	$paths = array(
		'info'    => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12" y2="8"/>',
		'warning' => '<path d="M10.3 3.9 2.6 17.2A1.9 1.9 0 0 0 4.3 20h15.4a1.9 1.9 0 0 0 1.7-2.8L13.7 3.9a1.9 1.9 0 0 0-3.4 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12" y2="17"/>',
		'success' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 2.7 2.7L16 9.5"/>',
		'danger'  => '<circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/>',
		'chevron' => '<path d="m6 9 6 6 6-6"/>',
		'copy'    => '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M15 5.5A1.5 1.5 0 0 0 13.5 4H5.5A1.5 1.5 0 0 0 4 5.5v8A1.5 1.5 0 0 0 5.5 15"/>',
		'check'   => '<path d="m5 12 4.5 4.5L19 7"/>',
		'bell'    => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/>',
		'x'       => '<path d="M6 6 18 18M18 6 6 18"/>',
		'arrow'   => '<path d="M5 12h13M13 6l6 6-6 6"/>',
		'pin'     => '<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
		'image'   => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m4 17 5-5 4 4 3-3 4 4"/>',
		'link'    => '<path d="M10 13a4 4 0 0 0 5.7 0l2.6-2.6a4 4 0 0 0-5.7-5.7L11 6.3"/><path d="M14 11a4 4 0 0 0-5.7 0L5.7 13.6a4 4 0 0 0 5.7 5.7L13 17.7"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="pk-kit-ico" viewBox="0 0 24 24" width="' . (int) $size . '" height="' . (int) $size . '"'
		. ' fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
		. $paths[ $name ] . '</svg>';
}

/* ============================================================
   注册三个新增区块（动态区块，编辑器脚本统一由 build/index.js 提供）
   ============================================================ */
/**
 * 注册 Bang 区块组里的新增成员。
 */
function pk_register_content_blocks() {
	$map = array(
		'tip'    => 'pk_render_tip_block',
		'fold'   => 'pk_render_fold_block',
		'code'   => 'pk_render_code_block',
		/* 0.13.20：阅读解锁，render_callback 在 inc/unlock.php */
		'unlock' => 'pk_render_unlock_block',
	);
	foreach ( $map as $dir => $callback ) {
		$path = get_template_directory() . '/blocks/' . $dir;
		if ( file_exists( $path . '/block.json' ) ) {
			register_block_type( $path, array( 'render_callback' => $callback ) );
		}
	}
}
add_action( 'init', 'pk_register_content_blocks', 11 );

/* ============================================================
   提示框
   ============================================================ */
/**
 * 四种状态的语义与图标。四种状态用低饱和主色区分（图标 / 标签 / 边框 / 底色），
 * 卡片形状完全一致，不再靠左边线或图标形状。
 *
 * @return array
 */
function pk_tip_states() {
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
function pk_kit_tip_html( $type, $title, $content ) {
	$states = pk_tip_states();
	$type   = isset( $states[ $type ] ) ? $type : 'info';
	$state  = $states[ $type ];
	$title  = trim( (string) $title );

	$head  = '<div class="pk-tip-head">'
		. '<span class="pk-tip-ico">' . pk_kit_icon( $state['icon'], 16 ) . '</span>'
		. '<span class="pk-tip-label">' . esc_html( $state['label'] ) . '</span>'
		. ( '' !== $title ? '<strong class="pk-tip-title">' . esc_html( $title ) . '</strong>' : '' )
		. '</div>';
	$body = '<div class="pk-tip-body">' . wp_kses_post( $content ) . '</div>';

	return '<div class="pk-tip pk-tip--' . esc_attr( $type ) . '" data-pk-tip="' . esc_attr( $type ) . '">'
		. $head . $body . '</div>';
}

/**
 * 区块回调：提示框。
 *
 * @param array $attributes 区块属性。
 * @return string
 */
function pk_render_tip_block( $attributes ) {
	$type    = isset( $attributes['type'] ) ? (string) $attributes['type'] : 'info';
	$title   = isset( $attributes['title'] ) ? (string) $attributes['title'] : '';
	$content = isset( $attributes['content'] ) ? (string) $attributes['content'] : '';
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'pk-tip-wrap' ) );
	return '<div ' . $wrapper . '>' . pk_kit_tip_html( $type, $title, $content ) . '</div>';
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
function pk_code_lang_label( $lang ) {
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
function pk_code_guess( $code ) {
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
function pk_kit_code_html( $lang, $code, $caption = '' ) {
	$lang = preg_replace( '/[^a-z0-9+#_-]/i', '', strtolower( (string) $lang ) );
	$code = html_entity_decode( (string) $code, ENT_QUOTES, 'UTF-8' );
	if ( '' === $lang ) {
		$lang = pk_code_guess( $code );
	}
	$cap = trim( (string) $caption );

	return '<div class="pk-code" data-pk-code data-lang="' . esc_attr( $lang ) . '">'
		. '<div class="pk-code-bar"><span class="pk-code-lang">' . esc_html( pk_code_lang_label( $lang ) ) . '</span>'
		. ( '' !== $cap ? '<span class="pk-code-file">' . esc_html( $cap ) . '</span>' : '' )
		. '</div>'
		. '<pre class="pk-code-pre"><code>' . esc_html( $code ) . '</code></pre>'
		. '</div>';
}

/**
 * 区块回调：代码块。
 *
 * @param array $attributes 区块属性。
 * @return string
 */
function pk_render_code_block( $attributes ) {
	$lang    = isset( $attributes['lang'] ) ? (string) $attributes['lang'] : '';
	$code    = isset( $attributes['content'] ) ? (string) $attributes['content'] : '';
	$caption = isset( $attributes['caption'] ) ? (string) $attributes['caption'] : '';
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'pk-code-wrap' ) );
	return '<div ' . $wrapper . '>' . pk_kit_code_html( $lang, $code, $caption ) . '</div>';
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
function pk_kit_fold_html( $title, $content, $open = false ) {
	$title = trim( (string) $title );
	return '<details class="pk-fold" data-pk-fold' . ( $open ? ' open' : '' ) . '>'
		. '<summary class="pk-fold-sum"><span class="pk-fold-chev">' . pk_kit_icon( 'chevron', 15 ) . '</span>'
		. '<span class="pk-fold-title">' . esc_html( '' !== $title ? $title : '点击展开' ) . '</span>'
		. '<span class="pk-fold-hint">' . esc_html__( '展开', 'pianke' ) . '</span></summary>'
		. '<div class="pk-fold-body">' . wp_kses_post( $content ) . '</div>'
		. '</details>';
}

/**
 * 区块回调：折叠内容。
 *
 * @param array $attributes 区块属性。
 * @return string
 */
function pk_render_fold_block( $attributes ) {
	$title   = isset( $attributes['title'] ) ? (string) $attributes['title'] : '';
	$content = isset( $attributes['content'] ) ? (string) $attributes['content'] : '';
	$open    = ! empty( $attributes['open'] );
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'pk-fold-wrap' ) );
	return '<div ' . $wrapper . '>' . pk_kit_fold_html( $title, $content, $open ) . '</div>';
}

/* ============================================================
   画廊：紧贴的多张图 → 宫格，单张 → 大图（页面模板 / 简码共用）
   ============================================================ */
/**
 * 画廊宫格 HTML。外层 data-pk-gallery 让灯箱把这一组收成一个序列。
 *
 * @param array  $images 每项 array('full'=>,'src'=>,'alt'=>)。
 * @param string $mode   grid | single。
 * @return string
 */
function pk_kit_wall_html( $images, $mode = 'grid' ) {
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
	$cls  = $one || 'single' === $mode ? 'pk-wall pk-wall--single' : 'pk-wall pk-wall--grid pk-wall--n' . min( count( $items ), 6 );
	$html = '<div class="' . esc_attr( $cls ) . '" data-pk-gallery="1" data-caption="">';
	foreach ( $items as $i => $item ) {
		$html .= '<a class="pk-wall-cell" href="' . esc_url( $item['full'] ) . '"'
			. ' data-pk-lightbox="1" data-index="' . (int) $i . '"'
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
function pk_sc_tip( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array( 'type' => 'info', 'title' => '' ),
		(array) $atts,
		'pk_tip'
	);
	return pk_kit_tip_html( $atts['type'], $atts['title'], $content );
}
add_shortcode( 'tip', 'pk_sc_tip' );

/**
 * [fold title="点击展开" open="0"]内容[/fold]
 *
 * @param array  $atts    属性。
 * @param string $content 内容。
 * @return string
 */
function pk_sc_fold( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array( 'title' => '', 'open' => '0' ),
		(array) $atts,
		'pk_fold'
	);
	$open = in_array( (string) $atts['open'], array( '1', 'true', 'yes', 'on' ), true );
	return pk_kit_fold_html( $atts['title'], $content, $open );
}
add_shortcode( 'fold', 'pk_sc_fold' );

/**
 * [code lang="php" file="inc/x.php"]代码[/code]
 *
 * the_content 的 wpautop 已经跑过，先去掉它插进来的 br / p，再还原实体。
 *
 * @param array  $atts    属性。
 * @param string $content 内容。
 * @return string
 */
function pk_sc_code( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array( 'lang' => '', 'file' => '' ),
		(array) $atts,
		'pk_code'
	);
	$code = preg_replace( '#</?p[^>]*>|<br\s*/?>#i', "\n", (string) $content );
	$code = trim( wp_strip_all_tags( $code ) );
	return pk_kit_code_html( $atts['lang'], $code, $atts['file'] );
}
add_shortcode( 'code', 'pk_sc_code' );

/**
 * [wall]图片地址 | 说明[/wall]，一行一张，至少两张自动变宫格。
 *
 * @param array  $atts    属性。
 * @param string $content 内容。
 * @return string
 */
function pk_sc_wall( $atts, $content = '' ) {
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
	return pk_kit_wall_html( $images );
}
add_shortcode( 'wall', 'pk_sc_wall' );

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
function pk_kit_render_core_block( $content, $block ) {
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
			$lang = pk_code_guess( $code );
		}
		$bar = '<div class="pk-code-bar"><span class="pk-code-lang">'
			. esc_html( pk_code_lang_label( $lang ) ) . '</span></div>';
		$content = preg_replace( '/<pre([^>]*)>/i', '<pre$1 class="pk-code-pre">', $content, 1 );
		return '<div class="pk-code pk-code--core" data-pk-code data-lang="' . esc_attr( $lang ) . '">'
			. $bar . $content . '</div>';
	}

	if ( 'core/details' === $name ) {
		$content = preg_replace_callback(
			'/<details([^>]*)>/i',
			function ( $m ) {
				$attrs = $m[1];
				if ( preg_match( '/\sclass="([^"]*)"/i', $attrs ) ) {
					$attrs = preg_replace( '/\sclass="([^"]*)"/i', 'class="$1 pk-fold pk-fold--core"', $attrs, 1 );
				} else {
					$attrs .= ' class="pk-fold pk-fold--core"';
				}
				return '<details' . $attrs . ' data-pk-fold>';
			},
			$content,
			1
		);
		return preg_replace( '/<summary([^>]*)>/i', '<summary$1 class="pk-fold-sum pk-fold-sum--core">', $content, 1 );
	}

	return $content;
}
add_filter( 'render_block', 'pk_kit_render_core_block', 12, 2 );

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
function pk_kit_allowed_html( $tags, $context ) {
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
add_filter( 'wp_kses_allowed_html', 'pk_kit_allowed_html', 12, 2 );

/* ============================================================
   画展：一行紧挨一行的图片 → 宫格，空行分开 → 单张大图
   ============================================================ */
/**
 * 示例图片：正文为空时从媒体库取最近上传的图，空站也能看到画廊。
 *
 * @param int $count 张数。
 * @return array
 */
function pk_kit_sample_images( $count = 6 ) {
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

