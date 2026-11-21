<?php
/**
 * Bang · 后台设置（常规 / 安全设置 / SEO / 阅读解锁 / 高级）
 *
 * 全部选项存为单个 bang_options 数组，避免逐条查询；代码类字段（统计脚本等）
 * 只有具备 unfiltered_html 权限的管理员才能保存原文，其余走 kses 过滤。
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BANG_OPTION_KEY' ) ) {
	define( 'BANG_OPTION_KEY', 'bang_options' );
}

/**
 * 默认值。新增字段只需在这里与 bang_setting_fields() 各加一处。
 */
function bang_default_options() {
	return array(
		'logo_url'         => 'https://cdn.naigou.cn/logo.png',
		'logo_dark_url'    => '',
		'site_icon_url'    => '',
		'tabbar_style'     => 'float', // 手机端底部导航：float 悬浮胶囊 / flush 贴合底部
		'slides_mode'      => 'random',
		'slides_count'     => 5,
		'slides_posts'     => '',
		'ticker_on'        => 1,
		'ticker_tag'       => '公告',
		'ticker_text'      => bang_ticker_default_text(),
		/* 作者卡签名与社交（0.13.28）：社交链接留空则不显示该图标 */
		'author_bio_mode'  => 'profile',
		'author_bio_custom'=> '',
		'author_bio_api'   => 'https://v1.hitokoto.cn/?encode=json',
		'author_bio_fallback' => '落笔有声，留白有度。',
		'social_qq'        => 'https://user.qzone.qq.com/123456789',
		'social_wechat'    => 'https://weixin.qq.com/',
		'social_douyin'    => 'https://www.douyin.com/',
		'social_x'         => 'https://x.com/',
		'social_weibo'     => 'https://weibo.com/',
		'social_github'    => 'https://github.com/naigoucn',
		'social_bilibili'  => 'https://space.bilibili.com/1',
		'subscribe_on'     => 1,
		'subscribe_title'  => '订阅文章推送',
		'subscribe_desc'   => '无骚扰邮件，随时可取消订阅。',
		'notice_on'        => 1,
		'notice_mode'      => 'ack',
		'notice_title'     => '网站公告',
		'notice_text'      => "欢迎来到 Bang。\n点开卡片即可阅读，← → 翻到上一篇 / 下一篇。",
		'notice_later'     => '我记住啦~',
		'notice_ok'        => '确认',
		'footer_text'      => '落笔有声，留白有度',
		/* 页脚链接分组：空行分块，每块首行为列标题，其余行「链接名 | 链接」 */
		'footer_links'     => "关于\n主题介绍 | /theme/\n文章归档 | /archive/\n关于我们 | /about/\n\n内容\n说说 | /say/\n图片集 | /gallery/\n足迹 | /footprint/\n\n更多\n全部分类 | /categories/\n友情链接 | /links/\n帮助中心 | /help/",
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
		/* 语言首选：detect=跟随浏览器；或具体语言代码。访客手动选过语言后个人偏好优先 */
		'lang_default'     => 'detect',
		'smtp_test_to'     => '',
		'icp'              => '粤ICP备2021021036号',
		'police_icp'       => '粤公网安备44228202000143号',
		'show_comments'    => 1,
		'copyright_on'     => 1,
		'related_on'       => 1,
		/* 热门文章排行榜：只留总开关，条数/范围/排序/权重固定，见 inc/ranking.php */
		'ranking_on'       => 1,
		/* 打赏：二维码图片 + 打赏链接，两种同时填就都显示 */
		'reward_on'        => 1,
		'reward_qr'        => '',
		'reward_link'      => '',
		'reward_title'     => '',
		'reward_desc'      => '',
		'reward_btn_text'  => '前往打赏',
		/* 文章侧栏小工具：逗号分隔、按显示顺序；自定义 HTML 仅管理员可编辑 */
		'sidebar_widgets'    => 'author,toc,reward,latest,hot',
		'sidebar_widget_html' => '',
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
		/* AI 摘要：默认关闭，填写 OpenAI 兼容接口后发布文章时自动生成一句摘要 */
		'ai_excerpt_on'    => 0,
		'ai_api_base'      => 'https://api.openai.com/v1',
		'ai_api_key'       => '',
		'ai_model'         => 'gpt-4o-mini',
		/* 发布器接口：供外部 AI Agent 发帖，Token 存独立 option（hash） */
		'publisher_on'     => 0,
		'publisher_rate'   => 10,
		/* 阅读解锁：登录 / 点赞 / 回复 / 密码 / 叠加在区块上选，下面为遗留兼容键，可忽略。 */
		'unlock_ttl'       => 72,
		'unlock_group_name'=> '交流群',
		'unlock_group_url' => '',
		'unlock_bot_name'  => '机器人',
		'unlock_hint'      => '',
		'unlock_secret'    => '',
		'unlock_password'  => '',
		'unlock_password_text' => '',
		/* 快捷登录：QQ 官方 OAuth2 + 彩虹聚合登录（密钥只留服务端） */
		'quicklogin_on'    => 0,
		'qq_on'            => 0,
		'qq_appid'         => '',
		'qq_appkey'        => '',
		'rainbow_on'       => 0,
		'rainbow_api'      => 'https://u.naigou.cn/connect.php',
		'rainbow_appid'    => '',
		'rainbow_appkey'   => '',
		'rainbow_plats'    => 'qq,wx,sina,douyin',
		/* 音乐播放器：接口留空 = 演示模式（QQ 音乐榜单），填了 = 走自定义接口 */
		'music_on'         => 0,
		'music_api'        => '',
		'music_demo_playlist' => '62',
		'music_demo_singer'  => '',
		/* 足迹地图：单色极简底图，前台可全屏 */
		'footprint_on'        => 1,
		'footprint_js_key'    => '',
		'footprint_web_key'   => '',
		'footprint_types'     => '旅游,美食,购物,住宿,交通,其他',
		/* 图片集：独立图片集文章类型 bang_gallery */
		'gallery_on'          => 1,
		'gallery_per_page'    => 24,
	);
}

/**
 * 读取全部设置（本请求内缓存）。
 */
function bang_options() {
	static $cache = null;
	if ( null === $cache ) {
		bang_migrate_social_defaults();
		bang_migrate_icp_defaults();
		$saved = get_option( BANG_OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		$cache = wp_parse_args( $saved, bang_default_options() );
	}
	return $cache;
}

/**
 * 一次性迁移：老站点已保存的 icp 为空字符串时，补成演示备案号。
 * 与社交默认值同理——wp_parse_args 会用 saved 的空值盖掉默认值。
 * 只填空字符串，用户自己填过的值一律保留；police_icp 是新增键，saved 里没有时
 * wp_parse_args 会自动取默认值，无需在这里处理。
 */
function bang_migrate_icp_defaults() {
	if ( get_option( 'bang_icp_defaults_migrated' ) ) {
		return;
	}
	$saved = get_option( BANG_OPTION_KEY, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$default = bang_default_options();
	if ( array_key_exists( 'icp', $saved ) && '' === trim( (string) $saved['icp'] ) ) {
		$saved['icp'] = $default['icp'];
		update_option( BANG_OPTION_KEY, $saved );
	}
	update_option( 'bang_icp_defaults_migrated', 1 );
}

/**
 * 一次性迁移：已保存的 bang_options 里社交字段为空字符串时，用示例地址填上。
 * 老站点存过空值，wp_parse_args 会拿空值盖掉 bang_default_options() 的默认值，
 * 导致作者卡/联系方式页一个图标都不显示。迁移只填空字符串，用户自己填过的值一律保留。
 */
function bang_migrate_social_defaults() {
	if ( get_option( 'bang_social_defaults_migrated' ) ) {
		return;
	}
	$saved = get_option( BANG_OPTION_KEY, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$changed = false;
	foreach ( bang_default_options() as $key => $default ) {
		if ( 0 !== strpos( $key, 'social_' ) || '' === $default ) {
			continue;
		}
		if ( array_key_exists( $key, $saved ) && '' === trim( (string) $saved[ $key ] ) ) {
			$saved[ $key ] = $default;
			$changed       = true;
		}
	}
	if ( $changed ) {
		update_option( BANG_OPTION_KEY, $saved );
	}
	update_option( 'bang_social_defaults_migrated', 1 );
}

/**
 * 读取单项设置。
 */
function bang_opt( $key ) {
	$options = bang_options();
	return isset( $options[ $key ] ) ? $options[ $key ] : '';
}

/**
 * 界面与交互配置：集中注入到 bangFront.ui，供 app.js 读取。
 * 后台已移除这组开关菜单，逐项按固定默认值（全为 1）读取，视觉与行为零变化。
 */
function bang_ui_config() {
	return array(
		'sweepOn'       => (int) bang_opt( 'ui_sweep_on' ),
		'sweepMs'       => max( 100, min( 1500, (int) bang_opt( 'ui_sweep_ms' ) ) ),
		'progressOn'    => (int) bang_opt( 'ui_progress_on' ),
		'postNavOn'     => (int) bang_opt( 'ui_post_nav_on' ),
		'swipeOn'       => (int) bang_opt( 'ui_swipe_on' ),
		'keyNavOn'      => (int) bang_opt( 'ui_key_nav_on' ),
		'toTopOn'       => (int) bang_opt( 'ui_to_top_on' ),
		'toTopOffset'   => max( 100, min( 2000, (int) bang_opt( 'ui_to_top_offset' ) ) ),
		'linkDialogOn'  => (int) bang_opt( 'ui_link_dialog_on' ),
		'cmdkOn'        => (int) bang_opt( 'ui_cmdk_on' ),
		'cmdkAiOn'      => (int) bang_opt( 'ui_cmdk_ai_on' ),
		'codeHighlight' => (int) bang_opt( 'ui_code_highlight_on' ),
		'codeCopy'      => (int) bang_opt( 'ui_code_copy_on' ),
		'lightboxOn'    => (int) bang_opt( 'ui_lightbox_on' ),
		'shareOn'       => (int) bang_opt( 'ui_share_on' ),
		'morphOn'       => (int) bang_opt( 'ui_morph_on' ),
	);
}

/**
 * 页脚导航：一行一条「名称 | 链接 | blank」。
 * 链接可写站内路径（/about/）或完整网址，第三列填 blank 表示新窗口打开。
 * 留空返回空数组，footer.php 据此整行不输出。
 */
function bang_footer_nav_items() {
	$raw = trim( (string) bang_opt( 'ui_footer_nav' ) );
	$out = array();
	/* 后台导航可以留空；留空时仍要输出下面的固定入口，不能提前 return */
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
			/* 单段路径（/blocks/）先交给 bang_site_page_url 解析真实 permalink：
			   固定链接为 /%post_id%/ 时 get_permalink() 与 /slug/ 并不一致。 */
			$slug = trim( $url, '/' );
			if ( '' !== $slug && false === strpos( $slug, '/' ) && function_exists( 'bang_site_page_url' ) ) {
				$maybe = bang_site_page_url( $slug );
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

	/* 站点固定入口：文章归档 / 隐私政策 / 用户协议 / 站点地图 / RSS 订阅始终出现在页脚末尾。
	   后台 ui_footer_nav 里若已填过同名条目，按名称去重，不重复输出。 */
	$bang_fixed = array(
		array( 'name' => __( '文章归档', 'bang' ), 'url' => function_exists( 'bang_site_page_url' ) ? bang_site_page_url( 'archive' ) : home_url( '/archive/' ), 'blank' => false ),
		array( 'name' => __( '隐私政策', 'bang' ), 'url' => function_exists( 'bang_site_page_url' ) ? bang_site_page_url( 'privacy' ) : home_url( '/privacy/' ), 'blank' => false ),
		array( 'name' => __( '用户协议', 'bang' ), 'url' => function_exists( 'bang_site_page_url' ) ? bang_site_page_url( 'terms' ) : home_url( '/terms/' ), 'blank' => false ),
		array( 'name' => __( '站点地图', 'bang' ), 'url' => home_url( '/wp-sitemap.xml' ), 'blank' => true ),
		array( 'name' => __( 'RSS订阅', 'bang' ), 'url' => home_url( '/feed/' ), 'blank' => true ),
	);
	if ( bang_opt( 'reward_on' ) ) {
		$bang_fixed[] = array( 'name' => __( '打赏', 'bang' ), 'url' => function_exists( 'bang_site_page_url' ) ? bang_site_page_url( 'reward' ) : home_url( '/reward/' ), 'blank' => false );
	}
	if ( bang_opt( 'footprint_on' ) ) {
		$bang_fixed[] = array( 'name' => __( '足迹', 'bang' ), 'url' => function_exists( 'bang_site_page_url' ) ? bang_site_page_url( 'footprint' ) : home_url( '/footprint/' ), 'blank' => false );
	}
	if ( bang_opt( 'gallery_on' ) ) {
		$bang_fixed[] = array( 'name' => __( '图片集', 'bang' ), 'url' => function_exists( 'bang_site_page_url' ) ? bang_site_page_url( 'gallery' ) : home_url( '/gallery/' ), 'blank' => false );
	}
	$bang_names = wp_list_pluck( $out, 'name' );
	foreach ( $bang_fixed as $bang_item ) {
		if ( in_array( $bang_item['name'], $bang_names, true ) ) {
			continue;
		}
		$out[]      = array(
			'name'  => $bang_item['name'],
			'url'   => esc_url_raw( $bang_item['url'] ),
			'blank' => ! empty( $bang_item['blank'] ),
		);
		$bang_names[] = $bang_item['name'];
	}

	return $out;
}

/**
 * 字段定义。type: text / url / number / textarea / code / checkbox / select
 */
function bang_setting_raw_tabs() {
	return array(
		'general'  => array(
			'label'  => __( '常规', 'bang' ),
			'desc'   => __( '站点外观与内容的基础设置，保存后立即在前台生效。', 'bang' ),
			'fields' => array(
				array(
					'key'     => 'author_bio_mode',
					'type'    => 'select',
					'label'   => __( '作者卡签名来源', 'bang' ),
					'desc'    => __( '文章右侧作者卡头像下的那行签名文字。作者资料 = 用 WordPress 用户的个人简介；自定义 = 用下方填写的固定文案；一言接口 = 每次打开文章从下方 API 随机取一句（如「一言」Hitokoto）。', 'bang' ),
					'choices' => array(
						'profile' => __( '作者资料（用户个人简介）', 'bang' ),
						'custom'  => __( '自定义文案', 'bang' ),
						'api'     => __( '一言接口（随机语录）', 'bang' ),
					),
				),
				array( 'key' => 'author_bio_custom', 'type' => 'textarea', 'rows' => 3, 'label' => __( '自定义签名文案', 'bang' ), 'desc' => __( '仅在签名来源选「自定义文案」时生效。支持换行，留空则不显示签名。', 'bang' ) ),
				array( 'key' => 'author_bio_api', 'type' => 'text', 'label' => __( '一言接口地址', 'bang' ), 'desc' => __( '仅在签名来源选「一言接口」时生效。返回一个 JSON 对象（取 hitokoto / content / text / quote 字段）或直接返回纯文本的接口均可，默认 https://v1.hitokoto.cn/?encode=json 。跨域失败时自动回退到下方兜底文案。', 'bang' ) ),
				array( 'key' => 'author_bio_fallback', 'type' => 'text', 'label' => __( '一言兜底文案', 'bang' ), 'desc' => __( '一言接口请求失败或超时（3 秒）时显示的文字；也用作接口返回为空时的默认值。', 'bang' ) ),
				array( 'key' => 'subscribe_on', 'type' => 'checkbox', 'label' => __( '首页订阅卡片', 'bang' ), 'desc' => __( '在首页文章列表与分页器下方显示邮箱订阅卡片。', 'bang' ) ),
				array( 'key' => 'subscribe_title', 'type' => 'text', 'label' => __( '订阅标题', 'bang' ), 'desc' => __( '留空回退为「订阅文章推送」。', 'bang' ) ),
				array( 'key' => 'subscribe_desc', 'type' => 'text', 'label' => __( '订阅说明', 'bang' ), 'desc' => __( '标题下面的一行小字，留空则不显示。', 'bang' ) ),
				array( 'key' => 'notice_on', 'type' => 'checkbox', 'label' => __( '全站公告弹窗', 'bang' ), 'desc' => __( '访客首次访问任意页面时居中弹出。点「确认」后永久不再显示；「我记住啦」只关本次。改了标题或正文会重新弹一次。', 'bang' ) ),
				array( 'key' => 'notice_mode', 'type' => 'select', 'label' => __( '显示方式', 'bang' ), 'desc' => __( '「确认后不再显示」：点确认后该访客永久不再弹（改了公告会重新弹）。「一直显示」：不记忆关闭状态，每次打开页面都弹。', 'bang' ), 'choices' => array( 'ack' => __( '确认后不再显示', 'bang' ), 'always' => __( '一直显示', 'bang' ) ) ),
				array( 'key' => 'notice_title', 'type' => 'text', 'label' => __( '公告标题', 'bang' ), 'desc' => __( '留空回退为「网站公告」。', 'bang' ) ),
				array( 'key' => 'notice_text', 'type' => 'textarea', 'label' => __( '公告正文', 'bang' ), 'rows' => 4, 'desc' => __( '纯文本，换行自动分段；留空则不弹窗。', 'bang' ) ),
				array( 'key' => 'notice_later', 'type' => 'text', 'label' => __( '次要按钮文案', 'bang' ), 'desc' => __( '只关本次访问，留空回退为「我记住啦~」。', 'bang' ) ),
				array( 'key' => 'notice_ok', 'type' => 'text', 'label' => __( '确认按钮文案', 'bang' ), 'desc' => __( '点击后永久不再弹，留空回退为「确认」。', 'bang' ) ),
				array( 'key' => 'show_comments', 'type' => 'checkbox', 'label' => __( '显示评论区', 'bang' ), 'desc' => __( '关闭后文章页不再输出评论列表与表单。', 'bang' ) ),
				array( 'key' => 'copyright_on', 'type' => 'checkbox', 'label' => __( '文章底部版权声明', 'bang' ), 'desc' => __( '在正文末尾输出作者、首发链接与许可协议；首页展开层与独立文章页同步显示。', 'bang' ) ),
				array( 'key' => 'related_on', 'type' => 'checkbox', 'label' => __( '相关文章', 'bang' ), 'desc' => __( '在正文末尾版权声明下方输出「相关阅读」时间线，按同分类 / 同标签匹配，不足用近期文章补齐。', 'bang' ) ),
				array( 'key' => 'ranking_on', 'type' => 'checkbox', 'label' => __( '浏览量与热门榜', 'bang' ), 'desc' => __( '开启后文章页统计浏览量（同一浏览器 24 小时内只计一次），并在文章归档页顶部显示热门文章榜与正文浏览量。', 'bang' ) ),
				array(
					'key'     => 'copyright_license',
					'type'    => 'select',
					'label'   => __( '默认许可协议', 'bang' ),
					'desc'    => __( '只影响版权声明里的协议文案，不会给页面加额外标记。', 'bang' ),
					'choices' => array(
						'by-nc-sa'   => __( 'CC BY-NC-SA 4.0（署名-非商业-相同方式共享）', 'bang' ),
						'by'         => __( 'CC BY 4.0（署名）', 'bang' ),
						'all-rights' => __( '保留所有权利', 'bang' ),
						'none'       => __( '不写许可协议', 'bang' ),
					),
				),
				array( 'key' => 'copyright_note', 'type' => 'textarea', 'label' => __( '版权补充说明', 'bang' ), 'rows' => 3, 'desc' => __( '可选，纯文本，换行自动分段。例如「转载请先邮件联系」。', 'bang' ) ),
				array(
					'key'   => 'sidebar_widgets',
					'type'  => 'widgets',
					'label' => __( '文章侧栏小工具', 'bang' ),
					'desc'  => __( '勾选启用、按住左侧手柄拖动排序。未勾选的不显示；顺序即前台文章侧栏的显示顺序。', 'bang' ),
				),
				array( 'key' => 'sidebar_widget_html', 'type' => 'code', 'rows' => 6, 'label' => __( '自定义 HTML 内容', 'bang' ), 'desc' => __( '仅在上方启用「自定义 HTML」小工具时显示于文章侧栏。只有具备未过滤 HTML 权限的管理员可原样保存。', 'bang' ) ),
			),
		),
		'site'     => array(
			'label'  => __( '站点设置', 'bang' ),
			'desc'   => __( '站点图标、深浅色 Logo、时区与备案号。保存后立即在前台生效。', 'bang' ),
			'fields' => array(
				array( 'key' => 'site_icon_url', 'type' => 'url', 'media' => true, 'label' => __( '站点图标（Favicon）', 'bang' ), 'desc' => __( '浏览器标签页、书签与添加到桌面的图标，建议 512×512 的 PNG / SVG / ICO。留空则使用 WordPress 自带的站点图标（外观 → 自定义 → 站点身份）。', 'bang' ) ),
				array( 'key' => 'logo_url', 'type' => 'url', 'media' => true, 'label' => __( '白色模式 Logo（浅色背景）', 'bang' ), 'desc' => __( '白底 / 浅色模式下侧栏与顶栏显示的 Logo，默认用 CDN 彩色原图；留空则回退为文字站名。也用作作者卡头像的最后兜底。', 'bang' ) ),
				array( 'key' => 'logo_dark_url', 'type' => 'url', 'media' => true, 'label' => __( '黑色模式 Logo（深色背景）', 'bang' ), 'desc' => __( '黑底 / 深色模式下显示的 Logo，通常放反白版本。留空则沿用「白色模式 Logo」，两种模式显示同一张。', 'bang' ) ),
				array(
					'key'     => 'site_timezone',
					'type'    => 'select',
					'label'   => __( '站点时区', 'bang' ),
					'desc'    => __( '决定文章时间、归档日期与邮件时间戳的显示时区。留空 = 跟随「设置 → 常规 → 时区」；选了自定义时区会直接写入 WordPress 的 timezone_string，日期、RSS 与后台列表一起生效。', 'bang' ),
					'choices' => bang_timezone_choices(),
				),
				array(
					'key'     => 'lang_default',
					'type'    => 'select',
					'label'   => __( '语言首选', 'bang' ),
					'desc'    => __( '访客首次访问、尚未手动选过语言时使用的语言。「跟随浏览器」保留原行为：中文浏览器自动识别，非中文浏览器保持中文并弹确认卡；选定具体语言则直接以该语言呈现，不再弹确认卡。访客在点开语言菜单手动选择后，个人偏好会长期记住并优先于此项。', 'bang' ),
					'choices' => array(
						'detect'              => __( '跟随浏览器', 'bang' ),
						'chinese_simplified'  => __( '简体中文', 'bang' ),
						'chinese_traditional' => __( '繁體中文', 'bang' ),
						'english'             => __( 'English', 'bang' ),
						'japanese'            => __( '日本語', 'bang' ),
						'korean'              => __( '한국어', 'bang' ),
					),
				),
				array( 'key' => 'icp', 'type' => 'text', 'label' => __( 'ICP 备案号', 'bang' ), 'desc' => __( '可选，显示在页脚，如「粤ICP备12345678号」。', 'bang' ) ),
				array( 'key' => 'police_icp', 'type' => 'text', 'label' => __( '公安备案号', 'bang' ), 'desc' => __( '可选，显示在页脚，如「粤公网安备44030000000000号」。', 'bang' ) ),
				array( 'key' => 'footer_text', 'type' => 'text', 'label' => __( '页脚文字', 'bang' ), 'desc' => __( '显示在站点名之后。', 'bang' ) ),
				array(
					'key'  => 'footer_links',
					'type' => 'links',
					'label' => __( '页脚链接分组', 'bang' ),
					'desc'  => __( '按分组填写页脚链接列：每组一个标题，下面是若干「链接名 + 链接」；链接可写站内路径（/about/）或完整网址。删除整部分组即隐藏该列。', 'bang' ),
				),
				array(
					'key'     => 'tabbar_style',
					'type'    => 'select',
					'label'   => __( '手机端底部导航样式', 'bang' ),
					'desc'    => __( '悬浮胶囊：圆角浮条、左右留边；贴合底部：通栏贴底、直角、顶部一条细边。仅在手机端（宽度小于 1024px）显示，PC 端不显示。', 'bang' ),
					'choices' => array(
						'float' => __( '悬浮胶囊', 'bang' ),
						'flush' => __( '贴合底部', 'bang' ),
					),
				),
			),
		),
		'slide'    => array(
			'label'  => __( '幻灯片设置', 'bang' ),
			'desc'   => __( '首页顶部的幻灯片轮播与下方的滚动公告栏，仅在首页显示（分类页不出现）。', 'bang' ),
			'fields' => array(
				array(
					'key'     => 'slides_mode',
					'type'    => 'select',
					'label'   => __( '首页幻灯片', 'bang' ),
					'desc'    => __( 'PC 端首页顶部的轮播卡片，只在首页显示（分类页不出现）。', 'bang' ),
					'choices' => array(
						'random' => __( '自动：随机挑选文章', 'bang' ),
						'manual' => __( '手动：按下面填写的 ID 顺序', 'bang' ),
						'off'    => __( '关闭：不显示幻灯片', 'bang' ),
					),
				),
				array( 'key' => 'slides_count', 'type' => 'number', 'label' => __( '幻灯片数量', 'bang' ), 'desc' => __( '自动模式下随机取几篇，建议 3–5。', 'bang' ), 'min' => 1, 'max' => 8 ),
				array( 'key' => 'slides_posts', 'type' => 'text', 'label' => __( '指定文章 ID', 'bang' ), 'desc' => __( '仅手动模式生效，逗号分隔，例如 75,7,12；顺序即播放顺序。留空或 ID 全部无效时回退随机。', 'bang' ) ),
				array( 'key' => 'ticker_on', 'type' => 'checkbox', 'label' => __( '首页公告栏', 'bang' ), 'desc' => __( '首页幻灯片下方的一条横向滚动公告，鼠标悬停暂停。', 'bang' ) ),
				array( 'key' => 'ticker_tag', 'type' => 'text', 'label' => __( '公告栏标签', 'bang' ), 'desc' => __( '条带最左侧黑底小标签的文字，留空则只用底色不显示标签。', 'bang' ) ),
				array( 'key' => 'ticker_text', 'type' => 'textarea', 'label' => __( '公告栏内容', 'bang' ), 'rows' => 6, 'placeholder' => bang_ticker_default_text(), 'desc' => __( '只滚这里写的内容，不再抓取文章。一行一条，格式「文字 | 链接 | blank」：链接可写站内路径（/about/）或完整网址，第三列填 blank 表示新窗口，后两列可省略。留空则滚灰色占位里的内置文案；要整条隐藏请关闭上方开关。', 'bang' ) ),
			),
		),
		'contact'  => array(
			'label'  => __( '联系方式设置', 'bang' ),
			'desc'   => __( '联系邮箱与作者卡的社交平台链接。社交链接留空则不显示对应图标。', 'bang' ),
			'fields' => array(
				array( 'key' => 'contact_email', 'type' => 'text', 'label' => __( '联系邮箱', 'bang' ), 'desc' => __( '显示在「联系」页；有人提交友链申请时发通知到这个邮箱，留空则使用管理员邮箱。', 'bang' ) ),
				array( 'key' => 'social_qq', 'type' => 'text', 'label' => __( 'QQ', 'bang' ), 'desc' => __( '作者卡社交图标链接，填完整地址（如 https://q.qlogo.cn/... 或 QQ 空间主页）。留空不显示该图标。', 'bang' ) ),
				array( 'key' => 'social_wechat', 'type' => 'text', 'label' => __( '微信', 'bang' ), 'desc' => __( '微信号或二维码图片 / 说明页地址。图标只作展示与复制提示，移动端不会自动打开微信。留空不显示。', 'bang' ) ),
				array( 'key' => 'social_douyin', 'type' => 'text', 'label' => __( '抖音', 'bang' ), 'desc' => __( '抖音主页完整地址，留空不显示该图标。', 'bang' ) ),
				array( 'key' => 'social_x', 'type' => 'text', 'label' => __( 'X（原 Twitter）', 'bang' ), 'desc' => __( 'X 主页完整地址，留空不显示该图标。', 'bang' ) ),
				array( 'key' => 'social_weibo', 'type' => 'text', 'label' => __( '微博', 'bang' ), 'desc' => __( '微博主页完整地址，留空不显示该图标。', 'bang' ) ),
				array( 'key' => 'social_github', 'type' => 'text', 'label' => __( 'GitHub', 'bang' ), 'desc' => __( 'GitHub 主页完整地址，留空不显示该图标。', 'bang' ) ),
				array( 'key' => 'social_bilibili', 'type' => 'text', 'label' => __( '哔哩哔哩', 'bang' ), 'desc' => __( 'B 站主页完整地址，留空不显示该图标。', 'bang' ) ),
			),
		),
	'reward'   => array(
		'label'  => __( '打赏设置', 'bang' ),
		'desc'   => __( '打赏卡片（文章侧栏小工具）与打赏页。二维码图片和打赏链接可以只填一种，也可以两种都填；两项都留空时打赏页给出配置提示。', 'bang' ),
		'fields' => array(
			array( 'key' => 'reward_on', 'type' => 'checkbox', 'label' => __( '启用打赏', 'bang' ), 'desc' => __( '关闭后文章侧栏不再输出打赏卡片，打赏页显示未开启提示。', 'bang' ) ),
			array( 'key' => 'reward_qr', 'type' => 'url', 'media' => true, 'label' => __( '打赏二维码', 'bang' ), 'desc' => __( '上传收款二维码图片（微信 / 支付宝 / 其它均可），打赏页会显示可扫码的大图。留空则不显示扫码卡。', 'bang' ) ),
			array( 'key' => 'reward_link', 'type' => 'url', 'label' => __( '打赏链接', 'bang' ), 'desc' => __( '点按钮前往的打赏地址，只接受 http / https。留空则不显示链接卡。', 'bang' ) ),
			array( 'key' => 'reward_title', 'type' => 'text', 'label' => __( '打赏页标题', 'bang' ), 'desc' => __( '留空回退为「打赏」。', 'bang' ) ),
			array( 'key' => 'reward_desc', 'type' => 'textarea', 'rows' => 3, 'label' => __( '打赏页说明', 'bang' ), 'desc' => __( '标题下面的一行小字，留空回退为默认文案。', 'bang' ) ),
			array( 'key' => 'reward_btn_text', 'type' => 'text', 'label' => __( '按钮文字', 'bang' ), 'desc' => __( '打赏卡片与打赏页「链接打赏」卡上的按钮文字，留空回退为「前往打赏」。', 'bang' ) ),
		),
	),
	'footprint' => array(
		'label'  => __( '足迹地图', 'bang' ),
		'desc'   => __( '足迹页（/footprint/）的单色极简高德地图（白底浅灰轮廓，无路况）。足迹内容在后台「足迹」菜单添加；坐标取点后填入即可。需填高德浏览器端 Key；前台可全屏查看。', 'bang' ),
		'fields' => array(
			array( 'key' => 'footprint_on', 'type' => 'checkbox', 'label' => __( '启用足迹页', 'bang' ), 'desc' => __( '关闭后侧栏不再显示足迹入口，足迹页显示未开启提示。', 'bang' ) ),
			array( 'key' => 'footprint_js_key', 'type' => 'text', 'label' => __( '高德浏览器端 Key', 'bang' ), 'desc' => __( '高德开放平台「Web端(JS API)」类型的 Key。留空则地图无法加载并提示。', 'bang' ) ),
			array( 'key' => 'footprint_web_key', 'type' => 'text', 'label' => __( '高德 Web 服务 Key（预留）', 'bang' ), 'desc' => __( '预留字段，当前未使用；经纬度在后台「足迹」里手动填写即可，可留空。', 'bang' ) ),
			array( 'key' => 'footprint_types', 'type' => 'text', 'label' => __( '足迹类型', 'bang' ), 'desc' => __( '后台编辑足迹时可选的类型，英文逗号分隔。', 'bang' ) ),
		),
	),
	'gallery' => array(
		'label'  => __( '图片集', 'bang' ),
		'desc'   => __( '图片集页（/gallery/）展示后台独立建立的图片集相册：一条相册一张瀑布流卡片，封面取第一张图，其余图进入灯箱，卡片底部遮罩显示标题与位置。新增相册请到后台「图片集」菜单。', 'bang' ),
		'fields' => array(
			array( 'key' => 'gallery_on', 'type' => 'checkbox', 'label' => __( '启用图片集', 'bang' ), 'desc' => __( '关闭后侧栏不再显示图片集入口，图片集页显示未开启提示。', 'bang' ) ),
			array( 'key' => 'gallery_per_page', 'type' => 'number', 'label' => __( '每页卡片数', 'bang' ), 'desc' => __( '图片集页一页最多展示多少个相册，建议 8–24。', 'bang' ), 'min' => 4, 'max' => 120 ),
		),
	),
	'safety' => array(
		'label'  => __( '安全设置', 'bang' ),
		'desc'   => __( '注册限制、违禁词词表与人机校验。注册邮箱后缀限制只作用于「用户中心」注册；违禁词只从主题 inc/data 目录读取——把 .txt 放进去就自动识别，后台不用填词也不用填路径；人机校验默认自研滑块，也可切换到极验 v4。', 'bang' ),
		'fields' => array(
			array( 'key' => 'allow_register', 'type' => 'checkbox', 'label' => __( '开放注册', 'bang' ), 'desc' => __( '开启后可在「用户中心」自行注册账号（同步 WordPress 的「任何人都可以注册」）。关闭则只能由管理员建号。', 'bang' ) ),
			array( 'key' => 'register_email_domains', 'type' => 'textarea', 'label' => __( '注册邮箱后缀限制', 'bang' ), 'rows' => 5, 'placeholder' => "留空 = 不限制\n@qq.com\n@gmail.com\n# 以 - 开头为拒绝列表\n-example.com", 'desc' => __( '留空则不限制。一行一个后缀，可写 @qq.com 或 qq.com；以 - 开头表示拒绝该后缀（如 -qq.com 表示禁止 qq.com），其余行组成允许列表——只允许列表里的后缀注册。', 'bang' ) ),
			array( 'key' => 'moderation_on', 'type' => 'checkbox', 'label' => __( '启用违禁词审核', 'bang' ), 'desc' => __( '关闭后完全跳过扫描，已有词表不再生效。', 'bang' ) ),
			array( 'key' => 'moderation_threshold', 'type' => 'number', 'label' => __( '命中阈值（累计权重）', 'bang' ), 'desc' => __( '留空或 0 = 命中任一词即算命中（老行为）。填了数字（如 10）就要求同一条内容里命中的词权重加起来达到这个数才算命中——单独一个低权重词不会被拦。', 'bang' ), 'min' => 0, 'max' => 100 ),
			array(
				'key'     => 'moderation_action',
				'type'    => 'select',
				'label'   => __( '命中后的动作', 'bang' ),
				'desc'    => __( '待审核：评论照常入库但需管理员放行，前端提示「待审核后显示」；直接拒绝：不入库并提示修改；替换为 ***：违禁词打码后照常显示。', 'bang' ),
				'choices' => array(
					'hold'   => __( '待审核（推荐）', 'bang' ),
					'reject' => __( '直接拒绝提交', 'bang' ),
					'mask'   => __( '替换为 ***', 'bang' ),
				),
			),
			array( 'key' => 'captcha_mode', 'type' => 'select', 'label' => __( '人机校验 · 验证方式', 'bang' ), 'desc' => __( '注册与评论前的验证方式。自研滑块：无第三方依赖、内网与离线都能用。极验 v4：需填下方 Captcha ID 与 Key；ID / Key 没配齐、脚本或接口连不上时，前台自动回退到自研滑块。', 'bang' ), 'choices' => array( 'local' => __( '自研滑块（默认，离线可用）', 'bang' ), 'geetest' => __( '极验行为验证 v4', 'bang' ) ) ),
			array( 'key' => 'geetest_id', 'type' => 'text', 'label' => __( '极验 Captcha ID', 'bang' ), 'desc' => __( '极验后台「行为验证 v4」应用的 Captcha ID，会下发到前台用于初始化；留空则不启用极验。', 'bang' ) ),
			array( 'key' => 'geetest_key', 'type' => 'text', 'label' => __( '极验 Key', 'bang' ), 'desc' => __( '极验后台的验证密钥，只保存在服务端用于生成 sign_token，绝不下发到前台。留空则不启用极验。', 'bang' ) ),
			array( 'key' => 'geetest_validate_url', 'type' => 'text', 'label' => __( '二次校验接口', 'bang' ), 'desc' => __( '一般不用改。默认 https://gcaptcha4.geetest.com/validate；如果极验分配了专用域名，在这里换成完整地址即可。', 'bang' ) ),
		),
	),
	'mail'     => array(
		'label'  => __( '邮件', 'bang' ),
		'desc'   => __( '用 SMTP 发信：填好主机 / 端口 / 账号密码后打开开关，站内所有 wp_mail（友链申请通知等）都会改走这台服务器。留空或关闭开关时沿用 WordPress 默认发信方式，本地预览与未配置站点不受影响。', 'bang' ),
		'fields' => array(
			array( 'key' => 'smtp_on', 'type' => 'checkbox', 'label' => __( '启用 SMTP 发信', 'bang' ), 'desc' => __( '打开后所有 wp_mail 走下方配置；关闭则完全交还给 WordPress 默认发信。', 'bang' ) ),
			array( 'key' => 'smtp_host', 'type' => 'text', 'label' => __( 'SMTP 主机', 'bang' ), 'desc' => __( '例如 smtp.exmail.qq.com、smtp.gmail.com。留空即使打开开关也不会接管发信。', 'bang' ) ),
			array( 'key' => 'smtp_port', 'type' => 'number', 'label' => __( '端口', 'bang' ), 'desc' => __( 'SSL 常用 465，STARTTLS / 无加密常用 587，明文常用 25。', 'bang' ), 'min' => 1, 'max' => 65535 ),
			array(
				'key'     => 'smtp_encryption',
				'type'    => 'select',
				'label'   => __( '加密方式', 'bang' ),
				'desc'    => __( '按服务商要求选择，选错会导致连接被拒绝。', 'bang' ),
				'choices' => array(
					'ssl'      => __( 'SSL / TLS（端口 465）', 'bang' ),
					'starttls' => __( 'STARTTLS（端口 587）', 'bang' ),
					'none'     => __( '不加密（端口 25，仅内网）', 'bang' ),
				),
			),
			array( 'key' => 'smtp_user', 'type' => 'text', 'label' => __( '账号', 'bang' ), 'desc' => __( 'SMTP 登录名，一般就是完整邮箱地址。', 'bang' ) ),
			array( 'key' => 'smtp_pass', 'type' => 'text', 'label' => __( '密码 / 授权码', 'bang' ), 'desc' => __( '多数服务商要求填「授权码」而不是登录密码。只保存在本站数据库，不会下发到前台。', 'bang' ) ),
			array( 'key' => 'smtp_from', 'type' => 'text', 'label' => __( '发件人地址', 'bang' ), 'desc' => __( '留空则用上方账号作为发件人；必须与服务商允许的发件域名一致，否则会被拒收。', 'bang' ) ),
			array( 'key' => 'smtp_from_name', 'type' => 'text', 'label' => __( '发件人名称', 'bang' ), 'desc' => __( '收件人看到的发件人名字，留空则用站点名称。', 'bang' ) ),
			array( 'key' => 'smtp_test_to', 'type' => 'text', 'label' => __( '测试收件地址', 'bang' ), 'desc' => __( '点下方「发送测试邮件」时收信的地址，留空则发给管理员邮箱。', 'bang' ) ),
		),
	),
		'seo'      => array(
			'label'  => __( 'SEO', 'bang' ),
			'desc'   => __( '标题、描述、canonical、Open Graph、结构化数据与站点验证。留空的字段会自动取文章内容。', 'bang' ),
			'fields' => array(
				array( 'key' => 'seo_home_title', 'type' => 'text', 'label' => __( '首页标题', 'bang' ), 'desc' => __( '留空则使用站点名称。', 'bang' ) ),
				array( 'key' => 'seo_home_desc', 'type' => 'textarea', 'label' => __( '首页描述', 'bang' ), 'rows' => 2, 'desc' => __( '建议 60–120 字，会输出为 meta description 与 og:description。', 'bang' ) ),
				array( 'key' => 'title_sep', 'type' => 'text', 'label' => __( '标题分隔符', 'bang' ), 'desc' => __( '文章标题与站点名之间的符号。', 'bang' ) ),
				array( 'key' => 'seo_keywords', 'type' => 'text', 'label' => __( '关键词', 'bang' ), 'desc' => __( '可选，用英文逗号分隔。主流搜索引擎已不依赖此项。', 'bang' ) ),
				array( 'key' => 'seo_canonical', 'type' => 'checkbox', 'label' => __( '输出 canonical', 'bang' ), 'desc' => __( '首页、文章、分类页自动输出规范链接。', 'bang' ) ),
				array( 'key' => 'seo_og', 'type' => 'checkbox', 'label' => __( '输出 Open Graph', 'bang' ), 'desc' => __( '微信、微博、Facebook 分享卡片所需。', 'bang' ) ),
				array( 'key' => 'seo_twitter', 'type' => 'checkbox', 'label' => __( '输出 Twitter Card', 'bang' ) ),
				array( 'key' => 'seo_jsonld', 'type' => 'checkbox', 'label' => __( '输出结构化数据', 'bang' ), 'desc' => __( 'WebSite / BlogPosting / BreadcrumbList（schema.org）。', 'bang' ) ),
				array( 'key' => 'og_default_image', 'type' => 'url', 'media' => true, 'label' => __( '默认分享图', 'bang' ), 'desc' => __( '文章无特色图时使用，建议 1200×630。', 'bang' ) ),
				array( 'key' => 'twitter_site', 'type' => 'text', 'label' => __( 'Twitter 账号', 'bang' ), 'desc' => __( '可选，形如 @example。', 'bang' ) ),
				array( 'key' => 'noindex_search', 'type' => 'checkbox', 'label' => __( '搜索结果页 noindex', 'bang' ) ),
				array( 'key' => 'noindex_archive', 'type' => 'checkbox', 'label' => __( '归档页 noindex', 'bang' ), 'desc' => __( '日期、作者、标签归档；分类归档不受影响。', 'bang' ) ),
				array( 'key' => 'noindex_404', 'type' => 'checkbox', 'label' => __( '404 页 noindex', 'bang' ) ),
				array( 'key' => 'google_verify', 'type' => 'text', 'label' => __( 'Google 站点验证', 'bang' ), 'desc' => __( '只填验证码，不用贴完整 meta 标签。', 'bang' ) ),
				array( 'key' => 'bing_verify', 'type' => 'text', 'label' => __( 'Bing 站点验证', 'bang' ) ),
				array( 'key' => 'baidu_verify', 'type' => 'text', 'label' => __( '百度站点验证', 'bang' ) ),
			),
		),
		'unlock'   => array(
			'label'  => __( '阅读解锁', 'bang' ),
			'desc'   => __( '正文里用「阅读解锁」区块或 [unlock] 简码，把一段内容锁起来；「下载」区块（[download] 简码）复用同一套解锁。方式在区块上五选一：登录可见 / 点赞可见 / 回复可见 / 密码可见 / 点赞+评论可见；入群已屏蔽。前四种不需要额外配置，密码可见用下面的站点密码或区块专属密码；叠加解锁需点赞与评论两步都完成（密码不参与叠加）。', 'bang' ),
			'fields' => array(
				array( 'key' => 'unlock_password', 'type' => 'text', 'label' => __( '站点解锁密码', 'bang' ), 'desc' => __( '「密码可见」的默认密码：区块上没单独设置密码时才用它。读者在锁定卡片里输入即可解锁，30 天内免重输。留空则自动使用一串固定的站点派生密码（显示在下方说明里）。也可给单篇文章加自定义字段 bang_unlock_password 覆盖，或在编辑器里把「阅读解锁」切成「密码可见」直接填本段专属密码。', 'bang' ) ),
				array( 'key' => 'unlock_hint', 'type' => 'textarea', 'label' => __( '自定义说明', 'bang' ), 'rows' => 3, 'desc' => __( '可选，显示在锁定卡片标题下方。留空则用默认文案。', 'bang' ) ),
				array( 'key' => 'unlock_password_text', 'type' => 'textarea', 'label' => __( '密码解锁提示文案', 'bang' ), 'rows' => 3, 'desc' => __( '只作用于「密码可见」。显示在密码输入框上方的醒目提示里，可写引导语，例如「关注公众号「奶狗资源」回复关键词获取密码」。支持纯文本换行，留空则不显示这行。', 'bang' ) ),
			),
		),
		'quicklogin' => array(
			'label'  => __( '快捷登录', 'bang' ),
			'desc'   => __( 'QQ 官方 OAuth2 与奶狗彩虹聚合登录。两条通道共用同一个回调地址：/wp-json/bang/v1/quick-login/callback —— 需把它分别登记到 QQ 互联后台的「回调地址」与彩虹聚合后台的回调域名里（域名要一致）。密钥只在服务端使用，不会下发到浏览器。用户首次用某平台登录会自动建号并绑定该平台 UID，之后同一 UID 直接登录；已登录时再点会绑定到当前账号。', 'bang' ),
			'fields' => array(
				array( 'key' => 'quicklogin_on', 'type' => 'checkbox', 'label' => __( '开启快捷登录', 'bang' ), 'desc' => __( '总开关。关闭后登录卡片不显示任何快捷登录按钮。', 'bang' ) ),
				array( 'key' => 'qq_on', 'type' => 'checkbox', 'label' => __( '启用 QQ 官方登录', 'bang' ), 'desc' => __( '直连 connect.qq.com 的官方 OAuth2，需在 QQ 互联（connect.qq.com）创建应用并把回调地址设为上面那个地址。', 'bang' ) ),
				array( 'key' => 'qq_appid', 'type' => 'text', 'label' => __( 'QQ AppID', 'bang' ), 'desc' => __( 'QQ 互联应用的 AppID。', 'bang' ) ),
				array( 'key' => 'qq_appkey', 'type' => 'text', 'label' => __( 'QQ AppKey', 'bang' ), 'desc' => __( 'QQ 互联应用的 AppKey（密钥，只存服务端）。', 'bang' ) ),
				array( 'key' => 'rainbow_on', 'type' => 'checkbox', 'label' => __( '启用彩虹聚合登录', 'bang' ), 'desc' => __( '一套 appid/appkey 支持 QQ / 微信 / 微博 / 抖音等多个平台。', 'bang' ) ),
				array( 'key' => 'rainbow_api', 'type' => 'url', 'label' => __( '彩虹接口地址', 'bang' ), 'desc' => __( '聚合登录的 connect.php 完整地址，默认 https://u.naigou.cn/connect.php。自建彩虹或第三方兼容服务只要实现同样的 act=login / act=callback 协议即可，填自己的接口地址；留空回退默认。', 'bang' ) ),
				array( 'key' => 'rainbow_appid', 'type' => 'text', 'label' => __( '彩虹 AppID', 'bang' ), 'desc' => __( '彩虹聚合后台分配的 appid。', 'bang' ) ),
				array( 'key' => 'rainbow_appkey', 'type' => 'text', 'label' => __( '彩虹 AppKey', 'bang' ), 'desc' => __( '彩虹聚合后台分配的 appkey（密钥，只存服务端）。', 'bang' ) ),
				array( 'key' => 'rainbow_plats', 'type' => 'checks', 'label' => __( '彩虹启用平台', 'bang' ), 'desc' => __( '勾选要显示的平台，登录卡片会为每个平台显示一颗按钮。', 'bang' ), 'choices' => function_exists( 'bang_ql_platform_choices' ) ? bang_ql_platform_choices() : array() ),
			),
		),
		'music' => array(
			'label'  => __( '音乐播放器', 'bang' ),
			'desc'   => __( '侧栏迷你播放器 + 全屏播放面板（含封面与歌词）。接口地址留空时使用内置演示数据源（QQ 音乐榜单或指定歌手的曲目，仅用于演示，勿用于生产）；打包开源版时会移除演示数据源，请在后台填入自己的接口。统一协议：GET {你的接口}?act=song&step=0|1，返回 {"code":0,"data":{"id":"","name":"","artist":"","cover":"https://...","audio":"https://....mp3","lyric":"[00:00.00]歌词\\n..."}}（step=0 取一首、step=1 下一首，lyric 为标准 LRC 文本）。', 'bang' ),
			'fields' => array(
				array( 'key' => 'music_on', 'type' => 'checkbox', 'label' => __( '开启音乐播放器', 'bang' ), 'desc' => __( '总开关。开启后侧栏底部显示迷你播放器，点击展开全屏播放面板（含封面与歌词）。', 'bang' ) ),
				array( 'key' => 'music_api', 'type' => 'text', 'label' => __( '自定义接口地址', 'bang' ), 'placeholder' => 'https://your-api.com/music', 'desc' => __( '留空 = 演示模式（内置 QQ 音乐榜单）。填入后走此接口，只接受 http(s)。演示源仅用于功能展示，生产环境请填自己的接口。', 'bang' ) ),
				array( 'key' => 'music_demo_singer', 'type' => 'text', 'label' => __( '演示歌手名（仅演示模式）', 'bang' ), 'desc' => __( '填了就播放这位歌手的曲目（如「陶喆」，取该歌手热门歌曲），留空则按下面的榜单 ID 取歌。仅内置演示数据源有效，自定义接口模式下无效。', 'bang' ) ),
				array( 'key' => 'music_demo_playlist', 'type' => 'text', 'label' => __( '演示榜单 ID（仅演示模式）', 'bang' ), 'desc' => __( '未填歌手名时，取该 QQ 音乐榜单的曲目：62 飙升榜、26 热歌榜、27 新歌榜等，默认 62。自定义接口模式下此项无效。', 'bang' ) ),
			),
		),
		'advanced' => array(
			'label'  => __( '高级', 'bang' ),
			'desc'   => __( '前台资源优化与自定义代码。自定义代码仅管理员可编辑，会原样输出。', 'bang' ),
			'fields' => array(
				array( 'key' => 'clean_wp_head', 'type' => 'checkbox', 'label' => __( '清理 WordPress 冗余头部', 'bang' ), 'desc' => __( '移除 emoji 脚本、RSD、wlwmanifest、generator、oEmbed、短链接等。', 'bang' ) ),
				array( 'key' => 'lazy_blocks_css', 'type' => 'checkbox', 'label' => __( '区块样式非阻塞加载', 'bang' ), 'desc' => __( 'blocks.css 改为 media=print + onload 切换，消除首屏渲染阻塞。', 'bang' ) ),
				array( 'key' => 'minify_assets', 'type' => 'checkbox', 'label' => __( '使用压缩版 CSS / JS', 'bang' ), 'desc' => __( '存在 style.min.css / app.min.js 且比源文件新时自动启用，否则回退源码。', 'bang' ) ),
				array( 'key' => 'asset_guard', 'type' => 'checkbox', 'label' => __( '资源加载自检', 'bang' ), 'desc' => __( '访客首次打开时静默检测样式表与交互脚本，加载失败才弹一张提示卡，引导刷新重试（不提供下载）。无视/关闭后 7 天内不再提示。', 'bang' ) ),
				array( 'key' => 'ai_excerpt_on', 'type' => 'checkbox', 'label' => __( 'AI 自动生成文章摘要', 'bang' ), 'desc' => __( '开启后，文章首次发布时调用下方接口生成一句摘要，自动用于 SEO 描述、分享海报与 AI 语义搜索索引。前台零外部请求；未配置或失败时自动回退为正文截取。', 'bang' ) ),
				array( 'key' => 'ai_api_base', 'type' => 'text', 'label' => __( 'AI 接口地址', 'bang' ), 'placeholder' => 'https://api.openai.com/v1', 'desc' => __( 'OpenAI 兼容的 API 根地址（末尾不要带 /chat/completions）。支持 DeepSeek、通义、智谱、Moonshot、OpenAI 等。', 'bang' ) ),
				array( 'key' => 'ai_api_key', 'type' => 'text', 'label' => __( 'API Key', 'bang' ), 'placeholder' => 'sk-...', 'desc' => __( '只在服务端调用，绝不下发到浏览器或写入页面。请妥善保管。', 'bang' ) ),
				array( 'key' => 'ai_model', 'type' => 'text', 'label' => __( '模型名称', 'bang' ), 'placeholder' => 'gpt-4o-mini', 'desc' => __( '接口对应的模型 ID，如 gpt-4o-mini、deepseek-chat、qwen-plus、glm-4-flash。留空回退 gpt-4o-mini。', 'bang' ) ),
				array( 'key' => 'publisher_on', 'type' => 'checkbox', 'label' => __( '开启发布器接口', 'bang' ), 'desc' => __( '开放的发帖接口供外部 AI Agent 调用：POST /wp-json/bang/v1/publish，需 X-Bang-Token 认证。Token 在下方生成。不用时请关闭。', 'bang' ) ),
				array( 'key' => 'publisher_rate', 'type' => 'number', 'label' => __( '发布频率上限（篇/分钟）', 'bang' ), 'min' => 1, 'max' => 60, 'desc' => __( '同一 Token 每分钟最多发多少篇，防止 Token 泄露后被批量刷稿。', 'bang' ) ),
				array( 'key' => 'head_code', 'type' => 'code', 'label' => __( '自定义 <head> 代码', 'bang' ), 'rows' => 5, 'desc' => __( '统计脚本、字体预连接等，输出在 wp_head 末尾。', 'bang' ) ),
				array( 'key' => 'footer_code', 'type' => 'code', 'label' => __( '自定义页脚代码', 'bang' ), 'rows' => 5, 'desc' => __( '输出在 wp_footer 末尾，适合异步统计脚本。', 'bang' ) ),
			),
		),
	);
}

/**
 * 后台菜单重组：把上面 15 个原始 tab 按功能域合并成 7 个，
 * 字段 key 完全不动（option 数据零迁移），只在字段上加 group 用于组内小标题。
 * 合并映射表集中在这里，改分组只需动 $layout，不用碰字段定义。
 */
function bang_setting_fields() {
	/* 1) 把原始 tab 的所有字段摊平成按 key 索引的池子 */
	$pool = array();
	foreach ( bang_setting_raw_tabs() as $raw_tab ) {
		foreach ( $raw_tab['fields'] as $field ) {
			$pool[ $field['key'] ] = $field;
		}
	}

	/* 2) 功能域布局：每项 = 新菜单 => array( 分组小标题 => 字段 key 列表 ) */
	$layout = array(
		'site'     => array(
			'label'  => __( '站点设置', 'bang' ),
			'desc'   => __( '站点身份、Logo、联系方式、打赏、幻灯片与各类展示页。保存后立即在前台生效。', 'bang' ),
			'groups' => array(
				__( '站点身份', 'bang' )        => array( 'site_icon_url', 'logo_url', 'logo_dark_url', 'site_timezone', 'icp', 'police_icp', 'footer_text' ),
				__( '语言', 'bang' )            => array( 'lang_default' ),
				__( '页脚链接', 'bang' )        => array( 'footer_links' ),
				__( '手机端底部导航', 'bang' )  => array( 'tabbar_style' ),
				__( '作者卡', 'bang' )          => array( 'author_bio_mode', 'author_bio_custom', 'author_bio_api', 'author_bio_fallback' ),
				__( '联系方式与社交', 'bang' )  => array( 'contact_email', 'social_qq', 'social_wechat', 'social_douyin', 'social_x', 'social_weibo', 'social_github', 'social_bilibili' ),
				__( '打赏', 'bang' )            => array( 'reward_on', 'reward_qr', 'reward_link', 'reward_title', 'reward_desc', 'reward_btn_text' ),
				__( '幻灯片与公告栏', 'bang' )  => array( 'slides_mode', 'slides_count', 'slides_posts', 'ticker_on', 'ticker_tag', 'ticker_text' ),
				__( '足迹与图片集', 'bang' )    => array( 'footprint_on', 'footprint_js_key', 'footprint_web_key', 'footprint_types', 'gallery_on', 'gallery_per_page' ),
			),
		),
		'content'  => array(
			'label'  => __( '内容与互动', 'bang' ),
			'desc'   => __( '文章展示、评论区、公告订阅、热门榜单与音乐播放器。', 'bang' ),
			'groups' => array(
				__( '文章展示', 'bang' )        => array( 'show_comments', 'copyright_on', 'copyright_license', 'copyright_note', 'related_on', 'ranking_on' ),
				__( '文章侧栏小工具', 'bang' )  => array( 'sidebar_widgets', 'sidebar_widget_html' ),
				__( '全站公告弹窗', 'bang' )    => array( 'notice_on', 'notice_mode', 'notice_title', 'notice_text', 'notice_later', 'notice_ok' ),
				__( '订阅卡片', 'bang' )        => array( 'subscribe_on', 'subscribe_title', 'subscribe_desc' ),
				__( '音乐播放器', 'bang' )      => array( 'music_on', 'music_api', 'music_demo_singer', 'music_demo_playlist' ),
			),
		),
		'safety'   => array(
			'label'  => __( '用户与安全', 'bang' ),
			'desc'   => __( '注册限制、违禁词审核、人机校验、快捷登录与阅读解锁。', 'bang' ),
			'groups' => array(
				__( '注册与审核', 'bang' )      => array( 'allow_register', 'register_email_domains', 'moderation_on', 'moderation_threshold', 'moderation_action' ),
				__( '人机校验', 'bang' )        => array( 'captcha_mode', 'geetest_id', 'geetest_key', 'geetest_validate_url' ),
				__( '快捷登录', 'bang' )        => array( 'quicklogin_on', 'qq_on', 'qq_appid', 'qq_appkey', 'rainbow_on', 'rainbow_api', 'rainbow_appid', 'rainbow_appkey', 'rainbow_plats' ),
				__( '阅读解锁', 'bang' )              => array( 'unlock_password', 'unlock_password_text', 'unlock_hint' ),
			),
		),
		'seo'      => array(
			'label'  => __( 'SEO', 'bang' ),
			'desc'   => __( '标题、描述、canonical、Open Graph、结构化数据与站点验证。留空的字段会自动取文章内容。', 'bang' ),
			'groups' => array(
				__( '基础', 'bang' )            => array( 'seo_home_title', 'seo_home_desc', 'title_sep', 'seo_keywords' ),
				__( '输出开关', 'bang' )        => array( 'seo_canonical', 'seo_og', 'seo_twitter', 'seo_jsonld', 'og_default_image', 'twitter_site' ),
				__( 'noindex', 'bang' )         => array( 'noindex_search', 'noindex_archive', 'noindex_404' ),
				__( '站点验证', 'bang' )        => array( 'google_verify', 'bing_verify', 'baidu_verify' ),
			),
		),
		'ai'       => array(
			'label'  => __( 'AI', 'bang' ),
			'desc'   => __( 'AI 摘要生成与发布器接口。密钥只在服务端使用，绝不下发浏览器。', 'bang' ),
			'groups' => array(
				__( 'AI 摘要', 'bang' )         => array( 'ai_excerpt_on', 'ai_api_base', 'ai_api_key', 'ai_model' ),
				__( '发布器接口', 'bang' )      => array( 'publisher_on', 'publisher_rate' ),
			),
		),
		'mail'     => array(
			'label'  => __( '邮件', 'bang' ),
			'desc'   => __( '用 SMTP 发信：填好主机 / 端口 / 账号密码后打开开关，站内所有 wp_mail（友链申请通知等）都会改走这台服务器。', 'bang' ),
			'groups' => array(
				__( 'SMTP 配置', 'bang' )       => array( 'smtp_on', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_user', 'smtp_pass', 'smtp_from', 'smtp_from_name', 'smtp_test_to' ),
			),
		),
		'advanced' => array(
			'label'  => __( '高级', 'bang' ),
			'desc'   => __( '前台资源优化与自定义代码。自定义代码仅管理员可编辑，会原样输出。', 'bang' ),
			'groups' => array(
				__( '性能优化', 'bang' )        => array( 'clean_wp_head', 'lazy_blocks_css', 'minify_assets', 'asset_guard' ),
				__( '自定义代码', 'bang' )      => array( 'head_code', 'footer_code' ),
			),
		),
	);

	/* 3) 按布局组装：给每个字段挂上 group，用于渲染组内小标题 */
	$tabs = array();
	$used = array();
	foreach ( $layout as $slug => $def ) {
		$fields = array();
		foreach ( $def['groups'] as $group => $keys ) {
			foreach ( $keys as $key ) {
				if ( ! isset( $pool[ $key ] ) ) {
					continue;
				}
				$field          = $pool[ $key ];
				$field['group'] = $group;
				$fields[]       = $field;
				$used[ $key ]   = 1;
			}
		}
		$tabs[ $slug ] = array(
			'label'  => $def['label'],
			'desc'   => $def['desc'],
			'fields' => $fields,
		);
	}

	/* 4) 兜底：布局里漏掉的字段全部塞进高级，确保任何 option 都不会丢失 */
	$leftovers = array();
	foreach ( $pool as $key => $field ) {
		if ( ! isset( $used[ $key ] ) ) {
			$field['group'] = __( '其它', 'bang' );
			$leftovers[]    = $field;
		}
	}
	if ( $leftovers ) {
		$tabs['advanced']['fields'] = array_merge( $tabs['advanced']['fields'], $leftovers );
	}

	return $tabs;
}

/**
 * 站点时区候选：空 = 跟随 WordPress 常规设置；其余为常用 IANA 时区。
 * 选中值直接写入 timezone_string，由核心负责换算与显示。
 *
 * @return array
 */
function bang_timezone_choices() {
	$out = array(
		'' => __( '跟随 WordPress 设置 → 常规 → 时区', 'bang' ),
	);
	$zones = array(
		'Asia/Shanghai'       => __( '中国标准时间（北京 / UTC+8）', 'bang' ),
		'Asia/Hong_Kong'      => __( '香港（UTC+8）', 'bang' ),
		'Asia/Taipei'         => __( '台北（UTC+8）', 'bang' ),
		'Asia/Singapore'      => __( '新加坡（UTC+8）', 'bang' ),
		'Asia/Tokyo'          => __( '东京（UTC+9）', 'bang' ),
		'Asia/Seoul'          => __( '首尔（UTC+9）', 'bang' ),
		'Asia/Bangkok'        => __( '曼谷（UTC+7）', 'bang' ),
		'Asia/Jakarta'        => __( '雅加达（UTC+7）', 'bang' ),
		'Asia/Kolkata'        => __( '加尔各答（UTC+5:30）', 'bang' ),
		'Asia/Dubai'          => __( '迪拜（UTC+4）', 'bang' ),
		'Europe/Moscow'       => __( '莫斯科（UTC+3）', 'bang' ),
		'Europe/Istanbul'     => __( '伊斯坦布尔（UTC+3）', 'bang' ),
		'Europe/Berlin'       => __( '柏林（UTC+1 / 夏令时 UTC+2）', 'bang' ),
		'Europe/Paris'        => __( '巴黎（UTC+1 / 夏令时 UTC+2）', 'bang' ),
		'Europe/London'       => __( '伦敦（UTC+0 / 夏令时 UTC+1）', 'bang' ),
		'Atlantic/Reykjavik'  => __( '雷克雅未克（UTC+0）', 'bang' ),
		'Africa/Cairo'        => __( '开罗（UTC+2）', 'bang' ),
		'Africa/Johannesburg' => __( '约翰内斯堡（UTC+2）', 'bang' ),
		'America/Sao_Paulo'   => __( '圣保罗（UTC-3）', 'bang' ),
		'America/New_York'    => __( '纽约（UTC-5 / 夏令时 UTC-4）', 'bang' ),
		'America/Chicago'     => __( '芝加哥（UTC-6 / 夏令时 UTC-5）', 'bang' ),
		'America/Denver'      => __( '丹佛（UTC-7 / 夏令时 UTC-6）', 'bang' ),
		'America/Los_Angeles' => __( '洛杉矶（UTC-8 / 夏令时 UTC-7）', 'bang' ),
		'America/Vancouver'   => __( '温哥华（UTC-8 / 夏令时 UTC-7）', 'bang' ),
		'Pacific/Auckland'    => __( '奥克兰（UTC+12 / 夏令时 UTC+13）', 'bang' ),
		'Australia/Sydney'    => __( '悉尼（UTC+10 / 夏令时 UTC+11）', 'bang' ),
		'UTC'                 => __( 'UTC（协调世界时）', 'bang' ),
	);
	foreach ( $zones as $id => $label ) {
		$out[ $id ] = $label;
	}
	return $out;
}

/**
 * 让「开放注册」与 WordPress 的注册开关保持一致。
 * 只在主题设置保存时单向同步，不再每次 admin_init 覆盖核心设置页的修改。
 */
function bang_sync_register_option() {
	$options = bang_options();
	$want    = empty( $options['allow_register'] ) ? 0 : 1;
	if ( (int) get_option( 'users_can_register' ) !== $want ) {
		update_option( 'users_can_register', $want );
	}
}
add_action( 'update_option_' . BANG_OPTION_KEY, 'bang_sync_register_option', 10, 2 );
add_action( 'add_option_' . BANG_OPTION_KEY, 'bang_sync_register_option', 10, 2 );

/**
 * 清洗并合并设置。checkbox 未勾选时不会出现在 $_POST，需要显式置 0。
 */
function bang_sanitize_options( $input ) {
	$out      = bang_options();
	$can_code = current_user_can( 'unfiltered_html' );
	if ( ! is_array( $input ) ) {
		return $out;
	}
	foreach ( bang_setting_fields() as $tab ) {
		foreach ( $tab['fields'] as $field ) {
			$key  = $field['key'];
			$type = isset( $field['type'] ) ? $field['type'] : 'text';
			if ( 'checkbox' === $type ) {
				$out[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
				continue;
			}
			if ( 'checks' === $type ) {
				/* 多选勾选：按 choices 白名单过滤后拼回逗号串，兼容前台按逗号解析 */
				$raw     = isset( $input[ $key ] ) && is_array( $input[ $key ] ) ? $input[ $key ] : array();
				$allowed = isset( $field['choices'] ) ? array_keys( $field['choices'] ) : array();
				$keep    = array();
				foreach ( $raw as $one ) {
					$one = sanitize_key( wp_unslash( $one ) );
					if ( in_array( $one, $allowed, true ) ) {
						$keep[] = $one;
					}
				}
				$out[ $key ] = implode( ',', $keep );
				continue;
			}
			if ( 'widgets' === $type ) {
				/* 侧栏小工具：按白名单过滤，保留拖动后的顺序；checkbox 未勾选时整项不出现 */
				$defs = function_exists( 'bang_sidebar_widget_defs' ) ? array_keys( bang_sidebar_widget_defs() ) : array();
				$raw  = isset( $input[ $key ] ) ? $input[ $key ] : '';
				if ( is_array( $raw ) ) { $raw = implode( ',', $raw ); }
				$ids  = array_filter( array_map( 'sanitize_key', explode( ',', (string) $raw ) ) );
				$keep = array();
				foreach ( $ids as $one ) {
					if ( in_array( $one, $defs, true ) && ! in_array( $one, $keep, true ) ) {
						$keep[] = $one;
					}
				}
				$out[ $key ] = implode( ',', $keep );
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
				case 'links':
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

function bang_register_settings() {
	register_setting(
		'bang_settings_group',
		BANG_OPTION_KEY,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'bang_sanitize_options',
			'default'           => bang_default_options(),
		)
	);
}
add_action( 'admin_init', 'bang_register_settings' );

function bang_settings_menu() {
	/* 顶级菜单，紧挨「外观」；图标用内联 SVG（墨水笔尖），跟随 WP 菜单的着色 */
	$icon = 'data:image/svg+xml;base64,' . base64_encode(
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a7aaad" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.3601 4.07866L15.2869 3.15178C16.8226 1.61607 19.3125 1.61607 20.8482 3.15178C22.3839 4.68748 22.3839 7.17735 20.8482 8.71306L19.9213 9.63993M14.3601 4.07866C14.3601 4.07866 14.4759 6.04828 16.2138 7.78618C17.9517 9.52407 19.9213 9.63993 19.9213 9.63993M19.9213 9.63993L11.4001 18.1612C10.8229 18.7383 10.5344 19.0269 10.2162 19.2751C9.84082 19.5679 9.43469 19.8189 9.00498 20.0237C8.6407 20.1973 8.25352 20.3263 7.47918 20.5844L4.19792 21.6782L3.39584 21.9456C3.01478 22.0726 2.59466 21.9734 2.31063 21.6894C2.0266 21.4053 1.92743 20.9852 2.05445 20.6042L2.32181 19.8021L3.41556 16.5208C3.67368 15.7465 3.80273 15.3593 3.97634 14.995C4.18114 14.5653 4.43213 14.1592 4.7249 13.7838C4.97308 13.4656 5.26166 13.1771 5.83882 12.5999L14.3601 4.07866M4.19792 21.6782L2.32181 19.8021" stroke-linecap="round" stroke="currentColor"/></svg>'
	);
	add_menu_page(
		__( 'Bang 设置', 'bang' ),
		__( 'Bang 设置', 'bang' ),
		'manage_options',
		'bang-settings',
		'bang_render_settings_page',
		$icon,
		61
	);
	/* 邮件订阅：从「内容与互动」里独立出来，作为 Bang 设置下的子菜单 */
	add_submenu_page(
		'bang-settings',
		__( '邮件订阅', 'bang' ),
		__( '邮件订阅', 'bang' ),
		'manage_options',
		'bang-subscribers',
		'bang_render_subscribers_page'
	);
}
add_action( 'admin_menu', 'bang_settings_menu' );

/**
 * 独立后台菜单页：邮件订阅（订阅者列表 + 导出 CSV）。
 */
function bang_render_subscribers_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( '没有权限。', 'bang' ) );
	}
	echo '<div class="wrap bang-subs-page">';
	echo '<h1>' . esc_html__( '邮件订阅', 'bang' ) . '</h1>';
	echo '<style>'
		. '.bang-subs-page{max-width:900px}'
		. '.bang-subs-page .bang-subs{display:flex;flex-direction:column;gap:16px;margin-top:16px}'
		. '.bang-subs-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}'
		. '.bang-subs-stat{display:flex;flex-direction:column;gap:6px;padding:16px 18px;border:1px solid #dcdcde;border-radius:12px;background:#fff}'
		. '.bang-subs-stat strong{font-size:24px;font-weight:600;line-height:1;color:#1d2327}'
		. '.bang-subs-stat span{font-size:12px;color:#646970}'
		. '.bang-subs-card{padding:18px 20px;border:1px solid #dcdcde;border-radius:12px;background:#fff}'
		. '.bang-subs-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}'
		. '.bang-subs-card-head h3{margin:0;font-size:14px;font-weight:600;color:#1d2327}'
		. '.bang-subs-muted{margin:0 0 8px;color:#646970;font-size:13px}'
		. '.bang-subs-latest-meta{margin:0;font-size:13px;color:#1d2327;line-height:1.7}'
		. '.bang-subs-err{margin:8px 0 0;font-size:12.5px;color:#b32d2e;line-height:1.6}'
		. '.bang-subs-notice{margin:0;padding:10px 14px;border-radius:8px;font-size:13px}'
		. '.bang-subs-notice.is-ok{background:#f0f6fc;color:#1d2327}'
		. '.bang-subs-notice.is-err{background:#fcf0f1;color:#b32d2e}'
		. '.bang-subs-page .button{box-shadow:none!important;border-radius:8px!important}'
		. '.bang-subs-page .bang-subs-table.widefat{margin:0;border:1px solid #dcdcde;border-radius:8px;overflow:hidden}'
		. '.bang-subs-page .bang-subs-table.widefat th,.bang-subs-page .bang-subs-table.widefat td{padding:10px 14px;border:0;border-bottom:1px solid #f0f0f1;font-size:13px;text-align:left;vertical-align:top}'
		. '.bang-subs-page .bang-subs-table.widefat th{font-weight:600;color:#1d2327;background:#f6f7f7}'
		. '.bang-subs-page .bang-subs-table.widefat tr:last-child td{border-bottom:0}'
		. '.bang-subs-page .bang-subs-table.widefat td:last-child{color:#646970;word-break:break-all}'
		. '@media(max-width:782px){.bang-subs-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}'
		. '</style>';
	if ( function_exists( 'bang_subscribers_panel' ) ) {
		bang_subscribers_panel( false );
	}
	echo '</div>';
}

/**
 * 图片字段的「从媒体库选择」要用 wp.media，只在本设置页入队媒体库脚本，
 * 不污染其它后台页面。wp_enqueue_media() 自带所需脚本与样式。
 */
function bang_settings_media_assets( $hook ) {
	if ( false === strpos( (string) $hook, 'bang-settings' ) ) {
		return;
	}
	wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'bang_settings_media_assets' );

/**
 * 单个字段。div 布局：标签在上、说明在下，控件占满一列。
 */
function bang_render_field( $field, $value ) {
	$key      = $field['key'];
	$type     = isset( $field['type'] ) ? $field['type'] : 'text';
	$id       = 'bang-field-' . $key;
	$name     = BANG_OPTION_KEY . '[' . $key . ']';
	$disabled = ! empty( $field['disabled'] );
	$dis      = $disabled ? ' disabled' : '';
	echo '<div class="bang-f' . ( 'checkbox' === $type ? ' bang-f-row' : '' ) . ( $disabled ? ' bang-f-disabled' : '' ) . '">';
	if ( 'checkbox' === $type ) {
		echo '<span class="bang-f-t"><label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';
		if ( ! empty( $field['desc'] ) ) {
			echo '<span class="bang-f-d">' . esc_html( $field['desc'] ) . '</span>';
		}
		echo '</span><span class="bang-toggle"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( 1, (int) $value, false ) . $dis . '><span class="bang-toggle-track" aria-hidden="true"></span></span>';
		echo '</div>';
		return;
	}
	echo '<label class="bang-f-t" for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';
	switch ( $type ) {
		case 'links':
			/* 结构化页脚链接编辑：可视化增删分组/链接，隐藏 textarea 承载序列化文本（复用前台解析） */
			echo '<div class="bang-f-links" data-bang-links="' . esc_attr( $id ) . '">';
			echo '<div class="bang-f-links-groups" data-bang-links-groups></div>';
			echo '<button type="button" class="button bang-f-links-add-group" data-bang-links-add-group>＋ ' . esc_html__( '添加分组', 'bang' ) . '</button>';
			echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="bang-f-links-data" hidden>' . esc_textarea( (string) $value ) . '</textarea>';
			echo '</div>';
			break;
		case 'textarea':
		case 'code':
			$rows = isset( $field['rows'] ) ? (int) $field['rows'] : 3;
			$cls  = 'code' === $type ? ' bang-f-code' : '';
			$ph   = isset( $field['placeholder'] ) ? ' placeholder="' . esc_attr( $field['placeholder'] ) . '"' : '';
			echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="' . esc_attr( $rows ) . '" class="bang-f-input' . esc_attr( $cls ) . '"' . $ph . $dis . '>' . esc_textarea( (string) $value ) . '</textarea>';
			break;
		case 'number':
			echo '<input type="number" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" min="' . esc_attr( isset( $field['min'] ) ? $field['min'] : 1 ) . '" max="' . esc_attr( isset( $field['max'] ) ? $field['max'] : 60 ) . '" class="bang-f-num"' . $dis . '>';
			break;
		case 'url':
			if ( ! empty( $field['media'] ) ) {
				$bang_url = trim( (string) $value );
				/* 图片字段：直接输入链接的 input 原样保留，旁边加「从媒体库选择」与预览 */
				echo '<div class="bang-f-media" data-bang-media-field>';
				echo '<div class="bang-f-media-row">';
				echo '<input type="url" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="bang-f-input bang-f-media-url" placeholder="https://" data-bang-media-input' . $dis . '>';
				echo '<button type="button" class="button bang-f-media-pick" data-bang-media-pick="' . esc_attr( $id ) . '"' . $dis . '>' . esc_html__( '从媒体库选择', 'bang' ) . '</button>';
				echo '<button type="button" class="button bang-f-media-clear" data-bang-media-clear="' . esc_attr( $id ) . '"' . $dis . '>' . esc_html__( '清除', 'bang' ) . '</button>';
				echo '</div>';
				echo '<span class="bang-f-media-preview" data-bang-media-preview' . ( '' === $bang_url ? ' hidden' : '' ) . '>';
				if ( '' !== $bang_url ) {
					echo '<img src="' . esc_url( $bang_url ) . '" alt="">';
				}
				echo '</span>';
				echo '</div>';
				break;
			}
			echo '<input type="url" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="bang-f-input" placeholder="https://"' . $dis . '>';
			break;
		case 'select':
			$choices = isset( $field['choices'] ) ? $field['choices'] : array();
			echo '<span class="bang-f-select"><select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . $dis . '>';
			foreach ( $choices as $choice => $label ) {
				echo '<option value="' . esc_attr( $choice ) . '"' . selected( (string) $value, (string) $choice, false ) . '>'
					. esc_html( $label ) . '</option>';
			}
			echo '</select><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M19 9L12 15L5 9" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/></svg></span>';
			break;
		case 'checks':
			/* 多选勾选：一组 checkbox（name 带 []），底层存逗号串，兼容既有解析 */
			$choices = isset( $field['choices'] ) ? $field['choices'] : array();
			$picked  = array_filter( array_map( 'trim', explode( ',', (string) $value ) ) );
			echo '<div class="bang-f-checks">';
			foreach ( $choices as $cval => $clabel ) {
				echo '<label class="bang-f-check"><input type="checkbox" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( $cval ) . '"' . checked( in_array( (string) $cval, $picked, true ), true, false ) . $dis . '><span>' . esc_html( $clabel ) . '</span></label>';
			}
			echo '</div>';
			break;
		case 'widgets':
			/* 侧栏小工具：拖拽排序 + 逐项启用；隐藏 input 承载「已启用且按顺序」的 id 串 */
			$wdefs = function_exists( 'bang_sidebar_widget_defs' ) ? bang_sidebar_widget_defs() : array();
			$won   = array_filter( array_map( 'trim', explode( ',', (string) $value ) ) );
			$order = array();
			foreach ( $won as $wid ) {
				if ( isset( $wdefs[ $wid ] ) ) { $order[ $wid ] = 1; }
			}
			foreach ( $wdefs as $wid => $wlabel ) {
				if ( ! isset( $order[ $wid ] ) ) { $order[ $wid ] = 0; }
			}
			$grip = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M9 5h.01M9 12h.01M9 19h.01M15 5h.01M15 12h.01M15 19h.01"/></svg>';
			echo '<div class="bang-f-widgets" data-bang-widgets>';
			echo '<div class="bang-f-widgets-list" data-bang-widgets-list>';
			foreach ( $order as $wid => $enabled ) {
				echo '<div class="bang-f-widget" data-bang-widget="' . esc_attr( $wid ) . '">'
					. '<span class="bang-f-widget-grip" draggable="true" aria-label="' . esc_attr__( '拖动排序', 'bang' ) . '">' . $grip . '</span>'
					. '<span class="bang-f-widget-name">' . esc_html( $wdefs[ $wid ] ) . '</span>'
					. '<label class="bang-f-widget-on"><input type="checkbox" data-bang-widget-on' . ( $enabled ? ' checked' : '' ) . $dis . '><span>' . esc_html__( '启用', 'bang' ) . '</span></label>'
					. '</div>';
			}
			echo '</div>';
			echo '<input type="hidden" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="bang-f-widgets-data" value="' . esc_attr( (string) $value ) . '">';
			echo '</div>';
			break;
		default:
			echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="bang-f-input"' . $dis . '>';
			break;
	}
	if ( ! empty( $field['desc'] ) ) {
		echo '<span class="bang-f-d">' . esc_html( $field['desc'] ) . '</span>';
	}
	echo '</div>';
}

/**
 * 服务器层面的性能建议（主题无法代改 nginx / 证书）。
 */
function bang_server_hints() {
	$nginx = "location ~* \\.(css|js|png|jpg|jpeg|gif|webp|avif|svg|woff2?)$ {\n    expires 1y;\n    add_header Cache-Control \"public, immutable\";\n}\ngzip on;\ngzip_types text/html text/css application/javascript application/json image/svg+xml;";
	return '<h3>' . esc_html__( '服务器优化建议', 'bang' ) . '</h3>'
		. '<p class="bang-f-d">' . esc_html__( 'Lighthouse 的「缓存生命周期」与 HTTPS 属于服务器配置，主题无法代改。上线前建议在 nginx 站点配置中加入：', 'bang' ) . '</p>'
		. '<pre class="bang-code">' . esc_html( $nginx ) . '</pre>'
		. '<p class="bang-f-d">' . esc_html__( '另外：为站点签发证书并全站 HTTPS，可消除 Lighthouse 的 is-on-https 扣分；本站当前为内网 HTTP，本地审计该项必然为 0。', 'bang' ) . '</p>';
}

/**
 * 后台：友情链接入口（只读，不参与保存）。
 * 友链数据已改走 WordPress 核心链接管理器，这里只给出入口与状态。
 */
function bang_links_admin_panel() {
	$admin = admin_url( 'link-manager.php' );
	$count = function_exists( 'bang_friend_links' ) ? count( bang_friend_links() ) : 0;
	return '<div class="bang-links-admin">'
		. '<h3>' . esc_html__( '友情链接', 'bang' ) . '</h3>'
		. '<p class="bang-f-d">' . esc_html( sprintf( '当前共 %d 条友链。数据保存在 WordPress 核心链接管理器里，前台 /links/ 与申请审核都读它。', $count ) ) . '</p>'
		. '<p class="bang-f-d">' . esc_html__( '增删改友链、标记「不可见」（前台显示为暂不可达）都在下面这个页面完成。', 'bang' ) . '</p>'
		. '<p class="bang-f-d"><a class="button is-primary" href="' . esc_url( $admin ) . '">' . esc_html__( '打开链接管理器', 'bang' ) . '</a></p>'
		. '</div>';
}

/**
 * 设置页。所有 tab 的字段一次性放进同一个表单，切换只是显示隐藏，
 * 这样保存时不会丢掉其它 tab 的值；无 JS 时按 tab 参数跳转，依旧可用。
 */
/**
 * 设置备份：把当前全部设置导出为 JSON 文件下载。
 * 只给管理员，链接带 nonce；密钥类字段也在备份里，面板会提示妥善保管。
 */
function bang_backup_export() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( '权限不足', 'bang' ) );
	}
	check_admin_referer( 'bang_backup_export' );
	$payload = array(
		'app'         => 'bang',
		'version'     => BANG_VERSION,
		'exported_at' => current_time( 'mysql' ),
		'options'     => bang_options(),
	);
	$filename = 'bang-settings-' . gmdate( 'Ymd-His' ) . '.json';
	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
	echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	exit;
}
add_action( 'wp_ajax_bang_backup_export', 'bang_backup_export' );

/**
 * 设置备份：导入 JSON 备份并覆盖当前设置。
 * 复用 bang_sanitize_options() 做白名单清洗，未识别字段直接丢弃；
 * 顺带同步开放注册与站点时区（清洗函数里已处理）。
 */
function bang_backup_import() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'msg' => __( '权限不足', 'bang' ) ) );
	}
	check_ajax_referer( 'bang_backup_import' );
	if ( empty( $_FILES['file'] ) || ! isset( $_FILES['file']['error'] ) || UPLOAD_ERR_OK !== (int) $_FILES['file']['error'] ) {
		wp_send_json_error( array( 'msg' => __( '请选择备份文件', 'bang' ) ) );
	}
	$size = isset( $_FILES['file']['size'] ) ? (int) $_FILES['file']['size'] : 0;
	if ( $size <= 0 || $size > 2 * 1024 * 1024 ) {
		wp_send_json_error( array( 'msg' => __( '备份文件需在 2MB 以内', 'bang' ) ) );
	}
	$tmp = isset( $_FILES['file']['tmp_name'] ) ? $_FILES['file']['tmp_name'] : '';
	if ( ! is_uploaded_file( $tmp ) ) {
		wp_send_json_error( array( 'msg' => __( '上传文件无效', 'bang' ) ) );
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$raw  = file_get_contents( $tmp );
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) || empty( $data['options'] ) || ! is_array( $data['options'] ) ) {
		wp_send_json_error( array( 'msg' => __( '备份文件格式不正确', 'bang' ) ) );
	}
	update_option( BANG_OPTION_KEY, bang_sanitize_options( $data['options'] ) );
	wp_send_json_success( array( 'msg' => __( '导入成功', 'bang' ) ) );
}
add_action( 'wp_ajax_bang_backup_import', 'bang_backup_import' );

function bang_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tabs    = bang_setting_fields();
	$active  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'site';
	$active  = ( isset( $tabs[ $active ] ) || 'backup' === $active ) ? $active : 'site';
	$options = bang_options();
	$base    = admin_url( 'admin.php?page=bang-settings' );
	$icons   = array(
		'content'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3h6l4 4v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor"/><path d="M14 3v4h4" stroke="currentColor"/><path d="M9 12h6M9 15.5h6M9 19h4" stroke="currentColor"/></svg>',
		'ai'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2.75l1.55 4.35a3 3 0 0 0 1.8 1.8L19.7 10.5a3 3 0 0 1 0 5.7l-4.35 1.6a3 3 0 0 0-1.8 1.8L12 24l-1.55-4.4a3 3 0 0 0-1.8-1.8L4.3 16.2a3 3 0 0 1 0-5.7l4.35-1.6a3 3 0 0 0 1.8-1.8L12 2.75Z" stroke="currentColor"/></svg>',
		'general'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 14.5C14 12.8431 15.3431 11.5 17 11.5C18.6568 11.5 20 12.8431 20 14.5C20 16.1569 18.6568 17.5 17 17.5C15.3431 17.5 14 16.1569 14 14.5Z" stroke-linecap="round" stroke="currentColor"/> <path d="M3.99998 9.5C3.99998 11.1569 5.34312 12.5 6.99998 12.5C8.65683 12.5 9.99998 11.1569 9.99998 9.5C9.99998 7.84315 8.65683 6.5 6.99998 6.5C5.34312 6.5 3.99998 7.84315 3.99998 9.5Z" stroke-linecap="round" stroke="currentColor"/> <path d="M16.959 9L16.959 2" stroke-linecap="round" stroke="currentColor"/> <path d="M6.95898 15L6.95898 22" stroke-linecap="round" stroke="currentColor"/> <path d="M16.959 22L16.959 20" stroke-linecap="round" stroke="currentColor"/> <path d="M6.95898 2L6.95898 4" stroke-linecap="round" stroke="currentColor"/></svg>',
		'site'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="10" r="7" stroke-linecap="round" stroke="currentColor"/> <path d="M4 16.5623C5.88838 18.6722 8.63263 20 11.687 20C17.3827 20 22 15.3827 22 9.68699C22 6.63263 20.6722 3.88838 18.5623 2" stroke-linecap="round" stroke="currentColor"/> <path d="M7.27441 4.8355C7.89871 5.52627 8.80985 6.84054 8.93082 8.23681C9.06209 9.75203 10.0265 10.9833 11.4997 10.9999C12.0659 11.0063 12.6385 10.5822 12.637 9.99503C12.6366 9.81347 12.6077 9.62783 12.5624 9.45704C12.4995 9.21949 12.494 8.9462 12.6247 8.66663C13.0821 7.68861 13.9818 7.4259 14.6946 6.89475C15.0108 6.65919 15.2992 6.41068 15.4264 6.21051C15.7298 5.73283 16.0333 4.84227 15.9967 4.25259" stroke-linecap="round" stroke="currentColor"/> <path d="M18.8194 11.5868C18.5711 12.3119 18.0284 13.2562 16.1455 13.2759C16.1455 13.2759 13.9497 13.2759 13.291 14.5172C12.8053 15.4325 13.0283 16.418 13.2382 16.8908" stroke-linecap="round" stroke="currentColor"/> <path d="M12 22V20" stroke-linecap="round" stroke="currentColor"/> <path d="M12 22H10" stroke-linecap="round" stroke="currentColor"/> <path d="M14 22H12" stroke-linecap="round" stroke="currentColor"/></svg>',
		'slide'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22Z" stroke-linecap="round" stroke="currentColor"/> <circle cx="16" cy="8" r="2" stroke-linecap="round" stroke="currentColor"/> <path d="M2 12.5001L3.75159 10.9675C4.66286 10.1702 6.03628 10.2159 6.89249 11.0721L11.1822 15.3618C11.8694 16.0491 12.9512 16.1428 13.7464 15.5839L14.0446 15.3744C15.1888 14.5702 16.7369 14.6634 17.7765 15.599L21 18.5001" stroke-linecap="round" stroke="currentColor"/></svg>',
		'contact'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 20H14C17.7712 20 19.6569 20 20.8284 18.8284C22 17.6569 22 15.7712 22 12C22 8.22876 22 6.34315 20.8284 5.17157C19.6569 4 17.7712 4 14 4H10C6.22876 4 4.34315 4 3.17157 5.17157C2 6.34315 2 8.22876 2 12C2 15.7712 2 17.6569 3.17157 18.8284C4.34315 20 6.22876 20 10 20Z" stroke-linecap="round" stroke="currentColor"/> <path d="M6 8L8.1589 9.79908C9.99553 11.3296 10.9139 12.0949 12 12.0949C13.0861 12.0949 14.0045 11.3296 15.8411 9.79908L18 8" stroke-linecap="round" stroke="currentColor"/></svg>',
		'reward'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8.96173 18.9108C6.01943 16.5913 2 14 2 9.13704C2 4.2741 7.50016 0.825403 12 5.50057C16.4998 0.825403 22 4.2741 22 9.13704C22 14 17.9806 16.5913 15.0383 18.9108C14 19.7293 13 20.4999 12 20.4999C11 20.4999 10 19.7293 8.96173 18.9108Z" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/></svg>',
		'ranking'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22Z" stroke-linecap="round" stroke="currentColor"/> <path d="M7 14L9.29289 11.7071C9.68342 11.3166 10.3166 11.3166 10.7071 11.7071L12.2929 13.2929C12.6834 13.6834 13.3166 13.6834 13.7071 13.2929L17 10M14.5 10H17V12.5" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/></svg>',
		'footprint' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13.4629 21.6744C12.5343 22.1085 11.4657 22.1085 10.5371 21.6744C6.55332 19.8124 4 14.6055 4 10.1433C4 5.64588 7.58172 2 12 2C16.4183 2 20 5.64588 20 10.1433C20 14.6055 17.4467 19.8124 13.4629 21.6744Z" stroke-linecap="round" stroke="currentColor"/> <circle cx="12" cy="10" r="3" stroke-linecap="round" stroke="currentColor"/></svg>',
		'gallery'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22Z" stroke-linecap="round" stroke="currentColor"/> <circle cx="16" cy="8" r="2" stroke-linecap="round" stroke="currentColor"/> <path d="M2 12.5001L3.75159 10.9675C4.66286 10.1702 6.03628 10.2159 6.89249 11.0721L11.1822 15.3618C11.8694 16.0491 12.9512 16.1428 13.7464 15.5839L14.0446 15.3744C15.1888 14.5702 16.7369 14.6634 17.7765 15.599L21 18.5001" stroke-linecap="round" stroke="currentColor"/></svg>',
		'safety'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.9914C21 11.4234 21 10.8996 21 10.4167C21 7.21907 21 5.62028 20.6225 5.08241C20.245 4.54454 18.7417 4.02996 15.7351 3.00079L15.1623 2.80472C13.595 2.26824 12.8114 2 12 2C11.1886 2 10.405 2.26824 8.83772 2.80472L8.26491 3.00079C5.25832 4.02996 3.75503 4.54454 3.37752 5.08241C3 5.62028 3 7.21907 3 10.4167C3 10.8996 3 11.4234 3 11.9914C3 17.6294 7.23896 20.3655 9.89856 21.5273C10.62 21.8424 10.9807 22 12 22C13.0193 22 13.38 21.8424 14.1014 21.5273C16.761 20.3655 21 17.6294 21 11.9914Z" stroke-linecap="round" stroke="currentColor"/> <path d="M9.5 12.4L10.9286 14L14.5 10" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/></svg>',
		'mail'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 20H14C17.7712 20 19.6569 20 20.8284 18.8284C22 17.6569 22 15.7712 22 12C22 8.22876 22 6.34315 20.8284 5.17157C19.6569 4 17.7712 4 14 4H10C6.22876 4 4.34315 4 3.17157 5.17157C2 6.34315 2 8.22876 2 12C2 15.7712 2 17.6569 3.17157 18.8284C4.34315 20 6.22876 20 10 20Z" stroke-linecap="round" stroke="currentColor"/> <path d="M6 8L8.1589 9.79908C9.99553 11.3296 10.9139 12.0949 12 12.0949C13.0861 12.0949 14.0045 11.3296 15.8411 9.79908L18 8" stroke-linecap="round" stroke="currentColor"/></svg>',
		'seo'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11.5" cy="11.5" r="9.5" stroke-linecap="round" stroke="currentColor"/> <path d="M18.2168 18.2178L21.9994 22.0004" stroke-linecap="round" stroke="currentColor"/></svg>',
		'unlock'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 22H16C18.8284 22 20.2426 22 21.1213 21.1213C22 20.2426 22 18.8284 22 16C22 13.1716 22 11.7574 21.1213 10.8787C20.2426 10 18.8284 10 16 10H8C5.17157 10 3.75736 10 2.87868 10.8787C2 11.7574 2 13.1716 2 16C2 18.8284 2 20.2426 2.87868 21.1213C3.75736 22 5.17157 22 8 22Z" stroke-linecap="round" stroke="currentColor"/> <path d="M6 10V8C6 4.68629 8.68629 2 12 2C15.3137 2 18 4.68629 18 8V10" stroke-linecap="round" stroke="currentColor"/></svg>',
		'quicklogin' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12C4 16.4183 7.58172 20 12 20C16.4183 20 20 16.4183 20 12" stroke-linecap="round" stroke="currentColor"/> <path d="M12 14L12 4M9 7L12 4L15 7" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/></svg>',
		'music'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 19C9 20.6569 7.65685 22 6 22C4.34315 22 3 20.6569 3 19C3 17.3431 4.34315 16 6 16C7.65685 16 9 17.3431 9 19Z" stroke-linecap="round" stroke="currentColor"/> <path d="M21 17C21 18.6569 19.6569 20 18 20C16.3431 20 15 18.6569 15 17C15 15.3431 16.3431 14 18 14C19.6569 14 21 15.3431 21 17Z" stroke-linecap="round" stroke="currentColor"/> <path d="M9 19V8" stroke-linecap="round" stroke="currentColor"/> <path d="M21 17V6" stroke-linecap="round" stroke="currentColor"/> <path d="M11.7351 5.08836L15.7351 3.75503C18.1376 2.95419 19.3388 2.55378 20.1694 3.15242C21 3.75107 21 5.0173 21 7.54976V8.00006L9 12.0001V8.88309C9 7.49165 9 6.79593 9.37752 6.27215C9.75503 5.74838 10.4151 5.52837 11.7351 5.08836Z" stroke-linecap="round" stroke="currentColor"/></svg>',
		'advanced' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 7.82959L18.6965 9.35641C20.239 10.7447 21.0103 11.4389 21.0103 12.3296C21.0103 13.2203 20.239 13.9145 18.6965 15.3028L17 16.8296" stroke-linecap="round" stroke="currentColor"/> <path d="M13.9863 5L11.9995 12.4149L10.0127 19.8297" stroke-linecap="round" stroke="currentColor"/> <path d="M6.99956 7.82959L5.30309 9.35641C3.76054 10.7447 2.98926 11.4389 2.98926 12.3296C2.98926 13.2203 3.76054 13.9145 5.30309 15.3028L6.99956 16.8296" stroke-linecap="round" stroke="currentColor"/></svg>',
		'backup'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12" stroke="currentColor"/><path d="M8 11l4 4 4-4" stroke="currentColor"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="currentColor"/></svg>',
	);

	/* 二级子菜单：每个功能域的小标题即子项，点击后直滚到对应设置组 */
	$tab_groups = array();
	foreach ( $tabs as $slug => $tab ) {
		$groups = array();
		foreach ( $tab['fields'] as $field ) {
			if ( ! empty( $field['group'] ) && ! in_array( $field['group'], $groups, true ) ) {
				$groups[] = $field['group'];
			}
		}
		$tab_groups[ $slug ] = $groups;
	}

	echo '<div class="bang-set">';
	echo '<header class="bang-set-head">'
		. '<div class="bang-set-brand">'
		. '<span class="bang-set-logo" aria-hidden="true"><img src="' . esc_url( get_template_directory_uri() . '/assets/bang-logo.png' ) . '" alt="" width="56" height="56"></span>'
		. '<span class="bang-set-ttl">'
		. '<h1>' . esc_html__( 'Bang', 'bang' ) . '<span class="bang-set-edition">' . esc_html__( 'INK', 'bang' ) . '</span></h1>'
		. '<p>' . esc_html__( '极简博客主题 · 落笔有声，留白有度。卡片时间线 + 阅读层，移动端优先。', 'bang' ) . '</p>'
		. '<span class="bang-set-meta"><span class="bang-set-ver">v' . esc_html( BANG_VERSION ) . '</span><span class="bang-set-oss">GPL-2.0 · <a href="https://github.com/naigoucn/bang" target="_blank" rel="noopener noreferrer">github.com/naigoucn/bang</a></span></span>'
		. '</span></div>'
		. '<a class="bang-set-src" href="https://github.com/naigoucn/bang" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( '在 GitHub 查看开源仓库', 'bang' ) . '">'
		. '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 .5C5.73.5.5 5.73.5 12c0 5.08 3.29 9.39 7.86 10.91.57.11.78-.25.78-.55 0-.27-.01-1.05-.02-2.06-3.19.69-3.87-1.54-3.87-1.54-.52-1.33-1.28-1.68-1.28-1.68-1.04-.71.08-.7.08-.7 1.15.08 1.76 1.18 1.76 1.18 1.03 1.77 2.7 1.26 3.36.96.1-.75.4-1.26.73-1.55-2.55-.29-5.23-1.28-5.23-5.69 0-1.26.45-2.29 1.18-3.1-.12-.29-.51-1.46.11-3.04 0 0 .97-.31 3.17 1.18a11 11 0 0 1 5.77 0c2.2-1.49 3.17-1.18 3.17-1.18.62 1.58.23 2.75.11 3.04.74.81 1.18 1.84 1.18 3.1 0 4.42-2.69 5.39-5.25 5.68.41.36.78 1.06.78 2.14 0 1.55-.01 2.8-.01 3.18 0 .3.21.67.79.55A11.51 11.51 0 0 0 23.5 12C23.5 5.73 18.27.5 12 .5Z"/></svg>'
		. '<span>' . esc_html__( '开源仓库', 'bang' ) . '</span></a>'
		. '</header>';
	echo '<div class="bang-set-body">';
	echo '<nav class="bang-set-nav" aria-label="' . esc_attr__( '设置分组', 'bang' ) . '">';
	foreach ( $tabs as $slug => $tab ) {
		$url     = add_query_arg( 'tab', $slug, $base );
		$is_open = ( $slug === $active );
		echo '<div class="bang-set-nav-item' . ( $is_open ? ' is-open' : '' ) . '" data-bang-nav-tab="' . esc_attr( $slug ) . '">';
		echo '<a href="' . esc_url( $url ) . '" class="bang-set-item' . ( $is_open ? ' is-active' : '' ) . '" data-bang-tab-link="' . esc_attr( $slug ) . '">'
			. ( isset( $icons[ $slug ] ) ? $icons[ $slug ] : '' ) // phpcs:ignore WordPress.Security.EscapeOutput
			. '<span>' . esc_html( $tab['label'] ) . '</span></a>';
		if ( ! empty( $tab_groups[ $slug ] ) ) {
			echo '<div class="bang-set-subnav"' . ( $is_open ? '' : ' hidden' ) . '>';
			foreach ( $tab_groups[ $slug ] as $gi => $g ) {
				$gid = 'bang-g-' . $slug . '-' . $gi;
				echo '<a href="' . esc_url( $url . '#' . $gid ) . '" class="bang-set-subitem" data-bang-tab-link="' . esc_attr( $slug ) . '" data-bang-group="' . esc_attr( $gid ) . '">' . esc_html( $g ) . '</a>';
			}
			echo '</div>';
		}
		echo '</div>';
	}
	/* 设置备份：与七个功能域并列的独立入口 */
	echo '<div class="bang-set-nav-item' . ( 'backup' === $active ? ' is-open' : '' ) . '" data-bang-nav-tab="backup">';
	echo '<a href="' . esc_url( add_query_arg( 'tab', 'backup', $base ) ) . '" class="bang-set-item' . ( 'backup' === $active ? ' is-active' : '' ) . '" data-bang-tab-link="backup">'
		. ( isset( $icons['backup'] ) ? $icons['backup'] : '' ) // phpcs:ignore WordPress.Security.EscapeOutput
		. '<span>' . esc_html__( '设置备份', 'bang' ) . '</span></a>';
	echo '</div>';
	echo '</nav>';
	echo '<form method="post" action="options.php" class="bang-set-form">';
	settings_fields( 'bang_settings_group' );
	echo '<div class="bang-set-main">';
	foreach ( $tabs as $slug => $tab ) {
		echo '<section class="bang-set-panel" data-bang-tab="' . esc_attr( $slug ) . '"' . ( $slug === $active ? '' : ' hidden' ) . '>';
		echo '<div class="bang-set-intro"><h2>' . esc_html( $tab['label'] ) . '</h2><p>' . esc_html( $tab['desc'] ) . '</p></div>';
		$cur_group = '';
		$gi        = -1;
		foreach ( $tab['fields'] as $field ) {
			$g = isset( $field['group'] ) ? $field['group'] : '';
			if ( $g && $g !== $cur_group ) {
				$gi++;
				$gid = 'bang-g-' . $slug . '-' . $gi;
				echo '<h3 class="bang-f-group" id="' . esc_attr( $gid ) . '">' . esc_html( $g ) . '</h3>';
				$cur_group = $g;
			}
			bang_render_field( $field, isset( $options[ $field['key'] ] ) ? $options[ $field['key'] ] : '' );
		}
		if ( 'ai' === $slug ) {
			if ( function_exists( 'bang_ai_panel' ) ) {
				echo bang_ai_panel(); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			if ( function_exists( 'bang_publisher_panel' ) ) {
				echo bang_publisher_panel(); // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}
		if ( 'advanced' === $slug ) {
			echo bang_server_hints(); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		if ( 'safety' === $slug && function_exists( 'bang_unlock_panel' ) ) {
			echo bang_unlock_panel(); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		/* 邮件订阅已独立为「Bang 设置 → 邮件订阅」子菜单，不再占设置页空间 */
		/* 友链申请：只读待审列表 + 通过 / 忽略链接，同样不参与保存 */
		if ( 'content' === $slug && function_exists( 'bang_link_applications_panel' ) ) {
			bang_link_applications_panel();
		}
		/* 友情链接：只读入口，指向 WordPress 核心链接管理器 */
		if ( 'content' === $slug && function_exists( 'bang_links_admin_panel' ) ) {
			echo bang_links_admin_panel(); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		/* 人机校验：只读接线说明，不参与保存 */
		if ( 'safety' === $slug && function_exists( 'bang_geetest_panel' ) ) {
			echo bang_geetest_panel(); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		/* SMTP：只读接线说明 + 测试发送按钮，不参与保存 */
		if ( 'mail' === $slug && function_exists( 'bang_smtp_panel' ) ) {
			echo bang_smtp_panel(); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		/* 词库：已识别的 inc/data 词库文件 + 命中统计，只读展示，不参与保存 */
		if ( 'safety' === $slug && function_exists( 'bang_moderation_panel' ) ) {
			echo bang_moderation_panel(); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</section>';
	}
	/* 设置备份：与功能域并列，复用同一套 tab 切换；导入用 JS + admin-ajax，避免表单嵌套 */
	echo '<section class="bang-set-panel" data-bang-tab="backup"' . ( 'backup' === $active ? '' : ' hidden' ) . '>';
	echo '<div class="bang-set-intro"><h2>' . esc_html__( '设置备份', 'bang' ) . '</h2><p>' . esc_html__( '一键导出全部设置为 JSON 文件，或从备份文件恢复。', 'bang' ) . '</p></div>';
	echo '<div class="bang-backup">';
	echo '<h3 class="bang-f-group">' . esc_html__( '导出设置', 'bang' ) . '</h3>';
	echo '<p class="bang-f-d">' . esc_html__( '把当前主题的全部设置打包下载，换站或重装后可以原样恢复。备份文件含 API 密钥、邮箱密码等敏感信息，请妥善保管。', 'bang' ) . '</p>';
	echo '<a class="button" href="' . esc_url( add_query_arg( array( 'action' => 'bang_backup_export', '_wpnonce' => wp_create_nonce( 'bang_backup_export' ) ), admin_url( 'admin-ajax.php' ) ) ) . '">' . esc_html__( '下载备份文件', 'bang' ) . '</a>';
	echo '<h3 class="bang-f-group">' . esc_html__( '导入设置', 'bang' ) . '</h3>';
	echo '<p class="bang-f-d">' . esc_html__( '选择之前导出的 JSON 备份文件，导入后会覆盖当前全部设置，并同步开放注册与站点时区。', 'bang' ) . '</p>';
	echo '<div class="bang-backup-row">';
	echo '<input type="file" id="bang-backup-file" class="bang-backup-file" accept=".json,application/json" aria-label="' . esc_attr__( '选择备份文件', 'bang' ) . '">';
	echo '<button type="button" class="button" data-bang-backup-import>' . esc_html__( '导入并覆盖', 'bang' ) . '</button>';
	echo '<span class="bang-set-hint" data-bang-backup-status></span>';
	echo '</div>';
	echo '</div></section>';
	echo '</div>';
	echo '<footer class="bang-set-foot"' . ( 'backup' === $active ? ' hidden' : '' ) . '><button type="submit" class="bang-set-save">' . esc_html__( '保存设置', 'bang' ) . '</button><span class="bang-set-hint">' . esc_html__( '保存后立即在前台生效。', 'bang' ) . '</span></footer>';
	echo '</form>';
	echo '</div></div>';
	?>
<style>
/* ============================================================
   Bang 设置 · 统一视觉
   一套 token 收口全部颜色 / 圆角 / 动效，不再散落硬编码：
   · 颜色：白底 + 黑(#111)点缀，灰阶只用 --bang-ink-2 / -3 / --bang-line / --bang-soft
   · 圆角：矩形统一 12px(--bang-r)，内嵌小件 8px(--bang-r-sm)，胶囊 999px(--bang-pill) 仅开关与徽章
   · 与前台同语言：白底黑按钮；黑色只出现在 logo 块 / 激活导航项 / 保存按钮 / 开关 / focus 描边
   · 不用 box-shadow、渐变、hover 上浮；focus 一律 border-color + outline
   · 动效只允许 opacity / background-color / border-color / transform
   ============================================================ */
.bang-set{
	--bang-ink:#111;--bang-ink-2:#5b5b5b;--bang-ink-3:#8c8c8c;
	--bang-line:#e5e5e5;--bang-soft:#f6f6f6;--bang-bg:#fff;
	--bang-r:12px;--bang-r-sm:8px;--bang-pill:999px;
	--bang-ease:.18s ease;
	max-width:none;margin:16px 0 32px;color:var(--bang-ink);font-family:inherit
}
.bang-set *,.bang-set *::before,.bang-set *::after{box-sizing:border-box}
/* 头部：与面板同为白底描边卡片，黑底只留给 logo 方块，消除「黑头 + 白面板」两套语言 */
.bang-set-head{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:22px 24px;background:var(--bang-bg);border:1px solid var(--bang-line);border-radius:var(--bang-r);color:var(--bang-ink)}
.bang-set-brand{display:flex;align-items:center;gap:16px;min-width:0}
.bang-set-logo{display:inline-flex;flex:none;width:56px;height:56px;color:var(--bang-ink)}
.bang-set-logo img{width:100%;height:100%;display:block;object-fit:cover;border-radius:9px}
.bang-set-ttl{min-width:0}
.bang-set-ttl h1{display:flex;align-items:center;gap:8px;margin:0;font-size:24px;font-weight:700;line-height:1.2;letter-spacing:-.04em;color:var(--bang-ink)}
.bang-set-edition{padding:2px 7px;border:1px solid var(--bang-line);border-radius:var(--bang-r-sm);font-size:10px;font-weight:600;letter-spacing:.12em;color:var(--bang-ink-3)}
.bang-set-ttl p{margin:5px 0 0;font-size:12.5px;line-height:1.6;color:var(--bang-ink-2)}
.bang-set-meta{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:9px}
.bang-set-ver{padding:2px 9px;border:1px solid var(--bang-line);border-radius:var(--bang-pill);background:var(--bang-soft);font-size:11px;color:var(--bang-ink-2)}
.bang-set-oss{font-size:11.5px;color:var(--bang-ink-3)}
.bang-set-oss a{color:var(--bang-ink-2);text-decoration:none;border-bottom:1px solid var(--bang-line);transition:border-color var(--bang-ease)}
.bang-set-oss a:hover{border-color:var(--bang-ink)}
.bang-set-src{display:inline-flex;align-items:center;gap:8px;flex:none;padding:9px 14px;border:1px solid var(--bang-ink);border-radius:var(--bang-r);background:var(--bang-ink);color:var(--bang-bg);font-size:12.5px;font-weight:600;text-decoration:none;white-space:nowrap;transition:opacity var(--bang-ease)}
.bang-set-src svg{width:17px;height:17px}
.bang-set-src:hover{opacity:.82}
.bang-set-src:focus-visible{outline:2px solid var(--bang-ink);outline-offset:2px}
/* 主体：左侧导航 + 右侧内容 */
.bang-set-body{display:flex;align-items:flex-start;gap:20px;margin-top:20px}
.bang-set-nav{position:sticky;top:46px;flex:0 0 200px;display:flex;flex-direction:column;gap:4px;padding:10px;background:var(--bang-bg);border:1px solid var(--bang-line);border-radius:var(--bang-r)}
.bang-set-item{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:var(--bang-r-sm);font-size:13px;font-weight:500;text-decoration:none;color:var(--bang-ink-2);transition:background-color var(--bang-ease),color var(--bang-ease)}
.bang-set-item svg{width:16px;height:16px;flex:none}
.bang-set-item:hover{color:var(--bang-ink);background:var(--bang-soft)}
.bang-set-item.is-active,.bang-set-item.is-active:hover{background:var(--bang-ink);color:var(--bang-bg)}
.bang-set-item:focus-visible{outline:2px solid var(--bang-ink);outline-offset:2px}
/* 二级子菜单：一级项激活时展开，点子项直接滚到对应设置组 */
.bang-set-nav-item{display:flex;flex-direction:column}
.bang-set-subnav{display:flex;flex-direction:column;gap:4px;margin:6px 0 6px 22px;padding-left:10px;border-left:1px solid var(--bang-line)}
.bang-set-subnav[hidden]{display:none}
.bang-set-subitem{display:block;padding:8px 10px;border-radius:var(--bang-r-sm);font-size:12px;line-height:1.45;color:var(--bang-ink-3);text-decoration:none;box-shadow:none;transition:background-color var(--bang-ease),color var(--bang-ease)}
.bang-set-subitem:focus,.bang-set-subitem:focus-visible{outline:none;box-shadow:none}
.bang-set-subitem:focus-visible{background:var(--bang-soft);color:var(--bang-ink)}
.bang-set-subitem:hover{color:var(--bang-ink);background:var(--bang-soft)}
.bang-set-subitem.is-current,.bang-set-subitem.is-current:hover{background:var(--bang-ink);color:var(--bang-bg);font-weight:600}
.bang-set-main{flex:1;min-width:0}
.bang-set-panel{padding:22px 24px 26px;background:var(--bang-bg);border:1px solid var(--bang-line);border-radius:var(--bang-r);margin:0 0 20px}
.bang-set-panel[hidden]{display:none}
.bang-set-intro{margin:0 0 18px;padding-bottom:16px;border-bottom:1px solid var(--bang-line)}
.bang-set-intro h2{margin:0 0 6px;font-size:16px;font-weight:600;color:var(--bang-ink)}
.bang-set-intro p{margin:0;font-size:12px;line-height:1.6;color:var(--bang-ink-3)}
/* 字段 */
.bang-f{display:flex;flex-direction:column;gap:7px;margin:0 0 18px}
.bang-f-row{flex-direction:row;align-items:flex-start;justify-content:space-between;gap:16px;max-width:760px}
.bang-f-t{font-size:13px;font-weight:600;color:var(--bang-ink)}
.bang-f-row .bang-f-t{display:flex;flex-direction:column;gap:4px;max-width:520px}
.bang-f-d{font-size:12px;font-weight:400;line-height:1.6;color:var(--bang-ink-3)}
.bang-f-d b{font-weight:600;color:var(--bang-ink-2)}
.bang-set-panel > p.bang-f-d{margin:0 0 8px}
.bang-set code{padding:1px 5px;border:1px solid var(--bang-line);border-radius:var(--bang-r-sm);background:var(--bang-soft);font-family:Consolas,Monaco,monospace;font-size:11.5px;color:var(--bang-ink)}
/* 输入控件：统一描边 / 圆角 / 高度；focus 改 border-color + outline，并清掉 WP 默认 box-shadow */
.bang-f-input,.bang-f-num,.bang-f-select select,textarea.bang-f-input{appearance:none;-webkit-appearance:none;width:100%;max-width:760px;padding:9px 12px;border:1px solid var(--bang-line);border-radius:var(--bang-r);background:var(--bang-bg);color:var(--bang-ink);font-size:13px;line-height:1.5;font-family:inherit;transition:border-color var(--bang-ease)}
.bang-f-num{width:110px;max-width:none}
textarea.bang-f-input{max-width:760px;resize:vertical;min-height:64px}
textarea.bang-f-code{font-family:Consolas,Monaco,monospace;font-size:12px;direction:ltr}
.bang-f-input:focus,.bang-f-num:focus,.bang-f-select select:focus{box-shadow:none;outline:2px solid var(--bang-ink);outline-offset:1px;border-color:var(--bang-ink)}
.bang-f-input::placeholder,textarea.bang-f-input::placeholder{color:var(--bang-ink-3)}
/* 未开发完成的功能：整组置灰、控件禁用，明确标注「开发中」 */
.bang-f-disabled{opacity:.48}
.bang-f-disabled .bang-f-input,.bang-f-disabled .bang-f-num,.bang-f-disabled select,.bang-f-disabled textarea{background:var(--bang-soft);cursor:not-allowed}
.bang-f-disabled .bang-toggle{pointer-events:none}
.bang-f-disabled .button{pointer-events:none;opacity:.6}
.bang-f-disabled .bang-f-t::after{content:"开发中";margin-left:8px;padding:1px 6px;border:1px solid var(--bang-line);border-radius:var(--bang-r-sm);font-size:10px;font-weight:500;color:var(--bang-ink-3)}
.bang-unlock-locked{opacity:.48;pointer-events:none}
.bang-f-select{position:relative;display:inline-block;width:100%;max-width:760px}
.bang-f-select select{padding-right:34px;cursor:pointer}
.bang-f-select svg{position:absolute;right:12px;top:50%;width:14px;height:14px;transform:translateY(-50%);color:var(--bang-ink-2);pointer-events:none}
/* 多选勾选：胶囊标签，选中反白，未选描边 */
.bang-f-checks{display:flex;flex-wrap:wrap;gap:8px;max-width:760px}
.bang-f-check{display:inline-flex;align-items:center;cursor:pointer}
.bang-f-check input{position:absolute;opacity:0;width:1px;height:1px}
.bang-f-check span{display:inline-flex;align-items:center;padding:6px 13px;border:1px solid var(--bang-line);border-radius:var(--bang-pill);background:var(--bang-bg);font-size:12.5px;font-weight:500;color:var(--bang-ink-2);transition:background var(--bang-ease),color var(--bang-ease),border-color var(--bang-ease)}
.bang-f-check input:checked + span{border-color:var(--bang-ink);background:var(--bang-ink);color:var(--bang-bg)}
.bang-f-check input:focus-visible + span{outline:2px solid var(--bang-ink);outline-offset:2px}
.bang-f-disabled .bang-f-check{pointer-events:none}
/* 图片字段：直接填链接的 input 原样保留，旁边是同一套次要按钮 + 缩略图预览 */
.bang-f-media{display:flex;flex-direction:column;gap:8px;max-width:760px}
.bang-f-media-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.bang-f-media-row .bang-f-input{flex:1 1 240px;min-width:200px;max-width:none}
/* 次要按钮：从媒体库选择 / 清除 / 导出 CSV 共用一套，不再各写各的 */
.bang-set .button{appearance:none;-webkit-appearance:none;display:inline-flex;align-items:center;justify-content:center;height:36px;padding:0 14px;border:1px solid var(--bang-line);border-radius:var(--bang-r);background:var(--bang-bg);color:var(--bang-ink);font-size:12px;font-weight:500;line-height:1;font-family:inherit;text-decoration:none;cursor:pointer;box-shadow:none;flex:none;transition:background-color var(--bang-ease),border-color var(--bang-ease)}
.bang-set .button:hover{background:var(--bang-soft);border-color:var(--bang-ink);color:var(--bang-ink)}
.bang-set .button:focus{box-shadow:none;outline:2px solid var(--bang-ink);outline-offset:1px}
.bang-f-media-preview{display:inline-flex;align-items:center;justify-content:center;align-self:flex-start;max-width:240px;max-height:120px;padding:6px;border:1px solid var(--bang-line);border-radius:var(--bang-r);background:var(--bang-soft)}
.bang-f-media-preview[hidden]{display:none}
.bang-f-media-preview img{display:block;max-width:100%;max-height:104px;width:auto;height:auto;object-fit:contain;border-radius:var(--bang-r-sm)}
/* 页脚链接分组：可视化增删「列 / 链接」，沿用同一套输入控件 + 次要按钮语言 */
.bang-f-links{display:flex;flex-direction:column;gap:12px;max-width:760px}
.bang-f-links-groups{display:flex;flex-direction:column;gap:12px}
.bang-f-link-group{padding:14px;border:1px solid var(--bang-line);border-radius:var(--bang-r);background:var(--bang-bg)}
.bang-f-link-head{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.bang-f-link-head .bang-f-link-title{flex:1 1 auto;min-width:0;padding:8px 10px;border:1px solid var(--bang-line);border-radius:var(--bang-r);background:var(--bang-bg);color:var(--bang-ink);font-size:13px;line-height:1.5;font-family:inherit}
.bang-f-link-head .bang-f-link-title:focus{box-shadow:none;outline:2px solid var(--bang-ink);outline-offset:1px;border-color:var(--bang-ink)}
.bang-f-link-head .bang-f-link-title::placeholder{color:var(--bang-ink-3)}
.bang-f-link-group-del,.bang-f-link-del{appearance:none;-webkit-appearance:none;flex:none;border:1px solid var(--bang-line);border-radius:var(--bang-r);background:var(--bang-bg);color:var(--bang-ink-3);font-family:inherit;cursor:pointer;transition:color var(--bang-ease),border-color var(--bang-ease)}
.bang-f-link-group-del{padding:7px 10px;font-size:12px;line-height:1}
.bang-f-link-del{width:30px;height:30px;padding:0;font-size:16px;line-height:1}
.bang-f-link-group-del:hover,.bang-f-link-del:hover{color:var(--bang-ink);border-color:var(--bang-ink)}
.bang-f-link-list{display:flex;flex-direction:column;gap:8px}
.bang-f-link-row{display:flex;align-items:center;gap:8px}
.bang-f-link-row .bang-f-link-name,.bang-f-link-row .bang-f-link-url{min-width:0;padding:8px 10px;border:1px solid var(--bang-line);border-radius:var(--bang-r);background:var(--bang-bg);color:var(--bang-ink);font-size:12.5px;line-height:1.5;font-family:inherit;transition:border-color var(--bang-ease)}
.bang-f-link-row .bang-f-link-name{flex:0 1 160px}
.bang-f-link-row .bang-f-link-url{flex:1 1 240px}
.bang-f-link-row .bang-f-link-name:focus,.bang-f-link-row .bang-f-link-url:focus{box-shadow:none;outline:2px solid var(--bang-ink);outline-offset:1px;border-color:var(--bang-ink)}
.bang-f-link-row .bang-f-link-name::placeholder,.bang-f-link-row .bang-f-link-url::placeholder{color:var(--bang-ink-3)}
.bang-f-link-add,.bang-f-links-add-group{align-self:flex-start;height:30px;padding:0 12px}
@media(max-width:782px){.bang-f-link-row{flex-wrap:wrap}.bang-f-link-row .bang-f-link-name,.bang-f-link-row .bang-f-link-url{flex:1 1 100%}}
/* 文章侧栏小工具：拖拽手柄排序 + 启用勾选 */
.bang-f-widgets{display:flex;flex-direction:column;gap:10px;max-width:560px}
.bang-f-widgets-list{display:flex;flex-direction:column;gap:8px}
.bang-f-widget{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--bang-line);border-radius:var(--bang-r);background:var(--bang-bg);transition:border-color var(--bang-ease),opacity var(--bang-ease)}
.bang-f-widget.is-dragging{opacity:.42}
.bang-f-widget.is-over{border-color:var(--bang-ink)}
.bang-f-widget-grip{flex:none;display:flex;align-items:center;color:var(--bang-ink-3);cursor:grab;touch-action:none}
.bang-f-widget-grip:active{cursor:grabbing}
.bang-f-widget-grip svg{display:block;width:18px;height:18px}
.bang-f-widget-name{flex:1 1 auto;min-width:0;font-size:13px;color:var(--bang-ink)}
.bang-f-widget-on{display:flex;align-items:center;gap:6px;flex:none;font-size:12px;color:var(--bang-ink-2);cursor:pointer}
.bang-f-widget-on input{width:15px;height:15px;margin:0;accent-color:var(--bang-ink)}
/* 开关：轨道盖在 input 上必须让点击穿透，否则点开关本体不会切换 checkbox（勿回退） */
.bang-toggle{position:relative;flex:none;width:40px;height:22px;margin-top:1px;cursor:pointer}
.bang-toggle input{position:absolute;z-index:1;opacity:0;width:100%;height:100%;margin:0;cursor:pointer}
.bang-toggle-track{position:absolute;inset:0;pointer-events:none;border-radius:var(--bang-pill);background:var(--bang-line);transition:background-color var(--bang-ease)}
.bang-toggle-track::after{content:"";position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:var(--bang-pill);background:var(--bang-bg);transition:transform var(--bang-ease)}
.bang-toggle input:checked + .bang-toggle-track{background:var(--bang-ink)}
.bang-toggle input:checked + .bang-toggle-track::after{transform:translateX(18px)}
.bang-toggle input:focus-visible + .bang-toggle-track{outline:2px solid var(--bang-ink);outline-offset:2px}
/* 底部保存：悬浮固定在内容区底边，滚到任意位置都能保存；left/right 由 JS 跟随 .bang-set，避开 WP 后台菜单 */
.bang-set-form{padding-bottom:88px}
.bang-set-foot{position:fixed;bottom:0;left:0;right:0;z-index:99;display:flex;align-items:center;gap:14px;padding:12px 24px;border-top:1px solid var(--bang-line);background:var(--bang-bg)}
.bang-set-save{appearance:none;-webkit-appearance:none;padding:10px 28px;border:1px solid var(--bang-ink);border-radius:var(--bang-r);background:var(--bang-ink);color:var(--bang-bg);font-size:13px;font-weight:600;line-height:1.4;font-family:inherit;cursor:pointer;transition:opacity var(--bang-ease)}
.bang-set-save:hover{opacity:.82}
.bang-set-save:focus{box-shadow:none;outline:2px solid var(--bang-ink);outline-offset:2px}
.bang-set-hint{font-size:12px;color:var(--bang-ink-3)}
.bang-set-foot[hidden]{display:none}
/* 设置备份面板 */
.bang-backup{max-width:760px}
.bang-backup > .bang-f-group:first-child{margin-top:0;padding-top:0;border-top:0}
.bang-backup-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:12px}
.bang-backup-file{max-width:360px;font-size:12px;color:var(--bang-ink-2)}
/* 追加面板：服务器建议 / 解锁接线说明 / 订阅者列表 */
.bang-set h3{margin:22px 0 8px;font-size:14px;font-weight:600;color:var(--bang-ink)}
.bang-set-panel > h3{margin-top:26px;padding-top:20px;border-top:1px solid var(--bang-line)}
.bang-set-panel > h3.bang-f-group:first-of-type{margin-top:0;padding-top:0;border-top:0}
.bang-set .bang-code{max-width:760px;overflow:auto;padding:14px 16px;margin:10px 0 0;border:1px solid var(--bang-line);border-radius:var(--bang-r);background:var(--bang-soft);font-family:Consolas,Monaco,monospace;font-size:12px;line-height:1.7;color:var(--bang-ink)}
.bang-set .bang-subs{margin-top:26px;padding-top:20px;border-top:1px solid var(--bang-line)}
.bang-set .bang-subs h3{margin:0 0 6px;padding:0;border:0}
.bang-set .description{font-size:12px;line-height:1.6;color:var(--bang-ink-3);margin:6px 0 0}
.bang-set .bang-subs-table.widefat{max-width:760px;margin:12px 0 0;background:var(--bang-bg);border:1px solid var(--bang-line);border-radius:var(--bang-r);border-collapse:separate;border-spacing:0;overflow:hidden;box-shadow:none}
.bang-set .bang-subs-table.widefat th,.bang-set .bang-subs-table.widefat td{padding:9px 12px;border:0;border-bottom:1px solid var(--bang-line);font-size:12px;color:var(--bang-ink);text-align:left}
.bang-set .bang-subs-table.widefat thead th{background:var(--bang-soft);font-weight:600}
.bang-set .bang-subs-table.widefat.striped tbody tr:nth-child(odd) td{background:var(--bang-bg)}
.bang-set .bang-subs-table.widefat.striped tbody tr:nth-child(even) td{background:var(--bang-soft)}
.bang-set .bang-subs-table.widefat tbody tr:last-child td{border-bottom:0}
/* 友链申请：待审列表 + 通过 / 忽略操作，沿用同一套 token */
.bang-set .bang-linkapply{margin-top:26px;padding-top:20px;border-top:1px solid var(--bang-line)}
.bang-set .bang-linkapply h3{margin:0 0 6px;padding:0;border:0}
.bang-set .bang-linkapply-ok{color:var(--bang-ink);font-weight:600}
.bang-set .bang-linkapply-list{display:flex;flex-direction:column;gap:10px;margin-top:14px}
.bang-set .bang-linkapply-row{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:12px 14px;border:1px solid var(--bang-line);border-radius:var(--bang-r)}
.bang-set .bang-linkapply-main{min-width:0;font-size:13px;color:var(--bang-ink)}
.bang-set .bang-linkapply-main strong{font-weight:600}
.bang-set .bang-linkapply-main a{color:var(--bang-ink-2);text-decoration:none;border-bottom:1px solid var(--bang-line)}
.bang-set .bang-linkapply-meta{display:block;margin-top:4px;font-size:11px;color:var(--bang-ink-3)}
.bang-set .bang-linkapply-desc{margin:6px 0 0;font-size:12px;line-height:1.6;color:var(--bang-ink-2)}
.bang-set .bang-linkapply-warn{margin:6px 0 0;font-size:12px;line-height:1.6;color:var(--bang-ink);font-weight:600}
.bang-set .bang-linkapply-ops{flex:none;display:flex;gap:8px}
.bang-set .bang-linkapply-ops .button.is-primary{background:var(--bang-ink);border-color:var(--bang-ink);color:var(--bang-bg)}
/* 友情链接入口：只读面板，指向核心链接管理器 */
.bang-set .bang-links-admin{margin-top:26px;padding-top:20px;border-top:1px solid var(--bang-line)}
.bang-set .bang-links-admin h3{margin:0 0 8px;padding:0;border:0}
.bang-set .bang-links-admin p.bang-f-d{margin:0 0 8px}
/* SMTP 测试面板：挂在邮件 tab 字段之后（只读 + 测试按钮，不参与保存） */
.bang-set .bang-smtp-panel{margin-top:26px;padding-top:20px;border-top:1px solid var(--bang-line)}
.bang-set .bang-smtp-panel h3{margin:0 0 8px;padding:0;border:0}
.bang-set .bang-smtp-panel p.bang-f-d{margin:0 0 8px}
.bang-set .bang-smtp-actions{display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin:14px 0 0}
.bang-set .bang-smtp-msg{font-size:12.5px;line-height:1.6;color:var(--bang-ink-3)}
.bang-set .bang-smtp-msg.is-ok{color:var(--bang-ink)}
.bang-set .bang-smtp-msg.is-err{color:#c0392b}
/* 人机校验接线说明（挂在安全设置 tab） */
.bang-set .bang-geetest-panel{margin-top:26px;padding-top:20px;border-top:1px solid var(--bang-line)}
.bang-set .bang-geetest-panel h3{margin:0 0 10px;padding:0;border:0;font-size:14px;font-weight:600;color:var(--bang-ink)}
.bang-set .bang-geetest-panel p.bang-f-d{margin:0 0 8px}
/* 词库面板：inc/data 词库文件列表 + 命中统计（只读） */
.bang-set .bang-words-panel{margin-top:26px;padding-top:20px;border-top:1px solid var(--bang-line)}
.bang-set .bang-words-panel > p.bang-f-d{margin:0 0 10px}
.bang-set .bang-words-panel .bang-f-row{margin:16px 0 8px}
.bang-set .bang-wordfiles{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 4px;padding:0;list-style:none}
.bang-set .bang-wordfiles li{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border:1px solid var(--bang-line);border-radius:var(--bang-r-sm);background:var(--bang-soft);font-size:12px;color:var(--bang-ink)}
.bang-set .bang-wordfiles code{padding:0;border:0;background:none}
.bang-set .bang-wordfiles span{color:var(--bang-ink-3)}
.bang-set .bang-wordstats.widefat,.bang-set .bang-wordlog.widefat{max-width:760px;margin:12px 0 0;background:var(--bang-bg);border:1px solid var(--bang-line);border-radius:var(--bang-r);border-collapse:separate;border-spacing:0;overflow:hidden;box-shadow:none}
.bang-set .bang-wordstats.widefat th,.bang-set .bang-wordstats.widefat td,.bang-set .bang-wordlog.widefat th,.bang-set .bang-wordlog.widefat td{padding:9px 12px;border:0;border-bottom:1px solid var(--bang-line);font-size:12px;color:var(--bang-ink);text-align:left}
.bang-set .bang-wordstats.widefat thead th,.bang-set .bang-wordlog.widefat thead th{background:var(--bang-soft);font-weight:600}
.bang-set .bang-wordstats.widefat tbody tr:last-child td,.bang-set .bang-wordlog.widefat tbody tr:last-child td{border-bottom:0}
/* AI 摘要批量面板 */
.bang-set .bang-ai-panel{margin:16px 0;padding:16px 18px;background:var(--bang-soft);border:1px solid var(--bang-line);border-radius:var(--bang-r)}
.bang-set .bang-ai-panel h3{margin:0 0 6px;font-size:14px;font-weight:600;color:var(--bang-ink)}
.bang-set .bang-ai-panel button[disabled]{opacity:.5;cursor:not-allowed}
.bang-set .bang-ai-panel [data-bang-ai-status]{display:inline-block;margin-left:8px;font-size:12px;color:var(--bang-ink-2)}
/* 发布器面板 */
.bang-set .bang-pub-panel{margin:16px 0;padding:16px 18px;background:var(--bang-soft);border:1px solid var(--bang-line);border-radius:var(--bang-r)}
.bang-set .bang-pub-panel h3{margin:0 0 6px;font-size:14px;font-weight:600;color:var(--bang-ink)}
.bang-set .bang-pub-panel code{background:var(--bang-bg);border:1px solid var(--bang-line);border-radius:6px;padding:2px 6px;font-size:12px;word-break:break-all}
.bang-set .bang-pub-panel [data-bang-pub-status]{font-size:12px;color:var(--bang-ink-2)}
.bang-set .bang-pub-panel [data-bang-pub-secret]{margin-top:10px}
.bang-set .bang-pub-panel [data-bang-pub-value]{width:100%;margin-top:4px;font-family:monospace}
.bang-set .bang-pub-doc{margin-top:12px}
.bang-set .bang-pub-doc summary{cursor:pointer;font-size:13px;color:var(--bang-ink-2)}
/* 窄屏：导航收到顶部横排 */
@media (max-width:782px){
	.bang-set-body{flex-direction:column;gap:14px}
	.bang-set-nav{position:static;flex:none;width:100%;flex-direction:row;flex-wrap:wrap}
	.bang-set-nav-item{flex:1 1 auto;min-width:104px}
	.bang-set-subnav{margin:2px 0 4px;padding-left:8px}
	.bang-set-item{padding:9px 12px}
	.bang-set-head{padding:16px 18px;align-items:flex-start}
	.bang-set-logo{width:44px;height:44px}
	.bang-set-ttl h1{font-size:20px}
	.bang-set-src span{display:none}
	.bang-set-src{padding:9px}
	.bang-set-panel{padding:18px 16px 22px}
	.bang-set-foot{flex-wrap:wrap;padding:10px 16px}
	.bang-set-form{padding-bottom:80px}
	.bang-set .bang-linkapply-row{flex-direction:column;gap:10px}
}
</style>
<script>
/* 一级 tab + 二级子菜单：切面板、展开归属子菜单、点子项滚到对应设置组 */
(function(){
	var links=document.querySelectorAll('.bang-set [data-bang-tab-link]');
	var panels=document.querySelectorAll('.bang-set [data-bang-tab]');
	var navItems=document.querySelectorAll('.bang-set-nav-item');
	var subnavs=document.querySelectorAll('.bang-set-subnav');
	var foot=document.querySelector('.bang-set-foot');
	var setHost=document.querySelector('.bang-set');
	/* 悬浮保存条左右对齐内容区：避开 WP 后台菜单，菜单折叠/展开后自动校正 */
	function placeFoot(){
		if(!foot||foot.hidden||!setHost){return;}
		var r=setHost.getBoundingClientRect();
		foot.style.left=Math.max(0,Math.round(r.left))+'px';
		foot.style.right=Math.max(0,Math.round(window.innerWidth-r.right))+'px';
	}
	function activate(tab,clicked){
		Array.prototype.forEach.call(panels,function(p){p.hidden=p.getAttribute('data-bang-tab')!==tab});
		Array.prototype.forEach.call(links,function(x){
			if(x.classList.contains('bang-set-item')){x.classList.toggle('is-active',x.getAttribute('data-bang-tab-link')===tab);}
			else{x.classList.toggle('is-current',x===clicked);}
		});
		Array.prototype.forEach.call(navItems,function(item){item.classList.toggle('is-open',item.getAttribute('data-bang-nav-tab')===tab)});
		Array.prototype.forEach.call(subnavs,function(s){s.hidden=!s.parentNode.classList.contains('is-open')});
		if(foot){foot.hidden=(tab==='backup');}
		placeFoot();
	}
	function scrollToGroup(gid){
		if(!gid){return;}
		setTimeout(function(){
			var el=document.getElementById(gid);
			if(!el){return;}
			window.scrollTo({top:el.getBoundingClientRect().top+window.pageYOffset-46,behavior:'smooth'});
		},40);
	}
	Array.prototype.forEach.call(links,function(a){
		a.addEventListener('click',function(e){
			e.preventDefault();
			var t=a.getAttribute('data-bang-tab-link');
			activate(t,a);
			if(window.history&&history.replaceState){history.replaceState(null,'',a.href)}
			scrollToGroup(a.getAttribute('data-bang-group'));
		});
	});
	/* 带 #bang-g-* 进来的链接：先切到对应 tab 再滚过去 */
	var hash=window.location.hash.replace(/^#/,'');
	if(hash.indexOf('bang-g-')===0){
		var target=null;
		Array.prototype.forEach.call(links,function(a){if(a.getAttribute('data-bang-group')===hash){target=a;}});
		if(target){activate(target.getAttribute('data-bang-tab-link'),target);scrollToGroup(hash);}
	}
	/* 悬浮保存条：初始定位 + 窗口缩放 / 后台菜单折叠后重新对齐内容区 */
	placeFoot();
	window.addEventListener('resize',placeFoot);
	if(window.MutationObserver&&document.body){
		new MutationObserver(function(){setTimeout(placeFoot,220);}).observe(document.body,{attributes:true,attributeFilter:['class']});
	}
})();
/* 图片字段：从媒体库选择 + 直接填链接，两者都刷新预览 */
(function(){
	var frame=null,activeInput=null;
	function preview(input){
		if(!input){return;}
		var wrap=input.closest?input.closest('[data-bang-media-field]'):null;
		var box=wrap?wrap.querySelector('[data-bang-media-preview]'):null;
		if(!box){return;}
		var url=(input.value||'').trim();
		if(!url){box.hidden=true;box.innerHTML='';return;}
		box.innerHTML='';
		var img=document.createElement('img');
		img.src=url;img.alt='';
		box.appendChild(img);
		box.hidden=false;
	}
	Array.prototype.forEach.call(document.querySelectorAll('[data-bang-media-input]'),function(input){
		input.addEventListener('input',function(){preview(input)});
		input.addEventListener('change',function(){preview(input)});
	});
	Array.prototype.forEach.call(document.querySelectorAll('[data-bang-media-pick]'),function(btn){
		btn.addEventListener('click',function(e){
			e.preventDefault();
			var input=document.getElementById(btn.getAttribute('data-bang-media-pick'));
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
	Array.prototype.forEach.call(document.querySelectorAll('[data-bang-media-clear]'),function(btn){
		btn.addEventListener('click',function(e){
			e.preventDefault();
			var input=document.getElementById(btn.getAttribute('data-bang-media-clear'));
			if(!input){return;}
			input.value='';
			preview(input);
			input.focus();
		});
	});
})();
/* 页脚链接分组：可视化增删「列 / 链接」，序列化回隐藏 textarea（格式与前台解析一致） */
(function(){
	function parse(raw){
		var blocks=(raw||'').split(/\n\s*\n/),groups=[];
		blocks.forEach(function(block){
			var lines=block.split(/\r\n|\r|\n/).map(function(s){return s.replace(/^\s+|\s+$/g,'');}).filter(function(s){return s.length;});
			if(!lines.length){return;}
			var title=lines.shift(),items=[];
			lines.forEach(function(line){
				var p=line.split('|');
				var name=(p[0]||'').replace(/^\s+|\s+$/g,'');
				var url=(p[1]||'').replace(/^\s+|\s+$/g,'');
				if(name&&url){items.push({name:name,url:url});}
			});
			groups.push({title:title,items:items});
		});
		return groups;
	}
	function serialize(groups){
		return groups.map(function(g){
			var lines=[g.title];
			g.items.forEach(function(it){lines.push(it.name+' | '+it.url);});
			return lines.join('\n');
		}).join('\n\n');
	}
	function buildRow(item){
		var row=document.createElement('div');
		row.className='bang-f-link-row';
		row.innerHTML='<input type="text" class="bang-f-link-name" placeholder="链接名" data-bang-link-name>'
			+'<input type="text" class="bang-f-link-url" placeholder="/about/ 或 https://" data-bang-link-url>'
			+'<button type="button" class="bang-f-link-del" data-bang-link-del title="删除这条">×</button>';
		row.querySelector('[data-bang-link-name]').value=item.name;
		row.querySelector('[data-bang-link-url]').value=item.url;
		return row;
	}
	function buildGroup(group){
		var box=document.createElement('div');
		box.className='bang-f-link-group';
		var head=document.createElement('div');
		head.className='bang-f-link-head';
		head.innerHTML='<input type="text" class="bang-f-link-title" placeholder="分组标题（如：关于）" data-bang-link-title>'
			+'<button type="button" class="bang-f-link-group-del" data-bang-link-group-del title="删除整个分组">删除分组</button>';
		head.querySelector('[data-bang-link-title]').value=group.title;
		var list=document.createElement('div');
		list.className='bang-f-link-list';
		group.items.forEach(function(it){list.appendChild(buildRow(it));});
		var add=document.createElement('button');
		add.type='button';
		add.className='button bang-f-link-add';
		add.setAttribute('data-bang-link-add','');
		add.textContent='＋ 添加链接';
		box.appendChild(head);box.appendChild(list);box.appendChild(add);
		return box;
	}
	Array.prototype.forEach.call(document.querySelectorAll('[data-bang-links]'),function(root){
		var data=root.querySelector('.bang-f-links-data');
		var holder=root.querySelector('[data-bang-links-groups]');
		var addGroup=root.querySelector('[data-bang-links-add-group]');
		if(!data||!holder){return;}
		var groups=parse(data.value);
		groups.forEach(function(g){holder.appendChild(buildGroup(g));});
		function sync(){
			var out=[];
			Array.prototype.forEach.call(holder.querySelectorAll('.bang-f-link-group'),function(g){
				var title=g.querySelector('[data-bang-link-title]');
				var items=[];
				Array.prototype.forEach.call(g.querySelectorAll('.bang-f-link-row'),function(r){
					var n=r.querySelector('[data-bang-link-name]');
					var u=r.querySelector('[data-bang-link-url]');
					var name=n?n.value.replace(/^\s+|\s+$/g,''):'';
					var url=u?u.value.replace(/^\s+|\s+$/g,''):'';
					if(name&&url){items.push({name:name,url:url});}
				});
				out.push({title:title?title.value.replace(/^\s+|\s+$/g,''):'',items:items});
			});
			data.value=serialize(out);
		}
		addGroup.addEventListener('click',function(){
			holder.appendChild(buildGroup({title:'',items:[{name:'',url:''}]}));
		});
		holder.addEventListener('click',function(e){
			var add=e.target.closest?e.target.closest('[data-bang-link-add]'):null;
			if(add){var g=add.parentNode;g.querySelector('.bang-f-link-list').appendChild(buildRow({name:'',url:''}));return;}
			var del=e.target.closest?e.target.closest('[data-bang-link-del]'):null;
			if(del){var r=del.parentNode;if(r.parentNode){r.parentNode.removeChild(r);}return;}
			var gd=e.target.closest?e.target.closest('[data-bang-link-group-del]'):null;
			if(gd){var gb=gd.closest('.bang-f-link-group');if(gb&&gb.parentNode){gb.parentNode.removeChild(gb);}return;}
		});
		var form=data.form||data.closest('form');
		if(form){form.addEventListener('submit',sync);}
	});
})();
/* 文章侧栏小工具：拖拽手柄排序 + 启用勾选，隐藏 input 保存「已启用且按顺序」的 id 串 */
(function(){
	var roots=document.querySelectorAll('[data-bang-widgets]');
	if(!roots.length){return;}
	Array.prototype.forEach.call(roots,function(root){
		var data=root.querySelector('.bang-f-widgets-data');
		var list=root.querySelector('[data-bang-widgets-list]');
		if(!data||!list){return;}
		function sync(){
			var out=[];
			Array.prototype.forEach.call(list.children,function(row){
				var cb=row.querySelector('[data-bang-widget-on]');
				if(cb&&cb.checked){out.push(row.getAttribute('data-bang-widget')||'');}
			});
			data.value=out.filter(Boolean).join(',');
		}
		var dragging=null;
		list.addEventListener('dragstart',function(e){
			var grip=e.target.closest?e.target.closest('[draggable="true"]'):null;
			if(!grip){return;}
			dragging=grip.parentNode;
			dragging.classList.add('is-dragging');
			if(e.dataTransfer){e.dataTransfer.effectAllowed='move';e.dataTransfer.setData('text/plain',dragging.getAttribute('data-bang-widget')||'');}
		});
		list.addEventListener('dragover',function(e){
			if(!dragging){return;}
			e.preventDefault();
			var row=e.target.closest?e.target.closest('.bang-f-widget'):null;
			if(!row||row===dragging){return;}
			var box=row.getBoundingClientRect();
			if((e.clientY-box.top)<box.height/2){list.insertBefore(dragging,row);}
			else{list.insertBefore(dragging,row.nextSibling);}
		});
		list.addEventListener('drop',function(e){if(dragging){e.preventDefault();}});
		list.addEventListener('dragend',function(){
			if(dragging){dragging.classList.remove('is-dragging');dragging=null;sync();}
		});
		list.addEventListener('change',sync);
		var form=data.form||data.closest('form');
		if(form){form.addEventListener('submit',sync);}
	});
})();
/* AI 摘要：批量为旧文章补生成，走 bang/v1/ai-excerpt/batch，管理员权限 */
(function(){
	var btn=document.querySelector('[data-bang-ai-batch]');
	if(!btn){return;}
	var status=document.querySelector('[data-bang-ai-status]');
	var restUrl=<?php echo wp_json_encode( rest_url( 'bang/v1/ai-excerpt/batch' ) ); ?>;
	var nonce=<?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>;
	var running=false;
	function setStatus(t){if(status){status.textContent=t;}}
	function step(total){
		return fetch(restUrl,{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':nonce},body:JSON.stringify({limit:5})})
			.then(function(r){return r.json();})
			.then(function(res){
				if(!res||!res.ok){setStatus((res&&res.msg)||'生成失败，请检查接口配置');return;}
				total+=res.generated||0;
				setStatus('已生成 '+total+' 篇'+(res.failed?'（失败 '+res.failed+' 篇）':''));
				if(res.done){setStatus('完成，共生成 '+total+' 篇摘要');return;}
				return step(total);
			})
			.catch(function(){setStatus('请求失败，请重试');});
	}
	btn.addEventListener('click',function(){
		if(running){return;}
		running=true;btn.disabled=true;setStatus('开始生成…');
		step(0).then(function(){running=false;btn.disabled=false;});
	});
})();
/* 发布器：生成 / 重置 Token，明文只显示一次 */
(function(){
	var btn=document.querySelector('[data-bang-pub-token]');
	if(!btn){return;}
	var box=document.querySelector('[data-bang-pub-secret]');
	var out=document.querySelector('[data-bang-pub-value]');
	var status=document.querySelector('[data-bang-pub-status]');
	var tokenUrl=<?php echo wp_json_encode( rest_url( 'bang/v1/publish/token' ) ); ?>;
	var nonce=<?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>;
	btn.addEventListener('click',function(){
		btn.disabled=true;if(status){status.textContent='生成中…';}
		fetch(tokenUrl,{method:'POST',headers:{'X-WP-Nonce':nonce}})
			.then(function(r){return r.json();})
			.then(function(res){
				btn.disabled=false;
				if(!res||!res.ok){if(status){status.textContent=(res&&res.msg)||'生成失败';}return;}
				if(box){box.hidden=false;}
				if(out){out.value=res.token||'';out.focus();out.select();}
				if(status){status.textContent='已生成，请立即复制保存';}
				btn.textContent='重置 Token';
			})
			.catch(function(){btn.disabled=false;if(status){status.textContent='请求失败，请重试';}});
	});
})();
/* 设置备份：导入 JSON 走 admin-ajax，避免与主设置表单嵌套 */
(function(){
	var btn=document.querySelector('[data-bang-backup-import]');
	if(!btn){return;}
	var file=document.getElementById('bang-backup-file');
	var status=document.querySelector('[data-bang-backup-status]');
	var ajaxUrl=<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
	var nonce=<?php echo wp_json_encode( wp_create_nonce( 'bang_backup_import' ) ); ?>;
	function setStatus(t){if(status){status.textContent=t;}}
	btn.addEventListener('click',function(){
		if(!file||!file.files||!file.files.length){setStatus('请先选择备份文件');return;}
		btn.disabled=true;setStatus('导入中…');
		var fd=new FormData();
		fd.append('action','bang_backup_import');
		fd.append('_ajax_nonce',nonce);
		fd.append('file',file.files[0]);
		fetch(ajaxUrl,{method:'POST',body:fd,credentials:'same-origin'})
			.then(function(r){return r.json();})
			.then(function(res){
				btn.disabled=false;
				if(res&&res.success){setStatus('导入成功，即将刷新');setTimeout(function(){location.reload();},600);}
				else{setStatus((res&&res.data&&res.data.msg)||'导入失败，请检查文件格式');}
			})
			.catch(function(){btn.disabled=false;setStatus('请求失败，请重试');});
	});
})();
</script>
	<?php
}
