<?php
/**
 * 首页 / 分类归档：卡片时间线 + 全屏阅读层
 *
 * 分类归档（plain permalink 下为 ?cat=ID）复用本模板，见 functions.php 的
 * bang_category_template()。这样分类地址可分享、刷新后仍保持筛选态与阅读层。
 *
 * @package Naigou
 */
get_header();

/* 全站文章：分类页单独做成了信息页（/categories/），
   这里分类/标签归档直接由服务端按当前 term 过滤，不再靠前端 tab 筛选 */
/* 直接访问 / 刷新归档链接时的服务端初始态，避免先闪一下全部卡片 */
$bang_queried  = is_category() || is_tag() ? get_queried_object() : null;
$bang_has_term = $bang_queried && ! is_wp_error( $bang_queried );

$bang_args = array(
	'numberposts'      => 30,
	'post_status'      => 'publish',
	'orderby'          => 'date',
	'order'            => 'DESC',
	'suppress_filters' => true,
);
if ( $bang_has_term ) {
	if ( is_tag() ) {
		$bang_args['tag_id'] = (int) $bang_queried->term_id;
	} elseif ( is_category() ) {
		$bang_args['cat'] = (int) $bang_queried->term_id;
	}
}
$bang_posts = get_posts( $bang_args );

$bang_home    = home_url( '/' );
?>
<div id="swup-wrapper">
<main class="wrap" id="main">
	<?php
	/* PC 端幻灯片只在首页出现：分类归档保持纯卡片流，避免「筛选后幻灯片还是全部文章」 */
	if ( ! $bang_has_term ) {
		echo bang_slides_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- 内部字段均已转义
		echo bang_ticker_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- 内部字段均已转义
	}
	?>

	<div class="feed" id="feed">
		<?php
		foreach ( $bang_posts as $post ) {
			setup_postdata( $post );
			get_template_part( 'template-parts/card' );
		}
		wp_reset_postdata();
		?>
	</div>
	<div class="no-result" id="no-result">没有找到相关内容，换个词试试。</div>

	<?php
	/* 订阅卡片：只在首页底部，分类归档不出现（筛选态下订阅入口没有意义） */
	if ( ! $bang_has_term ) {
		echo bang_subscribe_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- 内部字段均已转义
	}
	?>
</main>

<div class="overlay" id="overlay" hidden>
	<article class="detail" id="detail" role="dialog" aria-modal="true" aria-labelledby="d-title" tabindex="-1">
		<div class="detail-inner" id="detail-inner"></div>
	</article>
</div>
</div>

<?php get_footer(); ?>
