<?php
/**
 * 兜底模板
 *
 * @package Naigou
 */
get_header();
?>
<div id="swup-wrapper">
<main class="wrap" id="main">
	<div class="feed-head"><h1>最近</h1><span class="hint">按时间排列</span></div>
	<div class="feed">
		<?php
		if ( have_posts() ) :
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/card' );
			endwhile;
		else :
			echo '<p>还没有发布任何内容。</p>';
		endif;
		?>
	</div>
</main>
</div>
<?php get_footer(); ?>
