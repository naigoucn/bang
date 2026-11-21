<?php
/**
 * Bang · Gutenberg 区块
 *
 * 四个区块都是动态区块（编辑器里 save 返回 null），前台由 render_callback
 * 输出纯 HTML。这样首页把阅读层 HTML 内联进 #bang-data 时不需要执行脚本，
 * 也不会被 wp_kses_post() 剥掉自定义结构。
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 编辑器插入器里单独成组，四个区块聚在一起。
 */
function bang_block_categories( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'bang-blocks',
			'title' => __( 'Bang 区块', 'bang' ),
		)
	);
	return $categories;
}
add_filter( 'block_categories_all', 'bang_block_categories' );

/**
 * 注册区块。block.json 由 PHP 读取，编辑器脚本统一挂一份 build/index.js，
 * 避免四个 block.json 各自声明 editorScript 导致同一份脚本被加载四次。
 */
function bang_register_blocks() {
	$callbacks = array(
		'gallery'  => 'bang_render_gallery_block',
		'quote'    => 'bang_render_quote_block',
		'linkcard' => 'bang_render_linkcard_block',
		'meta'     => 'bang_render_meta_block',
	);

	foreach ( $callbacks as $dir => $callback ) {
		$path = get_template_directory() . '/blocks/' . $dir;
		if ( ! file_exists( $path . '/block.json' ) ) {
			continue;
		}
		register_block_type(
			$path,
			array( 'render_callback' => $callback )
		);
	}
}
add_action( 'init', 'bang_register_blocks' );

/**
 * 编辑器脚本（wp-scripts 构建产物 + 自动生成的依赖清单）。
 */
function bang_enqueue_block_editor_assets() {
	$asset_file = get_template_directory() . '/build/index.asset.php';
	$asset      = file_exists( $asset_file )
		? require $asset_file
		: array(
			'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			'version'      => BANG_VERSION,
		);

	wp_enqueue_script(
		'bang-blocks-editor',
		get_template_directory_uri() . '/build/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);
	/* 区块在前台 / 编辑器里共用一份预览样式（含 0.13.20 阅读解锁卡片） */
	wp_enqueue_style(
		'bang-blocks-editor-style',
		get_template_directory_uri() . '/assets/css/blocks.css',
		array(),
		$asset['version']
	);
}
add_action( 'enqueue_block_editor_assets', 'bang_enqueue_block_editor_assets' );

/**
 * 前台区块样式。放在主样式之后，保证覆盖核心区块默认外观。
 */
function bang_enqueue_block_styles() {
	wp_enqueue_style(
		'bang-blocks',
		get_template_directory_uri() . '/assets/css/blocks.css',
		array( 'bang-style' ),
		BANG_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'bang_enqueue_block_styles', 15 );

/**
 * 正文经过 wp_kses_post()，这里补齐区块输出会用到的标签与属性。
 * 只放宽图片属性与 data、aria 前缀属性，不放行 script / iframe / 自定义元素。
 */
function bang_allow_block_html( $tags, $context ) {
	if ( 'post' !== $context ) {
		return $tags;
	}

	$image_attrs = array(
		'src'         => true,
		'alt'         => true,
		'title'       => true,
		'class'       => true,
		'id'          => true,
		'width'       => true,
		'height'      => true,
		'srcset'      => true,
		'sizes'       => true,
		'loading'     => true,
		'decoding'    => true,
		'aria-hidden' => true,
		'data-*'      => true,
	);
	$tags['img'] = isset( $tags['img'] ) ? array_merge( $tags['img'], $image_attrs ) : $image_attrs;

	foreach ( array( 'div', 'span', 'figure', 'figcaption', 'blockquote', 'p', 'a', 'time' ) as $tag ) {
		if ( ! isset( $tags[ $tag ] ) ) {
			$tags[ $tag ] = array();
		}
		$tags[ $tag ]['data-*']      = true;
		$tags[ $tag ]['aria-hidden'] = true;
		$tags[ $tag ]['aria-label']  = true;
	}

	$tags['a']['target'] = true;
	$tags['a']['rel']    = true;
	$tags['a']['title']  = true;

	return $tags;
}
add_filter( 'wp_kses_allowed_html', 'bang_allow_block_html', 10, 2 );

/**
 * 单张可点图片：用 <a> 而不是 <button>，wp_kses_post() 才会保留。
 * href 指向原图，无 JS 时也能直接打开；灯箱脚本会 preventDefault 接管。
 */
function bang_gallery_link( $item, $index, $class, $img, $label ) {
	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $item['full'] ) . '"'
		. ' data-bang-lightbox="1" data-index="' . (int) $index . '"'
		. ' aria-label="' . esc_attr( $label ) . '" title="' . esc_attr( $label ) . '">'
		. $img . '</a>';
}

/**
 * 图集：默认「主图 + 缩略条」，可选「拼贴宫格」。全部图片点击后进入灯箱。
 * 旧的 marquee 值兼容映射到 stack，保证此前用区块写的文章不用重新保存。
 */
function bang_render_gallery_block( $attributes ) {
	$raw_layout = isset( $attributes['layout'] ) ? $attributes['layout'] : 'stack';
	$layout     = 'grid' === $raw_layout ? 'grid' : 'stack';
	$caption    = isset( $attributes['caption'] ) ? sanitize_text_field( $attributes['caption'] ) : '';
	$raw        = isset( $attributes['images'] ) && is_array( $attributes['images'] ) ? $attributes['images'] : array();

	$images = array();
	foreach ( $raw as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$id  = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
		$url = isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
		$alt = isset( $item['alt'] ) ? sanitize_text_field( $item['alt'] ) : '';

		if ( $id ) {
			$large = wp_get_attachment_image( $id, 'large', false, array( 'alt' => $alt ) );
			if ( $large ) {
				$thumb = wp_get_attachment_image( $id, 'medium', false, array( 'alt' => $alt, 'loading' => 'lazy' ) );
				$full  = wp_get_attachment_image_url( $id, 'full' );
				$images[] = array(
					'large' => $large,
					'thumb' => $thumb ? $thumb : $large,
					'full'  => $full ? $full : $url,
					'alt'   => $alt,
				);
				continue;
			}
		}
		if ( $url ) {
			$img = '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async">';
			$images[] = array( 'large' => $img, 'thumb' => $img, 'full' => $url, 'alt' => $alt );
		}
	}

	$classes = 'bang-gallery bang-gallery--' . $layout . ' bang-gallery--n' . min( count( $images ), 3 );
	if ( count( $images ) < 2 ) {
		$classes .= ' bang-gallery--single';
	}
	$wrapper = get_block_wrapper_attributes( array( 'class' => $classes ) );
	$open    = '<figure ' . $wrapper . ' data-bang-gallery="1" data-caption="' . esc_attr( $caption ) . '">';

	if ( empty( $images ) ) {
		return $open . '<div class="bang-gallery-empty">' . esc_html__( '还没有选择图片', 'bang' ) . '</div></figure>';
	}

	$first = $images[0];
	$total = count( $images );

	if ( 'grid' === $layout ) {
		$cells = '';
		foreach ( $images as $index => $item ) {
			$extra  = 0 === $index ? '<span class="bang-gallery-count">' . esc_html( $total ) . ' 张</span>' : '';
			$anchor = bang_gallery_link( $item, $index, 'bang-gallery-cell', $item['thumb'], '查看第 ' . ( $index + 1 ) . ' 张图片' );
			$cells .= str_replace( '</a>', $extra . '</a>', $anchor );
		}
		$html = $open . '<div class="bang-gallery-grid">' . $cells . '</div>';
	} else {
		$main = bang_gallery_link( $first, 0, 'bang-gallery-main', $first['large'], '查看原图' );
		$main = str_replace(
			'</a>',
			'<span class="bang-gallery-zoom" aria-hidden="true"></span>'
			. '<span class="bang-gallery-count">' . esc_html( $total ) . ' 张</span></a>',
			$main
		);
		$thumbs = '';
		foreach ( $images as $index => $item ) {
			$thumbs .= bang_gallery_link(
				$item,
				$index,
				'bang-gallery-thumb' . ( 0 === $index ? ' is-current' : '' ),
				$item['thumb'],
				'查看第 ' . ( $index + 1 ) . ' 张图片'
			);
		}
		$html = $open . '<div class="bang-gallery-stage">' . $main . '</div>'
			. '<div class="bang-gallery-thumbs">' . $thumbs . '</div>';
	}

	if ( $caption ) {
		$html .= '<figcaption>' . esc_html( $caption ) . '</figcaption>';
	}
	return $html . '</figure>';
}

/**
 * 金句卡。
 */
function bang_render_quote_block( $attributes ) {
	$text   = isset( $attributes['text'] ) ? wp_kses_post( $attributes['text'] ) : '';
	$source = isset( $attributes['source'] ) ? sanitize_text_field( $attributes['source'] ) : '';
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'bang-quote' ) );

	$html = '<blockquote ' . $wrapper . '><p class="bang-quote-text">' . $text . '</p>';
	if ( $source ) {
		$html .= '<span class="bang-quote-source">' . esc_html( $source ) . '</span>';
	}
	return $html . '</blockquote>';
}

/**
 * 链接卡：外链一律走 ?bang_go= 中转确认页，新标签打开。
 */
function bang_render_linkcard_block( $attributes ) {
	$url  = isset( $attributes['url'] ) ? bang_safe_comment_site( $attributes['url'] ) : '';
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'bang-linkcard' ) );

	if ( ! $url ) {
		return '<div ' . $wrapper . '><span class="bang-linkcard-host">' . esc_html__( '链接无效或不被允许', 'bang' ) . '</span></div>';
	}

	$host = wp_parse_url( $url, PHP_URL_HOST );
	$title = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : '';
	$desc  = isset( $attributes['description'] ) ? sanitize_text_field( $attributes['description'] ) : '';
	if ( ! $title ) {
		$title = $host;
	}

	$html  = '<a ' . $wrapper . ' href="' . esc_url( bang_go_url( $url ) ) . '"'
		. ' target="_blank" rel="noopener noreferrer nofollow">';
	$html .= '<span class="bang-linkcard-title">' . esc_html( $title ) . '</span>';
	if ( $desc ) {
		$html .= '<span class="bang-linkcard-desc">' . esc_html( $desc ) . '</span>';
	}
	$html .= '<span class="bang-linkcard-foot"><span class="bang-linkcard-host">' . esc_html( $host ) . '</span>'
		. '<span class="bang-linkcard-arrow" aria-hidden="true"></span></span>';
	return $html . '</a>';
}

/**
 * 元信息条：一组「名称 / 内容」，用圆点分隔。
 */
function bang_render_meta_block( $attributes ) {
	$items = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : array();
	$out   = '';

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$label = isset( $item['label'] ) ? sanitize_text_field( $item['label'] ) : '';
		$value = isset( $item['value'] ) ? sanitize_text_field( $item['value'] ) : '';
		if ( '' === $label && '' === $value ) {
			continue;
		}
		$out .= '<span class="bang-meta-item"><span class="bang-meta-label">' . esc_html( $label ) . '</span>'
			. '<span class="bang-meta-value">' . esc_html( $value ) . '</span></span>';
	}

	if ( ! $out ) {
		return '';
	}
	return '<div ' . get_block_wrapper_attributes( array( 'class' => 'bang-meta' ) ) . '>' . $out . '</div>';
}

/* ============================================================
   简码：与四个早期区块一一对应，区块 / 简码双功能
   ============================================================ */
/**
 * [quote source="出处"]引文[/quote] —— 对应 bang/quote。
 *
 * @param array  $atts    属性。
 * @param string $content 引文。
 * @return string
 */
function bang_sc_quote( $atts, $content = '' ) {
	$atts = shortcode_atts( array( 'source' => '' ), (array) $atts, 'bang_quote' );
	/* wpautop 会先把包裹内容变成 <p>，这里还原成纯文本，避免块内嵌套 p */
	$text = trim( preg_replace( '#</?p[^>]*>|<br\s*/?>#i', '', (string) $content ) );
	return bang_render_quote_block(
		array(
			'text'   => $text,
			'source' => $atts['source'],
		)
	);
}
add_shortcode( 'quote', 'bang_sc_quote' );

/**
 * [linkcard url="https://…" title="标题" description="说明"] —— 对应 bang/linkcard。
 * 不写 title 时用包裹内容兜底，粘旧文案也能出卡片。
 *
 * @param array  $atts    属性。
 * @param string $content 兜底标题。
 * @return string
 */
function bang_sc_linkcard( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'url'         => '',
			'title'       => '',
			'description' => '',
			'desc'        => '',
		),
		(array) $atts,
		'bang_linkcard'
	);
	$title = '' !== trim( (string) $atts['title'] ) ? $atts['title'] : trim( wp_strip_all_tags( (string) $content ) );
	$desc  = '' !== trim( (string) $atts['description'] ) ? $atts['description'] : $atts['desc'];
	return bang_render_linkcard_block(
		array(
			'url'         => $atts['url'],
			'title'       => $title,
			'description' => $desc,
		)
	);
}
add_shortcode( 'linkcard', 'bang_sc_linkcard' );

/**
 * [meta items="地点=山脚;天气=多云"] —— 对应 bang/meta。
 * items 用「;」分隔条目、「=」分隔名称与内容；也兼容 [meta 地点="山脚"] 这类具名写法。
 *
 * @param array  $atts    属性。
 * @param string $content 未使用。
 * @return string
 */
function bang_sc_meta( $atts, $content = '' ) {
	$atts  = (array) $atts;
	$items = array();

	if ( isset( $atts['items'] ) && '' !== trim( (string) $atts['items'] ) ) {
		foreach ( preg_split( '/[;；]/u', (string) $atts['items'] ) as $pair ) {
			$pair = trim( $pair );
			if ( '' === $pair ) {
				continue;
			}
			$parts = preg_split( '/\s*[=:：]\s*/u', $pair, 2 );
			$items[] = array(
				'label' => isset( $parts[0] ) ? $parts[0] : '',
				'value' => isset( $parts[1] ) ? $parts[1] : ( isset( $parts[0] ) ? $parts[0] : '' ),
			);
		}
		unset( $atts['items'] );
	}

	foreach ( $atts as $label => $value ) {
		$items[] = array( 'label' => (string) $label, 'value' => (string) $value );
	}

	return bang_render_meta_block( array( 'items' => $items ) );
}
add_shortcode( 'meta', 'bang_sc_meta' );

/**
 * [gallery ids="12,13" layout="grid" caption="说明"] —— 对应 bang/gallery。
 * 不写 ids 时从包裹内容逐行取图片地址（写法与 [wall] 一致），一行一张，
 * 支持「地址 | 说明」；ids 优先。
 *
 * @param array  $atts    属性。
 * @param string $content 图片地址列表。
 * @return string
 */
function bang_sc_gallery( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'ids'     => '',
			'layout'  => 'stack',
			'caption' => '',
		),
		(array) $atts,
		'bang_gallery'
	);

	$images = array();
	$ids    = array_filter( array_map( 'absint', explode( ',', (string) $atts['ids'] ) ) );
	foreach ( $ids as $id ) {
		$images[] = array(
			'id'  => $id,
			'url' => '',
			'alt' => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
		);
	}

	if ( ! $images ) {
		$content = preg_replace( '#<br\s*/?>#i', "\n", (string) $content );
		foreach ( preg_split( '/\r\n|\r|\n/', $content ) as $line ) {
			$line = trim( wp_strip_all_tags( $line ) );
			if ( '' === $line ) {
				continue;
			}
			$parts = array_map( 'trim', preg_split( '/\s*[|｜]\s*/u', $line ) );
			if ( ! preg_match( '#^(https?://|/)#i', $parts[0] ) ) {
				continue;
			}
			$images[] = array(
				'id'  => 0,
				'url' => $parts[0],
				'alt' => isset( $parts[1] ) ? $parts[1] : '',
			);
		}
	}

	return bang_render_gallery_block(
		array(
			'images'  => $images,
			'layout'  => $atts['layout'],
			'caption' => $atts['caption'],
		)
	);
}
add_shortcode( 'gallery', 'bang_sc_gallery' );
