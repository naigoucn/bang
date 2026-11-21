<?php
/**
 * 联系（页面 slug: contact）
 *
 * 邮箱取自设置里的「联系邮箱」，没填就用管理员邮箱；
 * 社交渠道来自后台「联系方式与社交」配置，只输出填了完整网址的；
 * 页面正文可用来补一段自我介绍，留空则只显示联系卡片。
 *
 * @package Naigou
 */

get_header();

$bang_mail  = bang_contact_email();
$bang_links = bang_site_page_url( 'links' );

/* 社交渠道：后台 social_* 填了完整网址才显示（图标文件名直读 assets/icons/） */
$bang_social_defs = array(
	'qq'       => array( 'qq', 'QQ' ),
	'wechat'   => array( 'wechat', '微信' ),
	'weibo'    => array( 'weibo', '微博' ),
	'douyin'   => array( 'douyin', '抖音' ),
	'github'   => array( 'github', 'GitHub' ),
	'x'        => array( 'x-social', 'X' ),
	'bilibili' => array( 'bilibili', '哔哩哔哩' ),
);
$bang_socials = array();
foreach ( $bang_social_defs as $bang_key => $bang_def ) {
	$bang_url = trim( (string) bang_opt( 'social_' . $bang_key ) );
	if ( '' !== $bang_url && preg_match( '#^https?://#i', $bang_url ) ) {
		$bang_socials[] = array( $bang_def[0], $bang_def[1], $bang_url );
	}
}

bang_page_open( '联系', '写信、纠错、约稿、换友链，都从这里开始。' );
?>
	<div class="bang-contact">
		<div class="bang-contact-hero">
			<span class="bang-contact-hero-ico"><?php echo bang_icon( 'mail', 24 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<div class="bang-contact-hero-main">
				<span class="bang-contact-label">电子邮件</span>
				<a class="bang-contact-mail" href="mailto:<?php echo esc_attr( antispambot( $bang_mail ) ); ?>"><?php echo esc_html( $bang_mail ); ?></a>
				<span class="bang-contact-hint">写信、纠错、约稿、换友链，都欢迎来信。</span>
			</div>
			<a class="bang-page-btn bang-contact-hero-btn" href="mailto:<?php echo esc_attr( antispambot( $bang_mail ) ); ?>">写封信<?php echo bang_icon( 'external-link', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		</div>

		<div class="bang-contact-grid">
			<div class="bang-contact-card">
				<span class="bang-contact-card-ico"><?php echo bang_icon( 'link', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<h2 class="bang-contact-card-t">友情链接</h2>
				<p class="bang-contact-card-d">站点互链、资源互换，欢迎提交申请。</p>
				<a class="bang-contact-card-link" href="<?php echo esc_url( $bang_links ); ?>">去友链页提交<?php echo bang_icon( 'next', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			</div>
			<div class="bang-contact-card">
				<span class="bang-contact-card-ico"><?php echo bang_icon( 'message-circle', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<h2 class="bang-contact-card-t">文章讨论</h2>
				<p class="bang-contact-card-d">任意文章下方都能评论，登录后还能收到回复。</p>
			</div>
			<div class="bang-contact-card">
				<span class="bang-contact-card-ico"><?php echo bang_icon( 'bell', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<h2 class="bang-contact-card-t">响应时间</h2>
				<p class="bang-contact-card-d">通常一两天内回信，慢的话再催一次。</p>
			</div>
		</div>

		<?php if ( $bang_socials ) : ?>
			<section class="bang-contact-social-sec">
				<h2 class="bang-contact-sec-t">在其他地方找到我</h2>
				<div class="bang-contact-socials">
					<?php foreach ( $bang_socials as $bang_s ) : ?>
						<a class="bang-contact-social" href="<?php echo esc_url( $bang_s[2] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo bang_icon( $bang_s[0], 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span><?php echo esc_html( $bang_s[1] ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

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
	</div>
<?php
bang_page_close();
get_footer();
