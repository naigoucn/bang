<?php
/**
 * 站点页脚
 *
 * @package Naigou
 */
?>
<?php /* 页脚常驻 DOM：pjax 只交换 #swup-wrapper，用 hidden 同步「文章页不显示页脚」的态；
   用户中心与信息页（归档 / 关于 …）按列表页处理，保留页脚。
   内容与文章页共用 bang_footer_content_html()，改版只改一处 */ ?>
<div class="wrap" data-bang-foot-wrap<?php echo ( is_singular() && ! is_page( array( 'user', 'login' ) ) && ! bang_is_site_page() ) ? ' hidden' : ''; ?>>
	<footer class="site-foot"><?php echo bang_footer_content_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- 内部字段均已转义 ?></footer>
</div>

<dialog class="search-dialog" id="search-dialog" aria-label="搜索">
	<form class="search-field" id="search-form" autocomplete="off">
		<span class="search-ico" aria-hidden="true"><?php echo bang_icon( 'search', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<input type="search" id="search-input" name="s" placeholder="<?php echo esc_attr( __( '搜索标题或分类', 'bang' ) ); ?>" autocomplete="off" aria-label="<?php echo esc_attr( __( '搜索文章', 'bang' ) ); ?>">
		<button type="button" class="search-close" data-bang-search-close aria-label="<?php echo esc_attr( __( '关闭', 'bang' ) ); ?>"><?php echo bang_icon( 'x', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
	</form>
	<div class="search-results" id="search-results" hidden></div>
</dialog>

<?php /* 分享面板：footer 常驻（#swup-wrapper 外），Swup 换页不重建。
   手机从底部滑出、PC 居中弹窗；海报预览 + 保存海报 / 复制链接两个按钮。 */ ?>
<div class="bang-share-mask" id="bang-share-mask" aria-hidden="true"></div>
<section class="bang-share-sheet" id="bang-share-sheet" role="dialog" aria-modal="true" aria-hidden="true" aria-label="<?php echo esc_attr__( '分享', 'bang' ); ?>">
	<div class="bang-share-grip" aria-hidden="true"></div>
	<header class="bang-share-head">
		<span class="bang-share-heading"><?php echo esc_html__( '分享这篇文章', 'bang' ); ?></span>
		<button class="bang-share-close" type="button" data-bang-share-close aria-label="<?php echo esc_attr__( '关闭', 'bang' ); ?>"><?php echo bang_icon( 'close', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
	</header>
	<div class="bang-share-body">
		<div class="bang-share-poster">
			<div class="bang-poster-stage">
				<canvas class="bang-poster-canvas" id="bang-poster-canvas" width="640" height="900"></canvas>
				<div class="bang-poster-loading" id="bang-poster-loading">正在生成海报…</div>
			</div>
			<p class="bang-poster-tip"><?php echo esc_html__( '长按图片保存，或点击下方按钮下载', 'bang' ); ?></p>
		</div>
		<div class="bang-share-actions">
			<button class="bang-share-btn bang-share-btn--solid" type="button" data-bang-share-action="poster-save"><?php echo bang_icon( 'arrow-up', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html__( '保存海报', 'bang' ); ?></button>
			<button class="bang-share-btn" type="button" data-bang-share-action="copy-link"><?php echo bang_icon( 'link', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html__( '复制链接', 'bang' ); ?></button>
		</div>
	</div>
</section>

<?php /* 0.13.27 友情链接：第一次点卡片弹窗看详情，第二次点弹窗卡片 / 按钮才访问外链。
   footer 常驻（#swup-wrapper 外），Swup 换页不重建，document 级委托一直有效。
   弹窗里的两个外链都是 target="_blank"，已被 Swup 的 linkSelector 排除，不会被接管。 */ ?>
<dialog class="bang-link-dialog" id="bang-link-dialog" aria-labelledby="bang-link-dialog-label">
	<div class="bang-link-dialog-inner">
		<div class="bang-link-dialog-top">
			<span class="bang-link-dialog-label" id="bang-link-dialog-label"><?php echo esc_html( __( '站点卡片', 'bang' ) ); ?></span>
			<button class="bang-link-dialog-close" type="button" data-bang-link-close aria-label="<?php echo esc_attr( __( '关闭', 'bang' ) ); ?>"><?php echo bang_icon( 'close', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</div>
		<a class="say-card bang-link-dialog-card" id="bang-link-dialog-card" data-bang-link-dialog-card href="#" target="_blank" rel="noopener noreferrer">
			<header class="say-card-head">
				<span class="say-avatar bang-link-avatar" data-bang-link-d-initial aria-hidden="true">?</span>
				<span class="say-author">
					<span class="say-name" data-bang-link-d-name></span>
					<span class="say-time bang-link-host" data-bang-link-d-host></span>
				</span>
				<span class="bang-link-go" aria-hidden="true"><?php echo bang_icon( 'external-link', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</header>
			<div class="say-content" data-bang-link-d-desc hidden></div>
			<footer class="say-foot">
				<span class="say-loc" data-bang-link-d-group-row hidden><?php echo bang_kit_icon( 'pin', 13 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span data-bang-link-d-group></span></span>
				<span class="bang-link-dialog-dot" aria-hidden="true"></span>
				<span class="bang-link-state-text"><?php echo esc_html( __( '站点正常', 'bang' ) ); ?></span>
			</footer>
		</a>
		<div class="bang-link-dialog-foot">
			<a class="bang-page-btn bang-link-visit" id="bang-link-visit" data-bang-link-visit href="#" target="_blank" rel="noopener noreferrer"><?php echo esc_html( __( '访问站点', 'bang' ) ); ?><?php echo bang_icon( 'external-link', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<button class="bang-link-cancel" type="button" data-bang-link-close><?php echo esc_html( __( '再看看', 'bang' ) ); ?></button>
		</div>
	</div>
</dialog>

<?php /* 0.12.0 命令面板：Ctrl/Cmd+K 唤起。命令 + 文章搜索合一；
   AI 语义搜索是可选开关（transformers.js 浏览器内推理，首次需下载量化小模型）。
   footer 常驻（#swup-wrapper 外），pjax 换页不重建。 */ ?>
<dialog class="bang-cmdk" id="bang-cmdk" aria-label="命令面板">
	<div class="cmdk-field">
		<span class="cmdk-ico" aria-hidden="true"><?php echo bang_icon( 'search', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<input type="text" id="cmdk-input" placeholder="<?php echo esc_attr( __( '搜索文章，或输入命令…', 'bang' ) ); ?>" autocomplete="off" aria-label="命令面板输入" spellcheck="false">
		<?php if ( bang_opt( 'ui_cmdk_ai_on' ) ) : ?>
		<button type="button" class="cmdk-ai" id="cmdk-ai" aria-pressed="false" title="AI 语义搜索：模型跑在你浏览器里，按内容含义找文章（首次启用需下载约 135MB 模型，之后走浏览器缓存）">
			<span class="cmdk-ai-ico" aria-hidden="true"><?php echo bang_icon( 'sparkles', 13 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span>AI</span>
		</button>
		<?php endif; ?>
		<kbd class="cmdk-esc" aria-hidden="true">ESC</kbd>
	</div>
	<div class="cmdk-list" id="cmdk-list"></div>
	<div class="cmdk-foot">
		<span class="cmdk-hint"><kbd>↑</kbd><kbd>↓</kbd>选择</span>
		<span class="cmdk-hint"><kbd>Enter</kbd>打开</span>
		<span class="cmdk-brand" aria-hidden="true">Bang</span>
	</div>
</dialog>

<?php /* 全站公告：footer 常驻（在 #swup-wrapper 外），pjax 换页不重建、也不会重复弹 */ ?>
<?php echo bang_notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- 内部字段均已转义 ?>

<?php /* 登录弹窗：与 /login/ 整页内容同源（bang_auth_card_html），打开时地址栏同步成 /login/ */ ?>
<?php echo bang_auth_dialog_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- 内部字段均已转义 ?>

<?php /* 音乐播放器：全屏歌词面板 + 页面悬浮球，footer 常驻（#swup-wrapper 外），换页不重建 */ ?>
<?php if ( function_exists( 'bang_music_stage_html' ) ) { bang_music_stage_html(); } ?>

<div class="bang-drawer-backdrop" id="bang-comments-backdrop" hidden aria-hidden="true"></div>

<div class="bang-toast" id="bang-toast" role="status" aria-live="polite"><span class="bang-toast-icon"><?php echo bang_icon( 'check', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="bang-toast-text" id="bang-toast-text"></span></div>

<?php /* 全站返回顶部统一用文章页 / 站点信息页的 dock 置顶按钮，不再输出独立的 .bang-to-top */ ?>

<?php /* 手势翻篇时跟手显示目标文章：「下一篇：标题」/「上一篇：标题」，到尽头说明没有更多 */ ?>
<div class="bang-swipe-hint" id="bang-swipe-hint" aria-hidden="true"><span class="bang-swipe-kind" id="bang-swipe-kind"></span><span class="bang-swipe-title" id="bang-swipe-label"></span></div>

<dialog class="bang-lightbox" id="bang-lightbox" aria-label="图片查看">
	<div class="bang-lightbox-bar">
		<span class="bang-lightbox-count" id="bang-lightbox-count"></span>
		<button class="bang-lightbox-btn bang-lightbox-close" type="button" data-bang-lightbox-close aria-label="关闭" title="关闭"><?php echo bang_icon( 'close', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
	</div>
	<div class="bang-lightbox-stage" id="bang-lightbox-stage">
		<button class="bang-lightbox-btn bang-lightbox-nav" type="button" data-bang-lightbox-prev aria-label="上一张" title="上一张"><?php echo bang_icon( 'prev', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<img id="bang-lightbox-img" alt="">
		<button class="bang-lightbox-btn bang-lightbox-nav" type="button" data-bang-lightbox-next aria-label="下一张" title="下一张"><?php echo bang_icon( 'next', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
	</div>
	<p class="bang-lightbox-caption" id="bang-lightbox-caption" hidden></p>
</dialog>

<?php /* 侧栏图标统一提示气泡（黑底标签、右侧浮出）：挂在 #swup-wrapper 外，fixed 定位不受侧栏 overflow 裁切；文案由 app.js 取按钮 aria-label 注入 */ ?>
<div class="bang-side-tip" id="bang-side-tip" aria-hidden="true"></div>

<?php /* 0.13.31 手机端底部 tabbar：首页 / 说说 / 分类 / 个人中心。
   常驻 footer（#swup-wrapper 外），Swup 换页不重建；PC 由 CSS 隐藏。
   个人中心登录态直接进 /user/，游客点开登录弹窗（同顶栏头像逻辑）；
   图标沿用侧栏同款，active 态由 app.js::bangAsideActive() 按 href 比对。 */ ?>
<?php
$bang_tab_say = function_exists( 'get_post_type_archive_link' ) ? get_post_type_archive_link( 'bang_say' ) : '';
$bang_tab_me  = is_user_logged_in() ? home_url( '/user/' ) : home_url( '/login/' );
$bang_tabbar_style = bang_opt( 'tabbar_style' );
if ( 'flush' !== $bang_tabbar_style ) {
	$bang_tabbar_style = 'float';
}
?>
<nav class="bang-tabbar" data-bang-tabbar-style="<?php echo esc_attr( $bang_tabbar_style ); ?>" aria-label="<?php echo esc_attr__( '底部导航', 'bang' ); ?>">
	<a class="bang-tab" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr__( '首页', 'bang' ); ?>"><?php echo bang_icon( 'home', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html__( '首页', 'bang' ); ?></span></a>
	<?php if ( $bang_tab_say ) : ?>
	<a class="bang-tab" href="<?php echo esc_url( $bang_tab_say ); ?>" aria-label="<?php echo esc_attr__( '说说', 'bang' ); ?>"><?php echo bang_icon( 'message-circle', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html__( '说说', 'bang' ); ?></span></a>
	<?php endif; ?>
	<a class="bang-tab" href="<?php echo esc_url( bang_site_page_url( 'categories' ) ); ?>" aria-label="<?php echo esc_attr__( '分类', 'bang' ); ?>"><?php echo bang_icon( 'layers', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html__( '分类', 'bang' ); ?></span></a>
	<a class="bang-tab" data-bang-tab="me" href="<?php echo esc_url( $bang_tab_me ); ?>"<?php echo is_user_logged_in() ? '' : ' data-bang-auth-open'; ?> aria-label="<?php echo esc_attr__( '个人中心', 'bang' ); ?>"><?php echo bang_icon( 'user-round', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html__( '个人中心', 'bang' ); ?></span></a>
</nav>
<?php /* 手机端底部 tabbar 结束 */ ?>

<?php /* 0.12.1 资源自检哨兵：样式与脚本全内联，外部资源挂掉时也能提示刷新重试（0.13.3 起不再提供下载） */ ?>
<?php echo bang_asset_guard_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- 静态内联片段，无用户输入 ?>
<?php wp_footer(); ?>
</body>
</html>
