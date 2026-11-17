<?php
/**
 * 站点信息页：文章归档 / 关于 / 联系 / 友情链接
 *
 * 页面由主题自动创建（首次访问或后台任意页面时补齐），正文可在后台编辑；
 * 模板按 slug 自动匹配 page-{slug}.php，无需手动指定模板。
 *
 * @package Naigou
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 页面清单。改 slug / 标题只改这里，侧栏与模板都读它。
 */
function pk_site_page_defs() {
	return array(
		'archive'    => array(
			'title' => __( '文章归档', 'pianke' ),
			'text'  => '',
		),
		'categories' => array(
			'title' => __( '分类', 'pianke' ),
			'text'  => '',
		),
		'theme'    => array(
			'title' => __( '主题介绍', 'pianke' ),
			'text'  => '',
		),
		'about'    => array(
			'title' => __( '关于', 'pianke' ),
			'text'  => "Bang，一个用 ink 写作的博客——落笔有声，留白有度。\n\n这里收录短动态、图集与长文章：点开任意卡片即可在当前页展开阅读，读完关掉会缩回原位；手机上左右滑动就能翻到上一篇或下一篇。",
		),
		'contact'  => array(
			'title' => __( '联系', 'pianke' ),
			'text'  => '',
		),
		'privacy'  => array(
			'title' => __( '隐私政策', 'pianke' ),
			'text'  => "我们重视你的隐私。本政策说明本站如何收集、使用和保护你在使用本站过程中提供的信息。\n\n一、我们收集的信息\n你注册时提供的用户名、邮箱与个人资料；你发表的文章、评论、说说与友链申请；以及为保障安全所必需的技术信息（如 IP 地址、浏览器标识与操作时间）。\n\n二、信息的使用\n上述信息仅用于提供和改进本站服务、完成账号与互动功能、防范垃圾与滥用行为。我们不会将你的个人信息出售给第三方。\n\n三、Cookie 与本地存储\n本站使用必要的 Cookie 维持登录状态与偏好设置（如深浅色）。辅助功能若使用本地存储，仅保存在你的设备上，你可以随时清除。\n\n四、信息共享\n除法律法规要求或为保护你的权益所必需的情形外，我们不会向第三方披露你的个人信息。评论与公开内容会按你发布时的可见范围展示。\n\n五、数据安全\n我们采用合理的技术与管理措施保护信息安全，但互联网传输无法保证绝对安全，请妥善保管你的账号凭据。\n\n六、你的权利\n你可以随时在账号设置中修改个人资料，也可以联系我们申请导出或删除你的数据。\n\n七、政策更新\n本政策可能随功能调整而更新，更新后会在本页公布。继续使用本站即视为你接受更新后的政策。\n\n如有疑问，请通过「联系」页与我们取得联系。",
		),
		'terms'    => array(
			'title' => __( '用户协议', 'pianke' ),
			'text'  => "欢迎使用本站。使用前请仔细阅读以下条款，继续使用即表示你同意遵守。\n\n一、服务说明\n本站提供内容浏览、账号注册、文章与评论发布、说说、友链申请等功能。我们可能随时调整或停止部分功能，无需另行通知。\n\n二、账号规则\n请使用真实、合法的信息注册，并妥善保管账号凭据。你的账号不得转让、出租或供他人使用。发现异常时请及时联系我们。\n\n三、用户内容\n你发布的内容应遵守法律法规，不得包含违法、侵权、骚扰、垃圾广告或有害信息。你保留内容的著作权，同时授予本站为展示与传播所必需的合理使用许可。我们有权对违规内容进行修改、隐藏或删除。\n\n四、知识产权\n本站的程序、设计、文案与标识受法律保护。未经许可，不得复制、篡改或用于商业用途。第三方内容的权利归原作者所有。\n\n五、禁止行为\n不得利用本站从事攻击、爬虫滥用、批量注册、刷量、传播恶意代码或干扰他人正常使用的行为。\n\n六、免责声明\n本站内容由用户自行发布，不代表本站立场。因不可抗力、网络故障或第三方服务导致的服务中断，我们会在合理范围内协助处理，但不承担由此产生的间接损失。\n\n七、条款变更\n条款更新后会在本页公布，继续使用即视为接受。\n\n如有疑问，请通过「联系」页与我们取得联系。",
		),
		'links'    => array(
			'title' => __( '友情链接', 'pianke' ),
			'text'  => '',
		),
		'blocks'   => array(
			'title' => __( '区块展厅', 'pianke' ),
			'text'  => '',
		),
		'html-tag' => array(
			'title' => __( '排版标签', 'pianke' ),
			'text'  => '',
		),
		'help'     => array(
			'title' => __( '帮助中心', 'pianke' ),
			'text'  => '',
		),
		'settings' => array(
			'title' => __( '账号设置', 'pianke' ),
			'text'  => '',
		),
	);
}

/**
 * 页面 ID（本请求内缓存，避免侧栏一次渲染查五次）。
 */
function pk_site_page_id( $slug, $flush = false ) {
	static $cache = array();
	if ( $flush ) {
		$cache = array();
	}
	if ( ! array_key_exists( $slug, $cache ) ) {
		$page           = get_page_by_path( $slug, OBJECT, 'page' );
		$cache[ $slug ] = $page ? (int) $page->ID : 0;
	}
	return $cache[ $slug ];
}

/**
 * 当前请求是不是这些信息页。
 *
 * 信息页是普通页面，但视觉上按「列表页」处理：不套阅读层的单页头部、不隐藏页脚。
 */
function pk_is_site_page() {
	return is_page( array_keys( pk_site_page_defs() ) );
}

/**
 * 页面地址。页面还没建好时退回固定路径，避免侧栏输出空 href。
 */
function pk_site_page_url( $slug ) {
	$id = pk_site_page_id( $slug );
	return $id ? get_permalink( $id ) : home_url( '/' . $slug . '/' );
}

/**
 * 补齐缺失页面。
 *
 * 用版本号做闸门：只在版本变化时查一次数据库，之后每个请求只读一个 option。
 * 不校验权限——主题换到新站（含演示站）时前台也可能先被打开，页面必须存在。
 */
function pk_ensure_site_pages() {
	$ver = '9';
	if ( $ver === get_option( 'pk_site_pages_v' ) ) {
		return;
	}
	foreach ( pk_site_page_defs() as $slug => $def ) {
		if ( pk_site_page_id( $slug ) ) {
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_title'   => $def['title'],
				'post_name'    => $slug,
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => isset( $def['text'] ) ? $def['text'] : '',
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			/* 让缓存失效：同请求内后续调用（header 渲染）拿到真实 ID */
			pk_site_page_id( $slug, true );
			wp_cache_delete( $id, 'posts' );
		}
	}

	/* 已下线的信息页（0.11.28 去掉「创作者们」）：移入回收站而不是硬删，
	   否则页面会失去 page-{slug}.php 模板，被当成普通页面套上阅读层头部。
	   回收站里可随时恢复：后台 → 页面 → 回收站。 */
	foreach ( array( 'creators' ) as $slug ) {
		$id = pk_site_page_id( $slug );
		if ( $id ) {
			wp_trash_post( $id );
			pk_site_page_id( $slug, true );
		}
	}

	update_option( 'pk_site_pages_v', $ver );
}
add_action( 'init', 'pk_ensure_site_pages', 20 );

/**
 * 站点统计：关于页 / 归档页共用。
 */
function pk_site_stats() {
	static $stats = null;
	if ( null !== $stats ) {
		return $stats;
	}
	$posts   = wp_count_posts( 'post' );
	$cats    = wp_count_terms( array( 'taxonomy' => 'category', 'hide_empty' => true ) );
	$tags    = wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => true ) );
	$comm    = wp_count_comments();
	$first   = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'numberposts'    => 1,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	$stats = array(
		'posts'    => isset( $posts->publish ) ? (int) $posts->publish : 0,
		'cats'     => is_wp_error( $cats ) ? 0 : (int) $cats,
		'tags'     => is_wp_error( $tags ) ? 0 : (int) $tags,
		'comments' => isset( $comm->approved ) ? (int) $comm->approved : 0,
		'since'    => $first ? (int) get_the_date( 'Y', $first[0] ) : (int) gmdate( 'Y' ),
	);
	return $stats;
}

/**
 * 联系邮箱：优先使用设置里的「联系邮箱」，否则退回管理员邮箱。
 */
function pk_contact_email() {
	$mail = trim( (string) pk_opt( 'contact_email' ) );
	if ( ! is_email( $mail ) ) {
		$mail = (string) get_option( 'admin_email' );
	}
	return $mail;
}

/**
 * 打开 WordPress 核心链接管理器（外观 → 链接）。
 *
 * 主题不再自带友链文本框，数据一律走核心 wp_links 表；核心默认不注册
 * 「链接」菜单，这里在首次加载时静默补上开关，后台与前台都不需要手动设置。
 */
function pk_enable_link_manager() {
	if ( ! get_option( 'link_manager_enabled' ) ) {
		/* WP 默认已存 '0'，add_option 不会覆盖已存在的值，必须用 update_option 才会显示后台「链接」菜单 */
		update_option( 'link_manager_enabled', 1 );
	}
}
add_action( 'init', 'pk_enable_link_manager', 5 );

/**
 * 友情链接：读核心链接管理器（wp_links），映射成卡片流用的数组。
 *
 * 字段对应：link_name → 名称、link_url → 地址、link_description → 描述、
 * link_category 分类名 → 分组、link_visible 为 N / link_offline=1 → 暂不可达。
 * 分类取第一条命中分类的名称；无分类记为空串（前台显示为「未分组」）。
 *
 * @return array
 */
function pk_friend_links() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	/* WP 7.1+ 的 get_links() 是老式模板标签：直接 echo HTML 且返回 null，
	   必须改用 get_bookmarks() 才能拿到对象数组。 */
	if ( ! function_exists( 'get_bookmarks' ) ) {
		return $cache;
	}

	/* 0.13.27 起只认核心链接；老站点若还有文本框数据，只读迁移一次进核心，
	   之后不再回读，保证「只有一份数据源」。 */
	pk_migrate_legacy_friend_links();

	$links = get_bookmarks(
		array(
			'limit'          => -1,
			'orderby'        => 'name',
			'order'          => 'ASC',
			/* 核心里未勾「可见」的也读出来，前台按 offline 显示「暂不可达」 */
			'hide_invisible' => 0,
		)
	);
	if ( ! is_array( $links ) ) {
		return $cache;
	}

	foreach ( $links as $link ) {
		$url = isset( $link->link_url ) ? trim( (string) $link->link_url ) : '';
		if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
			continue;
		}
		$name = isset( $link->link_name ) ? trim( (string) $link->link_name ) : '';
		$cat  = '';
		/* get_bookmarks() 返回的对象不带 link_category，分类要单独取（前台无 wp_get_link_cats） */
		$cat_ids = wp_get_object_terms(
			$link->link_id,
			'link_category',
			array( 'fields' => 'ids' )
		);
		if ( is_wp_error( $cat_ids ) ) {
			$cat_ids = array();
		}
		$cat_ids = array_filter( array_map( 'absint', (array) $cat_ids ) );
		if ( $cat_ids ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'link_category',
					'include'    => $cat_ids,
					'hide_empty' => false,
				)
			);
			if ( ! is_wp_error( $terms ) && $terms ) {
				$cat = (string) $terms[0]->name;
			}
		}
		$cache[] = array(
			'name'    => '' !== $name ? $name : $url,
			'url'     => $url,
			'desc'    => isset( $link->link_description ) ? trim( (string) $link->link_description ) : '',
			'group'   => $cat,
			'offline' => ( isset( $link->link_visible ) && 'Y' !== $link->link_visible ) ? 1 : 0,
		);
	}
	return $cache;
}

/**
 * 老的「友情链接」文本框一次性迁移到核心链接管理器。
 *
 * 迁移过的数据记在 pk_links_migrated option 里，之后原封不动保留（不删后台旧数据），
 * 前台只读核心，不会再出现两份数据打架。
 */
function pk_migrate_legacy_friend_links() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	if ( get_option( 'pk_links_migrated' ) || ! function_exists( 'wp_insert_link' ) ) {
		return;
	}
	$raw = trim( (string) pk_opt( 'friend_links' ) );
	if ( '' === $raw ) {
		update_option( 'pk_links_migrated', 1 );
		return;
	}
	$lines = preg_split( '/\r\n|\r|\n/', $raw );
	$lines = is_array( $lines ) ? $lines : array();
	foreach ( $lines as $line ) {
		$line = trim( (string) $line );
		if ( '' === $line ) {
			continue;
		}
		$parts   = array_map( 'trim', preg_split( '/\s*[|｜]\s*/u', $line ) );
		$url     = isset( $parts[1] ) ? $parts[1] : '';
		$name    = isset( $parts[0] ) && '' !== $parts[0] ? $parts[0] : '';
		$desc    = isset( $parts[2] ) ? $parts[2] : '';
		$group   = isset( $parts[3] ) ? $parts[3] : '';
		$offline = isset( $parts[4] ) && preg_match( '/^(1|off|offline|down|bad)$/i', $parts[4] );
		if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
			continue;
		}
		$cat_id = 0;
		if ( '' !== $group ) {
			$term   = get_term_by( 'name', $group, 'link_category' );
			$cat_id = $term ? (int) $term->term_id : 0;
			if ( ! $cat_id ) {
				$added = wp_insert_term( $group, 'link_category' );
				if ( ! is_wp_error( $added ) ) {
					$cat_id = (int) $added['term_id'];
				}
			}
		}
		$args = array(
			'link_name'        => '' !== $name ? $name : $url,
			'link_url'         => $url,
			'link_description' => $desc,
			'link_visible'     => $offline ? 'N' : 'Y',
		);
		if ( $cat_id ) {
			$args['link_category'] = $cat_id;
		}
		wp_insert_link( $args );
	}
	update_option( 'pk_links_migrated', 1 );
}

/**
 * 友情链接分类清单：读核心 link_category 分类法，按名称排序返回。
 * 前台筛选按钮、分组顺序与申请表单下拉共用这一份，避免三处各自解析。
 *
 * @return string[]
 */
function pk_link_groups() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	if ( ! function_exists( 'get_terms' ) ) {
		return $cache;
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'link_category',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);
	if ( is_wp_error( $terms ) || ! $terms ) {
		return $cache;
	}
	foreach ( $terms as $term ) {
		$name = trim( (string) $term->name );
		if ( '' !== $name ) {
			$cache[] = $name;
		}
	}
	return $cache;
}

/**
 * 友情链接：卡片上显示的域名（去掉 www. 与协议，保留路径前的主机名）。
 *
 * @param string $url 站点地址。
 * @return string
 */
function pk_link_host( $url ) {
	$host = (string) wp_parse_url( $url, PHP_URL_HOST );
	if ( '' === $host ) {
		return (string) $url;
	}
	return preg_replace( '/^www\./i', '', $host );
}

/**
 * 友情链接：站点名首字，作为卡片头像的文字回退（不走 Gravatar、不取远程 favicon）。
 *
 * @param string $name 站点名。
 * @return string
 */
function pk_link_initial( $name ) {
	$name = trim( (string) $name );
	if ( '' === $name ) {
		return '?';
	}
	if ( function_exists( 'mb_substr' ) ) {
		return mb_strtoupper( mb_substr( $name, 0, 1, 'UTF-8' ), 'UTF-8' );
	}
	return strtoupper( substr( $name, 0, 1 ) );
}

/**
 * 页面外壳开场：标题 + 一句说明。各信息页模板共用。
 */
function pk_page_open( $title, $sub = '', $extra_class = '' ) {
	$class_attr = $extra_class ? ' ' . esc_attr( trim( (string) $extra_class ) ) : '';
	echo '<div id="swup-wrapper">';
	echo '<main class="wrap pk-page-wrap pk-page-wide' . $class_attr . '" id="main">';
	echo '<div class="pk-page-inner">';
	echo '<header class="pk-page-head"><h1 class="pk-page-title">' . esc_html( $title ) . '</h1>';
	if ( $sub ) {
		echo '<p class="pk-page-sub">' . esc_html( $sub ) . '</p>';
	}
	echo '</header>';
}

/**
 * 页面外壳收尾。
 */
function pk_page_close() {
	echo '</div></main></div>';
}
