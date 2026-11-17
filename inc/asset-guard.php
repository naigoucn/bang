<?php
/**
 * 资源自检哨兵（0.12.1 / 0.13.3 去掉下载）
 *
 * 场景：CDN / WAF / 网络抖动导致 style.css、app.js 没能加载，页面会裸奔。
 * 方案：footer 输出一段完全自包含的内联样式 + 内联脚本（不引用任何外部资源），
 *       load 后自检 CSS 变量与 app.js 的启动标记；失败才提示，让访客刷新重试。
 *       0.13.3 起不再提供「下载资源包」，也不再输出 ?pk_asset_pack=1 端点。
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * swup.umd.js 偶发加载失败时同步补一份（带缓存绕过参数）。
 * 两个脚本都在 footer 同步输出、没有 async/defer，onerror 里用 document.write
 * 插入的脚本会在 app.js 之前解析执行，保证 window.Swup 在 app.js 初始化时已就绪，
 * 否则 app.js 会「安静地」跳过 Swup 初始化，整站链接偷偷退化成整页刷新。
 */
function pk_swup_retry_tag( $tag, $handle, $src ) {
	if ( 'swup' !== $handle || false === strpos( $tag, '<script ' ) ) {
		return $tag;
	}
	$sep   = false === strpos( $src, '?' ) ? '?' : '&';
	$retry = $src . $sep . 'pk_retry=1';
	$js    = 'document.write(\'<script src="' . $retry . '"><\\/script>\')';
	return str_replace( '<script ', '<script onerror="' . esc_attr( $js ) . '" ', $tag );
}
add_filter( 'script_loader_tag', 'pk_swup_retry_tag', 10, 3 );

/**
 * footer 常驻的自包含提示卡（内联样式 + 内联脚本，零外部依赖）。
 * pjax 只换 #swup-wrapper，这段在 wrapper 外，全站只输出一次。
 */
function pk_asset_guard_html() {
	if ( ! pk_opt( 'asset_guard' ) ) {
		return '';
	}
	ob_start();
	?>
<style id="pk-ag-style">
.pk-ag{position:fixed;right:24px;bottom:24px;z-index:2147483647;width:360px;max-width:calc(100vw - 32px);box-sizing:border-box;display:flex;gap:12px;padding:18px 16px 16px;border:1px solid #E5E5E5;border-radius:12px;background:#FFF;color:#000;text-align:left;font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI","PingFang SC","Microsoft YaHei",sans-serif}
.pk-ag *{box-sizing:border-box}
.pk-ag[hidden]{display:none}
.pk-ag-ico{flex:0 0 32px;width:32px;height:32px;border-radius:10px;background:#000;color:#FFF;display:flex;align-items:center;justify-content:center}
.pk-ag-ico svg{width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.pk-ag-body{flex:1 1 auto;min-width:0}
.pk-ag-title{margin:0 0 5px;font-size:14px;font-weight:600;line-height:1.4;color:inherit}
.pk-ag-desc{margin:0;color:#666;font-size:12px;line-height:1.65}
.pk-ag-actions{display:flex;align-items:center;gap:14px;margin-top:13px}
.pk-ag-btn{display:inline-flex;align-items:center;justify-content:center;height:32px;padding:0 14px;border:0;border-radius:8px;background:#000;color:#FFF;text-decoration:none;font-family:inherit;font-size:12px;font-weight:600;white-space:nowrap;cursor:pointer}
.pk-ag-btn:hover,.pk-ag-btn:focus-visible{background:#333;color:#FFF;text-decoration:none}
.pk-ag-later{border:0;background:none;padding:0;color:#999;font-size:12px;line-height:1;cursor:pointer;font-family:inherit}
.pk-ag-later:hover{color:#000}
.pk-ag-x{position:absolute;top:8px;right:8px;width:24px;height:24px;display:flex;align-items:center;justify-content:center;border:0;border-radius:6px;background:none;color:#999;cursor:pointer}
.pk-ag-x:hover{background:#F5F5F5;color:#000}
.pk-ag-x svg{width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round}
@media (max-width:600px){.pk-ag{left:12px;right:12px;bottom:12px;width:auto}}
@media (prefers-color-scheme:dark){.pk-ag{background:#111;border-color:#333;color:#FFF}.pk-ag-ico{background:#FFF;color:#000}.pk-ag-desc{color:#AAA}.pk-ag-btn{background:#FFF;color:#000}.pk-ag-btn:hover,.pk-ag-btn:focus-visible{background:#DDD;color:#000}.pk-ag-later:hover{color:#FFF}.pk-ag-x:hover{background:#222;color:#FFF}}
</style>
<div class="pk-ag" id="pk-asset-guard" role="alert" aria-live="polite" hidden>
	<span class="pk-ag-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-2.6-6.4"/><path d="M21 4v5h-5"/></svg></span>
	<div class="pk-ag-body">
		<p class="pk-ag-title">页面资源没加载成功</p>
		<p class="pk-ag-desc" id="pk-ag-desc">样式或脚本没能加载，页面可能显示不完整。刷新一次通常就能恢复。</p>
		<div class="pk-ag-actions">
			<button class="pk-ag-btn" id="pk-ag-retry" type="button">刷新重试</button>
			<button class="pk-ag-later" id="pk-ag-later" type="button">暂不</button>
		</div>
	</div>
	<button class="pk-ag-x" id="pk-ag-x" type="button" aria-label="关闭提示"><svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
</div>
<script>
(function(){
	var el=document.getElementById('pk-asset-guard');
	if(!el){return;}
	var KEY='pk.asset.guard.until';
	var store={
		get:function(k){try{return window.localStorage.getItem(k)}catch(e){return null}},
		set:function(k,v){try{window.localStorage.setItem(k,v)}catch(e){}}
	};
	/* 忽略后 7 天内不再打扰；整页加载都会静默自检（pjax 换页不重跑） */
	if(parseInt(store.get(KEY)||'0',10)>Date.now()){return;}
	var muted=false;
	function dismiss(){
		muted=true;
		store.set(KEY,String(Date.now()+7*24*60*60*1000));
		el.hidden=true;
	}
	function cssLoaded(){
		var byVar=false;
		try{
			var cs=window.getComputedStyle(document.documentElement);
			byVar=!!String(cs.getPropertyValue('--bg')||'').trim()&&!!String(cs.getPropertyValue('--ink')||'').trim();
		}catch(e){}
		if(byVar){return true;}
		/* 兜底：某些环境自定义属性取不到，就看名为 style*.css 的同源样式表有没有规则 */
		try{
			var sheets=document.styleSheets;
			for(var i=0;i<sheets.length;i++){
				var s=sheets[i];
				if(s.href&&/style(\.min)?\.css/.test(s.href)&&s.cssRules&&s.cssRules.length){return true;}
			}
		}catch(e){}
		return false;
	}
	function check(){
		var misses=[];
		if(!cssLoaded()){misses.push('样式表');}
		if(document.documentElement.getAttribute('data-pk-boot')!=='1'){misses.push('交互脚本');}
		if(document.documentElement.getAttribute('data-pk-swup')!=='1'){misses.push('页面切换脚本');}
		if(!misses.length){return;}
		if(muted){return;}
		var desc=document.getElementById('pk-ag-desc');
		if(desc){desc.textContent=misses.join('、')+'未能加载。页面可能显示不完整，刷新一次通常就能恢复。';}
		el.hidden=false;
	}
	var retry=document.getElementById('pk-ag-retry');
	var later=document.getElementById('pk-ag-later');
	var x=document.getElementById('pk-ag-x');
	if(retry){retry.addEventListener('click',function(){window.location.reload();});}
	if(later){later.addEventListener('click',dismiss);}
	if(x){x.addEventListener('click',dismiss);}
	document.addEventListener('keydown',function(e){
		if(!el.hidden&&(e.key==='Escape'||e.key==='Esc')){dismiss();}
	});
	function run(){window.setTimeout(check,1200);}
	if(document.readyState==='complete'){run();}
	else{window.addEventListener('load',run,{once:true});}
})();
</script>
	<?php
	return trim( (string) ob_get_clean() );
}
