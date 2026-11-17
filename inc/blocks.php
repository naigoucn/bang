<?php
/**
 * Bang · Gutenberg 区块
 *
 * 四个区块都是动态区块（编辑器里 save 返回 null），前台由 render_callback
 * 输出纯 HTML。这样首页把阅读层 HTML 内联进 #pk-data 时不需要执行脚本，
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
function pk_block_categories( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'pianke-blocks',
			'title' => __( 'Bang 区块', 'pianke' ),
		)
	);
	return $categories;
}
add_filter( 'block_categories_all', 'pk_block_categories' );

/**
 * 注册区块。block.json 由 PHP 读取，编辑器脚本统一挂一份 build/index.js，
 * 避免四个 block.json 各自声明 editorScript 导致同一份脚本被加载四次。
 */
function pk_register_blocks() {
	$callbacks = array(
		'gallery'  => 'pk_render_gallery_block',
		'quote'    => 'pk_render_quote_block',
		'linkcard' => 'pk_render_linkcard_block',
		'meta'     => 'pk_render_meta_block',
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
add_action( 'init', 'pk_register_blocks' );

/**
 * 编辑器脚本（wp-scripts 构建产物 + 自动生成的依赖清单）。
 */
function pk_enqueue_block_editor_assets() {
	$asset_file = get_template_directory() . '/build/index.asset.php';
	$asset      = file_exists( $asset_file )
		? require $asset_file
		: array(
			'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			'version'      => PK_VERSION,
		);

	wp_enqueue_script(
		'pianke-blocks-editor',
		get_template_directory_uri() . '/build/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);
	/* 区块在前台 / 编辑器里共用一份预览样式（含 0.13.20 阅读解锁卡片） */
	wp_enqueue_style(
		'pianke-blocks-editor-style',
		get_template_directory_uri() . '/assets/css/blocks.css',
		array(),
		$asset['version']
	);
}
add_action( 'enqueue_block_editor_assets', 'pk_enqueue_block_editor_assets' );

/**
 * 前台区块样式。放在主样式之后，保证覆盖核心区块默认外观。
 */
function pk_enqueue_block_styles() {
	wp_enqueue_style(
		'pianke-blocks',
		get_template_directory_uri() . '/assets/css/blocks.css',
		array( 'pianke-style' ),
		PK_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'pk_enqueue_block_styles', 15 );

/**
 * 正文经过 wp_kses_post()，这里补齐区块输出会用到的标签与属性。
 * 只放宽图片属性与 data、aria 前缀属性，不放行 script / iframe / 自定义元素。
 */
function pk_allow_block_html( $tags, $context ) {
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
add_filter( 'wp_kses_allowed_html', 'pk_allow_block_html', 10, 2 );

/**
 * 单张可点图片：用 <a> 而不是 <button>，wp_kses_post() 才会保留。
 * href 指向原图，无 JS 时也能直接打开；灯箱脚本会 preventDefault 接管。
 */
function pk_gallery_link( $item, $index, $class, $img, $label ) {
	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $item['full'] ) . '"'
		. ' data-pk-lightbox="1" data-index="' . (int) $index . '"'
		. ' aria-label="' . esc_attr( $label ) . '" title="' . esc_attr( $label ) . '">'
		. $img . '</a>';
}

/**
 * 图集：默认「主图 + 缩略条」，可选「拼贴宫格」。全部图片点击后进入灯箱。
 * 旧的 marquee 值兼容映射到 stack，保证此前用区块写的文章不用重新保存。
 */
function pk_render_gallery_block( $attributes ) {
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

	$classes = 'pk-gallery pk-gallery--' . $layout . ' pk-gallery--n' . min( count( $images ), 3 );
	if ( count( $images ) < 2 ) {
		$classes .= ' pk-gallery--single';
	}
	$wrapper = get_block_wrapper_attributes( array( 'class' => $classes ) );
	$open    = '<figure ' . $wrapper . ' data-pk-gallery="1" data-caption="' . esc_attr( $caption ) . '">';

	if ( empty( $images ) ) {
		return $open . '<div class="pk-gallery-empty">' . esc_html__( '还没有选择图片', 'pianke' ) . '</div></figure>';
	}

	$first = $images[0];
	$total = count( $images );

	if ( 'grid' === $layout ) {
		$cells = '';
		foreach ( $images as $index => $item ) {
			$extra  = 0 === $index ? '<span class="pk-gallery-count">' . esc_html( $total ) . ' 张</span>' : '';
			$anchor = pk_gallery_link( $item, $index, 'pk-gallery-cell', $item['thumb'], '查看第 ' . ( $index + 1 ) . ' 张图片' );
			$cells .= str_replace( '</a>', $extra . '</a>', $anchor );
		}
		$html = $open . '<div class="pk-gallery-grid">' . $cells . '</div>';
	} else {
		$main = pk_gallery_link( $first, 0, 'pk-gallery-main', $first['large'], '查看原图' );
		$main = str_replace(
			'</a>',
			'<span class="pk-gallery-zoom" aria-hidden="true"></span>'
			. '<span class="pk-gallery-count">' . esc_html( $total ) . ' 张</span></a>',
			$main
		);
		$thumbs = '';
		foreach ( $images as $index => $item ) {
			$thumbs .= pk_gallery_link(
				$item,
				$index,
				'pk-gallery-thumb' . ( 0 === $index ? ' is-current' : '' ),
				$item['thumb'],
				'查看第 ' . ( $index + 1 ) . ' 张图片'
			);
		}
		$html = $open . '<div class="pk-gallery-stage">' . $main . '</div>'
			. '<div class="pk-gallery-thumbs">' . $thumbs . '</div>';
	}

	if ( $caption ) {
		$html .= '<figcaption>' . esc_html( $caption ) . '</figcaption>';
	}
	return $html . '</figure>';
}

/**
 * 金句卡。
 */
function pk_render_quote_block( $attributes ) {
	$text   = isset( $attributes['text'] ) ? wp_kses_post( $attributes['text'] ) : '';
	$source = isset( $attributes['source'] ) ? sanitize_text_field( $attributes['source'] ) : '';
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'pk-quote' ) );

	$html = '<blockquote ' . $wrapper . '><p class="pk-quote-text">' . $text . '</p>';
	if ( $source ) {
		$html .= '<span class="pk-quote-source">' . esc_html( $source ) . '</span>';
	}
	return $html . '</blockquote>';
}

/**
 * 链接卡：外链一律走 ?pk_go= 中转确认页，新标签打开。
 */
function pk_render_linkcard_block( $attributes ) {
	$url  = isset( $attributes['url'] ) ? pk_safe_comment_site( $attributes['url'] ) : '';
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'pk-linkcard' ) );

	if ( ! $url ) {
		return '<div ' . $wrapper . '><span class="pk-linkcard-host">' . esc_html__( '链接无效或不被允许', 'pianke' ) . '</span></div>';
	}

	$host = wp_parse_url( $url, PHP_URL_HOST );
	$title = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : '';
	$desc  = isset( $attributes['description'] ) ? sanitize_text_field( $attributes['description'] ) : '';
	if ( ! $title ) {
		$title = $host;
	}

	$html  = '<a ' . $wrapper . ' href="' . esc_url( pk_go_url( $url ) ) . '"'
		. ' target="_blank" rel="noopener noreferrer nofollow">';
	$html .= '<span class="pk-linkcard-title">' . esc_html( $title ) . '</span>';
	if ( $desc ) {
		$html .= '<span class="pk-linkcard-desc">' . esc_html( $desc ) . '</span>';
	}
	$html .= '<span class="pk-linkcard-foot"><span class="pk-linkcard-host">' . esc_html( $host ) . '</span>'
		. '<span class="pk-linkcard-arrow" aria-hidden="true"></span></span>';
	return $html . '</a>';
}

/**
 * 元信息条：一组「名称 / 内容」，用圆点分隔。
 */
function pk_render_meta_block( $attributes ) {
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
		$out .= '<span class="pk-meta-item"><span class="pk-meta-label">' . esc_html( $label ) . '</span>'
			. '<span class="pk-meta-value">' . esc_html( $value ) . '</span></span>';
	}

	if ( ! $out ) {
		return '';
	}
	return '<div ' . get_block_wrapper_attributes( array( 'class' => 'pk-meta' ) ) . '>' . $out . '</div>';
}
