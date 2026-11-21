<?php
/**
 * 图片集页（页面 slug: gallery）
 *
 * 列表：所有独立图片集相册的封面瀑布流，点卡片进入单个图集；
 * 单图集（?bang_gal=ID&i=N）：页面内直接展示大图 + 左右翻页 + 底部缩略图，站点侧栏保留。
 *
 * @package Bang
 */

get_header();

$bang_gal_on    = (int) bang_opt( 'gallery_on' );
$bang_gal_base  = function_exists( 'bang_site_page_url' ) ? bang_site_page_url( 'gallery' ) : home_url( '/gallery/' );
$bang_single_id = isset( $_GET['bang_gal'] ) ? absint( $_GET['bang_gal'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 只读单图集 ID
$bang_single    = null;

if ( $bang_single_id && function_exists( 'bang_gallery_items' ) ) {
	foreach ( bang_gallery_items() as $bang_item ) {
		if ( (int) $bang_item['post_id'] === $bang_single_id ) {
			$bang_single = $bang_item;
			break;
		}
	}
}

if ( $bang_single_id && ! $bang_single ) :
	/* 指定图集不存在 / 无图：不回退显示全部 */
	bang_page_open( '图片集', '图集不存在或还没有图片', 'bang-gallery-page' );
	?>
	<p class="bang-page-empty"><?php esc_html_e( '这个图集不存在或还没有图片。', 'bang' ); ?> <a href="<?php echo esc_url( $bang_gal_base ); ?>"><?php esc_html_e( '返回图片集', 'bang' ); ?></a></p>
	<?php
	bang_page_close();
	get_footer();
	return;

elseif ( $bang_single ) :
	/* 单个图集：直接展示大图，无需再点开；站点侧栏照常保留 */
	$bang_imgs  = $bang_single['images'];
	$bang_total = count( $bang_imgs );
	$bang_start = isset( $_GET['i'] ) ? absint( $_GET['i'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 只读起始图序号
	$bang_start = max( 0, min( $bang_total - 1, $bang_start ) );
	$bang_cur   = $bang_imgs[ $bang_start ];
	/* 单图集是 non-public CPT，get_permalink() 不可靠；分享与返回地址显式构造 */
	$bang_gal_permalink = add_query_arg(
		array(
			'bang_gal' => $bang_single['post_id'],
			'i'      => $bang_start,
		),
		$bang_gal_base
	);
	/* 位置点击跳足迹页定位：只带市/省结构化字段，与说说共用 ?bang_loc= 协议 */
	$bang_fp_url = function_exists( 'bang_site_page_url' ) ? bang_site_page_url( 'footprint' ) : home_url( '/footprint/' );
	$bang_fp_key = implode( ' ', array_filter( array(
		trim( (string) get_post_meta( $bang_single['post_id'], 'bang_gal_city', true ) ),
		trim( (string) get_post_meta( $bang_single['post_id'], 'bang_gal_province', true ) ),
	) ) );
	if ( '' === $bang_fp_key ) {
		$bang_fp_key = $bang_single['place'];
	}
	$bang_fp_link = '' !== $bang_fp_key ? add_query_arg( 'bang_loc', rawurlencode( $bang_fp_key ), $bang_fp_url ) : '';
	$bang_bits  = array();
	if ( $bang_single['place'] ) {
		$bang_bits[] = $bang_single['place'];
	}
	if ( $bang_single['time'] ) {
		$bang_bits[] = $bang_single['time'];
	}
	if ( $bang_cur['device'] ) {
		$bang_bits[] = $bang_cur['device'];
	}

	/* 查看器每帧数据：作者信息全组共用，位置 / 日期 / 设备逐图携带 */
	$bang_viewer = array();
	foreach ( $bang_imgs as $bang_img ) {
		$bang_viewer[] = array(
			'full'   => $bang_img['full'],
			'title'  => $bang_img['caption'],
			'place'   => $bang_img['place'],
			'placeUrl' => $bang_fp_link,
			'time'    => $bang_img['time'],
			'device'  => $bang_img['device'],
		);
	}
	$bang_viewer_json = wp_json_encode( $bang_viewer );
	$bang_viewer_json = is_string( $bang_viewer_json ) ? str_replace( '<', '\\u003c', $bang_viewer_json ) : '[]';
	$bang_owner       = function_exists( 'bang_say_owner_identity' ) ? bang_say_owner_identity( 40 ) : array( 'name' => get_bloginfo( 'name' ), 'avatar' => '' );

	bang_page_open( $bang_single['title'], '', 'bang-gallery-page bang-gallery-single-page' );
	?>
	<div class="bang-gal-single">
		<div class="bang-gal-viewer<?php echo ( 1 === $bang_total ) ? ' bang-gal-viewer--single' : ''; ?>" data-bang-gal-viewer data-bang-gallery data-bang-gal-start="<?php echo (int) $bang_start; ?>">
			<div class="bang-gal-viewer-top">
				<div class="bang-gal-viewer-top-info">
					<div class="bang-gal-viewer-author">
						<?php echo $bang_owner['avatar']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 由 bang_say_owner_identity 内部转义 ?>
						<span class="bang-gal-viewer-author-name"><?php echo esc_html( $bang_owner['name'] ); ?></span>
					</div>
					<div class="bang-gal-viewer-meta" data-bang-gal-meta><?php echo esc_html( implode( ' · ', $bang_bits ) ); ?></div>
				</div>
				<div class="bang-gal-viewer-actions">
					<span class="bang-gal-viewer-count" data-bang-gal-count><?php echo (int) ( $bang_start + 1 ) . ' / ' . (int) $bang_total; ?></span>
					<button class="bang-gal-pure-btn" type="button" data-bang-gal-pure aria-label="<?php esc_attr_e( '纯享看图', 'bang' ); ?>" title="<?php esc_attr_e( '纯享看图', 'bang' ); ?>"><?php echo bang_icon( 'maximize', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 由 bang_icon 内部转义 ?></button>
				</div>
			</div>
			<div class="bang-gal-viewer-stage">
				<figure class="bang-gal-viewer-figure">
					<a class="bang-gal-viewer-open" data-bang-gal-open data-no-swup data-index="<?php echo (int) $bang_start; ?>" href="<?php echo esc_url( $bang_cur['full'] ); ?>">
						<img class="bang-gal-viewer-img" data-bang-gal-img src="<?php echo esc_url( $bang_cur['full'] ); ?>" alt="<?php echo esc_attr( $bang_cur['caption'] ); ?>">
					</a>
					<button class="bang-gal-viewer-nav bang-gal-viewer-prev" type="button" data-bang-gal-prev aria-label="<?php esc_attr_e( '上一张', 'bang' ); ?>"><?php echo bang_icon( 'prev', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 由 bang_icon 内部转义 ?></button>
					<button class="bang-gal-viewer-nav bang-gal-viewer-next" type="button" data-bang-gal-next aria-label="<?php esc_attr_e( '下一张', 'bang' ); ?>"><?php echo bang_icon( 'next', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 由 bang_icon 内部转义 ?></button>
					<nav class="bang-gal-filmstrip" aria-label="<?php esc_attr_e( '图集缩略图', 'bang' ); ?>">
						<?php foreach ( $bang_imgs as $bang_i => $bang_img ) : ?>
							<button class="bang-gal-thumb<?php echo $bang_i === $bang_start ? ' is-on' : ''; ?><?php echo $bang_i < $bang_start ? ' is-seen' : ''; ?>" type="button" data-bang-gal-jump="<?php echo (int) $bang_i; ?>" aria-label="<?php echo esc_attr( sprintf( '%d: %s', $bang_i + 1, $bang_img['caption'] ) ); ?>">
								<img src="<?php echo esc_url( $bang_img['thumb'] ); ?>" alt="" loading="lazy" decoding="async">
							</button>
						<?php endforeach; ?>
					</nav>
				</figure>
			</div>
			<script type="application/json" data-bang-gal-data><?php echo $bang_viewer_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 已做 < 转义 ?></script>
		</div>
	</div>
	<?php
	/* 图片推荐：除当前外的其他相册，复用列表瀑布流卡片 */
	$bang_recs = array();
	if ( function_exists( 'bang_gallery_items' ) ) {
		foreach ( bang_gallery_items() as $bang_rec ) {
			if ( (int) $bang_rec['post_id'] === (int) $bang_single['post_id'] ) {
				continue;
			}
			$bang_recs[] = $bang_rec;
			if ( count( $bang_recs ) >= 8 ) {
				break;
			}
		}
	}
	if ( $bang_recs ) :
		?>
		<section class="bang-gal-rec">
			<h2 class="bang-gal-rec-title"><?php esc_html_e( '图片推荐', 'bang' ); ?></h2>
			<div class="bang-gal-grid">
				<?php foreach ( $bang_recs as $bang_card ) : ?>
					<?php
					$bang_rec_imgs  = $bang_card['images'];
					$bang_rec_first = $bang_rec_imgs[0];
					$bang_rec_count = count( $bang_rec_imgs );
					$bang_rec_url   = add_query_arg( 'bang_gal', (int) $bang_card['post_id'], $bang_gal_base );
					?>
					<a class="bang-gal-card" href="<?php echo esc_url( $bang_rec_url ); ?>" aria-label="<?php echo esc_attr( sprintf( '%s · %d 张', $bang_card['title'], $bang_rec_count ) ); ?>">
						<div class="bang-gal-cover">
							<img src="<?php echo esc_url( $bang_rec_first['thumb'] ); ?>" alt="<?php echo esc_attr( $bang_rec_first['caption'] ); ?>" loading="lazy" decoding="async">
						</div>
						<div class="bang-gal-wm">
							<h3 class="bang-gal-wm-title"><?php echo esc_html( $bang_card['title'] ); ?></h3>
							<?php if ( $bang_card['place'] ) : ?><div class="bang-gal-wm-place"><?php echo esc_html( $bang_card['place'] ); ?></div><?php endif; ?>
						</div>
						<span class="bang-gal-badge"><?php echo (int) $bang_rec_count . __( '张', 'bang' ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
	<?php
	/* 评论区复用文章同款右侧抽屉：dock 的「评论」按钮靠 #bang-comments 打开 */
	echo bang_comments_html( $bang_single['post_id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 函数内部逐字段转义

	/* 返回图片集 / 置顶 / 点赞 / 评论 / 分享，与文章同一款 dock；
	   force_back 让 PC 也显示返回，back_url 指向图片集列表而非首页。 */
	bang_page_close(
		bang_dock_html(
			$bang_single['post_id'],
			array(
				'back_url'   => $bang_gal_base,
				'permalink'  => $bang_gal_permalink,
				'force_back' => true,
			)
		)
	);
	get_footer();
	return;

else :
	/* 列表：所有相册的封面 */
	$bang_gal_page = max( 1, (int) ( isset( $_GET['paged'] ) ? $_GET['paged'] : 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 只读分页
	$bang_gal_data = function_exists( 'bang_gallery_paginate' ) ? bang_gallery_paginate( $bang_gal_page ) : array(
		'items'       => array(),
		'total'       => 0,
		'photo_total' => 0,
		'pages'       => 1,
		'page'        => 1,
	);

	bang_page_open(
		'图片集',
		$bang_gal_data['total'] ? sprintf( '%d 张图片 · %d 组相册', $bang_gal_data['photo_total'], $bang_gal_data['total'] ) : '把喜欢的照片收进一面墙',
		'bang-gallery-page'
	);
	?>

	<?php if ( ! $bang_gal_on ) : ?>
		<p class="bang-page-empty"><?php esc_html_e( '图片集未开启，可在后台「Bang 设置 → 图片集」打开。', 'bang' ); ?></p>
	<?php elseif ( ! $bang_gal_data['items'] ) : ?>
		<p class="bang-page-empty"><?php esc_html_e( '还没有图片集。在后台「图片集」新建相册并添加图片，就会出现在这里。', 'bang' ); ?></p>
	<?php else : ?>
		<div class="bang-gal-grid">
			<?php
			foreach ( $bang_gal_data['items'] as $bang_card ) :
				$bang_imgs  = $bang_card['images'];
				$bang_first = $bang_imgs[0];
				$bang_count = count( $bang_imgs );
				$bang_url   = add_query_arg( 'bang_gal', (int) $bang_card['post_id'], $bang_gal_base );
				?>
				<a class="bang-gal-card" href="<?php echo esc_url( $bang_url ); ?>" aria-label="<?php echo esc_attr( sprintf( '%s · %d 张', $bang_card['title'], $bang_count ) ); ?>">
					<div class="bang-gal-cover">
						<img src="<?php echo esc_url( $bang_first['thumb'] ); ?>" alt="<?php echo esc_attr( $bang_first['caption'] ); ?>" loading="lazy" decoding="async">
					</div>
					<div class="bang-gal-wm">
						<h3 class="bang-gal-wm-title"><?php echo esc_html( $bang_card['title'] ); ?></h3>
						<?php if ( $bang_card['place'] ) : ?><div class="bang-gal-wm-place"><?php echo esc_html( $bang_card['place'] ); ?></div><?php endif; ?>
					</div>
					<span class="bang-gal-badge"><?php echo (int) $bang_count . __( '张', 'bang' ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>

		<?php if ( $bang_gal_data['pages'] > 1 ) : ?>
			<nav class="bang-pagination" aria-label="<?php esc_attr_e( '图片集分页', 'bang' ); ?>">
				<?php
				for ( $bang_n = 1; $bang_n <= $bang_gal_data['pages']; $bang_n++ ) :
					$bang_url = 1 === $bang_n ? $bang_gal_base : add_query_arg( 'paged', $bang_n, $bang_gal_base );
					?>
					<a class="bang-page-num<?php echo $bang_n === $bang_gal_data['page'] ? ' is-on' : ''; ?>" href="<?php echo esc_url( $bang_url ); ?>"<?php echo $bang_n === $bang_gal_data['page'] ? ' aria-current="page"' : ''; ?>><?php echo (int) $bang_n; ?></a>
				<?php endfor; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>

	<?php
	bang_page_close();
	get_footer();
endif;
