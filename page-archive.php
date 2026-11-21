<?php
/**
 * 文章归档（页面 slug: archive）
 *
 * 一次取出全部已发布文章，集中展示：站点统计 / 热门文章榜 / 按时间归档 / 按标签归档。
 * 时间线按「年-月」分组，标签按使用频次排列，不翻页，方便从头扫到尾。
 *
 * @package Naigou
 */

get_header();

$bang_all = get_posts(
	array(
		'post_type'     => 'post',
		'post_status'   => 'publish',
		'numberposts'   => -1,
		'orderby'       => 'date',
		'order'         => 'DESC',
		'no_found_rows' => true,
	)
);
/* 按 YYYY年MM月 分组（同月安静地排在一起） */
$bang_months = array();
foreach ( $bang_all as $bang_item ) {
	$bang_month_key = get_the_date( 'Y年m月', $bang_item );
	$bang_months[ $bang_month_key ][] = $bang_item;
}
$bang_stats     = bang_site_stats();
$bang_total_views = function_exists( 'bang_rank_total_views' ) ? bang_rank_total_views() : 0;

/* 标签归档：按使用次数从多到少，每个标签带文章数 */
$bang_tags = get_tags(
	array(
		'taxonomy'   => 'post_tag',
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
	)
);
if ( is_wp_error( $bang_tags ) ) {
	$bang_tags = array();
}

/* 热门文章榜：与归档同页展示；只保留总开关，条数/范围/排序/权重固定（见 inc/ranking.php） */
$bang_rank_on     = (int) bang_opt( 'ranking_on' );
$bang_rank_rows   = $bang_rank_on ? bang_rank_query() : array();
$bang_rank_label  = __( '综合热度', 'bang' );
$bang_rank_scope  = __( '全部时间', 'bang' );

bang_page_open( '文章归档', $bang_stats['posts'] ? '共 ' . $bang_stats['posts'] . ' 篇 · ' . $bang_stats['since'] . ' 年至今' : '还没有发布文章', 'bang-archive-page' );
?>

<!-- 站点统计 -->
<ul class="bang-facts bang-arch-facts">
	<li><strong><?php echo esc_html( (int) $bang_stats['posts'] ); ?></strong><span><?php esc_html_e( '篇文章', 'bang' ); ?></span></li>
	<li><strong><?php echo esc_html( (int) $bang_total_views ); ?></strong><span><?php esc_html_e( '次阅读', 'bang' ); ?></span></li>
	<li><strong><?php echo esc_html( (int) $bang_stats['cats'] ); ?></strong><span><?php esc_html_e( '个分类', 'bang' ); ?></span></li>
	<li><strong><?php echo esc_html( (int) $bang_stats['tags'] ); ?></strong><span><?php esc_html_e( '个标签', 'bang' ); ?></span></li>
	<li><strong><?php echo esc_html( (int) $bang_stats['comments'] ); ?></strong><span><?php esc_html_e( '条评论', 'bang' ); ?></span></li>
</ul>

<?php if ( ! empty( $bang_rank_rows ) ) : ?>
	<section class="bang-ranking" aria-labelledby="bang-rank-heading">
		<h2 class="bang-rank-heading" id="bang-rank-heading">
			<span class="bang-rank-heading-title"><?php echo bang_icon( 'trending-up', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( '热门文章', 'bang' ); ?></span>
			<span class="bang-rank-heading-sub"><?php echo esc_html( sprintf( __( '按%s排序 · %s', 'bang' ), $bang_rank_label, $bang_rank_scope ) ); ?></span>
		</h2>
		<ol class="bang-rank-feed">
			<?php foreach ( $bang_rank_rows as $bang_i => $bang_row ) :
				$bang_post  = get_post( $bang_row['id'] );
				if ( ! $bang_post ) {
					continue;
				}
				$bang_rank  = $bang_i + 1;
				$bang_medal = $bang_rank <= 3 ? ' is-top' : '';
			?>
			<li class="bang-rank-card">
				<a class="card" href="<?php echo esc_url( get_permalink( $bang_post ) ); ?>"
				   data-id="<?php echo esc_attr( (string) $bang_post->ID ); ?>">
					<span class="bang-rank-visual">
						<?php echo bang_visual( $bang_row['id'], false ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span class="bang-rank-no<?php echo esc_attr( $bang_medal ); ?>" aria-hidden="true"><?php echo esc_html( (string) $bang_rank ); ?></span>
					</span>
					<h2 class="card-title"><?php echo esc_html( get_the_title( $bang_post ) ); ?></h2>
				</a>
			</li>
			<?php endforeach; ?>
		</ol>
	</section>
<?php endif; ?>

<!-- 按时间归档 -->
<?php if ( $bang_months ) : ?>
	<section class="bang-arch-sec" aria-labelledby="bang-arch-heading">
		<h2 class="bang-sec-heading" id="bang-arch-heading">
			<span class="bang-sec-heading-title"><?php echo bang_icon( 'archive', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( '按时间归档', 'bang' ); ?></span>
			<span class="bang-sec-heading-sub"><?php echo esc_html( sprintf( __( '共 %d 个月', 'bang' ), count( $bang_months ) ) ); ?></span>
		</h2>
		<div class="bang-arch-grid">
			<?php foreach ( $bang_months as $bang_month => $bang_list ) : ?>
				<section class="bang-arch-group">
					<h3 class="bang-arch-month"><?php echo esc_html( $bang_month ); ?><span class="bang-arch-count"><?php echo esc_html( (string) count( $bang_list ) ); ?></span></h3>
					<ul class="bang-arch-list">
						<?php foreach ( $bang_list as $bang_post ) : ?>
							<li class="bang-arch-row">
								<a class="bang-arch-item" href="<?php echo esc_url( get_permalink( $bang_post ) ); ?>">
									<span class="bang-arch-day"><?php echo esc_html( get_the_date( 'd', $bang_post ) ); ?></span>
									<span class="bang-arch-title"><?php echo esc_html( get_the_title( $bang_post ) ); ?></span>
									<span class="bang-arch-arrow" aria-hidden="true"><?php echo bang_icon( 'arrow-right', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>

<!-- 按标签归档 -->
<?php if ( ! empty( $bang_tags ) ) : ?>
	<section class="bang-tag-sec" aria-labelledby="bang-tag-heading">
		<h2 class="bang-sec-heading" id="bang-tag-heading">
			<span class="bang-sec-heading-title"><?php echo bang_icon( 'tag', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( '按标签归档', 'bang' ); ?></span>
			<span class="bang-sec-heading-sub"><?php echo esc_html( sprintf( __( '共 %d 个标签', 'bang' ), count( $bang_tags ) ) ); ?></span>
		</h2>
		<div class="bang-tag-cloud">
			<?php foreach ( $bang_tags as $bang_tag ) : ?>
				<a class="bang-tag-chip" href="<?php echo esc_url( get_tag_link( $bang_tag ) ); ?>" title="<?php echo esc_attr( sprintf( __( '查看「%s」下的文章', 'bang' ), $bang_tag->name ) ); ?>">
					<span class="bang-tag-name"><?php echo esc_html( $bang_tag->name ); ?></span>
					<span class="bang-tag-count"><?php echo esc_html( (string) (int) $bang_tag->count ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>

<?php if ( ! $bang_months && empty( $bang_tags ) ) : ?>
	<p class="bang-page-empty">这里还空着，写点什么就会出现。</p>
<?php endif; ?>
<?php
bang_page_close();
get_footer();
