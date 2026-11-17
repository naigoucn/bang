<?php
/**
 * 「说说」归档页：响应式卡片流
 *
 * @package Naigou
 */
get_header();
?>
<div id="swup-wrapper">
<main id="main" class="wrap say-page">
	<header class="say-head">
		<h1 class="say-title">说说</h1>
		<p class="say-desc">随手记下的短句与片段。</p>
	</header>
	<?php if ( have_posts() ) : ?>
	<?php /* 0.13.25：卡片流，每条说说带作者头像 / 昵称 / 时间，结构统一由 pk_say_card_html() 输出 */ ?>
	<ol class="say-list">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<li class="say-item"><?php echo pk_say_card_html( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 内部字段均已转义 ?></li>
		<?php endwhile; ?>
	</ol>
	<?php else : ?>
	<p class="say-empty">还没有说说，去后台写一条吧。</p>
	<?php endif; ?>
</main>
</div>
<?php get_footer(); ?>
