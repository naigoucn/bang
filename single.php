<?php
/**
 * 独立文章页：直接访问 / 刷新 / 搜索引擎落地时使用
 * 与首页阅读层共用同一套渲染函数，视觉保持一致
 *
 * @package Naigou
 */
get_header();

/* 上一篇 / 下一篇：手势左右滑翻篇的数据源（必须在 the_post() 之后取，$post 才是当前文章） */
$pk_prev_post = null;
$pk_next_post = null;
$pk_reading   = '';
while ( have_posts() ) :
	the_post();
	$pk_prev_post = get_previous_post();
	$pk_next_post = get_next_post();
	$pk_reading   = pk_reading_html( get_the_ID() );
endwhile;

$pk_prev = $pk_prev_post instanceof WP_Post ? get_permalink( $pk_prev_post->ID ) : '';
$pk_next = $pk_next_post instanceof WP_Post ? get_permalink( $pk_next_post->ID ) : '';
$pk_pt   = $pk_prev_post instanceof WP_Post ? get_the_title( $pk_prev_post->ID ) : '';
$pk_nt   = $pk_next_post instanceof WP_Post ? get_the_title( $pk_next_post->ID ) : '';
?>
<div id="swup-wrapper">
<div class="overlay single-view" id="overlay">
	<article class="detail" id="detail" role="article">
		<?php /* 阅读进度条：作为 .detail 的后代才能引用它上面命名的 scroll timeline */ ?>
		<?php if ( pk_opt( 'ui_progress_on' ) ) : ?>
		<div class="pk-progress" aria-hidden="true"></div>
		<?php endif; ?>
		<div class="detail-inner">
			<?php echo $pk_reading; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php /* 0.13.24：PC 文章底部按钮翻页，取代桌面左右滑手势 */ ?>
			<?php if ( pk_opt( 'ui_post_nav_on' ) ) : ?>
			<?php echo pk_post_nav_html( $pk_prev, $pk_next, $pk_pt, $pk_nt ); // phpcs:ignore WordPress.Security.EscapeOutput -- 内部字段均已转义 ?>
			<?php endif; ?>
		</div>
	</article>
</div>
<?php /* 手势翻篇目标：pjax 换页时随 #swup-wrapper 一起更新，前端只读 data 属性 */ ?>
<div id="pk-post-nav" hidden aria-hidden="true"
	data-prev="<?php echo esc_url( $pk_prev ); ?>" data-next="<?php echo esc_url( $pk_next ); ?>"
	data-prev-title="<?php echo esc_attr( $pk_pt ); ?>" data-next-title="<?php echo esc_attr( $pk_nt ); ?>"></div>
</div>
<?php get_footer(); ?>
