<?php
/**
 * 单条说说页：内容 + 时间，无评论区、无封面
 *
 * 0.13.27：与 /say/ 归档共用同一套头部与卡片，卡片进入放大态并左对齐，
 * 「返回说说」移到卡片上方与卡片左边缘对齐，避免原来 640px 窄卡居中、大片留白。
 *
 * @package Naigou
 */
get_header();
?>
<div id="swup-wrapper">
<main id="main" class="wrap say-page say-single">
	<header class="say-head">
		<h1 class="say-title">说说</h1>
		<p class="say-desc">随手记下的短句与片段。</p>
	</header>
	<a class="say-back" href="<?php echo esc_url( get_post_type_archive_link( 'bang_say' ) ); ?>"><span>返回说说</span></a>
	<?php
	while ( have_posts() ) :
		the_post();
		?>
	<div class="say-single-inner">
		<?php echo bang_say_card_html( get_the_ID(), 'single' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 内部字段均已转义 ?>
	</div>
	<?php endwhile; ?>
</main>
</div>
<?php get_footer(); ?>
