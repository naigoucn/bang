<?php
/**
 * 文章归档（页面 slug: archive）
 *
 * 一次取出全部已发布文章，按年份分组；不翻页，方便从头扫到尾。
 *
 * @package Naigou
 */

get_header();

$pk_all = get_posts(
	array(
		'post_type'     => 'post',
		'post_status'   => 'publish',
		'numberposts'   => -1,
		'orderby'       => 'date',
		'order'         => 'DESC',
		'no_found_rows' => true,
	)
);
$pk_years = array();
foreach ( $pk_all as $pk_item ) {
	$pk_years[ get_the_date( 'Y', $pk_item ) ][] = $pk_item;
}
$pk_stats = pk_site_stats();

pk_page_open( '文章归档', $pk_stats['posts'] ? '共 ' . $pk_stats['posts'] . ' 篇 · ' . $pk_stats['since'] . ' 年至今' : '还没有发布文章' );
?>
<?php if ( $pk_years ) : ?>
	<div class="pk-arch">
		<?php foreach ( $pk_years as $pk_year => $pk_list ) : ?>
			<section class="pk-arch-year">
				<h2 class="pk-arch-y">
					<span><?php echo esc_html( $pk_year ); ?></span>
					<span class="pk-arch-count"><?php echo (int) count( $pk_list ); ?> 篇</span>
				</h2>
				<ul class="pk-arch-list">
					<?php foreach ( $pk_list as $pk_post ) : ?>
						<li>
							<a class="pk-arch-item" href="<?php echo esc_url( get_permalink( $pk_post ) ); ?>">
								<span class="pk-arch-date"><?php echo esc_html( get_the_date( 'm.d', $pk_post ) ); ?></span>
								<span class="pk-arch-title"><?php echo esc_html( get_the_title( $pk_post ) ); ?></span>
								<span class="pk-arch-cat"><?php echo esc_html( pk_topic( $pk_post->ID ) ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endforeach; ?>
	</div>
<?php else : ?>
	<p class="pk-page-empty">这里还空着，写点什么就会出现。</p>
<?php endif; ?>
<?php
pk_page_close();
get_footer();
