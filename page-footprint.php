<?php
/**
 * 足迹页（页面 slug: footprint）
 *
 * 展示去过的地方：单色极简高德地图（白底浅灰轮廓 + 全屏查看）。
 * 地图点第一次点击显示地点与关联文章，再次点击用 Swup 展开文章。
 * 足迹内容在后台「足迹」菜单添加；地图坐标取点后填入即可。
 * 数据以 JSON 内嵌，首屏无需额外请求；刷新时走 bang/v1/footprints。
 *
 * @package Bang
 */

get_header();

$bang_fp_on    = (int) bang_opt( 'footprint_on' );
$bang_fp_rows  = function_exists( 'bang_fp_query' ) ? bang_fp_query() : array();
$bang_fp_stats = function_exists( 'bang_fp_stats' ) ? bang_fp_stats() : array(
	'footprints' => 0,
	'provinces'  => 0,
	'cities'     => 0,
);

/* 内嵌给前端地图用：只带必要字段 */
$bang_fp_json = array();
foreach ( $bang_fp_rows as $bang_r ) {
	$bang_fp_json[] = array(
		'id'      => $bang_r['id'],
		'name'    => $bang_r['name'],
		'desc'    => $bang_r['desc'],
		'city'    => $bang_r['city'],
		'province'=> $bang_r['province'],
		'address' => $bang_r['address'],
		'type'    => $bang_r['type'],
		'visited' => $bang_r['visited'],
		'lng'     => $bang_r['lng'],
		'lat'     => $bang_r['lat'],
		'cover'   => $bang_r['cover'],
		'article'       => $bang_r['article'],
		'article_title' => $bang_r['article_title'],
		'article_cover' => $bang_r['article_cover'],
		'article_date'  => $bang_r['article_date'],
		'gallery_count' => count( $bang_r['gallery'] ),
	);
}
$bang_fp_embed = wp_json_encode( $bang_fp_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
/* 防 </script> 逃逸：转义尖括号与 & */
$bang_fp_embed = str_replace( array( '<', '>', '&' ), array( '\u003C', '\u003E', '\u0026' ), (string) $bang_fp_embed );

bang_page_open(
	'足迹',
	$bang_fp_stats['footprints'] ? sprintf( '%d 处足迹 · %d 个省份 · %d 座城市', $bang_fp_stats['footprints'], $bang_fp_stats['provinces'], $bang_fp_stats['cities'] ) : '走过的地方，会在这里留下坐标',
	'bang-footprint-page'
);
?>

<?php if ( ! $bang_fp_on ) : ?>
	<p class="bang-page-empty"><?php esc_html_e( '足迹地图未开启，可在后台「Bang 设置 → 足迹地图」打开。', 'bang' ); ?></p>
<?php elseif ( ! $bang_fp_rows ) : ?>
	<p class="bang-page-empty"><?php esc_html_e( '还没有足迹，去后台「足迹」添加第一处吧。', 'bang' ); ?></p>
<?php else : ?>
	<div class="bang-fp-map-wrap" data-bang-fp-map>
		<div class="bang-fp-panel bang-fp-panel--2d is-on" data-bang-fp-panel="2d">
			<div class="bang-fp-amap" id="bang-fp-amap"></div>
			<p class="bang-fp-map-tip" data-bang-fp-tip hidden></p>
		</div>
		<div class="bang-fp-dock" role="toolbar" aria-label="<?php esc_attr_e( '地图工具', 'bang' ); ?>">
			<button class="bang-fp-dock-btn" type="button" data-bang-fp-fit><?php echo bang_icon( 'map-pin', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( '全部足迹', 'bang' ); ?></span></button>
			<button class="bang-fp-dock-btn" type="button" data-bang-fp-full aria-label="<?php esc_attr_e( '全屏查看地图', 'bang' ); ?>" title="<?php esc_attr_e( '全屏查看地图', 'bang' ); ?>">
				<span class="bang-fp-full-in"><?php echo bang_icon( 'maximize', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="bang-fp-full-out"><?php echo bang_icon( 'minimize', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="bang-fp-full-label"><?php esc_html_e( '全屏', 'bang' ); ?></span>
			</button>
		</div>
	</div>

	<script type="application/json" id="bang-fp-data"><?php echo $bang_fp_embed; // phpcs:ignore WordPress.Security.EscapeOutput -- 已做 </script> 转义 ?></script>
<?php endif; ?>

<?php
bang_page_close();
get_footer();
