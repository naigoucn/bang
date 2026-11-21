<?php
/**
 * Template Name: 排版标签
 * 模板说明：slug 为 html-tag 的页面自动套用本模板，也可在后台手动选择。
 *
 * 把正文里能用得到的基础 HTML 标签集中摆出来：左边是写法，右边是它在
 * .prose 里真实的渲染效果。示例即正文，标签样式全部落在 style.css 的
 * .prose 规则里，所以「展厅里看到的」和「文章里发出去的」是同一套东西。
 *
 * 只收写作场景用得到的标签，不堆表单控件；文案、示例与分组都是本主题
 * 自己写的，版式沿用 1180 宽容器 + 细线圆角卡片 + 黑白两色。
 * 本页由 bang_ensure_site_pages() 自动创建（slug: html-tag）。
 *
 * @package Bang
 */

get_header();

$bang_blocks_url = bang_site_page_url( 'blocks' );

$bang_groups = array(
	array(
		'id'    => 'type',
		'no'    => '01',
		'name'  => '文字与强调',
		'desc'  => '标题层级、段落强调与修订痕迹。只用字重、字距和一档灰度，不引入第二种颜色。',
		'items' => array(
			array(
				'tag'  => '<h2> – <h6>',
				'desc' => '正文分节用 h2 / h3，字号逐级收小；h1 是页面标题，一页只出现一次。',
				'src'  => array(
					'<h2>章节标题</h2>',
					'<h3>小节标题</h3>',
					'<h4>再细一层</h4>',
				),
				'demo' => '<h2>留白有度</h2><h3>字距与行距</h3><h4>再细一层</h4><h5>继续收小</h5><h6>最细的一级</h6><p>六级之下，就不再往下分了。</p>',
			),
			array(
				'tag'  => '<p> <strong> <em>',
				'desc' => '段落之间留 18px；强调只靠字重与斜体，不换颜色。',
				'src'  => array(
					'<p>一段正文，<strong>这几个字重一点</strong>，<em>这几个字斜一点</em>。</p>',
				),
				'demo' => '<p>落笔有声，留白有度。<strong>该重的地方重一点</strong>，<em>该轻的地方轻一点</em>，剩下的交给空白。</p>',
			),
			array(
				'tag'  => '<a>',
				'desc' => '正文链接用 1px 下划线加 3px 偏移，不另起一种颜色；站内链接直接交给页面切换。',
				'src'  => array(
					'<a href="/blocks/">去看区块展厅</a>',
				),
				'demo' => '<p>想要更完整的写法，可以<a href="' . esc_url( $bang_blocks_url ) . '">去看区块展厅</a>。</p>',
			),
			array(
				'tag'  => '<mark>',
				'desc' => '不用荧光色。浅底、深色字、5px 圆角，把需要被看见的一句轻轻托出来。',
				'src'  => array(
					'<mark>留白有度</mark>',
				),
				'demo' => '<p>整句里只有<mark>留白有度</mark>需要被标出来。</p>',
			),
			array(
				'tag'  => '<del> <s> <ins>',
				'desc' => '修订痕迹降到一档灰并加删除线，新增内容只加下划线，不打扰正文节奏。',
				'src'  => array(
					'<del>热闹一点</del> <ins>安静一点</ins>',
				),
				'demo' => '<p>初稿写的是<del>热闹一点</del>，后来改成了<ins>安静一点</ins>。</p>',
			),
			array(
				'tag'  => '<sup> <sub>',
				'desc' => '脚注序号与变量下标，缩到 .7em 且不吃行高。',
				'src'  => array(
					'第<sup>2</sup>版 / x<sub>1</sub>',
				),
				'demo' => '<p>这一版是第<sup>2</sup>版，坐标记作 x<sub>1</sub> … x<sub>n</sub>。</p>',
			),
			array(
				'tag'  => '<ruby> <rt>',
				'desc' => '东亚文字注音。注释用小字灰，正文的字距和行高都不变。',
				'src'  => array(
					'<ruby>留白<rt>liú bái</rt></ruby>',
				),
				'demo' => '<p>写字讲究<ruby>留白<rt>liú bái</rt></ruby>，也讲究<ruby>落笔<rt>luò bǐ</rt></ruby>。</p>',
			),
			array(
				'tag'  => '<kbd>',
				'desc' => '本主题的命令面板就用它：细线、6px 圆角、等宽字。',
				'src'  => array(
					'<kbd>⌘</kbd> + <kbd>K</kbd>',
				),
				'demo' => '<p>按 <kbd>⌘</kbd> + <kbd>K</kbd> 打开命令面板，搜标题或分类。</p>',
			),
			array(
				'tag'  => '<small>',
				'desc' => '附注、版权与时间戳，缩到 .85em 并降一档灰度。',
				'src'  => array(
					'<small>最后修订于 2026.11</small>',
				),
				'demo' => '<p>正文到此结束。<br><small>最后修订于 2026.11 · 示例文字</small></p>',
			),
		),
	),
	array(
		'id'    => 'block',
		'no'    => '02',
		'name'  => '结构与列表',
		'desc'  => '引用、列表、分隔线、表格与折叠。全部用 1px 细线和 12px 圆角收边。',
		'items' => array(
			array(
				'tag'  => '<blockquote>',
				'desc' => '左侧 2px 实线，文字降一档灰；不加引号装饰，也不居中。',
				'src'  => array(
					'<blockquote>好的排版，是让人忘记排版的。</blockquote>',
				),
				'demo' => '<blockquote><p>好的排版，是让人忘记排版的。</p></blockquote><p><small>— 写在这套主题的样式说明里</small></p>',
			),
			array(
				'tag'  => '<ul> <ol> <li>',
				'desc' => '缩进 22px，有序与无序同节奏，可以再嵌一层。',
				'src'  => array(
					'<ul>',
					'  <li>短动态</li>',
					'  <li>长文章</li>',
					'</ul>',
				),
				'demo' => '<ul><li>短动态</li><li>图集</li><li>长文章<ul><li>带小标题的那种</li></ul></li></ul><ol><li>先写下来</li><li>再删一遍</li><li>然后发布</li></ol>',
			),
			array(
				'tag'  => '<hr>',
				'desc' => '1px 细线，上下各留 28px；用来换话题，不是装饰。',
				'src'  => array(
					'<hr>',
				),
				'demo' => '<p>上面是一段。</p><hr><p>下面是另一段。</p>',
			),
			array(
				'tag'  => '<table> <th> <td>',
				'desc' => '全宽、细线、表头浅底；窄屏上横向滚动，不挤压单元格。',
				'src'  => array(
					'<table>',
					'  <tr><th>标签</th><th>用途</th></tr>',
					'  <tr><td>mark</td><td>标出一句</td></tr>',
					'</table>',
				),
				'demo' => '<table><thead><tr><th>标签</th><th>用途</th></tr></thead><tbody><tr><td><code>mark</code></td><td>标出一句</td></tr><tr><td><code>kbd</code></td><td>键盘按键</td></tr><tr><td><code>blockquote</code></td><td>整段引用</td></tr></tbody></table>',
			),
			array(
				'tag'  => '<figure> <figcaption>',
				'desc' => '图片统一 12px 圆角，图注小字居中；这里用占位块代替真实图片。',
				'src'  => array(
					'<figure>',
					'  <img src="…" alt="…">',
					'  <figcaption>图注</figcaption>',
					'</figure>',
				),
				'demo' => '<figure><div class="bang-tag-ph">图片占位 · 12px 圆角</div><figcaption>换成 &lt;img&gt; 就是文章里的样子</figcaption></figure>',
			),
			array(
				'tag'  => '<details> <summary>',
				'desc' => '原生折叠，不写脚本也能展开；主题自己的折叠区块也是同一套结构。',
				'src'  => array(
					'<details>',
					'  <summary>展开看看</summary>',
					'  <p>内容</p>',
					'</details>',
				),
				'demo' => '<details><summary>展开看看</summary><p>被收起来的那句，点标题就出来。</p></details>',
			),
		),
	),
	array(
		'id'    => 'code',
		'no'    => '03',
		'name'  => '代码与简码',
		'desc'  => '行内代码、预格式文本，以及主题自己的简码写法。',
		'items' => array(
			array(
				'tag'  => '<code>',
				'desc' => '细线 + 浅底 + 6px 圆角，等宽字缩一档；和正文明显分开但不抢眼。',
				'src'  => array(
					'用 <code>[tip]</code> 插入一条提示',
				),
				'demo' => '<p>用 <code>[tip]</code> 插入一条提示，用 <code>[fold]</code> 收起一段说明。</p>',
			),
			array(
				'tag'  => '<pre> <code>',
				'desc' => '保留空格与换行，横向可滚动；主题代码块会额外加语言标签、语法高亮和一键复制。',
				'src'  => array(
					'<pre><code>const bang = \'ink\';',
					'console.log(bang);</code></pre>',
				),
				'demo' => bang_kit_code_html( 'js', "const bang = { ink: '#000', paper: '#fff' };\nconsole.log(bang.ink);", 'app.js' ),
			),
			array(
				'tag'  => '[tip] [fold] [code]',
				'desc' => '主题自己的简码，和编辑器区块共用一套渲染；完整清单在区块展厅。',
				'src'  => array(
					'[tip type="info"]补充说明[/tip]',
					'[fold title="展开看看"]内容[/fold]',
				),
				'demo' => '<p>这几条不是 HTML 标签，是主题的简码写法。它们生成的提示框、折叠与代码块，在<a href="' . esc_url( $bang_blocks_url ) . '">区块展厅</a>里能逐条看效果。</p>',
			),
		),
	),
);

bang_page_open( '排版标签', '写字时用得到的 HTML 标签，在这套黑白版式里长什么样。' );
?>
<div class="bang-tag">
	<p class="bang-tag-intro">Bang 的正文只做一件事：让文字安静地待在该在的位置。下面这些标签可以直接写进文章，主题会自动给它们统一的间距、描边与圆角——不用额外加类名，也不用自己写样式。左边是写法，右边是它在正文里真实的样子。</p>

	<nav class="bang-tag-index" aria-label="标签分组">
		<?php foreach ( $bang_groups as $bang_group ) : ?>
			<a class="bang-tag-index-link" href="#<?php echo esc_attr( $bang_group['id'] ); ?>" data-no-swup><?php echo esc_html( $bang_group['no'] . ' ' . $bang_group['name'] ); ?></a>
		<?php endforeach; ?>
		<a class="bang-tag-index-link" href="<?php echo esc_url( $bang_blocks_url ); ?>">区块展厅 →</a>
	</nav>

	<?php foreach ( $bang_groups as $bang_group ) : ?>
	<section class="bang-tag-group" id="<?php echo esc_attr( $bang_group['id'] ); ?>">
		<header class="bang-tag-group-head">
			<h2><?php echo esc_html( $bang_group['name'] ); ?></h2>
			<span><?php echo esc_html( $bang_group['no'] . ' / 共 ' . count( $bang_group['items'] ) . ' 条' ); ?></span>
		</header>
		<p class="bang-tag-group-desc"><?php echo esc_html( $bang_group['desc'] ); ?></p>
		<div class="bang-tag-grid">
			<?php foreach ( $bang_group['items'] as $bang_item ) : ?>
			<article class="bang-tag-row">
				<div class="bang-tag-meta">
					<code class="bang-tag-name"><?php echo esc_html( $bang_item['tag'] ); ?></code>
					<p class="bang-tag-desc"><?php echo esc_html( $bang_item['desc'] ); ?></p>
					<pre class="bang-tag-src"><code><?php echo esc_html( implode( "\n", $bang_item['src'] ) ); ?></code></pre>
				</div>
				<div class="bang-tag-demo prose"><?php echo $bang_item['demo']; // phpcs:ignore WordPress.Security.EscapeOutput -- 静态示例 HTML，渲染函数内部已转义 ?></div>
			</article>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endforeach; ?>

	<div class="bang-tag-more">
		<span>上面是正文标签。提示框、折叠、代码块、画廊这些正文区块，是另一套东西。</span>
		<a class="bang-tag-more-link" href="<?php echo esc_url( $bang_blocks_url ); ?>">去区块展厅 <?php echo bang_kit_icon( 'arrow', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
	</div>
</div>
<?php
bang_page_close();
get_footer();
