<?php
/**
 * 信息流卡片：图片 / 标题 / 分类 / 时间，点击整卡展开文章
 *
 * @package Naigou
 */
$bang_id        = get_the_ID();
$bang_terms     = implode( ' ', bang_topic_slugs( $bang_id ) );
// 搜索用：标题 + 摘要 + 分类的小写串，前端 applyFilter 直接读 data-search，不再依赖内联 payload
$bang_search    = mb_strtolower( get_the_title( $bang_id ) . ' ' . bang_plain_excerpt( $bang_id ) . ' ' . bang_topic( $bang_id ), 'UTF-8' );
$bang_format    = bang_format_key( $bang_id );
// 分类归档页由 front-page.php 传入当前分类，服务端直接隐藏其它分类的卡片，避免刷新时闪一下
$bang_initial   = isset( $args['bang_initial_term'] ) ? $args['bang_initial_term'] : 'all';
$bang_hidden_cl = ( 'all' !== $bang_initial && ! in_array( $bang_initial, bang_topic_slugs( $bang_id ), true ) ) ? ' is-hidden' : '';
?>
<a class="card<?php echo esc_attr( $bang_hidden_cl ); ?>" href="<?php the_permalink(); ?>"
	data-id="<?php echo esc_attr( $bang_id ); ?>"
	data-terms="<?php echo esc_attr( $bang_terms ); ?>"
	data-search="<?php echo esc_attr( $bang_search ); ?>" data-format="<?php echo esc_attr( $bang_format ); ?>">
	<?php echo bang_visual( $bang_id, false ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<h2 class="card-title"><?php echo esc_html( get_the_title() ); ?></h2>
	<div class="card-meta">
		<span class="card-topic"><?php echo esc_html( bang_topic( $bang_id ) ); ?></span>
		<span class="dot" aria-hidden="true"></span>
		<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></time>
	</div>
</a>
