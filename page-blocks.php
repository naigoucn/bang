<?php
/**
 * Template Name: 区块展厅
 * 模板说明：slug 为 blocks 的页面会自动套用本模板，也可在后台手动选择。
 *
 * 把 Bang 的 11 个正文区块集中展示，每个都给出「区块写法 + 短代码写法」两种入口，
 * 两种写法复用同一个 render_callback，输出完全一致的 HTML。
 * 本页由 bang_ensure_site_pages() 自动创建（slug: blocks），也可在后台手动套用模板。
 *
 * @package Bang
 */

get_header();

$bang_imgs = bang_kit_sample_images( 6 );
$bang_grid = $bang_imgs ? bang_kit_wall_html( $bang_imgs ) : '<p class="bang-kit-empty">媒体库还没有图片，上传几张后这里会出现宫格画廊。</p>';
$bang_one  = $bang_imgs ? bang_kit_wall_html( array( $bang_imgs[0] ), 'single' ) : '';

/* 文章卡片：取站点最新一篇文章做真实样本，空站时给提示 */
$bang_sample_posts  = get_posts( array( 'numberposts' => 1, 'post_status' => 'publish', 'fields' => 'ids' ) );
$bang_sample_pid    = $bang_sample_posts ? (int) $bang_sample_posts[0] : 0;
$bang_postcard_demo = $bang_sample_pid
	? bang_kit_postcard_html( (string) $bang_sample_pid, '推荐阅读', '封面、标题、摘要与日期都从文章自动读取，点卡片进文章。' )
	: '<p class="bang-kit-empty">还没有文章，发布一篇后这里会出现文章卡片。</p>';

/* 阅读解锁：五种方式的锁定卡片样本（前台未解锁时看到的样子） */
$bang_unlock_demo = $bang_sample_pid
	? '<div class="bang-kit-stack">'
		. bang_unlock_card_html( array( 'method' => 'login', 'post_id' => $bang_sample_pid, 'compact' => true, 'title' => '登录可见', 'desc' => '登录后即可阅读这段内容' ) )
		. bang_unlock_card_html( array( 'method' => 'like', 'post_id' => $bang_sample_pid, 'compact' => true, 'title' => '点赞可见', 'desc' => '给文章点赞后解锁' ) )
		. bang_unlock_card_html( array( 'method' => 'comment', 'post_id' => $bang_sample_pid, 'compact' => true, 'title' => '回复可见', 'desc' => '发表评论后解锁' ) )
		. bang_unlock_card_html( array( 'method' => 'password', 'post_id' => $bang_sample_pid, 'compact' => true, 'title' => '密码可见', 'desc' => '输入解锁密码后阅读' ) )
		. bang_unlock_card_html( array( 'method' => 'combo', 'post_id' => $bang_sample_pid, 'compact' => true, 'title' => '点赞+评论可见', 'desc' => '点赞并发表评论，两步都完成才解锁' ) )
		. '</div>'
	: '<p class="bang-kit-empty">还没有文章，发布一篇后这里会出现五种锁定卡片。</p>';

/* 下载区块：展厅里用 locked=0 直接展示解锁后样貌，作者实际用时按方式锁定 */
$bang_download_demo = bang_kit_download_html(
	array(
		'title'   => '资源下载',
		'desc'    => '解锁后显示下面的下载地址，网盘可带提取码',
		'locked'  => false,
		'post_id' => $bang_sample_pid,
		'items'   => "百度网盘|https://pan.baidu.com/s/1example|abcd\n夸克网盘|https://pan.quark.cn/s/example|1234\n直链下载|https://example.com/bang-resource.zip",
	)
);

/* 提示框：四种状态用简码真跑一遍，展厅同时是简码链路的活体样本 */
$bang_tips = '';
foreach ( array(
	'info'    => array( '这是一条提示', '最轻的一档：补充说明、延伸阅读、默认值。' ),
	'warning' => array( '动手前先看一眼', '这个操作会覆盖已有设置，建议先备份。' ),
	'success' => array( '已经写好了', '区块会自动适配深浅色，不用额外写样式。' ),
	'danger'  => array( '别这样写', '正文里贴 script 会被过滤，代码块请用简码包起来。' ),
) as $bang_type => $bang_tip ) {
	$bang_tips .= do_shortcode( '[tip type="' . $bang_type . '" title="' . esc_attr( $bang_tip[0] ) . '"]<p>' . esc_html( $bang_tip[1] ) . '</p>[/tip]' );
}

/* 简码直写：走 the_content + kses 的真实渲染，跟文章正文是同一条链路 */
$bang_probe = "[tip type=\"info\" title=\"简码实渲染\"]这段走 the_content 过滤，和文章正文同一条渲染链路。[/tip]\n"
	. "[fold title=\"简码折叠\" open=\"1\"]折叠里的文字，带 <code>open=\"1\"</code>。[/fold]\n"
	. "[code lang=\"js\"]const bang = 'ink';[/code]\n"
	. "[quote source=\"山里的傍晚\"]好看的其实不是太阳，是它走之后那几分钟。[/quote]\n"
	. "[meta items=\"地点=山脚;天气=多云\"][/meta]\n"
	. "[timeline items=\"2024-03|上线|第一版发布\"][/timeline]\n"
	. "[unlock method=\"login\" title=\"登录可见示例\"]解锁后才可读的内容[/unlock]\n"
	. "[postcard id=\"" . (int) $bang_sample_pid . "\"]\n"
	. "[download locked=\"0\" title=\"资源下载\"]百度网盘|https://pan.baidu.com/s/1example|abcd[/download]\n";
if ( $bang_imgs ) {
	$bang_probe .= "[wall]\n" . $bang_imgs[0]['src'] . "\n"
		. ( isset( $bang_imgs[1] ) ? $bang_imgs[1]['src'] : $bang_imgs[0]['src'] ) . "\n[/wall]";
}
$bang_probe_html = wp_kses_post( apply_filters( 'the_content', $bang_probe ) );

$bang_demos = array(
	array(
		'no'        => '01',
		'name'      => '提示框',
		'tag'       => 'bang/tip',
		'desc'      => '四种状态用不同颜色区分，卡片形状一致；支持自定义标题，正文可放链接与行内代码。',
		'html'      => '<div class="bang-kit-stack">' . $bang_tips . '</div>',
		'block'     => '<!-- wp:bang/tip {"type":"info","title":"这是一条提示","content":"补充说明写在这里"} /-->' . "\n"
			. '<!-- wp:bang/tip {"type":"warning","title":"动手前先看一眼","content":"建议先备份"} /-->',
		'shortcode' => '[tip type="info" title="这是一条提示"]补充说明写在这里[/tip]' . "\n"
			. '[tip type="warning" title="动手前先看一眼"]建议先备份[/tip]' . "\n"
			. '[tip type="success"]已经写好了[/tip]' . "\n"
			. '[tip type="danger"]别这样写[/tip]',
	),
	array(
		'no'        => '02',
		'name'      => '代码块',
		'tag'       => 'bang/code',
		'desc'      => '顶部一行语言标签，右上角一键复制；按语言做语法高亮，语言留空时按开头特征自动识别。核心 code 区块加 lang-php 类名也会套同一套外观。',
		'html'      => bang_kit_code_html( 'php', "<?php\n// functions.php\necho '落笔有声，留白有度。';", 'functions.php' )
			. bang_kit_code_html( '', "const bang = { ink: '#000', paper: '#fff' };\nconsole.log(bang.ink);", 'auto.js（自动识别）' ),
		'block'     => '<!-- wp:bang/code {"lang":"php","file":"functions.php","content":"<?php echo \'Bang\';"} /-->',
		'shortcode' => '[code lang="php" file="functions.php"]<?php echo "Bang";[/code]' . "\n"
			. '核心 code 区块：右侧 → 高级 → 额外的 CSS 类填 lang-php',
	),
	array(
		'no'        => '03',
		'name'      => '折叠内容',
		'tag'       => 'bang/fold',
		'desc'      => '原生 details / summary，键盘、读屏、无脚本环境都能用；可设置默认展开，展开时箭头翻转、内容淡入。',
		'html'      => bang_kit_fold_html( '点击展开：这段写了什么', '<p>被折叠的内容默认收起，点标题即可展开。</p>', false )
			. bang_kit_fold_html( '默认展开：一段说明', '<p>这个块带 <code>open="1"</code>，落地就是展开的。</p>', true ),
		'block'     => '<!-- wp:bang/fold {"title":"点击展开","content":"被折叠的内容","open":false} /-->' . "\n"
			. '<!-- wp:bang/fold {"title":"默认展开","content":"内容","open":true} /-->',
		'shortcode' => '[fold title="点击展开"]被折叠的内容[/fold]' . "\n" . '[fold title="默认展开" open="1"]内容[/fold]',
	),
	array(
		'no'        => '04',
		'name'      => '图集',
		'tag'       => 'bang/gallery',
		'desc'      => '一组图片：主图 + 缩略条，或拼贴宫格，点任意一张进灯箱。简码 ids 用媒体库 ID，也能直接贴图片地址（旧 [wall] 写法继续兼容）。',
		'html'      => $bang_grid . ( $bang_one ? '<p class="bang-kit-note">单图模式</p>' . $bang_one : '' ),
		'block'     => '<!-- wp:bang/gallery {"layout":"grid","caption":"路上的光","images":[{"id":12},{"id":13},{"id":14}]} /-->',
		'shortcode' => '[gallery layout="grid" caption="路上的光" ids="12,13,14"]' . "\n"
			. '[gallery caption="路上的光"]' . "\n" . '/uploads/one.jpg | 第一张' . "\n" . '/uploads/two.jpg | 第二张' . "\n" . '[/gallery]' . "\n"
			. '旧写法 [wall]…[/wall] 仍然可用',
	),
	array(
		'no'        => '05',
		'name'      => '金句卡',
		'tag'       => 'bang/quote',
		'desc'      => '一段引文配出处，正文支持安全 HTML；出处单独一行小字，跟着深浅色自动反色。',
		'html'      => bang_render_quote_block(
			array(
				'text'   => '好看的其实不是太阳，是它走之后那几分钟。',
				'source' => '山里的傍晚',
			)
		),
		'block'     => '<!-- wp:bang/quote {"text":"好看的其实不是太阳，是它走之后那几分钟。","source":"山里的傍晚"} /-->',
		'shortcode' => '[quote source="山里的傍晚"]好看的其实不是太阳，是它走之后那几分钟。[/quote]',
	),
	array(
		'no'        => '06',
		'name'      => '链接卡',
		'tag'       => 'bang/linkcard',
		'desc'      => '一张站点卡片，外链统一走 ?bang_go= 中转确认页，在新标签打开；只接受 http / https。',
		'html'      => bang_render_linkcard_block(
			array(
				'url'         => 'https://example.com/',
				'title'       => '一个值得留下的站点',
				'description' => '写在卡片上的一句话说明。',
			)
		),
		'block'     => '<!-- wp:bang/linkcard {"url":"https://example.com/","title":"一个值得留下的站点","description":"写在卡片上的一句话说明。"} /-->',
		'shortcode' => '[linkcard url="https://example.com/" title="一个值得留下的站点" description="写在卡片上的一句话说明。"]' . "\n"
			. '不写 title 时也可以用包裹内容：[linkcard url="https://example.com/"]站点标题[/linkcard]',
	),
	array(
		'no'        => '07',
		'name'      => '元信息条',
		'tag'       => 'bang/meta',
		'desc'      => '一组「名称 / 内容」用圆点排成一行，适合时间、地点、天气、设备等正文头信息。',
		'html'      => bang_render_meta_block(
			array(
				'items' => array(
					array( 'label' => '地点', 'value' => '山脚' ),
					array( 'label' => '天气', 'value' => '多云' ),
					array( 'label' => '时间', 'value' => '傍晚' ),
				),
			)
		),
		'block'     => '<!-- wp:bang/meta {"items":[{"label":"地点","value":"山脚"},{"label":"天气","value":"多云"}]} /-->',
		'shortcode' => '[meta items="地点=山脚;天气=多云;时间=傍晚"][/meta]',
	),
	array(
		'no'        => '08',
		'name'      => '时间轴',
		'tag'       => 'bang/timeline',
		'desc'      => '逐条填「日期 / 标题 / 说明」三个框，点「添加一条」接着写，不用记任何格式；竖线加圆点的时间线自动生成。适合版本记录、大事记与写作历程。',
		'html'      => bang_kit_timeline_html( "2024-03|上线|第一版发布，白底黑字定下基调\n2024-06|改版|换成极简风，去掉多余阴影\n2025-01|开源|代码全部公开，欢迎共建" ),
		'block'     => '<!-- wp:bang/timeline {"text":"2024-03|上线|第一版发布\\n2024-06|改版|换成极简风"} /-->',
		'shortcode' => '[timeline items="2024-03|上线|第一版发布;2024-06|改版|换成极简风"]' . "\n"
			. '多行写法：[timeline]' . "\n" . '2024-03|上线|第一版发布' . "\n" . '[/timeline]',
	),
	array(
		'no'        => '09',
		'name'      => '文章卡片',
		'tag'       => 'bang/postcard',
		'desc'      => '正文里插一张文章卡：区块里下拉选文章（可搜索），短代码填 ID 或链接；封面、标题、摘要与日期自动读取，自定义字段可覆盖。',
		'html'      => $bang_postcard_demo,
		'block'     => '<!-- wp:bang/postcard {"target":"' . (int) $bang_sample_pid . '","title":"推荐阅读","desc":"封面、标题、摘要自动读取"} /-->',
		'shortcode' => '[postcard id="' . (int) $bang_sample_pid . '"]' . "\n"
			. '[postcard id="' . (int) $bang_sample_pid . '" title="推荐阅读" desc="自定义摘要"]' . "\n"
			. '[postcard url="https://example.com/"]' . "\n"
			. '不写 id / url 时，包裹内容当作目标',
	),
	/* 阅读解锁：五种方式（登录 / 点赞 / 回复 / 密码 / 点赞+评论叠加），入群已屏蔽 */
	array(
		'no'        => '10',
		'name'      => '阅读解锁',
		'tag'       => 'bang/unlock',
		'desc'      => '把一段内容锁起来：作者五选一（登录可见 / 点赞可见 / 回复可见 / 密码可见 / 点赞+评论可见），读者只能用选定的方式解锁；叠加解锁需点赞与评论两步都完成，密码不参与叠加。未解锁时服务端不输出正文。',
		'html'      => $bang_unlock_demo,
		'block'     => '<!-- wp:bang/unlock {"method":"login","teaser":"前面的内容所有人都能看到……","content":"解锁后才可读的内容"} /-->' . "\n"
			. '<!-- wp:bang/unlock {"method":"password","password":"本段专属密码","content":"输入密码后可读"} /-->' . "\n"
			. '<!-- wp:bang/unlock {"method":"combo","content":"点赞并评论后可读"} /-->',
		'shortcode' => '[unlock method="login"]登录后才可读的内容[/unlock]' . "\n"
			. '[unlock method="like" title="点赞可见"]点赞后可读的内容[/unlock]' . "\n"
			. '[unlock method="comment" teaser="引言"]回复后才可读的内容[/unlock]' . "\n"
			. '[unlock method="password" password="本段专属密码"]输入密码后可读[/unlock]' . "\n"
			. '[unlock method="combo"]点赞+评论后可读[/unlock]',
	),
	array(
		'no'        => '11',
		'name'      => '下载',
		'tag'       => 'bang/download',
		'desc'      => '网盘 / 直链下载列表，每行填「名称|链接|提取码」，网盘可带提取码一键复制。解锁方式与阅读解锁相同五选一（含点赞+评论叠加），未解锁时服务端不输出任何下载地址；这是个演示，这里解锁后样貌直接展示。',
		'html'      => $bang_download_demo,
		'block'     => '<!-- wp:bang/download {"title":"资源下载","method":"login","items":"百度网盘|https://pan.baidu.com/s/1example|abcd\\n直链下载|https://example.com/file.zip"} /-->',
		'shortcode' => '[download method="login" title="资源下载"]' . "\n"
			. '百度网盘|https://pan.baidu.com/s/1example|abcd' . "\n"
			. '直链下载|https://example.com/file.zip' . "\n"
			. '[/download]' . "\n"
			. '[download method="combo" title="点赞+评论后下载"]…[/download]',
	),
	array(
		'no'        => '12',
		'name'      => '站点级组件',
		'tag'       => '',
		'desc'      => '首页滚动公告、公告弹窗、订阅卡、幻灯片各自独立开关，互不打架；通知条支持点 × / 上滑 / 双击关闭，本次会话不再出现。',
		'html'      => '<ul class="bang-kit-list">'
			. '<li><span>首页公告栏</span><span>横向无缝滚动 · hover 暂停</span></li>'
			. '<li><span>公告弹窗</span><span>内容 hash 变更才重新弹</span></li>'
			. '<li><span>命令面板</span><span>Ctrl / ⌘ + K · 可选浏览器内语义搜索</span></li>'
			. '</ul>',
		'shortcode' => '后台 → Bang 设置 → 对应分栏开启',
	),
	array(
		'no'        => '13',
		'name'      => '页面模板',
		'tag'       => '',
		'desc'      => '文章归档、关于、联系、友情链接、说说、区块展厅都由主题自动建页，模板按 slug 匹配，无需手动指定。',
		'html'      => '<div class="bang-kit-links">'
			. '<a href="' . esc_url( bang_site_page_url( 'links' ) ) . '">友情链接' . bang_kit_icon( 'arrow', 14 ) . '</a>'
			. '<a href="' . esc_url( get_post_type_archive_link( 'bang_say' ) ) . '">说说' . bang_kit_icon( 'arrow', 14 ) . '</a>'
			. '<a href="' . esc_url( bang_site_page_url( 'archive' ) ) . '">文章归档' . bang_kit_icon( 'arrow', 14 ) . '</a>'
			. '</div>',
		'shortcode' => '后台 → 页面 → 新建页面，模板选「区块展厅」',
	),
	array(
		'no'        => '14',
		'name'      => '渲染链路',
		'tag'       => '',
		'desc'      => '下面这一整块是简码经过 the_content 与 kses 过滤后的真实输出，和文章正文走的是同一条渲染链路。',
		'html'      => '<div class="bang-kit-probe">' . $bang_probe_html . '</div>',
		'shortcode' => '[tip] / [fold] / [code] / [wall] / [quote] / [linkcard] / [meta] / [timeline] / [postcard] / [unlock] / [download]' . "\n"
			. '全部由 do_shortcode 挂到 the_content，与区块共用 render_callback',
	),
);

bang_page_open( '区块展厅', '正文里能用的东西，都摆在这里了。11 个区块全部支持「区块 + 短代码」双写法，同一种输出。' );
?>
<div class="bang-kit">
	<?php foreach ( $bang_demos as $bang_demo ) : ?>
	<section class="bang-kit-demo">
		<header class="bang-kit-head">
			<span class="bang-kit-no"><?php echo esc_html( $bang_demo['no'] ); ?></span>
			<div class="bang-kit-titles">
				<h2><?php echo esc_html( $bang_demo['name'] ); ?><?php if ( ! empty( $bang_demo['tag'] ) ) : ?><span class="bang-kit-tag"><?php echo esc_html( $bang_demo['tag'] ); ?></span><?php endif; ?></h2>
				<p><?php echo esc_html( $bang_demo['desc'] ); ?></p>
			</div>
		</header>
		<div class="bang-kit-body"><?php echo $bang_demo['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- 各渲染函数内部已转义 ?></div>
		<?php if ( ! empty( $bang_demo['block'] ) ) : ?>
		<div class="bang-kit-uses">
			<div class="bang-kit-use">
				<span class="bang-kit-use-label">区块写法</span>
				<?php echo bang_kit_code_html( 'html', $bang_demo['block'], 'Gutenberg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<div class="bang-kit-use">
				<span class="bang-kit-use-label">短代码写法</span>
				<?php echo bang_kit_code_html( 'text', $bang_demo['shortcode'], 'Shortcode' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</div>
		<?php else : ?>
		<div class="bang-kit-code"><?php echo bang_kit_code_html( 'text', $bang_demo['shortcode'], '用法' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<?php endif; ?>
	</section>
	<?php endforeach; ?>
</div>
<?php
bang_page_close();
get_footer();
