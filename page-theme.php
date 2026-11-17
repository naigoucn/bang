<?php
/**
 * 主题介绍页（页面 slug: theme）
 *
 * 仿 zaxu.com/theme 的编排，但把 Bang 的招牌动效做成可播放的舞台：
 * Hero 实况自动演示「共享元素展开」，下方交互舞台可切换 6 种特效。
 * 样式与脚本随模板内联，只在本页加载，不污染全站。
 *
 * @package Bang
 */

get_header();

/* 换成自己的仓库 / 发行包地址即可，全页按钮都读这两个变量 */
$pk_repo = 'https://github.com/naigoucn/bang';
$pk_zip  = $pk_repo . '/releases/latest';

pk_page_open( '主题介绍', 'Bang · 永久免费的开源 WordPress 主题。' );
?>
<style>
/* 版式由全站 .pk-page-wide 统一控制（1180px 左对齐），本页不再单独设宽度 */
.pk-th{--line:#e5e5e5;--soft:#f7f7f7;--soft2:#efefef;--ink3:#666;--ink4:#999;--r:12px;color:#000;font-size:15px;line-height:1.75}
.pk-th *{box-sizing:border-box}
.pk-th a{text-decoration:none;color:inherit}
.pk-th a.pk-th-link{text-decoration:underline;text-underline-offset:3px}
.pk-th a.pk-th-link:hover{text-decoration-thickness:2px}
.pk-th img,.pk-th svg{display:block;max-width:100%}
.pk-th button{font:inherit;color:inherit;background:none;border:0;cursor:pointer}
.pk-th .pk-th-eyebrow{display:inline-flex;align-items:center;gap:9px;font-size:11.5px;letter-spacing:.18em;color:var(--ink4);text-transform:uppercase;font-weight:600}
.pk-th .pk-th-eyebrow::before{content:"";width:16px;height:1px;background:var(--ink4)}
.pk-th-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:44px;padding:0 24px;border-radius:var(--r);font-size:14px;font-weight:600;border:1px solid transparent;white-space:nowrap;transition:background .18s,border-color .18s,color .18s}
.pk-th-page a.pk-th-btn-dark{background:#000;color:#fff;border-color:#000}
.pk-th-page a.pk-th-btn-dark:hover{background:#262626}
.pk-th-page a.pk-th-btn-ghost{background:#fff;color:#000;border-color:var(--line)}
.pk-th-page a.pk-th-btn-ghost:hover{border-color:#000}

/* ---------- Hero ---------- */
.pk-th-hero{display:grid;grid-template-columns:1fr 1.06fr;gap:52px;align-items:center;padding:64px 0 60px;border-bottom:1px solid var(--line)}
.pk-th-hero h2{margin:18px 0 0;font-size:clamp(38px,5.4vw,66px);line-height:1.06;letter-spacing:-.045em;font-weight:800}
.pk-th-hero h2 span{display:block}
.pk-th-hero h2 em{font-style:normal;color:var(--ink4)}
.pk-th-hero p.pk-th-sub{margin:22px 0 0;max-width:460px;font-size:15.5px;color:#333}
.pk-th-hero p.pk-th-sub b{font-weight:700;color:#000}
.pk-th-tags{display:flex;flex-wrap:wrap;gap:8px;margin-top:22px}
.pk-th-tag{padding:5px 11px;border:1px solid var(--line);border-radius:100px;font-size:12px;color:var(--ink3)}
.pk-th-cta{display:flex;flex-wrap:wrap;gap:10px;margin-top:28px}
.pk-th-hero-foot{display:flex;flex-wrap:wrap;gap:22px;margin-top:34px;padding-top:20px;border-top:1px solid var(--line);font-size:12px;color:var(--ink4)}
.pk-th-hero-foot b{display:block;font-size:15px;font-weight:700;color:#000;letter-spacing:-.01em}

/* ---------- 实况窗口（Hero 右侧，自动播放） ---------- */
.pk-th-live{position:relative;border:1px solid var(--line);border-radius:14px;background:#fff;overflow:hidden}
.pk-th-live-bar{display:flex;align-items:center;gap:6px;padding:10px 14px;border-bottom:1px solid var(--line)}
.pk-th-live-bar i{width:7px;height:7px;border-radius:50%;background:var(--line)}
.pk-th-live-bar span{margin-left:10px;font-size:10.5px;color:var(--ink4);letter-spacing:.04em}
.pk-th-live-body{position:relative;height:330px;padding:18px;background:var(--soft);overflow:hidden}
.pk-th-feed{display:grid;gap:10px;transition:opacity .35s,transform .45s cubic-bezier(.2,.8,.2,1)}
.pk-th-fcard{display:grid;grid-template-columns:76px 1fr;gap:12px;align-items:center;padding:10px;border:1px solid var(--line);border-radius:10px;background:#fff}
.pk-th-fcard:nth-child(2){opacity:.55}
.pk-th-fcard:nth-child(3){opacity:.3}
.pk-th-fthumb{height:56px;border-radius:7px;background:var(--soft2)}
.pk-th-fcard:nth-child(1) .pk-th-fthumb{background:#d9d9d9}
.pk-th-ftext b{display:block;font-size:12.5px;font-weight:700}
.pk-th-ftext i{display:block;height:7px;margin-top:8px;width:78%;border-radius:4px;background:var(--soft2)}
.pk-th-ftext i+i{width:48%;margin-top:5px}
.pk-th-live.is-reading .pk-th-feed{opacity:0;transform:scale(.94) translateY(-6px)}
/* 阅读层：从卡片位置放大到整窗 */
.pk-th-reader{position:absolute;inset:14px;border-radius:12px;background:#fff;border:1px solid var(--line);padding:18px;opacity:0;pointer-events:none;transform:translateY(26px) scale(.88);transform-origin:50% 12%;transition:transform .55s cubic-bezier(.16,1,.3,1),opacity .3s}
.pk-th-live.is-reading .pk-th-reader{opacity:1;transform:translateY(0) scale(1)}
.pk-th-reader-img{height:112px;border-radius:9px;background:#d9d9d9}
.pk-th-reader h4{margin:16px 0 0;font-size:16px;font-weight:800;letter-spacing:-.02em}
.pk-th-reader p{margin:10px 0 0;font-size:11.5px;color:var(--ink3)}
.pk-th-rline{height:7px;margin-top:9px;border-radius:4px;background:var(--soft2)}
.pk-th-rline.w90{width:90%}.pk-th-rline.w65{width:65%}.pk-th-rline.w40{width:40%}
.pk-th-live-hint{position:absolute;right:12px;bottom:10px;display:flex;align-items:center;gap:7px;font-size:10.5px;color:var(--ink4);z-index:3}
.pk-th-pulse{width:6px;height:6px;border-radius:50%;background:#000;animation:pkThPulse 1.6s infinite}
@keyframes pkThPulse{0%,100%{opacity:.25;transform:scale(.8)}50%{opacity:1;transform:scale(1.25)}}
.pk-th-live-hint button{font-size:10.5px;color:var(--ink4);text-decoration:underline;text-underline-offset:2px}

/* ---------- 通用区块 ---------- */
.pk-th-sec{padding:72px 0;border-bottom:1px solid var(--line)}
.pk-th-sec:last-of-type{border-bottom:0}
.pk-th-sec-head{display:flex;align-items:flex-end;justify-content:space-between;gap:24px}
.pk-th-sec h3{margin:14px 0 0;font-size:clamp(26px,3.8vw,40px);line-height:1.18;letter-spacing:-.035em;font-weight:800}
.pk-th-sec p.pk-th-lead{margin:14px 0 0;max-width:640px;font-size:14.5px;color:var(--ink3)}
.pk-th-sec-note{font-size:12.5px;color:var(--ink4);text-align:right;max-width:260px}

/* ---------- 动效舞台 ---------- */
.pk-th-lab{display:grid;grid-template-columns:210px 1fr;gap:22px;margin-top:34px}
.pk-th-picks{display:grid;gap:2px;align-content:start}
.pk-th-pick{display:flex;align-items:center;gap:11px;width:100%;padding:12px 12px;border-radius:9px;text-align:left;font-size:13.5px;color:var(--ink3);transition:background .18s,color .18s}
.pk-th-pick:hover{background:var(--soft)}
.pk-th-pick.is-on{background:#000;color:#fff}
.pk-th-pick span{font-size:11px;font-variant-numeric:tabular-nums;opacity:.55}
.pk-th-pick.is-on span{opacity:.7}
.pk-th-stage{position:relative;border:1px solid var(--line);border-radius:14px;background:var(--soft);height:390px;overflow:hidden}
.pk-th-stage-bar{position:absolute;top:0;left:0;right:0;z-index:9;display:flex;align-items:center;gap:6px;height:32px;padding:0 12px;border-bottom:1px solid var(--line);background:rgba(255,255,255,.9)}
.pk-th-stage-bar i{width:6px;height:6px;border-radius:50%;background:var(--line)}
.pk-th-stage-bar em{margin-left:auto;font-style:normal;font-size:10.5px;color:var(--ink4);letter-spacing:.05em}
.pk-th-scene{position:absolute;inset:32px 0 0;display:none;overflow:hidden}
.pk-th-scene.is-on{display:block}
.pk-th-scene-tag{position:absolute;left:16px;bottom:12px;z-index:8;font-size:10.5px;color:var(--ink4);letter-spacing:.04em}

/* 1 共享元素展开 */
.pk-th-s1 .pk-th-mini{position:absolute;left:24px;right:24px;top:30px;padding:12px;border:1px solid var(--line);border-radius:10px;background:#fff;display:grid;grid-template-columns:64px 1fr;gap:12px;align-items:center}
.pk-th-s1 .pk-th-mini i{height:48px;border-radius:7px;background:#d9d9d9}
.pk-th-s1 .pk-th-mini b{display:block;font-size:12px}
.pk-th-s1 .pk-th-mini u{display:block;width:70%;height:6px;margin-top:7px;border-radius:3px;background:var(--soft2)}
.pk-th-s1 .pk-th-sheet{position:absolute;left:24px;right:24px;top:30px;bottom:34px;border:1px solid var(--line);border-radius:11px;background:#fff;padding:16px;opacity:0;transform:translateY(24px) scale(.86);transform-origin:50% 0}
.pk-th-s1.is-play .pk-th-mini{animation:pkThFadeOut .3s .25s forwards}
.pk-th-s1.is-play .pk-th-sheet{animation:pkThSheet 1.5s .28s cubic-bezier(.16,1,.3,1) forwards}
@keyframes pkThFadeOut{to{opacity:0;transform:scale(.94)}}
@keyframes pkThSheet{0%{opacity:0;transform:translateY(24px) scale(.86)}22%{opacity:1;transform:translateY(0) scale(1)}78%{opacity:1;transform:translateY(0) scale(1)}100%{opacity:0;transform:translateY(18px) scale(.94)}}
.pk-th-s1 .pk-th-sheet i{display:block;height:96px;border-radius:8px;background:#d9d9d9}
.pk-th-s1 .pk-th-sheet b{display:block;margin-top:14px;font-size:14px}
.pk-th-s1 .pk-th-sheet u{display:block;width:88%;height:6px;margin-top:9px;border-radius:3px;background:var(--soft2)}
.pk-th-s1 .pk-th-sheet u+u{width:60%}

/* 2 无刷新换页 */
.pk-th-s2 .pk-th-progress{position:absolute;top:0;left:0;height:2px;width:0;background:#000;z-index:5}
.pk-th-s2.is-play .pk-th-progress{animation:pkThProgress 1.5s ease-in-out forwards}
@keyframes pkThProgress{0%{width:0}65%{width:100%}100%{width:100%;opacity:0}}
.pk-th-s2 .pk-th-pagebox{position:absolute;inset:26px 24px 34px;border:1px solid var(--line);border-radius:10px;background:#fff;padding:16px}
.pk-th-s2.is-play .pk-th-pagebox{animation:pkThSwap 1.5s .18s forwards}
@keyframes pkThSwap{0%{opacity:.25;transform:translateY(10px)}30%{opacity:1;transform:translateY(0)}100%{opacity:1;transform:translateY(0)}}
.pk-th-s2 .pk-th-tab{display:inline-block;padding:4px 12px;border-radius:100px;background:#000;color:#fff;font-size:10px}
.pk-th-s2 .pk-th-pagebox i{display:block;height:8px;width:52%;margin-top:18px;border-radius:4px;background:var(--soft2)}
.pk-th-s2 .pk-th-pagebox i+i{width:88%;margin-top:8px}
.pk-th-s2 .pk-th-pagebox i+i+i{width:70%}
.pk-th-s2 .pk-th-blocks2{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:20px}
.pk-th-s2 .pk-th-blocks2 span{height:52px;border:1px solid var(--line);border-radius:7px;background:var(--soft)}

/* 3 抽屉 */
.pk-th-s3 .pk-th-mask{position:absolute;inset:0;background:rgba(0,0,0,.28);opacity:0}
.pk-th-s3.is-play .pk-th-mask{animation:pkThFadeInOut 1.6s .1s forwards}
@keyframes pkThFadeInOut{0%,100%{opacity:0}20%,80%{opacity:1}}
.pk-th-s3 .pk-th-drawer{position:absolute;top:0;bottom:0;left:0;width:62%;background:#fff;border-right:1px solid var(--line);padding:18px;transform:translateX(-101%)}
.pk-th-s3.is-play .pk-th-drawer{animation:pkThDrawer 1.6s .1s cubic-bezier(.16,1,.3,1) forwards}
@keyframes pkThDrawer{0%,8%{transform:translateX(-101%)}28%,80%{transform:translateX(0)}100%{transform:translateX(-101%)}}
.pk-th-s3 .pk-th-drawer b{display:block;font-size:13px;font-weight:800}
.pk-th-s3 .pk-th-drawer u{display:block;height:7px;width:70%;margin-top:12px;border-radius:4px;background:var(--soft2)}
.pk-th-s3 .pk-th-drawer em{display:block;margin-top:20px;padding:9px 0;border-top:1px solid var(--line);font-style:normal;font-size:11px;color:var(--ink3)}
.pk-th-s3 .pk-th-drawer em+em{border-top:0;padding-top:0;margin-top:10px}

/* 4 灯箱 */
.pk-th-s4 .pk-th-dim{position:absolute;inset:0;background:rgba(0,0,0,.82);opacity:0}
.pk-th-s4.is-play .pk-th-dim{animation:pkThFadeInOut 1.6s .1s forwards}
.pk-th-s4 .pk-th-photo{position:absolute;left:50%;top:50%;width:74%;height:60%;margin:-30% 0 0 -37%;border-radius:10px;background:#e8e8e8;opacity:0;transform:scale(.86)}
.pk-th-s4.is-play .pk-th-photo{animation:pkThPhoto 1.6s .15s cubic-bezier(.16,1,.3,1) forwards}
@keyframes pkThPhoto{0%,10%{opacity:0;transform:scale(.86)}30%,82%{opacity:1;transform:scale(1)}100%{opacity:0;transform:scale(.94)}}
.pk-th-s4 .pk-th-photo::after{content:"←　1 / 3　→";position:absolute;left:0;right:0;bottom:-28px;text-align:center;font-size:10.5px;color:#fff}

/* 5 命令面板 */
.pk-th-s5 .pk-th-cmdk{position:absolute;left:50%;top:46%;width:76%;margin:-70px 0 0 -38%;border:1px solid var(--line);border-radius:11px;background:#fff;padding:14px;opacity:0;transform:translateY(-8px) scale(.96)}
.pk-th-s5.is-play .pk-th-cmdk{animation:pkThCmdk 1.6s .1s cubic-bezier(.16,1,.3,1) forwards}
@keyframes pkThCmdk{0%{opacity:0;transform:translateY(-10px) scale(.96)}22%,82%{opacity:1;transform:translateY(0) scale(1)}100%{opacity:0;transform:translateY(-6px) scale(.98)}}
.pk-th-s5 .pk-th-cmdk input{width:100%;height:34px;padding:0 10px;border:1px solid var(--line);border-radius:7px;font-size:11px;background:var(--soft);outline:none}
.pk-th-s5 .pk-th-cmditem{display:flex;justify-content:space-between;padding:8px 2px;border-bottom:1px solid var(--line);font-size:10.5px;color:var(--ink3)}
.pk-th-s5 .pk-th-cmditem:last-child{border-bottom:0}
.pk-th-s5.is-play .pk-th-cmditem{animation:pkThItem .4s forwards;opacity:0}
.pk-th-s5.is-play .pk-th-cmditem:nth-child(2){animation-delay:.34s}
.pk-th-s5.is-play .pk-th-cmditem:nth-child(3){animation-delay:.44s}
.pk-th-s5.is-play .pk-th-cmditem:nth-child(4){animation-delay:.54s}
@keyframes pkThItem{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:translateY(0)}}

/* 6 幻灯片 */
.pk-th-s6 .pk-th-slide{position:absolute;inset:22px 24px 40px;border-radius:11px;overflow:hidden;background:#d9d9d9}
.pk-th-s6 .pk-th-slide i{position:absolute;inset:0;background:#cfcfcf}
.pk-th-s6 .pk-th-slide i+i{background:#b8b8b8;transform:translateX(100%)}
.pk-th-s6.is-play .pk-th-slide i:first-child{animation:pkThSlideA 1.6s forwards}
.pk-th-s6.is-play .pk-th-slide i+i{animation:pkThSlideB 1.6s forwards}
@keyframes pkThSlideA{0%,28%{transform:translateX(0)}55%,100%{transform:translateX(-100%)}}
@keyframes pkThSlideB{0%,28%{transform:translateX(100%)}55%,100%{transform:translateX(0)}}
.pk-th-s6 .pk-th-slidecap{position:absolute;left:0;right:0;bottom:0;padding:12px 14px;background:rgba(0,0,0,.44);color:#fff}
.pk-th-s6 .pk-th-slidecap b{display:block;font-size:12.5px}
.pk-th-s6 .pk-th-slidecap span{display:inline-block;margin-top:8px;padding:4px 12px;border-radius:6px;background:#fff;color:#000;font-size:10px;font-weight:600}
.pk-th-s6 .pk-th-dots{position:absolute;right:16px;bottom:14px;z-index:3;display:flex;gap:5px}
.pk-th-s6 .pk-th-dots i{width:5px;height:5px;border-radius:50%;background:rgba(255,255,255,.5)}
.pk-th-s6 .pk-th-dots i:first-child{background:#fff}

/* ---------- 特性编号列表 ---------- */
.pk-th-feats{margin-top:34px;border-top:1px solid var(--line)}
.pk-th-feat{display:grid;grid-template-columns:64px 1fr 200px;gap:20px;align-items:center;padding:22px 0;border-bottom:1px solid var(--line)}
.pk-th-feat-no{font-size:12px;font-weight:700;color:var(--ink4);font-variant-numeric:tabular-nums;letter-spacing:.06em}
.pk-th-feat h4{margin:0;font-size:18px;font-weight:800;letter-spacing:-.02em}
.pk-th-feat p{margin:6px 0 0;font-size:13px;color:var(--ink3);max-width:520px}
.pk-th-viz{height:56px;border:1px solid var(--line);border-radius:9px;background:var(--soft);position:relative;overflow:hidden}
.pk-th-viz.v1 i{position:absolute;left:10px;top:14px;width:28px;height:28px;border-radius:6px;background:#000;animation:pkThV1 2.6s infinite cubic-bezier(.16,1,.3,1)}
@keyframes pkThV1{0%,20%{transform:scale(1);border-radius:6px}50%,75%{transform:scale(2.1);border-radius:9px}100%{transform:scale(1);border-radius:6px}}
.pk-th-viz.v2 i{position:absolute;top:0;left:0;height:2px;width:40%;background:#000;animation:pkThV2 2.2s infinite ease-in-out}
@keyframes pkThV2{0%{left:-40%}60%,100%{left:100%}}
.pk-th-viz.v3 i{position:absolute;top:8px;bottom:8px;left:8px;width:44%;border-radius:6px;background:#000;animation:pkThV3 2.6s infinite cubic-bezier(.16,1,.3,1)}
@keyframes pkThV3{0%,18%{transform:translateX(-115%)}45%,80%{transform:translateX(0)}100%{transform:translateX(-115%)}}
.pk-th-viz.v4 i{position:absolute;inset:10px;border-radius:6px;background:#000;animation:pkThV4 2.4s infinite cubic-bezier(.16,1,.3,1)}
@keyframes pkThV4{0%,20%{transform:scale(.35);opacity:.25}50%,80%{transform:scale(1);opacity:1}100%{transform:scale(.35);opacity:.25}}
.pk-th-viz.v5{display:grid;place-items:center}
.pk-th-viz.v5 i{width:76%;height:26px;border:1px solid var(--line);border-radius:6px;background:#fff;animation:pkThV5 2.6s infinite}
@keyframes pkThV5{0%,20%{transform:translateY(8px) scale(.94);opacity:.3}45%,82%{transform:translateY(0) scale(1);opacity:1}100%{transform:translateY(8px) scale(.94);opacity:.3}}
.pk-th-viz.v6 i{position:absolute;inset:0;background:#000;animation:pkThV6 2.6s infinite}
@keyframes pkThV6{0%,25%{clip-path:inset(0 0 0 0)}50%,75%{clip-path:inset(0 0 0 52%)}100%{clip-path:inset(0 0 0 0)}}

/* ---------- 开源说明 ---------- */
.pk-th-free-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:32px}
.pk-th-free-card{padding:22px;border:1px solid var(--line);border-radius:var(--r)}
.pk-th-free-card b{display:block;font-size:15px;font-weight:800;letter-spacing:-.01em}
.pk-th-free-card p{margin:8px 0 0;font-size:13px;color:var(--ink3)}
.pk-th-note{margin-top:16px;font-size:12.5px;color:var(--ink4)}

/* ---------- 技术信息 / Q&A ---------- */
.pk-th-spec{margin-top:30px;border:1px solid var(--line);border-radius:var(--r);overflow:hidden}
.pk-th-spec div{display:grid;grid-template-columns:190px 1fr;gap:16px;padding:12px 18px;border-bottom:1px solid var(--line);font-size:13.5px}
.pk-th-spec div:last-child{border-bottom:0}
.pk-th-spec div:nth-child(odd){background:var(--soft)}
.pk-th-spec dt{color:var(--ink3)}
.pk-th-spec dd{margin:0}
.pk-th-subhead{margin:46px 0 0;font-size:21px;font-weight:800;letter-spacing:-.02em}
.pk-th-qa{margin-top:14px;border-top:1px solid var(--line)}
.pk-th-qa details{border-bottom:1px solid var(--line)}
.pk-th-qa summary{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:17px 0;font-size:14.5px;font-weight:700;cursor:pointer;list-style:none;letter-spacing:-.01em}
.pk-th-qa summary::-webkit-details-marker{display:none}
.pk-th-plus{position:relative;flex:0 0 18px;height:18px}
.pk-th-plus::before,.pk-th-plus::after{content:"";position:absolute;left:50%;top:50%;background:#000;transform:translate(-50%,-50%);transition:opacity .2s}
.pk-th-plus::before{width:11px;height:1.5px}
.pk-th-plus::after{width:1.5px;height:11px}
.pk-th-qa details[open] .pk-th-plus::after{opacity:0}
.pk-th-ans{padding:0 0 18px;max-width:820px;font-size:13.5px;color:var(--ink3)}

/* ---------- CTA ---------- */
.pk-th-end{padding:76px 0;text-align:center;background:var(--soft);border-top:1px solid var(--line)}
.pk-th-end h3{font-size:clamp(26px,4vw,40px);letter-spacing:-.035em;font-weight:800}
.pk-th-end p{margin:14px 0 0;font-size:14.5px;color:var(--ink3)}
.pk-th-end .pk-th-cta{justify-content:center}
.pk-th-end .pk-th-tip{margin-top:18px;font-size:12px;color:var(--ink4)}

/* ---------- 动效开关（尊重减少动画偏好） ---------- */
@media(prefers-reduced-motion:reduce){.pk-th *{animation:none!important;transition:none!important}}

@media(max-width:900px){
  .pk-th-hero{grid-template-columns:1fr;gap:34px;padding:44px 0 40px}
  .pk-th-live-body{height:280px}
  .pk-th-sec{padding:52px 0}
  .pk-th-lab{grid-template-columns:1fr;gap:14px}
  .pk-th-picks{grid-template-columns:repeat(3,1fr);gap:4px}
  .pk-th-pick{padding:9px;font-size:12px}
  .pk-th-stage{height:320px}
  .pk-th-feat{grid-template-columns:40px 1fr;gap:14px}
  .pk-th-viz{display:none}
  .pk-th-free-grid{grid-template-columns:1fr}
  .pk-th-sec-note{display:none}
}
@media(max-width:600px){
  .pk-th-hero h2{font-size:40px}
  .pk-th-picks{grid-template-columns:repeat(2,1fr)}
  .pk-th-stage{height:280px}
  .pk-th-spec div{grid-template-columns:1fr;gap:2px}
  .pk-th-cta .pk-th-btn{flex:1 1 100%}
  .pk-th-sec-head{display:block}
}
</style>

<div class="pk-th pk-th-page">

	<!-- ============ HERO ============ -->
	<div class="pk-th-hero">
		<div>
			<span class="pk-th-eyebrow">Bang · Open Source Theme</span>
			<h2><span>落笔有声</span><span><em>留白</em>有度</span></h2>
			<p class="pk-th-sub">为写作者造的开源主题。<b>纯白底、黑字、零打扰</b>——把注意力还给内容本身。点开卡片就地展开、换页不白屏、手机上滑出侧栏，动效全都长在内容里。</p>
			<div class="pk-th-tags">
				<span class="pk-th-tag">永久免费</span>
				<span class="pk-th-tag">GPL v2+</span>
				<span class="pk-th-tag">全站 PJAX</span>
				<span class="pk-th-tag">无 CDN 依赖</span>
			</div>
			<div class="pk-th-cta">
				<a class="pk-th-btn pk-th-btn-dark" href="<?php echo esc_url( $pk_zip ); ?>">免费下载主题</a>
				<a class="pk-th-btn pk-th-btn-ghost" href="<?php echo esc_url( $pk_repo ); ?>">查看开源源码</a>
			</div>
			<div class="pk-th-hero-foot">
				<div><b>0.13.27</b>当前版本</div>
				<div><b>7.4+</b>PHP 最低</div>
				<div><b>6.0+</b>WordPress</div>
				<div><b>1.8 MB</b>主题包</div>
			</div>
		</div>

		<!-- 实况演示：自动播放「共享元素展开」 -->
		<div class="pk-th-live" id="pk-th-live">
			<div class="pk-th-live-bar"><i></i><i></i><i></i><span>实况演示 · 共享元素转场</span></div>
			<div class="pk-th-live-body">
				<div class="pk-th-feed">
					<div class="pk-th-fcard"><div class="pk-th-fthumb"></div><div class="pk-th-ftext"><b>点开任意卡片，就地展开阅读</b><i></i><i></i></div></div>
					<div class="pk-th-fcard"><div class="pk-th-fthumb"></div><div class="pk-th-ftext"><b>读完关掉，缩回原来的位置</b><i></i><i></i></div></div>
					<div class="pk-th-fcard"><div class="pk-th-fthumb"></div><div class="pk-th-ftext"><b>手机上左右滑动，翻到下一篇</b><i></i><i></i></div></div>
				</div>
				<div class="pk-th-reader">
					<div class="pk-th-reader-img"></div>
					<h4>落笔有声，留白有度</h4>
					<p>阅读层从卡片本身长出来，没有跳转、没有白屏。</p>
					<div class="pk-th-rline w90"></div><div class="pk-th-rline w65"></div><div class="pk-th-rline w40"></div>
				</div>
				<div class="pk-th-live-hint"><i class="pk-th-pulse"></i>循环播放 <button type="button" id="pk-th-replay">重播</button></div>
			</div>
		</div>
	</div>

	<div class="pk-th-notice" style="display:flex;gap:12px;padding:16px 0;font-size:13px;color:var(--ink3);border-bottom:1px solid var(--line)">
		<svg style="flex:0 0 16px;margin-top:3px;stroke:var(--ink3)" viewBox="0 0 24 24" fill="none" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v.01M11 12h1v4h1"/></svg>
		<div>温馨提示：Bang 是<b>永久免费的开源主题</b>，无需授权码、不绑定域名。请从官方开源仓库 <a class="pk-th-link" href="<?php echo esc_url( $pk_repo ); ?>">github.com/naigoucn/bang</a> 获取最新版。<b>Typecho 与 Halo 版本同步开发中</b>，会把同一套设计与交互带到更多博客平台。演示站中的图片与文字均为演示用途。</div>
	</div>

	<!-- ============ 动效实验室 ============ -->
	<div class="pk-th-sec" id="pk-th-motion">
		<div class="pk-th-sec-head">
			<div>
				<span class="pk-th-eyebrow">Motion</span>
				<h3>动效不是装饰<br>是阅读的一部分</h3>
				<p class="pk-th-lead">点左边任意一项，右侧会真实播放该效果。全部由主题自带的 CSS 与少量 JS 实现，没有第三方动画库。</p>
			</div>
			<div class="pk-th-sec-note">共享元素转场 · 无刷新换页 · 抽屉 · 灯箱 · 命令面板 · 幻灯片</div>
		</div>

		<div class="pk-th-lab">
			<div class="pk-th-picks" id="pk-th-picks">
				<button type="button" class="pk-th-pick is-on" data-demo="s1"><span>01</span>共享元素展开</button>
				<button type="button" class="pk-th-pick" data-demo="s2"><span>02</span>无刷新换页</button>
				<button type="button" class="pk-th-pick" data-demo="s3"><span>03</span>手机侧栏抽屉</button>
				<button type="button" class="pk-th-pick" data-demo="s4"><span>04</span>沉浸灯箱</button>
				<button type="button" class="pk-th-pick" data-demo="s5"><span>05</span>⌘K 命令面板</button>
				<button type="button" class="pk-th-pick" data-demo="s6"><span>06</span>整卡压图幻灯片</button>
			</div>

			<div class="pk-th-stage" id="pk-th-stage">
				<div class="pk-th-stage-bar"><i></i><i></i><i></i><em id="pk-th-stage-label">共享元素展开</em></div>

				<div class="pk-th-scene pk-th-s1 is-on" data-scene="s1">
					<div class="pk-th-mini"><i></i><div><b>点开卡片，阅读层就地展开</b><u></u></div></div>
					<div class="pk-th-sheet"><i></i><b>落笔有声，留白有度</b><u></u><u></u></div>
					<span class="pk-th-scene-tag">卡片 → 阅读层 → 缩回原位</span>
				</div>

				<div class="pk-th-scene pk-th-s2" data-scene="s2">
					<div class="pk-th-progress"></div>
					<div class="pk-th-pagebox"><span class="pk-th-tab">首页</span><i></i><i></i><i></i><div class="pk-th-blocks2"><span></span><span></span><span></span></div></div>
					<span class="pk-th-scene-tag">顶部进度条 + 内容区淡入，零白屏</span>
				</div>

				<div class="pk-th-scene pk-th-s3" data-scene="s3">
					<div class="pk-th-mask"></div>
					<div class="pk-th-drawer"><b>Bang</b><u></u><em>首页</em><em>分类</em><em>关于本站</em><em>友情链接</em></div>
					<span class="pk-th-scene-tag">左侧滑出 · 遮罩淡入 · 点链接收起</span>
				</div>

				<div class="pk-th-scene pk-th-s4" data-scene="s4">
					<div class="pk-th-dim"></div>
					<div class="pk-th-photo"></div>
					<span class="pk-th-scene-tag" style="color:#fff">半透明背景 · 图片放大 · 键盘翻页</span>
				</div>

				<div class="pk-th-scene pk-th-s5" data-scene="s5">
					<div class="pk-th-cmdk"><input type="text" value="搜索文章、页面或命令…" readonly><div class="pk-th-cmditem"><span>⌘K　打开命令面板</span><span>↵</span></div><div class="pk-th-cmditem"><span>搜索：留白</span><span>→</span></div><div class="pk-th-cmditem"><span>跳到分类</span><span>→</span></div></div>
					<span class="pk-th-scene-tag">面板弹入 + 结果逐条浮现</span>
				</div>

				<div class="pk-th-scene pk-th-s6" data-scene="s6">
					<div class="pk-th-slide"><i></i><i></i><div class="pk-th-slidecap"><b>标题直接压在图片上</b><span>阅读全文</span></div></div>
					<div class="pk-th-dots"><i></i><i></i><i></i></div>
					<span class="pk-th-scene-tag">左右滑切换 · 文字与按钮都在图内</span>
				</div>
			</div>
		</div>
	</div>

	<!-- ============ 特性 ============ -->
	<div class="pk-th-sec" id="pk-th-feature">
		<div class="pk-th-sec-head">
			<div>
				<span class="pk-th-eyebrow">Features</span>
				<h3>主题特性</h3>
				<p class="pk-th-lead">每一项都不是摆设：右边的小图会一直循环演示它的动作。</p>
			</div>
		</div>
		<div class="pk-th-feats">
			<div class="pk-th-feat"><div class="pk-th-feat-no">01</div><div><h4>共享元素转场</h4><p>点开卡片就地展开成阅读层，关掉缩回原位。没有整页跳转，视线不丢。</p></div><div class="pk-th-viz v1"><i></i></div></div>
			<div class="pk-th-feat"><div class="pk-th-feat-no">02</div><div><h4>全站 PJAX 无刷新</h4><p>Swup 接管站内跳转，换页只换内容区；进度条与淡入让等待有反馈。</p></div><div class="pk-th-viz v2"><i></i></div></div>
			<div class="pk-th-feat"><div class="pk-th-feat-no">03</div><div><h4>移动端侧栏抽屉</h4><p>顶栏一个按钮滑出完整侧栏，遮罩、ESC、点链接都会收起，个人中心自动隐藏。</p></div><div class="pk-th-viz v3"><i></i></div></div>
			<div class="pk-th-feat"><div class="pk-th-feat-no">04</div><div><h4>PhotoSwipe 沉浸灯箱</h4><p>本地化集成，半透明背景保留上下文，图注与缩略图当前态同步。</p></div><div class="pk-th-viz v4"><i></i></div></div>
			<div class="pk-th-feat"><div class="pk-th-feat-no">05</div><div><h4>⌘K 命令面板</h4><p>搜索与命令合一，键盘优先，可选接入 AI 语义搜索。</p></div><div class="pk-th-viz v5"><i></i></div></div>
			<div class="pk-th-feat"><div class="pk-th-feat-no">06</div><div><h4>整卡压图幻灯片</h4><p>手机上封面铺满整卡，标题与按钮压在磨砂玻璃条上，左右滑即翻篇。</p></div><div class="pk-th-viz v6"><i></i></div></div>
		</div>
	</div>

	<!-- ============ 开源免费 ============ -->
	<div class="pk-th-sec" id="pk-th-free">
		<span class="pk-th-eyebrow">Open Source</span>
		<h3>永久免费，完全开源</h3>
		<p class="pk-th-lead">不收费、不订阅、不设 Pro 版。Bang 遵循 GPL v2.0 及以上协议开放全部源代码，可自由下载、使用、修改、分发，个人博客与商业站点都不需要任何授权，也没有域名数量限制。</p>
		<div class="pk-th-free-grid">
			<div class="pk-th-free-card"><b>零门槛下载</b><p>不注册、不授权、不绑域名。下载 ZIP 上传 WordPress 即可启用。</p></div>
			<div class="pk-th-free-card"><b>源码全部开放</b><p>PHP、CSS、JS 全部可查可改，没有加密、混淆或远程校验。</p></div>
			<div class="pk-th-free-card"><b>欢迎一起共建</b><p>提 Issue、发 PR、做子主题、翻译语言包都欢迎，改得好看记得回传一份。</p></div>
		</div>
		<p class="pk-th-note">唯一的要求：基于 Bang 二次分发的作品，请同样遵循 GPL 协议并保留原作者版权声明。</p>
	</div>

	<!-- ============ 技术信息 ============ -->
	<div class="pk-th-sec" id="pk-th-spec">
		<div class="pk-th-sec-head">
			<div><span class="pk-th-eyebrow">Specs</span><h3>技术信息</h3><p class="pk-th-lead">最近更新日期：2026 年 11 月 13 日</p></div>
		</div>
		<dl class="pk-th-spec">
			<div><dt>主题版本号</dt><dd>V0.13.27</dd></div>
			<div><dt>主题价格</dt><dd>永久免费，无付费版本</dd></div>
			<div><dt>开源协议</dt><dd>GPL v2.0 及以上</dd></div>
			<div><dt>源码仓库</dt><dd><a class="pk-th-link" href="<?php echo esc_url( $pk_repo ); ?>">github.com/naigoucn/bang</a>，可自由 Fork 与提交 PR</dd></div>
			<div><dt>主题包大小 / 格式</dt><dd>1.8 MB · ZIP</dd></div>
			<div><dt>浏览器兼容</dt><dd>Edge、Firefox、Safari、Chrome 等现代浏览器</dd></div>
			<div><dt>WordPress 兼容</dt><dd>6.0 及以上</dd></div>
			<div><dt>环境兼容</dt><dd>PHP 7.4 至 8.3</dd></div>
			<div><dt>主题类型</dt><dd>轻量响应式博客主题</dd></div>
			<div><dt>其他平台</dt><dd>Typecho / Halo 版本同步开发中</dd></div>
			<div><dt>授权方式</dt><dd>无需授权码、不绑定域名、不限站点数量</dd></div>
		</dl>

		<h3 class="pk-th-subhead">注意事项</h3>
		<div class="pk-th-qa">
			<details open><summary>请勿擅自更改父主题源代码<span class="pk-th-plus"></span></summary><div class="pk-th-ans">建议通过子主题做二次开发。因直接改动父主题源代码而导致的数据丢失及其他问题，请自行排查处理。</div></details>
			<details><summary>演示内容版权说明<span class="pk-th-plus"></span></summary><div class="pk-th-ans">主题演示站点中所涉及的图片、音视频、商品均以演示之用，版权归原作者所有。</div></details>
			<details><summary>二次开发与分发<span class="pk-th-plus"></span></summary><div class="pk-th-ans">源代码依 GPL v2.0 及以上协议开放，可自由修改与再分发；衍生作品请沿用相同协议并保留原作者版权声明。</div></details>
			<details><summary>主题没有担保<span class="pk-th-plus"></span></summary><div class="pk-th-ans">开源软件按「现状」提供，不附带任何明示或默示担保，请自行做好站点与数据库备份。</div></details>
		</div>
	</div>

	<!-- ============ Q&A ============ -->
	<div class="pk-th-sec" id="pk-th-qa">
		<span class="pk-th-eyebrow">Q&amp;A</span>
		<h3>有问？必答！</h3>
		<div class="pk-th-qa">
			<details open><summary>主题真的免费吗？以后会收费吗？<span class="pk-th-plus"></span></summary><div class="pk-th-ans">永久免费，不会推出付费版、Pro 版或订阅制。全部功能开箱即用，未来所有版本更新同样免费。</div></details>
			<details><summary>是开源主题吗？可以商用和二次开发吗？<span class="pk-th-plus"></span></summary><div class="pk-th-ans">是。全部源码依 GPL v2.0 及以上协议开放，个人博客、商业站点均可免费使用；可自由修改、做成子主题、翻译语言包或再分发，只需保留版权声明并沿用 GPL 协议。</div></details>
			<details><summary>需要授权码或绑定域名吗？<span class="pk-th-plus"></span></summary><div class="pk-th-ans">都不需要。不联网校验、不限制域名数量、不收集站点信息，下载安装即可使用。</div></details>
			<details><summary>怎么获取版本更新？<span class="pk-th-plus"></span></summary><div class="pk-th-ans">在开源仓库的 Releases 下载最新 ZIP，后台直接上传覆盖即可；也可以 git pull 后重新打包。更新前建议先备份站点。</div></details>
			<details><summary>遇到问题怎么反馈？<span class="pk-th-plus"></span></summary><div class="pk-th-ans">请在仓库提 Issue，尽量附上 WordPress / PHP 版本、复现步骤与截图。也欢迎直接提交 Pull Request。</div></details>
			<details><summary>演示站里的图片和文章能直接拿来用吗？<span class="pk-th-plus"></span></summary><div class="pk-th-ans">演示内容仅作展示，图片与文字版权归原作者所有，请勿直接搬到自己的站点。主题本身的代码不受此限制。</div></details>
			<details><summary>提供一对一技术支持吗？<span class="pk-th-plus"></span></summary><div class="pk-th-ans">开源项目以社区互助为主，Issue 区会尽量回复主题自身的设置与使用问题；站点环境排障、数据恢复与定制开发不在范围内。</div></details>
		</div>
	</div>

	<div class="pk-th-end" id="pk-th-buy">
		<span class="pk-th-eyebrow">Get Started</span>
		<h3>现在起，让您的站点焕然一新</h3>
		<p>永久免费 · 完全开源 · 无需授权 · 更新不收费</p>
		<div class="pk-th-cta">
			<a class="pk-th-btn pk-th-btn-dark" href="<?php echo esc_url( $pk_zip ); ?>">免费下载主题</a>
			<a class="pk-th-btn pk-th-btn-ghost" href="<?php echo esc_url( $pk_repo ); ?>">查看源码 / 参与共建</a>
		</div>
		<p class="pk-th-tip">遵循 GPL v2.0 及以上协议 · 可自由使用、修改与分发</p>
	</div>

</div>

<script>
(function () {
	/* Hero 实况：自动循环播放「共享元素展开」 */
	var live = document.getElementById('pk-th-live');
	if (live) {
		var on = false, timer = null;
		function tick() { on = !on; live.classList.toggle('is-reading', on); }
		function start() { if (timer) clearInterval(timer); timer = setInterval(tick, 2600); }
		/* 先让用户看到卡片流，稍后再展开，才看得出「从卡片长出来」 */
		setTimeout(function () { tick(); start(); }, 900);
		var replay = document.getElementById('pk-th-replay');
		if (replay) replay.addEventListener('click', function () { on = false; live.classList.remove('is-reading'); tick(); start(); });
	}

	/* 动效实验室：切换场景并重播 CSS 动画 */
	var picks = document.getElementById('pk-th-picks');
	var stage = document.getElementById('pk-th-stage');
	var label = document.getElementById('pk-th-stage-label');
	if (!picks || !stage) return;
	var names = { s1: '共享元素展开', s2: '无刷新换页', s3: '手机侧栏抽屉', s4: '沉浸灯箱', s5: '⌘K 命令面板', s6: '整卡压图幻灯片' };
	var auto = null;

	function play(key) {
		var scenes = stage.querySelectorAll('.pk-th-scene');
		for (var i = 0; i < scenes.length; i++) {
			var s = scenes[i], active = s.getAttribute('data-scene') === key;
			s.classList.toggle('is-on', active);
			s.classList.remove('is-play');
		}
		var btns = picks.querySelectorAll('.pk-th-pick');
		for (var j = 0; j < btns.length; j++) btns[j].classList.toggle('is-on', btns[j].getAttribute('data-demo') === key);
		if (label) label.textContent = names[key] || '';
		var cur = stage.querySelector('.pk-th-scene[data-scene="' + key + '"]');
		if (!cur) return;
		void cur.offsetWidth;              /* 强制回流，让动画从头播 */
		cur.classList.add('is-play');
		if (auto) clearInterval(auto);
		auto = setInterval(function () { cur.classList.remove('is-play'); void cur.offsetWidth; cur.classList.add('is-play'); }, 2600);
	}

	picks.addEventListener('click', function (e) {
		var b = e.target.closest ? e.target.closest('.pk-th-pick') : null;
		if (b) play(b.getAttribute('data-demo'));
	});
	play('s1');
}());
</script>
<?php
pk_page_close();
get_footer();
