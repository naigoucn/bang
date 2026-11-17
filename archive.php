<?php
/**
 * 分类 / 标签 / 日期归档
 *
 * @package Naigou
 */
get_header();
?>
<div id="swup-wrapper">
<main class="wrap" id="main">
	<div class="feed-head">
		<h1><?php the_archive_title(); ?></h1>
		<span class="hint">共 <?php echo esc_html( (int) $wp_query->found_posts ); ?> 篇</span>
	</div>
	<div class="feed">
		<?php
		if ( have_posts() ) :
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/card' );
			endwhile;
		else :
			echo '<p>这个分类下还没有内容。</p>';
		endif;
		?>
	</div>
	<div class="load-more-wrap">
		<?php
		the_posts_pagination(
			array(
				'prev_text' => '上一页',
				'next_text' => '下一页',
			)
		);
		?>
	</div>
</main>
</div>
<?php get_footer(); ?>
