<?php
/**
 * 分类 / 标签 / 日期归档
 *
 * 布局：say-page 居中窄栏 + 顶部分类名/篇数；文章流复用首页 .feed，卡片样式与首页完全一致。
 * 分类页标题取纯分类名（不带「分类：」前缀），其余归档退回默认标题。
 *
 * @package Naigou
 */
get_header();

if ( is_category() ) {
	$bang_archive_title = single_cat_title( '', false );
} else {
	$bang_archive_title = get_the_archive_title();
}
?>
<div id="swup-wrapper">
<main id="main" class="wrap bang-cat-archive">
	<header class="bang-page-head">
		<h1 class="bang-page-title"><?php echo esc_html( $bang_archive_title ); ?></h1>
		<p class="bang-page-sub">共 <?php echo esc_html( (int) $wp_query->found_posts ); ?> 篇文章</p>
	</header>
	<?php if ( have_posts() ) : ?>
	<div class="feed" id="feed">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/card' );
		endwhile;
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
	<?php else : ?>
		<p class="no-result show">这个分类下还没有内容。</p>
	<?php endif; ?>
</main>
</div>
<?php get_footer(); ?>
