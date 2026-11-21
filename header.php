<?php
/**
 * 站点头部
 *
 * PC（≥1024）：左侧栏承载全部入口与图标 —— 顶部搜索框、导航与「更多」、底部登录 / 我的；
 *              顶栏只剩 Logo 图标 + 站点名称，没有菜单栏、没有右侧工具组。
 * 移动端：搜索 / 深浅色两个图标按钮（文章页右上角），搜索走弹窗。
 * 分享按钮不在头部：放在文章正文 .detail-meta 之后（见 bang_reading_html）。
 *
 * @package Naigou
 */
$bang_is_single  = is_singular() && ! is_page( array( 'user', 'login' ) ) && ! bang_is_site_page();
/* 独立文章页由服务端带上标题；首页展开层由 JS 在 applyOpen() 里写入 */
$bang_head_title = $bang_is_single ? get_the_title( get_queried_object_id() ) : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<script>try{if(localStorage.getItem('bang-aside')==='0'){document.documentElement.classList.add('bang-aside-collapsed')}}catch(e){}</script>
<?php /* 首屏就还原用户选过的深浅色，避免手动深色模式先闪一帧浅色背景 / 浅色 Logo（0.13.14） */ ?>
<script>try{var t=localStorage.getItem('bang-theme');if(t==='dark'||t==='light'){document.documentElement.setAttribute('data-theme',t)}}catch(e){}</script>
<?php wp_head(); ?>
</head>
<body <?php body_class( 'bang-body' ); ?>>
<?php wp_body_open(); ?>

<aside class="pc-aside" id="pc-aside" aria-label="站点导航">
	<div class="pc-aside-inner">
		<?php /* 搜索框在侧栏最上面：PC 不再有顶栏工具组，搜索 / 登录入口全部收进侧栏 */ ?>
		<div class="pc-side-top">
			<?php /* PC 顶栏整条移除后，品牌（Logo 图标 + 站点名称）落在侧栏最上；手机端抽屉里同样显示 */ ?>
			<a class="pc-side-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<?php $bang_side_logo = bang_logo_html( 'pc-side-logo', 28 ); ?>
				<?php if ( $bang_side_logo ) : ?>
				<?php echo $bang_side_logo; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endif; ?>
				<span class="pc-side-brand-txt"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			</a>
			<?php /* 手机端抽屉的关闭按钮（PC 侧栏里由 CSS 隐藏） */ ?>
			<button class="bang-drawer-close" type="button" id="aside-close" aria-label="关闭侧边栏" title="关闭侧边栏"><?php echo bang_icon( 'close', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<form class="pc-search" id="pc-search-form" role="search" autocomplete="off">
				<span class="pc-search-ico" aria-hidden="true"><?php echo bang_icon( 'search', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<input type="search" id="pc-search-input" name="s" placeholder="<?php echo esc_attr( __( '搜索文章', 'bang' ) ); ?>" aria-label="<?php echo esc_attr( __( '搜索文章', 'bang' ) ); ?>" autocomplete="off">
				<?php if ( bang_opt( 'ui_cmdk_on' ) ) : ?>
			<kbd class="pc-search-kbd" aria-hidden="true" title="命令面板">⌘K</kbd>
			<?php endif; ?>
				<div class="pc-search-pop" id="pc-search-results" hidden></div>
			</form>
		</div>

		<nav class="pc-nav" aria-label="主导航">
			<a class="pc-nav-link pc-nav-dup" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( __( '首页', 'bang' ) ); ?>" title="<?php echo esc_attr( __( '首页', 'bang' ) ); ?>"><?php echo bang_icon( 'home', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( __( '首页', 'bang' ) ); ?></span></a>
			<?php $bang_say_url = function_exists( 'get_post_type_archive_link' ) ? get_post_type_archive_link( 'bang_say' ) : ''; ?>
			<?php if ( $bang_say_url ) : ?>
			<a class="pc-nav-link pc-nav-dup" href="<?php echo esc_url( $bang_say_url ); ?>" aria-label="<?php echo esc_attr( __( '说说', 'bang' ) ); ?>" title="<?php echo esc_attr( __( '说说', 'bang' ) ); ?>"><?php echo bang_icon( 'message-circle', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( __( '说说', 'bang' ) ); ?></span></a>
			<?php endif; ?>
			<?php /* 分类单独做成信息页（0.13.10）：直接跳转，不再用侧栏弹层 */ ?>
			<a class="pc-nav-link pc-nav-dup" href="<?php echo esc_url( bang_site_page_url( 'categories' ) ); ?>" aria-label="<?php echo esc_attr( __( '分类', 'bang' ) ); ?>" title="<?php echo esc_attr( __( '分类', 'bang' ) ); ?>"><?php echo bang_icon( 'layers', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( __( '分类', 'bang' ) ); ?></span></a>
			<?php /* 图片集紧随分类（用户指定），从「更多」移出，与主导航同款平铺 */ ?>
			<?php if ( bang_opt( 'gallery_on' ) ) : ?>
			<a class="pc-nav-link<?php echo is_page( 'gallery' ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( bang_site_page_url( 'gallery' ) ); ?>" aria-label="<?php echo esc_attr( __( '图片集', 'bang' ) ); ?>" title="<?php echo esc_attr( __( '图片集', 'bang' ) ); ?>"><?php echo bang_icon( 'images', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( __( '图片集', 'bang' ) ); ?></span></a>
			<?php endif; ?>

			<?php /* 「更多」只留两个平铺按钮，不再套可折叠的「关于」卡片（0.13.11） */ ?>
			<p class="pc-nav-label"><?php echo esc_html( __( '更多', 'bang' ) ); ?></p>
			<a class="pc-nav-link<?php echo is_page( 'about' ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( bang_site_page_url( 'about' ) ); ?>" aria-label="<?php echo esc_attr( __( '关于我们', 'bang' ) ); ?>" title="<?php echo esc_attr( __( '关于我们', 'bang' ) ); ?>"><?php echo bang_icon( 'info', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( __( '关于我们', 'bang' ) ); ?></span></a>
			<a class="pc-nav-link<?php echo is_page( 'links' ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( bang_site_page_url( 'links' ) ); ?>" aria-label="<?php echo esc_attr( __( '友情链接', 'bang' ) ); ?>" title="<?php echo esc_attr( __( '友情链接', 'bang' ) ); ?>"><?php echo bang_icon( 'link', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( __( '友情链接', 'bang' ) ); ?></span></a>
			<?php if ( bang_opt( 'footprint_on' ) ) : ?>
			<a class="pc-nav-link<?php echo is_page( 'footprint' ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( bang_site_page_url( 'footprint' ) ); ?>" aria-label="<?php echo esc_attr( __( '足迹', 'bang' ) ); ?>" title="<?php echo esc_attr( __( '足迹', 'bang' ) ); ?>"><?php echo bang_icon( 'map-pin', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( __( '足迹', 'bang' ) ); ?></span></a>
			<?php endif; ?>
		</nav>

		<?php /* 侧栏迷你音乐播放器（总开关由后台 music_on 控制，函数内判断） */ ?>
		<?php if ( function_exists( 'bang_music_mini_html' ) ) { bang_music_mini_html(); } ?>

		<?php /* 版权 + 深浅色切换整体贴底；折叠把手已移到卡片右缘，不再参与这里排布 */ ?>
		<div class="pc-side-bottom">
			<?php
			/* 登录 / 我的：侧栏最底部。pjax 登录或退出后由 JS（bangSetUser）同步文案与状态 */
			$bang_me      = is_user_logged_in() ? wp_get_current_user() : null;
			$bang_me_name = $bang_me ? $bang_me->display_name : '';
			/* 侧栏头像圈优先显示账号真实头像（附件 ID / URL），没有才回退名字首字 */
			$bang_avatar_url = $bang_me ? bang_user_avatar_url( $bang_me, 30 ) : '';
			$bang_me_ini  = '';
			if ( '' !== $bang_me_name ) {
				$bang_me_ini = function_exists( 'mb_substr' ) ? mb_substr( $bang_me_name, 0, 1 ) : substr( $bang_me_name, 0, 1 );
			}
			?>
			<?php
			$bang_me_href = $bang_me ? home_url( '/user/' ) : home_url( '/login/' );
			?>
			<a class="pc-side-user<?php echo $bang_me ? ' is-on' : ''; ?><?php echo $bang_avatar_url ? ' has-avatar' : ''; ?>" href="<?php echo esc_url( $bang_me_href ); ?>"<?php echo $bang_me ? '' : ' data-bang-auth-open'; ?> aria-label="<?php echo esc_attr( $bang_me ? __( '个人中心', 'bang' ) : __( '登录', 'bang' ) ); ?>" title="<?php echo esc_attr( $bang_me ? __( '个人中心', 'bang' ) : __( '登录', 'bang' ) ); ?>">
				<span class="pc-side-avatar" aria-hidden="true">
					<span class="pc-side-avatar-ico"><?php echo bang_icon( 'user-round', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="pc-side-avatar-txt"><?php echo esc_html( $bang_me_ini ); ?></span>
					<img class="pc-side-avatar-img"<?php echo $bang_avatar_url ? ' src="' . esc_url( $bang_avatar_url ) . '"' : ''; ?> alt="" width="30" height="30" decoding="async">
				</span>
				<span class="pc-side-user-main">
					<span class="pc-side-user-name"><?php echo $bang_me ? esc_html( $bang_me_name ) : esc_html( __( '登录', 'bang' ) ); ?></span>
					<span class="pc-side-user-sub"><?php echo $bang_me ? esc_html( __( '个人中心', 'bang' ) ) : esc_html( __( '登录 / 注册', 'bang' ) ); ?></span>
				</span>
			</a>

			<?php /* 版权 + 侧栏折叠 / 深浅色同一行（0.13.13）：展开并排、折叠竖排 */ ?>
			<div class="pc-side-foot-row">
				<span class="pc-side-icons">
					<button class="aside-toggle" type="button" id="aside-toggle" aria-label="收起或展开侧边栏" aria-expanded="true">
						<?php echo bang_icon( 'chevron-left', 18, 'i-aside-close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo bang_icon( 'chevron-right', 18, 'i-aside-open' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</button>
					<button class="aside-toggle aside-theme theme-toggle" type="button" id="aside-theme" aria-label="切换深色模式" title="切换深色模式" aria-pressed="false">
						<?php echo bang_icon( 'moon', 18, 'i-moon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo bang_icon( 'sun', 18, 'i-sun' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</button>
					<?php /* 语言切换：从页脚移到侧栏，PC / 手机端都在这里；JS 文档级委托，多处同款生效 */ ?>
					<?php if ( function_exists( 'bang_lang_switch_html' ) ) { echo bang_lang_switch_html(); } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
			</div>
		</div>
	</div>
</aside>
<?php /* 手机端侧栏抽屉的遮罩：点它关闭（PC 由 CSS 隐藏） */ ?>
<div class="aside-mask" id="aside-mask" aria-hidden="true"></div>

<header class="site-head<?php echo $bang_is_single ? ' is-single' : ''; ?>" id="site-head">
	<div class="wrap head-inner">
		<div class="brand">
			<a class="mark" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<?php $bang_logo = bang_logo_html( 'logo-img', 32, get_bloginfo( 'name' ) ); ?>
				<?php if ( $bang_logo ) : ?>
				<?php echo $bang_logo; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endif; ?>
				<span class="mark-text"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			</a>
		</div>
		<div class="head-title" id="head-title" aria-hidden="true"><span id="head-title-text"><?php echo esc_html( $bang_head_title ); ?></span></div>
	<?php /* 工具组只在移动端用（PC 已移除：搜索在侧栏顶部、登录在侧栏底部） */ ?>
	<nav class="head-tools" aria-label="站点工具">
		<?php /* 手机端侧栏抽屉入口（PC 顶栏整条隐藏，侧栏常驻，用不到这个按钮） */ ?>
		<button class="icon-btn" type="button" id="aside-open" aria-label="打开侧边栏" title="打开侧边栏" aria-controls="pc-aside" aria-expanded="false"><?php echo bang_icon( 'menu', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<?php /* 手机端顶栏搜索框：inline 输入、输入即搜、回车进搜索页；PC 端整组 head-tools 隐藏 */ ?>
		<form class="head-search" id="head-search-form" role="search" autocomplete="off">
			<span class="head-search-ico" aria-hidden="true"><?php echo bang_icon( 'search', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<input type="search" id="head-search-input" name="s" placeholder="<?php echo esc_attr__( '搜索', 'bang' ); ?>" autocomplete="off" aria-label="<?php echo esc_attr__( '搜索文章', 'bang' ); ?>">
			<div class="head-search-pop" id="head-search-results" hidden></div>
		</form>
		<button class="icon-btn" type="button" id="search-toggle" aria-label="<?php echo esc_attr( __( '搜索', 'bang' ) ); ?>" title="<?php echo esc_attr( __( '搜索', 'bang' ) ); ?>"><?php echo bang_icon( 'search', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<?php /* 手机端音乐入口（music_on 才显示）：复用 data-bang-music-open，点开全屏歌词播放器 */ ?>
		<?php if ( bang_opt( 'music_on' ) ) : ?>
		<button class="icon-btn head-music" type="button" data-bang-music-open aria-label="<?php echo esc_attr( __( '音乐', 'bang' ) ); ?>" title="<?php echo esc_attr( __( '音乐', 'bang' ) ); ?>"><?php echo bang_icon( 'music-2', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<?php endif; ?>
		<button class="icon-btn theme-toggle" type="button" id="theme-toggle" aria-label="切换深色模式" title="切换深色模式">
			<?php echo bang_icon( 'moon', 18, 'i-moon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bang_icon( 'sun', 18, 'i-sun' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</button>
		<button class="head-icon theme-toggle" type="button" id="theme-toggle-single" aria-label="切换深色模式" title="切换深色模式">
			<?php echo bang_icon( 'moon', 18, 'i-moon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bang_icon( 'sun', 18, 'i-sun' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</button>
		<button class="head-icon" type="button" id="read-search-toggle" aria-label="搜索" title="搜索"><?php echo bang_icon( 'search', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<?php /* 用户中心入口（移动端顶栏）：登录态点头像直接进个人中心，不再展开下拉菜单；
		   PC 隐藏整个工具组，入口在侧栏底部；未登录时点开登录弹窗 */ ?>
		<?php if ( is_user_logged_in() ) : ?>
		<div class="head-user-box">
			<a class="head-user" href="<?php echo esc_url( home_url( '/user/' ) ); ?>" aria-label="<?php echo esc_attr__( '个人中心', 'bang' ); ?>" title="<?php echo esc_attr__( '个人中心', 'bang' ); ?>"><?php echo bang_icon( 'user-round', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		</div>
		<?php else : ?>
		<a class="head-user" href="<?php echo esc_url( home_url( '/login/' ) ); ?>" data-bang-auth-open aria-label="<?php echo esc_attr__( '登录', 'bang' ); ?>" title="<?php echo esc_attr__( '登录', 'bang' ); ?>"><?php echo bang_icon( 'user-round', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		<?php endif; ?>
		</nav>
	</div>
</header>
