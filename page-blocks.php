<?php
/**
 * Template Name: 区块展厅
 * 模板说明：slug 为 blocks 的页面会自动套用本模板，也可在后台手动选择。
 *
 * 把 Bang 的正文区块、简码写法与页面模板集中展示，方便直接看效果 / 复制用法。
 * 本页由 pk_ensure_site_pages() 自动创建（slug: blocks），也可在后台手动套用模板。
 *
 * @package Bang
 */

get_header();

$pk_imgs = pk_kit_sample_images( 6 );
$pk_grid = $pk_imgs ? pk_kit_wall_html( $pk_imgs ) : '<p class="pk-kit-empty">媒体库还没有图片，上传几张后这里会出现宫格画廊。</p>';
$pk_one  = $pk_imgs ? pk_kit_wall_html( array( $pk_imgs[0] ), 'single' ) : '';

/* 01 用简码真跑一遍，展厅同时也是简码链路的活体样本 */
$pk_tips = '';
foreach ( array(
	'info'    => array( '这是一条提示', '最轻的一档：补充说明、延伸阅读、默认值。' ),
	'warning' => array( '动手前先看一眼', '这个操作会覆盖已有设置，建议先备份。' ),
	'success' => array( '已经写好了', '区块会自动适配深浅色，不用额外写样式。' ),
	'danger'  => array( '别这样写', '正文里贴 script 会被过滤，代码块请用简码包起来。' ),
) as $pk_type => $pk_tip ) {
	$pk_tips .= do_shortcode( '[tip type="' . $pk_type . '" title="' . esc_attr( $pk_tip[0] ) . '"]<p>' . esc_html( $pk_tip[1] ) . '</p>[/tip]' );
}

/* 07：完全走 the_content + kses 的简码实渲染，跟文章正文是同一条链路 */
$pk_probe = "[tip type=\"info\" title=\"简码实渲染\"]这段走 the_content 过滤，和文章正文同一条渲染链路。[/tip]\n"
	. "[fold title=\"简码折叠\" open=\"1\"]折叠里的文字，带 <code>open=\"1\"</code>。[/fold]\n"
	. "[code lang=\"js\"]const bang = 'ink';[/code]\n";
if ( $pk_imgs ) {
	$pk_probe .= "[wall]\n" . $pk_imgs[0]['src'] . "\n"
		. ( isset( $pk_imgs[1] ) ? $pk_imgs[1]['src'] : $pk_imgs[0]['src'] ) . "\n[/wall]";
}
$pk_probe_html = wp_kses_post( apply_filters( 'the_content', $pk_probe ) );

$pk_demos = array(
	array(
		'no'   => '01',
		'name' => '提示框',
		'desc' => '四种状态用不同颜色区分，卡片形状一致；支持自定义标题，正文可放链接与行内代码。',
		'html' => '<div class="pk-kit-stack">' . $pk_tips . '</div>',
		'code' => '[tip type="info" title="这是一条提示"]补充说明写在这里[/tip]' . "\n"
			. '[tip type="warning" title="动手前先看一眼"]建议先备份[/tip]' . "\n"
			. '[tip type="success"]已经写好了[/tip]' . "\n"
			. '[tip type="danger"]别这样写[/tip]',
	),
	array(
		'no'   => '02',
		'name' => '代码块',
		'desc' => '顶部一行语言标签，右上角一键复制；按语言做语法高亮（关键字 / 字符串 / 注释 / 数字 / 函数 / 变量分色），语言留空时按开头特征自动识别。核心「代码」区块加 lang-php 类名也会套同一套外观。',
		'html' => pk_kit_code_html( 'php', "<?php\n// functions.php\necho '落笔有声，留白有度。';", 'functions.php' )
			. pk_kit_code_html( '', "const bang = { ink: '#000', paper: '#fff' };\nconsole.log(bang.ink);", 'auto.js（自动识别）' ),
		'code' => '[code lang="php" file="functions.php"]<?php echo "Bang";[/code]' . "\n"
			. '核心代码区块：右侧 → 高级 → 额外的 CSS 类填 lang-php',
	),
	array(
		'no'   => '03',
		'name' => '折叠内容',
		'desc' => '原生 details / summary，键盘、读屏、无脚本环境都能用；可设置默认展开，展开时箭头翻转、内容淡入。',
		'html' => pk_kit_fold_html( '点击展开：这段写了什么', '<p>被折叠的内容默认收起，点标题即可展开。展开状态由浏览器自己维护，pjax 换页也不会串。</p>', false )
			. pk_kit_fold_html( '默认展开：一段说明', '<p>这个块带 <code>open="1"</code>，落地就是展开的。</p>', true ),
		'code' => '[fold title="点击展开"]被折叠的内容[/fold]' . "\n" . '[fold title="默认展开" open="1"]内容[/fold]',
	),
	array(
		'no'   => '04',
		'name' => '画廊 / 画展',
		'desc' => '连续出现的图片自动并成宫格，中间空一行就是单张大图；点任意一张进灯箱，左右键切换。图集区块还支持「主图 + 缩略条」与「拼贴宫格」两种排版。',
		'html' => $pk_grid . ( $pk_one ? '<p class="pk-kit-note">单图模式</p>' . $pk_one : '' ),
		'code' => '[wall]' . "\n" . '/wp-content/uploads/2026/11/one.jpg | 第一张' . "\n" . '/wp-content/uploads/2026/11/two.jpg | 第二张' . "\n" . '[/wall]',
	),
	array(
		'no'   => '05',
		'name' => '站点级组件',
		'desc' => '顶部通知条、首页滚动公告、公告弹窗、订阅卡、幻灯片各自独立开关，互不打架；通知条支持点 × / 上滑 / 双击关闭，本次会话不再出现。',
		'html' => '<ul class="pk-kit-list">'
			. '<li><span>顶部通知条</span><span>黑底细带 · 上滑 / 双击 / × 关闭</span></li>'
			. '<li><span>首页公告栏</span><span>横向无缝滚动 · hover 暂停</span></li>'
			. '<li><span>公告弹窗</span><span>内容 hash 变更才重新弹</span></li>'
			. '<li><span>命令面板</span><span>Ctrl / ⌘ + K · 可选浏览器内语义搜索</span></li>'
			. '</ul>',
		'code' => '后台 → Bang 设置 → 常规 → 顶部通知条',
	),
	array(
		'no'   => '06',
		'name' => '页面模板',
		'desc' => '文章归档、关于、联系、友情链接、说说、区块展厅都由主题自动建页，模板按 slug 匹配，无需手动指定。',
		'html' => '<div class="pk-kit-links">'
			. '<a href="' . esc_url( pk_site_page_url( 'links' ) ) . '">友情链接' . pk_kit_icon( 'arrow', 14 ) . '</a>'
			. '<a href="' . esc_url( get_post_type_archive_link( 'pk_say' ) ) . '">说说' . pk_kit_icon( 'arrow', 14 ) . '</a>'
			. '<a href="' . esc_url( pk_site_page_url( 'archive' ) ) . '">文章归档' . pk_kit_icon( 'arrow', 14 ) . '</a>'
			. '</div>',
		'code' => '后台 → 页面 → 新建页面，模板选「区块展厅」',
	),
	array(
		'no'   => '07',
		'name' => '简码直写',
		'desc' => '从别处粘过来的写法也能直接用。下面这一整块是简码经过 the_content 与 kses 过滤后的真实输出，和文章正文走的是同一条渲染链路。',
		'html' => '<div class="pk-kit-probe">' . $pk_probe_html . '</div>',
		'code' => '[tip type="info" title="标题"]内容[/tip]' . "\n"
			. '[fold title="点击展开" open="1"]内容[/fold]' . "\n"
			. '[code lang="js" file="app.js"]代码[/code]' . "\n"
			. '[wall]' . "\n" . '/uploads/one.jpg' . "\n" . '/uploads/two.jpg' . "\n" . '[/wall]',
	),
);

pk_page_open( '区块展厅', '正文里能用的东西，都摆在这里了。黑白、12px 圆角、线性图标，没有多余颜色。' );
?>
<div class="pk-kit">
	<?php foreach ( $pk_demos as $pk_demo ) : ?>
	<section class="pk-kit-demo">
		<header class="pk-kit-head">
			<span class="pk-kit-no"><?php echo esc_html( $pk_demo['no'] ); ?></span>
			<div class="pk-kit-titles">
				<h2><?php echo esc_html( $pk_demo['name'] ); ?></h2>
				<p><?php echo esc_html( $pk_demo['desc'] ); ?></p>
			</div>
		</header>
		<div class="pk-kit-body"><?php echo $pk_demo['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- 各渲染函数内部已转义 ?></div>
		<div class="pk-kit-code"><?php echo pk_kit_code_html( 'text', $pk_demo['code'], '用法' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	</section>
	<?php endforeach; ?>
</div>
<?php
pk_page_close();
get_footer();
