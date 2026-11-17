<?php
/**
 * 隐私政策（页面 slug: privacy）
 * 正文在后台「页面 → 隐私政策」编辑，本模板只负责统一外壳与更新日期。
 *
 * @package Naigou
 */

get_header();

pk_page_open( '隐私政策', '我们如何收集、使用与保护你的信息。', 'pk-legal-page' );
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
		?>
		<p class="pk-legal-updated">最后更新：<?php echo esc_html( get_the_modified_time( 'Y-m-d' ) ); ?></p>
		<?php
	endwhile;
	?>
<?php
pk_page_close();
get_footer();
