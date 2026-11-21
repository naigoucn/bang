<?php
/**
 * 账号设置（独立页面 slug: settings）
 *
 * 从 /user/ 拆出来：群绑定资料 + 昵称 / 邮箱 / 密码 / 头像 / 注销账号。
 * 只给登录用户看；游客给登录卡片，登录后跳回本页。
 * 沿用 /user/ 的 .user-wrap / .user-account / .user-unlock 样式，零新增视觉体系。
 *
 * @package Naigou
 */

get_header();

$bang_user = is_user_logged_in() ? wp_get_current_user() : null;
$bang_mode = isset( $_GET['mode'] ) ? sanitize_key( wp_unslash( $_GET['mode'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification
$bang_err  = isset( $_GET['bang_err'] ) ? sanitize_key( wp_unslash( $_GET['bang_err'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$bang_msg  = isset( $_GET['bang_msg'] ) ? sanitize_key( wp_unslash( $_GET['bang_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
?>
<div id="swup-wrapper">
<main class="wrap user-wrap bang-user-page bang-settings-page" id="main">
<div class="user-inner">
<?php if ( ! $bang_user ) : ?>

	<header class="bang-page-head">
		<h1 class="bang-page-title">账号设置</h1>
		<p class="bang-page-sub">登录后才能修改资料。</p>
		<a class="say-back bang-settings-back" href="<?php echo esc_url( bang_user_url() ); ?>"><span>返回个人中心</span></a>
	</header>
	<section class="user-auth auth-page">
		<?php
		echo bang_auth_card_html(
			array(
				'mode'     => $bang_mode,
				'err'      => $bang_err,
				'redirect' => bang_settings_url(),
				'base'     => bang_user_url(),
				'from'     => bang_user_url(),
			)
		); // phpcs:ignore WordPress.Security.EscapeOutput -- 内部字段均已转义
		?>
	</section>

<?php else : ?>
	<?php
	$bang_msgs    = array( 'profile' => '资料（昵称 / 邮箱 / 密码 / 头像）已保存。' );
	$bang_err_txt = $bang_err ? bang_auth_error_text( $bang_err ) : '';
	/* 违禁词提示：命中时后台会把具体词带在 bang_err_msg 里，优先用它，别退回码表里的笼统文案 */
	$bang_err_msg = isset( $_GET['bang_err_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['bang_err_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $bang_err_msg ) {
		$bang_err_txt = $bang_err_msg;
	}
	/* 账号设置的校验码只在本页用，本地补文案即可，不污染登录 / 注册共用错误码表 */
	if ( $bang_err && '' === $bang_err_txt ) {
		$bang_local_err = array(
			'pwd_current'   => '当前密码不正确。',
			'pwd_match'     => '两次输入的新密码不一致。',
			'pwd_short'     => '新密码至少 6 个字符。',
			'email_invalid' => '邮箱格式不正确。',
			'email_used'    => '这个邮箱已被其它账号使用。',
		);
		$bang_err_txt = isset( $bang_local_err[ $bang_err ] ) ? $bang_local_err[ $bang_err ] : '';
	}
	?>

	<header class="bang-page-head">
		<h1 class="bang-page-title">账号设置</h1>
		<p class="bang-page-sub">昵称 / 邮箱 / 密码 / 头像。</p>
		<a class="say-back bang-settings-back" href="<?php echo esc_url( bang_user_url() ); ?>"><span>返回个人中心</span></a>
	</header>

	<?php if ( isset( $bang_msgs[ $bang_msg ] ) ) : ?>
		<p class="user-notice" role="status"><?php echo esc_html( $bang_msgs[ $bang_msg ] ); ?></p>
	<?php elseif ( $bang_err_txt ) : ?>
		<p class="user-notice is-err" role="alert"><?php echo esc_html( $bang_err_txt ); ?></p>
	<?php endif; ?>

	<!-- 入群解锁已屏蔽，群绑定卡不再显示 -->

	<!-- 账号设置：昵称 / 邮箱 / 密码 / 头像 / 注销账号 -->
	<section class="user-account" aria-labelledby="bang-account-title">
		<h2 class="user-account-title" id="bang-account-title">资料与登录信息</h2>
		<form class="user-account-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="bang_profile">
			<?php wp_nonce_field( 'bang_profile' ); ?>
			<input type="hidden" name="bang_auth_from" value="<?php echo esc_attr( bang_settings_url() ); ?>">
			<div class="user-field user-field-ro">
				<span>登录名（用于登录，不可修改）</span>
				<code class="user-login-ro"><?php echo esc_html( $bang_user->user_login ); ?></code>
			</div>
			<p class="user-account-hint user-login-hint">昵称是站内显示名，和登录名互相独立；改昵称不影响登录。</p>
			<div class="user-avatar-edit">
				<span class="user-avatar-edit-img" data-bang-avatar-preview><?php echo bang_user_avatar( $bang_user, 64 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<div class="user-avatar-edit-body">
					<label class="user-avatar-pick" for="bang-avatar-file">更换头像</label>
					<input class="bang-file" type="file" id="bang-avatar-file" name="bang_avatar" accept="image/jpeg,image/png,image/webp" data-bang-avatar-input>
					<p class="user-account-hint">JPG / PNG / WebP，2MB 以内，选好会先预览。</p>
				</div>
			</div>
			<label class="user-field">
				<span>昵称</span>
				<input type="text" name="display_name" value="<?php echo esc_attr( $bang_user->display_name ); ?>" minlength="2" maxlength="24" required autocomplete="nickname">
			</label>
			<label class="user-field">
				<span>邮箱</span>
				<input type="email" name="user_email" value="<?php echo esc_attr( $bang_user->user_email ); ?>" maxlength="120" autocomplete="email">
			</label>
			<!-- 邮箱验证码：改邮箱或密码时必填，发到当前绑定邮箱 -->
			<div class="user-field user-email-code" data-bang-email-code>
				<span>邮箱验证码 <em class="user-field-note">修改邮箱或密码时必填</em></span>
				<div class="user-email-code-row">
					<input type="text" name="bang_email_code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="6 位验证码" value="" data-bang-email-code-input>
					<button class="user-email-code-send" type="button" data-bang-email-code-send>发送验证码</button>
				</div>
				<p class="user-account-hint">验证码将发到当前邮箱 <?php echo esc_html( $bang_user->user_email ); ?>，10 分钟内有效。只改昵称 / 头像无需填写。</p>
			</div>
			<fieldset class="user-pwd-set">
				<legend>修改密码（不修改请留空）</legend>
				<label class="user-field">
					<span>当前密码</span>
					<input type="password" name="current_password" autocomplete="current-password">
				</label>
				<label class="user-field">
					<span>新密码</span>
					<input type="password" name="new_password" minlength="6" autocomplete="new-password">
				</label>
				<label class="user-field">
					<span>确认新密码</span>
					<input type="password" name="confirm_password" minlength="6" autocomplete="new-password">
				</label>
			</fieldset>
			<button class="user-submit user-submit-inline" type="submit">保存修改</button>
		</form>

		<div class="user-danger">
			<div class="user-danger-text">
				<h3 class="user-danger-title">注销账号</h3>
				<p class="user-danger-desc">账号、点赞与评论记录一并删除，无法恢复。</p>
			</div>
			<button class="user-danger-btn" type="button" data-bang-del-open>注销账号</button>
		</div>
	</section>

	<dialog class="bang-del" id="bang-del-dialog" aria-labelledby="bang-del-title">
		<form class="bang-del-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="bang_delete_account">
			<?php wp_nonce_field( 'bang_delete_account' ); ?>
			<input type="hidden" name="bang_auth_from" value="<?php echo esc_attr( bang_settings_url() ); ?>">
			<h2 class="bang-del-title" id="bang-del-title">确认注销账号</h2>
			<p class="bang-del-desc">此操作不可撤销。请输入当前密码，并在下方输入“注销”两个字确认。</p>
			<label class="bang-del-field">
				<span>当前密码</span>
				<input type="password" name="pwd" required autocomplete="current-password">
			</label>
			<label class="bang-del-field">
				<span>确认文字</span>
				<input type="text" name="confirm" required placeholder="注销" autocomplete="off" data-bang-del-ok>
			</label>
			<div class="bang-del-actions">
				<button class="bang-del-cancel" type="button" data-bang-del-cancel>取消</button>
				<button class="bang-del-submit" type="submit" data-bang-del-submit disabled>永久注销</button>
			</div>
		</form>
	</dialog>

<?php endif; ?>
</div>
</main>
</div>
<?php get_footer(); ?>
