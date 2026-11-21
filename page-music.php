<?php
/**
 * 音乐单页（页面 slug: music）
 *
 * 播放器本体（.bang-music-stage）常驻 footer、在 #swup-wrapper 外，换页不重建；
 * 本模板只提供一个「正在播放」页面壳，由 app.js 的 music.isSoloPage() 检测 body
 * 上的 .bang-music-solo 标记后自动展开那份常驻播放器，避免复制 DOM 造成双实例。
 *
 * @package Bang
 */

get_header();
?>
<div id="swup-wrapper">
	<main id="main" class="wrap bang-music-solo-page">
		<header class="bang-page-head">
			<h1 class="bang-page-title"><?php echo esc_html__( '音乐', 'bang' ); ?></h1>
			<p class="bang-page-sub"><?php echo esc_html__( '正在播放 · 戴上耳机，慢慢听', 'bang' ); ?></p>
		</header>
		<p class="bang-page-empty"><?php echo esc_html__( '播放器已在下方展开，用底部控制栏切歌、看歌词。', 'bang' ); ?></p>
	</main>
</div>
<?php
get_footer();
