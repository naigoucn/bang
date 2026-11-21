<?php
/**
 * 关于（页面 slug: about）
 *
 * 正文取自页面内容（后台可改），下方补主题介绍、功能、技术构成、统计与相关入口。
 *
 * @package Bang
 */

get_header();

$bang_stats = bang_site_stats();
$bang_sub   = trim( (string) get_bloginfo( 'description' ) );

/* 第三参给外壳加 scoped 类：关于页统一到 /say/ 卡片流版式，不动通用 720px 外壳 */
bang_page_open( get_the_title( get_queried_object_id() ), $bang_sub, 'bang-about-page' );
?>
	<?php
	while ( have_posts() ) :
		the_post();
		$bang_body = trim( (string) get_the_content() );
		if ( '' !== $bang_body ) :
			?>
			<div class="prose bang-page-body"><?php the_content(); ?></div>
			<?php
		endif;
	endwhile;
	?>

	<section class="bang-about-sec">
		<h2 class="bang-about-sec-title"><?php esc_html_e( '关于 Bang', 'bang' ); ?></h2>
		<div class="bang-about-prose">
			<p><?php esc_html_e( '这里是 naigou 的个人站点，用来记录 WordPress 主题开发、前端技术文章和日常随笔。Bang 是陪着这个站点一路长起来的主题——从一张卡片开始，慢慢有了说说、足迹、图片集和音乐。', 'bang' ); ?></p>
			<p><?php esc_html_e( '主题永久免费、开源，遵循 GPL v2.0 及以上协议。没有授权码，不绑定域名，也不设 Pro 版。你可以直接用它，也可以拿去做子主题。', 'bang' ); ?></p>
		</div>
	</section>

	<section class="bang-about-sec">
		<h2 class="bang-about-sec-title"><?php esc_html_e( '主题做了什么', 'bang' ); ?></h2>
		<div class="bang-about-features">
			<?php
			$bang_feats = array(
				array(
					'icon'  => 'layers',
					'title' => __( '就地阅读', 'bang' ),
					'desc'  => __( '点开卡片在当前页展开，换页不白屏，读完缩回原位；手机上左右滑动翻篇。', 'bang' ),
				),
				array(
					'icon'  => 'message-circle',
					'title' => __( '说说', 'bang' ),
					'desc'  => __( '短动态支持多图与省市区，地点标签一点就跳到足迹地图定位。', 'bang' ),
				),
				array(
					'icon'  => 'map-pin',
					'title' => __( '足迹', 'bang' ),
					'desc'  => __( '高德单色极简地图，每个标记是一篇文章，点开弹文章卡片。', 'bang' ),
				),
				array(
					'icon'  => 'images',
					'title' => __( '图片集', 'bang' ),
					'desc'  => __( '独立相册文章类型，瀑布流列表、页面内翻页与全站灯箱，位置可跳地图。', 'bang' ),
				),
				array(
					'icon'  => 'music-2',
					'title' => __( '音乐', 'bang' ),
					'desc'  => __( '侧栏迷你播放器，歌词、播放列表与全屏舞台，手机端自动收起。', 'bang' ),
				),
				array(
					'icon'  => 'sparkles',
					'title' => __( '编辑自由', 'bang' ),
					'desc'  => __( 'Gutenberg 区块与短代码双支持，所有设置集中在一个选项页。', 'bang' ),
				),
			);
			foreach ( $bang_feats as $bang_feat ) :
				?>
				<div class="bang-about-feature">
					<span class="bang-about-feature-ico"><?php echo bang_icon( $bang_feat['icon'], 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 由 bang_icon 内部转义 ?></span>
					<h3><?php echo esc_html( $bang_feat['title'] ); ?></h3>
					<p><?php echo esc_html( $bang_feat['desc'] ); ?></p>
				</div>
				<?php
			endforeach;
			?>
		</div>
	</section>

	<ul class="bang-facts">
		<li><strong><?php echo esc_html( (int) $bang_stats['posts'] ); ?></strong><span>篇文章</span></li>
		<li><strong><?php echo esc_html( (int) $bang_stats['cats'] ); ?></strong><span>个分类</span></li>
		<li><strong><?php echo esc_html( (int) $bang_stats['tags'] ); ?></strong><span>个标签</span></li>
		<li><strong><?php echo esc_html( (int) $bang_stats['comments'] ); ?></strong><span>条评论</span></li>
	</ul>

	<section class="bang-about-sec">
		<h2 class="bang-about-sec-title"><?php esc_html_e( '技术构成', 'bang' ); ?></h2>
		<dl class="bang-about-specs">
			<div><dt><?php esc_html_e( '平台', 'bang' ); ?></dt><dd>WordPress 5.x+ · PHP 7.4+</dd></div>
			<div><dt><?php esc_html_e( '前端', 'bang' ); ?></dt><dd>原生 JavaScript + CSS 变量 · Swup 无刷新换页 · PhotoSwipe 灯箱</dd></div>
			<div><dt><?php esc_html_e( '编辑器', 'bang' ); ?></dt><dd>Gutenberg 自定义区块 + 短代码双支持</dd></div>
			<div><dt><?php esc_html_e( '地图', 'bang' ); ?></dt><dd>高德地图 JS API（单色极简底图）</dd></div>
			<div><dt><?php esc_html_e( '协议', 'bang' ); ?></dt><dd>GPL v2.0 及以上 · 永久免费</dd></div>
		</dl>
	</section>

	<section class="bang-about-sec">
		<h2 class="bang-about-sec-title"><?php esc_html_e( '继续逛逛', 'bang' ); ?></h2>
		<div class="bang-about-links">
			<a class="bang-about-link" href="<?php echo esc_url( bang_site_page_url( 'theme' ) ); ?>"><?php esc_html_e( '主题介绍', 'bang' ); ?><?php echo bang_icon( 'arrow-right', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 由 bang_icon 内部转义 ?></a>
			<a class="bang-about-link" href="<?php echo esc_url( bang_site_page_url( 'help' ) ); ?>"><?php esc_html_e( '帮助中心', 'bang' ); ?><?php echo bang_icon( 'arrow-right', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 由 bang_icon 内部转义 ?></a>
			<?php if ( bang_opt( 'footprint_on' ) ) : ?>
			<a class="bang-about-link" href="<?php echo esc_url( bang_site_page_url( 'footprint' ) ); ?>"><?php esc_html_e( '足迹', 'bang' ); ?><?php echo bang_icon( 'arrow-right', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 由 bang_icon 内部转义 ?></a>
			<?php endif; ?>
			<?php if ( bang_opt( 'gallery_on' ) ) : ?>
			<a class="bang-about-link" href="<?php echo esc_url( bang_site_page_url( 'gallery' ) ); ?>"><?php esc_html_e( '图片集', 'bang' ); ?><?php echo bang_icon( 'arrow-right', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 由 bang_icon 内部转义 ?></a>
			<?php endif; ?>
			<a class="bang-about-link" href="https://github.com/naigoucn/bang" target="_blank" rel="noopener noreferrer">GitHub<?php echo bang_icon( 'external-link', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 由 bang_icon 内部转义 ?></a>
		</div>
	</section>

	<p class="bang-page-note">
		想聊点什么，去 <a href="<?php echo esc_url( bang_site_page_url( 'contact' ) ); ?>">联系</a> 页找我。
	</p>
<?php
bang_page_close();
get_footer();
