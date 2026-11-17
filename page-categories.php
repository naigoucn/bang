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

$pk_cats = get_categories( array( 'hide_empty' => true, 'orderby' => 'term_id', 'order' => 'ASC' ) );
$pk_stats = pk_site_stats();

pk_page_open(
	'分类',
	$pk_stats['cats'] ? sprintf( '%d 个分类 · %d 篇文章', $pk_stats['cats'], $pk_stats['posts'] ) : '还没有发布文章',
	'pk-cats-page'
);
?>
<?php if ( $pk_cats ) : ?>
	<div class="pk-cats-grid">
		<?php foreach ( $pk_cats as $pk_cat ) :
			$pk_latest = get_posts(
				array(
					'cat'           => $pk_cat->term_id,
					'post_status'   => 'publish',
					'numberposts'   => 1,
					'no_found_rows' => true,
				)
			);
			$pk_latest_id = $pk_latest ? $pk_latest[0]->ID : 0;
			$pk_img_ids   = $pk_latest_id ? pk_post_images( $pk_latest_id ) : array();
			?>
			<a class="pk-cat-card" href="<?php echo esc_url( get_category_link( $pk_cat->term_id ) ); ?>">
				<span class="pk-cat-visual<?php echo ( ! $pk_img_ids && ! $pk_latest_id ) ? ' is-empty' : ''; ?>" aria-hidden="true">
					<?php
					if ( $pk_img_ids ) {
						echo pk_image_html( $pk_img_ids[0], 'medium', $pk_latest_id ); // phpcs:ignore WordPress.Security.EscapeOutput
					} elseif ( $pk_latest_id ) {
						echo pk_art_for( $pk_latest_id ); // phpcs:ignore WordPress.Security.EscapeOutput
					} else {
						echo esc_html( function_exists( 'mb_substr' ) ? mb_substr( $pk_cat->name, 0, 1 ) : substr( $pk_cat->name, 0, 1 ) );
					}
					?>
				</span>
				<span class="pk-cat-main">
					<span class="pk-cat-name"><?php echo esc_html( $pk_cat->name ); ?></span>
					<span class="pk-cat-count"><?php echo (int) $pk_cat->count; ?> 篇</span>
					<?php if ( $pk_cat->description ) : ?>
						<span class="pk-cat-desc"><?php echo esc_html( wp_trim_words( $pk_cat->description, 24 ) ); ?></span>
					<?php endif; ?>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
<?php else : ?>
	<p class="pk-page-empty">这里还空着，写点什么就会出现。</p>
<?php endif; ?>
<?php
pk_page_close();
get_footer();
