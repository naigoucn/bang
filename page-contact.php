<?php
/**
 * 联系（页面 slug: contact）
 *
 * 邮箱取自设置里的「联系邮箱」，没填就用管理员邮箱；
 * 页面正文可用来补一段自我介绍，留空则只显示邮箱卡。
 *
 * @package Naigou
 */

get_header();

$pk_mail  = pk_contact_email();
$pk_links = pk_site_page_url( 'links' );

pk_page_open( '联系', '写信、纠错、约稿、换友链，都从这里开始。' );
?>
	<div class="pk-contact">
		<span class="pk-contact-label">电子邮件</span>
		<a class="pk-contact-mail" href="mailto:<?php echo esc_attr( $pk_mail ); ?>"><?php echo esc_html( $pk_mail ); ?></a>
		<a class="pk-page-btn" href="mailto:<?php echo esc_attr( $pk_mail ); ?>">写封信<?php echo pk_icon( 'external-link', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
	</div>

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

	<ul class="pk-pairs">
		<li><span class="pk-pairs-k">友情链接</span><a class="pk-pairs-v" href="<?php echo esc_url( $pk_links ); ?>">去友链页提交申请</a></li>
		<li><span class="pk-pairs-k">文章讨论</span><span class="pk-pairs-v">任意文章下方都能评论，登录后还能收到回复</span></li>
		<li><span class="pk-pairs-k">响应时间</span><span class="pk-pairs-v">通常一两天内回信，慢的话再催一次</span></li>
	</ul>
<?php
pk_page_close();
get_footer();
