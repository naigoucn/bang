<?php
/**
 * 用户中心（页面 slug: user）
 *
 * 未登录：登录 / 注册。
 * 已登录：资料卡 + 统计 + 点赞 / 评论两个列表（收藏已合并进点赞）。
 *
 * 数据全部来自 WordPress：点赞在 user_meta，评论走 get_comments。
 *
 * @package Naigou
 */

get_header();

$bang_user     = is_user_logged_in() ? wp_get_current_user() : null;
$bang_mode     = isset( $_GET['mode'] ) ? sanitize_key( wp_unslash( $_GET['mode'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification
$bang_err      = isset( $_GET['bang_err'] ) ? sanitize_key( wp_unslash( $_GET['bang_err'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$bang_tab      = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'like'; // phpcs:ignore WordPress.Security.NonceVerification
$bang_redirect = bang_user_redirect();
$bang_tabs     = array(
	'like'    => '我的点赞',
	'comment' => '我的评论',
);
/* 初始高亮只认「明确保存过」的值；从未选择时给空串，
   前端 syncSegButtons() 会按本机生效偏好补高亮（见 assets/js/app.js） */
$bang_side     = $bang_user ? (string) get_user_meta( $bang_user->ID, 'bang_dock_side', true ) : '';
$bang_sides    = bang_dock_sides();
?>
<div id="swup-wrapper">
<main class="wrap user-wrap bang-user-page" id="main">
<div class="user-inner">
<?php /* 与 /say/、/categories/、/links/、/about/ 同版式：宽容器 + 统一标题头 */ ?>
<header class="bang-page-head">
	<h1 class="bang-page-title">个人中心</h1>
	<p class="bang-page-sub">你的资料、点赞与评论。</p>
</header>
<?php if ( ! $bang_user ) : ?>

	<section class="user-auth auth-page">
		<?php
		/* 与 /login/ 整页、全站弹窗共用同一张卡片，三条入口长得一样 */
		echo bang_auth_card_html(
			array(
				'mode'     => $bang_mode,
				'err'      => $bang_err,
				'redirect' => $bang_redirect,
				'base'     => bang_user_url(),
				'from'     => bang_user_url(),
			)
		); // phpcs:ignore WordPress.Security.EscapeOutput -- 内部字段均已转义
		?>
	</section>

<?php else : ?>
	<?php
	$bang_stats    = bang_user_stats( $bang_user->ID );
	$bang_ids      = 'comment' === $bang_tab ? array() : bang_social_ids( $bang_user->ID );
	$bang_passkeys = bang_passkeys_of( $bang_user->ID );
	$bang_items    = array();
	if ( $bang_ids ) {
		$bang_items = get_posts(
			array(
				'post__in'            => $bang_ids,
				'orderby'             => 'post__in',
				'posts_per_page'      => 60,
				'post_status'         => 'publish',
				'ignore_sticky_posts' => true,
			)
		);
	}
	$bang_comments = array();
	if ( 'comment' === $bang_tab ) {
		$bang_comments = get_comments(
			array(
				'user_id' => $bang_user->ID,
				'status'  => 'approve',
				'type'    => 'comment',
				'number'  => 60,
			)
		);
	}
	?>

	<section class="user-profile">
		<div class="user-head">
			<?php echo bang_user_avatar( $bang_user, 64 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="user-id">
				<h2 class="user-name"><?php echo esc_html( $bang_user->display_name ); ?></h2>
				<p class="user-since">加入于 <?php echo esc_html( mysql2date( 'Y.m.d', $bang_user->user_registered ) ); ?></p>
			</div>
			<a class="user-logout" href="<?php echo esc_url( wp_logout_url( bang_user_url() ) ); ?>" data-no-swup data-bang-logout>退出</a>
		</div>

		<div class="user-stats">
			<a class="user-stat" href="<?php echo esc_url( add_query_arg( 'tab', 'like', bang_user_url() ) ); ?>"><b><?php echo (int) $bang_stats['like']; ?></b><span>点赞</span></a>
			<a class="user-stat" href="<?php echo esc_url( add_query_arg( 'tab', 'comment', bang_user_url() ) ); ?>"><b><?php echo (int) $bang_stats['comment']; ?></b><span>评论</span></a>
		</div>

		<a class="user-settings-btn" href="<?php echo esc_url( bang_settings_url() ); ?>">
			<?php echo bang_icon( 'user-round', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span>账号设置</span>
		</a>
		<!-- 账号设置、群绑定与注销弹窗已移到独立页面 /settings/ -->
		<!-- [暂时禁用 · 恢复时删掉本行与下方 [/暂时禁用] 行] 手机端动作条位置三档（重力换边）
		<section class="user-prefs" aria-labelledby="bang-prefs-title">
			<h2 class="user-prefs-title" id="bang-prefs-title">手机端动作条</h2>
			<p class="user-prefs-desc">文章页的点赞 / 评论 / 分享按钮放在哪一侧。跟随倾斜时，手机向哪边歪就换到哪边；固定后不再跟随。</p>
			<div class="user-seg" role="group" aria-label="动作条位置">
				<?php foreach ( $bang_sides as $bang_key => $bang_label ) : ?>
					<button type="button" class="user-seg-btn<?php echo $bang_side === $bang_key ? ' is-active' : ''; ?>"
						data-bang-side="<?php echo esc_attr( $bang_key ); ?>"
						aria-pressed="<?php echo $bang_side === $bang_key ? 'true' : 'false'; ?>"><?php echo esc_html( $bang_label ); ?></button>
				<?php endforeach; ?>
			</div>
			<p class="user-prefs-saved" data-bang-side-saved aria-live="polite"></p>
		</section>
		[/暂时禁用] -->

		<!-- [暂时禁用 · 恢复时删掉本行与下方 [/暂时禁用] 行] 指纹 / Face ID 通行密钥管理卡
		<section class="user-prefs" id="bang-passkeys" aria-labelledby="bang-passkey-title">
			<h2 class="user-prefs-title" id="bang-passkey-title">指纹 / Face ID 登录</h2>
			<p class="user-prefs-desc">给这台设备登记一把通行密钥，之后在本机不用输密码，直接用指纹 / 面容 ID 登录。密钥只留在你的设备上，这里只存公钥。</p>
			<?php if ( $bang_passkeys ) : ?>
				<ul class="bang-key-list">
					<?php foreach ( $bang_passkeys as $bang_key ) : ?>
						<li class="bang-key">
							<span class="bang-key-ico" aria-hidden="true"><?php echo bang_icon( 'scan-face', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<span class="bang-key-body">
								<b><?php echo esc_html( (string) ( isset( $bang_key['label'] ) ? $bang_key['label'] : '本机' ) ); ?></b>
								<span><?php echo esc_html( date_i18n( 'Y.m.d', (int) $bang_key['created'] ) ); ?>登记</span>
							</span>
							<button class="bang-key-remove" type="button" data-bang-passkey-remove="<?php echo esc_attr( $bang_key['id'] ); ?>" aria-label="移除这把通行密钥">
								<?php echo bang_icon( 'trash', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>移除
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<button class="user-submit user-submit-alt" type="button" data-bang-passkey-add>
				<?php echo bang_icon( 'fingerprint', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>添加本机
			</button>
		</section>
		[/暂时禁用] -->

		<nav class="user-tabs" aria-label="我的内容">
			<?php foreach ( $bang_tabs as $bang_key => $bang_label ) : ?>
				<a class="user-tab<?php echo $bang_tab === $bang_key ? ' is-active' : ''; ?>"
					href="<?php echo esc_url( add_query_arg( 'tab', $bang_key, bang_user_url() ) ); ?>"><?php echo esc_html( $bang_label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<div class="user-list user-list--<?php echo esc_attr( $bang_tab ); ?>">
			<?php if ( 'comment' === $bang_tab ) : ?>
				<?php if ( ! $bang_comments ) : ?>
					<p class="user-empty">还没有评论。去文章下面说两句？</p>
				<?php else : ?>
					<?php foreach ( $bang_comments as $bang_comment ) : ?>
						<?php
						$bang_text = wp_strip_all_tags( $bang_comment->comment_content );
						$bang_text = function_exists( 'mb_substr' ) ? mb_substr( $bang_text, 0, 140, 'UTF-8' ) : substr( $bang_text, 0, 140 );
						$bang_link = get_permalink( (int) $bang_comment->comment_post_ID );
						?>
						<article class="user-comment">
							<p class="user-comment-text"><?php echo esc_html( $bang_text ); ?></p>
							<p class="user-comment-meta">
								<a href="<?php echo esc_url( $bang_link ); ?>"><?php echo esc_html( get_the_title( (int) $bang_comment->comment_post_ID ) ); ?></a>
								<span>·</span>
								<span><?php echo esc_html( mysql2date( 'Y.m.d', $bang_comment->comment_date ) ); ?></span>
							</p>
						</article>
					<?php endforeach; ?>
				<?php endif; ?>
			<?php else : ?>
				<?php if ( ! $bang_items ) : ?>
					<p class="user-empty">还没有点赞，点文章右侧的心形图标试试。</p>
				<?php else : ?>
					<?php /* 和首页信息流同款 .card：带 data-id，Swup 打开 / 返回都能做共享元素 morph，
					   从个人中心点进文章、再返回时回到打开前的这个列表 */ ?>
					<div class="user-posts bang-user-cards">
						<?php
						foreach ( $bang_items as $bang_item ) {
							$GLOBALS['post'] = $bang_item;
							setup_postdata( $bang_item );
							get_template_part( 'template-parts/card' );
						}
						wp_reset_postdata();
						?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</section>

<?php endif; ?>
</div>
</main>
</div>
<?php get_footer(); ?>
