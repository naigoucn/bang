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

$pk_user     = is_user_logged_in() ? wp_get_current_user() : null;
$pk_mode     = isset( $_GET['mode'] ) ? sanitize_key( wp_unslash( $_GET['mode'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification
$pk_err      = isset( $_GET['pk_err'] ) ? sanitize_key( wp_unslash( $_GET['pk_err'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$pk_tab      = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'like'; // phpcs:ignore WordPress.Security.NonceVerification
$pk_redirect = pk_user_redirect();
$pk_tabs     = array(
	'like'    => '我的点赞',
	'comment' => '我的评论',
);
/* 初始高亮只认「明确保存过」的值；从未选择时给空串，
   前端 syncSegButtons() 会按本机生效偏好补高亮（见 assets/js/app.js） */
$pk_side     = $pk_user ? (string) get_user_meta( $pk_user->ID, 'pk_dock_side', true ) : '';
$pk_sides    = pk_dock_sides();
?>
<div id="swup-wrapper">
<main class="wrap user-wrap pk-user-page" id="main">
<div class="user-inner">
<?php /* 与 /say/、/categories/、/links/、/about/ 同版式：宽容器 + 统一标题头 */ ?>
<header class="pk-page-head">
	<h1 class="pk-page-title">个人中心</h1>
	<p class="pk-page-sub">你的资料、点赞与评论。</p>
</header>
<?php if ( ! $pk_user ) : ?>

	<section class="user-auth auth-page">
		<?php
		/* 与 /login/ 整页、全站弹窗共用同一张卡片，三条入口长得一样 */
		echo pk_auth_card_html(
			array(
				'mode'     => $pk_mode,
				'err'      => $pk_err,
				'redirect' => $pk_redirect,
				'base'     => pk_user_url(),
				'from'     => pk_user_url(),
			)
		); // phpcs:ignore WordPress.Security.EscapeOutput -- 内部字段均已转义
		?>
	</section>

<?php else : ?>
	<?php
	$pk_stats    = pk_user_stats( $pk_user->ID );
	$pk_ids      = 'comment' === $pk_tab ? array() : pk_social_ids( $pk_user->ID );
	$pk_passkeys = pk_passkeys_of( $pk_user->ID );
	$pk_items    = array();
	if ( $pk_ids ) {
		$pk_items = get_posts(
			array(
				'post__in'            => $pk_ids,
				'orderby'             => 'post__in',
				'posts_per_page'      => 60,
				'post_status'         => 'publish',
				'ignore_sticky_posts' => true,
			)
		);
	}
	$pk_comments = array();
	if ( 'comment' === $pk_tab ) {
		$pk_comments = get_comments(
			array(
				'user_id' => $pk_user->ID,
				'status'  => 'approve',
				'type'    => 'comment',
				'number'  => 60,
			)
		);
	}
	?>

	<section class="user-profile">
		<div class="user-head">
			<?php echo pk_user_avatar( $pk_user, 64 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="user-id">
				<h2 class="user-name"><?php echo esc_html( $pk_user->display_name ); ?></h2>
				<p class="user-since">加入于 <?php echo esc_html( mysql2date( 'Y.m.d', $pk_user->user_registered ) ); ?></p>
			</div>
			<a class="user-logout" href="<?php echo esc_url( wp_logout_url( pk_user_url() ) ); ?>" data-no-swup data-pk-logout>退出</a>
		</div>

		<div class="user-stats">
			<a class="user-stat" href="<?php echo esc_url( add_query_arg( 'tab', 'like', pk_user_url() ) ); ?>"><b><?php echo (int) $pk_stats['like']; ?></b><span>点赞</span></a>
			<a class="user-stat" href="<?php echo esc_url( add_query_arg( 'tab', 'comment', pk_user_url() ) ); ?>"><b><?php echo (int) $pk_stats['comment']; ?></b><span>评论</span></a>
		</div>

		<a class="user-settings-btn" href="<?php echo esc_url( pk_settings_url() ); ?>">
			<?php echo pk_icon( 'user-round', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span>账号设置</span>
		</a>
		<!-- 账号设置、群绑定与注销弹窗已移到独立页面 /settings/ -->
		<!-- [暂时禁用 · 恢复时删掉本行与下方 [/暂时禁用] 行] 手机端动作条位置三档（重力换边）
		<section class="user-prefs" aria-labelledby="pk-prefs-title">
			<h2 class="user-prefs-title" id="pk-prefs-title">手机端动作条</h2>
			<p class="user-prefs-desc">文章页的点赞 / 评论 / 分享按钮放在哪一侧。跟随倾斜时，手机向哪边歪就换到哪边；固定后不再跟随。</p>
			<div class="user-seg" role="group" aria-label="动作条位置">
				<?php foreach ( $pk_sides as $pk_key => $pk_label ) : ?>
					<button type="button" class="user-seg-btn<?php echo $pk_side === $pk_key ? ' is-active' : ''; ?>"
						data-pk-side="<?php echo esc_attr( $pk_key ); ?>"
						aria-pressed="<?php echo $pk_side === $pk_key ? 'true' : 'false'; ?>"><?php echo esc_html( $pk_label ); ?></button>
				<?php endforeach; ?>
			</div>
			<p class="user-prefs-saved" data-pk-side-saved aria-live="polite"></p>
		</section>
		[/暂时禁用] -->

		<!-- [暂时禁用 · 恢复时删掉本行与下方 [/暂时禁用] 行] 指纹 / Face ID 通行密钥管理卡
		<section class="user-prefs" id="pk-passkeys" aria-labelledby="pk-passkey-title">
			<h2 class="user-prefs-title" id="pk-passkey-title">指纹 / Face ID 登录</h2>
			<p class="user-prefs-desc">给这台设备登记一把通行密钥，之后在本机不用输密码，直接用指纹 / 面容 ID 登录。密钥只留在你的设备上，这里只存公钥。</p>
			<?php if ( $pk_passkeys ) : ?>
				<ul class="pk-key-list">
					<?php foreach ( $pk_passkeys as $pk_key ) : ?>
						<li class="pk-key">
							<span class="pk-key-ico" aria-hidden="true"><?php echo pk_icon( 'scan-face', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<span class="pk-key-body">
								<b><?php echo esc_html( (string) ( isset( $pk_key['label'] ) ? $pk_key['label'] : '本机' ) ); ?></b>
								<span><?php echo esc_html( date_i18n( 'Y.m.d', (int) $pk_key['created'] ) ); ?>登记</span>
							</span>
							<button class="pk-key-remove" type="button" data-pk-passkey-remove="<?php echo esc_attr( $pk_key['id'] ); ?>" aria-label="移除这把通行密钥">
								<?php echo pk_icon( 'trash', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>移除
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<button class="user-submit user-submit-alt" type="button" data-pk-passkey-add>
				<?php echo pk_icon( 'fingerprint', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>添加本机
			</button>
		</section>
		[/暂时禁用] -->

		<nav class="user-tabs" aria-label="我的内容">
			<?php foreach ( $pk_tabs as $pk_key => $pk_label ) : ?>
				<a class="user-tab<?php echo $pk_tab === $pk_key ? ' is-active' : ''; ?>"
					href="<?php echo esc_url( add_query_arg( 'tab', $pk_key, pk_user_url() ) ); ?>"><?php echo esc_html( $pk_label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<div class="user-list user-list--<?php echo esc_attr( $pk_tab ); ?>">
			<?php if ( 'comment' === $pk_tab ) : ?>
				<?php if ( ! $pk_comments ) : ?>
					<p class="user-empty">还没有评论。去文章下面说两句？</p>
				<?php else : ?>
					<?php foreach ( $pk_comments as $pk_comment ) : ?>
						<?php
						$pk_text = wp_strip_all_tags( $pk_comment->comment_content );
						$pk_text = function_exists( 'mb_substr' ) ? mb_substr( $pk_text, 0, 140, 'UTF-8' ) : substr( $pk_text, 0, 140 );
						$pk_link = get_permalink( (int) $pk_comment->comment_post_ID );
						?>
						<article class="user-comment">
							<p class="user-comment-text"><?php echo esc_html( $pk_text ); ?></p>
							<p class="user-comment-meta">
								<a href="<?php echo esc_url( $pk_link ); ?>"><?php echo esc_html( get_the_title( (int) $pk_comment->comment_post_ID ) ); ?></a>
								<span>·</span>
								<span><?php echo esc_html( mysql2date( 'Y.m.d', $pk_comment->comment_date ) ); ?></span>
							</p>
						</article>
					<?php endforeach; ?>
				<?php endif; ?>
			<?php else : ?>
				<?php if ( ! $pk_items ) : ?>
					<p class="user-empty">还没有点赞，点文章右侧的心形图标试试。</p>
				<?php else : ?>
					<?php /* 和首页信息流同款 .card：带 data-id，Swup 打开 / 返回都能做共享元素 morph，
					   从个人中心点进文章、再返回时回到打开前的这个列表 */ ?>
					<div class="user-posts pk-user-cards">
						<?php
						foreach ( $pk_items as $pk_item ) {
							$GLOBALS['post'] = $pk_item;
							setup_postdata( $pk_item );
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
