<?php
/**
 * 站点页脚
 *
 * @package Naigou
 */
$pk_icp = trim( (string) pk_opt( 'icp' ) );
?>
<?php /* 页脚常驻 DOM：pjax 只交换 #swup-wrapper，用 hidden 同步「文章页不显示页脚」的态；
   用户中心与信息页（归档 / 关于 …）按列表页处理，保留页脚 */ ?>
<div class="wrap" data-pk-foot-wrap<?php echo ( is_singular() && ! is_page( array( 'user', 'login' ) ) && ! pk_is_site_page() ) ? ' hidden' : ''; ?>>
	<footer class="site-foot">
		<?php $pk_foot_nav = function_exists( 'pk_footer_nav_items' ) ? pk_footer_nav_items() : array(); ?>
		<?php if ( $pk_foot_nav ) : ?>
		<nav class="site-foot-nav" aria-label="页脚导航">
			<?php foreach ( $pk_foot_nav as $pk_fn ) : ?>
			<a class="site-foot-link" href="<?php echo esc_url( $pk_fn['url'] ); ?>"<?php echo $pk_fn['blank'] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $pk_fn['name'] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php endif; ?>
		<div class="site-foot-meta">
			<span><?php echo esc_html( get_bloginfo( 'name' ) ); ?> · <?php echo esc_html( pk_opt( 'footer_text' ) ); ?></span>
			<span><?php echo esc_html( gmdate( 'Y' ) ); ?><?php echo $pk_icp ? ' · ' . esc_html( $pk_icp ) : ''; ?></span>
		</div>
	</footer>
</div>

<dialog class="search-dialog" id="search-dialog" aria-label="搜索">
	<form class="search-field" id="search-form" autocomplete="off">
		<span class="search-ico" aria-hidden="true"><?php echo pk_icon( 'search', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<input type="search" id="search-input" name="s" placeholder="<?php echo esc_attr( __( '搜索标题或分类', 'pianke' ) ); ?>" autocomplete="off" aria-label="<?php echo esc_attr( __( '搜索文章', 'pianke' ) ); ?>">
		<button type="button" class="search-close" data-pk-search-close aria-label="<?php echo esc_attr( __( '关闭', 'pianke' ) ); ?>"><?php echo pk_icon( 'x', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
	</form>
	<div class="search-results" id="search-results" hidden></div>
</dialog>

<dialog class="share-dialog" id="share-dialog" aria-label="分享">
	<h3 class="share-title" id="share-dialog-title"></h3>
	<div class="share-link-row">
		<input class="share-link-input" id="share-link-input" type="text" readonly aria-label="文章链接">
		<button class="share-copy-link" type="button" data-pk-share-copy="link"><?php echo esc_html( __( '复制链接', 'pianke' ) ); ?></button>
	</div>
	<div class="share-actions">
		<button class="link-btn" type="button" data-pk-share-copy="both"><?php echo esc_html( __( '复制标题和链接', 'pianke' ) ); ?></button>
		<button class="share-cancel" type="button" data-pk-share-close><?php echo esc_html( __( '关闭', 'pianke' ) ); ?></button>
	</div>
</dialog>

<?php /* 0.13.27 友情链接：第一次点卡片弹窗看详情，第二次点弹窗卡片 / 按钮才访问外链。
   footer 常驻（#swup-wrapper 外），Swup 换页不重建，document 级委托一直有效。
   弹窗里的两个外链都是 target="_blank"，已被 Swup 的 linkSelector 排除，不会被接管。 */ ?>
<dialog class="pk-link-dialog" id="pk-link-dialog" aria-labelledby="pk-link-dialog-label">
	<div class="pk-link-dialog-inner">
		<div class="pk-link-dialog-top">
			<span class="pk-link-dialog-label" id="pk-link-dialog-label"><?php echo esc_html( __( '站点卡片', 'pianke' ) ); ?></span>
			<button class="pk-link-dialog-close" type="button" data-pk-link-close aria-label="<?php echo esc_attr( __( '关闭', 'pianke' ) ); ?>"><?php echo pk_icon( 'close', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</div>
		<a class="say-card pk-link-dialog-card" id="pk-link-dialog-card" data-pk-link-dialog-card href="#" target="_blank" rel="noopener noreferrer">
			<header class="say-card-head">
				<span class="say-avatar pk-link-avatar" data-pk-link-d-initial aria-hidden="true">?</span>
				<span class="say-author">
					<span class="say-name" data-pk-link-d-name></span>
					<span class="say-time pk-link-host" data-pk-link-d-host></span>
				</span>
				<span class="pk-link-go" aria-hidden="true"><?php echo pk_icon( 'external-link', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</header>
			<div class="say-content" data-pk-link-d-desc hidden></div>
			<footer class="say-foot">
				<span class="say-loc" data-pk-link-d-group-row hidden><?php echo pk_kit_icon( 'pin', 13 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span data-pk-link-d-group></span></span>
				<span class="pk-link-dialog-dot" aria-hidden="true"></span>
				<span class="pk-link-state-text"><?php echo esc_html( __( '站点正常', 'pianke' ) ); ?></span>
			</footer>
		</a>
		<div class="pk-link-dialog-foot">
			<a class="pk-page-btn pk-link-visit" id="pk-link-visit" data-pk-link-visit href="#" target="_blank" rel="noopener noreferrer"><?php echo esc_html( __( '访问站点', 'pianke' ) ); ?><?php echo pk_icon( 'external-link', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<button class="pk-link-cancel" type="button" data-pk-link-close><?php echo esc_html( __( '再看看', 'pianke' ) ); ?></button>
		</div>
	</div>
</dialog>

<?php /* 0.12.0 命令面板：Ctrl/Cmd+K 唤起。命令 + 文章搜索合一；
   AI 语义搜索是可选开关（transformers.js 浏览器内推理，首次需下载量化小模型）。
   footer 常驻（#swup-wrapper 外），pjax 换页不重建。 */ ?>
<dialog class="pk-cmdk" id="pk-cmdk" aria-label="命令面板">
	<div class="cmdk-field">
		<span class="cmdk-ico" aria-hidden="true"><?php echo pk_icon( 'search', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<input type="text" id="cmdk-input" placeholder="<?php echo esc_attr( __( '搜索文章，或输入命令…', 'pianke' ) ); ?>" autocomplete="off" aria-label="命令面板输入" spellcheck="false">
		<?php if ( pk_opt( 'ui_cmdk_ai_on' ) ) : ?>
		<button type="button" class="cmdk-ai" id="cmdk-ai" aria-pressed="false" title="AI 语义搜索：模型跑在你浏览器里，按内容含义找文章（首次启用需下载约 135MB 模型，之后走浏览器缓存）">
			<span class="cmdk-ai-ico" aria-hidden="true"><?php echo pk_icon( 'sparkles', 13 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span>AI</span>
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
<?php echo pk_notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- 内部字段均已转义 ?>

<?php /* 登录弹窗：与 /login/ 整页内容同源（pk_auth_card_html），打开时地址栏同步成 /login/ */ ?>
<?php echo pk_auth_dialog_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- 内部字段均已转义 ?>

<div class="pk-drawer-backdrop" id="pk-comments-backdrop" hidden aria-hidden="true"></div>

<div class="pk-toast" id="pk-toast" role="status" aria-live="polite"><span class="pk-toast-icon"><?php echo pk_icon( 'check', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="pk-toast-text" id="pk-toast-text"></span></div>

<?php /* 全站返回顶部：footer 常驻（在 #swup-wrapper 外），Swup 换页不重建；滚动超过阈值由 app.js 淡入 */ ?>
<?php if ( pk_opt( 'ui_to_top_on' ) ) : ?>
<button class="pk-to-top" id="pk-to-top" type="button" data-pk-to-top aria-label="返回顶部" title="返回顶部"><?php echo pk_icon( 'top', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
<?php endif; ?>

<?php /* 手势翻篇时跟手显示目标文章：「下一篇：标题」/「上一篇：标题」，到尽头说明没有更多 */ ?>
<div class="pk-swipe-hint" id="pk-swipe-hint" aria-hidden="true"><span class="pk-swipe-kind" id="pk-swipe-kind"></span><span class="pk-swipe-title" id="pk-swipe-label"></span></div>

<dialog class="pk-lightbox" id="pk-lightbox" aria-label="图片查看">
	<div class="pk-lightbox-bar">
		<span class="pk-lightbox-count" id="pk-lightbox-count"></span>
		<button class="pk-lightbox-btn pk-lightbox-close" type="button" data-pk-lightbox-close aria-label="关闭" title="关闭"><?php echo pk_icon( 'close', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
	</div>
	<div class="pk-lightbox-stage" id="pk-lightbox-stage">
		<button class="pk-lightbox-btn pk-lightbox-nav" type="button" data-pk-lightbox-prev aria-label="上一张" title="上一张"><?php echo pk_icon( 'prev', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<img id="pk-lightbox-img" alt="">
		<button class="pk-lightbox-btn pk-lightbox-nav" type="button" data-pk-lightbox-next aria-label="下一张" title="下一张"><?php echo pk_icon( 'next', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
	</div>
	<p class="pk-lightbox-caption" id="pk-lightbox-caption" hidden></p>
</dialog>

<?php /* 侧栏图标统一提示气泡（黑底标签、右侧浮出）：挂在 #swup-wrapper 外，fixed 定位不受侧栏 overflow 裁切；文案由 app.js 取按钮 aria-label 注入 */ ?>
<div class="pk-side-tip" id="pk-side-tip" aria-hidden="true"></div>

<?php /* 0.12.1 资源自检哨兵：样式与脚本全内联，外部资源挂掉时也能提示刷新重试（0.13.3 起不再提供下载） */ ?>
<?php echo pk_asset_guard_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- 静态内联片段，无用户输入 ?>
<?php wp_footer(); ?>
</body>
</html>
