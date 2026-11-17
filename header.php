<?php
/**
 * 站点头部
 *
 * PC（≥1024）：左侧栏承载全部入口与图标 —— 顶部搜索框、导航与「更多」、底部登录 / 我的；
 *              顶栏只剩 Logo 图标 + 站点名称，没有菜单栏、没有右侧工具组。
 * 移动端：搜索 / 深浅色两个图标按钮（文章页右上角），搜索走弹窗。
 * 分享按钮不在头部：放在文章正文 .detail-meta 之后（见 pk_reading_html）。
 *
 * @package Naigou
 */
$pk_is_single  = is_singular() && ! is_page( array( 'user', 'login' ) ) && ! pk_is_site_page();
/* 独立文章页由服务端带上标题；首页展开层由 JS 在 applyOpen() 里写入 */
$pk_head_title = $pk_is_single ? get_the_title( get_queried_object_id() ) : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<script>try{if(localStorage.getItem('pk-aside')==='0'){document.documentElement.classList.add('pk-aside-collapsed')}}catch(e){}</script>
<?php /* 首屏就还原用户选过的深浅色，避免手动深色模式先闪一帧浅色背景 / 浅色 Logo（0.13.14） */ ?>
<script>try{var t=localStorage.getItem('pk-theme');if(t==='dark'||t==='light'){document.documentElement.setAttribute('data-theme',t)}}catch(e){}</script>
<?php wp_head(); ?>
</head>
<body <?php body_class( 'pk-body' ); ?>>
<?php wp_body_open(); ?>

<aside class="pc-aside" id="pc-aside" aria-label="站点导航">
	<div class="pc-aside-inner">
		<?php /* 搜索框在侧栏最上面：PC 不再有顶栏工具组，搜索 / 登录入口全部收进侧栏 */ ?>
		<div class="pc-side-top">
			<?php /* PC 顶栏整条移除后，品牌（Logo 图标 + 站点名称）落在侧栏最上；手机端抽屉里同样显示 */ ?>
			<a class="pc-side-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<?php $pk_side_logo = pk_logo_html( 'pc-side-logo', 28 ); ?>
				<?php if ( $pk_side_logo ) : ?>
				<?php echo $pk_side_logo; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endif; ?>
				<span class="pc-side-brand-txt"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			</a>
			<?php /* 手机端抽屉的关闭按钮（PC 侧栏里由 CSS 隐藏） */ ?>
			<button class="pk-drawer-close" type="button" id="aside-close" aria-label="关闭侧边栏" title="关闭侧边栏"><?php echo pk_icon( 'close', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<form class="pc-search" id="pc-search-form" role="search" autocomplete="off">
				<span class="pc-search-ico" aria-hidden="true"><?php echo pk_icon( 'search', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<input type="search" id="pc-search-input" name="s" placeholder="<?php echo esc_attr( __( '搜索文章', 'pianke' ) ); ?>" aria-label="<?php echo esc_attr( __( '搜索文章', 'pianke' ) ); ?>" autocomplete="off">
				<?php if ( pk_opt( 'ui_cmdk_on' ) ) : ?>
			<kbd class="pc-search-kbd" aria-hidden="true" title="命令面板">⌘K</kbd>
			<?php endif; ?>
				<div class="pc-search-pop" id="pc-search-results" hidden></div>
			</form>
		</div>

		<nav class="pc-nav" aria-label="主导航">
			<a class="pc-nav-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( __( '首页', 'pianke' ) ); ?>" title="<?php echo esc_attr( __( '首页', 'pianke' ) ); ?>"><?php echo pk_icon( 'home', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( __( '首页', 'pianke' ) ); ?></span></a>
			<?php $pk_say_url = function_exists( 'get_post_type_archive_link' ) ? get_post_type_archive_link( 'pk_say' ) : ''; ?>
			<?php if ( $pk_say_url ) : ?>
			<a class="pc-nav-link" href="<?php echo esc_url( $pk_say_url ); ?>" aria-label="<?php echo esc_attr( __( '说说', 'pianke' ) ); ?>" title="<?php echo esc_attr( __( '说说', 'pianke' ) ); ?>"><?php echo pk_icon( 'message-circle', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( __( '说说', 'pianke' ) ); ?></span></a>
			<?php endif; ?>
			<?php /* 分类单独做成信息页（0.13.10）：直接跳转，不再用侧栏弹层 */ ?>
			<a class="pc-nav-link" href="<?php echo esc_url( pk_site_page_url( 'categories' ) ); ?>" aria-label="<?php echo esc_attr( __( '分类', 'pianke' ) ); ?>" title="<?php echo esc_attr( __( '分类', 'pianke' ) ); ?>"><?php echo pk_icon( 'layers', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( __( '分类', 'pianke' ) ); ?></span></a>

			<?php /* 「更多」只留两个平铺按钮，不再套可折叠的「关于」卡片（0.13.11） */ ?>
			<p class="pc-nav-label"><?php echo esc_html( __( '更多', 'pianke' ) ); ?></p>
			<a class="pc-nav-link<?php echo is_page( 'about' ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( pk_site_page_url( 'about' ) ); ?>" aria-label="<?php echo esc_attr( __( '关于我们', 'pianke' ) ); ?>" title="<?php echo esc_attr( __( '关于我们', 'pianke' ) ); ?>"><?php echo pk_icon( 'info', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( __( '关于我们', 'pianke' ) ); ?></span></a>
			<a class="pc-nav-link<?php echo is_page( 'links' ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( pk_site_page_url( 'links' ) ); ?>" aria-label="<?php echo esc_attr( __( '友情链接', 'pianke' ) ); ?>" title="<?php echo esc_attr( __( '友情链接', 'pianke' ) ); ?>"><?php echo pk_icon( 'link', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( __( '友情链接', 'pianke' ) ); ?></span></a>
		</nav>

		<?php /* 版权 + 深浅色切换整体贴底；折叠把手已移到卡片右缘，不再参与这里排布 */ ?>
		<div class="pc-side-bottom">
			<?php
			/* 登录 / 我的：侧栏最底部。pjax 登录或退出后由 JS（pkSetUser）同步文案与状态 */
			$pk_me      = is_user_logged_in() ? wp_get_current_user() : null;
			$pk_me_name = $pk_me ? $pk_me->display_name : '';
			/* 侧栏头像圈优先显示账号真实头像（附件 ID / URL），没有才回退名字首字 */
			$pk_avatar_url = $pk_me ? pk_user_avatar_url( $pk_me, 30 ) : '';
			$pk_me_ini  = '';
			if ( '' !== $pk_me_name ) {
				$pk_me_ini = function_exists( 'mb_substr' ) ? mb_substr( $pk_me_name, 0, 1 ) : substr( $pk_me_name, 0, 1 );
			}
			?>
			<?php
			$pk_me_href = $pk_me ? home_url( '/user/' ) : home_url( '/login/' );
			?>
			<a class="pc-side-user<?php echo $pk_me ? ' is-on' : ''; ?><?php echo $pk_avatar_url ? ' has-avatar' : ''; ?>" href="<?php echo esc_url( $pk_me_href ); ?>"<?php echo $pk_me ? '' : ' data-pk-auth-open'; ?> aria-label="<?php echo esc_attr( $pk_me ? __( '个人中心', 'pianke' ) : __( '登录', 'pianke' ) ); ?>" title="<?php echo esc_attr( $pk_me ? __( '个人中心', 'pianke' ) : __( '登录', 'pianke' ) ); ?>">
				<span class="pc-side-avatar" aria-hidden="true">
					<span class="pc-side-avatar-ico"><?php echo pk_icon( 'user-round', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="pc-side-avatar-txt"><?php echo esc_html( $pk_me_ini ); ?></span>
					<img class="pc-side-avatar-img"<?php echo $pk_avatar_url ? ' src="' . esc_url( $pk_avatar_url ) . '"' : ''; ?> alt="" width="30" height="30" decoding="async">
				</span>
				<span class="pc-side-user-main">
					<span class="pc-side-user-name"><?php echo $pk_me ? esc_html( $pk_me_name ) : esc_html( __( '登录', 'pianke' ) ); ?></span>
					<span class="pc-side-user-sub"><?php echo $pk_me ? esc_html( __( '个人中心', 'pianke' ) ) : esc_html( __( '登录 / 注册', 'pianke' ) ); ?></span>
				</span>
			</a>

			<?php /* 版权 + 侧栏折叠 / 深浅色同一行（0.13.13）：展开并排、折叠竖排 */ ?>
			<div class="pc-side-foot-row">
				<div class="pc-foot">
					<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
					<?php $pk_icp = pk_opt( 'icp' ); ?>
					<?php if ( $pk_icp ) : ?>
						<span class="pc-icp"><?php echo esc_html( $pk_icp ); ?></span>
					<?php endif; ?>
				</div>
				<span class="pc-side-icons">
					<button class="aside-toggle" type="button" id="aside-toggle" aria-label="收起或展开侧边栏" aria-expanded="true">
						<?php echo pk_icon( 'chevron-left', 18, 'i-aside-close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo pk_icon( 'chevron-right', 18, 'i-aside-open' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</button>
					<button class="aside-toggle aside-theme theme-toggle" type="button" id="aside-theme" aria-label="切换深色模式" title="切换深色模式" aria-pressed="false">
						<?php echo pk_icon( 'moon', 18, 'i-moon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo pk_icon( 'sun', 18, 'i-sun' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</button>
				</span>
			</div>
		</div>
	</div>
</aside>
<?php /* 手机端侧栏抽屉的遮罩：点它关闭（PC 由 CSS 隐藏） */ ?>
<div class="aside-mask" id="aside-mask" aria-hidden="true"></div>

<header class="site-head<?php echo $pk_is_single ? ' is-single' : ''; ?>" id="site-head">
	<div class="wrap head-inner">
		<div class="brand">
			<a class="mark" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<?php $pk_logo = pk_logo_html( 'logo-img', 32, get_bloginfo( 'name' ) ); ?>
				<?php if ( $pk_logo ) : ?>
				<?php echo $pk_logo; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endif; ?>
				<span class="mark-text"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			</a>
		</div>
		<div class="head-title" id="head-title" aria-hidden="true"><span id="head-title-text"><?php echo esc_html( $pk_head_title ); ?></span></div>
	<?php /* 工具组只在移动端用（PC 已移除：搜索在侧栏顶部、登录在侧栏底部） */ ?>
	<nav class="head-tools" aria-label="站点工具">
		<?php /* 手机端侧栏抽屉入口（PC 顶栏整条隐藏，侧栏常驻，用不到这个按钮） */ ?>
		<button class="icon-btn" type="button" id="aside-open" aria-label="打开侧边栏" title="打开侧边栏" aria-controls="pc-aside" aria-expanded="false"><?php echo pk_icon( 'menu', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<button class="icon-btn" type="button" id="search-toggle" aria-label="<?php echo esc_attr( __( '搜索', 'pianke' ) ); ?>" title="<?php echo esc_attr( __( '搜索', 'pianke' ) ); ?>"><?php echo pk_icon( 'search', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<button class="icon-btn theme-toggle" type="button" id="theme-toggle" aria-label="切换深色模式" title="切换深色模式">
			<?php echo pk_icon( 'moon', 18, 'i-moon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo pk_icon( 'sun', 18, 'i-sun' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</button>
		<button class="head-icon theme-toggle" type="button" id="theme-toggle-single" aria-label="切换深色模式" title="切换深色模式">
			<?php echo pk_icon( 'moon', 18, 'i-moon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo pk_icon( 'sun', 18, 'i-sun' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</button>
		<button class="head-icon" type="button" id="read-search-toggle" aria-label="搜索" title="搜索"><?php echo pk_icon( 'search', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<?php /* 用户中心入口（移动端顶栏）：PC 隐藏整个工具组，入口在侧栏底部；未登录时点开登录弹窗 */ ?>
		<a class="head-user" href="<?php echo esc_url( is_user_logged_in() ? home_url( '/user/' ) : home_url( '/login/' ) ); ?>"<?php echo is_user_logged_in() ? '' : ' data-pk-auth-open'; ?> aria-label="<?php echo is_user_logged_in() ? '用户中心' : '登录'; ?>" title="<?php echo is_user_logged_in() ? '用户中心' : '登录'; ?>"><?php echo pk_icon( 'user-round', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		</nav>
	</div>
</header>
