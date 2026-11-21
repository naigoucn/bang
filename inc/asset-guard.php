<?php
/**
 * 资源自检哨兵（0.12.1 / 0.13.3 去掉下载）
 *
 * 场景：CDN / WAF / 网络抖动导致 style.css、app.js 没能加载，页面会裸奔。
 * 方案：footer 输出一段完全自包含的内联样式 + 内联脚本（不引用任何外部资源），
 *       load 后自检 CSS 变量与 app.js 的启动标记；失败才提示，让访客刷新重试。
 *       0.13.3 起不再提供「下载资源包」，也不再输出 ?bang_asset_pack=1 端点。
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
function bang_swup_retry_tag( $tag, $handle, $src ) {
	if ( 'swup' !== $handle || false === strpos( $tag, '<script ' ) ) {
		return $tag;
	}
	$sep   = false === strpos( $src, '?' ) ? '?' : '&';
	$retry = $src . $sep . 'bang_retry=1';
	$js    = 'document.write(\'<script src="' . $retry . '"><\\/script>\')';
	return str_replace( '<script ', '<script onerror="' . esc_attr( $js ) . '" ', $tag );
}
add_filter( 'script_loader_tag', 'bang_swup_retry_tag', 10, 3 );

/**
 * footer 常驻的自包含提示卡（内联样式 + 内联脚本，零外部依赖）。
 * pjax 只换 #swup-wrapper，这段在 wrapper 外，全站只输出一次。
 */
function bang_asset_guard_html() {
	if ( ! bang_opt( 'asset_guard' ) ) {
		return '';
	}
	ob_start();
	?>
<style id="bang-ag-style">
.bang-ag{position:fixed;right:24px;bottom:24px;z-index:2147483647;width:360px;max-width:calc(100vw - 32px);box-sizing:border-box;display:flex;gap:12px;padding:18px 16px 16px;border:1px solid #E5E5E5;border-radius:12px;background:#FFF;color:#000;text-align:left;font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI","PingFang SC","Microsoft YaHei",sans-serif}
.bang-ag *{box-sizing:border-box}
.bang-ag[hidden]{display:none}
.bang-ag-ico{flex:0 0 32px;width:32px;height:32px;border-radius:10px;background:#000;color:#FFF;display:flex;align-items:center;justify-content:center}
.bang-ag-ico svg{width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.bang-ag-body{flex:1 1 auto;min-width:0}
.bang-ag-title{margin:0 0 5px;font-size:14px;font-weight:600;line-height:1.4;color:inherit}
.bang-ag-desc{margin:0;color:#666;font-size:12px;line-height:1.65}
.bang-ag-actions{display:flex;align-items:center;gap:14px;margin-top:13px}
.bang-ag-btn{display:inline-flex;align-items:center;justify-content:center;height:32px;padding:0 14px;border:0;border-radius:8px;background:#000;color:#FFF;text-decoration:none;font-family:inherit;font-size:12px;font-weight:600;white-space:nowrap;cursor:pointer}
.bang-ag-btn:hover,.bang-ag-btn:focus-visible{background:#333;color:#FFF;text-decoration:none}
.bang-ag-later{border:0;background:none;padding:0;color:#999;font-size:12px;line-height:1;cursor:pointer;font-family:inherit}
.bang-ag-later:hover{color:#000}
.bang-ag-x{position:absolute;top:8px;right:8px;width:24px;height:24px;display:flex;align-items:center;justify-content:center;border:0;border-radius:6px;background:none;color:#999;cursor:pointer}
.bang-ag-x:hover{background:#F5F5F5;color:#000}
.bang-ag-x svg{width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round}
@media (max-width:600px){.bang-ag{left:12px;right:12px;bottom:12px;width:auto}}
@media (prefers-color-scheme:dark){.bang-ag{background:#111;border-color:#333;color:#FFF}.bang-ag-ico{background:#FFF;color:#000}.bang-ag-desc{color:#AAA}.bang-ag-btn{background:#FFF;color:#000}.bang-ag-btn:hover,.bang-ag-btn:focus-visible{background:#DDD;color:#000}.bang-ag-later:hover{color:#FFF}.bang-ag-x:hover{background:#222;color:#FFF}}
</style>
<div class="bang-ag" id="bang-asset-guard" role="alert" aria-live="polite" hidden>
	<span class="bang-ag-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3.67981 13L3.67981 11.3333C3.67981 6.73096 7.4402 3 12.0789 3C15.1178 3 17.7799 4.60136 19.2545 7M2 11.3333L3.67981 13L5.35962 11.3333" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/> <path d="M20.3139 11V12.6667C20.3139 17.269 16.5391 21 11.8827 21C8.83213 21 6.15995 19.3986 4.67969 17M22.0001 12.6667L20.3139 11L18.6277 12.6667" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/></svg></span>
	<div class="bang-ag-body">
		<p class="bang-ag-title">页面资源没加载成功</p>
		<p class="bang-ag-desc" id="bang-ag-desc">样式或脚本没能加载，页面可能显示不完整。刷新一次通常就能恢复。</p>
		<div class="bang-ag-actions">
			<button class="bang-ag-btn" id="bang-ag-retry" type="button">刷新重试</button>
			<button class="bang-ag-later" id="bang-ag-later" type="button">暂不</button>
		</div>
	</div>
	<button class="bang-ag-x" id="bang-ag-x" type="button" aria-label="关闭提示"><svg viewBox="0 0 24 24"><path d="M19.0068 5L5.00684 19" stroke-linecap="round" stroke="currentColor"/> <path d="M19 19L5 5" stroke-linecap="round" stroke="currentColor"/></svg></button>
</div>
<script>
(function(){
	var el=document.getElementById('bang-asset-guard');
	if(!el){return;}
	var KEY='bang.asset.guard.until';
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
		if(document.documentElement.getAttribute('data-bang-boot')!=='1'){misses.push('交互脚本');}
		if(document.documentElement.getAttribute('data-bang-swup')!=='1'){misses.push('页面切换脚本');}
		if(!misses.length){return;}
		if(muted){return;}
		var desc=document.getElementById('bang-ag-desc');
		if(desc){desc.textContent=misses.join('、')+'未能加载。页面可能显示不完整，刷新一次通常就能恢复。';}
		el.hidden=false;
	}
	var retry=document.getElementById('bang-ag-retry');
	var later=document.getElementById('bang-ag-later');
	var x=document.getElementById('bang-ag-x');
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
