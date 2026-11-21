<?php
/**
 * 用户协议（页面 slug: terms）
 * 正文在后台「页面 → 用户协议」编辑，本模板只负责统一外壳与更新日期。
 *
 * @package Naigou
 */

get_header();

bang_page_open( '用户协议', '使用本站前，请先读完这些约定。', 'bang-legal-page' );
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
		?>
		<p class="bang-legal-updated">最后更新：<?php echo esc_html( get_the_modified_time( 'Y-m-d' ) ); ?></p>
		<?php
	endwhile;
	?>
<?php
bang_page_close();
get_footer();
