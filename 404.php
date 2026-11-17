<?php
/**
 * 404
 *
 * @package Naigou
 */
get_header();
?>
<div id="swup-wrapper">
<main class="wrap" id="main">
	<div class="feed-head"><h1>404</h1></div>
	<div class="empty-state">
		<div class="card-visual quote"><span>「走错了也没关系，回去就好。」</span></div>
		<p>页面不存在或已被移动</p>
		<a class="link-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo pk_icon( 'back', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>返回首页</span></a>
	</div>
</main>
</div>
<?php get_footer(); ?>
