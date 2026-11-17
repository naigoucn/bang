<?php
/**
 * 登录 / 注册页（页面 slug: login）
 *
 * 与全站弹窗（footer 里的 #pk-auth）共用 pk_auth_card_html()，
 * 所以两种形态长得一模一样，路径也统一是 /login/。
 *
 * @package Naigou
 */

get_header();

$pk_user     = is_user_logged_in() ? wp_get_current_user() : null;
$pk_mode     = isset( $_GET['mode'] ) ? sanitize_key( wp_unslash( $_GET['mode'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification
$pk_err      = isset( $_GET['pk_err'] ) ? sanitize_key( wp_unslash( $_GET['pk_err'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$pk_redirect = pk_user_redirect( '' );
?>
<div id="swup-wrapper">
<main class="wrap auth-wrap pk-page-wrap pk-page-wide" id="main">
<div class="pk-page-inner">
<header class="pk-page-head">
	<h1 class="pk-page-title">登录</h1>
	<p class="pk-page-sub">登录后可以点赞、评论，并同步你的阅读记录。</p>
</header>
	<div class="auth-page">

	<?php if ( $pk_user ) : ?>

		<div class="auth-card is-signed" data-pk-auth-card>
			<header class="auth-head">
				<p class="auth-mark"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
				<h1 class="auth-title">已经登录了</h1>
				<p class="auth-sub">当前账号：<?php echo esc_html( $pk_user->display_name ); ?></p>
			</header>
			<div class="auth-signed">
				<a class="user-submit auth-submit" href="<?php echo esc_url( pk_user_url() ); ?>">去用户中心</a>
				<a class="auth-back" href="<?php echo esc_url( home_url( '/' ) ); ?>">回首页</a>
			</div>
		</div>

	<?php else : ?>

		<?php
		echo pk_auth_card_html(
			array(
				'mode'     => $pk_mode,
				'err'      => $pk_err,
				'redirect' => $pk_redirect,
			)
		); // phpcs:ignore WordPress.Security.EscapeOutput -- 内部字段均已转义
		?>
		<p class="auth-page-foot">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">回首页</a>
			<span>·</span>
			<a href="<?php echo esc_url( pk_user_url() ); ?>">用户中心</a>
		</p>

	<?php endif; ?>

	</div>
</div>
</main>
</div>
<?php get_footer(); ?>
