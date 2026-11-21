<?php
/**
 * 友情链接（页面 slug: links）
 *
 * 0.13.27：版式统一到 /say/ 卡片流 —— 宽容器、22px 标题、--ink-2 描述、
 * 1/2/3 列响应式网格，每张卡片是和说说同款的 12px 圆角细线卡片。
 * 卡片不是外链：第一次点击弹窗显示站点卡片，弹窗里再点一次才直接访问。
 *
 * 分类来自 WordPress 核心链接分类（link_category），前台提供筛选按钮；
 * 暂不可达（核心里未勾选「可见」）显示黑白空心点 + 不可点击，
 * 不用红绿色，跟全站中性配色一致。
 * 底部是真正的申请表单（游客可提交）→ 后台审核通过后写入核心链接管理器。
 *
 * @package Bang
 */

get_header();

$bang_items  = bang_friend_links();
$bang_total  = count( $bang_items );
$bang_down   = 0;
$bang_groups = array();

foreach ( $bang_items as $bang_item ) {
	if ( $bang_item['offline'] ) {
		++$bang_down;
	}
	$bang_key = $bang_item['group'] ? $bang_item['group'] : '';
	if ( ! isset( $bang_groups[ $bang_key ] ) ) {
		$bang_groups[ $bang_key ] = array();
	}
	$bang_groups[ $bang_key ][] = $bang_item;
}

/* 分组顺序跟随核心分类清单；没配分类时保持链接出现顺序（默认行为零变化） */
if ( bang_link_groups() ) {
	$bang_ordered = array();
	foreach ( bang_link_groups() as $bang_g ) {
		if ( isset( $bang_groups[ $bang_g ] ) ) {
			$bang_ordered[ $bang_g ] = $bang_groups[ $bang_g ];
			unset( $bang_groups[ $bang_g ] );
		}
	}
	if ( isset( $bang_groups[''] ) ) {
		$bang_ordered[''] = $bang_groups[''];
		unset( $bang_groups[''] );
	}
	foreach ( $bang_groups as $bang_g => $bang_list ) {
		$bang_ordered[ $bang_g ] = $bang_list;
	}
	$bang_groups = $bang_ordered;
}

/* 筛选按钮：只有出现两个以上分组时才输出，避免只有一类时多一排 */
$bang_filters = array();
foreach ( $bang_groups as $bang_g => $bang_list ) {
	$bang_filters[] = array(
		'key'   => $bang_g,
		'label' => '' !== $bang_g ? $bang_g : __( '未分组', 'bang' ),
		'count' => count( $bang_list ),
	);
}
$bang_show_filter = count( $bang_filters ) > 1;

$bang_sub = $bang_total
	? sprintf( '互相看着顺眼的站点，共 %d 个%s。', $bang_total, $bang_down ? '，其中 ' . $bang_down . ' 个暂时走不通' : '' )
	: '还没有添加友链。';


/* 第三参给外壳加 scoped 类：只影响本页，不改通用 720px 信息页外壳 */
bang_page_open( __( '友情链接', 'bang' ), $bang_sub, 'bang-links-page' );
?>
	<?php if ( $bang_show_filter ) : ?>
		<nav class="bang-links-filter" aria-label="友链分类筛选">
			<button type="button" class="bang-links-chip is-active" data-bang-link-filter="all" aria-pressed="true"><?php echo esc_html( __( '全部', 'bang' ) ); ?> <span><?php echo esc_html( (string) $bang_total ); ?></span></button>
			<?php foreach ( $bang_filters as $bang_f ) : ?>
			<button type="button" class="bang-links-chip" data-bang-link-filter="<?php echo esc_attr( $bang_f['key'] ); ?>" aria-pressed="false"><?php echo esc_html( $bang_f['label'] ); ?> <span><?php echo esc_html( (string) $bang_f['count'] ); ?></span></button>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php if ( $bang_items ) : ?>
		<ul class="bang-links">
			<?php foreach ( $bang_groups as $bang_group => $bang_list ) : ?>
				<?php if ( '' !== $bang_group ) : ?>
				<li class="bang-links-group-row" data-bang-group="<?php echo esc_attr( $bang_group ); ?>">
					<h2 class="bang-links-group"><span class="bang-links-group-dot" aria-hidden="true"></span><?php echo esc_html( $bang_group ); ?><span class="bang-links-group-count"><?php echo esc_html( count( $bang_list ) ); ?></span></h2>
				</li>
				<?php endif; ?>
				<?php foreach ( $bang_list as $bang_item ) : ?>
					<?php
					$bang_host    = bang_link_host( $bang_item['url'] );
					$bang_initial = bang_link_initial( $bang_item['name'] );
					$bang_online  = ! $bang_item['offline'];
					?>
				<li class="bang-links-item" data-bang-group="<?php echo esc_attr( $bang_group ); ?>">
					<article class="say-card bang-link-card<?php echo $bang_online ? '' : ' is-offline'; ?>"
						data-bang-link-name="<?php echo esc_attr( $bang_item['name'] ); ?>"
						data-bang-link-url="<?php echo $bang_online ? esc_url( $bang_item['url'] ) : ''; ?>"
						data-bang-link-desc="<?php echo esc_attr( $bang_item['desc'] ); ?>"
						data-bang-link-group="<?php echo esc_attr( $bang_item['group'] ); ?>"
						data-bang-link-host="<?php echo esc_attr( $bang_host ); ?>"
						<?php if ( $bang_online ) : ?>
						role="link" tabindex="0" aria-label="<?php echo esc_attr( sprintf( '查看 %s 的站点卡片', $bang_item['name'] ) ); ?>"
						<?php else : ?>
						aria-disabled="true" title="这个站点暂时打不开"
						<?php endif; ?>
					>
						<header class="say-card-head">
							<span class="say-avatar bang-link-avatar" aria-hidden="true"><?php echo esc_html( $bang_initial ); ?></span>
							<span class="say-author">
								<span class="say-name"><?php echo esc_html( $bang_item['name'] ); ?></span>
								<span class="say-time bang-link-host"><?php echo esc_html( $bang_host ); ?></span>
							</span>
							<span class="bang-link-go" aria-hidden="true"><?php echo bang_icon( $bang_online ? 'external-link' : 'info', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						</header>
						<?php if ( $bang_item['desc'] ) : ?>
						<div class="say-content"><?php echo esc_html( $bang_item['desc'] ); ?></div>
						<?php endif; ?>
						<footer class="say-foot">
							<?php if ( $bang_item['group'] ) : ?>
							<span class="say-loc"><?php echo bang_kit_icon( 'pin', 13 ) . esc_html( $bang_item['group'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<?php endif; ?>
							<span class="bang-link-state<?php echo $bang_online ? ' is-online' : ''; ?>" aria-hidden="true"></span>
							<span class="bang-link-state-text<?php echo $bang_online ? '' : ' bang-link-off'; ?>"><?php echo esc_html( $bang_online ? __( '站点正常', 'bang' ) : __( '暂不可达', 'bang' ) ); ?></span>
						</footer>
					</article>
				</li>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p class="bang-page-empty"><?php echo esc_html( '站长还没添加友链，先看看下面的申请方式。' ); ?></p>
	<?php endif; ?>

	<section class="bang-apply" id="bang-link-apply">
		<h2 class="bang-apply-title"><?php echo esc_html( __( '申请友链', 'bang' ) ); ?></h2>
		<ul class="bang-apply-list">
			<li><?php echo esc_html( __( '1、站点正常可访问，内容健康且长期维护。', 'bang' ) ); ?></li>
			<li><?php echo esc_html( __( '2、已把本站加入贵站的友链页。', 'bang' ) ); ?></li>
			<li><?php echo esc_html( __( '3、按下面表格填写即可，分类可不选。', 'bang' ) ); ?></li>
		</ul>

		<form class="bang-apply-form" id="bang-link-apply-form" method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
			<input type="hidden" name="action" value="bang_link_apply">
			<?php wp_nonce_field( 'bang_ajax', 'nonce' ); ?>
			<p class="bang-apply-hp" aria-hidden="true"><label>请勿填写此栏<input type="text" name="bang_hp" tabindex="-1" autocomplete="off"></label></p>
			<div class="bang-apply-grid">
				<label class="bang-apply-field">
					<span><?php echo esc_html( __( '网站名称', 'bang' ) ); ?> <b aria-hidden="true">*</b></span>
					<input type="text" name="link_name" required maxlength="60" placeholder="<?php echo esc_attr( __( '例如：某某小站', 'bang' ) ); ?>">
				</label>
				<label class="bang-apply-field">
					<span><?php echo esc_html( __( '网站链接', 'bang' ) ); ?> <b aria-hidden="true">*</b></span>
					<input type="url" name="link_url" required placeholder="<?php echo esc_attr( __( 'https://example.com', 'bang' ) ); ?>">
				</label>
				<label class="bang-apply-field bang-apply-field--wide">
					<span><?php echo esc_html( __( '网站描述', 'bang' ) ); ?></span>
					<input type="text" name="link_desc" maxlength="200" placeholder="<?php echo esc_attr( __( '一句话介绍你的站点', 'bang' ) ); ?>">
				</label>
				<label class="bang-apply-field">
					<span><?php echo esc_html( __( '友链分类', 'bang' ) ); ?></span>
					<select name="link_group">
						<option value=""><?php echo esc_html( __( '未分组', 'bang' ) ); ?></option>
						<?php foreach ( bang_link_groups() as $bang_g ) : ?>
						<option value="<?php echo esc_attr( $bang_g ); ?>"><?php echo esc_html( $bang_g ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="bang-apply-field">
					<span><?php echo esc_html( __( '联系邮箱', 'bang' ) ); ?></span>
					<input type="email" name="link_email" placeholder="<?php echo esc_attr( __( '选填，方便我们回复', 'bang' ) ); ?>">
				</label>
			</div>
			<div class="bang-apply-foot">
				<button type="submit" class="bang-page-btn bang-apply-submit"><?php echo esc_html( __( '提交申请', 'bang' ) ); ?></button>
				<span class="bang-apply-msg" id="bang-link-apply-msg" role="status" aria-live="polite"></span>
			</div>
		</form>
	</section>
<?php
bang_page_close();
get_footer();
