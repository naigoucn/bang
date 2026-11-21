<?php
/**
 * 搜索页（?s=关键词）
 *
 * 顶栏内联搜索框回车、搜索结果下拉里的「查看全部结果」都落到这里，
 * 展示该关键词的全部命中（卡片流 + 分页），并可原地改词重搜。
 *
 * @package Naigou
 */
get_header();

global $wp_query;
$bang_q     = get_search_query();
$bang_total = (int) $wp_query->found_posts;
?>
<div id="swup-wrapper">
<main class="wrap" id="main">
	<div class="feed-head">
		<h1>搜索</h1>
		<span class="hint">共 <?php echo esc_html( $bang_total ); ?> 篇</span>
	</div>

	<?php if ( '' !== $bang_q ) : ?>
		<p class="search-page-q">关键词「<strong><?php echo esc_html( $bang_q ); ?></strong>」</p>
	<?php endif; ?>

	<form class="search-page-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" autocomplete="off">
		<span class="search-page-ico" aria-hidden="true"><?php echo bang_icon( 'search', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<input type="search" name="s" value="<?php echo esc_attr( $bang_q ); ?>" placeholder="搜索标题、正文或分类" aria-label="搜索文章" autocomplete="off">
		<button class="link-btn" type="submit">搜索</button>
	</form>

	<div class="feed">
		<?php
		if ( have_posts() ) :
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/card' );
			endwhile;
		else :
			?>
			<div class="empty-state search-page-empty">
				<p>
					<?php
					echo '' !== $bang_q
						? '没有找到「' . esc_html( $bang_q ) . '」相关的内容。'
						: '输入关键词，找一篇想看的东西。';
					?>
				</p>
				<?php
				$bang_tips = get_categories( array( 'hide_empty' => true, 'number' => 6, 'orderby' => 'count', 'order' => 'DESC' ) );
				if ( $bang_tips ) :
					?>
					<p class="search-page-tips-label">换个分类看看</p>
					<div class="pc-tags search-page-tips">
						<?php foreach ( $bang_tips as $bang_tip ) : ?>
							<a class="pc-tag" href="<?php echo esc_url( get_category_link( $bang_tip->term_id ) ); ?>"><?php echo esc_html( $bang_tip->name ); ?></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
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
