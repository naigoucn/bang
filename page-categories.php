<?php
/**
 * 分类页（页面 slug: categories）
 *
 * 全部分类一张网格卡片：封面取该分类最新文章的首图（无图用主题插画），
 * 加分类名 / 文章数 / 描述；点进卡片即到该分类的归档流。
 *
 * @package Naigou
 */

get_header();

$bang_cats = get_categories( array( 'hide_empty' => true, 'orderby' => 'term_id', 'order' => 'ASC' ) );
$bang_stats = bang_site_stats();

bang_page_open(
	'分类',
	$bang_stats['cats'] ? sprintf( '%d 个分类 · %d 篇文章', $bang_stats['cats'], $bang_stats['posts'] ) : '还没有发布文章',
	'bang-cats-page'
);
?>
<?php if ( $bang_cats ) : ?>
	<div class="bang-cats-grid">
		<?php foreach ( $bang_cats as $bang_cat ) :
			$bang_latest = get_posts(
				array(
					'cat'           => $bang_cat->term_id,
					'post_status'   => 'publish',
					'numberposts'   => 1,
					'no_found_rows' => true,
				)
			);
			$bang_latest_id = $bang_latest ? $bang_latest[0]->ID : 0;
			$bang_img_ids   = $bang_latest_id ? bang_post_images( $bang_latest_id ) : array();
			?>
			<a class="bang-cat-card" href="<?php echo esc_url( get_category_link( $bang_cat->term_id ) ); ?>">
				<span class="bang-cat-visual<?php echo ( ! $bang_img_ids && ! $bang_latest_id ) ? ' is-empty' : ''; ?>" aria-hidden="true">
					<?php
					if ( $bang_img_ids ) {
						echo bang_image_html( $bang_img_ids[0], 'medium', $bang_latest_id ); // phpcs:ignore WordPress.Security.EscapeOutput
					} elseif ( $bang_latest_id ) {
						echo bang_art_for( $bang_latest_id ); // phpcs:ignore WordPress.Security.EscapeOutput
					} else {
						echo esc_html( function_exists( 'mb_substr' ) ? mb_substr( $bang_cat->name, 0, 1 ) : substr( $bang_cat->name, 0, 1 ) );
					}
					?>
				</span>
				<span class="bang-cat-main">
					<span class="bang-cat-name"><?php echo esc_html( $bang_cat->name ); ?></span>
					<span class="bang-cat-count"><?php echo (int) $bang_cat->count; ?> 篇</span>
					<?php if ( $bang_cat->description ) : ?>
						<span class="bang-cat-desc"><?php echo esc_html( wp_trim_words( $bang_cat->description, 24 ) ); ?></span>
					<?php endif; ?>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
<?php else : ?>
	<p class="bang-page-empty">这里还空着，写点什么就会出现。</p>
<?php endif; ?>
<?php
bang_page_close();
get_footer();
