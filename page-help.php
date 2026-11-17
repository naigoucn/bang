<?php
/**
 * Template Name: 帮助中心
 * 模板说明：slug 为 help 的页面自动套用本模板，也可在后台手动选择。
 *
 * 结构参照成熟的「帮助中心 / 文档索引」页：大标题 + 搜索 + 左侧目录 +
 * 分组的可展开条目 + 常见问题 + 开源致谢 + 联系卡。
 * 内容全部围绕 Bang 自己写，链接都指向站内真实地址，不留死链。
 *
 * @package Bang
 */

get_header();

$pk_groups = array(
	array(
		'id'    => 'start',
		'num'   => '01',
		'title' => '开始使用',
		'desc'  => '从上传主题到发出第一篇文章，先走完这一段。',
		'items' => array(
			array(
				't' => '服务器及软件要求',
				'd' => 'PHP 7.4+ / WordPress 6.0+',
				'c' => '<p>主题是经典 PHP 模板，不依赖 Node 运行时，也不需要前端构建。常规浏览用到的样式与脚本全部随主题本地存放（含灯箱与页面切换库），不依赖 CDN；只有可选的 AI 语义搜索会在开启后按需加载推理运行时。</p><p>建议开启伪静态，并把 PHP 的 <code>ZipArchive</code> 打开（没有也能用，主题自带纯 PHP 的 ZIP 兜底）。</p>',
			),
			array(
				't' => '安装主题',
				'd' => '上传 zip，不要直接覆盖目录',
				'c' => '<p>后台 → 外观 → 主题 → 添加主题 → 上传主题，选择打包好的 <code>zip</code> 后点「现在安装」。部署包必须是 deflate 压缩的标准 zip，Windows 资源管理器或 <code>tar -a</code> 生成的未压缩包会被部分主机 WAF 拦下。</p><p>安装完成后点「启用」。别把文件直接解压覆盖到正在使用的主题目录，容易留下旧文件。</p>',
			),
			array(
				't' => '激活后先改固定链接',
				'd' => '/%post_id%/，保存两次',
				'c' => '<p>设置 → 固定链接，选「自定义结构」填 <code>/%post_id%/</code>，<strong>保存两次</strong>让重写规则真正刷新。</p><p>写成 <code>/%post_id%.html</code> 会让文章页判断、翻篇与登录共用路径一并失效；评论区的 <code>/{文章ID}/talk</code> 也依赖这一步。</p>',
			),
			array(
				't' => '主题包含内容',
				'd' => '自动建页与需要手建的一页',
				'c' => '<p>主题会自动建好 <code>/login/</code>、文章归档、关于、联系、友情链接、<a href="' . esc_url( pk_site_page_url( 'blocks' ) ) . '">区块展厅</a>、<a href="' . esc_url( pk_site_page_url( 'html-tag' ) ) . '">排版标签</a>与本页，按 slug 自动匹配模板，正文可在后台编辑。</p><p>唯一需要手动新建的是 <code>/user/</code> 用户中心：后台 → 页面 → 新建页面，slug 填 <code>user</code> 发布即可。</p>',
			),
			array(
				't' => '导入演示数据',
				'd' => '空站也能立刻看到效果',
				'c' => '<p>演示内容是一批示例文章与图片，用来检验卡片、幻灯片、图集与评论区。导入走后台 cookie + REST nonce，不需要额外建应用密码。</p><p>正式站点建议导入后逐篇替换，别把示例文字留在首页。</p>',
			),
			array(
				't' => '更新主题 / 使用子主题',
				'd' => '同名 zip 覆盖，定制请放子主题',
				'c' => '<p>升级时上传同名主题 zip 覆盖即可，设置存在数据库里不会丢。开发者改完源文件后要重新生成压缩资源，再打包上传，否则前台可能继续用旧的压缩副本。</p><p>要改 PHP 模板或样式，请建子主题覆盖，别直接改父主题——下次升级会被覆盖掉。</p>',
			),
		),
	),
	array(
		'id'    => 'write',
		'num'   => '02',
		'title' => '写作与区块',
		'desc'  => '编辑器区块与简码两种写法，共用同一套渲染。',
		'items' => array(
			array(
				't' => '提示框',
				'd' => '四种状态，不用彩色区分',
				'c' => '<p>编辑器里插入「提示框」区块，或直接写简码：</p><p><code>[tip type="info" title="补充说明"]内容[/tip]</code></p><p><code>type</code> 可选 <code>info</code> / <code>warning</code> / <code>success</code> / <code>danger</code>，靠图标形状与左边线粗细区分，保持黑白。</p>',
			),
			array(
				't' => '折叠内容',
				'd' => '原生 details，无脚本也能用',
				'c' => '<p><code>[fold title="展开看看" open="1"]内容[/fold]</code>，<code>open="1"</code> 表示默认展开。使用原生 <code>details/summary</code>，禁用 JavaScript 时依旧可点。</p>',
			),
			array(
				't' => '代码块',
				'd' => '语言标注 + 一键复制',
				'c' => '<p><code>[code lang="php"]echo "Bang";[/code]</code>。语言留空会按开头特征自动嗅探；右上角按钮走 Clipboard API，失败时退回选中复制。</p>',
			),
			array(
				't' => '画廊与画展',
				'd' => '连续图片并宫格，空行分开成单张',
				'c' => '<p>图集区块选「主图 + 缩略条」或「拼贴宫格」；也可以写 <code>[wall]</code>，每行一个图片地址。正文里连续图片会自动并成宫格，中间空一行则是单张大图。</p>',
			),
			array(
				't' => '金句 / 链接卡 / 元信息',
				'd' => '三个现成区块',
				'c' => '<p>金句卡是主题里唯一允许渐变的组件；链接卡的外链统一走站内中转确认页并在新标签打开；元信息条适合写时间、地点、天气、设备。</p><p>到 <a href="' . esc_url( pk_site_page_url( 'blocks' ) ) . '">区块展厅</a> 可以一次看全七组示例并直接复制写法。</p>',
			),
			array(
				't' => '区块还是简码',
				'd' => '同一条渲染链路',
				'c' => '<p>新写文章推荐用编辑器区块，所见即所得；从别处迁移文章、或粘贴纯文本时，用简码最省事。两种写法最后都经过同样的转义与过滤，不会出现两套样式。</p>',
			),
		),
	),
	array(
		'id'    => 'read',
		'num'   => '03',
		'title' => '阅读与交互',
		'desc'  => '换页、搜索、翻篇、评论与看图。',
		'items' => array(
			array(
				't' => '⌘K 命令面板',
				'd' => 'Ctrl / ⌘ + K',
				'c' => '<p>命令与文章即时搜索合一：切深浅色、打开归档、跳到用户中心，或直接输入关键词找文章。手机上从侧栏搜索框进入。</p>',
			),
			array(
				't' => 'AI 语义搜索（可选）',
				'd' => '浏览器内推理，默认关闭',
				'c' => '<p>在命令面板里打开开关后，用量化小模型在本地把文章标题与摘要转成向量，按余弦相似度排序。向量缓存在浏览器 localStorage，模型权重优先读同源的 <code>uploads/pk-hf-cache/</code>，不占用服务器算力。</p>',
			),
			array(
				't' => '无刷新换页',
				'd' => 'pjax + View Transitions',
				'c' => '<p>站内链接由页面切换库接管，配共享元素转场。回源慢时顶部有细加载条，超时会退回整页跳转，不会出现「地址变了页面不动」的死状态。hover 与首屏可见卡片会自动预取。</p>',
			),
			array(
				't' => '手势翻篇',
				'd' => '手机左右滑 / PC 左右键',
				'c' => '<p>左滑到下一篇、右滑到上一篇，方向键同向。只位移头图与正文，避免生成新的固定定位包含块；地址栏用 <code>replaceState</code> 同步，不额外压历史栈。</p>',
			),
			array(
				't' => '评论区独立路径',
				'd' => '/{文章ID}/talk',
				'c' => '<p>点悬浮条「评论」时地址栏会变成 <code>/29/talk</code>，关闭回落到文章地址；直接访问或分享这个地址会渲染文章并自动拉开评论抽屉。canonical 仍指向文章本体，避免重复收录。</p>',
			),
			array(
				't' => '灯箱看大图',
				'd' => '浮起来，不盖住正文',
				'c' => '<p>点图片会从缩略图原位放大浮起，背景是半透明磨砂层，正文依然看得见；左右键或滑动切换，滚轮 / 双击缩放，ESC 或点空白处关闭。</p><p>用的是随主题自带的 PhotoSwipe（本地 UMD，MIT），不需要任何外部 CDN。</p>',
			),
		),
	),
	array(
		'id'    => 'user',
		'num'   => '04',
		'title' => '用户与互动',
		'desc'  => '登录、验证、回复、点赞与订阅。',
		'items' => array(
			array(
				't' => '登录与注册',
				'd' => '一套卡片，三种入口',
				'c' => '<p><code>/login/</code> 整页、页面右下常驻弹窗、<code>/user/</code> 未登录态，用的是同一套表单。打开弹窗时地址栏同步成 <code>/login/</code>，关闭写回来源地址，不压历史栈。</p>',
			),
			array(
				't' => '滑块验证',
				'd' => '注册常驻，评论点提交才出现',
				'c' => '<p>拖到底换一张一次性票据，服务端再验一次：终点位置、采样密度、耗时与轨迹单调性都会检查。注册表单的滑块常驻；评论表单默认收起，点「发表评论」才展开，拖完自动续上这次提交。</p>',
			),
			array(
				't' => '通行密钥',
				'd' => 'WebAuthn，挑战存 transient',
				'c' => '<p>在用户中心绑定设备后可用指纹 / 面容登录。挑战以十六进制写进 transient，避免二进制在序列化时损坏；信任设备最多保留 5 台。</p>',
			),
			array(
				't' => '上下级回复',
				'd' => '三层缩进，保留 @ 指向',
				'c' => '<p>点任意评论的「回复」会亮出「正在回复 @某人」，提交时带上 <code>comment_parent</code>；成功后服务端回整棵树，前端一次替换，不会把回复错塞到列表开头。</p>',
			),
			array(
				't' => '点赞与订阅',
				'd' => 'REST + 后台可导出',
				'c' => '<p>登录后可点赞文章；首页订阅卡收集邮箱，按 IP 限流，后台能看列表并导出 CSV。</p>',
			),
			array(
				't' => '公告弹窗',
				'd' => '确认后不再打扰',
				'c' => '<p>内容变了才会重新弹。点「确认」把内容 hash 记下来；× / ESC / 点遮罩只关本次会话。</p>',
			),
		),
	),
	array(
		'id'    => 'manage',
		'num'   => '05',
		'title' => '站点管理',
		'desc'  => '设置页、首页组件、版权与资源自检。',
		'items' => array(
			array(
				't' => 'Bang 设置',
				'd' => '左侧顶级菜单，三个 tab',
				'c' => '<p>常规 / SEO / 高级三个 tab 共用同一个表单，切换只是隐藏字段，保存不会丢值。页面完全自绘，不套 WordPress 的 form-table。</p>',
			),
			array(
				't' => '幻灯片与公告栏',
				'd' => '只在首页、分类页不输出',
				'c' => '<p>宽屏首页顶部是自动播放的幻灯片，下方是无缝滚动的公告栏；内容不足一屏时脚本会补齐，避免滚到末端露白。公告内容可在后台一行一条配置，留空取最新文章标题。</p>',
			),
			array(
				't' => '文章版权声明',
				'd' => '许可协议可配',
				'c' => '<p>正文底部输出标题、作者、首发链接与协议。后台可开关、选协议（CC BY-NC-SA / CC BY / 保留所有权利 / 不写）并补一段自己的说明。</p>',
			),
			array(
				't' => '首访资源自检',
				'd' => '加载失败才提示',
				'c' => '<p>首次打开会静默检查全站样式、交互脚本与页面切换库是否真的就绪；缺了才弹一张自包含提示卡，点「刷新重试」重新拉取资源，不会再让你下载文件。</p>',
			),
			array(
				't' => '深浅色与降级',
				'd' => '跟随系统，可手动覆盖',
				'c' => '<p>所有新特性都包在 <code>@supports</code> 与 <code>prefers-reduced-motion</code> 里，基础态本身就是完成态；老浏览器或开了「减少动态效果」会自动退化成静态展示。</p>',
			),
		),
	),
	array(
		'id'    => 'faq',
		'num'   => '06',
		'title' => '常见问题',
		'desc'  => '遇到这些情况，先按这里排查。',
		'items' => array(
			array(
				't' => '路径变了，页面没变，也没动画',
				'd' => '多半是回源慢',
				'c' => '<p>换页时地址栏会先更新，再等新页面内容。顶部细加载条显示进度；超过超时时间会直接退回整页跳转，不会一直卡住。hover 与首屏预取能把这段等待压到几乎为零。</p>',
			),
			array(
				't' => '灯箱又把正文盖住了',
				'd' => '检查本地库是否加载',
				'c' => '<p>正常态是半透明磨砂背景，能看到后面的文字。若变成整块纯色，说明本地灯箱脚本没加载成功，主题会退回原生 dialog；刷新一次多半就恢复，仍不行请告诉站长。</p>',
			),
			array(
				't' => '评论输入框怎么这么小',
				'd' => '点一下展开',
				'c' => '<p>默认单行紧凑态，点一下变多行并显示「完善信息 / 发表评论」；提交成功自动收回。评论抽屉不再自动聚焦输入框，否则一打开就把紧凑态撑开了。</p>',
			),
			array(
				't' => '注册提示「缺票据」',
				'd' => '重新拖一次滑块',
				'c' => '<p>票据 10 分钟有效、最多用 5 次，提交失败会被解锁重新展开。连续失败太多次会按 IP 限流，等一会儿再试。</p>',
			),
			array(
				't' => '后台找不到主题设置',
				'd' => '看左侧顶级菜单',
				'c' => '<p>菜单名是「Bang 设置」，是独立顶级菜单，不在「外观」子菜单里。</p>',
			),
			array(
				't' => '固定链接改完还是 404',
				'd' => '保存两次',
				'c' => '<p>WordPress 的重写规则要再次保存才会 flush。改完 <code>/%post_id%/</code> 保存两次，再打开 <code>/{文章ID}/talk</code> 验证。</p>',
			),
		),
	),
);

pk_page_open( '帮助中心', '先自己动手，再开口问。这里收着 Bang 的安装、写作、阅读与排障。' );
?>
<div class="pk-help" data-pk-help>
	<aside class="pk-help-side" aria-label="帮助目录">
		<p class="pk-help-side-label">目录</p>
		<nav class="pk-help-toc" id="pk-help-toc">
			<?php foreach ( $pk_groups as $pk_g ) : ?>
			<a class="pk-help-toc-link" href="#h-<?php echo esc_attr( $pk_g['id'] ); ?>" data-pk-help-link="h-<?php echo esc_attr( $pk_g['id'] ); ?>"><span><?php echo esc_html( $pk_g['num'] ); ?></span><?php echo esc_html( $pk_g['title'] ); ?></a>
			<?php endforeach; ?>
			<a class="pk-help-toc-link" href="#h-credits" data-pk-help-link="h-credits"><span>07</span>开源致谢</a>
			<a class="pk-help-toc-link" href="#h-contact" data-pk-help-link="h-contact"><span>08</span>还没解决</a>
		</nav>
	</aside>

	<div class="pk-help-main">
		<div class="pk-help-tools">
			<label class="pk-help-search">
				<span class="pk-help-search-ico" aria-hidden="true"><?php echo pk_icon( 'search', 17 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<input type="search" id="pk-help-search" placeholder="搜索条目，比如「滑块」「灯箱」「固定链接」" autocomplete="off" aria-label="搜索帮助条目">
			</label>
			<button type="button" class="pk-help-toggle" id="pk-help-toggle" aria-pressed="false">展开全部</button>
		</div>
		<p class="pk-help-hits" id="pk-help-hits" hidden></p>

		<?php foreach ( $pk_groups as $pk_g ) : ?>
		<section class="pk-help-sec" id="h-<?php echo esc_attr( $pk_g['id'] ); ?>" data-pk-help-sec>
			<header class="pk-help-sec-head">
				<span class="pk-help-num"><?php echo esc_html( $pk_g['num'] ); ?></span>
				<div>
					<h2><?php echo esc_html( $pk_g['title'] ); ?></h2>
					<p><?php echo esc_html( $pk_g['desc'] ); ?></p>
				</div>
				<span class="pk-help-sec-count"><?php echo count( $pk_g['items'] ); ?> 条</span>
			</header>
			<div class="pk-help-items">
				<?php foreach ( $pk_g['items'] as $pk_item ) : ?>
				<details class="pk-help-item" data-pk-help-item>
					<summary>
						<span class="pk-help-item-t"><?php echo esc_html( $pk_item['t'] ); ?></span>
						<span class="pk-help-item-d"><?php echo esc_html( $pk_item['d'] ); ?></span>
						<span class="pk-help-caret" aria-hidden="true"><?php echo pk_icon( 'chevron-down', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					</summary>
					<div class="pk-help-answer"><?php echo wp_kses_post( $pk_item['c'] ); ?></div>
				</details>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endforeach; ?>

		<section class="pk-help-sec" id="h-credits" data-pk-help-sec>
			<header class="pk-help-sec-head">
				<span class="pk-help-num">07</span>
				<div><h2>开源致谢</h2><p>站在这些项目上面，才有了现在这副样子。</p></div>
			</header>
			<div class="pk-help-credits">
				<div class="pk-help-credit"><span class="pk-help-credit-n">PhotoSwipe 5.4.4</span><span class="pk-help-credit-l">MIT</span><span class="pk-help-credit-d">灯箱。随主题本地存放，不使用 CDN。</span></div>
				<div class="pk-help-credit"><span class="pk-help-credit-n">Swup</span><span class="pk-help-credit-l">MIT</span><span class="pk-help-credit-d">全站无刷新换页。随主题本地存放。</span></div>
				<div class="pk-help-credit"><span class="pk-help-credit-n">WordPress</span><span class="pk-help-credit-l">GPL</span><span class="pk-help-credit-d">内容、用户、评论与 REST。</span></div>
				<div class="pk-help-credit"><span class="pk-help-credit-n">transformers.js</span><span class="pk-help-credit-l">Apache-2.0</span><span class="pk-help-credit-d">可选的浏览器内语义搜索，默认关闭、按需加载。</span></div>
			</div>
		</section>

		<section class="pk-help-sec" id="h-contact" data-pk-help-sec>
			<header class="pk-help-sec-head">
				<span class="pk-help-num">08</span>
				<div><h2>还没解决</h2><p>先把现象、浏览器和出现路径记下来，再联系。</p></div>
			</header>
			<div class="pk-help-contact">
				<a href="<?php echo esc_url( pk_site_page_url( 'about' ) ); ?>"><span>关于本站</span><?php echo pk_icon( 'next', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<a href="<?php echo esc_url( pk_site_page_url( 'contact' ) ); ?>"><span>联系</span><?php echo pk_icon( 'next', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<a href="mailto:<?php echo esc_attr( pk_contact_email() ); ?>"><span><?php echo esc_html( pk_contact_email() ); ?></span><?php echo pk_icon( 'next', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><span>回到首页</span><?php echo pk_icon( 'next', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			</div>
		</section>

		<p class="pk-help-empty" id="pk-help-empty" hidden>没有匹配的条目，换个关键词试试。</p>
	</div>
</div>
<?php
pk_page_close();
get_footer();
