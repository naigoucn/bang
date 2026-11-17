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

$pk_items  = pk_friend_links();
$pk_total  = count( $pk_items );
$pk_down   = 0;
$pk_groups = array();

foreach ( $pk_items as $pk_item ) {
	if ( $pk_item['offline'] ) {
		++$pk_down;
	}
	$pk_key = $pk_item['group'] ? $pk_item['group'] : '';
	if ( ! isset( $pk_groups[ $pk_key ] ) ) {
		$pk_groups[ $pk_key ] = array();
	}
	$pk_groups[ $pk_key ][] = $pk_item;
}

/* 分组顺序跟随核心分类清单；没配分类时保持链接出现顺序（默认行为零变化） */
if ( pk_link_groups() ) {
	$pk_ordered = array();
	foreach ( pk_link_groups() as $pk_g ) {
		if ( isset( $pk_groups[ $pk_g ] ) ) {
			$pk_ordered[ $pk_g ] = $pk_groups[ $pk_g ];
			unset( $pk_groups[ $pk_g ] );
		}
	}
	if ( isset( $pk_groups[''] ) ) {
		$pk_ordered[''] = $pk_groups[''];
		unset( $pk_groups[''] );
	}
	foreach ( $pk_groups as $pk_g => $pk_list ) {
		$pk_ordered[ $pk_g ] = $pk_list;
	}
	$pk_groups = $pk_ordered;
}

/* 筛选按钮：只有出现两个以上分组时才输出，避免只有一类时多一排 */
$pk_filters = array();
foreach ( $pk_groups as $pk_g => $pk_list ) {
	$pk_filters[] = array(
		'key'   => $pk_g,
		'label' => '' !== $pk_g ? $pk_g : __( '未分组', 'pianke' ),
		'count' => count( $pk_list ),
	);
}
$pk_show_filter = count( $pk_filters ) > 1;

$pk_sub = $pk_total
	? sprintf( '互相看着顺眼的站点，共 %d 个%s。', $pk_total, $pk_down ? '，其中 ' . $pk_down . ' 个暂时走不通' : '' )
	: '还没有添加友链。';


/* 第三参给外壳加 scoped 类：只影响本页，不改通用 720px 信息页外壳 */
pk_page_open( __( '友情链接', 'pianke' ), $pk_sub, 'pk-links-page' );
?>
	<?php if ( $pk_show_filter ) : ?>
		<nav class="pk-links-filter" aria-label="友链分类筛选">
			<button type="button" class="pk-links-chip is-active" data-pk-link-filter="all" aria-pressed="true"><?php echo esc_html( __( '全部', 'pianke' ) ); ?> <span><?php echo esc_html( (string) $pk_total ); ?></span></button>
			<?php foreach ( $pk_filters as $pk_f ) : ?>
			<button type="button" class="pk-links-chip" data-pk-link-filter="<?php echo esc_attr( $pk_f['key'] ); ?>" aria-pressed="false"><?php echo esc_html( $pk_f['label'] ); ?> <span><?php echo esc_html( (string) $pk_f['count'] ); ?></span></button>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php if ( $pk_items ) : ?>
		<ul class="pk-links">
			<?php foreach ( $pk_groups as $pk_group => $pk_list ) : ?>
				<?php if ( '' !== $pk_group ) : ?>
				<li class="pk-links-group-row" data-pk-group="<?php echo esc_attr( $pk_group ); ?>">
					<h2 class="pk-links-group"><span class="pk-links-group-dot" aria-hidden="true"></span><?php echo esc_html( $pk_group ); ?><span class="pk-links-group-count"><?php echo esc_html( count( $pk_list ) ); ?></span></h2>
				</li>
				<?php endif; ?>
				<?php foreach ( $pk_list as $pk_item ) : ?>
					<?php
					$pk_host    = pk_link_host( $pk_item['url'] );
					$pk_initial = pk_link_initial( $pk_item['name'] );
					$pk_online  = ! $pk_item['offline'];
					?>
				<li class="pk-links-item" data-pk-group="<?php echo esc_attr( $pk_group ); ?>">
					<article class="say-card pk-link-card<?php echo $pk_online ? '' : ' is-offline'; ?>"
						data-pk-link-name="<?php echo esc_attr( $pk_item['name'] ); ?>"
						data-pk-link-url="<?php echo $pk_online ? esc_url( $pk_item['url'] ) : ''; ?>"
						data-pk-link-desc="<?php echo esc_attr( $pk_item['desc'] ); ?>"
						data-pk-link-group="<?php echo esc_attr( $pk_item['group'] ); ?>"
						data-pk-link-host="<?php echo esc_attr( $pk_host ); ?>"
						<?php if ( $pk_online ) : ?>
						role="link" tabindex="0" aria-label="<?php echo esc_attr( sprintf( '查看 %s 的站点卡片', $pk_item['name'] ) ); ?>"
						<?php else : ?>
						aria-disabled="true" title="这个站点暂时打不开"
						<?php endif; ?>
					>
						<header class="say-card-head">
							<span class="say-avatar pk-link-avatar" aria-hidden="true"><?php echo esc_html( $pk_initial ); ?></span>
							<span class="say-author">
								<span class="say-name"><?php echo esc_html( $pk_item['name'] ); ?></span>
								<span class="say-time pk-link-host"><?php echo esc_html( $pk_host ); ?></span>
							</span>
							<span class="pk-link-go" aria-hidden="true"><?php echo pk_icon( $pk_online ? 'external-link' : 'info', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						</header>
						<?php if ( $pk_item['desc'] ) : ?>
						<div class="say-content"><?php echo esc_html( $pk_item['desc'] ); ?></div>
						<?php endif; ?>
						<footer class="say-foot">
							<?php if ( $pk_item['group'] ) : ?>
							<span class="say-loc"><?php echo pk_kit_icon( 'pin', 13 ) . esc_html( $pk_item['group'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<?php endif; ?>
							<span class="pk-link-state<?php echo $pk_online ? ' is-online' : ''; ?>" aria-hidden="true"></span>
							<span class="pk-link-state-text<?php echo $pk_online ? '' : ' pk-link-off'; ?>"><?php echo esc_html( $pk_online ? __( '站点正常', 'pianke' ) : __( '暂不可达', 'pianke' ) ); ?></span>
						</footer>
					</article>
				</li>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p class="pk-page-empty"><?php echo esc_html( '站长还没添加友链，先看看下面的申请方式。' ); ?></p>
	<?php endif; ?>

	<section class="pk-apply" id="pk-link-apply">
		<h2 class="pk-apply-title"><?php echo esc_html( __( '申请友链', 'pianke' ) ); ?></h2>
		<ul class="pk-apply-list">
			<li><?php echo esc_html( __( '1、站点正常可访问，内容健康且长期维护。', 'pianke' ) ); ?></li>
			<li><?php echo esc_html( __( '2、已把本站加入贵站的友链页。', 'pianke' ) ); ?></li>
			<li><?php echo esc_html( __( '3、按下面表格填写即可，分类可不选。', 'pianke' ) ); ?></li>
		</ul>

		<form class="pk-apply-form" id="pk-link-apply-form" method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
			<input type="hidden" name="action" value="pk_link_apply">
			<?php wp_nonce_field( 'pk_ajax', 'nonce' ); ?>
			<p class="pk-apply-hp" aria-hidden="true"><label>请勿填写此栏<input type="text" name="pk_hp" tabindex="-1" autocomplete="off"></label></p>
			<div class="pk-apply-grid">
				<label class="pk-apply-field">
					<span><?php echo esc_html( __( '网站名称', 'pianke' ) ); ?> <b aria-hidden="true">*</b></span>
					<input type="text" name="link_name" required maxlength="60" placeholder="<?php echo esc_attr( __( '例如：某某小站', 'pianke' ) ); ?>">
				</label>
				<label class="pk-apply-field">
					<span><?php echo esc_html( __( '网站链接', 'pianke' ) ); ?> <b aria-hidden="true">*</b></span>
					<input type="url" name="link_url" required placeholder="<?php echo esc_attr( __( 'https://example.com', 'pianke' ) ); ?>">
				</label>
				<label class="pk-apply-field pk-apply-field--wide">
					<span><?php echo esc_html( __( '网站描述', 'pianke' ) ); ?></span>
					<input type="text" name="link_desc" maxlength="200" placeholder="<?php echo esc_attr( __( '一句话介绍你的站点', 'pianke' ) ); ?>">
				</label>
				<label class="pk-apply-field">
					<span><?php echo esc_html( __( '友链分类', 'pianke' ) ); ?></span>
					<select name="link_group">
						<option value=""><?php echo esc_html( __( '未分组', 'pianke' ) ); ?></option>
						<?php foreach ( pk_link_groups() as $pk_g ) : ?>
						<option value="<?php echo esc_attr( $pk_g ); ?>"><?php echo esc_html( $pk_g ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="pk-apply-field">
					<span><?php echo esc_html( __( '联系邮箱', 'pianke' ) ); ?></span>
					<input type="email" name="link_email" placeholder="<?php echo esc_attr( __( '选填，方便我们回复', 'pianke' ) ); ?>">
				</label>
			</div>
			<div class="pk-apply-foot">
				<button type="submit" class="pk-page-btn pk-apply-submit"><?php echo esc_html( __( '提交申请', 'pianke' ) ); ?></button>
				<span class="pk-apply-msg" id="pk-link-apply-msg" role="status" aria-live="polite"></span>
			</div>
		</form>
	</section>
<?php
pk_page_close();
get_footer();
