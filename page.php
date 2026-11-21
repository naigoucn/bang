<?php
/**
 * 独立页面
 *
 * @package Naigou
 */
get_header();
?>
<div id="swup-wrapper">
<div class="overlay single-view">
	<article class="detail" id="detail" role="article">
		<div class="detail-inner">
			<?php
			while ( have_posts() ) :
				the_post();
				echo bang_reading_html( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput
			endwhile;
			?>
		</div>
	</article>
</div>
</div>
<?php get_footer(); ?>
