<?php
/**
 * 独立文章页：直接访问 / 刷新 / 搜索引擎落地时使用
 * 与首页阅读层共用同一套渲染函数，视觉保持一致
 *
 * @package Naigou
 */
get_header();

/* 上一篇 / 下一篇：手势左右滑翻篇的数据源（必须在 the_post() 之后取，$post 才是当前文章） */
$bang_prev_post = null;
$bang_next_post = null;
$bang_reading   = '';
$bang_post_id   = 0;
while ( have_posts() ) :
	the_post();
	$bang_post_id   = get_the_ID();
	$bang_prev_post = get_previous_post();
	$bang_next_post = get_next_post();
	$bang_reading   = bang_reading_html( $bang_post_id );
endwhile;

$bang_prev = $bang_prev_post instanceof WP_Post ? get_permalink( $bang_prev_post->ID ) : '';
$bang_next = $bang_next_post instanceof WP_Post ? get_permalink( $bang_next_post->ID ) : '';
$bang_pt   = $bang_prev_post instanceof WP_Post ? get_the_title( $bang_prev_post->ID ) : '';
$bang_nt   = $bang_next_post instanceof WP_Post ? get_the_title( $bang_next_post->ID ) : '';
?>
<div id="swup-wrapper">
<div class="overlay single-view" id="overlay">
	<article class="detail" id="detail" role="article">
		<?php /* 阅读进度条：作为 .detail 的后代才能引用它上面命名的 scroll timeline */ ?>
		<?php if ( bang_opt( 'ui_progress_on' ) ) : ?>
		<div class="bang-progress" aria-hidden="true"></div>
		<?php endif; ?>
		<div class="detail-inner">
			<?php echo $bang_reading; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php /* 0.13.24：PC 文章底部按钮翻页，取代桌面左右滑手势 */ ?>
			<?php if ( bang_opt( 'ui_post_nav_on' ) ) : ?>
			<?php echo bang_post_nav_html( $bang_prev, $bang_next, $bang_pt, $bang_nt ); // phpcs:ignore WordPress.Security.EscapeOutput -- 内部字段均已转义 ?>
			<?php endif; ?>
			<?php /* 相关阅读：放在上一篇 / 下一篇下面 */ ?>
			<?php echo bang_related_posts_html( $bang_post_id ); // phpcs:ignore WordPress.Security.EscapeOutput -- 内部字段均已转义 ?>
			<?php /* 文章内页脚：fixed overlay 内部滚动到不了公共页脚，故在正文末尾补一份 */ ?>
			<?php echo bang_article_footer_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- 内部字段均已转义 ?>
		</div>
	</article>
</div>
<?php /* 手势翻篇目标：pjax 换页时随 #swup-wrapper 一起更新，前端只读 data 属性 */ ?>
<div id="bang-post-nav" hidden aria-hidden="true"
	data-prev="<?php echo esc_url( $bang_prev ); ?>" data-next="<?php echo esc_url( $bang_next ); ?>"
	data-prev-title="<?php echo esc_attr( $bang_pt ); ?>" data-next-title="<?php echo esc_attr( $bang_nt ); ?>"></div>
</div>
<?php get_footer(); ?>
