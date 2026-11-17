<?php
/**
 * Bang · 后台设置（常规 / 安全设置 / SEO / 阅读解锁 / 高级）
 *
 * 全部选项存为单个 pk_options 数组，避免逐条查询；代码类字段（统计脚本等）
 * 只有具备 unfiltered_html 权限的管理员才能保存原文，其余走 kses 过滤。
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PK_OPTION_KEY' ) ) {
	define( 'PK_OPTION_KEY', 'pk_options' );
}

/**
 * 默认值。新增字段只需在这里与 pk_setting_fields() 各加一处。
 */
function pk_default_options() {
	return array(
		'logo_url'         => 'https://cdn.naigou.cn/logo.png',
		'logo_dark_url'    => '',
		'site_icon_url'    => '',
		'posts_per_page'   => 30,
		'slides_mode'      => 'random',
		'slides_count'     => 5,
		'slides_posts'     => '',
		'ticker_on'        => 1,
		'ticker_tag'       => '公告',
		'ticker_text'      => pk_ticker_default_text(),
		'author_avatar'    => '',
		/* 作者卡签名与社交（0.13.28）：社交链接留空则不显示该图标 */
		'author_bio_mode'  => 'profile',
		'author_bio_custom'=> '',
		'author_bio_api'   => 'https://v1.hitokoto.cn/?encode=json',
		'author_bio_fallback' => '落笔有声，留白有度。',
		'social_qq'        => '',
		'social_wechat'    => '',
		'social_douyin'    => '',
		'social_x'         => '',
		'social_weibo'     => '',
		'social_github'    => '',
		'social_bilibili'  => '',
		'subscribe_on'     => 1,
		'subscribe_title'  => '订阅文章推送',
		'subscribe_desc'   => '无骚扰邮件，随时可取消订阅。',
		'notice_on'        => 1,
		'notice_title'     => '网站公告',
		'notice_text'      => "欢迎来到 Bang。\n点开卡片即可阅读，← → 翻到上一篇 / 下一篇。",
		'notice_later'     => '我记住啦~',
		'notice_ok'        => '确认',
		'footer_text'      => '落笔有声，留白有度',
		'contact_email'    => '',
		/* SMTP 邮件（0.13.28）：smtp_on 关闭时 wp_mail 走 WordPress 默认发信 */
		'smtp_on'          => 0,
		'smtp_host'        => '',
		'smtp_port'        => 465,
		'smtp_encryption'  => 'ssl',
		'smtp_user'        => '',
		'smtp_pass'        => '',
		'smtp_from'        => '',
		'smtp_from_name'   => '',
		/* 站点时区：留空 = 跟随 WordPress 设置 → 常规 → 时区；填了就写 timezone_string */
		'site_timezone'    => '',
		'smtp_test_to'     => '',
		'icp'              => '',
		'show_comments'    => 1,
		'copyright_on'     => 1,
		'copyright_license'=> 'by-nc-sa',
		'copyright_note'   => '',
		'allow_register'   => 1,
		/* 注册邮箱后缀限制：留空 = 不限制；一行一个后缀（@qq.com 或 qq.com），
		   以 - 开头为拒绝列表，否则为允许列表 */
		'register_email_domains' => '',
		/* 人机校验：默认自研滑块；切到极验 v4 且填齐 ID/Key 才启用 */
		'captcha_mode'         => 'local',
		'geetest_id'           => '',
		'geetest_key'          => '',
		'geetest_validate_url' => 'https://gcaptcha4.geetest.com/validate',
		/* 违禁词审核：词表只读取主题 inc/data 目录下所有 .txt（递归）；
		   词表为空时零影响 */
		'moderation_on'     => 1,
		'moderation_action' => 'hold',
		/* 权重阈值：留空 / 0 = 命中任一词即算；填了就要求累计到该分才算命中 */
		'moderation_threshold' => 0,
		/* 界面与交互：固定默认值，全部开启。按约定后台不再提供这组开关菜单，
		   这些键只作为模板 / JS 的常量兜底，视觉与行为保持零变化。 */
		'ui_sweep_on'          => 1,
		'ui_sweep_ms'          => 500,
		'ui_progress_on'       => 1,
		'ui_post_nav_on'       => 1,
		'ui_swipe_on'          => 1,
		'ui_key_nav_on'        => 1,
		'ui_to_top_on'         => 1,
		'ui_to_top_offset'     => 420,
		'ui_link_dialog_on'    => 1,
		'ui_cmdk_on'           => 1,
		'ui_cmdk_ai_on'        => 1,
		'ui_code_highlight_on' => 1,
		'ui_code_copy_on'      => 1,
		'ui_lightbox_on'       => 1,
		'ui_share_on'          => 1,
		'ui_morph_on'          => 1,
		'ui_footer_nav'        => "区块展厅 | /blocks/\n主题介绍 | /theme/\n排版标签 | /html-tag/",
		'seo_home_title'   => '',
		'seo_home_desc'    => 'Bang，一个用 ink 写作的博客：收录文章、短动态与图片内容。',
		'title_sep'        => '–',
		'seo_keywords'     => '',
		'seo_canonical'    => 1,
		'seo_og'           => 1,
		'seo_twitter'      => 1,
		'seo_jsonld'       => 1,
		'og_default_image' => '',
		'twitter_site'     => '',
		'noindex_search'   => 1,
		'noindex_archive'  => 0,
		'noindex_404'      => 1,
		'google_verify'    => '',
		'bing_verify'      => '',
		'baidu_verify'     => '',
		'clean_wp_head'    => 1,
		'lazy_blocks_css'  => 1,
		'minify_assets'    => 1,
		'asset_guard'      => 1,
		'head_code'        => '',
		'footer_code'      => '',
		/* 0.13.22 阅读解锁：四种方式默认全开，后台只保留机器人与密码配置。 */
		'unlock_ttl'       => 72,
		'unlock_group_name'=> '交流群',
		'unlock_group_url' => '',
		'unlock_bot_name'  => '机器人',
		'unlock_hint'      => '',
		'unlock_secret'    => '',
		'unlock_password'  => '',
	);
}

/**
 * 读取全部设置（本请求内缓存）。
 */
function pk_options() {
	static $cache = null;
	if ( null === $cache ) {
		$saved = get_option( PK_OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		$cache = wp_parse_args( $saved, pk_default_options() );
	}
	return $cache;
}

/**
 * 读取单项设置。
 */
function pk_opt( $key ) {
	$options = pk_options();
	return isset( $options[ $key ] ) ? $options[ $key ] : '';
}

/**
 * 界面与交互配置：集中注入到 pkFront.ui，供 app.js 读取。
 * 后台已移除这组开关菜单，逐项按固定默认值（全为 1）读取，视觉与行为零变化。
 */
function pk_ui_config() {
	return array(
		'sweepOn'       => (int) pk_opt( 'ui_sweep_on' ),
		'sweepMs'       => max( 100, min( 1500, (int) pk_opt( 'ui_sweep_ms' ) ) ),
		'progressOn'    => (int) pk_opt( 'ui_progress_on' ),
		'postNavOn'     => (int) pk_opt( 'ui_post_nav_on' ),
		'swipeOn'       => (int) pk_opt( 'ui_swipe_on' ),
		'keyNavOn'      => (int) pk_opt( 'ui_key_nav_on' ),
		'toTopOn'       => (int) pk_opt( 'ui_to_top_on' ),
		'toTopOffset'   => max( 100, min( 2000, (int) pk_opt( 'ui_to_top_offset' ) ) ),
		'linkDialogOn'  => (int) pk_opt( 'ui_link_dialog_on' ),
		'cmdkOn'        => (int) pk_opt( 'ui_cmdk_on' ),
		'cmdkAiOn'      => (int) pk_opt( 'ui_cmdk_ai_on' ),
		'codeHighlight' => (int) pk_opt( 'ui_code_highlight_on' ),
		'codeCopy'      => (int) pk_opt( 'ui_code_copy_on' ),
		'lightboxOn'    => (int) pk_opt( 'ui_lightbox_on' ),
		'shareOn'       => (int) pk_opt( 'ui_share_on' ),
		'morphOn'       => (int) pk_opt( 'ui_morph_on' ),
	);
}

/**
 * 页脚导航：一行一条「名称 | 链接 | blank」。
 * 链接可写站内路径（/about/）或完整网址，第三列填 blank 表示新窗口打开。
 * 留空返回空数组，footer.php 据此整行不输出。
 */
function pk_footer_nav_items() {
	$raw = trim( (string) pk_opt( 'ui_footer_nav' ) );
	$out = array();
	if ( '' === $raw ) {
		return $out;
	}
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '|', $line ) );
		$name  = isset( $parts[0] ) ? $parts[0] : '';
		$url   = isset( $parts[1] ) ? $parts[1] : '';
		if ( '' === $name || '' === $url ) {
			continue;
		}
		if ( 0 === strpos( $url, '/' ) ) {
			/* 单段路径（/blocks/）先交给 pk_site_page_url 解析真实 permalink：
			   固定链接为 /%post_id%/ 时 get_permalink() 与 /slug/ 并不一致。 */
			$slug = trim( $url, '/' );
			if ( '' !== $slug && false === strpos( $slug, '/' ) && function_exists( 'pk_site_page_url' ) ) {
				$maybe = pk_site_page_url( $slug );
				$url   = $maybe ? $maybe : home_url( $url );
			} else {
				$url = home_url( $url );
			}
		} elseif ( ! preg_match( '#^https?://#i', $url ) ) {
			$url = home_url( '/' . ltrim( $url, '/' ) );
		}
		$out[] = array(
			'name'   => $name,
			'url'    => esc_url_raw( $url ),
			'blank'  => isset( $parts[2] ) && 'blank' === strtolower( $parts[2] ),
		);
	}

	/* 站点固定入口：隐私政策 / 用户协议 / 站点地图 / RSS 订阅始终出现在页脚末尾。
	   后台 ui_footer_nav 里若已填过同名条目，按名称去重，不重复输出。 */
	$pk_fixed = array(
		array( 'name' => __( '隐私政策', 'pianke' ), 'url' => function_exists( 'pk_site_page_url' ) ? pk_site_page_url( 'privacy' ) : home_url( '/privacy/' ), 'blank' => false ),
		array( 'name' => __( '用户协议', 'pianke' ), 'url' => function_exists( 'pk_site_page_url' ) ? pk_site_page_url( 'terms' ) : home_url( '/terms/' ), 'blank' => false ),
		array( 'name' => __( '站点地图', 'pianke' ), 'url' => home_url( '/wp-sitemap.xml' ), 'blank' => true ),
		array( 'name' => __( 'RSS订阅', 'pianke' ), 'url' => home_url( '/feed/' ), 'blank' => true ),
	);
	$pk_names = wp_list_pluck( $out, 'name' );
	foreach ( $pk_fixed as $pk_item ) {
		if ( in_array( $pk_item['name'], $pk_names, true ) ) {
			continue;
		}
		$out[]      = array(
			'name'  => $pk_item['name'],
			'url'   => esc_url_raw( $pk_item['url'] ),
			'blank' => ! empty( $pk_item['blank'] ),
		);
		$pk_names[] = $pk_item['name'];
	}

	return $out;
}

/**
 * 字段定义。type: text / url / number / textarea / code / checkbox / select
 */
function pk_setting_fields() {
	return array(
		'general'  => array(
			'label'  => __( '常规', 'pianke' ),
			'desc'   => __( '站点外观与内容的基础设置，保存后立即在前台生效。', 'pianke' ),
			'fields' => array(
				array( 'key' => 'site_icon_url', 'type' => 'url', 'media' => true, 'label' => __( '站点图标（Favicon）', 'pianke' ), 'desc' => __( '浏览器标签页、书签与添加到桌面的图标，建议 512×512 的 PNG / SVG / ICO。留空则使用 WordPress 自带的站点图标（外观 → 自定义 → 站点身份）。', 'pianke' ) ),
				array( 'key' => 'logo_url', 'type' => 'url', 'media' => true, 'label' => __( '白色模式 Logo（浅色背景）', 'pianke' ), 'desc' => __( '白底 / 浅色模式下侧栏与顶栏显示的 Logo，默认用 CDN 彩色原图；留空则回退为文字站名。也用作作者卡头像的最后兜底。', 'pianke' ) ),
				array( 'key' => 'logo_dark_url', 'type' => 'url', 'media' => true, 'label' => __( '黑色模式 Logo（深色背景）', 'pianke' ), 'desc' => __( '黑底 / 深色模式下显示的 Logo，通常放反白版本。留空则沿用「白色模式 Logo」，两种模式显示同一张。', 'pianke' ) ),
				array( 'key' => 'posts_per_page', 'type' => 'number', 'label' => __( '首页文章数', 'pianke' ), 'desc' => __( '卡片流一次输出的文章数量，建议 20–40，太大首屏 HTML 会变重。', 'pianke' ), 'min' => 1, 'max' => 60 ),
				array(
					'key'     => 'slides_mode',
					'type'    => 'select',
					'label'   => __( '首页幻灯片', 'pianke' ),
					'desc'    => __( 'PC 端首页顶部的轮播卡片，只在首页显示（分类页不出现）。', 'pianke' ),
					'choices' => array(
						'random' => __( '自动：随机挑选文章', 'pianke' ),
						'manual' => __( '手动：按下面填写的 ID 顺序', 'pianke' ),
						'off'    => __( '关闭：不显示幻灯片', 'pianke' ),
					),
				),
				array( 'key' => 'slides_count', 'type' => 'number', 'label' => __( '幻灯片数量', 'pianke' ), 'desc' => __( '自动模式下随机取几篇，建议 3–5。', 'pianke' ), 'min' => 1, 'max' => 8 ),
				array( 'key' => 'slides_posts', 'type' => 'text', 'label' => __( '指定文章 ID', 'pianke' ), 'desc' => __( '仅手动模式生效，逗号分隔，例如 75,7,12；顺序即播放顺序。留空或 ID 全部无效时回退随机。', 'pianke' ) ),
				array( 'key' => 'ticker_on', 'type' => 'checkbox', 'label' => __( '首页公告栏', 'pianke' ), 'desc' => __( '首页幻灯片下方的一条横向滚动公告，鼠标悬停暂停。', 'pianke' ) ),
				array( 'key' => 'ticker_tag', 'type' => 'text', 'label' => __( '公告栏标签', 'pianke' ), 'desc' => __( '条带最左侧黑底小标签的文字，留空则只用底色不显示标签。', 'pianke' ) ),
				array( 'key' => 'ticker_text', 'type' => 'textarea', 'label' => __( '公告栏内容', 'pianke' ), 'rows' => 6, 'placeholder' => pk_ticker_default_text(), 'desc' => __( '只滚这里写的内容，不再抓取文章。一行一条，格式「文字 | 链接 | blank」：链接可写站内路径（/about/）或完整网址，第三列填 blank 表示新窗口，后两列可省略。留空则滚灰色占位里的内置文案；要整条隐藏请关闭上方开关。', 'pianke' ) ),
				array( 'key' => 'author_avatar', 'type' => 'url', 'media' => true, 'label' => __( '作者头像', 'pianke' ), 'desc' => __( '文章右侧作者卡优先用这张图；留空则依次回退到用户资料头像、站点 Logo、姓名首字。不走 Gravatar，离线也不破图。', 'pianke' ) ),
				array(
					'key'     => 'author_bio_mode',
					'type'    => 'select',
					'label'   => __( '作者卡签名来源', 'pianke' ),
					'desc'    => __( '文章右侧作者卡头像下的那行签名文字。作者资料 = 用 WordPress 用户的个人简介；自定义 = 用下方填写的固定文案；一言接口 = 每次打开文章从下方 API 随机取一句（如「一言」Hitokoto）。', 'pianke' ),
					'choices' => array(
						'profile' => __( '作者资料（用户个人简介）', 'pianke' ),
						'custom'  => __( '自定义文案', 'pianke' ),
						'api'     => __( '一言接口（随机语录）', 'pianke' ),
					),
				),
				array( 'key' => 'author_bio_custom', 'type' => 'textarea', 'rows' => 3, 'label' => __( '自定义签名文案', 'pianke' ), 'desc' => __( '仅在签名来源选「自定义文案」时生效。支持换行，留空则不显示签名。', 'pianke' ) ),
				array( 'key' => 'author_bio_api', 'type' => 'text', 'label' => __( '一言接口地址', 'pianke' ), 'desc' => __( '仅在签名来源选「一言接口」时生效。返回一个 JSON 对象（取 hitokoto / content / text / quote 字段）或直接返回纯文本的接口均可，默认 https://v1.hitokoto.cn/?encode=json 。跨域失败时自动回退到下方兜底文案。', 'pianke' ) ),
				array( 'key' => 'author_bio_fallback', 'type' => 'text', 'label' => __( '一言兜底文案', 'pianke' ), 'desc' => __( '一言接口请求失败或超时（3 秒）时显示的文字；也用作接口返回为空时的默认值。', 'pianke' ) ),
				array( 'key' => 'social_qq', 'type' => 'text', 'label' => __( 'QQ', 'pianke' ), 'desc' => __( '作者卡社交图标链接，填完整地址（如 https://q.qlogo.cn/... 或 QQ 空间主页）。留空不显示该图标。', 'pianke' ) ),
				array( 'key' => 'social_wechat', 'type' => 'text', 'label' => __( '微信', 'pianke' ), 'desc' => __( '微信号或二维码图片 / 说明页地址。图标只作展示与复制提示，移动端不会自动打开微信。留空不显示。', 'pianke' ) ),
				array( 'key' => 'social_douyin', 'type' => 'text', 'label' => __( '抖音', 'pianke' ), 'desc' => __( '抖音主页完整地址，留空不显示该图标。', 'pianke' ) ),
				array( 'key' => 'social_x', 'type' => 'text', 'label' => __( 'X（原 Twitter）', 'pianke' ), 'desc' => __( 'X 主页完整地址，留空不显示该图标。', 'pianke' ) ),
				array( 'key' => 'social_weibo', 'type' => 'text', 'label' => __( '微博', 'pianke' ), 'desc' => __( '微博主页完整地址，留空不显示该图标。', 'pianke' ) ),
				array( 'key' => 'social_github', 'type' => 'text', 'label' => __( 'GitHub', 'pianke' ), 'desc' => __( 'GitHub 主页完整地址，留空不显示该图标。', 'pianke' ) ),
				array( 'key' => 'social_bilibili', 'type' => 'text', 'label' => __( '哔哩哔哩', 'pianke' ), 'desc' => __( 'B 站主页完整地址，留空不显示该图标。', 'pianke' ) ),
				array( 'key' => 'subscribe_on', 'type' => 'checkbox', 'label' => __( '首页订阅卡片', 'pianke' ), 'desc' => __( '在首页文章列表与分页器下方显示邮箱订阅卡片。', 'pianke' ) ),
				array( 'key' => 'subscribe_title', 'type' => 'text', 'label' => __( '订阅标题', 'pianke' ), 'desc' => __( '留空回退为「订阅文章推送」。', 'pianke' ) ),
				array( 'key' => 'subscribe_desc', 'type' => 'text', 'label' => __( '订阅说明', 'pianke' ), 'desc' => __( '标题下面的一行小字，留空则不显示。', 'pianke' ) ),
				array( 'key' => 'notice_on', 'type' => 'checkbox', 'label' => __( '全站公告弹窗', 'pianke' ), 'desc' => __( '访客首次访问任意页面时居中弹出。点「确认」后永久不再显示；「我记住啦」只关本次。改了标题或正文会重新弹一次。', 'pianke' ) ),
				array( 'key' => 'notice_title', 'type' => 'text', 'label' => __( '公告标题', 'pianke' ), 'desc' => __( '留空回退为「网站公告」。', 'pianke' ) ),
				array( 'key' => 'notice_text', 'type' => 'textarea', 'label' => __( '公告正文', 'pianke' ), 'rows' => 4, 'desc' => __( '纯文本，换行自动分段；留空则不弹窗。', 'pianke' ) ),
				array( 'key' => 'notice_later', 'type' => 'text', 'label' => __( '次要按钮文案', 'pianke' ), 'desc' => __( '只关本次访问，留空回退为「我记住啦~」。', 'pianke' ) ),
				array( 'key' => 'notice_ok', 'type' => 'text', 'label' => __( '确认按钮文案', 'pianke' ), 'desc' => __( '点击后永久不再弹，留空回退为「确认」。', 'pianke' ) ),
				array( 'key' => 'footer_text', 'type' => 'text', 'label' => __( '页脚文字', 'pianke' ), 'desc' => __( '显示在站点名之后。', 'pianke' ) ),
				array( 'key' => 'contact_email', 'type' => 'text', 'label' => __( '联系邮箱', 'pianke' ), 'desc' => __( '显示在「联系」页；有人提交友链申请时发通知到这个邮箱，留空则使用管理员邮箱。', 'pianke' ) ),
				array(
					'key'     => 'site_timezone',
					'type'    => 'select',
					'label'   => __( '站点时区', 'pianke' ),
					'desc'    => __( '决定文章时间、归档日期与邮件时间戳的显示时区。留空 = 跟随「设置 → 常规 → 时区」；选了自定义时区会直接写入 WordPress 的 timezone_string，日期、RSS 与后台列表一起生效。', 'pianke' ),
					'choices' => pk_timezone_choices(),
				),
				array( 'key' => 'icp', 'type' => 'text', 'label' => __( '备案号', 'pianke' ), 'desc' => __( '可选，显示在页脚。', 'pianke' ) ),
				array( 'key' => 'show_comments', 'type' => 'checkbox', 'label' => __( '显示评论区', 'pianke' ), 'desc' => __( '关闭后文章页不再输出评论列表与表单。', 'pianke' ) ),
				array( 'key' => 'copyright_on', 'type' => 'checkbox', 'label' => __( '文章底部版权声明', 'pianke' ), 'desc' => __( '在正文末尾输出作者、首发链接与许可协议；首页展开层与独立文章页同步显示。', 'pianke' ) ),
				array(
					'key'     => 'copyright_license',
					'type'    => 'select',
					'label'   => __( '默认许可协议', 'pianke' ),
					'desc'    => __( '只影响版权声明里的协议文案，不会给页面加额外标记。', 'pianke' ),
					'choices' => array(
						'by-nc-sa'   => __( 'CC BY-NC-SA 4.0（署名-非商业-相同方式共享）', 'pianke' ),
						'by'         => __( 'CC BY 4.0（署名）', 'pianke' ),
						'all-rights' => __( '保留所有权利', 'pianke' ),
						'none'       => __( '不写许可协议', 'pianke' ),
					),
				),
				array( 'key' => 'copyright_note', 'type' => 'textarea', 'label' => __( '版权补充说明', 'pianke' ), 'rows' => 3, 'desc' => __( '可选，纯文本，换行自动分段。例如「转载请先邮件联系」。', 'pianke' ) ),
			),
		),
	'safety' => array(
		'label'  => __( '安全设置', 'pianke' ),
		'desc'   => __( '注册限制、违禁词词表与人机校验。注册邮箱后缀限制只作用于「用户中心」注册；违禁词只从主题 inc/data 目录读取——把 .txt 放进去就自动识别，后台不用填词也不用填路径；人机校验默认自研滑块，也可切换到极验 v4。', 'pianke' ),
		'fields' => array(
			array( 'key' => 'allow_register', 'type' => 'checkbox', 'label' => __( '开放注册', 'pianke' ), 'desc' => __( '开启后可在「用户中心」自行注册账号（同步 WordPress 的「任何人都可以注册」）。关闭则只能由管理员建号。', 'pianke' ) ),
			array( 'key' => 'register_email_domains', 'type' => 'textarea', 'label' => __( '注册邮箱后缀限制', 'pianke' ), 'rows' => 5, 'placeholder' => "留空 = 不限制\n@qq.com\n@gmail.com\n# 以 - 开头为拒绝列表\n-example.com", 'desc' => __( '留空则不限制。一行一个后缀，可写 @qq.com 或 qq.com；以 - 开头表示拒绝该后缀（如 -qq.com 表示禁止 qq.com），其余行组成允许列表——只允许列表里的后缀注册。', 'pianke' ) ),
			array( 'key' => 'moderation_on', 'type' => 'checkbox', 'label' => __( '启用违禁词审核', 'pianke' ), 'desc' => __( '关闭后完全跳过扫描，已有词表不再生效。', 'pianke' ) ),
			array( 'key' => 'moderation_threshold', 'type' => 'number', 'label' => __( '命中阈值（累计权重）', 'pianke' ), 'desc' => __( '留空或 0 = 命中任一词即算命中（老行为）。填了数字（如 10）就要求同一条内容里命中的词权重加起来达到这个数才算命中——单独一个低权重词不会被拦。', 'pianke' ), 'min' => 0, 'max' => 100 ),
			array(
				'key'     => 'moderation_action',
				'type'    => 'select',
				'label'   => __( '命中后的动作', 'pianke' ),
				'desc'    => __( '待审核：评论照常入库但需管理员放行，前端提示「待审核后显示」；直接拒绝：不入库并提示修改；替换为 ***：违禁词打码后照常显示。', 'pianke' ),
				'choices' => array(
					'hold'   => __( '待审核（推荐）', 'pianke' ),
					'reject' => __( '直接拒绝提交', 'pianke' ),
					'mask'   => __( '替换为 ***', 'pianke' ),
				),
			),
			array( 'key' => 'captcha_mode', 'type' => 'select', 'label' => __( '人机校验 · 验证方式', 'pianke' ), 'desc' => __( '注册与评论前的验证方式。自研滑块：无第三方依赖、内网与离线都能用。极验 v4：需填下方 Captcha ID 与 Key；ID / Key 没配齐、脚本或接口连不上时，前台自动回退到自研滑块。', 'pianke' ), 'choices' => array( 'local' => __( '自研滑块（默认，离线可用）', 'pianke' ), 'geetest' => __( '极验行为验证 v4', 'pianke' ) ) ),
			array( 'key' => 'geetest_id', 'type' => 'text', 'label' => __( '极验 Captcha ID', 'pianke' ), 'desc' => __( '极验后台「行为验证 v4」应用的 Captcha ID，会下发到前台用于初始化；留空则不启用极验。', 'pianke' ) ),
			array( 'key' => 'geetest_key', 'type' => 'text', 'label' => __( '极验 Key', 'pianke' ), 'desc' => __( '极验后台的验证密钥，只保存在服务端用于生成 sign_token，绝不下发到前台。留空则不启用极验。', 'pianke' ) ),
			array( 'key' => 'geetest_validate_url', 'type' => 'text', 'label' => __( '二次校验接口', 'pianke' ), 'desc' => __( '一般不用改。默认 https://gcaptcha4.geetest.com/validate；如果极验分配了专用域名，在这里换成完整地址即可。', 'pianke' ) ),
		),
	),
	'mail'     => array(
		'label'  => __( '邮件', 'pianke' ),
		'desc'   => __( '用 SMTP 发信：填好主机 / 端口 / 账号密码后打开开关，站内所有 wp_mail（友链申请通知等）都会改走这台服务器。留空或关闭开关时沿用 WordPress 默认发信方式，本地预览与未配置站点不受影响。', 'pianke' ),
		'fields' => array(
			array( 'key' => 'smtp_on', 'type' => 'checkbox', 'label' => __( '启用 SMTP 发信', 'pianke' ), 'desc' => __( '打开后所有 wp_mail 走下方配置；关闭则完全交还给 WordPress 默认发信。', 'pianke' ) ),
			array( 'key' => 'smtp_host', 'type' => 'text', 'label' => __( 'SMTP 主机', 'pianke' ), 'desc' => __( '例如 smtp.exmail.qq.com、smtp.gmail.com。留空即使打开开关也不会接管发信。', 'pianke' ) ),
			array( 'key' => 'smtp_port', 'type' => 'number', 'label' => __( '端口', 'pianke' ), 'desc' => __( 'SSL 常用 465，STARTTLS / 无加密常用 587，明文常用 25。', 'pianke' ), 'min' => 1, 'max' => 65535 ),
			array(
				'key'     => 'smtp_encryption',
				'type'    => 'select',
				'label'   => __( '加密方式', 'pianke' ),
				'desc'    => __( '按服务商要求选择，选错会导致连接被拒绝。', 'pianke' ),
				'choices' => array(
					'ssl'      => __( 'SSL / TLS（端口 465）', 'pianke' ),
					'starttls' => __( 'STARTTLS（端口 587）', 'pianke' ),
					'none'     => __( '不加密（端口 25，仅内网）', 'pianke' ),
				),
			),
			array( 'key' => 'smtp_user', 'type' => 'text', 'label' => __( '账号', 'pianke' ), 'desc' => __( 'SMTP 登录名，一般就是完整邮箱地址。', 'pianke' ) ),
			array( 'key' => 'smtp_pass', 'type' => 'text', 'label' => __( '密码 / 授权码', 'pianke' ), 'desc' => __( '多数服务商要求填「授权码」而不是登录密码。只保存在本站数据库，不会下发到前台。', 'pianke' ) ),
			array( 'key' => 'smtp_from', 'type' => 'text', 'label' => __( '发件人地址', 'pianke' ), 'desc' => __( '留空则用上方账号作为发件人；必须与服务商允许的发件域名一致，否则会被拒收。', 'pianke' ) ),
			array( 'key' => 'smtp_from_name', 'type' => 'text', 'label' => __( '发件人名称', 'pianke' ), 'desc' => __( '收件人看到的发件人名字，留空则用站点名称。', 'pianke' ) ),
			array( 'key' => 'smtp_test_to', 'type' => 'text', 'label' => __( '测试收件地址', 'pianke' ), 'desc' => __( '点下方「发送测试邮件」时收信的地址，留空则发给管理员邮箱。', 'pianke' ) ),
		),
	),
		'seo'      => array(
			'label'  => __( 'SEO', 'pianke' ),
			'desc'   => __( '标题、描述、canonical、Open Graph、结构化数据与站点验证。留空的字段会自动取文章内容。', 'pianke' ),
			'fields' => array(
				array( 'key' => 'seo_home_title', 'type' => 'text', 'label' => __( '首页标题', 'pianke' ), 'desc' => __( '留空则使用站点名称。', 'pianke' ) ),
				array( 'key' => 'seo_home_desc', 'type' => 'textarea', 'label' => __( '首页描述', 'pianke' ), 'rows' => 2, 'desc' => __( '建议 60–120 字，会输出为 meta description 与 og:description。', 'pianke' ) ),
				array( 'key' => 'title_sep', 'type' => 'text', 'label' => __( '标题分隔符', 'pianke' ), 'desc' => __( '文章标题与站点名之间的符号。', 'pianke' ) ),
				array( 'key' => 'seo_keywords', 'type' => 'text', 'label' => __( '关键词', 'pianke' ), 'desc' => __( '可选，用英文逗号分隔。主流搜索引擎已不依赖此项。', 'pianke' ) ),
				array( 'key' => 'seo_canonical', 'type' => 'checkbox', 'label' => __( '输出 canonical', 'pianke' ), 'desc' => __( '首页、文章、分类页自动输出规范链接。', 'pianke' ) ),
				array( 'key' => 'seo_og', 'type' => 'checkbox', 'label' => __( '输出 Open Graph', 'pianke' ), 'desc' => __( '微信、微博、Facebook 分享卡片所需。', 'pianke' ) ),
				array( 'key' => 'seo_twitter', 'type' => 'checkbox', 'label' => __( '输出 Twitter Card', 'pianke' ) ),
				array( 'key' => 'seo_jsonld', 'type' => 'checkbox', 'label' => __( '输出结构化数据', 'pianke' ), 'desc' => __( 'WebSite / BlogPosting / BreadcrumbList（schema.org）。', 'pianke' ) ),
				array( 'key' => 'og_default_image', 'type' => 'url', 'media' => true, 'label' => __( '默认分享图', 'pianke' ), 'desc' => __( '文章无特色图时使用，建议 1200×630。', 'pianke' ) ),
				array( 'key' => 'twitter_site', 'type' => 'text', 'label' => __( 'Twitter 账号', 'pianke' ), 'desc' => __( '可选，形如 @example。', 'pianke' ) ),
				array( 'key' => 'noindex_search', 'type' => 'checkbox', 'label' => __( '搜索结果页 noindex', 'pianke' ) ),
				array( 'key' => 'noindex_archive', 'type' => 'checkbox', 'label' => __( '归档页 noindex', 'pianke' ), 'desc' => __( '日期、作者、标签归档；分类归档不受影响。', 'pianke' ) ),
				array( 'key' => 'noindex_404', 'type' => 'checkbox', 'label' => __( '404 页 noindex', 'pianke' ) ),
				array( 'key' => 'google_verify', 'type' => 'text', 'label' => __( 'Google 站点验证', 'pianke' ), 'desc' => __( '只填验证码，不用贴完整 meta 标签。', 'pianke' ) ),
				array( 'key' => 'bing_verify', 'type' => 'text', 'label' => __( 'Bing 站点验证', 'pianke' ) ),
				array( 'key' => 'baidu_verify', 'type' => 'text', 'label' => __( '百度站点验证', 'pianke' ) ),
			),
		),
		'unlock'   => array(
			'label'  => __( '阅读解锁', 'pianke' ),
			'desc'   => __( '只配置机器人与解锁密码。点赞可见 / 评论可见 / 密码可见 / 入群可见在「阅读解锁」区块里四选一，读者只能用作者选定的那一种方式解锁，后台不需要任何开关；选「密码可见」时可直接在区块上设置本段专属密码。锁定内容未解锁时服务端不会输出到前台、REST 或 feed。', 'pianke' ),
			'fields' => array(
				array( 'key' => 'unlock_secret', 'type' => 'text', 'label' => __( '机器人回调密钥', 'pianke' ), 'desc' => __( '必须与 WeBot 插件 bang-unlock 里的 wp_secret 完全一致，用于 HMAC-SHA256 签名。留空时只有「入群可见」的机器人回调会被拒绝（503），其它三种方式不受影响。', 'pianke' ) ),
				array( 'key' => 'unlock_group_name', 'type' => 'text', 'label' => __( '群名称', 'pianke' ), 'desc' => __( '显示在卡片的「入群可见」与提示文案里。', 'pianke' ) ),
				array( 'key' => 'unlock_group_url', 'type' => 'url', 'label' => __( '进群链接', 'pianke' ), 'desc' => __( '「入群可见」的主按钮，留空则不显示该按钮。', 'pianke' ) ),
				array( 'key' => 'unlock_bot_name', 'type' => 'text', 'label' => __( '机器人名称', 'pianke' ), 'desc' => __( '显示在「给机器人发送绑定码」的说明里。', 'pianke' ) ),
				array( 'key' => 'unlock_ttl', 'type' => 'number', 'label' => __( '群内状态有效期（小时）', 'pianke' ), 'desc' => __( '机器人在群里每收到一条该用户的消息就会续期。超过这个时长没动静（退群、被移出、潜水）就重新上锁并提示进群。建议 24–168。', 'pianke' ), 'min' => 1, 'max' => 720 ),
				array( 'key' => 'unlock_password', 'type' => 'text', 'label' => __( '站点解锁密码', 'pianke' ), 'desc' => __( '「密码可见」的默认密码：区块上没单独设置密码时才用它。读者在锁定卡片里输入即可解锁，30 天内免重输。留空则自动使用一串固定的站点派生密码（显示在下方接线说明里）。也可给单篇文章加自定义字段 pk_unlock_password 覆盖，或在编辑器里把「阅读解锁」切成「密码可见」直接填本段专属密码。', 'pianke' ) ),
				array( 'key' => 'unlock_hint', 'type' => 'textarea', 'label' => __( '自定义说明', 'pianke' ), 'rows' => 3, 'desc' => __( '可选，显示在锁定卡片标题下方。留空则用默认文案。', 'pianke' ) ),
			),
		),
		'advanced' => array(
			'label'  => __( '高级', 'pianke' ),
			'desc'   => __( '前台资源优化与自定义代码。自定义代码仅管理员可编辑，会原样输出。', 'pianke' ),
			'fields' => array(
				array( 'key' => 'clean_wp_head', 'type' => 'checkbox', 'label' => __( '清理 WordPress 冗余头部', 'pianke' ), 'desc' => __( '移除 emoji 脚本、RSD、wlwmanifest、generator、oEmbed、短链接等。', 'pianke' ) ),
				array( 'key' => 'lazy_blocks_css', 'type' => 'checkbox', 'label' => __( '区块样式非阻塞加载', 'pianke' ), 'desc' => __( 'blocks.css 改为 media=print + onload 切换，消除首屏渲染阻塞。', 'pianke' ) ),
				array( 'key' => 'minify_assets', 'type' => 'checkbox', 'label' => __( '使用压缩版 CSS / JS', 'pianke' ), 'desc' => __( '存在 style.min.css / app.min.js 且比源文件新时自动启用，否则回退源码。', 'pianke' ) ),
				array( 'key' => 'asset_guard', 'type' => 'checkbox', 'label' => __( '资源加载自检', 'pianke' ), 'desc' => __( '访客首次打开时静默检测样式表与交互脚本，加载失败才弹一张提示卡，引导刷新重试（不提供下载）。无视/关闭后 7 天内不再提示。', 'pianke' ) ),
				array( 'key' => 'head_code', 'type' => 'code', 'label' => __( '自定义 <head> 代码', 'pianke' ), 'rows' => 5, 'desc' => __( '统计脚本、字体预连接等，输出在 wp_head 末尾。', 'pianke' ) ),
				array( 'key' => 'footer_code', 'type' => 'code', 'label' => __( '自定义页脚代码', 'pianke' ), 'rows' => 5, 'desc' => __( '输出在 wp_footer 末尾，适合异步统计脚本。', 'pianke' ) ),
			),
		),
	);
}

/**
 * 站点时区候选：空 = 跟随 WordPress 常规设置；其余为常用 IANA 时区。
 * 选中值直接写入 timezone_string，由核心负责换算与显示。
 *
 * @return array
 */
function pk_timezone_choices() {
	$out = array(
		'' => __( '跟随 WordPress 设置 → 常规 → 时区', 'pianke' ),
	);
	$zones = array(
		'Asia/Shanghai'       => __( '中国标准时间（北京 / UTC+8）', 'pianke' ),
		'Asia/Hong_Kong'      => __( '香港（UTC+8）', 'pianke' ),
		'Asia/Taipei'         => __( '台北（UTC+8）', 'pianke' ),
		'Asia/Singapore'      => __( '新加坡（UTC+8）', 'pianke' ),
		'Asia/Tokyo'          => __( '东京（UTC+9）', 'pianke' ),
		'Asia/Seoul'          => __( '首尔（UTC+9）', 'pianke' ),
		'Asia/Bangkok'        => __( '曼谷（UTC+7）', 'pianke' ),
		'Asia/Jakarta'        => __( '雅加达（UTC+7）', 'pianke' ),
		'Asia/Kolkata'        => __( '加尔各答（UTC+5:30）', 'pianke' ),
		'Asia/Dubai'          => __( '迪拜（UTC+4）', 'pianke' ),
		'Europe/Moscow'       => __( '莫斯科（UTC+3）', 'pianke' ),
		'Europe/Istanbul'     => __( '伊斯坦布尔（UTC+3）', 'pianke' ),
		'Europe/Berlin'       => __( '柏林（UTC+1 / 夏令时 UTC+2）', 'pianke' ),
		'Europe/Paris'        => __( '巴黎（UTC+1 / 夏令时 UTC+2）', 'pianke' ),
		'Europe/London'       => __( '伦敦（UTC+0 / 夏令时 UTC+1）', 'pianke' ),
		'Atlantic/Reykjavik'  => __( '雷克雅未克（UTC+0）', 'pianke' ),
		'Africa/Cairo'        => __( '开罗（UTC+2）', 'pianke' ),
		'Africa/Johannesburg' => __( '约翰内斯堡（UTC+2）', 'pianke' ),
		'America/Sao_Paulo'   => __( '圣保罗（UTC-3）', 'pianke' ),
		'America/New_York'    => __( '纽约（UTC-5 / 夏令时 UTC-4）', 'pianke' ),
		'America/Chicago'     => __( '芝加哥（UTC-6 / 夏令时 UTC-5）', 'pianke' ),
		'America/Denver'      => __( '丹佛（UTC-7 / 夏令时 UTC-6）', 'pianke' ),
		'America/Los_Angeles' => __( '洛杉矶（UTC-8 / 夏令时 UTC-7）', 'pianke' ),
		'America/Vancouver'   => __( '温哥华（UTC-8 / 夏令时 UTC-7）', 'pianke' ),
		'Pacific/Auckland'    => __( '奥克兰（UTC+12 / 夏令时 UTC+13）', 'pianke' ),
		'Australia/Sydney'    => __( '悉尼（UTC+10 / 夏令时 UTC+11）', 'pianke' ),
		'UTC'                 => __( 'UTC（协调世界时）', 'pianke' ),
	);
	foreach ( $zones as $id => $label ) {
		$out[ $id ] = $label;
	}
	return $out;
}

/**
 * 让「开放注册」与 WordPress 的注册开关保持一致。
 * 首次进入后台时把主题默认值写回核心选项，避免两处状态对不上。
 */
function pk_sync_register_option() {
	$options = pk_options();
	$want    = empty( $options['allow_register'] ) ? 0 : 1;
	if ( (int) get_option( 'users_can_register' ) !== $want ) {
		update_option( 'users_can_register', $want );
	}
}
add_action( 'admin_init', 'pk_sync_register_option' );

/**
 * 清洗并合并设置。checkbox 未勾选时不会出现在 $_POST，需要显式置 0。
 */
function pk_sanitize_options( $input ) {
	$out      = pk_options();
	$can_code = current_user_can( 'unfiltered_html' );
	if ( ! is_array( $input ) ) {
		return $out;
	}
	foreach ( pk_setting_fields() as $tab ) {
		foreach ( $tab['fields'] as $field ) {
			$key  = $field['key'];
			$type = isset( $field['type'] ) ? $field['type'] : 'text';
			if ( 'checkbox' === $type ) {
				$out[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
				continue;
			}
			if ( ! isset( $input[ $key ] ) || is_array( $input[ $key ] ) ) {
				continue;
			}
			$val = trim( wp_unslash( $input[ $key ] ) );
			switch ( $type ) {
				case 'url':
					$out[ $key ] = esc_url_raw( $val );
					break;
				case 'number':
					$min         = isset( $field['min'] ) ? (int) $field['min'] : 1;
					$max         = isset( $field['max'] ) ? (int) $field['max'] : 60;
					$out[ $key ] = max( $min, min( $max, absint( $val ) ) );
					break;
				case 'textarea':
					$out[ $key ] = sanitize_textarea_field( $val );
					break;
				case 'code':
					$out[ $key ] = $can_code ? $val : wp_kses_post( $val );
					break;
				case 'select':
					$choices     = array_keys( $field['choices'] );
					$out[ $key ] = in_array( $val, $choices, true ) ? $val : '';
					break;
				default:
					$out[ $key ] = sanitize_text_field( $val );
					break;
			}
		}
	}

	/* 「开放注册」直接同步 WordPress 的注册开关，用户中心据此决定是否显示注册表单 */
	update_option( 'users_can_register', empty( $out['allow_register'] ) ? 0 : 1 );

	/* 站点时区：选了自定义时区就写 timezone_string（IANA 名称），
	   留空则清掉，交还给「设置 → 常规」里的原始时区配置 */
	$tz = isset( $out['site_timezone'] ) ? trim( (string) $out['site_timezone'] ) : '';
	if ( '' !== $tz ) {
		update_option( 'timezone_string', $tz );
	} else {
		delete_option( 'timezone_string' );
	}

	return $out;
}

function pk_register_settings() {
	register_setting(
		'pk_settings_group',
		PK_OPTION_KEY,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'pk_sanitize_options',
			'default'           => pk_default_options(),
		)
	);
}
add_action( 'admin_init', 'pk_register_settings' );

function pk_settings_menu() {
	/* 顶级菜单，紧挨「外观」；图标用内联 SVG（墨水笔尖），跟随 WP 菜单的着色 */
	$icon = 'data:image/svg+xml;base64,' . base64_encode(
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a7aaad" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18.4 2.6a2.1 2.1 0 0 1 3 3L9 18l-5 2 2-5Z"/><path d="M14 21c3.5 0 6-1.5 6-4"/></svg>'
	);
	add_menu_page(
		__( 'Bang 设置', 'pianke' ),
		__( 'Bang 设置', 'pianke' ),
		'manage_options',
		'pianke-settings',
		'pk_render_settings_page',
		$icon,
		61
	);
}
add_action( 'admin_menu', 'pk_settings_menu' );

/**
 * 图片字段的「从媒体库选择」要用 wp.media，只在本设置页入队媒体库脚本，
 * 不污染其它后台页面。wp_enqueue_media() 自带所需脚本与样式。
 */
function pk_settings_media_assets( $hook ) {
	if ( false === strpos( (string) $hook, 'pianke-settings' ) ) {
		return;
	}
	wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'pk_settings_media_assets' );

/**
 * 单个字段。div 布局：标签在上、说明在下，控件占满一列。
 */
function pk_render_field( $field, $value ) {
	$key  = $field['key'];
	$type = isset( $field['type'] ) ? $field['type'] : 'text';
	$id   = 'pk-field-' . $key;
	$name = PK_OPTION_KEY . '[' . $key . ']';
	echo '<div class="pk-f' . ( 'checkbox' === $type ? ' pk-f-row' : '' ) . '">';
	if ( 'checkbox' === $type ) {
		echo '<span class="pk-f-t"><label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';
		if ( ! empty( $field['desc'] ) ) {
			echo '<span class="pk-f-d">' . esc_html( $field['desc'] ) . '</span>';
		}
		echo '</span><span class="pk-toggle"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( 1, (int) $value, false ) . '><span class="pk-toggle-track" aria-hidden="true"></span></span>';
		echo '</div>';
		return;
	}
	echo '<label class="pk-f-t" for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';
	switch ( $type ) {
		case 'textarea':
		case 'code':
			$rows = isset( $field['rows'] ) ? (int) $field['rows'] : 3;
			$cls  = 'code' === $type ? ' pk-f-code' : '';
			$ph   = isset( $field['placeholder'] ) ? ' placeholder="' . esc_attr( $field['placeholder'] ) . '"' : '';
			echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="' . esc_attr( $rows ) . '" class="pk-f-input' . esc_attr( $cls ) . '"' . $ph . '>' . esc_textarea( (string) $value ) . '</textarea>';
			break;
		case 'number':
			echo '<input type="number" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" min="' . esc_attr( isset( $field['min'] ) ? $field['min'] : 1 ) . '" max="' . esc_attr( isset( $field['max'] ) ? $field['max'] : 60 ) . '" class="pk-f-num">';
			break;
		case 'url':
			if ( ! empty( $field['media'] ) ) {
				$pk_url = trim( (string) $value );
				/* 图片字段：直接输入链接的 input 原样保留，旁边加「从媒体库选择」与预览 */
				echo '<div class="pk-f-media" data-pk-media-field>';
				echo '<div class="pk-f-media-row">';
				echo '<input type="url" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="pk-f-input pk-f-media-url" placeholder="https://" data-pk-media-input>';
				echo '<button type="button" class="button pk-f-media-pick" data-pk-media-pick="' . esc_attr( $id ) . '">' . esc_html__( '从媒体库选择', 'pianke' ) . '</button>';
				echo '<button type="button" class="button pk-f-media-clear" data-pk-media-clear="' . esc_attr( $id ) . '">' . esc_html__( '清除', 'pianke' ) . '</button>';
				echo '</div>';
				echo '<span class="pk-f-media-preview" data-pk-media-preview' . ( '' === $pk_url ? ' hidden' : '' ) . '>';
				if ( '' !== $pk_url ) {
					echo '<img src="' . esc_url( $pk_url ) . '" alt="">';
				}
				echo '</span>';
				echo '</div>';
				break;
			}
			echo '<input type="url" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="pk-f-input" placeholder="https://">';
			break;
		case 'select':
			$choices = isset( $field['choices'] ) ? $field['choices'] : array();
			echo '<span class="pk-f-select"><select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
			foreach ( $choices as $choice => $label ) {
				echo '<option value="' . esc_attr( $choice ) . '"' . selected( (string) $value, (string) $choice, false ) . '>'
					. esc_html( $label ) . '</option>';
			}
			echo '</select><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></span>';
			break;
		default:
			echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="pk-f-input">';
			break;
	}
	if ( ! empty( $field['desc'] ) ) {
		echo '<span class="pk-f-d">' . esc_html( $field['desc'] ) . '</span>';
	}
	echo '</div>';
}

/**
 * 服务器层面的性能建议（主题无法代改 nginx / 证书）。
 */
function pk_server_hints() {
	$nginx = "location ~* \\.(css|js|png|jpg|jpeg|gif|webp|avif|svg|woff2?)$ {\n    expires 1y;\n    add_header Cache-Control \"public, immutable\";\n}\ngzip on;\ngzip_types text/html text/css application/javascript application/json image/svg+xml;";
	return '<h3>' . esc_html__( '服务器优化建议', 'pianke' ) . '</h3>'
		. '<p class="pk-f-d">' . esc_html__( 'Lighthouse 的「缓存生命周期」与 HTTPS 属于服务器配置，主题无法代改。上线前建议在 nginx 站点配置中加入：', 'pianke' ) . '</p>'
		. '<pre class="pk-code">' . esc_html( $nginx ) . '</pre>'
		. '<p class="pk-f-d">' . esc_html__( '另外：为站点签发证书并全站 HTTPS，可消除 Lighthouse 的 is-on-https 扣分；本站当前为内网 HTTP，本地审计该项必然为 0。', 'pianke' ) . '</p>';
}

/**
 * 后台：友情链接入口（只读，不参与保存）。
 * 友链数据已改走 WordPress 核心链接管理器，这里只给出入口与状态。
 */
function pk_links_admin_panel() {
	$admin  = admin_url( 'link-manager.php' );
	$count  = function_exists( 'pk_friend_links' ) ? count( pk_friend_links() ) : 0;
	$groups = function_exists( 'pk_link_groups' ) ? pk_link_groups() : array();
	$gtxt  = $groups ? implode( '、', array_slice( $groups, 0, 8 ) ) : __( '暂无分类', 'pianke' );
	return '<div class="pk-links-admin">'
		. '<h3>' . esc_html__( '友情链接', 'pianke' ) . '</h3>'
		. '<p class="pk-f-d">' . esc_html( sprintf( '当前共 %d 条友链、%d 个分类（%s）。数据保存在 WordPress 核心链接管理器里，前台 /links/ 与申请审核都读它。', $count, count( $groups ), $gtxt ) ) . '</p>'
		. '<p class="pk-f-d">' . esc_html__( '增删改友链、新建分类、标记「不可见」（前台显示为暂不可达）都在下面这个页面完成；分类名会直接成为前台筛选按钮与申请表单的下拉项。', 'pianke' ) . '</p>'
		. '<p class="pk-f-d"><a class="button is-primary" href="' . esc_url( $admin ) . '">' . esc_html__( '打开链接管理器', 'pianke' ) . '</a></p>'
		. '</div>';
}

/**
 * 设置页。所有 tab 的字段一次性放进同一个表单，切换只是显示隐藏，
 * 这样保存时不会丢掉其它 tab 的值；无 JS 时按 tab 参数跳转，依旧可用。
 */
function pk_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tabs    = pk_setting_fields();
	$active  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
	$active  = isset( $tabs[ $active ] ) ? $active : 'general';
	$options = pk_options();
	$base    = admin_url( 'admin.php?page=pianke-settings' );
	$icons   = array(
		'general'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/><line x1="14" x2="14" y1="2" y2="6"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="16" x2="16" y1="18" y2="22"/></svg>',
		'safety'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4 6v6c0 4.4 3.4 8.2 8 9 4.6-.8 8-4.6 8-9V6l-8-3Z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>',
		'mail'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/></svg>',
		'seo'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/><path d="M8 12h6M11 8.5v7"/></svg>',
		'unlock'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>',
		'advanced' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16 18 6-6-6-6"/><path d="m8 6-6 6 6 6"/></svg>',
	);

	echo '<div class="pk-set">';
	echo '<header class="pk-set-head">'
		. '<div class="pk-set-brand"><span class="pk-set-logo" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18.4 2.6a2.1 2.1 0 0 1 3 3L9 18l-5 2 2-5Z"/><path d="M14 21c3.5 0 6-1.5 6-4"/></svg></span>'
		. '<span class="pk-set-ttl"><h1>' . esc_html__( 'Bang 设置', 'pianke' ) . '</h1><p>' . esc_html__( '落笔有声，留白有度。', 'pianke' ) . '</p></span></div>'
		. '<span class="pk-set-ver">v' . esc_html( PK_VERSION ) . '</span>'
		. '</header>';
	echo '<div class="pk-set-body">';
	echo '<nav class="pk-set-nav" aria-label="' . esc_attr__( '设置分组', 'pianke' ) . '">';
	foreach ( $tabs as $slug => $tab ) {
		$url = add_query_arg( 'tab', $slug, $base );
		echo '<a href="' . esc_url( $url ) . '" class="pk-set-item' . ( $slug === $active ? ' is-active' : '' ) . '" data-pk-tab-link="' . esc_attr( $slug ) . '">'
			. $icons[ $slug ] // phpcs:ignore WordPress.Security.EscapeOutput
			. '<span>' . esc_html( $tab['label'] ) . '</span></a>';
	}
	echo '</nav>';
	echo '<form method="post" action="options.php" class="pk-set-form">';
	settings_fields( 'pk_settings_group' );
	echo '<div class="pk-set-main">';
	foreach ( $tabs as $slug => $tab ) {
		echo '<section class="pk-set-panel" data-pk-tab="' . esc_attr( $slug ) . '"' . ( $slug === $active ? '' : ' hidden' ) . '>';
		echo '<div class="pk-set-intro"><h2>' . esc_html( $tab['label'] ) . '</h2><p>' . esc_html( $tab['desc'] ) . '</p></div>';
		foreach ( $tab['fields'] as $field ) {
			pk_render_field( $field, isset( $options[ $field['key'] ] ) ? $options[ $field['key'] ] : '' );
		}
		if ( 'advanced' === $slug ) {
			echo pk_server_hints(); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		if ( 'unlock' === $slug && function_exists( 'pk_unlock_panel' ) ) {
			echo pk_unlock_panel(); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		/* 订阅者列表：只读展示 + CSV 导出，表单里只有文字表格，不会参与保存 */
		if ( 'general' === $slug && function_exists( 'pk_subscribers_panel' ) ) {
			pk_subscribers_panel();
		}
		/* 友链申请：只读待审列表 + 通过 / 忽略链接，同样不参与保存 */
		if ( 'general' === $slug && function_exists( 'pk_link_applications_panel' ) ) {
			pk_link_applications_panel();
		}
		/* 友情链接：只读入口，指向 WordPress 核心链接管理器 */
		if ( 'general' === $slug && function_exists( 'pk_links_admin_panel' ) ) {
			echo pk_links_admin_panel(); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		/* 人机校验：只读接线说明，不参与保存 */
		if ( 'safety' === $slug && function_exists( 'pk_geetest_panel' ) ) {
			echo pk_geetest_panel(); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		/* SMTP：只读接线说明 + 测试发送按钮，不参与保存 */
		if ( 'mail' === $slug && function_exists( 'pk_smtp_panel' ) ) {
			echo pk_smtp_panel(); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		/* 词库：已识别的 inc/data 词库文件 + 命中统计，只读展示，不参与保存 */
		if ( 'safety' === $slug && function_exists( 'pk_moderation_panel' ) ) {
			echo pk_moderation_panel(); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</section>';
	}
	echo '</div>';
	echo '<footer class="pk-set-foot"><button type="submit" class="pk-set-save">' . esc_html__( '保存设置', 'pianke' ) . '</button><span class="pk-set-hint">' . esc_html__( '保存后立即在前台生效。', 'pianke' ) . '</span></footer>';
	echo '</form>';
	echo '</div></div>';
	?>
<style>
/* ============================================================
   Bang 设置 · 统一视觉
   一套 token 收口全部颜色 / 圆角 / 动效，不再散落硬编码：
   · 颜色：白底 + 黑(#111)点缀，灰阶只用 --pk-ink-2 / -3 / --pk-line / --pk-soft
   · 圆角：矩形统一 12px(--pk-r)，内嵌小件 8px(--pk-r-sm)，胶囊 999px(--pk-pill) 仅开关与徽章
   · 与前台同语言：白底黑按钮；黑色只出现在 logo 块 / 激活导航项 / 保存按钮 / 开关 / focus 描边
   · 不用 box-shadow、渐变、hover 上浮；focus 一律 border-color + outline
   · 动效只允许 opacity / background-color / border-color / transform
   ============================================================ */
.pk-set{
	--pk-ink:#111;--pk-ink-2:#5b5b5b;--pk-ink-3:#8c8c8c;
	--pk-line:#e5e5e5;--pk-soft:#f6f6f6;--pk-bg:#fff;
	--pk-r:12px;--pk-r-sm:8px;--pk-pill:999px;
	--pk-ease:.18s ease;
	max-width:1080px;margin:16px 0 32px;color:var(--pk-ink);font-family:inherit
}
.pk-set *,.pk-set *::before,.pk-set *::after{box-sizing:border-box}
/* 头部：与面板同为白底描边卡片，黑底只留给 logo 方块，消除「黑头 + 白面板」两套语言 */
.pk-set-head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 22px;background:var(--pk-bg);border:1px solid var(--pk-line);border-radius:var(--pk-r);color:var(--pk-ink)}
.pk-set-brand{display:flex;align-items:center;gap:14px;min-width:0}
.pk-set-logo{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;flex:none;border-radius:var(--pk-r-sm);background:var(--pk-ink);color:var(--pk-bg)}
.pk-set-logo svg{width:20px;height:20px}
.pk-set-ttl h1{margin:0;font-size:17px;font-weight:600;line-height:1.3;color:var(--pk-ink)}
.pk-set-ttl p{margin:2px 0 0;font-size:12px;color:var(--pk-ink-3)}
.pk-set-ver{flex:none;padding:4px 10px;border:1px solid var(--pk-line);border-radius:var(--pk-pill);background:var(--pk-soft);font-size:12px;color:var(--pk-ink-2)}
/* 主体：左侧导航 + 右侧内容 */
.pk-set-body{display:flex;align-items:flex-start;gap:20px;margin-top:20px}
.pk-set-nav{position:sticky;top:46px;flex:0 0 200px;display:flex;flex-direction:column;gap:4px;padding:10px;background:var(--pk-bg);border:1px solid var(--pk-line);border-radius:var(--pk-r)}
.pk-set-item{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:var(--pk-r-sm);font-size:13px;font-weight:500;text-decoration:none;color:var(--pk-ink-2);transition:background-color var(--pk-ease),color var(--pk-ease)}
.pk-set-item svg{width:16px;height:16px;flex:none}
.pk-set-item:hover{color:var(--pk-ink);background:var(--pk-soft)}
.pk-set-item.is-active,.pk-set-item.is-active:hover{background:var(--pk-ink);color:var(--pk-bg)}
.pk-set-item:focus-visible{outline:2px solid var(--pk-ink);outline-offset:2px}
.pk-set-main{flex:1;min-width:0}
.pk-set-panel{padding:22px 24px 26px;background:var(--pk-bg);border:1px solid var(--pk-line);border-radius:var(--pk-r);margin:0 0 20px}
.pk-set-panel[hidden]{display:none}
.pk-set-intro{margin:0 0 18px;padding-bottom:16px;border-bottom:1px solid var(--pk-line)}
.pk-set-intro h2{margin:0 0 6px;font-size:16px;font-weight:600;color:var(--pk-ink)}
.pk-set-intro p{margin:0;font-size:12px;line-height:1.6;color:var(--pk-ink-3)}
/* 字段 */
.pk-f{display:flex;flex-direction:column;gap:7px;margin:0 0 18px}
.pk-f-row{flex-direction:row;align-items:flex-start;justify-content:space-between;gap:16px}
.pk-f-t{font-size:13px;font-weight:600;color:var(--pk-ink)}
.pk-f-row .pk-f-t{display:flex;flex-direction:column;gap:4px;max-width:520px}
.pk-f-d{font-size:12px;font-weight:400;line-height:1.6;color:var(--pk-ink-3)}
.pk-f-d b{font-weight:600;color:var(--pk-ink-2)}
.pk-set-panel > p.pk-f-d{margin:0 0 8px}
.pk-set code{padding:1px 5px;border:1px solid var(--pk-line);border-radius:var(--pk-r-sm);background:var(--pk-soft);font-family:Consolas,Monaco,monospace;font-size:11.5px;color:var(--pk-ink)}
/* 输入控件：统一描边 / 圆角 / 高度；focus 改 border-color + outline，并清掉 WP 默认 box-shadow */
.pk-f-input,.pk-f-num,.pk-f-select select,textarea.pk-f-input{appearance:none;-webkit-appearance:none;width:100%;max-width:560px;padding:9px 12px;border:1px solid var(--pk-line);border-radius:var(--pk-r);background:var(--pk-bg);color:var(--pk-ink);font-size:13px;line-height:1.5;font-family:inherit;transition:border-color var(--pk-ease)}
.pk-f-num{width:110px;max-width:none}
textarea.pk-f-input{max-width:640px;resize:vertical;min-height:64px}
textarea.pk-f-code{font-family:Consolas,Monaco,monospace;font-size:12px;direction:ltr}
.pk-f-input:focus,.pk-f-num:focus,.pk-f-select select:focus{box-shadow:none;outline:2px solid var(--pk-ink);outline-offset:1px;border-color:var(--pk-ink)}
.pk-f-input::placeholder,textarea.pk-f-input::placeholder{color:var(--pk-ink-3)}
.pk-f-select{position:relative;display:inline-block;width:100%;max-width:560px}
.pk-f-select select{padding-right:34px;cursor:pointer}
.pk-f-select svg{position:absolute;right:12px;top:50%;width:14px;height:14px;transform:translateY(-50%);color:var(--pk-ink-2);pointer-events:none}
/* 图片字段：直接填链接的 input 原样保留，旁边是同一套次要按钮 + 缩略图预览 */
.pk-f-media{display:flex;flex-direction:column;gap:8px;max-width:640px}
.pk-f-media-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.pk-f-media-row .pk-f-input{flex:1 1 240px;min-width:200px;max-width:none}
/* 次要按钮：从媒体库选择 / 清除 / 导出 CSV 共用一套，不再各写各的 */
.pk-set .button{appearance:none;-webkit-appearance:none;display:inline-flex;align-items:center;justify-content:center;height:36px;padding:0 14px;border:1px solid var(--pk-line);border-radius:var(--pk-r);background:var(--pk-bg);color:var(--pk-ink);font-size:12px;font-weight:500;line-height:1;font-family:inherit;text-decoration:none;cursor:pointer;box-shadow:none;flex:none;transition:background-color var(--pk-ease),border-color var(--pk-ease)}
.pk-set .button:hover{background:var(--pk-soft);border-color:var(--pk-ink);color:var(--pk-ink)}
.pk-set .button:focus{box-shadow:none;outline:2px solid var(--pk-ink);outline-offset:1px}
.pk-f-media-preview{display:inline-flex;align-items:center;justify-content:center;align-self:flex-start;max-width:240px;max-height:120px;padding:6px;border:1px solid var(--pk-line);border-radius:var(--pk-r);background:var(--pk-soft)}
.pk-f-media-preview[hidden]{display:none}
.pk-f-media-preview img{display:block;max-width:100%;max-height:104px;width:auto;height:auto;object-fit:contain;border-radius:var(--pk-r-sm)}
/* 开关：轨道盖在 input 上必须让点击穿透，否则点开关本体不会切换 checkbox（勿回退） */
.pk-toggle{position:relative;flex:none;width:40px;height:22px;margin-top:1px;cursor:pointer}
.pk-toggle input{position:absolute;z-index:1;opacity:0;width:100%;height:100%;margin:0;cursor:pointer}
.pk-toggle-track{position:absolute;inset:0;pointer-events:none;border-radius:var(--pk-pill);background:var(--pk-line);transition:background-color var(--pk-ease)}
.pk-toggle-track::after{content:"";position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:var(--pk-pill);background:var(--pk-bg);transition:transform var(--pk-ease)}
.pk-toggle input:checked + .pk-toggle-track{background:var(--pk-ink)}
.pk-toggle input:checked + .pk-toggle-track::after{transform:translateX(18px)}
.pk-toggle input:focus-visible + .pk-toggle-track{outline:2px solid var(--pk-ink);outline-offset:2px}
/* 底部保存：全站唯一的实心黑按钮，与前台同一语言 */
.pk-set-foot{display:flex;align-items:center;gap:14px;margin-top:4px;padding-top:18px;border-top:1px solid var(--pk-line)}
.pk-set-save{appearance:none;-webkit-appearance:none;padding:10px 28px;border:1px solid var(--pk-ink);border-radius:var(--pk-r);background:var(--pk-ink);color:var(--pk-bg);font-size:13px;font-weight:600;line-height:1.4;font-family:inherit;cursor:pointer;transition:opacity var(--pk-ease)}
.pk-set-save:hover{opacity:.82}
.pk-set-save:focus{box-shadow:none;outline:2px solid var(--pk-ink);outline-offset:2px}
.pk-set-hint{font-size:12px;color:var(--pk-ink-3)}
/* 追加面板：服务器建议 / 解锁接线说明 / 订阅者列表 */
.pk-set h3{margin:22px 0 8px;font-size:14px;font-weight:600;color:var(--pk-ink)}
.pk-set-panel > h3{margin-top:26px;padding-top:20px;border-top:1px solid var(--pk-line)}
.pk-set .pk-code{max-width:640px;overflow:auto;padding:14px 16px;margin:10px 0 0;border:1px solid var(--pk-line);border-radius:var(--pk-r);background:var(--pk-soft);font-family:Consolas,Monaco,monospace;font-size:12px;line-height:1.7;color:var(--pk-ink)}
.pk-set .pk-subs{margin-top:26px;padding-top:20px;border-top:1px solid var(--pk-line)}
.pk-set .pk-subs h3{margin:0 0 6px;padding:0;border:0}
.pk-set .description{font-size:12px;line-height:1.6;color:var(--pk-ink-3);margin:6px 0 0}
.pk-set .pk-subs-table.widefat{max-width:640px;margin:12px 0 0;background:var(--pk-bg);border:1px solid var(--pk-line);border-radius:var(--pk-r);border-collapse:separate;border-spacing:0;overflow:hidden;box-shadow:none}
.pk-set .pk-subs-table.widefat th,.pk-set .pk-subs-table.widefat td{padding:9px 12px;border:0;border-bottom:1px solid var(--pk-line);font-size:12px;color:var(--pk-ink);text-align:left}
.pk-set .pk-subs-table.widefat thead th{background:var(--pk-soft);font-weight:600}
.pk-set .pk-subs-table.widefat.striped tbody tr:nth-child(odd) td{background:var(--pk-bg)}
.pk-set .pk-subs-table.widefat.striped tbody tr:nth-child(even) td{background:var(--pk-soft)}
.pk-set .pk-subs-table.widefat tbody tr:last-child td{border-bottom:0}
/* 友链申请：待审列表 + 通过 / 忽略操作，沿用同一套 token */
.pk-set .pk-linkapply{margin-top:26px;padding-top:20px;border-top:1px solid var(--pk-line)}
.pk-set .pk-linkapply h3{margin:0 0 6px;padding:0;border:0}
.pk-set .pk-linkapply-ok{color:var(--pk-ink);font-weight:600}
.pk-set .pk-linkapply-list{display:flex;flex-direction:column;gap:10px;margin-top:14px}
.pk-set .pk-linkapply-row{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:12px 14px;border:1px solid var(--pk-line);border-radius:var(--pk-r)}
.pk-set .pk-linkapply-main{min-width:0;font-size:13px;color:var(--pk-ink)}
.pk-set .pk-linkapply-main strong{font-weight:600}
.pk-set .pk-linkapply-main a{color:var(--pk-ink-2);text-decoration:none;border-bottom:1px solid var(--pk-line)}
.pk-set .pk-linkapply-meta{display:block;margin-top:4px;font-size:11px;color:var(--pk-ink-3)}
.pk-set .pk-linkapply-desc{margin:6px 0 0;font-size:12px;line-height:1.6;color:var(--pk-ink-2)}
.pk-set .pk-linkapply-warn{margin:6px 0 0;font-size:12px;line-height:1.6;color:var(--pk-ink);font-weight:600}
.pk-set .pk-linkapply-ops{flex:none;display:flex;gap:8px}
.pk-set .pk-linkapply-ops .button.is-primary{background:var(--pk-ink);border-color:var(--pk-ink);color:var(--pk-bg)}
/* 友情链接入口：只读面板，指向核心链接管理器 */
.pk-set .pk-links-admin{margin-top:26px;padding-top:20px;border-top:1px solid var(--pk-line)}
.pk-set .pk-links-admin h3{margin:0 0 8px;padding:0;border:0}
.pk-set .pk-links-admin p.pk-f-d{margin:0 0 8px}
/* SMTP 测试面板：挂在邮件 tab 字段之后（只读 + 测试按钮，不参与保存） */
.pk-set .pk-smtp-panel{margin-top:26px;padding-top:20px;border-top:1px solid var(--pk-line)}
.pk-set .pk-smtp-panel h3{margin:0 0 8px;padding:0;border:0}
.pk-set .pk-smtp-panel p.pk-f-d{margin:0 0 8px}
.pk-set .pk-smtp-actions{display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin:14px 0 0}
.pk-set .pk-smtp-msg{font-size:12.5px;line-height:1.6;color:var(--pk-ink-3)}
.pk-set .pk-smtp-msg.is-ok{color:var(--pk-ink)}
.pk-set .pk-smtp-msg.is-err{color:#c0392b}
/* 人机校验接线说明（挂在安全设置 tab） */
.pk-set .pk-geetest-panel{margin-top:26px;padding-top:20px;border-top:1px solid var(--pk-line)}
.pk-set .pk-geetest-panel h3{margin:0 0 10px;padding:0;border:0;font-size:14px;font-weight:600;color:var(--pk-ink)}
.pk-set .pk-geetest-panel p.pk-f-d{margin:0 0 8px}
/* 词库面板：inc/data 词库文件列表 + 命中统计（只读） */
.pk-set .pk-words-panel{margin-top:26px;padding-top:20px;border-top:1px solid var(--pk-line)}
.pk-set .pk-words-panel > p.pk-f-d{margin:0 0 10px}
.pk-set .pk-words-panel .pk-f-row{margin:16px 0 8px}
.pk-set .pk-wordfiles{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 4px;padding:0;list-style:none}
.pk-set .pk-wordfiles li{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border:1px solid var(--pk-line);border-radius:var(--pk-r-sm);background:var(--pk-soft);font-size:12px;color:var(--pk-ink)}
.pk-set .pk-wordfiles code{padding:0;border:0;background:none}
.pk-set .pk-wordfiles span{color:var(--pk-ink-3)}
.pk-set .pk-wordstats.widefat,.pk-set .pk-wordlog.widefat{max-width:640px;margin:12px 0 0;background:var(--pk-bg);border:1px solid var(--pk-line);border-radius:var(--pk-r);border-collapse:separate;border-spacing:0;overflow:hidden;box-shadow:none}
.pk-set .pk-wordstats.widefat th,.pk-set .pk-wordstats.widefat td,.pk-set .pk-wordlog.widefat th,.pk-set .pk-wordlog.widefat td{padding:9px 12px;border:0;border-bottom:1px solid var(--pk-line);font-size:12px;color:var(--pk-ink);text-align:left}
.pk-set .pk-wordstats.widefat thead th,.pk-set .pk-wordlog.widefat thead th{background:var(--pk-soft);font-weight:600}
.pk-set .pk-wordstats.widefat tbody tr:last-child td,.pk-set .pk-wordlog.widefat tbody tr:last-child td{border-bottom:0}
/* 窄屏：导航收到顶部横排 */
@media (max-width:782px){
	.pk-set-body{flex-direction:column;gap:14px}
	.pk-set-nav{position:static;flex:none;width:100%;flex-direction:row;flex-wrap:wrap}
	.pk-set-item{padding:9px 12px}
	.pk-set-head{padding:16px 18px}
	.pk-set-panel{padding:18px 16px 22px}
	.pk-set-foot{flex-wrap:wrap}
	.pk-set .pk-linkapply-row{flex-direction:column;gap:10px}
}
</style>
<script>
(function(){var links=document.querySelectorAll('.pk-set [data-pk-tab-link]');var panels=document.querySelectorAll('.pk-set [data-pk-tab]');
Array.prototype.forEach.call(links,function(a){a.addEventListener('click',function(e){e.preventDefault();var t=a.getAttribute('data-pk-tab-link');
Array.prototype.forEach.call(panels,function(p){p.hidden=p.getAttribute('data-pk-tab')!==t});
Array.prototype.forEach.call(links,function(x){x.classList.toggle('is-active',x===a)});
if(window.history&&history.replaceState){history.replaceState(null,'',a.href)}})})})();
/* 图片字段：从媒体库选择 + 直接填链接，两者都刷新预览 */
(function(){
	var frame=null,activeInput=null;
	function preview(input){
		if(!input){return;}
		var wrap=input.closest?input.closest('[data-pk-media-field]'):null;
		var box=wrap?wrap.querySelector('[data-pk-media-preview]'):null;
		if(!box){return;}
		var url=(input.value||'').trim();
		if(!url){box.hidden=true;box.innerHTML='';return;}
		box.innerHTML='';
		var img=document.createElement('img');
		img.src=url;img.alt='';
		box.appendChild(img);
		box.hidden=false;
	}
	Array.prototype.forEach.call(document.querySelectorAll('[data-pk-media-input]'),function(input){
		input.addEventListener('input',function(){preview(input)});
		input.addEventListener('change',function(){preview(input)});
	});
	Array.prototype.forEach.call(document.querySelectorAll('[data-pk-media-pick]'),function(btn){
		btn.addEventListener('click',function(e){
			e.preventDefault();
			var input=document.getElementById(btn.getAttribute('data-pk-media-pick'));
			if(!input||!window.wp||!wp.media){return;}
			activeInput=input;
			if(!frame){
				frame=wp.media({title:'选择图片',button:{text:'使用这张图片'},multiple:false,library:{type:'image'}});
				frame.on('select',function(){
					if(!activeInput){return;}
					var att=frame.state().get('selection').first().toJSON();
					activeInput.value=att.url||'';
					preview(activeInput);
					if(window.Event){activeInput.dispatchEvent(new Event('change',{bubbles:true}));}
				});
			}
			frame.open();
		});
	});
	Array.prototype.forEach.call(document.querySelectorAll('[data-pk-media-clear]'),function(btn){
		btn.addEventListener('click',function(e){
			e.preventDefault();
			var input=document.getElementById(btn.getAttribute('data-pk-media-clear'));
			if(!input){return;}
			input.value='';
			preview(input);
			input.focus();
		});
	});
})();
</script>
	<?php
}
