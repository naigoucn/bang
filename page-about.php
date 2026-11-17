<?php
/**
 * 关于（页面 slug: about）
 *
 * 正文取自页面内容（后台可改），下方补一行站点统计。
 *
 * @package Naigou
 */

get_header();

$pk_stats = pk_site_stats();
$pk_sub   = trim( (string) get_bloginfo( 'description' ) );

/* 第三参给外壳加 scoped 类：关于页统一到 /say/ 卡片流版式，不动通用 720px 外壳 */
pk_page_open( get_the_title( get_queried_object_id() ), $pk_sub, 'pk-about-page' );
?>
	<?php
	while ( have_posts() ) :
		the_post();
		$pk_body = trim( (string) get_the_content() );
		if ( '' !== $pk_body ) :
			?>
			<div class="prose pk-page-body"><?php the_content(); ?></div>
			<?php
		endif;
	endwhile;
	?>

	<ul class="pk-facts">
		<li><strong><?php echo esc_html( (int) $pk_stats['posts'] ); ?></strong><span>篇文章</span></li>
		<li><strong><?php echo esc_html( (int) $pk_stats['cats'] ); ?></strong><span>个分类</span></li>
		<li><strong><?php echo esc_html( (int) $pk_stats['tags'] ); ?></strong><span>个标签</span></li>
		<li><strong><?php echo esc_html( (int) $pk_stats['comments'] ); ?></strong><span>条评论</span></li>
	</ul>

	<p class="pk-page-note">
		想聊点什么，去 <a href="<?php echo esc_url( pk_site_page_url( 'contact' ) ); ?>">联系</a> 页找我。
	</p>
<?php
pk_page_close();
get_footer();
