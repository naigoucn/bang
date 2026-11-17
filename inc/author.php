<?php
/**
 * 作者卡片：用户资料里的头像地址
 *
 * 侧栏作者卡优先用这里填写的图片；留空时回退到主题 Logo，再没有就显示名字首字。
 * 头像不走 Gravatar，避免内网 / 离线环境破图。
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 用户资料页 / 个人资料页：新增「作者头像」字段
 *
 * @param WP_User $user 当前编辑的用户。
 */
function pk_author_avatar_field( $user ) {
	if ( ! current_user_can( 'edit_user', $user->ID ) ) {
		return;
	}
	$url = (string) get_the_author_meta( 'pk_avatar', $user->ID );
	?>
	<h2><?php esc_html_e( 'Bang · 作者卡片', 'pianke' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="pk_avatar"><?php esc_html_e( '头像图片地址', 'pianke' ); ?></label></th>
			<td>
				<input type="url" id="pk_avatar" name="pk_avatar" class="regular-text code"
					value="<?php echo esc_attr( $url ); ?>" placeholder="https://">
				<p class="description">
					<?php esc_html_e( '填写图片 URL（正方形，建议 112px 以上）。留空时使用「Bang 设置」里的 Logo；Logo 也没有则显示名字首字。', 'pianke' ); ?>
				</p>
				<?php if ( $url ) : ?>
					<p><img src="<?php echo esc_url( $url ); ?>" alt="" width="56" height="56"
							style="border-radius:50%;border:1px solid #dcdcde;object-fit:cover"></p>
				<?php endif; ?>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'pk_author_avatar_field' );
add_action( 'edit_user_profile', 'pk_author_avatar_field' );

/**
 * 保存头像地址（只接受 http / https）
 *
 * @param int $user_id 用户 ID。
 */
function pk_save_author_avatar( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	if ( ! isset( $_POST['pk_avatar'] ) ) {
		return;
	}
	$url = esc_url_raw( trim( wp_unslash( $_POST['pk_avatar'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$url = pk_safe_comment_site( $url ); // 复用同一套协议白名单：只放行 http / https
	if ( $url ) {
		update_user_meta( $user_id, 'pk_avatar', $url );
	} else {
		delete_user_meta( $user_id, 'pk_avatar' );
	}
}
add_action( 'personal_options_update', 'pk_save_author_avatar' );
add_action( 'edit_user_profile_update', 'pk_save_author_avatar' );
