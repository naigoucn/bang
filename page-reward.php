<?php
/**
 * 打赏（页面 slug: reward）
 *
 * 两种方式二选一或同时显示：后台填的二维码图片、打赏链接。
 * 页面正文可在后台编辑，用来写一段感谢的话；留空则不显示。
 *
 * @package Bang
 */

get_header();

/* 二维码 / 链接都允许后台填站内相对地址，统一补成完整 URL 再过安全校验 */
$bang_resolve = function ( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	if ( 0 === strpos( $value, '/' ) ) {
		$value = home_url( $value );
	}
	return bang_safe_comment_site( $value );
};

$bang_on    = (int) bang_opt( 'reward_on' );
$bang_qr    = $bang_resolve( bang_opt( 'reward_qr' ) );
$bang_link  = $bang_resolve( bang_opt( 'reward_link' ) );
$bang_title = trim( (string) bang_opt( 'reward_title' ) );
$bang_desc  = trim( (string) bang_opt( 'reward_desc' ) );
$bang_btn   = trim( (string) bang_opt( 'reward_btn_text' ) );

if ( '' === $bang_title ) {
	$bang_title = __( '打赏', 'bang' );
}
if ( '' === $bang_desc ) {
	$bang_desc = __( '如果这篇文章帮到了你，可以扫码或点链接支持作者。', 'bang' );
}
if ( '' === $bang_btn ) {
	$bang_btn = __( '前往打赏', 'bang' );
}

bang_page_open( $bang_title, $bang_desc, 'bang-reward-page' );
?>
	<div class="bang-reward">
		<?php if ( ! $bang_on ) : ?>
			<p class="bang-kit-empty">打赏暂未开启。到「Bang 设置 → 打赏设置」打开开关。</p>
		<?php elseif ( ! $bang_qr && ! $bang_link ) : ?>
			<p class="bang-kit-empty">后台还没有配置打赏方式。到「Bang 设置 → 打赏设置」上传二维码或填写打赏链接。</p>
		<?php else : ?>
			<div class="bang-reward-cards">
				<?php if ( $bang_qr ) : ?>
				<section class="bang-reward-card">
					<h2 class="bang-reward-card-title"><?php esc_html_e( '扫码打赏', 'bang' ); ?></h2>
					<img class="bang-reward-qr" src="<?php echo esc_url( $bang_qr ); ?>" alt="<?php esc_attr_e( '打赏二维码', 'bang' ); ?>" loading="lazy" decoding="async">
					<p class="bang-reward-hint"><?php esc_html_e( '用微信或支付宝扫一扫', 'bang' ); ?></p>
				</section>
				<?php endif; ?>
				<?php if ( $bang_link ) : ?>
				<section class="bang-reward-card">
					<h2 class="bang-reward-card-title"><?php esc_html_e( '链接打赏', 'bang' ); ?></h2>
					<p class="bang-reward-intro"><?php esc_html_e( '不方便扫码？点下面的按钮前往打赏页面。', 'bang' ); ?></p>
					<a class="bang-page-btn" href="<?php echo esc_url( $bang_link ); ?>" target="_blank" rel="noopener noreferrer nofollow"><?php echo esc_html( $bang_btn ); ?><?php echo bang_icon( 'external-link', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				</section>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

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

	<ul class="bang-pairs">
		<li><span class="bang-pairs-k">其它支持</span><span class="bang-pairs-v">评论、点赞与转发，都是很好的鼓励</span></li>
		<li><span class="bang-pairs-k">合作联系</span><a class="bang-pairs-v" href="<?php echo esc_url( bang_site_page_url( 'contact' ) ); ?>">去联系页写信</a></li>
	</ul>
<?php
bang_page_close();
get_footer();
