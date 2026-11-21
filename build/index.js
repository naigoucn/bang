(()=>{"use strict";const e=window.wp.blocks,a=window.wp.i18n,t=window.wp.blockEditor,s=window.wp.components,n=JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"bang/gallery","version":"0.10.1","title":"图集","category":"bang-blocks","icon":"images-alt","description":"一组图片，主图加缩略条或拼贴宫格，点击可放大查看。","keywords":["图集","多图","gallery"],"textdomain":"bang","supports":{"align":["wide","full"],"anchor":true,"className":true,"spacing":{"margin":true,"padding":false}},"attributes":{"images":{"type":"array","default":[],"items":{"type":"object"}},"layout":{"type":"string","default":"stack","enum":["stack","grid","marquee"]},"caption":{"type":"string","default":""}},"example":{"attributes":{"layout":"stack"}}}'),l=window.ReactJSXRuntime,i={metadata:n,settings:{edit:function({attributes:e,setAttributes:n}){const{images:i=[],layout:o="stack",caption:r=""}=e,p="grid"===o?"grid":"stack",c=(0,t.useBlockProps)({className:"bang-gallery-editor bang-gallery-editor--"+p});return(0,l.jsxs)(l.Fragment,{children:[(0,l.jsx)(t.InspectorControls,{children:(0,l.jsxs)(s.PanelBody,{title:(0,a.__)("多图组设置","bang"),initialOpen:!0,children:[(0,l.jsx)(s.SelectControl,{label:(0,a.__)("排列方式","bang"),value:p,options:[{label:(0,a.__)("主图 + 缩略条","bang"),value:"stack"},{label:(0,a.__)("拼贴宫格","bang"),value:"grid"}],onChange:e=>n({layout:e})}),(0,l.jsx)(s.TextControl,{label:(0,a.__)("图注（可选）","bang"),value:r,onChange:e=>n({caption:e})})]})}),(0,l.jsxs)("figure",{...c,children:[(0,l.jsx)(t.MediaUploadCheck,{children:(0,l.jsx)(t.MediaUpload,{allowedTypes:["image"],multiple:!0,gallery:!0,value:i.map(e=>e.id).filter(Boolean),onSelect:e=>{n({images:e.map(e=>({id:e.id,url:e.url,alt:e.alt||""}))})},render:({open:e})=>(0,l.jsxs)("div",{className:"bang-gallery-editor-grid",children:[i.map((e,t)=>(0,l.jsxs)("div",{className:"bang-gallery-editor-thumb",children:[(0,l.jsx)("img",{src:e.url,alt:e.alt||""}),(0,l.jsx)(s.Button,{variant:"secondary",className:"bang-gallery-editor-remove",onClick:()=>(e=>{n({images:i.filter((a,t)=>t!==e)})})(t),children:(0,a.__)("移除","bang")})]},e.id||t)),(0,l.jsx)(s.Button,{variant:"secondary",className:"bang-gallery-editor-add",onClick:e,children:i.length?(0,a.__)("重新选择图片","bang"):(0,a.__)("选择图片","bang")})]})})}),r?(0,l.jsx)("figcaption",{children:r}):null]})]})},save:()=>null}},o={metadata:JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"bang/quote","version":"0.10.1","title":"金句卡","category":"bang-blocks","icon":"format-quote","description":"一段引文，可附上出处。","keywords":["引用","金句","quote"],"textdomain":"bang","supports":{"align":false,"anchor":true,"className":true},"attributes":{"text":{"type":"string","default":""},"source":{"type":"string","default":""}},"example":{"attributes":{"text":"好看的其实不是太阳，是它走之后那几分钟。","source":"山里的傍晚"}}}'),settings:{edit:function({attributes:e,setAttributes:n}){const{text:i="",source:o=""}=e,r=(0,t.useBlockProps)({className:"bang-quote"});return(0,l.jsxs)(l.Fragment,{children:[(0,l.jsx)(t.InspectorControls,{children:(0,l.jsx)(s.PanelBody,{title:(0,a.__)("金句卡设置","bang"),initialOpen:!0,children:(0,l.jsx)(s.TextControl,{label:(0,a.__)("出处（可选）","bang"),value:o,onChange:e=>n({source:e})})})}),(0,l.jsxs)("blockquote",{...r,children:[(0,l.jsx)(t.RichText,{tagName:"p",className:"bang-quote-text",value:i,onChange:e=>n({text:e}),placeholder:(0,a.__)("写一句想留下的话…","bang"),allowedFormats:["core/bold","core/italic"]}),o?(0,l.jsx)("span",{className:"bang-quote-source",children:o}):null]})]})},save:()=>null}},r={metadata:JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"bang/linkcard","version":"0.10.1","title":"链接卡","category":"bang-blocks","icon":"admin-links","description":"一张站点卡片，外链经中转确认页在新标签打开。","keywords":["链接","站点","link"],"textdomain":"bang","supports":{"align":false,"anchor":true,"className":true},"attributes":{"url":{"type":"string","default":""},"title":{"type":"string","default":""},"description":{"type":"string","default":""}},"example":{"attributes":{"url":"https://example.com/","title":"一个值得留下的站点","description":"写在卡片上的一句话说明。"}}}'),settings:{edit:function({attributes:e,setAttributes:n}){const{url:i="",title:o="",description:r=""}=e,p=(0,t.useBlockProps)({className:"bang-linkcard"}),c=function(e){try{return new URL(e).hostname}catch(a){return e}}(i);return(0,l.jsxs)(l.Fragment,{children:[(0,l.jsx)(t.InspectorControls,{children:(0,l.jsxs)(s.PanelBody,{title:(0,a.__)("链接卡设置","bang"),initialOpen:!0,children:[(0,l.jsx)(s.TextControl,{label:(0,a.__)("目标地址","bang"),type:"url",value:i,onChange:e=>n({url:e}),help:(0,a.__)("只支持 http / https，前台经中转确认页在新标签打开。","bang")}),(0,l.jsx)(s.TextControl,{label:(0,a.__)("标题","bang"),value:o,onChange:e=>n({title:e})}),(0,l.jsx)(s.TextControl,{label:(0,a.__)("描述（可选）","bang"),value:r,onChange:e=>n({description:e})})]})}),(0,l.jsxs)("div",{...p,children:[(0,l.jsx)("span",{className:"bang-linkcard-title",children:o||(0,a.__)("未命名站点","bang")}),r?(0,l.jsx)("span",{className:"bang-linkcard-desc",children:r}):null,(0,l.jsxs)("span",{className:"bang-linkcard-foot",children:[(0,l.jsx)("span",{className:"bang-linkcard-host",children:c||(0,a.__)("填写地址后显示域名","bang")}),(0,l.jsx)("span",{className:"bang-linkcard-arrow","aria-hidden":"true"})]})]})]})},save:()=>null}},p={metadata:JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"bang/meta","version":"0.10.1","title":"元信息条","category":"bang-blocks","icon":"list-view","description":"时间 / 地点 / 天气 / 设备等一行小字。","keywords":["元信息","时间","地点","meta"],"textdomain":"bang","supports":{"align":false,"anchor":true,"className":true},"attributes":{"items":{"type":"array","default":[],"items":{"type":"object"}}},"example":{"attributes":{"items":[{"label":"地点","value":"山脚"},{"label":"天气","value":"多云"}]}}}'),settings:{edit:function({attributes:e,setAttributes:n}){const{items:i=[]}=e,o=(0,t.useBlockProps)({className:"bang-meta"}),r=(e,a,t)=>{n({items:i.map((s,n)=>n===e?{...s,[a]:t}:s)})};return(0,l.jsxs)(l.Fragment,{children:[(0,l.jsx)(t.InspectorControls,{children:(0,l.jsxs)(s.PanelBody,{title:(0,a.__)("元信息条设置","bang"),initialOpen:!0,children:[i.map((e,t)=>(0,l.jsxs)("div",{className:"bang-meta-editor-row",children:[(0,l.jsx)(s.TextControl,{label:(0,a.__)("名称","bang"),value:e.label||"",onChange:e=>r(t,"label",e)}),(0,l.jsx)(s.TextControl,{label:(0,a.__)("内容","bang"),value:e.value||"",onChange:e=>r(t,"value",e)}),(0,l.jsx)(s.Button,{variant:"secondary",onClick:()=>(e=>{n({items:i.filter((a,t)=>t!==e)})})(t),children:(0,a.__)("删除这一项","bang")})]},t)),(0,l.jsx)(s.Button,{variant:"primary",onClick:()=>{n({items:[...i,{label:"",value:""}]})},children:(0,a.__)("添加一项","bang")})]})}),(0,l.jsx)("div",{...o,children:i.length?i.map((e,a)=>(0,l.jsxs)("span",{className:"bang-meta-item",children:[(0,l.jsx)("span",{className:"bang-meta-label",children:e.label}),(0,l.jsx)("span",{className:"bang-meta-value",children:e.value})]},a)):(0,l.jsx)("span",{className:"bang-meta-item bang-meta-placeholder",children:(0,a.__)("在右侧添加时间、地点等信息","bang")})})]})},save:()=>null}},c=JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"bang/tip","version":"0.13.0","title":"提示框","category":"bang-blocks","icon":"info","description":"四种状态的提示卡：提示 / 注意 / 搞定 / 警告，黑白图标区分。","keywords":["提示","警告","notice","tip"],"textdomain":"bang","supports":{"align":false,"anchor":true,"className":true},"attributes":{"type":{"type":"string","default":"info","enum":["info","warning","success","danger"]},"title":{"type":"string","default":""},"content":{"type":"string","default":""}},"example":{"attributes":{"type":"info","title":"写作提示","content":"留白不是空，是给眼睛一次呼吸。"}}}'),d={info:(0,a.__)("提示","bang"),warning:(0,a.__)("注意","bang"),success:(0,a.__)("搞定","bang"),danger:(0,a.__)("警告","bang")},u={metadata:c,settings:{edit:function({attributes:e,setAttributes:n}){const{type:i="info",title:o="",content:r=""}=e,p=(0,t.useBlockProps)({className:"bang-tip bang-tip--"+i});return(0,l.jsxs)(l.Fragment,{children:[(0,l.jsx)(t.InspectorControls,{children:(0,l.jsxs)(s.PanelBody,{title:(0,a.__)("提示框设置","bang"),initialOpen:!0,children:[(0,l.jsx)(s.SelectControl,{label:(0,a.__)("状态","bang"),value:i,options:Object.keys(d).map(e=>({label:d[e],value:e})),onChange:e=>n({type:e})}),(0,l.jsx)(s.TextControl,{label:(0,a.__)("标题（可选）","bang"),value:o,onChange:e=>n({title:e})})]})}),(0,l.jsxs)("div",{...p,children:[(0,l.jsxs)("div",{className:"bang-tip-head",children:[(0,l.jsx)("span",{className:"bang-tip-label",children:d[i]||d.info}),(0,l.jsx)(t.RichText,{tagName:"strong",className:"bang-tip-title",value:o,onChange:e=>n({title:e}),placeholder:(0,a.__)("标题（可选）","bang"),allowedFormats:[]})]}),(0,l.jsx)(t.RichText,{tagName:"div",className:"bang-tip-body",value:r,onChange:e=>n({content:e}),placeholder:(0,a.__)("写下提示内容…","bang")})]})]})},save:()=>null}},k={metadata:JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"bang/fold","version":"0.13.0","title":"折叠内容","category":"bang-blocks","icon":"arrow-down-alt2","description":"一段默认收起、点击展开的内容，可设置默认展开。","keywords":["折叠","展开","details","fold"],"textdomain":"bang","supports":{"align":false,"anchor":true,"className":true},"attributes":{"title":{"type":"string","default":"点击展开"},"content":{"type":"string","default":""},"open":{"type":"boolean","default":false}},"example":{"attributes":{"title":"点击展开","content":"这里是被折叠的内容。"}}}'),settings:{edit:function({attributes:e,setAttributes:n}){const{title:i="",content:o="",open:r=!1}=e,p=(0,t.useBlockProps)({className:"bang-fold is-editor"});return(0,l.jsxs)(l.Fragment,{children:[(0,l.jsx)(t.InspectorControls,{children:(0,l.jsx)(s.PanelBody,{title:(0,a.__)("折叠设置","bang"),initialOpen:!0,children:(0,l.jsx)(s.ToggleControl,{label:(0,a.__)("默认展开","bang"),checked:r,onChange:e=>n({open:e})})})}),(0,l.jsxs)("details",{...p,open:r||void 0,children:[(0,l.jsx)("summary",{className:"bang-fold-sum",children:(0,l.jsx)(t.RichText,{tagName:"span",className:"bang-fold-title",value:i,onChange:e=>n({title:e}),placeholder:(0,a.__)("点击展开","bang"),allowedFormats:[]})}),(0,l.jsx)(t.RichText,{tagName:"div",className:"bang-fold-body",value:o,onChange:e=>n({content:e}),placeholder:(0,a.__)("被折叠的内容…","bang")})]})]})},save:()=>null}},m=JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"bang/code","version":"0.13.0","title":"代码块","category":"bang-blocks","icon":"editor-code","description":"带语言标签与一键复制的代码块，语言留空时自动识别。","keywords":["代码","code","复制","高亮"],"textdomain":"bang","supports":{"align":false,"anchor":true,"className":true},"attributes":{"lang":{"type":"string","default":""},"file":{"type":"string","default":""},"content":{"type":"string","default":""}},"example":{"attributes":{"lang":"php","content":"<?php\\necho \'Hello Bang\';"}}}'),h=[["",(0,a.__)("自动识别","bang")],["php","PHP"],["js","JavaScript"],["ts","TypeScript"],["css","CSS"],["html","HTML"],["python","Python"],["bash","Bash"],["json","JSON"],["sql","SQL"],["go","Go"],["rust","Rust"],["java","Java"],["text","Text"]],g={metadata:m,settings:{edit:function({attributes:e,setAttributes:n}){const{lang:i="",file:o="",content:r=""}=e,p=(0,t.useBlockProps)({className:"bang-code is-editor"}),c=h.find(e=>e[0]===i);return(0,l.jsxs)(l.Fragment,{children:[(0,l.jsx)(t.InspectorControls,{children:(0,l.jsxs)(s.PanelBody,{title:(0,a.__)("代码块设置","bang"),initialOpen:!0,children:[(0,l.jsx)(s.TextControl,{label:(0,a.__)("语言","bang"),value:i,help:(0,a.__)("填 php / js / css / python / bash / json / sql…，留空自动识别。","bang"),onChange:e=>n({lang:e.replace(/[^a-z0-9+#_-]/gi,"").toLowerCase()})}),(0,l.jsx)(s.TextControl,{label:(0,a.__)("文件名（可选）","bang"),value:o,onChange:e=>n({file:e})})]})}),(0,l.jsxs)("div",{...p,children:[(0,l.jsxs)("div",{className:"bang-code-bar",children:[(0,l.jsx)("span",{className:"bang-code-lang",children:c?c[1]:i.toUpperCase()}),o?(0,l.jsx)("span",{className:"bang-code-file",children:o}):null]}),(0,l.jsx)(t.PlainText,{className:"bang-code-pre",value:r,onChange:e=>n({content:e}),placeholder:'<?php\necho "Hello Bang";',"aria-label":(0,a.__)("代码内容","bang")})]})]})},save:()=>null}},x=JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"bang/unlock","version":"0.13.32","title":"阅读解锁","category":"bang-blocks","icon":"lock","description":"把一段内容锁起来：作者四选一（点赞 / 评论 / 密码 / 入群），读者只能用选定的方式解锁；选「密码可见」可直接在区块上设置专属密码。","keywords":["解锁","点赞可见","评论可见","密码可见","入群可见","unlock","lock"],"textdomain":"bang","supports":{"align":false,"anchor":true,"className":true},"attributes":{"title":{"type":"string","default":""},"desc":{"type":"string","default":""},"teaser":{"type":"string","default":""},"content":{"type":"string","default":""},"method":{"type":"string","default":"group","enum":["like","comment","password","group"]},"password":{"type":"string","default":""},"locked":{"type":"boolean","default":true}},"example":{"attributes":{"method":"group","teaser":"前面的内容所有人都能看到……","content":"这一段需按上方选定的方式解锁后可见。"}}}'),_=[{label:(0,a.__)("点赞可见","bang"),value:"like"},{label:(0,a.__)("评论可见","bang"),value:"comment"},{label:(0,a.__)("密码可见","bang"),value:"password"},{label:(0,a.__)("入群可见","bang"),value:"group"}];[i,o,r,p,u,k,g].forEach(a=>{(0,e.registerBlockType)(a.metadata,a.settings)})})();

/* bang/timeline 编辑器注册（源码无 node_modules，手工追加，独立于上方构建产物） */
(function(){
	var wp = window.wp;
	if (!wp || !wp.blocks || !wp.element || !wp.blockEditor || !wp.components || !wp.i18n) { return; }
	var b = wp.blocks, el = wp.element.createElement, be = wp.blockEditor, c = wp.components, __ = wp.i18n.__;
	var meta = {
		apiVersion: 3, name: "bang/timeline", title: "时间轴", category: "bang-blocks", icon: "calendar",
		description: "逐条填日期、标题、说明，不用记格式；竖线 + 圆点的极简时间线。",
		keywords: ["时间轴", "大事记", "版本", "timeline"], textdomain: "bang",
		supports: { align: ["wide", "full"], anchor: true, className: true },
		attributes: { text: { type: "string", default: "" } }
	};
	function stripTime(v){ return String(v || "").trim().replace(/[\sT]+\d{1,2}:\d{2}(?::\d{2})?\s*$/, "").trim(); }
	function toPickerDate(value){
		var v = stripTime(value);
		if (/^\d{4}-\d{2}-\d{2}$/.test(v)) { return v; }
		if (/^\d{4}-\d{2}$/.test(v)) { return v + "-01"; }
		var now = new Date(), p = function(n){ return String(n).padStart(2, "0"); };
		return now.getFullYear() + "-" + p(now.getMonth() + 1) + "-" + p(now.getDate());
	}
	function parseItems(text){
		return String(text || "").split(/\r\n|\r|\n/).filter(function(l){ return l.trim(); }).map(function(line){
			var p = String(line).split(/[|｜]/);
			return { date: stripTime(p[0] || ""), title: (p[1] || "").trim(), desc: p.slice(2).join("|").trim() };
		});
	}
	function serialize(items){
		return items.map(function(it){
			return [it.date, it.title, it.desc].map(function(v){ return String(v || "").replace(/\s*[|｜]\s*/g, "、"); }).join("|");
		}).join("\n");
	}
	function preview(text){
		var items = parseItems(text);
		if (!items.length) { return el("p", { className: "bang-timeline-empty", style: { margin: 0, fontSize: 12, opacity: .55 } }, "填写内容后这里显示预览"); }
		return el("div", { className: "bang-timeline" }, items.map(function(it, i){
			return el("div", { className: "bang-timeline-item", key: i },
				el("span", { className: "bang-timeline-dot" }),
				el("div", { className: "bang-timeline-body" },
					it.date ? el("time", { className: "bang-timeline-date" }, it.date) : null,
					it.title ? el("p", { className: "bang-timeline-title" }, it.title) : null,
					it.desc ? el("p", { className: "bang-timeline-desc" }, it.desc) : null
				)
			);
		}));
	}
	function Edit(props){
		var text = props.attributes.text || "", items = parseItems(text);
		function update(i, k, v){
			var next = items.map(function(it, idx){ return idx === i ? Object.assign({}, it, function(){ var o = {}; o[k] = v; return o; }()) : it; });
			props.setAttributes({ text: serialize(next) });
		}
		function add(){ props.setAttributes({ text: serialize(items.concat([{ date: "", title: "", desc: "" }])) }); }
		function remove(i){ props.setAttributes({ text: serialize(items.filter(function(_, idx){ return idx !== i; })) }); }
		return el("div", be.useBlockProps({ className: "bang-timeline-editor" }),
			items.length ? el("div", { className: "bang-tl-rows" }, items.map(function(it, i){
				return el("div", { className: "bang-tl-row", key: i },
					el("span", { className: "bang-tl-index" }, String(i + 1)),
					el("div", { className: "bang-tl-fields" },
						el("div", { className: "bang-tl-date" },
							el("span", { className: "bang-tl-date-label" }, __("日期", "bang")),
							el(c.Dropdown, {
								className: "bang-tl-date-dropdown", contentClassName: "bang-tl-date-popover",
								renderToggle: function(a){
									return el(c.Button, { className: "bang-tl-date-toggle", isSecondary: true, "aria-expanded": a.isOpen, onClick: a.onToggle }, it.date || __("选择日期", "bang"));
								},
								renderContent: function(a){
									return el("div", { className: "bang-tl-date-content" },
										el(c.DatePicker, { currentDate: toPickerDate(it.date), onChange: function(v){ update(i, "date", v); a.onClose(); } }),
										it.date ? el(c.Button, { className: "bang-tl-date-clear", isLink: true, isDestructive: true, onClick: function(){ update(i, "date", ""); a.onClose(); } }, __("清除日期", "bang")) : null
									);
								}
							})
						),
						el(c.TextControl, { label: __("标题", "bang"), value: it.title, placeholder: __("如：上线", "bang"), onChange: function(v){ update(i, "title", v); } }),
						el(c.TextControl, { label: __("说明", "bang"), value: it.desc, placeholder: __("可留空", "bang"), onChange: function(v){ update(i, "desc", v); } })
					),
					el(c.Button, { className: "bang-tl-remove", onClick: function(){ remove(i); }, "aria-label": __("删除这一条", "bang") }, __("删除", "bang"))
				);
			})) : null,
			el(c.Button, { className: "bang-tl-add", variant: "secondary", onClick: add }, __("＋ 添加一条", "bang")),
			text ? el("div", { className: "bang-timeline-preview" }, preview(text)) : null
		);
	}
	b.registerBlockType(meta, { edit: Edit, save: function(){ return null; } });
})();

/* bang/postcard 编辑器注册（手工追加，独立于上方构建产物） */
(function(){
	var wp = window.wp;
	if (!wp || !wp.blocks || !wp.element || !wp.blockEditor || !wp.components || !wp.data) { return; }
	var b = wp.blocks, el = wp.element.createElement, be = wp.blockEditor, c = wp.components;
	b.registerBlockType({
		apiVersion: 3, name: "bang/postcard", title: "文章卡片", category: "bang-blocks", icon: "admin-post",
		description: "从站内文章中选择，自动带出封面、标题与摘要。",
		keywords: ["文章", "卡片", "推荐", "postcard"], textdomain: "bang",
		supports: { align: ["wide", "full"], anchor: true, className: true },
		attributes: { target: { type: "string", default: "" }, title: { type: "string", default: "" }, desc: { type: "string", default: "" } }
	}, {
		edit: function(props){
			var a = props.attributes, set = props.setAttributes;
			var text = a.target || "", title = a.title || "", desc = a.desc || "";
			var posts = wp.data.useSelect(function(select){
				return select("core").getEntityRecords("postType", "post", {
					per_page: 100, orderby: "date", order: "desc", _fields: "id,title"
				}) || [];
			}, []);
			var options = posts.map(function(post){
				var raw = post.title && post.title.rendered ? post.title.rendered : ("#" + post.id);
				return { value: String(post.id), label: String(raw).replace(/<[^>]*>/g, "") };
			});
			var picked = options.filter(function(o){ return o.value === String(text); })[0];
			return el("div", be.useBlockProps({ className: "bang-postcard-editor" }),
				el(c.ComboboxControl, {
					label: "选择文章", value: String(text), options: options,
					help: "从站内文章中选择；封面、标题、摘要与日期自动读取。",
					onChange: function(v){ set({ target: String(v || "") }); }
				}),
				el(c.TextControl, { label: "自定义标题（可选）", value: title, onChange: function(v){ set({ title: v }); } }),
				el(c.TextareaControl, { label: "自定义摘要（可选）", value: desc, rows: 2, onChange: function(v){ set({ desc: v }); } }),
				text ? el("div", { className: "bang-postcard-preview" },
					el("p", { className: "bang-postcard-preview-title" }, title || (picked ? picked.label : "（前台自动读取文章标题）")),
					el("p", { className: "bang-postcard-preview-desc" }, desc || "封面、摘要与日期在前台自动读取。")
				) : null
			);
		},
		save: function(){ return null; }
	});
})();

/* bang/unlock 编辑器注册（手工追加，五种方式：登录 / 点赞 / 回复 / 密码 / 点赞+评论；入群已屏蔽） */
(function(){
	var wp = window.wp;
	if (!wp || !wp.blocks || !wp.element || !wp.blockEditor || !wp.components || !wp.i18n) { return; }
	var b = wp.blocks, el = wp.element.createElement, be = wp.blockEditor, c = wp.components, __ = wp.i18n.__;
	var methods = [
		{ label: __("登录可见", "bang"), value: "login" },
		{ label: __("点赞可见", "bang"), value: "like" },
		{ label: __("回复可见", "bang"), value: "comment" },
		{ label: __("密码可见", "bang"), value: "password" },
		{ label: __("点赞+评论可见", "bang"), value: "combo" }
	];
	function randomPassword(){
		var a = Math.random().toString(36).slice(2, 10);
		var bb = Date.now().toString(36).slice(-6);
		return ("bang" + a + bb).slice(0, 16);
	}
	var meta = {
		apiVersion: 3, name: "bang/unlock", title: "阅读解锁", category: "bang-blocks", icon: "lock",
		description: "把一段内容锁起来：作者五选一（登录 / 点赞 / 回复 / 密码 / 点赞+评论），读者只能用选定的方式解锁。选「密码可见」可设专属密码；「点赞+评论可见」两步都完成才放行。入群已屏蔽。",
		keywords: ["解锁", "登录可见", "点赞可见", "回复可见", "密码可见", "叠加解锁", "unlock", "lock"], textdomain: "bang",
		supports: { align: false, anchor: true, className: true },
		attributes: {
			title: { type: "string", default: "" },
			desc: { type: "string", default: "" },
			teaser: { type: "string", default: "" },
			content: { type: "string", default: "" },
			method: { type: "string", default: "login", enum: ["login", "like", "comment", "password", "combo"] },
			password: { type: "string", default: "" },
			locked: { type: "boolean", default: true }
		}
	};
	function Edit(props){
		var a = props.attributes, set = props.setAttributes;
		var method = a.method || "login";
		var password = a.password || "";
		var current = methods.filter(function(m){ return m.value === method; })[0] || methods[0];
		var isPassword = method === "password";
		var setPassword = function(v){ set({ password: v }); };
		var passwordRow = isPassword ? el("div", { className: "bang-unlock-editor-pass" },
			el("span", { className: "bang-unlock-editor-pass-label" }, __("设置解锁密码（本区块专属）", "bang")),
			el("div", { className: "bang-unlock-editor-pass-row" },
				el("input", {
					type: "text", className: "bang-unlock-editor-pass-input", value: password,
					onChange: function(e){ setPassword(e.target.value); },
					placeholder: __("留空则使用后台站点解锁密码", "bang"), "aria-label": __("解锁密码", "bang"), maxLength: "64"
				}),
				el("button", { type: "button", className: "bang-unlock-editor-pass-gen", onClick: function(){ setPassword(randomPassword()); } }, __("生成密码", "bang"))
			),
			el("span", { className: "bang-unlock-editor-pass-help" }, password
				? __("读者必须输入上面这串密码才能读到这段内容；密码只保存在文章里，不会下发到前台。", "bang")
				: __("不填则用后台「站点解锁密码」或本文自定义字段 bang_unlock_password。", "bang"))
		) : null;
		return el(wp.element.Fragment, null,
			el(be.InspectorControls, null,
				el(c.PanelBody, { title: __("阅读解锁设置", "bang"), initialOpen: true },
					el(c.SelectControl, {
						label: __("解锁方式（五选一）", "bang"), value: method,
						options: methods,
						onChange: function(v){ set({ method: v }); },
						help: __("读者只能用选定的这一种方式解锁；其它方式既不会出现在卡片上，也不会放行。叠加解锁需点赞 + 评论两步都完成；入群已屏蔽。", "bang")
					}),
					isPassword ? el(c.TextControl, {
						label: __("解锁密码（本区块专属）", "bang"), value: password,
						onChange: setPassword, placeholder: __("留空则使用后台站点解锁密码", "bang"),
						help: __("只作用于这一个区块；前台不会输出明文密码。留空则回退到站点解锁密码。", "bang")
					}) : null,
					el(c.TextControl, { label: __("锁定卡片标题", "bang"), value: a.title || "", placeholder: __("内容已锁定", "bang"), onChange: function(v){ set({ title: v }); } }),
					el(c.TextControl, { label: __("锁定卡片说明", "bang"), value: a.desc || "", onChange: function(v){ set({ desc: v }); } })
				)
			),
			el("div", be.useBlockProps({ className: "bang-unlock is-editor" }),
				el("div", { className: "bang-unlock-teaser" },
					el(be.RichText, { tagName: "div", value: a.teaser || "", onChange: function(v){ set({ teaser: v }); }, placeholder: __("引子：所有人都能看到的内容（可留空）", "bang") })
				),
				el("div", { className: "bang-unlock-editor-bar" },
					el("span", { className: "bang-unlock-editor-tag" }, current.label),
					el("span", { className: "bang-unlock-editor-hint" }, __("以下为受保护内容，前台未解锁时不会输出", "bang"))
				),
				el("div", { className: "bang-unlock-editor-methods" },
					el("span", { className: "bang-unlock-editor-methods-label" }, __("解锁方式（五选一）", "bang")),
					el("div", { className: "bang-unlock-editor-methods-row" }, methods.map(function(m){
						return el("button", {
							key: m.value, type: "button",
							className: "bang-unlock-editor-method" + (m.value === method ? " is-active" : ""),
							"aria-pressed": m.value === method,
							onClick: function(){ set({ method: m.value }); }
						}, m.label);
					}))
				),
				passwordRow,
				el("div", { className: "bang-unlock-editor-content" },
					el(be.RichText, { tagName: "div", value: a.content || "", onChange: function(v){ set({ content: v }); }, placeholder: __("写解锁后才可读的内容…", "bang") })
				)
			)
		);
	}
	b.registerBlockType(meta, { edit: Edit, save: function(){ return null; } });
})();
/* bang/download 编辑器注册（手工追加，独立 IIFE；只用 window.wp 全局） */
(function(){
	var wp = window.wp;
	if (!wp || !wp.blocks || !wp.element || !wp.blockEditor || !wp.components || !wp.i18n) { return; }
	var b = wp.blocks, el = wp.element.createElement, be = wp.blockEditor, c = wp.components, __ = wp.i18n.__;
	var methods = [
		{ label: __("登录可见", "bang"), value: "login" },
		{ label: __("点赞可见", "bang"), value: "like" },
		{ label: __("回复可见", "bang"), value: "comment" },
		{ label: __("密码可见", "bang"), value: "password" },
		{ label: __("点赞+评论可见", "bang"), value: "combo" }
	];
	function parseItems(text){
		return String(text || "").split("\n").map(function(l){ return l.trim(); }).filter(Boolean).map(function(line){
			var p = line.split("|");
			return { name: (p[0] || "").trim(), url: (p[1] || "").trim(), code: (p[2] || "").trim() };
		});
	}
	function serialize(items){
		return items.map(function(it){
			var clean = function(s){ return String(s || "").replace(/[|｜\n\r]/g, "、").trim(); };
			return [clean(it.name), clean(it.url), clean(it.code)].join("|");
		}).filter(function(line){ return line.replace(/\|/g, "").trim() !== ""; }).join("\n");
	}
	var meta = {
		apiVersion: 3, name: "bang/download", title: "下载", category: "bang-blocks", icon: "download",
		description: "网盘 / 直链下载列表，每行一个「名称|链接|提取码」。支持五选一解锁方式，未解锁时服务端不输出下载地址。",
		keywords: ["下载", "网盘", "直链", "提取码", "download"], textdomain: "bang",
		supports: { align: false, anchor: true, className: true },
		attributes: {
			title: { type: "string", default: "" },
			desc: { type: "string", default: "" },
			teaser: { type: "string", default: "" },
			items: { type: "string", default: "" },
			method: { type: "string", default: "login", enum: ["login", "like", "comment", "password", "combo"] },
			password: { type: "string", default: "" },
			locked: { type: "boolean", default: true }
		}
	};
	function Edit(props){
		var a = props.attributes, set = props.setAttributes;
		var method = a.method || "login", password = a.password || "", items = a.items || "";
		var rows = parseItems(items);
		var current = methods.filter(function(m){ return m.value === method; })[0] || methods[0];
		var isPassword = method === "password";
		var update = function(next){ set({ items: serialize(next) }); };
		var changeRow = function(i, field, value){
			update(rows.map(function(r, idx){ return idx === i ? Object.assign({}, r, (function(){ var o = {}; o[field] = value; return o; })()) : r; }));
		};
		var removeRow = function(i){ update(rows.filter(function(_, idx){ return idx !== i; })); };
		var addRow = function(){ update(rows.concat([{ name: "", url: "", code: "" }])); };
		return el(wp.element.Fragment, null,
			el(be.InspectorControls, null,
				el(c.PanelBody, { title: __("下载区块设置", "bang"), initialOpen: true },
					el(c.SelectControl, {
						label: __("解锁方式（五选一）", "bang"), value: method, options: methods,
						onChange: function(v){ set({ method: v }); },
						help: __("读者只能用选定的方式解锁后才能看到下载地址。叠加解锁需点赞 + 评论两步都完成；入群已屏蔽。", "bang")
					}),
					isPassword ? el(c.TextControl, {
						label: __("解锁密码（本区块专属）", "bang"), value: password,
						onChange: function(v){ set({ password: v }); },
						placeholder: __("留空则使用后台站点解锁密码", "bang"),
						help: __("只作用于这一个区块；前台不会输出明文密码。", "bang")
					}) : null,
					el(c.TextControl, { label: __("卡片标题", "bang"), value: a.title || "", onChange: function(v){ set({ title: v }); }, placeholder: __("资源下载", "bang") }),
					el(c.TextControl, { label: __("卡片说明", "bang"), value: a.desc || "", onChange: function(v){ set({ desc: v }); } }),
					el(c.TextControl, { label: __("引子（锁定前可见）", "bang"), value: a.teaser || "", onChange: function(v){ set({ teaser: v }); } })
				)
			),
			el("div", be.useBlockProps({ className: "bang-download is-editor" }),
				el("div", { className: "bang-download-editor-bar" },
					el("span", { className: "bang-download-editor-tag" }, current.label),
					el("span", { className: "bang-download-editor-hint" }, __("读者按此方式解锁后才能看到下面的下载地址", "bang"))
				),
				el("div", { className: "bang-download-editor-rows" }, rows.map(function(row, i){
					return el("div", { className: "bang-download-editor-row", key: i },
						el("span", { className: "bang-download-editor-index" }, i + 1),
						el("div", { className: "bang-download-editor-fields" },
							el(c.TextControl, { value: row.name, onChange: function(v){ changeRow(i, "name", v); }, placeholder: __("名称，如 百度网盘", "bang") }),
							el(c.TextControl, { value: row.url, onChange: function(v){ changeRow(i, "url", v); }, placeholder: __("下载链接 http(s)://…", "bang") }),
							el(c.TextControl, { value: row.code, onChange: function(v){ changeRow(i, "code", v); }, placeholder: __("提取码（可选）", "bang") })
						),
						el("button", { type: "button", className: "bang-download-editor-remove", onClick: function(){ removeRow(i); } }, __("删除", "bang"))
					);
				})),
				el("button", { type: "button", className: "bang-download-editor-add", onClick: addRow }, __("＋ 添加一个下载地址", "bang")),
				el("p", { className: "bang-download-editor-help" }, __("每行一条：填名称、完整下载链接，网盘可填提取码。未解锁时前台只显示锁定卡片，不输出任何地址。", "bang"))
			)
		);
	}
	b.registerBlockType(meta, { edit: Edit, save: function(){ return null; } });
})();
