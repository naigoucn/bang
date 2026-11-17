<?php
/**
 * 信息流卡片：图片 / 标题 / 分类 / 时间，点击整卡展开文章
 *
 * @package Naigou
 */
$pk_id        = get_the_ID();
$pk_terms     = implode( ' ', pk_topic_slugs( $pk_id ) );
// 搜索用：标题 + 摘要 + 分类的小写串，前端 applyFilter 直接读 data-search，不再依赖内联 payload
$pk_search    = mb_strtolower( get_the_title( $pk_id ) . ' ' . pk_plain_excerpt( $pk_id ) . ' ' . pk_topic( $pk_id ), 'UTF-8' );
$pk_format    = pk_format_key( $pk_id );
// 分类归档页由 front-page.php 传入当前分类，服务端直接隐藏其它分类的卡片，避免刷新时闪一下
$pk_initial   = isset( $args['pk_initial_term'] ) ? $args['pk_initial_term'] : 'all';
$pk_hidden_cl = ( 'all' !== $pk_initial && ! in_array( $pk_initial, pk_topic_slugs( $pk_id ), true ) ) ? ' is-hidden' : '';
?>
<a class="card<?php echo esc_attr( $pk_hidden_cl ); ?>" href="<?php the_permalink(); ?>"
	data-id="<?php echo esc_attr( $pk_id ); ?>"
	data-terms="<?php echo esc_attr( $pk_terms ); ?>"
	data-search="<?php echo esc_attr( $pk_search ); ?>" data-format="<?php echo esc_attr( $pk_format ); ?>">
	<?php echo pk_visual( $pk_id, false ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<h2 class="card-title"><?php echo esc_html( get_the_title() ); ?></h2>
	<div class="card-meta">
		<span class="card-topic"><?php echo esc_html( pk_topic( $pk_id ) ); ?></span>
		<span class="dot" aria-hidden="true"></span>
		<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></time>
	</div>
</a>
