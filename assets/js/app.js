/**
 * Bang —— 卡片流交互
 * 1. 共享元素转场（View Transitions，失败降级 FLIP）
 * 2. 分类筛选 / 即时搜索 / 深色模式 / 关于弹层
 * 3. URL 与浏览器前进后退同步
 */
(function () {
	'use strict';

	/* 资源自检哨兵：外部脚本一执行就打标记，footer 内联哨兵据此判断 JS 是否加载成功 */
	document.documentElement.setAttribute('data-pk-boot', '1');

	var $ = function (id) { return document.getElementById(id); };
	var root = document.documentElement;
	var siteHead = $('site-head');
	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var supportsVT = typeof document.startViewTransition === 'function';
	var brandLink = document.querySelector('.brand .mark');
	var homeUrl = brandLink ? brandLink.getAttribute('href') : '/';

	/* 前端 AJAX 地址与令牌（由主题在 wp_localize_script 注入） */
	var pkFront = window.pkFront || {};
	var ajaxUrl = pkFront.ajaxUrl || (homeUrl.replace(/\/$/, '') + '/wp-admin/admin-ajax.php');
	var pkNonce = pkFront.nonce || '';

	/* 界面与交互开关（后台「Bang 设置 → 界面与交互」经 pkFront.ui 注入）。
	   uiOn() 默认放行：字段缺失 / 老缓存页面一律按「开启」处理，绝不误关功能。 */
	var ui = pkFront.ui || {};
	function uiOn(key) { return ui[key] === undefined || ui[key] === 1 || ui[key] === '1'; }
	var uiSweepMs = Math.max(100, Math.min(1500, Number(ui.sweepMs) || 500));
	var uiToTopOffset = Math.max(100, Math.min(2000, Number(ui.toTopOffset) || 420));

	/* ---------- 头部高度：阅读层从这里开始 ---------- */
	function measureHead() {
		if (!siteHead) { return; }
		root.style.setProperty('--head-h', Math.round(siteHead.getBoundingClientRect().height) + 'px');
	}
	measureHead();
	window.addEventListener('resize', measureHead);
	window.addEventListener('load', measureHead);

	/* ---------- 文章内页：透明头部 / 封面铺到顶部 / 多图自动滚动 ---------- */
	var detailEl = $('detail');

	/* 返回顶部按钮：阅读层滚动超过阈值才出现，回到顶部自动收起 */
	function syncDock() {
		var dock = document.querySelector('.pk-dock');
		if (!dock) { return; }
		var y = detailEl ? detailEl.scrollTop : (window.scrollY || 0);
		dock.classList.toggle('is-scrolled', y > 80);
	}

	/* ---------- 点赞（WordPress 账号，状态存在 user_meta） ----------
	   状态由主题在 pkUser 里注入：登录态、已点赞的文章 ID、REST 地址与 nonce。
	   收藏已合并进点赞，操作条上只剩一个心形；图标下方显示点赞数。
	   未登录时提示并跳去用户中心。 */
	var pkUser = window.pkUser || { loggedIn: false, likes: [], restUrl: '', restNonce: '', userUrl: '/user/' };

	/* ---------- 违禁词提示 ----------
	   只有提示，不拦提交：词表与动作都在服务端（inc/comment-moderation.php），
	   各入口的错误文案都是固定字符串、不会带用户输入，这里安全地直接显示。
	   关键词列表由服务端下发在 pkUser.words（留空即完全不显示）。
	   服务端已把 `词=权重` 归一成纯词，这里再兜一层，避免老缓存里带权重时把
	   「词=10」整段当关键词去匹配。 */
	function kpkWords() {
		var raw = (pkUser && pkUser.words) || [];
		if (!raw || !raw.length) { return []; }
		var out = [];
		for (var i = 0; i < raw.length; i++) {
			var w = typeof raw[i] === 'string' ? raw[i].replace(/^\s+|\s+$/g, '') : '';
			if (w.indexOf('=') !== -1) { w = w.split('=')[0].replace(/^\s+|\s+$/g, ''); }
			if (w) { out.push(w); }
		}
		return out;
	}
	function kpkScan(text) {
		var words = kpkWords();
		if (!words.length || !text) { return ''; }
		var hay = String(text).toLowerCase();
		for (var i = 0; i < words.length; i++) {
			var w = words[i].toLowerCase();
			if (w && hay.indexOf(w) !== -1) { return words[i]; }
		}
		return '';
	}

	function socialHas(id) {
		return (pkUser.likes || []).indexOf(Number(id)) !== -1;
	}
	/* 操作条上的计数：点赞数由 REST 回写，评论数由评论列表 / 提交结果回写 */
	function setDockCount(sel, n) {
		var el = document.querySelector('.pk-dock ' + sel);
		if (el && typeof n !== 'undefined' && n !== null) { el.textContent = n; }
	}
	/* 阅读层与 Swup 换页后都是新节点：按 data-post 把当前用户的点赞态写回图标 */
	function syncSocial(dock) {
		var d = dock || document.querySelector('.pk-dock');
		if (!d) { return; }
		var btn = d.querySelector('[data-pk-like]');
		if (!btn) { return; }
		var on = socialHas(d.getAttribute('data-post'));
		btn.classList.toggle('is-on', on);
		btn.setAttribute('aria-pressed', on ? 'true' : 'false');
	}
	document.addEventListener('click', function (e) {
		var btn = e.target.closest ? e.target.closest('[data-pk-like]') : null;
		if (!btn) { return; }
		if (!pkUser.loggedIn) {
			/* 就地弹登录，不打断当前阅读；弹窗本身就是反馈，不再叠 toast */
			authOpen('login');
			return;
		}
		var dock = btn.closest('.pk-dock');
		var id = Number(dock ? dock.getAttribute('data-post') : 0);
		if (!id || btn.disabled) { return; }
		btn.disabled = true;
		fetch(pkUser.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': pkUser.restNonce },
			body: JSON.stringify({ post_id: id })
		}).then(function (r) { return r.json(); }).then(function (res) {
			btn.disabled = false;
			/* REST 回调直接返回数组，响应体就是 {on, count}，没有 success/data 包一层 */
			if (!res || typeof res.on === 'undefined') { showToast('操作失败，稍后再试'); return; }
			var on = !!res.on;
			btn.classList.toggle('is-on', on);
			btn.setAttribute('aria-pressed', on ? 'true' : 'false');
			var list = pkUser.likes || (pkUser.likes = []);
			var at = list.indexOf(id);
			if (on && at === -1) { list.push(id); }
			if (!on && at !== -1) { list.splice(at, 1); }
			setDockCount('[data-dock-like]', res.count);
			if (navigator.vibrate) { try { navigator.vibrate(8); } catch (err) { /* 忽略 */ } }
		}, function () {
			btn.disabled = false;
			showToast('网络异常，稍后再试');
		});
	});
	syncSocial();

	/* ---------- 手机端动作条位置：跟随重力感应 / 固定左 / 固定右 ----------
	   follow 为默认：手机竖屏时按 gamma 判断往哪边歪，动作条就跟到哪一侧。
	   登录用户的偏好存 user_meta（跨设备），游客存 localStorage。
	   实际落点写在 <html> 的 dock-left 类上——html 不参与 Swup 交换，
	   换页后新出来的 .pk-dock 会自动继承当前位置。 */
	var DOCK_SIDE_KEY = 'pk.dock.side';
	var DOCK_HINT_KEY = 'pk.dock.hint';
	var DOCK_SIDES = ['follow', 'left', 'right'];
	var phoneMQ = window.matchMedia ? window.matchMedia('(max-width:1023px)') : null;

	function readStoredSide() {
		try {
			var v = localStorage.getItem(DOCK_SIDE_KEY);
			return DOCK_SIDES.indexOf(v) === -1 ? '' : v;
		} catch (e) { return ''; }
	}
	function prefSide() {
		if (DOCK_SIDES.indexOf(pkUser.dockSide) !== -1) { return pkUser.dockSide; }
		return readStoredSide() || 'follow';
	}

	var sidePref = prefSide();
	var tiltSide = 'right';   // follow 模式下的落点，默认贴右
	var tiltSmooth = 0;
	var tiltAt = 0;
	var tiltReady = false;
	var hintShowTimer = null;

	/* 换边：先量旧位置，改完类名再补一段位移补间，避免整条「瞬移」 */
	function renderSide(animate) {
		var eff = sidePref === 'follow' ? tiltSide : sidePref;
		var dock = document.querySelector('.pk-dock');
		var prev = dock ? dock.getBoundingClientRect().left : 0;
		root.classList.toggle('dock-left', eff === 'left');
		root.setAttribute('data-dock-side', eff);
		if (!dock || !animate || !dock.animate) { return; }
		var dx = prev - dock.getBoundingClientRect().left;
		if (Math.abs(dx) < 1) { return; }
		try {
			dock.animate(
				[{ transform: 'translateX(' + dx + 'px)' }, { transform: 'translateX(0)' }],
				{ duration: 240, easing: 'cubic-bezier(.22,.61,.36,1)' }
			);
		} catch (e) { /* 老浏览器直接跳到位 */ }
	}

	/* 服务端从未存过偏好（pkUser.dockSide 为空串）时，用户中心的三档按钮
	   按本机生效偏好补高亮；存过就完全交给服务端（跨设备同步语义）。 */
	function syncSegButtons() {
		if (pkUser.dockSide) { return; }
		var btns = document.querySelectorAll('[data-pk-side]');
		for (var i = 0; i < btns.length; i++) {
			var on = btns[i].getAttribute('data-pk-side') === sidePref;
			btns[i].classList.toggle('is-active', on);
			btns[i].setAttribute('aria-pressed', on ? 'true' : 'false');
		}
	}

	/* 保存偏好：游客只写本地，登录用户同时 POST 到 REST。
	   提示文案按真实结果区分：云端保存失败必须让用户知道，
	   否则「已保存」假象下下次进文章页会退回跟随倾斜。 */
	function setPrefSide(side) {
		if (DOCK_SIDES.indexOf(side) === -1) { return; }
		sidePref = side;
		pkUser.dockSide = side;
		try { localStorage.setItem(DOCK_SIDE_KEY, side); } catch (e) { /* 隐私模式忽略 */ }
		renderSide(true);
		var tip = document.querySelector('[data-pk-side-saved]');
		function say(text) { if (tip) { tip.textContent = text; } }
		if (!pkUser.loggedIn || !pkUser.prefsUrl) { say('已保存到本机。'); return; }
		fetch(pkUser.prefsUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': pkUser.restNonce },
			body: JSON.stringify({ dock_side: side })
		}).then(function (r) { return r.json(); }).then(function (res) {
			if (res && res.dockSide) { say('已保存，换设备也同步。'); }
			else { showToast((res && res.message) || '没保存上，稍后再试'); say('已在本机生效，但云端没保存上。'); }
		}, function () {
			showToast('网络异常，没保存上');
			say('已在本机生效，但云端没保存上，换个网络再试。');
		});
	}

	/* gamma：-90..90，绕屏幕纵轴。各家机型/浏览器给出的正负号并不统一，
	   实测取 -gamma 才跟物理倾斜一致：负值＝手机往左压低，动作条跟到左侧。
	   16° 进、6° 回，留出滞回区间，手轻微抖动不会来回闪。 */
	function onTilt(e) {
		if (!phoneMQ || !phoneMQ.matches || sidePref !== 'follow') { return; }
		/* 横屏时左右没有意义，保持竖屏时的落点 */
		if (window.matchMedia && window.matchMedia('(orientation:landscape)').matches) { return; }
		var now = Date.now();
		if (now - tiltAt < 40) { return; }
		tiltAt = now;
		var gamma = typeof e.gamma === 'number' ? -e.gamma : 0;
		tiltSmooth = tiltSmooth * 0.72 + gamma * 0.28;
		var next = tiltSide;
		if (tiltSide === 'right' && tiltSmooth < -16) { next = 'left'; }
		else if (tiltSide === 'left' && tiltSmooth > -6) { next = 'right'; }
		if (next !== tiltSide) {
			tiltSide = next;
			renderSide(true);
		}
	}

	function bindTilt() {
		if (tiltReady || !phoneMQ || !phoneMQ.matches || !window.DeviceOrientationEvent) { return; }
		tiltReady = true;
		/* 只有 iOS Safari 必须在用户手势里申请权限。新版桌面 / Android Chrome
		   也实现了 requestPermission，但授权并不必要——直接挂监听，
		   否则动作条换边和封面视差要等第一次点击后才激活。 */
		var ua = navigator.userAgent || '';
		var iOS = /iP(hone|ad|od)/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
		if (!iOS || typeof DeviceOrientationEvent.requestPermission !== 'function') {
			attachOrientationListeners();
			return;
		}
		/* iOS 13+ 必须在用户手势里申请权限：等第一次触摸 / 点击再问，
		   不让系统弹窗压在首屏阅读上。拒绝就保持当前落点。 */
		var ask = function () {
			document.removeEventListener('touchend', ask, true);
			document.removeEventListener('click', ask, true);
			try {
				DeviceOrientationEvent.requestPermission().then(function (state) {
					if (state === 'granted') {
						attachOrientationListeners();
					}
				}, function () { /* 拒绝：留在原侧 */ });
			} catch (e) { /* 老实现没有 Promise，忽略 */ }
		};
		document.addEventListener('touchend', ask, true);
		document.addEventListener('click', ask, true);
	}

	/* 一条 deviceorientation 事件同时喂两处：动作条换边 + 封面毛玻璃。
	   [暂时禁用 · 恢复时取消下一行注释] 换边功能暂下，毛玻璃照常工作 */
	function attachOrientationListeners() {
		/* window.addEventListener('deviceorientation', onTilt, { passive: true }); */
		window.addEventListener('deviceorientation', onParallax, { passive: true });
	}

	/* ---------- 陀螺仪毛玻璃遮罩：左右倾斜时头图一半深一半浅 ----------
	   图片本身完全不动；倾斜时在 .detail-hero 上盖一层 .hero-glass：
	   backdrop-filter 模糊度随倾斜加深，再用水平 mask-image 让毛玻璃只有一半、
	   往另一半平滑淡出（一半深一半浅）；白色反光横着流动。只看左右（gamma），
	   不做上下。回正即完全消隐。金句卡排除；prefers-reduced-motion 不启用。 */
	var parHero = null;
	var parGlass = null;
	var parBase = null;
	var parCur = { x: 0, y: 0 };
	var parTarget = { x: 0, y: 0 };
	var parRaf = 0;
	var PAR_BLUR = 8;        /* 最大模糊半径（px） */
	var PAR_RANGE = 16;      /* 倾斜多少度到达最大效果 */
	var PAR_GLARE = 0.4;     /* 反光峰值透明度 */

	function clearGlass() {
		if (!parGlass) { return; }
		var s = parGlass.style;
		s.backdropFilter = ''; s.webkitBackdropFilter = '';
		s.background = '';
		s.maskImage = ''; s.webkitMaskImage = '';
	}

	function parallaxReset() {
		if (parRaf) { cancelAnimationFrame(parRaf); parRaf = 0; }
		clearGlass();
		parHero = null;
		parGlass = null;
		parBase = null;
		parCur.x = parCur.y = parTarget.x = parTarget.y = 0;
	}

	function parallaxPaint() {
		parRaf = 0;
		parCur.x += (parTarget.x - parCur.x) * 0.16;
		parCur.y = 0;
		if (parGlass) {
			var mag = Math.min(1, Math.abs(parCur.x));
			/* 只有「目标回到中位且数值收敛」才清样式；从左侧扫到右侧时
			   穿过零点不清，否则半路会把引用一起销毁、效果断掉 */
			if (mag < 0.015 && Math.abs(parTarget.x) < 0.001) {
				clearGlass();
			} else {
				var toRight = parCur.x > 0;
				var blur = 'blur(' + (mag * PAR_BLUR).toFixed(2) + 'px)';
				parGlass.style.backdropFilter = blur;
				parGlass.style.webkitBackdropFilter = blur;
				/* 反光横向流动 + 毛玻璃只在倾过去的那一半，往另一半淡出 */
				parGlass.style.background = 'linear-gradient(' + (toRight ? '90deg' : '270deg')
					+ ', rgba(255,255,255,' + (mag * PAR_GLARE).toFixed(3) + ') 0%, rgba(255,255,255,0) 62%)';
				var mask = 'linear-gradient(' + (toRight ? 'to right' : 'to left') + ', #000 0%, #000 30%, rgba(0,0,0,0) 72%)';
				parGlass.style.maskImage = mask;
				parGlass.style.webkitMaskImage = mask;
			}
		}
		if (Math.abs(parTarget.x - parCur.x) > 0.004) {
			parRaf = requestAnimationFrame(parallaxPaint);
		}
	}

	function onParallax(e) {
		if (!phoneMQ || !phoneMQ.matches || reduceMotion) { return; }
		if (root.classList.contains('pk-morphing')) { return; }
		var d = document.getElementById('detail');
		if (!d) { return; }
		if (!parHero || !parHero.isConnected) {
			parallaxReset();
			var h = d.querySelector('.detail-hero');
			if (!h || h.classList.contains('quote')) { return; }
			parHero = h;
			parGlass = h.querySelector('.hero-glass');
			if (!parGlass) {
				parGlass = document.createElement('div');
				parGlass.className = 'hero-glass';
				h.appendChild(parGlass);
			}
			parBase = null;
		}
		if (typeof e.gamma !== 'number') { return; }
		/* 只看左右倾斜（gamma）；第一条有效读数记为基准，只对「变化量」做效果 */
		if (parBase === null) { parBase = { gamma: e.gamma }; return; }
		var nx = Math.max(-1, Math.min(1, (e.gamma - parBase.gamma) / PAR_RANGE));
		if (nx === parTarget.x) { return; }
		parTarget.x = nx;
		parTarget.y = 0;
		if (!parRaf) { parRaf = requestAnimationFrame(parallaxPaint); }
	}

	/* 第一次进来提示一次：可以倾斜换边，也能固定住。只提示一次，之后不再打扰。 */
	function showDockHint() {
		if (!phoneMQ || !phoneMQ.matches || sidePref !== 'follow') { return; }
		var dock = document.querySelector('.pk-dock');
		if (!dock || dock.querySelector('.dock-hint')) { return; }
		var seen = '';
		try { seen = localStorage.getItem(DOCK_HINT_KEY) || ''; } catch (e) { /* 忽略 */ }
		if (seen) { return; }
		try { localStorage.setItem(DOCK_HINT_KEY, '1'); } catch (e) { /* 忽略 */ }
		var tip = document.createElement('div');
		tip.className = 'dock-hint';
		tip.innerHTML = '倾斜手机可以换边<span>在「我的」里能固定位置</span>';
		dock.appendChild(tip);
		requestAnimationFrame(function () { tip.classList.add('is-show'); });
		setTimeout(function () {
			tip.classList.remove('is-show');
			setTimeout(function () { if (tip.parentNode) { tip.parentNode.removeChild(tip); } }, 320);
		}, 4200);
	}

	/* 换页 / 首屏后统一跑一次：恢复落点、挂重力监听、排一次首次提示 */
	function syncDockSide() {
		renderSide(false);
		bindTilt();
		/* [暂时禁用 · 恢复时取消下一行注释] 首次提示引导「倾斜换边 / 去用户中心固定」，换边暂下时不再弹出 */
		/* clearTimeout(hintShowTimer);
		hintShowTimer = setTimeout(showDockHint, 1500); */
	}

	/* [暂时禁用 · 恢复时删掉本注释首尾] 用户中心的三段式按钮：点一下即生效并写入偏好
	document.addEventListener('click', function (e) {
		var btn = e.target.closest ? e.target.closest('[data-pk-side]') : null;
		if (!btn) { return; }
		var side = btn.getAttribute('data-pk-side');
		if (DOCK_SIDES.indexOf(side) === -1) { return; }
		var group = btn.parentNode;
		var btns = group ? group.querySelectorAll('[data-pk-side]') : [];
		for (var i = 0; i < btns.length; i++) {
			var on = btns[i] === btn;
			btns[i].classList.toggle('is-active', on);
			btns[i].setAttribute('aria-pressed', on ? 'true' : 'false');
		}
		setPrefSide(side);
	});
	[/暂时禁用] */

	if (phoneMQ && phoneMQ.addEventListener) {
		phoneMQ.addEventListener('change', function () { renderSide(false); bindTilt(); });
	}

	/* 布局指标只在换页 / resize / load / 内容尺寸变化时重测。
	   scroll 回调里不再读 getBoundingClientRect / clientWidth / scrollHeight，
	   Lighthouse 报的那 45ms forced-reflow 就清掉了；class 语义保持原样，回归断言不受影响。 */
	var imm = { ready: false, heroH: 0, headH: 0, titleBottom: -1, heroW: 0, maxScroll: 1 };
	var innerRO = null;
	function pkMeasureImmersive() {
		imm.ready = false;
		if (!siteHead || !detailEl) { return; }
		var hero = detailEl.querySelector('.detail-hero');
		var titleEl = detailEl.querySelector('#d-title');
		imm.heroH = hero ? hero.getBoundingClientRect().height : 0;
		imm.headH = siteHead.getBoundingClientRect().height;
		imm.heroW = detailEl.clientWidth || 0;
		imm.maxScroll = Math.max(1, detailEl.scrollHeight - detailEl.clientHeight);
		imm.titleBottom = titleEl ? titleEl.getBoundingClientRect().bottom + detailEl.scrollTop : -1;
		if (imm.heroW && detailEl.querySelector('.detail-hero.marquee')) {
			var hw = imm.heroW + 'px';
			if (detailEl.style.getPropertyValue('--hero-w') !== hw) {
				detailEl.style.setProperty('--hero-w', hw);
			}
		}
		imm.ready = true;
	}
	/* 正文 / 评论异步撑高后重测，ResizeObserver 是异步通知，不会引入同步布局读取 */
	function pkObserveInner() {
		if (innerRO) { innerRO.disconnect(); innerRO = null; }
		var inner = detailEl && detailEl.querySelector('.detail-inner');
		if (!inner || !window.ResizeObserver) { return; }
		innerRO = new ResizeObserver(function () { pkMeasureImmersive(); pkIslandScroll(); });
		innerRO.observe(inner);
	}
	function syncImmersive() {
		syncDock();
		if (!siteHead || !detailEl) { return; }
		var immersive = siteHead.classList.contains('reading') || siteHead.classList.contains('is-single');
		if (!immersive) {
			siteHead.classList.remove('is-past-hero', 'is-past-title');
			return;
		}
		if (!imm.ready) { pkMeasureImmersive(); }
		var y = detailEl.scrollTop;
		siteHead.classList.toggle('is-past-hero', y >= Math.max(0, imm.heroH - imm.headH - 8));
		/* 标题的「文档坐标」在测量时算好，滚动后减 scrollTop 即为当前视口 bottom */
		siteHead.classList.toggle('is-past-title', imm.titleBottom >= 0 && (imm.titleBottom - y) <= imm.headH + 2);
		pkIslandScroll();
	}
	// capture 才能收到阅读层内部的 scroll（scroll 事件不冒泡）
	document.addEventListener('scroll', syncImmersive, true);
	window.addEventListener('resize', function () { pkMeasureImmersive(); syncImmersive(); });
	window.addEventListener('load', function () { pkMeasureImmersive(); syncImmersive(); });
	pkMeasureImmersive();
	syncImmersive();

	/* ---------- 底部悬浮操作条：返回顶部 / 定位到评论 ---------- */
	function scrollReading(top) {
		var el = $('detail');
		if (el && el.scrollHeight > el.clientHeight) {
			if (reduceMotion) { el.scrollTop = top; }
			else { el.scrollTo({ top: top, behavior: 'smooth' }); }
		} else if (!el) {
			window.scrollTo({ top: top, behavior: reduceMotion ? 'auto' : 'smooth' });
		}
	}
	/* ---------- 全站返回顶部 ----------
	   普通页滚 window，文章阅读层滚 #detail 内部；按钮在 #swup-wrapper 外常驻，
	   Swup 换页不销毁。scroll 必须用 capture 才能收到阅读层内部事件（scroll 不冒泡）。 */
	var pkToTopBtn = $('pk-to-top');
	function pkToTopY() {
		var el = $('detail');
		if (el && el.scrollHeight > el.clientHeight) { return el.scrollTop; }
		return window.scrollY || window.pageYOffset || 0;
	}
	function pkToTopSync() {
		if (!pkToTopBtn) { return; }
		if (!uiOn('toTopOn')) { pkToTopBtn.classList.remove('is-on'); return; }
		pkToTopBtn.classList.toggle('is-on', pkToTopY() > uiToTopOffset);
	}
	if (pkToTopBtn) {
		document.addEventListener('scroll', pkToTopSync, true);
		window.addEventListener('resize', pkToTopSync);
		window.addEventListener('pageshow', pkToTopSync);
		window.addEventListener('popstate', pkToTopSync);
		pkToTopBtn.addEventListener('click', function () { scrollReading(0); });
		pkToTopSync();
	}
	/* ---------- Web Share：移动端调起系统原生分享面板 ----------
	   手机 Chrome / Safari / Edge 支持时优先用系统面板，可以直接分享到微信、QQ 等任何 App；
   用户从面板取消（AbortError）不算错误；调起失败或不支持时走调用方的兜底。
   注意：只按视口宽度判断不够——PC 缩窄窗口 / DevTools 手机模式同样是 ≤1023，
   但桌面 Chrome 的 navigator.share 常常不弹面板甚至一直挂起，表现成「点了没反应」，
   所以必须同时确认是移动设备（含 iPadOS 13+ 伪装 macOS 的触点判断）。 */
	var IS_WECHAT = /MicroMessenger/i.test(navigator.userAgent || '');

	function isMobileDevice() {
		var ua = navigator.userAgent || '';
		if (/Android|iPhone|iPod|iPad|HarmonyOS|Mobile/i.test(ua)) { return true; }
		/* iPadOS 13+ 默认发送桌面 UA，只能靠触点数区分 */
		return /Macintosh/.test(ua) && navigator.maxTouchPoints > 1;
	}
	function shareData(title, url) {
		return { title: title || document.title || '', url: url || location.href };
	}
	function canNativeShare(data) {
		if (!navigator.share || typeof navigator.share !== 'function') { return false; }
		if (!phoneSheet() || !isMobileDevice()) { return false; }
		/* 部分浏览器 share 存在但对 url 不支持，提前问一句，免得调起来必失败 */
		if (typeof navigator.canShare === 'function') {
			try { return !!navigator.canShare(data); } catch (e) { return false; }
		}
		return true;
	}
	function nativeShare(title, url, fallback) {
		var data = shareData(title, url);
		if (!canNativeShare(data)) { return false; }
		try {
			navigator.share(data).then(function () {
				if (navigator.vibrate) { try { navigator.vibrate(8); } catch (err) { /* 忽略 */ } }
			}, function (err) {
				if (err && err.name === 'AbortError') { return; }   /* 用户取消：静默 */
				if (fallback) { fallback(err); }
			});
		} catch (e) {
			if (fallback) { fallback(e); }
			return false;
		}
		return true;
	}
	/* 原生分享不可用时的兜底：复制链接 + toast（微信内额外提示入口位置） */
	function shareFallbackCopy(url, err) {
		copyText(url).then(function (ok) {
			if (!ok) { showToast('复制失败，请手动复制'); return; }
			if (IS_WECHAT) { showToast('已复制链接，微信内请点右上角 ··· 发给朋友'); return; }
			showToast(err ? '系统分享不可用，已复制链接' : '链接已复制');
		});
	}
	document.addEventListener('click', function (e) {
		var btn = e.target.closest ? e.target.closest('[data-pk-action]') : null;
		if (!btn) { return; }
		var act = btn.getAttribute('data-pk-action');
		if (act === 'top') { scrollReading(0); return; }
		if (act === 'comments') { openComments(); return; }
		if (act === 'share') {
			if (!uiOn('shareOn')) { return; }
			/* 分享：移动端优先调起系统原生分享面板；地址与标题由 PHP 写在按钮上 */
			var url = btn.getAttribute('data-url') || location.href;
			var title = btn.getAttribute('data-title') || '';
			if (!nativeShare(title, url, function (err) { shareFallbackCopy(url, err); })) {
				shareFallbackCopy(url);
			}
			return;
		}
	});
	/* ---------- 侧栏文章目录：锚点跳到对应标题（平滑滚动 + 当前项高亮） ---------- */
	document.addEventListener('click', function (e) {
		var link = e.target.closest ? e.target.closest('.pk-toc a') : null;
		if (!link) { return; }
		var hash = link.getAttribute('href') || '';
		if (hash.charAt(0) !== '#') { return; }
		var target = document.getElementById(hash.slice(1));
		if (!target) { return; }
		e.preventDefault();
		var el = $('detail');
		var headH = siteHead ? siteHead.getBoundingClientRect().height : 0;
		if (el && el.scrollHeight > el.clientHeight) {
			var offset = target.getBoundingClientRect().top - el.getBoundingClientRect().top;
			scrollReading(Math.max(0, el.scrollTop + offset - headH - 16));
		} else {
			var top = target.getBoundingClientRect().top + (window.scrollY || 0) - headH - 16;
			window.scrollTo({ top: Math.max(0, top), behavior: reduceMotion ? 'auto' : 'smooth' });
		}
		var list = link.closest('.pk-toc');
		if (list) {
			var prev = list.querySelector('a.is-active');
			if (prev) { prev.classList.remove('is-active'); }
			link.classList.add('is-active');
		}
	});
	/* 阅读层返回：[data-pk-back] 是真实 <a>，但返回必须回到「来源页」（首页或分类页），
	   所以拦截后走 history.back()，由 Swup 的 popstate 渲染上一页并做 morph */
	document.addEventListener('click', function (e) {
		if (!e.target.closest) { return; }
		var back = e.target.closest('[data-pk-back]');
		if (!back || !swup) { return; }
		e.preventDefault();
		if (window.history.length > 1) { history.back(); }
		else { swup.navigate(back.getAttribute('href') || homeUrl); }
	});

	/* ---------- 深浅色切换：从按钮位置圆形扩散铺满全屏 ---------- */
	var saved = null;
	try { saved = localStorage.getItem('pk-theme'); } catch (e) { saved = null; }
	if (saved === 'dark' || saved === 'light') { root.setAttribute('data-theme', saved); }
	if (!supportsVT) { root.classList.add('pk-no-vt'); }
	/* 初始 aria-pressed（跟随深色 / 系统偏好），侧栏与顶栏的主题按钮共用 .theme-toggle */
	if (document.querySelectorAll('.theme-toggle').length) {
		var preDark = root.getAttribute('data-theme') === 'dark'
			|| (!root.getAttribute('data-theme') && !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches));
		document.querySelectorAll('.theme-toggle').forEach(function (b) {
			b.setAttribute('aria-pressed', preDark ? 'true' : 'false');
		});
	}

	function applyTheme(next) {
		root.setAttribute('data-theme', next);
		try { localStorage.setItem('pk-theme', next); } catch (e) { /* 隐私模式忽略 */ }
		/* 侧栏 / 顶栏的主题按钮都是 .theme-toggle，同步语义状态 */
		document.querySelectorAll('.theme-toggle').forEach(function (b) {
			b.setAttribute('aria-pressed', next === 'dark' ? 'true' : 'false');
		});
	}

	/* 深浅色切换走事件委托：Swup 交换 #swup-wrapper 后新按钮同样可用 */
	function toggleTheme(themeToggle, e) {
		var cur = root.getAttribute('data-theme');
		var systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
		var isDark = cur === 'dark' || (!cur && systemDark);
		var next = isDark ? 'light' : 'dark';

		if (!supportsVT || reduceMotion) { applyTheme(next); return; }

		var rect = themeToggle.getBoundingClientRect();
		var x = (e && e.clientX) || (rect.left + rect.width / 2);
		var y = (e && e.clientY) || (rect.top + rect.height / 2);
		var radius = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));

		root.classList.add('pk-theme-swap');
		var transition;
		try {
			transition = document.startViewTransition(function () { applyTheme(next); });
		} catch (err) {
			root.classList.remove('pk-theme-swap');
			applyTheme(next);
			return;
		}
		transition.ready.then(function () {
			root.animate(
				{
					clipPath: [
						'circle(0px at ' + x + 'px ' + y + 'px)',
						'circle(' + radius + 'px at ' + x + 'px ' + y + 'px)'
					]
				},
				{
					duration: 720,
					easing: 'cubic-bezier(.4,0,.2,1)',
					pseudoElement: '::view-transition-new(root)',
						fill: 'both'
					}
				);
			}).catch(function () { /* 转场被打断时忽略 */ });
			transition.finished.then(function () {}, function () {}).then(function () {
				root.classList.remove('pk-theme-swap');
			});
	}
	document.addEventListener('click', function (e) {
		var themeToggle = e.target.closest ? e.target.closest('.theme-toggle') : null;
		if (themeToggle) { toggleTheme(themeToggle, e); }
	});

	/* ---------- 文章分享：点击弹出「分享」弹窗（含标题与链接，可任选复制） ---------- */
	var toastEl = $('pk-toast');
	var toastTextEl = $('pk-toast-text');
	var toastTimer = null;

	function showToast(text) {
		if (!toastEl) { toastEl = $('pk-toast'); toastTextEl = $('pk-toast-text'); }
		if (!toastEl) { return; }
		if (toastTextEl) { toastTextEl.textContent = text; }
		else { toastEl.textContent = text; }
		toastEl.classList.add('show');
		if (toastTimer) { clearTimeout(toastTimer); }
		toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, 2200);
	}
	/* http 内网属于非安全上下文，navigator.clipboard 可能不存在，用 execCommand 兜底 */
	function legacyCopy(text) {
		var active = document.activeElement;
		var ta = document.createElement('textarea');
		ta.value = text;
		ta.setAttribute('readonly', '');
		ta.style.position = 'fixed';
		ta.style.top = '-9999px';
		document.body.appendChild(ta);
		ta.select();
		ta.setSelectionRange(0, text.length);
		var ok = false;
		try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
		document.body.removeChild(ta);
		if (active && typeof active.focus === 'function') { active.focus({ preventScroll: true }); }
		return ok;
	}
	function copyText(text) {
		if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
			return navigator.clipboard.writeText(text).then(
				function () { return true; },
				function () { return legacyCopy(text); }
			);
		}
		return Promise.resolve(legacyCopy(text));
	}

	/* ---------- 通行密钥：指纹 / Face ID 登录（WebAuthn） ----------
	   私钥留在设备里（Android 指纹 / iPhone 面容 ID / Windows Hello），站点只存公钥。
	   注册：登录后在用户中心「添加本机」；登录：用户中心登录页一键验证，免输密码。
	   服务端挑战走 transient（120 秒），REST 路由 pianke/v1/passkey/*。 */
	var passkeyOK = typeof window.PublicKeyCredential === 'function' &&
		navigator.credentials && typeof navigator.credentials.create === 'function' &&
		typeof navigator.credentials.get === 'function';

	/* base64url ↔ ArrayBuffer（挑战 / 凭据都是二进制） */
	function b64uToBuf(s) {
		s = String(s).replace(/-/g, '+').replace(/_/g, '/');
		while (s.length % 4) { s += '='; }
		var bin = atob(s);
		var u = new Uint8Array(bin.length);
		for (var i = 0; i < bin.length; i++) { u[i] = bin.charCodeAt(i); }
		return u.buffer;
	}
	function bufToB64u(buf) {
		var u = new Uint8Array(buf);
		var s = '';
		for (var i = 0; i < u.length; i++) { s += String.fromCharCode(u[i]); }
		return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
	}
	function passkeyApi(action, body) {
		return fetch(pkUser.passkeyBase + '/' + action, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': pkUser.restNonce },
			body: JSON.stringify(body || {})
		}).then(function (r) {
			return r.json().then(function (j) {
				if (!r.ok) { throw new Error((j && j.message) || '请求失败'); }
				return j;
			});
		});
	}
	/* 浏览器取消 / 设备无生物识别 / 无可用钥匙 / 超时都归 NotAllowedError，提示要友好 */
	function passkeyError(e) {
		var name = e && e.name;
		if (name === 'NotAllowedError') {
			/* Chrome 在「本机一把钥匙都没有」时会直接弹「未发现任何通行密钥」，用户取消后走到这里 */
			return '本机还没有登记通行密钥：请先用账号密码登录，再到用户中心「指纹 / Face ID 登录」里点「添加本机」';
		}
		if (name === 'InvalidStateError') { return '这把钥匙已经登记过了'; }
		if (name === 'SecurityError') { return '站点域名与登记时不一致，请重新登记'; }
		return (e && e.message) || '操作失败，稍后再试';
	}

	function passkeyRegister() {
		if (!passkeyOK) { showToast('这台浏览器不支持指纹 / Face ID 登录'); return; }
		showToast('请完成设备验证…');
		passkeyApi('options', { intent: 'register' }).then(function (res) {
			var pk = res.publicKey;
			pk.challenge = b64uToBuf(pk.challenge);
			pk.user.id = b64uToBuf(pk.user.id);
			(pk.excludeCredentials || []).forEach(function (c) { c.id = b64uToBuf(c.id); });
			return navigator.credentials.create({ publicKey: pk });
		}).then(function (cred) {
			return passkeyApi('register', {
				id: cred.id,
				rawId: bufToB64u(cred.rawId),
				clientDataJSON: bufToB64u(cred.response.clientDataJSON),
				attestationObject: bufToB64u(cred.response.attestationObject)
			});
		}).then(function () {
			showToast('已登记，本机以后可以指纹 / 面容直接登录');
			setTimeout(function () { location.reload(); }, 1200);
		}).catch(function (e) {
			showToast(passkeyError(e));
		});
	}

	function passkeyLogin() {
		if (!passkeyOK) { showToast('这台浏览器不支持指纹 / Face ID 登录'); return; }
		var redirectTo = '';
		var m = location.search.match(/[?&]redirect_to=([^&]+)/);
		if (m) { try { redirectTo = decodeURIComponent(m[1]); } catch (err) { redirectTo = ''; } }
		passkeyApi('options', { intent: 'login' }).then(function (res) {
			var pk = res.publicKey;
			pk.challenge = b64uToBuf(pk.challenge);
			return navigator.credentials.get({ publicKey: pk }).then(function (cred) {
				return { res: res, cred: cred };
			});
		}).then(function (pair) {
			var cred = pair.cred;
			return passkeyApi('login', {
				token: pair.res.token,
				id: cred.id,
				rawId: bufToB64u(cred.rawId),
				clientDataJSON: bufToB64u(cred.response.clientDataJSON),
				authenticatorData: bufToB64u(cred.response.authenticatorData),
				signature: bufToB64u(cred.response.signature),
				userHandle: cred.response.userHandle ? bufToB64u(cred.response.userHandle) : '',
				redirect_to: redirectTo
			});
		}).then(function (res) {
			if (res && res.loggedIn) {
				showToast('欢迎回来，' + (res.name || '朋友'));
				setTimeout(function () { location.href = res.redirect || pkUser.userUrl; }, 800);
			} else {
				showToast('验证没通过，请重试');
			}
		}).catch(function (e) {
			showToast(passkeyError(e));
		});
	}

	function passkeyRemove(id, btn) {
		btn.disabled = true;
		passkeyApi('remove', { id: id }).then(function (res) {
			var li = btn.closest('.pk-key');
			if (li) { li.parentNode.removeChild(li); }
			showToast('已移除');
			btn.disabled = false;
		}).catch(function (e) {
			btn.disabled = false;
			showToast((e && e.message) || '移除失败');
		});
	}

	var shareDialog = $('share-dialog');
	var shareToggle = $('share-toggle');
	var shareDialogTitle = $('share-dialog-title');
	var shareLinkInput = $('share-link-input');

	function closeShareDialog() {
		if (!shareDialog) { return; }
		if (typeof shareDialog.close === 'function') { shareDialog.close(); }
		else { shareDialog.removeAttribute('open'); }
		var t = $('share-toggle');
		if (t && typeof t.focus === 'function') { t.focus({ preventScroll: true }); }
	}

	if (shareDialog) {
		var openShareDialog = function (title, url) {
			if (shareDialogTitle) { shareDialogTitle.textContent = title; }
			if (shareLinkInput) { shareLinkInput.value = url; }
			if (typeof shareDialog.showModal === 'function') { shareDialog.showModal(); }
			else { shareDialog.setAttribute('open', ''); }
			if (shareLinkInput && typeof shareLinkInput.focus === 'function') { shareLinkInput.focus(); shareLinkInput.select(); }
		};
		/* 分享按钮在阅读层内，Swup 交换后会换新元素，必须走事件委托 */
		document.addEventListener('click', function (e) {
			var toggle = e.target.closest ? e.target.closest('#share-toggle') : null;
			if (!toggle) { return; }
			var title = toggle.getAttribute('data-title') || '';
			var url = toggle.getAttribute('data-url') || location.href;
			/* 移动端优先调起系统原生分享面板；调不起来（含调用中被拒）就退回复制弹窗，
			   不让按钮变成「点了没反应」 */
			if (nativeShare(title, url, function () { openShareDialog(title, url); })) { return; }
			openShareDialog(title, url);
		});

		shareDialog.addEventListener('click', function (e) {
			var copyBtn = e.target.closest ? e.target.closest('[data-pk-share-copy]') : null;
			if (copyBtn) {
				e.preventDefault();
				var mode = copyBtn.getAttribute('data-pk-share-copy');
				var title = (shareDialogTitle ? shareDialogTitle.textContent : '') || '';
				var url = (shareLinkInput ? shareLinkInput.value : '') || location.href;
				var text = mode === 'both' ? (title ? title + '\n' + url : url) : url;
				copyText(text).then(function (ok) {
					showToast(ok ? (mode === 'both' ? '标题与链接已复制' : '链接已复制') : '复制失败，请手动复制');
				});
				return;
			}
			if (e.target.closest('[data-pk-share-close]')) {
				e.preventDefault();
				closeShareDialog();
				return;
			}
			/* 点击遮罩关闭 */
			if (e.target === shareDialog) { closeShareDialog(); }
		});
	}

	/* ---------- 图集灯箱：PhotoSwipe 5（本地 UMD，MIT） ----------
	   阅读层是 innerHTML 注入的，所有事件都委托到 document；
	   图片数据来自 a[href] + data-index（按 index 去重，主图与缩略条会重复），
	   图注放在 figure 的 data-caption 上，不需要额外 JSON。
	   0.13.1 起换成本地 PhotoSwipe：半透明磨砂遮罩 + 从缩略图放大浮起，
	   背景正文依旧看得见；库没加载时退回原生 <dialog>，再不行就是纯链接。 */
	var lightbox = $('pk-lightbox');
	var pswpCtor = window.PhotoSwipe || null;

	/* 键盘翻篇 / ESC 退首页都要知道「灯箱是否打开」，两种实现统一走这里 */
	function pkLightboxActive() {
		return !!document.querySelector('.pswp--open') || !!(lightbox && lightbox.open);
	}

	if ((lightbox || pswpCtor) && uiOn('lightboxOn')) {
		var lbImg     = $('pk-lightbox-img');
		var lbCount   = $('pk-lightbox-count');
		var lbCap     = $('pk-lightbox-caption');
		var lbItems   = [];
		var lbIndex   = 0;
		var lbGallery = null;
		var pswpNow   = null;

		/* PhotoSwipe 必须知道图片尺寸才能算缩放级别。取已加载缩略图的自然宽高
		   （宽高比准确即可），取不到给个 1200×800，避免 0 尺寸把图撑满视口。 */
		function lbSize(img) {
			var w = img ? (img.naturalWidth || Number(img.getAttribute('width')) || 0) : 0;
			var h = img ? (img.naturalHeight || Number(img.getAttribute('height')) || 0) : 0;
			return (w > 0 && h > 0) ? { w: w, h: h } : { w: 1200, h: 800 };
		}
		function lbSyncThumbs(index) {
			if (!lbGallery) { return; }
			Array.prototype.forEach.call(lbGallery.querySelectorAll('[data-pk-lightbox]'), function (el) {
				el.classList.toggle('is-current', Number(el.getAttribute('data-index')) === index);
			});
		}

		/* 以下三个是无库兜底用的原生 dialog 分支 */
		function lbRender() {
			if (!lightbox) { return; }
			var item = lbItems[lbIndex];
			if (!item) { return; }
			lbImg.src = item.full;
			lbImg.alt = item.alt || '';
			lbCount.textContent = (lbIndex + 1) + ' / ' + lbItems.length;
			lbCap.textContent = item.caption || '';
			lbCap.hidden = !item.caption;
			lightbox.classList.toggle('is-many', lbItems.length > 1);
			lbSyncThumbs(lbIndex);
		}
		function lbClose() {
			if (!lightbox) { return; }
			if (lightbox.open && typeof lightbox.close === 'function') { lightbox.close(); }
			else { lightbox.removeAttribute('open'); }
		}
		function lbStep(step) {
			if (lbItems.length < 2) { return; }
			lbIndex = (lbIndex + step + lbItems.length) % lbItems.length;
			lbRender();
		}

		/* PhotoSwipe：dataSource 带 element，开合动画从缩略图原位放大浮起。
		   遮罩色 / 按钮 / 图注全部由 assets/css/photoswipe-bang.css 控制。 */
		function pkPswpOpen() {
			if (!pswpCtor || !lbItems.length || document.querySelector('.pswp--open')) { return false; }
			var data = lbItems.map(function (it) {
				return {
					src: it.full,
					msrc: it.thumb || it.full,
					width: it.w,
					height: it.h,
					alt: it.alt || '',
					caption: it.caption || '',
					element: it.el || null
				};
			});
			var pswp = new pswpCtor({
				dataSource: data,
				index: lbIndex,
				bgOpacity: 0.55,
				showHideAnimationType: 'zoom',
				showAnimationDuration: reduceMotion ? 0 : 300,
				hideAnimationDuration: reduceMotion ? 0 : 200,
				paddingFn: function (vp) {
					return vp.x < 700
						? { top: 58, bottom: 86, left: 14, right: 14 }
						: { top: 66, bottom: 82, left: 26, right: 26 };
				},
				bgClickAction: 'close',
				imageClickAction: 'zoom-or-close',
				closeOnVerticalDrag: true,
				wheelToZoom: true,
				zoom: true,
				preload: [1, 2],
				indexIndicatorSep: ' / ',
				errorMsg: '图片没能加载'
			});

			/* 自定义图注：黑底白字小胶囊浮在底部，随换页同步 */
			pswp.on('uiRegister', function () {
				pswp.ui.registerElement({
					name: 'pkCaption',
					tagName: 'p',
					className: 'pk-pswp-caption',
					appendTo: 'root',
					order: 9,
					onInit: function (el, inst) {
						function sync() {
							var d = inst.currSlide && inst.currSlide.data;
							var text = d && d.caption ? d.caption : '';
							el.textContent = text;
							el.hidden = !text;
						}
						inst.on('change', sync);
						inst.on('afterInit', sync);
						sync();
					}
				});
			});
			pswp.on('change', function () {
				lbIndex = pswp.currIndex;
				lbSyncThumbs(lbIndex);
			});
			pswp.on('close', function () { pswpNow = null; });
			pswpNow = pswp;
			pswp.init();
			return true;
		}

		function lbShow(trigger) {
			lbGallery = trigger.closest ? trigger.closest('[data-pk-gallery]') : null;
			var nodes = lbGallery ? lbGallery.querySelectorAll('[data-pk-lightbox]') : [trigger];
			var caption = lbGallery ? (lbGallery.getAttribute('data-caption') || '') : '';
			var map = {};
			Array.prototype.forEach.call(nodes, function (el) {
				var i = Number(el.getAttribute('data-index') || 0);
				if (map[i]) { return; }
				var img = el.querySelector('img');
				var size = lbSize(img);
				map[i] = {
					full: el.getAttribute('href') || (img ? (img.currentSrc || img.src) : ''),
					thumb: img ? (img.currentSrc || img.src) : '',
					alt: img ? img.alt : '',
					caption: caption || (img ? img.alt : ''),
					el: el,
					w: size.w,
					h: size.h
				};
			});
			var keys = Object.keys(map).sort(function (a, b) { return a - b; });
			lbItems = keys.map(function (k) { return map[k]; });
			if (!lbItems.length) { return; }
			var want = String(Number(trigger.getAttribute('data-index') || 0));
			lbIndex = Math.max(0, keys.indexOf(want));
			lbSyncThumbs(lbIndex);
			/* 预加载其它大图，左右切换时不闪白 */
			lbItems.forEach(function (item) { var pre = new Image(); pre.src = item.full; });
			if (pkPswpOpen()) { return; }
			lbRender();
			if (lightbox && typeof lightbox.showModal === 'function') { lightbox.showModal(); }
			else if (lightbox) { lightbox.setAttribute('open', ''); }
		}

		document.addEventListener('click', function (e) {
			if (!e.target.closest) { return; }
			/* PhotoSwipe 自己接管了关闭 / 左右 / 空白点击，别重复处理 */
			if (pswpNow) { return; }
			var trigger = e.target.closest('[data-pk-lightbox]');
			if (trigger) {
				/* 库没加载时别拦链接，让浏览器直接打开原图 */
				if (pswpCtor || lightbox) { e.preventDefault(); }
				lbShow(trigger);
				return;
			}
			if (e.target.closest('[data-pk-lightbox-close]')) { e.preventDefault(); lbClose(); return; }
			if (e.target.closest('[data-pk-lightbox-prev]')) { e.preventDefault(); lbStep(-1); return; }
			if (e.target.closest('[data-pk-lightbox-next]')) { e.preventDefault(); lbStep(1); return; }
			/* 点图片以外的空白处关闭 */
			if (lightbox && lightbox.open && (e.target === lightbox || e.target.id === 'pk-lightbox-stage')) { lbClose(); }
		});
		document.addEventListener('keydown', function (e) {
			if (!lightbox || !lightbox.open) { return; }
			if (e.key === 'ArrowLeft') { e.preventDefault(); lbStep(-1); }
			else if (e.key === 'ArrowRight') { e.preventDefault(); lbStep(1); }
			else if (e.key === 'Escape') { e.preventDefault(); e.stopImmediatePropagation(); lbClose(); }
		});
	}

	/* ---------- 评论：提交前校验 ----------
	   0.13.19：移除「完善信息 / 个人信息」弹窗与入口，身份一律由登录态提供；
	   未登录提交在 submitComment 里拦下并引导登录（见下方）。 */
	/* ---------- 评论区独立地址 /{文章ID}/talk ----------
	   只改地址栏、不压历史（Swup 自己管 history.state，pushState 会和它抢），
	   抽屉开合不污染后退栈，但地址可以复制分享、直接落地即展开。 */
	function isTalkUrl(url) {
		try { return /\/talk\/?$/.test(new URL(url, location.href).pathname); } catch (e) { return false; }
	}
	function talkUrlFrom(url) {
		try {
			var u = new URL(url, location.href);
			var p = u.pathname.replace(/\/+$/, '');
			if (/\/talk$/.test(p) || !/\/(\d+)$/.test(p)) { return null; }
			u.pathname = p + '/talk';
			u.search = ''; u.hash = '';
			return u.href;
		} catch (e) { return null; }
	}
	function articleUrlFrom(url) {
		try {
			var u = new URL(url, location.href);
			var p = u.pathname.replace(/\/+$/, '');
			if (!/\/talk$/.test(p)) { return null; }
			u.pathname = p.replace(/\/talk$/, '/');
			u.search = ''; u.hash = '';
			return u.href;
		} catch (e) { return null; }
	}
	function syncTalkUrl(open) {
		var next = open ? talkUrlFrom(location.href) : articleUrlFrom(location.href);
		if (!next || next === location.href) { return; }
		try { history.replaceState(history.state, '', next); } catch (e) { /* ignore */ }
	}

	/* ---------- 评论抽屉：从右侧滑出，毛玻璃遮罩 ---------- */
	function commentsOpen() { return root.classList.contains('pk-comments-open'); }
	function openComments(skipUrl) {
		var sec = document.getElementById('pk-comments');
		if (!sec) { return; }
		/* 手机端每次打开都回到默认半屏（上次可能被拖到过满屏） */
		if (phoneSheet()) { sec.style.removeProperty('--cm-h'); }
		var bd = document.getElementById('pk-comments-backdrop');
		if (bd) {
			/* 抽屉在 .overlay 的层叠上下文里（z-index 56），遮罩必须在同一上下文内，
			   否则 body 级遮罩会盖住抽屉本身；挂到 .overlay 后 z-index 才有可比性 */
			var host = document.querySelector('.overlay') || document.body;
			if (bd.parentNode !== host) { host.appendChild(bd); }
			bd.hidden = false;
		}
		void sec.offsetWidth; /* 先落一帧，保证 opacity / transform 过渡生效 */
		root.classList.add('pk-comments-open');
		if (!skipUrl) { syncTalkUrl(true); }
		/* 0.12.8 起不再自动聚焦：输入框默认保持单行紧凑态，用户点一下才展开。
		   自动聚焦会立刻触发 focusin 把表单展开，直接访问 /N/talk 时尤其明显。
		   恢复时去掉这对注释即可。 */
		/*
		var t = sec.querySelector('textarea');
		if (t && !phoneSheet()) {
			setTimeout(function () {
				if (commentsOpen()) { try { t.focus({ preventScroll: true }); } catch (err) { t.focus(); } }
			}, reduceMotion ? 0 : 340);
		}
		*/
	}
	function closeComments(skipUrl) {
		if (!commentsOpen()) { return; }
		root.classList.remove('pk-comments-open');
		if (!skipUrl) { syncTalkUrl(false); }
		var bd = document.getElementById('pk-comments-backdrop');
		var hide = function () { if (bd && !commentsOpen()) { bd.hidden = true; } };
		if (reduceMotion) { hide(); } else { setTimeout(hide, 380); }
	}
	document.addEventListener('click', function (e) {
		if (!e.target.closest) { return; }
		if (e.target.closest('[data-pk-comments-close]')) { e.preventDefault(); closeComments(); return; }
		if (e.target.id === 'pk-comments-backdrop') { closeComments(); }
	});

	/* ---------- 手机端：评论区是可拖拽的底部半屏面板（抖音式） ----------
	   拖动顶部栏改 --cm-h（px），松手吸附：< 40% 屏高直接收起，
	   ≥ 78% 吸附到近满屏，其余回到默认半屏。桌面端是右侧抽屉，不参与。 */
	var cmDrag = null;

	function phoneSheet() {
		return !!(window.matchMedia && window.matchMedia('(max-width:1023px)').matches);
	}
	function sheetEl() { return document.getElementById('pk-comments'); }

	document.addEventListener('pointerdown', function (e) {
		if (cmDrag || !phoneSheet() || !commentsOpen()) { return; }
		if (!e.target.closest || !e.target.closest('.comments-bar') || e.target.closest('.comments-close')) { return; }
		var sec = sheetEl();
		if (!sec) { return; }
		cmDrag = { id: e.pointerId, y: e.clientY, h: sec.getBoundingClientRect().height, moved: false };
		sec.classList.add('is-dragging');
		try { sec.setPointerCapture(e.pointerId); } catch (err) { /* 老浏览器忽略 */ }
		e.preventDefault();
	});

	document.addEventListener('pointermove', function (e) {
		if (!cmDrag || e.pointerId !== cmDrag.id) { return; }
		var sec = sheetEl();
		if (!sec) { return; }
		var dy = cmDrag.y - e.clientY;
		if (Math.abs(dy) > 3) { cmDrag.moved = true; }
		var h = Math.max(120, Math.min(window.innerHeight - 56, cmDrag.h + dy));
		sec.style.setProperty('--cm-h', Math.round(h) + 'px');
		e.preventDefault();
	});

	function endSheetDrag(e) {
		if (!cmDrag || (e && e.pointerId !== cmDrag.id)) { return; }
		var d = cmDrag;
		var sec = sheetEl();
		cmDrag = null;
		if (!sec) { return; }
		try { sec.releasePointerCapture(d.id); } catch (err) { /* 忽略 */ }
		/* 先读高度再撤 dragging，保证读到的是拖到的高度 */
		var h = sec.getBoundingClientRect().height;
		sec.classList.remove('is-dragging');
		if (!d.moved) { return; }  /* 只是点了下标题栏 */
		var vh = window.innerHeight;
		if (h <= vh * 0.4) { closeComments(); return; }
		sec.style.setProperty('--cm-h', (h >= vh * 0.78 ? vh - 56 : Math.round(vh * 0.62)) + 'px');
	}
	document.addEventListener('pointerup', endSheetDrag);
	document.addEventListener('pointercancel', endSheetDrag);
	/* 转屏 / 键盘弹起导致视口变化时，让高度回到默认半屏，避免超出屏幕 */
	window.addEventListener('resize', function () {
		var sec = sheetEl();
		if (sec && commentsOpen() && phoneSheet()) { sec.style.removeProperty('--cm-h'); }
	});

	/* ---------- 评论：pjax 加载列表 + AJAX 提交 ----------
	   列表在文章打开 / 独立页加载时通过 AJAX 拉取（与首页展开层一致），
	   提交也走 AJAX，不再整页跳转到 wp-comments-post.php。 */
	function loadComments(postId, section) {
		/* 独立文章页有一处首屏初始化也会走到这里，统一挡在预渲染期间，
		   等 prerenderingchange 后再拉，避免假的评论列表请求 */
		if (document.prerendering) {
			pkWhenActivated(function () { loadComments(postId, section); });
			return;
		}
		section = section || document.getElementById('pk-comments');
		if (!section || !postId) { return; }
		var list = section.querySelector('.comment-list');
		var countEl = section.querySelector('.pk-comment-count');
		if (!list) { return; }
		fetch(ajaxUrl + '?action=pk_comment_list&post_id=' + encodeURIComponent(postId))
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res || !res.success) { return; }
				var data = res.data || {};
				list.innerHTML = data.html || '';
				section.classList.toggle('is-empty', !data.has);
				if (countEl) { countEl.textContent = data.count; }
				/* 操作条上的评论数跟着列表走，只更新当前这篇的 */
				var dockNow = document.querySelector('.pk-dock');
				if (dockNow && String(dockNow.getAttribute('data-post')) === String(postId)) {
					setDockCount('[data-dock-comments]', data.count);
				}
			})
			.catch(function () {});
	}

	/* ---------- 人机校验：自写滑块 / 极验 v4 ----------
	   自写滑块：前端只判断「拖到底没有」，能不能过由服务端按终点 + 轨迹采样判定。
	   极验 v4：onSuccess 后把 getValidate() 交给服务端二次校验。
	   两条路最终都换同一张票据写进隐藏域，随表单提交；注册与评论的业务代码零改动。
	   （后端 inc/captcha.php + inc/geetest.php） */
	var capUrl = pkFront.capUrl || '';
	var capNonce = pkFront.capNonce || '';
	var capMode = pkFront.capMode || 'local';
	var capCaptchaId = pkFront.capCaptchaId || '';
	var capGeoUrl = pkFront.capGeoUrl || '';

	function pkGeetestConfigured() {
		return capMode === 'geetest' && !!capCaptchaId && !!capGeoUrl;
	}

	/* 票据到手后两种模式共用：写隐藏域、置 ok、评论表单自动续上被拦下的那次提交 */
	function pkCaptchaPass(box, ticket) {
		var input = box.querySelector('[data-pk-cap-input]');
		var msg = box.querySelector('[data-pk-cap-msg]');
		if (input) { input.value = ticket; }
		if (msg) { msg.textContent = ''; }
		box.setAttribute('data-state', 'ok');
		var form = box.closest ? box.closest('form') : null;
		if (form && form.id === 'commentform' && form.getAttribute('data-pk-cap-pending') === '1') {
			form.removeAttribute('data-pk-cap-pending');
			setTimeout(function () { submitComment(form); }, 320);
		}
	}

	function pkCaptchaInit(box) {
		if (!box || box.getAttribute('data-pk-cap-on')) { return; }
		/* 极验：只在后台切到 v4 且 ID/Key 配齐、页面也标记了 geetest 时启用 */
		if (pkGeetestConfigured() && box.getAttribute('data-pk-cap-mode') === 'geetest') {
			box.setAttribute('data-pk-cap-on', '1');
			pkGeetestInit(box);
			return;
		}
		/* 配置没跟上缓存页时把模式拉回本地，别让 CSS 把滑块轨道藏了 */
		box.setAttribute('data-pk-cap-mode', 'local');
		box.setAttribute('data-pk-cap-on', '1');
		pkLocalCaptchaInit(box);
	}

	function pkLocalCaptchaInit(box) {
		var track = box.querySelector('[data-pk-cap-track]');
		var thumb = box.querySelector('[data-pk-cap-thumb]');
		var fill = box.querySelector('[data-pk-cap-fill]');
		var hint = box.querySelector('[data-pk-cap-hint]');
		var msg = box.querySelector('[data-pk-cap-msg]');
		var input = box.querySelector('[data-pk-cap-input]');
		if (!track || !thumb || !fill || !input) { return; }

		var token = '', max = 0, ratio = 0, dragging = false, locked = false;
		var startX = 0, startT = 0, points = [];

		function setState(s) { box.setAttribute('data-state', s); }
		function measure() { max = Math.max(0, track.clientWidth - thumb.offsetWidth - 8); }
		function paint(animate) {
			thumb.style.transition = animate ? '' : 'none';
			fill.style.transition = animate ? '' : 'none';
			thumb.style.transform = 'translate3d(' + Math.round(ratio * max) + 'px,0,0)';
			fill.style.width = Math.round(ratio * max + thumb.offsetWidth * 0.7) + 'px';
			if (hint) { hint.style.opacity = String(Math.max(0, 1 - ratio * 1.8)); }
		}
		function ensureToken() {
			if (token) { return Promise.resolve(token); }
			if (!capUrl) { return Promise.reject(new Error('no captcha url')); }
			return fetch(capUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ action: 'challenge', nonce: capNonce })
			}).then(function (r) { return r.json(); }).then(function (j) {
				token = (j && j.token) || '';
				return token;
			});
		}
		function pass(ticket) {
			locked = true;
			ratio = 1;
			paint(true);
			pkCaptchaPass(box, ticket);
		}
		function fail(text) {
			locked = false;
			input.value = '';
			setState('fail');
			if (msg) { msg.textContent = text || ''; }
			track.classList.remove('is-shake');
			void track.offsetWidth;
			track.classList.add('is-shake');
			setState('fail');
			ratio = 0;
			paint(true);
			setTimeout(function () { track.classList.remove('is-shake'); }, 480);
			setTimeout(function () {
				if (box.getAttribute('data-state') === 'fail') { setState('idle'); }
			}, 760);
		}
		function verify(trace) {
			setState('checking');
			ensureToken().then(function (tk) {
				return fetch(capUrl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ action: 'verify', token: tk, x: ratio, points: trace, nonce: capNonce })
				}).then(function (r) {
					return r.json().then(function (j) { return { ok: r.ok, j: j }; });
				});
			}).then(function (res) {
				if (res.ok && res.j && res.j.ticket) { pass(res.j.ticket); return; }
				token = '';
				fail((res.j && res.j.message) || '验证没通过，再试一次。');
			}, function () {
				token = '';
				fail('网络异常，请重试。');
			});
		}
		/* 键盘走无障碍通道：等量采样一份轨迹，服务端规则一致 */
		function keyboardTrace() {
			var out = [], n = 14;
			for (var i = 0; i <= n; i++) { out.push([i / n, i * 32]); }
			return out;
		}
		function release() {
			if (locked) { return; }
			measure();
			if (ratio < 0.94) { fail('拖到最右侧再松手。'); return; }
			verify(points.length > 5 ? points : keyboardTrace());
		}

		thumb.addEventListener('pointerdown', function (e) {
			if (locked || e.button > 0) { return; }
			e.preventDefault();
			measure();
			dragging = true;
			startX = e.clientX;
			startT = (window.performance && performance.now) ? performance.now() : Date.now();
			points = [[0, 0]];
			ratio = 0;
			paint(false);
			thumb.classList.add('is-dragging');
			setState('drag');
			try { thumb.setPointerCapture(e.pointerId); } catch (err) { /* ignore */ }
		});
		thumb.addEventListener('pointermove', function (e) {
			if (!dragging) { return; }
			var dx = e.clientX - startX;
			ratio = max ? Math.max(0, Math.min(1, dx / max)) : 0;
			paint(false);
			var now = (window.performance && performance.now) ? performance.now() : Date.now();
			if (points.length < 200) { points.push([ratio, Math.round(now - startT)]); }
		});
		thumb.addEventListener('pointerup', function () {
			if (!dragging) { return; }
			dragging = false;
			thumb.classList.remove('is-dragging');
			release();
		});
		thumb.addEventListener('pointercancel', function () {
			if (!dragging) { return; }
			dragging = false;
			thumb.classList.remove('is-dragging');
			fail('松手太快了，再来一次。');
		});
		thumb.addEventListener('keydown', function (e) {
			if (locked) { return; }
			measure();
			if (e.key === 'ArrowRight' || e.key === 'ArrowUp') {
				e.preventDefault();
				ratio = Math.min(1, ratio + 0.1);
				paint(true);
				if (ratio >= 0.999) { verify(keyboardTrace()); }
			} else if (e.key === 'ArrowLeft' || e.key === 'ArrowDown') {
				e.preventDefault();
				ratio = Math.max(0, ratio - 0.1);
				paint(true);
			} else if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				ratio = 1;
				paint(true);
				verify(keyboardTrace());
			}
		});

		/* 换页 / 展开抽屉后宽度会变：重量一次，滑块位置跟着刷新 */
		measure();
		paint(false);
		if (window.ResizeObserver) {
			/* 评论抽屉从 display:none 展开时 track 才有宽度，靠它补一次测量 */
			var ro = new ResizeObserver(function () {
				measure();
				if (!dragging) { paint(false); }
			});
			ro.observe(track);
		}
		/* 提前把挑战取回来，第一次拖动就不用等网络 */
		setTimeout(function () { ensureToken().then(function () {}, function () {}); }, 400);

		/* 表单提交失败后可复位重来（票过期 / 已用满）；force 用于解掉已通过的锁 */
		box.pkCaptchaReset = function (force) {
			if (locked && !force) { return; }
			locked = false;
			token = '';
			input.value = '';
			ratio = 0;
			setState('idle');
			if (msg) { msg.textContent = ''; }
			paint(true);
		};
		/* 延迟出现的滑块从 display:none 展开后，宽度要重新量一次 */
		box.pkCaptchaMeasure = function () {
			measure();
			if (!dragging) { paint(false); }
		};
	}

	/* ---------- 极验行为验证 v4 ----------
	   只把 captchaId 下发前台，Key 只留服务端；onSuccess 拿到 getValidate() 的四个字段，
	   交给 /wp-json/pianke/v1/geetest 做二次校验，通过才换票据。
	   gt4.js / 极验接口连不上时，就地切回自研滑块：既不静默放行，也不把用户堵死。 */
	var geetestLoader = null;
	function pkGeetestLoad() {
		if (window.initGeetest4) { return Promise.resolve(); }
		if (geetestLoader) { return geetestLoader; }
		geetestLoader = new Promise(function (resolve, reject) {
			var s = document.createElement('script');
			s.src = 'https://static.geetest.com/v4/gt4.js';
			s.async = true;
			s.onload = function () {
				if (window.initGeetest4) { resolve(); }
				else { reject(new Error('initGeetest4 missing')); }
			};
			s.onerror = function () { reject(new Error('gt4.js blocked')); };
			document.head.appendChild(s);
		});
		return geetestLoader;
	}

	/* 运行时回退：把同一张 .pk-cap 从极验切回自研滑块，DOM 里两套都常驻 */
	function pkCaptchaFallback(box, text) {
		box.removeAttribute('data-pk-cap-on');
		box.setAttribute('data-pk-cap-mode', 'local');
		box.setAttribute('data-state', 'idle');
		var msg = box.querySelector('[data-pk-cap-msg]');
		if (msg) { msg.textContent = text || ''; }
		pkCaptchaInit(box);
	}

	function pkGeetestInit(box) {
		var mount = box.querySelector('[data-pk-cap-geetest-mount]');
		var input = box.querySelector('[data-pk-cap-input]');
		var msg = box.querySelector('[data-pk-cap-msg]');
		var captchaObj = null, locked = false, busy = false, initTimer = null;

		function reset(force) {
			if (locked && !force) { return; }
			locked = false;
			busy = false;
			if (input) { input.value = ''; }
			if (msg) { msg.textContent = ''; }
			box.setAttribute('data-state', 'idle');
			if (captchaObj && captchaObj.reset) { try { captchaObj.reset(); } catch (e) {} }
		}
		box.pkCaptchaReset = reset;
		box.pkCaptchaMeasure = function () {};

		function fail(text) {
			reset(true);
			if (msg) { msg.textContent = text || '验证没通过，请重试。'; }
			box.setAttribute('data-state', 'fail');
			setTimeout(function () {
				if (box.getAttribute('data-state') === 'fail') { box.setAttribute('data-state', 'idle'); }
			}, 1600);
		}

		function sendValidate(v) {
			busy = true;
			box.setAttribute('data-state', 'checking');
			if (msg) { msg.textContent = '正在校验…'; }
			fetch(capGeoUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					nonce: capNonce,
					lot_number: v.lot_number,
					captcha_output: v.captcha_output,
					pass_token: v.pass_token,
					gen_time: v.gen_time
				})
			}).then(function (r) {
				return r.json().then(function (j) { return { ok: r.ok, j: j || {} }; });
			}).then(function (res) {
				busy = false;
				if (res.ok && res.j.ticket) {
					locked = true;
					pkCaptchaPass(box, res.j.ticket);
					return;
				}
				/* 极验服务不可达 / 后台已关掉：切回自研滑块，用户还有一条能走的路 */
				if (res.j.code === 'pk_geetest_unavailable' || res.j.code === 'pk_geetest_off') {
					pkCaptchaFallback(box, '极验暂时不可用，已切换为滑块验证');
					return;
				}
				fail(res.j.message || '验证没通过，请重试。');
			}, function () {
				busy = false;
				pkCaptchaFallback(box, '网络异常，已切换为滑块验证');
			});
		}

		pkGeetestLoad().then(function () {
			/* 弹窗被 Swup 暂时摘出 DOM：放开初始化标记，等重新挂回时再扫一次 */
			if (!box.isConnected) { box.removeAttribute('data-pk-cap-on'); return; }
			if (box.getAttribute('data-pk-cap-mode') !== 'geetest') { return; }
			if (!mount) { pkCaptchaFallback(box, '验证组件未就绪，已切换为滑块验证'); return; }
			/* initGeetest4 自己还要拉资源，给一个兜底超时，避免按钮永远不出来 */
			initTimer = setTimeout(function () {
				if (!captchaObj && box.getAttribute('data-pk-cap-mode') === 'geetest') {
					pkCaptchaFallback(box, '极验初始化超时，已切换为滑块验证');
				}
			}, 8000);
			try {
				window.initGeetest4({ captchaId: capCaptchaId, product: 'float', language: 'zho' }, function (captcha) {
					captchaObj = captcha;
					if (initTimer) { clearTimeout(initTimer); initTimer = null; }
					try { captcha.appendTo(mount); }
					catch (e) { pkCaptchaFallback(box, '验证组件加载失败，已切换为滑块验证'); return; }
					if (captcha.onSuccess) {
						captcha.onSuccess(function () {
							if (locked || busy) { return; }
							var v = captcha.getValidate ? captcha.getValidate() : null;
							if (!v || !v.lot_number || !v.captcha_output || !v.pass_token || !v.gen_time) {
								fail('验证结果不完整，请重试。');
								return;
							}
							sendValidate(v);
						});
					}
					if (captcha.onError) {
						captcha.onError(function () {
							if (msg) { msg.textContent = '极验组件加载失败，可刷新页面重试。'; }
						});
					}
				});
			} catch (e2) {
				if (initTimer) { clearTimeout(initTimer); initTimer = null; }
				pkCaptchaFallback(box, '极验初始化失败，已切换为滑块验证');
			}
		}, function () {
			pkCaptchaFallback(box, '极验脚本加载失败，已切换为滑块验证');
		});
	}

	/* ---------- 0.13.0 内容区块：代码复制 / 顶部通知条 ----------
	   服务端只输出结构与 data 属性（正文要走 kses，button 会被剥掉），
	   这里在浏览器端补按钮；阅读层 innerHTML 注入后 doPageView 再扫一次。 */
	function pkCodeFallback(text, done) {
		var ta = document.createElement('textarea');
		ta.value = text;
		ta.setAttribute('readonly', '');
		ta.style.cssText = 'position:fixed;top:-9999px;opacity:0';
		document.body.appendChild(ta);
		ta.select();
		var ok = false;
		try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
		document.body.removeChild(ta);
		if (ok) { done(); } else { showToast('复制失败，请手动选中代码'); }
	}
	/* ---------- 代码块语法高亮：本地轻量分词，不引外部库 / CDN ---------- */
	var PK_HL_WORDS = {
		js: 'const let var function return if else for while do break continue class new this super import export from default async await try catch finally throw typeof instanceof in of delete void yield static get set extends switch case true false null undefined nan infinity',
		ts: 'const let var function return if else for while do break continue class new this super import export from default async await try catch finally throw typeof instanceof in of delete void yield static get set extends switch case interface type enum implements public private protected readonly namespace declare as any string number boolean never unknown true false null undefined',
		php: 'echo print function return if else elseif foreach for while do break continue class new public private protected static abstract final interface trait use namespace as try catch finally throw isset empty unset require include global const array list instanceof true false null',
		python: 'def class return if elif else for while in not and or is import from as with try except finally raise lambda yield pass break continue global nonlocal none true false self print len range',
		bash: 'if then else elif fi for while in do done case esac function return local export source alias echo cd set unset exit sudo apt npm yarn pnpm git curl chmod mkdir rm cp mv cat grep sed awk',
		sql: 'select from where insert into update delete create table drop alter join on group by order having limit offset as and or not null is in values set primary key index unique distinct count sum avg max min inner left right outer cross union all',
		go: 'func var const type struct interface map chan go defer if else for range return package import switch case break continue select fallthrough nil true false iota',
		rust: 'fn let mut const struct enum impl trait pub use mod match if else for while loop return self crate where async await move ref type in as true false some none ok err',
		c: 'int char float double void long short unsigned signed const static struct typedef union enum return if else for while do break continue switch case sizeof include define true false null nullptr class public private protected new template typename namespace using',
		json: 'true false null'
	};
	function pkHlMap(str) {
		var m = Object.create(null), a = str.split(/\s+/), i;
		for (i = 0; i < a.length; i++) { if (a[i]) { m[a[i]] = 1; } }
		return m;
	}
	function pkHlEsc(s) {
		return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	}
	function pkHlLang(lang) {
		var l = (lang || 'text').toLowerCase();
		var alias = { javascript:'js', jsx:'js', typescript:'ts', tsx:'ts', py:'python', sh:'bash', shell:'bash', zsh:'bash', yml:'yaml', yaml:'yaml', htm:'html', cplusplus:'c', 'c++':'c', cs:'c', csharp:'c', golang:'go', rs:'rust', md:'text', markdown:'text' };
		return alias[l] || l;
	}
	function pkCodeHighlight(el, lang) {
		if (!el || el.getAttribute('data-pk-hl') === '1') { return; }
		el.setAttribute('data-pk-hl', '1');
		var code = el.textContent;
		if (!code) { return; }
		var l = pkHlLang(lang);
		if (l === 'text' || l === 'yaml') { return; }
		var words = pkHlMap(PK_HL_WORDS[l] || '');
		var comment = '/\\*[\\s\\S]*?\\*/|//[^\\n]*';
		var phpVar = '(?!)';
		if (l === 'python' || l === 'bash') { comment = '#[^\\n]*'; }
		else if (l === 'php') { comment = '/\\*[\\s\\S]*?\\*/|//[^\\n]*|#[^\\n]*'; phpVar = '\\$[A-Za-z_][A-Za-z0-9_]*'; }
		else if (l === 'html' || l === 'xml') { comment = '<!--[\\s\\S]*?-->'; }
		else if (l === 'sql') { comment = '--[^\\n]*|/\\*[\\s\\S]*?\\*/'; }
		else if (l === 'css' || l === 'scss' || l === 'less' || l === 'json') { comment = '/\\*[\\s\\S]*?\\*/'; }
		var str = '"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'|`(?:\\\\.|[^`\\\\])*`';
		var num = '\\b0[xX][0-9a-fA-F]+\\b|\\b\\d+(?:\\.\\d+)?\\b';
		var word = '[A-Za-z_][A-Za-z0-9_]*';
		var re = new RegExp('(' + comment + ')|(' + str + ')|(' + num + ')|(' + phpVar + ')|(' + word + ')', 'g');
		var out = '', last = 0, m, t, type, low, before, after;
		while ((m = re.exec(code)) !== null) {
			out += pkHlEsc(code.slice(last, m.index));
			t = m[0]; type = '';
			if (m[1]) { type = 'c'; }
			else if (m[2]) { type = 's'; }
			else if (m[3]) { type = 'n'; }
			else if (m[4]) { type = 'v'; }
			else {
				low = t.toLowerCase();
				if (words[low]) { type = 'k'; }
				else if (l === 'html' || l === 'xml') {
					before = code.charAt(m.index - 1);
					if (before === '<' || (before === '/' && code.charAt(m.index - 2) === '<')) { type = 'f'; }
					else if (code.charAt(m.index + t.length) === '=') { type = 'v'; }
				}
				else if (l === 'css' || l === 'scss' || l === 'less') {
					after = code.charAt(m.index + t.length);
					if (after === ':') { type = 'v'; }
					else if (code.charAt(m.index - 1) === '.' || code.charAt(m.index - 1) === '#') { type = 'f'; }
				}
				else if (code.charAt(m.index + t.length) === '(') { type = 'f'; }
			}
			out += type ? '<span class="pk-hl-' + type + '">' + pkHlEsc(t) + '</span>' : pkHlEsc(t);
			last = m.index + t.length;
		}
		out += pkHlEsc(code.slice(last));
		el.innerHTML = out;
	}
	function pkCodeInit(scope) {
		var host = scope || document;
		[].forEach.call(host.querySelectorAll('[data-pk-code]:not([data-pk-code-ready])'), function (box) {
		box.setAttribute('data-pk-code-ready', '1');
		var codeEl = box.querySelector('pre code') || box.querySelector('pre');
		if (codeEl && uiOn('codeHighlight')) { pkCodeHighlight(codeEl, box.getAttribute('data-lang') || 'text'); }
		var bar = box.querySelector('.pk-code-bar');
			if (!bar || !uiOn('codeCopy')) { return; }
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'pk-code-copy';
			btn.setAttribute('aria-label', '复制代码');
			btn.innerHTML = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M15 5.5A1.5 1.5 0 0 0 13.5 4H5.5A1.5 1.5 0 0 0 4 5.5v8A1.5 1.5 0 0 0 5.5 15"/></svg><span>复制</span>';
			bar.appendChild(btn);
			btn.addEventListener('click', function () {
				var code = box.querySelector('pre code') || box.querySelector('pre') || box.querySelector('textarea');
				if (!code) { return; }
				var text = code.value !== undefined ? code.value : code.textContent;
				var label = btn.querySelector('span');
				var done = function () {
					btn.classList.add('is-done');
					if (label) { label.textContent = '已复制'; }
					showToast('代码已复制');
					setTimeout(function () {
						btn.classList.remove('is-done');
						if (label) { label.textContent = '复制'; }
					}, 1800);
				};
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text).then(done, function () { pkCodeFallback(text, done); });
				} else {
					pkCodeFallback(text, done);
				}
			});
		});
	}
	/* ---------- 帮助中心：搜索 / 展开全部 / 目录高亮 ---------- */
	function pkHelpInit() {
		var box = document.querySelector('[data-pk-help]');
		if (!box || box.getAttribute('data-pk-help-ready') === '1') { return; }
		box.setAttribute('data-pk-help-ready', '1');
		var input  = box.querySelector('#pk-help-search');
		var toggle = box.querySelector('#pk-help-toggle');
		var hits   = box.querySelector('#pk-help-hits');
		var empty  = box.querySelector('#pk-help-empty');
		var secs   = [].slice.call(box.querySelectorAll('[data-pk-help-sec]'));
		var items  = [].slice.call(box.querySelectorAll('[data-pk-help-item]'));
		var links  = [].slice.call(box.querySelectorAll('[data-pk-help-link]'));
		var index  = [];
		items.forEach(function (it) {
			index.push({ el: it, sec: it.closest('[data-pk-help-sec]'), text: (it.textContent || '').toLowerCase() });
		});

		function filter(q) {
			q = String(q || '').trim().toLowerCase();
			var total = 0;
			secs.forEach(function (sec) {
				var shown = 0;
				index.forEach(function (row) {
					if (row.sec !== sec) { return; }
					var hit = !q || row.text.indexOf(q) !== -1;
					row.el.hidden = !hit;
					if (hit) { shown++; }
					if (q && hit) { row.el.open = true; }
				});
				sec.hidden = (shown === 0);
				total += shown;
			});
			if (hits) { hits.hidden = !q; hits.textContent = q ? ('找到 ' + total + ' 条相关说明') : ''; }
			if (empty) { empty.hidden = (total !== 0); }
		}
		if (input) {
			input.addEventListener('input', function () { filter(input.value); });
			input.addEventListener('keydown', function (e) {
				if (e.key === 'Escape') { input.value = ''; filter(''); input.blur(); }
			});
			filter(input.value);
		}
		if (toggle) {
			toggle.addEventListener('click', function () {
				var open = toggle.getAttribute('aria-pressed') !== 'true';
				toggle.setAttribute('aria-pressed', open ? 'true' : 'false');
				toggle.textContent = open ? '收起全部' : '展开全部';
				index.forEach(function (row) { if (!row.el.hidden) { row.el.open = open; } });
			});
		}
		if (links.length && 'IntersectionObserver' in window) {
			var io = new IntersectionObserver(function (entries) {
				entries.forEach(function (en) {
					if (!en.isIntersecting) { return; }
					links.forEach(function (a) {
						a.classList.toggle('is-active', a.getAttribute('data-pk-help-link') === en.target.id);
					});
				});
			}, { rootMargin: '-12% 0px -72% 0px', threshold: 0 });
			secs.forEach(function (sec) { io.observe(sec); });
		}
	}

	/* 阅读层 / 手势预览用 DOM 克隆插入，没有统一的注入回调，用轻量观察器兜底。
	   只监听 childList，pkCodeInit 加的 ready 属性不会触发；没有新代码块时只会空扫一次。 */
	if (window.MutationObserver) {
		var kitObserver = new MutationObserver(function (records) {
			for (var i = 0; i < records.length; i++) {
				if (records[i].addedNodes && records[i].addedNodes.length) {
					pkCodeInit();
					return;
				}
			}
		});
		kitObserver.observe(document.documentElement, { childList: true, subtree: true });
	}

	function pkCaptchaScan(scope) {
		var host = scope || document;
		[].forEach.call(host.querySelectorAll('[data-pk-cap]'), function (box) {
			/* 延迟型滑块等展开时再初始化，别在首屏为隐藏组件白发一次挑战请求 */
			if (box.hasAttribute('data-pk-cap-lazy') && box.hidden) { return; }
			/* 极验按钮要在可见容器里 appendTo 才有宽度；隐藏面板等展开时再扫一次 */
			if (box.getAttribute('data-pk-cap-mode') === 'geetest' && box.offsetParent === null) { return; }
			pkCaptchaInit(box);
		});
	}

	/* ---------- 评论表单：紧凑输入框 + 提交后才出现的滑块 ----------
	   默认只有一行高的输入框，工具行与滑块都收着；点 / 聚焦输入框才展开。
	   点「发表评论」时若还没票据，就地展开滑块并记住这次提交，拖完自动续上。 */
	function commentCapBox(form) {
		return form ? form.querySelector('[data-pk-cap-lazy]') : null;
	}

	/* ---------- 上下级回复 ----------
	   点评论里的「回复」→ 写 comment_parent、亮出回复条、展开输入框；
	   取消回复 / 提交成功后复位。事件走 document 委托，pjax 换页不用重绑。 */
	function setReplyTarget(id, name) {
		var form = $('commentform');
		if (!form) { return; }
		var parent = form.querySelector('[name="comment_parent"]');
		var bar = form.querySelector('[data-pk-reply-bar]');
		var label = form.querySelector('[data-pk-reply-target]');
		var ta = form.querySelector('[data-pk-comment-input]');
		var who = name || '该评论';
		if (parent) { parent.value = id ? String(id) : '0'; }
		if (bar) { bar.hidden = !id; }
		if (label) { label.textContent = id ? '@' + who : ''; }
		if (ta) { ta.placeholder = id ? '回复 @' + who : '写下你的想法'; }
		form.classList.toggle('is-replying', !!id);
		if (id) {
			form.classList.add('is-expanded');
			if (ta) {
				try { ta.focus({ preventScroll: true }); } catch (e) { ta.focus(); }
			}
			var wrap = form.closest('.comments-form-wrap');
			if (wrap && wrap.scrollIntoView) {
				try { wrap.scrollIntoView({ block: 'nearest' }); } catch (e2) { /* noop */ }
			}
		}
	}
	document.addEventListener('click', function (e) {
		var t = e.target;
		if (!t || !t.closest) { return; }
		if (t.closest('[data-pk-reply-cancel]')) { setReplyTarget(0, ''); return; }
		var btn = t.closest('[data-pk-reply]');
		if (!btn) { return; }
		var id = btn.getAttribute('data-pk-reply');
		if (!id || id === '0') { return; }
		setReplyTarget(id, btn.getAttribute('data-pk-reply-author') || '该评论');
	});
	function revealCommentCaptcha(form) {
		var box = commentCapBox(form);
		if (!box) { return false; }
		box.hidden = false;
		box.classList.add('is-revealed');
		pkCaptchaInit(box);
		if (box.pkCaptchaMeasure) { box.pkCaptchaMeasure(); }
		/* 极验模式下轨道是隐藏的，别去 focus 一个不可见的滑块 */
		if (box.getAttribute('data-pk-cap-mode') !== 'geetest') {
			var thumb = box.querySelector('[data-pk-cap-thumb]');
			if (thumb && thumb.focus) {
				try { thumb.focus({ preventScroll: true }); } catch (e) { thumb.focus(); }
			}
		}
		return true;
	}
	function collapseCommentForm(form) {
		if (!form) { return; }
		form.classList.remove('is-expanded');
		form.removeAttribute('data-pk-cap-pending');
		setReplyTarget(0, '');
		var box = commentCapBox(form);
		if (box) {
			box.hidden = true;
			box.classList.remove('is-revealed');
			if (box.pkCaptchaReset) { box.pkCaptchaReset(true); }
		}
	}
	document.addEventListener('focusin', function (e) {
		var t = e.target;
		if (t && t.name === 'comment' && t.form && t.form.id === 'commentform') {
			t.form.classList.add('is-expanded');
		}
	});

	/* 0.13.19：游客不再填写称呼/邮箱/网站，提交前直接引导登录 */
	function submitComment(form) {
		var guest = form.querySelector('[data-pk-guest]');
		if (guest) {
			var loginLink = form.querySelector('[data-pk-auth-open]');
			if (loginLink) { loginLink.click(); }
			else if (typeof authOpen === 'function') { authOpen('login'); }
			showToast('请先登录后再评论');
			return;
		}
		var textarea = form.querySelector('[name="comment"]');
		if (!textarea || !textarea.value.trim()) {
			showToast('评论内容不能为空');
			if (textarea && typeof textarea.focus === 'function') { textarea.focus(); }
			return;
		}
		/* 违禁词：只提示不拦提交，发布结果仍由服务端决定 */
		var kpkHit = kpkScan(textarea.value);
		if (kpkHit) { showToast('内容包含违禁词「' + kpkHit + '」，可能无法通过审核'); }
		/* 没票据：这次不提交，先把滑块亮出来，拖到底后由 pass() 自动回调本函数 */
		var capInput = form.querySelector('[data-pk-cap-input]');
		if (capInput && !capInput.value) {
			form.setAttribute('data-pk-cap-pending', '1');
			if (revealCommentCaptcha(form)) {
				var capBox = form.querySelector('[data-pk-cap]');
				var isGeo = capBox && capBox.getAttribute('data-pk-cap-mode') === 'geetest' && pkGeetestConfigured();
				showToast(isGeo ? '完成验证后将自动发布' : '拖动滑块完成验证，通过后自动发布');
			} else {
				showToast('请先完成滑块验证');
			}
			return;
		}

		var fd = new FormData(form);
		fd.append('action', 'pk_comment_post');
		fd.append('nonce', pkNonce);
		var submitBtn = form.querySelector('.comment-submit');
		if (submitBtn) { submitBtn.disabled = true; submitBtn.style.opacity = '.6'; }

		fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (submitBtn) { submitBtn.disabled = false; submitBtn.style.opacity = ''; }
				if (!res || !res.success) {
					var err = res && res.data;
					if (err && typeof err === 'object' && err.code === 'captcha') {
						var box = commentCapBox(form);
						if (box && box.pkCaptchaReset) { box.pkCaptchaReset(true); }
						revealCommentCaptcha(form);
						showToast('验证已过期，请重新拖动滑块');
					} else {
						showToast(typeof err === 'string' ? err : ((err && err.message) || '评论提交失败，请稍后再试'));
					}
					return;
				}
				var data = res.data || {};
				if (data.status === 'approved' && data.html) {
					var section = document.getElementById('pk-comments');
					var list = section ? section.querySelector('.comment-list') : null;
					if (list) {
						/* 服务端回的是整棵树：回复要落在对应父节点下，不能简单插到列表开头 */
						list.innerHTML = data.html;
						section.classList.remove('is-empty');
						var c = section.querySelector('.pk-comment-count');
						if (c) { c.textContent = data.count; }
						setDockCount('[data-dock-comments]', data.count);
						var fresh = data.item_id ? list.querySelector('[data-comment-id="' + data.item_id + '"]') : null;
						var sc = fresh ? section.querySelector('.comments-scroll') : null;
						if (sc) {
							var nr = fresh.getBoundingClientRect(), sr = sc.getBoundingClientRect();
							if (nr.top < sr.top + 8 || nr.bottom > sr.bottom - 8) {
								sc.scrollTop += (nr.top - sr.top) - 24;
							}
						}
					}
					textarea.value = '';
					collapseCommentForm(form);
					/* 0.13.22：只有「评论可见」的卡片才需要发布后刷新重判 */
					if (document.querySelector('.pk-unlock[data-pk-unlock-method="comment"]')) {
						pkUnlockReload('评论已发布，正在解锁…');
					} else {
						showToast('评论已发布');
					}
				} else {
					textarea.value = '';
					collapseCommentForm(form);
					showToast(data.message || '评论已提交，待审核');
				}
			})
			.catch(function () {
				if (submitBtn) { submitBtn.disabled = false; submitBtn.style.opacity = ''; }
				showToast('网络异常，评论提交失败');
			});
	}
	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form || form.id !== 'commentform') { return; }
		/* 统一改为 AJAX 提交，阻止原生整页跳转 */
		e.preventDefault();
		submitComment(form);
	});

	/* 独立文章页：服务端已渲染评论，仍用 pjax 拉取最新列表（与首页展开层一致） */
	var initialComments = document.getElementById('pk-comments');
	if (initialComments) {
		loadComments(initialComments.getAttribute('data-pk-post-id'), initialComments);
	}

	/* ---------- 全站公告弹窗 ----------
	   首访弹一次：确认=永久记住（localStorage），关闭 / ESC / 点遮罩=只关本次（sessionStorage）。
	   指纹取内容 hash，站主改了文案就会再弹一次。 */
	var noticeDialog = $('pk-notice');
	function noticeHash() {
		return noticeDialog ? (noticeDialog.getAttribute('data-hash') || '') : '';
	}
	function noticeSeen() {
		var h = noticeHash();
		if (!h) { return true; }
		try {
			/* 兜底开关：localStorage 里设 pk_notice_off=1 可强制不弹（测试 / 紧急关闭用） */
			if (localStorage.getItem('pk_notice_off') === '1') { return true; }
			if (localStorage.getItem('pk_notice_ack') === h) { return true; }
			if (sessionStorage.getItem('pk_notice_seen') === h) { return true; }
		} catch (e) { /* 隐私模式下存储不可用：当没记过，照常弹 */ }
		return false;
	}
	function noticeClose(ack) {
		if (!noticeDialog || !noticeDialog.open) { return; }
		var h = noticeHash();
		try {
			if (ack) { localStorage.setItem('pk_notice_ack', h); }
			else { sessionStorage.setItem('pk_notice_seen', h); }
		} catch (e) { /* ignore */ }
		if (reduceMotion) { noticeDialog.close(); return; }
		noticeDialog.classList.add('is-closing');
		setTimeout(function () {
			noticeDialog.classList.remove('is-closing');
			noticeDialog.close();
		}, 220);
	}
	function noticeOpen() {
		if (!noticeDialog || noticeDialog.open || noticeSeen()) { return; }
		noticeDialog.classList.remove('is-closing');
		if (typeof noticeDialog.showModal === 'function') { noticeDialog.showModal(); }
		else { noticeDialog.setAttribute('open', ''); }
	}
	/* 延迟 600ms：不和首屏图片解码 / 幻灯片初始化抢同一帧 */
	function noticeInit() {
		if (!noticeDialog || noticeSeen()) { return; }
		setTimeout(noticeOpen, 600);
	}
	document.addEventListener('click', function (e) {
		if (!e.target.closest || !noticeDialog || !noticeDialog.open) { return; }
		if (e.target.closest('[data-pk-notice-ok]')) { noticeClose(true); return; }
		if (e.target.closest('[data-pk-notice-later],[data-pk-notice-close]')) { noticeClose(false); return; }
		/* 点遮罩空白处：dialog 本身铺满视口，target 就是它 */
		if (e.target === noticeDialog) { noticeClose(false); }
	});
	if (noticeDialog) {
		/* ESC 交给 dialog 的 cancel，统一按「关闭本次」处理，别直接关掉不留记录 */
		noticeDialog.addEventListener('cancel', function (e) {
			e.preventDefault();
			noticeClose(false);
		});
	}

	/* ---------- 搜索弹窗 ---------- */
	var searchToggle = $('search-toggle');
	var searchDialog = $('search-dialog');
	var searchInput = $('search-input');
	var searchClose = document.querySelector('[data-pk-search-close]');
	/* ---------- PC 内联搜索（顶栏下拉面板，非弹窗） ---------- */
	var pcSearchForm = $('pc-search-form');
	var pcSearchInput = $('pc-search-input');
	var pcSearchBox = $('pc-search-results');
	var pcSearchTimer = null;
	function isPc() {
		return !!(window.matchMedia && window.matchMedia('(min-width: 1024px)').matches);
	}
	function showPcResults() {
		if (!pcSearchBox) { return; }
		pcSearchBox.hidden = false;
		requestAnimationFrame(function () { pcSearchBox.classList.add('is-shown'); });
	}
	function hidePcResults() {
		if (!pcSearchBox) { return; }
		pcSearchBox.classList.remove('is-shown');
		pcSearchBox.hidden = true;
	}
	if (pcSearchInput && pcSearchBox) {
		pcSearchInput.addEventListener('input', function () {
			var q = pcSearchInput.value.trim();
			if (pcSearchTimer) { clearTimeout(pcSearchTimer); pcSearchTimer = null; }
			if (!q) { hidePcResults(); pcSearchBox.innerHTML = ''; return; }
			pcSearchTimer = setTimeout(function () {
				pcSearchTimer = null;
				doSearch(q, pcSearchBox);
				showPcResults();
			}, 220);
		});
		pcSearchInput.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') { pcSearchInput.value = ''; hidePcResults(); pcSearchInput.blur(); }
		});
	}
	if (pcSearchForm) {
		/* 回车不再「点开第一条」，而是打开搜索页看完整结果 */
		pcSearchForm.addEventListener('submit', function (e) {
			e.preventDefault();
			var q = pcSearchInput ? pcSearchInput.value.trim() : '';
			if (!q) { return; }
			hidePcResults();
			gotoSearchPage(q);
		});
	}
	/* 侧栏收起时搜索框只剩一枚图标：点它展开侧栏并把焦点交给输入框 */
	if (pcSearchForm) {
		pcSearchForm.addEventListener('click', function (e) {
			if (!root.classList.contains('pk-aside-collapsed')) { return; }
			e.preventDefault();
			applyAside(false);
			try { window.localStorage.setItem('pk-aside', '1'); } catch (err) { /* ignore */ }
			window.dispatchEvent(new Event('resize'));
			setTimeout(function () { if (pcSearchInput) { pcSearchInput.focus({ preventScroll: true }); } }, 300);
		});
	}
	/* 点击面板 / 输入框以外区域收起下拉 */
	document.addEventListener('click', function (e) {
		if (!e.target.closest || !pcSearchBox || pcSearchBox.hidden) { return; }
		if (pcSearchBox.contains(e.target) || (pcSearchForm && pcSearchForm.contains(e.target))) { return; }
		hidePcResults();
	});
	/* 面板内点击结果后收起（Swup 接管链接跳转） */
	if (pcSearchBox) {
		pcSearchBox.addEventListener('click', function (e) {
			if (e.target.closest && e.target.closest('.search-result, .search-more')) { hidePcResults(); }
		});
	}
	/* 移动端弹窗里的「查看全部结果」：跳之前先把 dialog 关掉（dialog 在交换容器外，不会自己关） */
	document.addEventListener('click', function (e) {
		if (!e.target.closest || !e.target.closest('.search-more')) { return; }
		if (searchDialog && searchDialog.open) { closeSearch(); }
	});
	/* ---------- 侧边栏收缩展开（PC）：--sidebar-w 归零驱动全程过渡 ---------- */
	var asideToggle = $('aside-toggle');
	function applyAside(collapsed) {
		root.classList.toggle('pk-aside-collapsed', !!collapsed);
		if (asideToggle) { asideToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true'); }
		/* 展开 / 收起时挂在外面的浮层位置会失效，直接收起 */
		closeAllAsidePops();
	}
	if (asideToggle) {
		try { if (window.localStorage.getItem('pk-aside') === '0') { applyAside(true); } } catch (e) { /* ignore */ }
		asideToggle.addEventListener('click', function () {
			var collapsed = !root.classList.contains('pk-aside-collapsed');
			applyAside(collapsed);
			asideToggle.setAttribute('data-tip', collapsed ? '展开' : '收缩');
			if (sideTipCur === asideToggle) { pkSideTipShow(asideToggle); }
			try { window.localStorage.setItem('pk-aside', collapsed ? '0' : '1'); } catch (e) { /* ignore */ }
			/* 列数随侧栏状态变化，通知分页器按新列数重排 */
			window.dispatchEvent(new Event('resize'));
		});
	}
	/* ---------- 侧栏图标统一提示：黑底标签右侧浮出（模仿参考站）----------
	   原生 title 气泡又小又慢：读 aria-label 生成 data-tip，所有侧栏图标共用一枚气泡。
	   气泡挂在 #swup-wrapper 外、fixed 定位，不受 .pc-aside 的 overflow:hidden 裁切。 */
	var sideTip = $('pk-side-tip');
	var sideTipCur = null;
	function pkSideTipPrepare() {
		var els = document.querySelectorAll('.pc-aside .pc-nav-link, .pc-aside #aside-toggle, .pc-aside #aside-theme, .pc-aside .pc-side-user');
		for (var i = 0; i < els.length; i++) {
			var el = els[i];
			if (el.id === 'aside-toggle') {
				el.setAttribute('data-tip', root.classList.contains('pk-aside-collapsed') ? '展开' : '收缩');
			} else if (!el.getAttribute('data-tip')) {
				el.setAttribute('data-tip', el.getAttribute('aria-label') || '');
			}
			if (el.hasAttribute('title')) { el.removeAttribute('title'); }
		}
	}
	function pkSideTipShow(el) {
		if (!sideTip) { return; }
		/* 侧栏展开时各项已有可见文字，气泡多余且会盖住正文；只在折叠态显示 */
		if (!root.classList.contains('pk-aside-collapsed')) { pkSideTipHide(); return; }
		var text = el.getAttribute('data-tip') || '';
		if (!text) { return; }
		sideTip.textContent = text;
		var r = el.getBoundingClientRect();
		var w = sideTip.offsetWidth;
		var h = sideTip.offsetHeight;
		var x = r.right + 12;
		var y = r.top + r.height / 2;
		sideTip.classList.remove('is-left');
		if (x + w > window.innerWidth - 8) { x = r.left - w - 12; sideTip.classList.add('is-left'); }
		if (y - h / 2 < 8) { y = h / 2 + 8; }
		if (y + h / 2 > window.innerHeight - 8) { y = window.innerHeight - h / 2 - 8; }
		sideTip.style.left = Math.round(x) + 'px';
		sideTip.style.top = Math.round(y) + 'px';
		sideTip.setAttribute('aria-hidden', 'false');
		sideTip.classList.add('is-on');
	}
	function pkSideTipHide() {
		if (!sideTip) { return; }
		sideTip.classList.remove('is-on');
		sideTip.setAttribute('aria-hidden', 'true');
		sideTipCur = null;
	}
	pkSideTipPrepare();
	document.addEventListener('mouseover', function (e) {
		if (!e.target.closest || window.innerWidth < 1024) { return; }
		var el = e.target.closest('.pc-aside [data-tip]');
		if (el && el !== sideTipCur) { sideTipCur = el; pkSideTipShow(el); }
		else if (!el && sideTipCur) { pkSideTipHide(); }
	});
	document.addEventListener('mouseout', function (e) {
		if (sideTipCur && (!e.relatedTarget || !sideTipCur.contains(e.relatedTarget))) { pkSideTipHide(); }
	});
	document.addEventListener('focusin', function (e) {
		if (!e.target.closest || window.innerWidth < 1024) { return; }
		var el = e.target.closest('.pc-aside [data-tip]');
		if (el) { pkSideTipShow(el); }
	});
	document.addEventListener('focusout', pkSideTipHide);
	/* ---------- 手机端侧栏抽屉（0.13.9）----------
	   顶栏最左侧按钮滑出 .pc-aside：head/aside 都在 #swup-wrapper 外，
	   换页不会重建，所以委托到 document 绑一次即可 */
	function pkDrawerOn(on) {
		document.body.classList.toggle('pk-aside-open', !!on);
		var op = document.getElementById('aside-open');
		if (op) { op.setAttribute('aria-expanded', on ? 'true' : 'false'); }
	}
	document.addEventListener('click', function (e) {
		if (!e.target.closest) { return; }
		if (e.target.closest('#aside-open')) {
			e.preventDefault();
			pkDrawerOn(!document.body.classList.contains('pk-aside-open'));
			return;
		}
		if (e.target.closest('#aside-close') || e.target.closest('#aside-mask')) {
			e.preventDefault();
			pkDrawerOn(false);
			return;
		}
		/* 抽屉里点任意链接（导航 / 分类 / 更多）就先收起，pjax 换页后不会还盖着页面 */
		if (window.innerWidth < 1024 && e.target.closest('#pc-aside a')) { pkDrawerOn(false); }
	});
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && document.body.classList.contains('pk-aside-open')) { pkDrawerOn(false); }
	});
	window.addEventListener('resize', function () {
		if (window.innerWidth >= 1024) { pkDrawerOn(false); }
	});
	/* 0.13.16：默认折叠的保险 —— 浏览器后退 / 前进缓存（bfcache）会原样恢复
	   body 上的 class，若离开页面时抽屉是展开的，回来就会直接盖住正文。
	   pageshow（含 bfcache 恢复）、popstate、页面重新可见、pjax 换页后都强制收起，
	   保证任何入口进到页面时侧栏都是折叠态，只有用户主动点顶栏按钮才展开。 */
	window.addEventListener('pageshow', function () { pkDrawerOn(false); });
	window.addEventListener('popstate', function () { pkDrawerOn(false); });
	document.addEventListener('visibilitychange', function () {
		if (!document.hidden) { pkDrawerOn(false); }
	});

	/* ---------- 手机端动作条折叠（0.13.18）----------
	   默认只留一颗 48px 圆按钮，避免一长条竖排动作栏压住正文右侧文字。
	   点开关展开；再点一次、点动作条外部或换页都会收起。
	   ESC 不接管：文章页 ESC 是「退回首页」的既有全局快捷键，一次按键不能触发两个动作。
	   开关是 .dock-toggle（不是 .dock-btn），不会混进六项动作的计数与语义。 */
	function pkDockSet(open) {
		var dock = document.querySelector('.pk-dock');
		if (!dock) { return; }
		dock.classList.toggle('is-open', !!open);
		var t = dock.querySelector('[data-pk-dock-toggle]');
		if (t) {
			t.setAttribute('aria-expanded', open ? 'true' : 'false');
			t.setAttribute('aria-label', open ? '收起文章操作' : '展开文章操作');
			t.setAttribute('title', open ? '收起文章操作' : '展开文章操作');
		}
	}
	document.addEventListener('click', function (e) {
		if (!e.target.closest) { return; }
		var t = e.target.closest('[data-pk-dock-toggle]');
		if (t) {
			e.preventDefault();
			var dock = t.closest('.pk-dock');
			pkDockSet(dock ? !dock.classList.contains('is-open') : false);
			return;
		}
		var d = document.querySelector('.pk-dock');
		if (d && d.classList.contains('is-open') && !e.target.closest('.pk-dock')) {
			/* 浮层内的操作不算「点正文」：否则关评论抽屉 / 弹窗会顺手把动作条也收了 */
			if (!e.target.closest('dialog, #pk-comments, .pk-comments-backdrop, .pk-lightbox, .pswp, .pk-toc, .read-rail, .pk-island')) {
				pkDockSet(false);
			}
		}
	});
	/* 不接管 ESC：文章页 ESC 是「退回首页」的既有全局快捷键（见文件末尾），
	   再拿来收起动作条会一次按键触发两个动作；收起交给「再点开关 / 点外部 / 换页」。 */
	/* 浏览器后退 / 前进缓存恢复后一律回到折叠态，动作栏不会残留盖住正文 */
	window.addEventListener('pageshow', function () { pkDockSet(false); });
	window.addEventListener('popstate', function () { pkDockSet(false); });


	/* ---------- PC 侧栏「分类 / 标签」弹出面板 ----------
	   面板 fixed 定位贴在侧栏右缘（JS 按触发按钮位置计算 top），
	   点击按钮切换、点击外部 / ESC / 选中链接后收起，Swup 换页时也收起 */
	function closeAsidePop(panel) {
		if (!panel || !panel.classList.contains('is-shown')) { return; }
		panel.classList.remove('is-shown');
		/* 触发器优先按面板上记的 id 找（「关于」那种没有 data-pk-pop 的按钮也能回写 aria） */
		var tid = panel.getAttribute('data-pk-pop-trigger');
		var name = panel.getAttribute('data-pk-pop-panel');
		var trigger = (tid && document.getElementById(tid)) || (name ? document.querySelector('[data-pk-pop="' + name + '"]') : null);
		if (trigger) { trigger.setAttribute('aria-expanded', 'false'); }
		setTimeout(function () {
			if (!panel.classList.contains('is-shown')) { panel.hidden = true; }
		}, 220);
	}
	function closeAllAsidePops() {
		document.querySelectorAll('.pc-pop.is-shown').forEach(closeAsidePop);
	}
	function openAsidePop(panel, trigger) {
		var aside = document.getElementById('pc-aside');
		if (!aside || aside.getBoundingClientRect().width < 40) { return; }
		closeAllAsidePops();
		var ab = aside.getBoundingClientRect();
		var tb = trigger.getBoundingClientRect();
		var top = Math.min(Math.max(10, tb.top - 6), window.innerHeight - Math.min(panel.scrollHeight + 30, window.innerHeight * 0.6) - 10);
		panel.style.top = Math.round(top) + 'px';
		panel.style.left = Math.round(ab.right + 10) + 'px';
		panel.hidden = false;
		requestAnimationFrame(function () { panel.classList.add('is-shown'); });
		if (trigger && trigger.id) { panel.setAttribute('data-pk-pop-trigger', trigger.id); }
		trigger.setAttribute('aria-expanded', 'true');
	}
	document.addEventListener('click', function (e) {
		if (!e.target.closest) { return; }
		var trigger = e.target.closest('[data-pk-pop]');
		if (trigger) {
			var panel = document.querySelector('[data-pk-pop-panel="' + trigger.getAttribute('data-pk-pop') + '"]');
			if (!panel) { return; }
			e.preventDefault();
			if (panel.classList.contains('is-shown')) { closeAsidePop(panel); }
			else { openAsidePop(panel, trigger); }
			return;
		}
		if (!e.target.closest('.pc-pop')) { closeAllAsidePops(); }
	});
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') { closeAllAsidePops(); }
	});
	window.addEventListener('resize', closeAllAsidePops);

	function clearSearchResults() {
		var sr = document.getElementById('search-results');
		if (sr) { sr.innerHTML = ''; sr.hidden = true; }
	}
	var searchCloseTimer = null;
	function clearSearchCloseTimer() {
		if (searchCloseTimer) { clearTimeout(searchCloseTimer); searchCloseTimer = null; }
	}
	function openSearch() {
		clearSearchCloseTimer();
		/* PC：任何入口都不再弹窗，聚焦顶栏内联搜索框 */
		if (isPc() && pcSearchInput) {
			hidePcResults();
			pcSearchInput.focus({ preventScroll: true });
			return;
		}
		if (!searchDialog) { return; }
		searchDialog.classList.remove('is-closing');
		if (typeof searchDialog.showModal === 'function') { searchDialog.showModal(); }
		else { searchDialog.setAttribute('open', ''); }
		/* 等进入动画开始后再聚焦，避免隐藏/缩放态聚焦造成的视觉跳动 */
		setTimeout(function () {
			if (searchDialog.open && searchInput) { searchInput.focus({ preventScroll: true }); }
		}, 240);
	}
	function closeSearch() {
		clearSearchResults();
		if (!searchDialog || !searchDialog.open) { return; }
		clearSearchCloseTimer();
		if (reduceMotion) { searchDialog.close(); return; }
		searchDialog.classList.add('is-closing');
		var done = function () {
			clearSearchCloseTimer();
			searchDialog.classList.remove('is-closing');
			searchDialog.removeEventListener('transitionend', done);
			searchDialog.close();
		};
		searchDialog.addEventListener('transitionend', done);
		searchCloseTimer = setTimeout(done, 320);
	}
	if (searchDialog) {
		/* ESC 触发原生 cancel：拦下立即关闭，改用带过渡的收起 */
		searchDialog.addEventListener('cancel', function (e) { e.preventDefault(); closeSearch(); });
		searchDialog.addEventListener('click', function (e) { if (e.target === searchDialog) { closeSearch(); } });
	}
	if (searchClose) {
		searchClose.addEventListener('click', function (e) { e.preventDefault(); closeSearch(); });
	}
	/* 搜索输入即时行为：首页列表页输入直接筛选卡片流；
	   文章页 / 阅读层展开时只清空历史结果，提交后才 AJAX 拉结果 */
	if (searchInput) {
		searchInput.addEventListener('input', function () {
			var reading = root.classList.contains('reading');
			if (feed && !reading) { applyFilter(); return; }
			var box = document.getElementById('search-results');
			if (box && !searchInput.value.trim()) { box.innerHTML = ''; box.hidden = true; }
		});
	}
	/* 文章页右上角搜索：事件委托（Swup 交换后元素会更换）。
	   无论首页还是文章页，都就地弹出搜索面板，不再跳回首页；
	   文章页（无卡片流 / 阅读层展开）提交时走 AJAX 在当前页拉结果 */
	document.addEventListener('click', function (e) {
		if (!e.target.closest || !e.target.closest('#read-search-toggle')) { return; }
		e.preventDefault();
		openSearch();
	});
	/* 文章页（无卡片流）搜索：在当前页用 AJAX 拉取结果，不返回首页 */
	function escapeHtml(s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}
	/* 搜索页地址：?s=关键词（search.php 展示完整结果，plain / 伪静态都适用） */
	function searchPageUrl(q) {
		var base = String(homeUrl || '/').split('#')[0].split('?')[0];
		if (base.charAt(base.length - 1) !== '/') { base += '/'; }
		return base + '?s=' + encodeURIComponent(q);
	}
	function gotoSearchPage(q) {
		var url = searchPageUrl(q);
		if (swup) { swup.navigate(url); }
		else { window.location.href = url; }
	}
	var searchSeq = 0;
	/* 只放行站内相对路径与 http(s) 绝对地址，挡住 javascript: / data: 之类的 href 注入 */
	function safeHref(url) {
		var s = String(url == null ? '' : url).trim();
		if (!s) { return '#'; }
		if (/^(\/|#|\?)/.test(s) || /^https?:\/\//i.test(s)) { return s; }
		return '#';
	}
	function doSearch(q, box) {
		box = box || document.getElementById('search-results');
		if (!box) { return; }
		var mySeq = ++searchSeq;
		box.hidden = false;
		box.innerHTML = '<p class="search-loading">' + escapeHtml('搜索中…') + '</p>';
		fetch(ajaxUrl + '?action=pk_search&q=' + encodeURIComponent(q))
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (mySeq !== searchSeq) { return; }
				if (!res || !res.success) { box.innerHTML = '<p class="search-empty">' + escapeHtml('搜索失败，请重试。') + '</p>'; return; }
				var items = (res.data && res.data.items) || [];
				if (!items.length) { box.innerHTML = '<p class="search-empty">' + escapeHtml('没有找到相关文章。') + '</p>'; return; }
				var html = '<ul class="search-result-list">';
				items.forEach(function (it) {
					html += '<li><a class="search-result" href="' + escapeHtml(safeHref(it.link)) + '">'
						+ '<span class="search-result-title">' + escapeHtml(it.title) + '</span>'
						+ '<span class="search-result-meta">' + escapeHtml(it.kind) + ' · ' + escapeHtml(it.date) + ' · ' + escapeHtml(it.topic) + '</span>'
						+ '<span class="search-result-excerpt">' + escapeHtml(it.excerpt) + '</span>'
						+ '</a></li>';
				});
				html += '</ul>';
				/* 下拉只有 20 条：给一个进搜索页看完整结果的出口（移动端弹窗也复用这段） */
				html += '<a class="search-more" href="' + escapeHtml(searchPageUrl(q)) + '">' + escapeHtml('查看全部结果') + '</a>';
				box.innerHTML = html;
			})
			.catch(function () {
				if (mySeq === searchSeq) { box.innerHTML = '<p class="search-empty">' + escapeHtml('搜索失败，请重试。') + '</p>'; }
			});
	}

	var searchForm = $('search-form');
	if (searchForm) {
		searchForm.addEventListener('submit', function (e) {
			e.preventDefault();
			var sq = (searchInput ? searchInput.value : '').trim();
			if (feed && !root.classList.contains('reading')) {
				/* 首页 / 列表页：直接就地筛选卡片流，不跳转 */
				applyFilter();
				closeSearch();
				return;
			}
			/* 文章页 / 阅读层展开：在当前页用 AJAX 拉取搜索结果，不返回首页 */
			if (sq) { doSearch(sq); }
		});
	}

	if (searchToggle && searchDialog) {
		searchToggle.addEventListener('click', function () {
				if (searchDialog.open) { closeSearch(); } else { openSearch(); }
		});
	}
	/* 从文章页点搜索跳回首页时自动打开搜索弹窗，并清掉临时 hash */
	if (location.hash === '#search') {
		try { history.replaceState(null, '', location.pathname + location.search); } catch (e) { /* ignore */ }
		setTimeout(function () { openSearch(); }, 0);
	}

	/* ---------- 命令面板（0.12.0 ⌘K）：命令 + 文章搜索合一 ----------
	   Ctrl/Cmd+K 唤起；输入即搜（pk_search 复用），AI 开关打开后走浏览器内
	   语义搜索（transformers.js，向量在客户端算、IndexedDB/localStorage 缓存）。
	   footer 常驻节点，pjax 换页不重建；事件直接绑在节点上，无需委托。
	   SEM 状态必须在这里声明（AI 段在后面，var 赋值不提升，开关恢复要提前用）。 */
	var SEM = { on: false, ready: false, pipe: null, sig: '', docs: null, vecs: null };
	var cmdkEl = $('pk-cmdk');
	var cmdkInput = $('cmdk-input');
	var cmdkList = $('cmdk-list');
	var cmdkAiBtn = $('cmdk-ai');
	var cmdkRefs = [];
	var cmdkActive = -1;
	var cmdkTimer = null;
	var cmdkSeq = 0;

	/* 内联 SVG（lucide 线性风格，与 pk_icon 同源）；JS 侧拿不到 PHP 图标函数，这里自持一份 */
	var CMDK_ICONS = {
		'search': '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.34-4.34"/>',
		'home': '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
		'layers': '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/><path d="M22 17.65l-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="M22 12.65l-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>',
		'info': '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
		'mail': '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
		'link': '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
		'heart': '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
		'user-round': '<circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/>',
		'back': '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>'
	};
	function kIcon(name) {
		var p = CMDK_ICONS[name] || CMDK_ICONS.search;
		return '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"'
			+ ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + p + '</svg>';
	}

	function cmdkAbsUrl(slug) {
		return homeUrl.replace(/\/$/, '') + '/' + slug;
	}
	/* 上下文命令：信息页跳转 + 当前文章动作 + 登录入口，每次打开现取 */
	function cmdkCommands() {
		var items = [];
		items.push({ ico: 'home', title: '返回首页', href: homeUrl });
		items.push({ ico: 'layers', title: '归档', href: cmdkAbsUrl('archive/') });
		items.push({ ico: 'info', title: '关于', href: cmdkAbsUrl('about/') });
		items.push({ ico: 'mail', title: '联系', href: cmdkAbsUrl('contact/') });
		items.push({ ico: 'link', title: '友情链接', href: cmdkAbsUrl('links/') });
		var dock = document.querySelector('.pk-dock');
		var postId = dock ? (Number(dock.getAttribute('data-post')) || 0) : 0;
		if (postId) {
			items.push({
				ico: 'heart', title: '点赞本文',
				action: function () {
					var b = document.querySelector('.pk-dock [data-pk-like]');
					/* 复用 dock 的点赞委托：未登录会就地弹登录，逻辑全部现成 */
					if (b) { b.click(); }
				}
			});
			items.push({
				ico: 'link', title: '复制本文链接',
				action: function () {
					if (navigator.clipboard && navigator.clipboard.writeText) {
						navigator.clipboard.writeText(location.href).then(function () { showToast('链接已复制'); }, function () { showToast('复制失败，请手动复制'); });
					} else { showToast('当前浏览器不支持一键复制'); }
				}
			});
		}
		if (isSingleUrl(location.href)) {
			items.push({
				ico: 'back', title: '返回列表',
				action: function () {
					if (swup) { swup.navigate(homeUrl); }
					else { location.href = homeUrl; }
				}
			});
		}
		if (pkUser.loggedIn) {
			items.push({ ico: 'user-round', title: '用户中心', href: pkUser.userUrl || cmdkAbsUrl('user/') });
		} else {
			items.push({ ico: 'user-round', title: '登录 / 注册', action: function () { authOpen('login'); } });
		}
		return items;
	}
	function cmdkFilterCmds(cmds, q) {
		if (!q) { return cmds; }
		var ql = q.toLowerCase();
		return cmds.filter(function (it) { return it.title.toLowerCase().indexOf(ql) !== -1; });
	}
	function cmdkPaintActive() {
		if (!cmdkList) { return; }
		var els = cmdkList.querySelectorAll('.cmdk-item');
		Array.prototype.forEach.call(els, function (el) {
			el.classList.toggle('is-active', Number(el.getAttribute('data-idx')) === cmdkActive);
		});
		var el = cmdkActive >= 0 ? cmdkList.querySelector('[data-idx="' + cmdkActive + '"]') : null;
		if (el && el.scrollIntoView) { el.scrollIntoView({ block: 'nearest' }); }
	}
	/* 统一渲染：cmds（命令）+ arts（文章结果）+ status（加载态文本） */
	function cmdkRender(cmds, arts, statusHtml) {
		if (!cmdkList) { return; }
		cmdkRefs = [];
		var html = '';
		if (cmds.length) {
			html += '<div class="cmdk-group">命令</div>';
			cmds.forEach(function (it) {
				var idx = cmdkRefs.length;
				cmdkRefs.push(it);
				var tag = it.href ? 'a' : 'button';
				html += '<' + tag + ' class="cmdk-item" data-idx="' + idx + '"'
					+ (it.href ? ' href="' + escapeHtml(safeHref(it.href)) + '"' : ' type="button"') + '>'
					+ '<span class="cmdk-item-ico" aria-hidden="true">' + kIcon(it.ico) + '</span>'
					+ '<span class="cmdk-item-main"><span class="cmdk-item-title">' + escapeHtml(it.title) + '</span>'
					+ (it.meta ? '<span class="cmdk-item-meta">' + escapeHtml(it.meta) + '</span>' : '')
					+ '</span></' + tag + '>';
			});
		}
		if (statusHtml) { html += '<p class="cmdk-status">' + escapeHtml(statusHtml) + '</p>'; }
		if (arts.length) {
			html += '<div class="cmdk-group">' + (SEM.on ? 'AI 语义结果' : '文章') + '</div>';
			arts.forEach(function (it) {
				var idx = cmdkRefs.length;
				cmdkRefs.push({ href: it.link });
				html += '<a class="cmdk-item" data-idx="' + idx + '" href="' + escapeHtml(safeHref(it.link)) + '">'
					+ '<span class="cmdk-item-ico" aria-hidden="true">' + kIcon('search') + '</span>'
					+ '<span class="cmdk-item-main"><span class="cmdk-item-title">' + escapeHtml(it.title) + '</span>'
					+ '<span class="cmdk-item-meta">' + escapeHtml([it.kind, it.date, it.topic].filter(Boolean).join(' · '))
					+ (SEM.on && typeof it.score === 'number' ? ' · ' + Math.round(it.score * 100) + '% 相似' : '')
					+ '</span></span></a>';
			});
		}
		if (!cmds.length && !arts.length && !statusHtml) { html = '<p class="cmdk-empty">没有匹配的命令或文章。</p>'; }
		cmdkList.innerHTML = html;
		cmdkActive = cmds.length ? 0 : (arts.length ? cmdkRefs.length - arts.length : -1);
		cmdkPaintActive();
	}
	var cmdkCloseTimer = null;
	function clearCmdkCloseTimer() {
		if (cmdkCloseTimer) { clearTimeout(cmdkCloseTimer); cmdkCloseTimer = null; }
	}
	function openCmdk() {
		if (!cmdkEl || cmdkEl.open) { return; }
		clearCmdkCloseTimer();
		if (cmdkInput) { cmdkInput.value = ''; }
		cmdkRender(cmdkCommands(), [], '');
		if (typeof cmdkEl.showModal === 'function') { cmdkEl.showModal(); }
		else { cmdkEl.setAttribute('open', ''); }
		if (cmdkInput) { cmdkInput.focus({ preventScroll: true }); }
	}
	function closeCmdk() {
		if (!cmdkEl || !cmdkEl.open) { return; }
		clearCmdkCloseTimer();
		if (reduceMotion) { cmdkEl.close(); return; }
		cmdkEl.classList.add('is-closing');
		var done = function () {
			clearCmdkCloseTimer();
			cmdkEl.classList.remove('is-closing');
			cmdkEl.removeEventListener('transitionend', done);
			cmdkEl.close();
		};
		cmdkEl.addEventListener('transitionend', done);
		/* transitionend 在元素隐藏 / 过渡被打断时可能不触发，兜底关闭避免卡死 */
		cmdkCloseTimer = setTimeout(done, 320);
	}
	if (cmdkEl) {
		cmdkEl.addEventListener('cancel', function (e) { e.preventDefault(); closeCmdk(); });
		cmdkEl.addEventListener('click', function (e) { if (e.target === cmdkEl) { closeCmdk(); } });
	}
	if (cmdkInput) {
		cmdkInput.addEventListener('input', cmdkOnInput);
		cmdkInput.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
				e.preventDefault();
				if (!cmdkRefs.length) { return; }
				cmdkActive += e.key === 'ArrowDown' ? 1 : -1;
				if (cmdkActive >= cmdkRefs.length) { cmdkActive = 0; }
				if (cmdkActive < 0) { cmdkActive = cmdkRefs.length - 1; }
				cmdkPaintActive();
			} else if (e.key === 'Enter') {
				e.preventDefault();
				if (cmdkActive >= 0) {
					var el = cmdkList.querySelector('[data-idx="' + cmdkActive + '"]');
					if (el) { el.click(); }
				}
			}
		});
	}
	if (cmdkList) {
		/* 鼠标滑过即选中，点击即执行；链接项关面板后交给 Swup 接管跳转 */
		cmdkList.addEventListener('mousemove', function (e) {
			var item = e.target.closest ? e.target.closest('.cmdk-item') : null;
			if (!item) { return; }
			var idx = Number(item.getAttribute('data-idx'));
			if (idx !== cmdkActive) { cmdkActive = idx; cmdkPaintActive(); }
		});
		cmdkList.addEventListener('click', function (e) {
			var item = e.target.closest ? e.target.closest('.cmdk-item') : null;
			if (!item) { return; }
			var ref = cmdkRefs[Number(item.getAttribute('data-idx'))];
			closeCmdk();
			if (ref && ref.action) { e.preventDefault(); ref.action(); }
		});
	}
	if (cmdkAiBtn) {
		try { if (localStorage.getItem('pk.sem.on') === '1') { SEM.on = true; cmdkAiBtn.setAttribute('aria-pressed', 'true'); } } catch (e) { /* ignore */ }
		cmdkAiBtn.addEventListener('click', function () {
			SEM.on = !SEM.on;
			cmdkAiBtn.setAttribute('aria-pressed', SEM.on ? 'true' : 'false');
			try { localStorage.setItem('pk.sem.on', SEM.on ? '1' : '0'); } catch (e) { /* ignore */ }
			if (cmdkInput) { cmdkInput.focus({ preventScroll: true }); }
			cmdkOnInput();
		});
	}
	function cmdkOnInput() {
		if (!cmdkInput) { return; }
		var q = cmdkInput.value.trim();
		if (cmdkTimer) { clearTimeout(cmdkTimer); cmdkTimer = null; }
		if (!q) { cmdkRender(cmdkCommands(), [], ''); return; }
		cmdkTimer = setTimeout(function () {
			cmdkTimer = null;
			var mySeq = ++cmdkSeq;
			if (SEM.on) { semQuery(q, mySeq); }
			else {
				var cmds = cmdkFilterCmds(cmdkCommands(), q);
				cmdkRender(cmds, [], '搜索中…');
				fetch(ajaxUrl + '?action=pk_search&q=' + encodeURIComponent(q))
					.then(function (r) { return r.json(); })
					.then(function (res) {
						if (mySeq !== cmdkSeq) { return; }
						var items = (res && res.success && res.data && res.data.items) || [];
						if (mySeq === cmdkSeq) { cmdkRender(cmds, items, ''); }
					})
					.catch(function () {
						if (mySeq === cmdkSeq) { cmdkRender(cmds, [], '搜索失败，请重试'); }
					});
			}
		}, 220);
	}
	/* 全局快捷键：⌘K / Ctrl+K 开合（弹窗内再按一次是关闭） */
	document.addEventListener('keydown', function (e) {
		if (!uiOn('cmdkOn')) { return; }
		var k = (e.key || '').toLowerCase();
		if ((e.metaKey || e.ctrlKey) && k === 'k') {
			e.preventDefault();
			if (cmdkEl && cmdkEl.open) { closeCmdk(); } else { openCmdk(); }
		}
	});

	/* ---------- AI 语义搜索：transformers.js 浏览器内推理（可选，默认关） ----------
	   模型 multilingual-e5-small（q8 量化，约 70MB，一次下载浏览器缓存），
	   文章索引由 pk_post_index 提供，向量按 sig 缓存进 localStorage；服务端零推理。
	   SEM 状态本体已在命令面板段声明。 */
	var SEM_MODEL = 'Xenova/multilingual-e5-small';
	var SEM_VER = '3.7.5';
	/* jsDelivr 主站在国内时常不可达：按顺序试多个镜像节点，谁先用谁。
	   注意必须走 /+esm 端点——包根路径拿到的是 CJS bundle，import() 会解析失败。
	   模型权重默认在 huggingface.co（国内同样多半不通），改走 hf-mirror.com；
	   ORT 的 wasm 运行时跟随库所在的镜像目录。 */
	var SEM_LIBS = [
		'https://cdn.jsdelivr.net/npm/@huggingface/transformers@' + SEM_VER + '/+esm',
		'https://fastly.jsdelivr.net/npm/@huggingface/transformers@' + SEM_VER + '/+esm',
		'https://gcore.jsdelivr.net/npm/@huggingface/transformers@' + SEM_VER + '/+esm',
		'https://testingcf.jsdelivr.net/npm/@huggingface/transformers@' + SEM_VER + '/+esm'
	];
	var SEM_HF = 'https://hf-mirror.com';
	/* 模型权重同源自托管（uploads/pk-hf-cache，服务器侧预下载）：
	   零跨域、零 CDN 依赖，国内访客不再受 hf-mirror 抖动影响；
	   缓存缺失时（新部署未预热）回退 hf-mirror 在线拉取。
	   注意用 location.origin 而不是 homeUrl——两者在异域代理环境下会不一致。 */
	var SEM_SAME = location.origin + '/wp-content/uploads/pk-hf-cache/';
	var SEM_MAX = 200;

	function f32ToB64(f32) {
		var u8 = new Uint8Array(f32.buffer);
		var s = '';
		for (var i = 0; i < u8.length; i += 8192) {
			s += String.fromCharCode.apply(null, u8.subarray(i, i + 8192));
		}
		return btoa(s);
	}
	function b64ToF32(b) {
		var s = atob(b);
		var u8 = new Uint8Array(s.length);
		for (var i = 0; i < s.length; i++) { u8[i] = s.charCodeAt(i); }
		return new Float32Array(u8.buffer);
	}
	function semEnsurePipe(onProgress) {
		if (SEM.pipe) { return Promise.resolve(SEM.pipe); }
		var tryLib = function (i) {
			if (i >= SEM_LIBS.length) { return Promise.reject(new Error('所有镜像均不可达')); }
			var base = SEM_LIBS[i].replace(/\/\+esm$/, '');
			return import(SEM_LIBS[i]).then(function (mod) {
				try {
					/* wasm 运行时跟库同镜像，避免 cdn.jsdelivr.net 不可达时卡死 */
					mod.env.allowLocalModels = false;
					mod.env.remotePathTemplate = '{model}/resolve/{revision}/';
					mod.env.backends.onnx.wasm.wasmPaths = base + '/dist/';
				} catch (eCfg) { /* 环境配置失败不致命，库可能自带兜底 */ }
				return mod;
			}).catch(function () { return tryLib(i + 1); });
		};
		return tryLib(0).then(function (mod) {
			/* 权重来源两级：同源缓存 → hf-mirror 在线；前一来源 404 / 失败自动降级 */
			var hosts = [SEM_SAME, SEM_HF];
			var lastErr = null;
			var tryHost = function (i) {
				if (i >= hosts.length) { return Promise.reject(lastErr || new Error('模型不可用')); }
				try { mod.env.remoteHost = hosts[i]; } catch (eHost) { /* ignore */ }
				return mod.pipeline('feature-extraction', SEM_MODEL, {
					dtype: 'q8',
					progress_callback: function (p) {
						/* 只报主模型权重文件的进度，配置/tokenizer 的 100% 闪一下没意义 */
						if (onProgress && p && p.status === 'progress' && p.file && /\.onnx(_quantized)?$/.test(p.file)) {
							onProgress(p.progress);
						}
					}
				}).catch(function (err) {
					lastErr = err;
					return tryHost(i + 1);
				});
			};
			return tryHost(0);
		}).then(function (pipe) { SEM.pipe = pipe; return pipe; });
	}
	/* e5 系模型要求 query:/passage: 前缀；逐条编码并回传进度 */
	function semEmbed(texts, onProgress) {
		return semEnsurePipe(function (pc) { if (onProgress) { onProgress('AI 模型下载中 ' + Math.round(pc) + '%'); } })
			.then(function (pipe) {
				var out = [];
				var step = function (i) {
					if (i >= texts.length) { return Promise.resolve(out); }
					return Promise.resolve(pipe(texts[i], { pooling: 'mean', normalize: true })).then(function (t) {
						out.push(Float32Array.from(t.data));
						if (onProgress && (i % 8 === 7 || i === texts.length - 1)) { onProgress('建立索引 ' + (i + 1) + '/' + texts.length); }
						return step(i + 1);
					});
				};
				return step(0);
			});
	}
	function semBoot(onMsg) {
		if (SEM.ready) { return Promise.resolve(); }
		return fetch(ajaxUrl + '?action=pk_post_index')
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res || !res.success) { throw new Error('索引接口不可用'); }
				SEM.sig = res.data.sig;
				SEM.docs = (res.data.items || []).slice(0, SEM_MAX);
				var cached = null;
				try { cached = localStorage.getItem('pk.sem.vecs.' + SEM.sig); } catch (e2) { /* ignore */ }
				if (cached) {
					try {
						var arr = JSON.parse(cached);
						if (arr.length === SEM.docs.length) {
							SEM.vecs = arr.map(b64ToF32);
							SEM.ready = true;
							return;
						}
					} catch (e3) { /* 缓存损坏就重算 */ }
				}
				return semEnsurePipe(function (pc) { if (onMsg) { onMsg('AI 模型下载中 ' + Math.round(pc) + '%'); } })
					.then(function () {
						return semEmbed(SEM.docs.map(function (d) {
							return ('passage: ' + ((d.title || '') + '。' + (d.excerpt || ''))).slice(0, 240);
						}), function (msg) { if (onMsg) { onMsg(msg); } });
					})
					.then(function (vecs) {
						SEM.vecs = vecs;
						SEM.ready = true;
						try { localStorage.setItem('pk.sem.vecs.' + SEM.sig, JSON.stringify(vecs.map(f32ToB64))); } catch (e4) { /* 容量不足不缓存 */ }
					});
			});
	}
	function semRun(q, mySeq) {
		return semEmbed(['query: ' + q]).then(function (vecs) {
			if (mySeq !== cmdkSeq) { return; }
			var qv = vecs[0];
			var scored = [];
			for (var i = 0; i < SEM.vecs.length; i++) {
				var v = SEM.vecs[i];
				var dot = 0;
				for (var j = 0; j < qv.length; j++) { dot += qv[j] * v[j]; }
				scored.push({ s: dot, i: i });
			}
			scored.sort(function (a, b) { return b.s - a.s; });
			var arts = scored.slice(0, 8).map(function (x) {
				var d = SEM.docs[x.i];
				return {
					id: d.id, title: d.title, link: d.link, excerpt: d.excerpt,
					kind: d.kind, date: d.date, topic: d.topic, score: x.s
				};
			});
			cmdkRender(cmdkFilterCmds(cmdkCommands(), q), arts, '');
		}).catch(function () {
			if (mySeq === cmdkSeq) { cmdkRender(cmdkFilterCmds(cmdkCommands(), q), [], '语义搜索失败，请重试'); }
		});
	}
	function semQuery(q, mySeq) {
		if (!SEM.ready) {
			cmdkRender(cmdkFilterCmds(cmdkCommands(), q), [], 'AI 组件准备中…');
			semBoot(function (msg) {
				if (mySeq === cmdkSeq && cmdkList) {
					var st = cmdkList.querySelector('.cmdk-status');
					if (st) { st.textContent = msg; }
				}
			}).then(function () {
				if (mySeq === cmdkSeq) { semRun(q, mySeq); }
			}).catch(function (err) {
				if (mySeq === cmdkSeq) {
					cmdkRender(cmdkFilterCmds(cmdkCommands(), q), [], 'AI 组件加载失败：' + ((err && err.message) || '请检查网络后重试'));
				}
			});
			return;
		}
		cmdkRender(cmdkFilterCmds(cmdkCommands(), q), [], '语义匹配中…');
		semRun(q, mySeq);
	}


	/* ---------- 首页幻灯片：一屏一卡，箭头 / 键盘切换 + 自动轮播 ----------
	   切换仿 Swiper 的 creative 效果：新卡从一侧 120% 处滑进来盖住旧卡，
	   旧卡原地缩小到 .92、抬高 10px、后退 1px（全程 600ms，和参考站一致） */
	var slideRoot = null, slideIndex = 0, slideTimer = 0, slideHold = false, slideBusy = false;

	function slideItems() {
		return slideRoot ? slideRoot.querySelectorAll('.pk-slide') : [];
	}
	function slideStop() {
		if (slideTimer) { clearInterval(slideTimer); slideTimer = 0; }
	}
	/* 语义状态：aria / inert / 屏读播报，视觉交给 class */
	function slideA11y() {
		var items = slideItems();
		if (!items.length) { return; }
		Array.prototype.forEach.call(items, function (el, i) {
			var on = i === slideIndex;
			el.setAttribute('aria-hidden', on ? 'false' : 'true');
			/* inert：隐藏张里的链接不进 Tab 序列；老浏览器忽略赋值 */
			try { el.inert = !on; } catch (err) { /* ignore */ }
		});
		var live = slideRoot.querySelector('[data-slide-live]');
		if (live) {
			var title = items[slideIndex].querySelector('.pk-slide-title');
			live.textContent = (slideIndex + 1) + ' / ' + items.length + '：' + (title ? title.textContent.trim() : '');
		}
		/* 圆点指示（手机端可见）：当前张点亮并拉长 */
		[].forEach.call(slideRoot.querySelectorAll('.pk-slide-dot'), function (d, i) {
			d.classList.toggle('is-on', i === slideIndex);
			d.setAttribute('aria-current', i === slideIndex ? 'true' : 'false');
		});
	}
	/* 初始化 / 换页回来后：无动画直接落到第 N 张 */
	function slideReset() {
		var items = slideItems();
		if (!items.length) { return; }
		if (slideIndex >= items.length) { slideIndex = 0; }
		Array.prototype.forEach.call(items, function (el, i) {
			el.style.transition = 'none';
			el.style.transform = '';
			el.style.opacity = '';
			el.classList.remove('is-in', 'is-out');
			if (i === slideIndex) { el.classList.add('is-active'); }
			else { el.classList.remove('is-active'); }
		});
		slideBusy = false;
		slideA11y();
		/* 复位完成后再把类里的过渡放回来，否则下一次切换会瞬间到位 */
		window.requestAnimationFrame(function () {
			Array.prototype.forEach.call(slideItems(), function (el) { el.style.transition = ''; });
		});
	}
	function slideGo(step) {
		var items = slideItems();
		if (!items.length || slideBusy) { return; }
		var nextIndex = (slideIndex + step + items.length) % items.length;
		if (nextIndex === slideIndex) { return; }
		var from = items[slideIndex], to = items[nextIndex];

		if (reduceMotion) {
			slideIndex = nextIndex;
			slideReset();
			slideRestart();
			return;
		}

		slideBusy = true;
		/* 新张：先关过渡贴到舞台外侧，强制重排后再放开，滑到位 */
		to.classList.remove('is-active', 'is-out');
		to.classList.add('is-in');
		to.style.transition = 'none';
		to.style.opacity = '1';
		to.style.transform = 'translate3d(' + (step > 0 ? 120 : -120) + '%,0,0)';
		void to.offsetWidth;
		to.style.transition = '';
		to.style.transform = 'translate3d(0,0,0)';

		/* 旧张：原地缩小 / 抬高 / 后退，被新张逐步盖住 */
		from.classList.remove('is-in');
		from.classList.add('is-out');

		slideIndex = nextIndex;
		slideA11y();
		slideRestart();

		/* 过渡结束后落定：新张转正，旧张回到底层待命（已经看不见了） */
		setTimeout(function () {
			to.classList.remove('is-in');
			to.classList.add('is-active');
			to.style.transform = '';
			to.style.opacity = '';
			from.classList.remove('is-out', 'is-active');
			slideBusy = false;
		}, 620);
	}
	function slideRestart() {
		slideStop();
		if (!slideRoot || slideHold || reduceMotion || slideItems().length < 2) { return; }
		var ms = parseInt(slideRoot.getAttribute('data-interval'), 10) || 6000;
		slideTimer = setInterval(function () {
			if (document.hidden) { return; }
			slideGo(1);
		}, ms);
	}
	/* 卡片高度按「最长的一条文字」自适应：短标题不再留一大块空白，
	   同时所有张共用一个高度（取最大值），切张时不会跳。CSS 里 440px 是兜底。
	   手机端（0.13.8 起）文字压在图上、高度由 CSS clamp 固定，不再参与测量。 */
	function slideFit() {
		if (!slideRoot) { return; }
		if (window.innerWidth < 1024) {
			slideRoot.style.removeProperty('--pk-slide-h');
			return;
		}
		var max = 0;
		[].forEach.call(slideRoot.querySelectorAll('.pk-slide-body'), function (body) {
			var cs = getComputedStyle(body);
			var h = parseFloat(cs.paddingTop || 0) + parseFloat(cs.paddingBottom || 0);
			[].forEach.call(body.children, function (el) {
				var s = getComputedStyle(el);
				h += el.getBoundingClientRect().height + parseFloat(s.marginTop || 0) + parseFloat(s.marginBottom || 0);
			});
			if (h > max) { max = h; }
		});
		if (!max) { return; }
		slideRoot.style.setProperty('--pk-slide-h', Math.round(Math.max(420, Math.min(520, max))) + 'px');
	}

	/* 公告栏：滚动时长按内容实际宽度算，条目多寡都保持同一个速度（约 55px/s）
	   不这样做的话条目一多就像快进，只有两条又慢得发闷；
	   内容总宽不足一屏时再复制几份，否则滚到后半段右边会露出空白 */
	var tickerHost = null, tickerBase = '';
	function pkTickerInit() {
		var bar = document.getElementById('pk-ticker');
		if (!bar) { return; }
		var view = bar.querySelector('.pk-ticker-view');
		var track = bar.querySelector('[data-ticker-track]');
		var groups = bar.querySelectorAll('.pk-ticker-group');
		if (!view || !track || groups.length < 2) { return; }
		if (bar !== tickerHost) {
			tickerHost = bar;
			tickerBase = groups[0].innerHTML;
		}
		var need = view.getBoundingClientRect().width;
		if (!need || !tickerBase) { return; }
		var html = tickerBase, w = 0;
		for (var i = 0; i < 8; i++) {
			groups[0].innerHTML = html;
			w = groups[0].getBoundingClientRect().width;
			if (w >= need) { break; }
			html += tickerBase;
		}
		/* 副本跟正本等宽，-50% 才能正好接回开头 */
		groups[1].innerHTML = groups[0].innerHTML;
		if (!w) { return; }
		track.style.animationDuration = Math.max(10, Math.round(w / 55)) + 's';
	}

	/* 个人中心（0.13.6）：头像本地预览 + 注销确认弹窗
	   纯增强：脚本没跑时表单照样能提交（服务端校验不依赖前端） */
	function pkAccountInit() {
		var input = document.querySelector('[data-pk-avatar-input]');
		var box = document.querySelector('[data-pk-avatar-preview]');
		if (input && box && input.getAttribute('data-pk-bound') !== '1') {
			input.setAttribute('data-pk-bound', '1');
			input.addEventListener('change', function () {
				var f = input.files && input.files[0];
				if (!f) { return; }
				if (f.size > 2 * 1024 * 1024) {
					alert('头像不能超过 2MB');
					input.value = '';
					return;
				}
				var url = URL.createObjectURL(f);
				var img = box.querySelector('img.user-avatar');
				if (img) {
					img.src = url;
					return;
				}
				var el = document.createElement('img');
				el.className = 'user-avatar';
				el.width = 64;
				el.height = 64;
				el.alt = '';
				el.src = url;
				box.innerHTML = '';
				box.appendChild(el);
			});
		}

		var dlg = document.getElementById('pk-del-dialog');
		if (!dlg || dlg.getAttribute('data-pk-bound') === '1') { return; }
		dlg.setAttribute('data-pk-bound', '1');
		var open = document.querySelector('[data-pk-del-open]');
		if (open) {
			open.addEventListener('click', function () {
				if (typeof dlg.showModal === 'function') { dlg.showModal(); }
				else { dlg.setAttribute('open', 'open'); }
			});
		}
		var cancel = dlg.querySelector('[data-pk-del-cancel]');
		if (cancel) {
			cancel.addEventListener('click', function () {
				if (typeof dlg.close === 'function') { dlg.close(); }
				else { dlg.removeAttribute('open'); }
			});
		}
		/* 输入「注销」两个字才放开确认按钮，防止手滑 */
		var okIn = dlg.querySelector('[data-pk-del-ok]');
		var submit = dlg.querySelector('[data-pk-del-submit]');
		if (okIn && submit) {
			okIn.addEventListener('input', function () {
				submit.disabled = okIn.value.trim() !== '注销';
			});
		}
		dlg.addEventListener('click', function (e) {
			if (e.target === dlg) { dlg.close(); }
		});
	}

	/* 换页后 #swup-wrapper 整块换新，重新取引用并重绑悬停 / 键盘 */
	function pkSlidesInit() {
		var host = document.getElementById('pk-slides');
		if (!host) {
			slideStop();
			slideRoot = null;
			return;
		}
		if (host === slideRoot) { return; }
		slideRoot = host;
		slideIndex = 0;
		slideHold = false;
		slideBusy = false;
		/* 只有一张时没有「上/下一张」可言，把箭头收起来 */
		var count = host.querySelectorAll('.pk-slide').length;
		host.classList.toggle('is-single', count < 2);
		/* 手机端圆点：点按直达某一张（CSS 里只在 <1024 显示） */
		var dots = host.querySelector('.pk-slide-dots');
		if (!dots && count > 1) {
			dots = document.createElement('div');
			dots.className = 'pk-slide-dots';
			for (var di = 0; di < count; di++) {
				var dot = document.createElement('button');
				dot.type = 'button';
				dot.className = 'pk-slide-dot';
				dot.setAttribute('aria-label', '第 ' + (di + 1) + ' 张');
				dot.setAttribute('data-go', di);
				dots.appendChild(dot);
			}
			var live = host.querySelector('[data-slide-live]');
			if (live && live.parentNode) { live.parentNode.insertBefore(dots, live); }
		}
		host.addEventListener('click', function (e) {
			var d = e.target.closest && e.target.closest('.pk-slide-dot');
			if (!d) { return; }
			var go = parseInt(d.getAttribute('data-go'), 10);
			if (isNaN(go)) { return; }
			slideGo(go - slideIndex);
		});
		/* 手机左右滑切张：横位移够大且明显大于纵位移才触发，不干扰页面滚动 */
		var swX = 0, swY = 0, swAt = 0;
		host.addEventListener('touchstart', function (e) {
			if (e.touches.length !== 1) { swAt = 0; return; }
			swX = e.touches[0].clientX;
			swY = e.touches[0].clientY;
			swAt = Date.now();
		}, { passive: true });
		host.addEventListener('touchend', function (e) {
			var t0 = swAt;
			swAt = 0;
			if (!t0 || Date.now() - t0 > 1200) { return; }
			var t = e.changedTouches[0];
			var dx = t.clientX - swX, dy = t.clientY - swY;
			if (Math.abs(dx) < 48 || Math.abs(dx) < Math.abs(dy) * 1.4) { return; }
			slideGo(dx < 0 ? 1 : -1);
		}, { passive: true });
		host.addEventListener('mouseenter', function () { slideHold = true; slideStop(); });
		host.addEventListener('mouseleave', function () { slideHold = false; slideRestart(); });
		host.addEventListener('focusin', function () { slideHold = true; slideStop(); });
		host.addEventListener('focusout', function (e) {
			if (host.contains(e.relatedTarget)) { return; }
			/* 切张时非激活张变 inert，焦点会被浏览器踢出去（relatedTarget 为 null）。
			   鼠标还停在卡片上就维持暂停，等 mouseleave 再恢复自动播放。 */
			if (host.matches(':hover')) { return; }
			slideHold = false;
			slideRestart();
		});
		host.addEventListener('keydown', function (e) {
			if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') { return; }
			e.preventDefault();
			slideGo(e.key === 'ArrowLeft' ? -1 : 1);
		});
		slideReset();
		slideFit();
		slideRestart();
	}

	/* 宽度变了标题可能换行数，重量一次高度（收藏 / 侧栏折叠也会改内容宽度）；
	   公告栏同理会换宽度，一并重算滚动时长 */
	var slideFitTimer = 0;
	window.addEventListener('resize', function () {
		clearTimeout(slideFitTimer);
		slideFitTimer = setTimeout(function () {
			slideFit();
			pkTickerInit();
		}, 160);
	});

	/* 箭头委托到 document：幻灯片节点会被 pjax 替换，直接绑定会失效 */
	document.addEventListener('click', function (e) {
		var btn = e.target && e.target.closest ? e.target.closest('[data-slide]') : null;
		if (!btn) { return; }
		e.preventDefault();
		if (!slideRoot) { pkSlidesInit(); }
		slideGo(btn.getAttribute('data-slide') === 'prev' ? -1 : 1);
	});

	/* 切回标签页继续轮播，切走时停表，不在后台空转 */
	document.addEventListener('visibilitychange', function () {
		if (document.hidden) { slideStop(); } else { slideRestart(); }
	});

	var feed = null, noResult = null;
	var activeTerm = 'all';
	var siteTitle = document.title;
	var homeUrlAttr = homeUrl;

	/* 文章容器 / 分类 / 筛选：Swup 交换 #swup-wrapper 后由 refreshRefs() 重新取引用 */
	function refreshRefs() {
		feed = document.getElementById('feed');
		noResult = document.getElementById('no-result');
	}
	refreshRefs();

	/* ---------- 分类筛选 + 即时搜索 + 分页（数据来自卡片 data-terms / data-search） ---------- */
	var lastQuery = null, lastTerm = null, page = 1, filteredCards = [];
	try { page = Math.max(1, parseInt((new URLSearchParams(location.search)).get('page'), 10) || 1); } catch (err) { /* ignore */ }
	function applyFilter() {
		if (!feed) { return; }
		var q = searchInput ? searchInput.value.trim().toLowerCase() : '';
		/* 筛选条件变了才回第 1 页：从文章层返回列表时保持原页码 */
		if (q !== lastQuery || activeTerm !== lastTerm) { page = 1; lastQuery = q; lastTerm = activeTerm; }
		filteredCards = [];
		Array.prototype.forEach.call(feed.querySelectorAll('.card'), function (card) {
			var terms = (card.dataset.terms || '').split(/\s+/);
			var okTerm = activeTerm === 'all' || terms.indexOf(activeTerm) !== -1;
			var okQuery = !q || (card.dataset.search || '').toLowerCase().indexOf(q) !== -1;
			if (okTerm && okQuery) { filteredCards.push(card); }
		});
		paintFeed();
	}

	/* ---------- 分页：每页 = 网格真实列数 x 3 行；移动端（flex 单列）不分页 ---------- */
	var CHEV_L = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>';
	var CHEV_R = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>';
	function feedCols() {
		if (!feed) { return 0; }
		var t = getComputedStyle(feed).gridTemplateColumns;
		if (!t || t === 'none') { return 0; }
		return t.split(' ').filter(Boolean).length;
	}
	function perPage() {
		var c = feedCols();
		return c > 0 ? c * 3 : 0; /* 0 = 不分页 */
	}
	function totalPages() {
		var pp = perPage();
		return pp > 0 ? Math.max(1, Math.ceil(filteredCards.length / pp)) : 1;
	}
	function pageWindow(cur, total) {
		if (total <= 7) { var all = []; for (var i = 1; i <= total; i++) { all.push(i); } return all; }
		var nums = [1, 2, total - 1, total, cur - 1, cur, cur + 1]
			.filter(function (n) { return n >= 1 && n <= total; })
			.sort(function (a, b) { return a - b; });
		var out = [], prev = 0;
		nums.forEach(function (n) {
			if (n - prev > 1) { out.push('gap'); }
			out.push(n); prev = n;
		});
		return out;
	}
	function paintFeed() {
		if (!feed) { return; }
		var pp = perPage();
		var total = pp > 0 ? Math.max(1, Math.ceil(filteredCards.length / pp)) : 1;
		if (page > total) { page = total; }
		var start = pp > 0 ? (page - 1) * pp : 0;
		var end = pp > 0 ? start + pp : filteredCards.length;
		for (var i = 0; i < filteredCards.length; i++) {
			filteredCards[i].classList.toggle('is-hidden', i < start || i >= end);
		}
		if (noResult) { noResult.classList.toggle('show', filteredCards.length === 0); }
		/* 分页器挂在 feed 之后（#swup-wrapper 内，随 Swup 交换重建，这里按 id 重新取） */
		var pager = document.getElementById('pk-pager');
		if (total <= 1) { if (pager) { pager.remove(); } return; }
		if (!pager) {
			pager = document.createElement('nav');
			pager.id = 'pk-pager';
			pager.className = 'pk-pager';
			pager.setAttribute('aria-label', '文章分页');
			feed.insertAdjacentElement('afterend', pager);
		}
		var html = '<button type="button" data-pk-page="prev" aria-label="上一页"' + (page <= 1 ? ' disabled' : '') + '>' + CHEV_L + '</button>';
		pageWindow(page, total).forEach(function (n) {
			if (n === 'gap') { html += '<span class="pk-pager-gap" aria-hidden="true">…</span>'; }
			else { html += '<button type="button" data-pk-page="' + n + '"' + (n === page ? ' class="is-active" aria-current="page"' : '') + '>' + n + '</button>'; }
		});
		html += '<button type="button" data-pk-page="next" aria-label="下一页"' + (page >= total ? ' disabled' : '') + '>' + CHEV_R + '</button>';
		pager.innerHTML = html;
	}
	function goToPage(n, scroll) {
		page = Math.min(Math.max(1, n), totalPages());
		paintFeed();
		if (scroll) { window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' }); }
		try {
			var u = new URL(location.href);
			if (page > 1 && perPage() > 0) { u.searchParams.set('page', String(page)); } else { u.searchParams.delete('page'); }
			history.replaceState(history.state, '', u.pathname + u.search + u.hash);
		} catch (err) { /* ignore */ }
	}
	document.addEventListener('click', function (e) {
		if (!e.target.closest) { return; }
		var btn = e.target.closest('#pk-pager [data-pk-page]');
		if (!btn) { return; }
		var v = btn.getAttribute('data-pk-page');
		goToPage(v === 'prev' ? page - 1 : (v === 'next' ? page + 1 : parseInt(v, 10)), true);
	});

	/* --- 首页订阅卡片：POST 到 pianke/v1/subscribe，成功后原地收起表单 ---
	   委托在 document 上，pjax 换页后的新卡片无需重新绑定 */
	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form || !form.matches || !form.matches('[data-pk-sub-form]')) { return; }
		e.preventDefault();
		var host = form.closest('[data-pk-sub]');
		if (!host || host.classList.contains('is-done')) { return; }
		var input = form.querySelector('input[name="email"]');
		var btn = form.querySelector('[data-pk-sub-submit]');
		var done = host.querySelector('[data-pk-sub-done]');
		var msg = host.querySelector('[data-pk-sub-msg]');
		var mail = input ? input.value.trim() : '';
		var say = function (text, bad) {
			if (!msg) { return; }
			msg.textContent = text || '';
			msg.classList.toggle('is-error', !!bad);
		};
		if (!mail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(mail)) {
			say('请输入有效的电子邮箱。', true);
			if (input) { input.focus(); }
			return;
		}
		var url = form.getAttribute('data-rest') || (window.pkUser && window.pkUser.subscribeUrl) || '';
		if (!url) { say('订阅服务暂不可用，请稍后再试。', true); return; }
		var kpkSub = kpkScan(mail);
		if (kpkSub) { showToast('邮箱包含违禁词「' + kpkSub + '」，可能无法通过审核'); }
		var label = btn ? (btn.querySelector('span') || btn) : null;
		var idle = label ? label.textContent : '';
		if (btn) { btn.disabled = true; }
		if (label) { label.textContent = '提交中'; }
		say('');
		fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ email: mail, from: location.href })
		}).then(function (r) {
			return r.json().then(function (data) { return { ok: r.ok, data: data || {} }; });
		}).then(function (res) {
			if (!res.ok || !res.data.ok) {
				throw new Error(res.data.message || '提交失败，请稍后再试。');
			}
			host.classList.add('is-done');
			if (done) {
				done.textContent = res.data.message || '订阅成功，感谢关注。';
				done.hidden = false;
			}
			say('');
		}).catch(function (err) {
			if (btn) { btn.disabled = false; }
			if (label) { label.textContent = idle; }
			say((err && err.message) || '提交失败，请稍后再试。', true);
		});
	});
	/* 键盘翻页：← → / PageUp PageDown；阅读层 / 搜索 / 分享 / 灯箱 / 输入框打开时不抢键 */
	document.addEventListener('keydown', function (e) {
		if (!uiOn('keyNavOn')) { return; }
		var k = e.key;
		var dir = (k === 'ArrowRight' || k === 'PageDown') ? 1 : (k === 'ArrowLeft' || k === 'PageUp') ? -1 : 0;
		if (!dir) { return; }
		if (root.classList.contains('reading')) { return; }
		if (noticeDialog && noticeDialog.open) { return; }
		if (pkLightboxActive()) { return; }
		if (searchDialog && searchDialog.open) { return; }
		if (shareDialog && shareDialog.open) { return; }
		var t = e.target;
		if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable)) { return; }
		if (!feed || perPage() <= 0 || filteredCards.length <= perPage()) { return; }
		e.preventDefault();
		goToPage(page + dir, true);
	});
	/* 窗口变化导致列数变化（侧栏折叠 / 大屏断点）时按新列数重排 */
	var rzTimer = null;
	window.addEventListener('resize', function () {
		clearTimeout(rzTimer);
		rzTimer = setTimeout(function () { if (feed) { paintFeed(); } }, 120);
	});
	/* 从文章页搜索回车跳转到 ?pk_s= 后，回到首页自动带入关键词并展开搜索 */
	var initialS = (new URLSearchParams(location.search)).get('pk_s');
	if (initialS) {
		searchInput.value = initialS;
		applyFilter();
		if (isPc() && pcSearchInput) {
			/* PC：带入顶栏内联搜索框并直接拉结果 */
			pcSearchInput.value = initialS;
			doSearch(initialS, pcSearchBox);
			showPcResults();
		} else {
			openSearch();
		}
	}

	/* 初始态：按 URL 里的 ?page= 分页 + 首页搜索词过滤（分类归档由服务端过滤，这里只管首页） */
	applyFilter();

	/* ---------- 全站 pjax：公共区同步 + 登录态 + 表单无刷新 ----------
	   Swup 只交换 #swup-wrapper，header / footer / body class 都不在容器里。
	   登录态、导航高亮、页脚显隐、标题这些「公共区」必须按新文档同步，
	   否则就会出现「URL 变了、页面还是上一页的样子」。 */

	/* ============================================================
	   0.11.16 · 预渲染 / HTML 预取 / 手势翻篇 / 灵动岛胶囊
	   ============================================================ */

	/* 预渲染页面会执行 JS：统计、自动播放、登录态接口都会「假触发」。
	   所有有副作用的初始化必须等 prerenderingchange（真正被用户看到）再跑。 */
	function pkWhenActivated(fn) {
		if (document.prerendering) {
			document.addEventListener('prerenderingchange', fn, { once: true });
		} else {
			fn();
		}
	}
	function pkSpecClean(u) {
		if (!u) { return ''; }
		try {
			var x = new URL(u, location.href);
			if (x.origin !== location.origin) { return ''; }
			if (/\/user\/?$/.test(x.pathname) || x.pathname.indexOf('/wp-admin') === 0 || x.pathname.indexOf('/go/') === 0) { return ''; }
			if (x.href === location.href) { return ''; }
			return x.href;
		} catch (e) { return ''; }
	}

	/* 首屏可见卡片的空闲预取：移动端没有 hover、用户也可能打开就点第一张。
	   只取前 2 张、错峰 1.2s 起，既不抢 LCP 资源也不打出一片并发回源。
	   pkPrefetch 内部自己有去重与 6 条上限，这里不重复检查 map。 */
	var autoPrefetched = 0;
	function pkAutoPrefetch(u) {
		if (autoPrefetched >= 2) { return; }
		var url = pkSpecClean(u);
		if (!url) { return; }
		autoPrefetched++;
		setTimeout(function () { pkPrefetch(url); }, 1200 + autoPrefetched * 350);
	}

	/* --- 动态 Speculation Rules：hover / 卡片进视口 / 相邻篇，最多 4 个候选 --- */
	var specSupported = !!(window.HTMLScriptElement && HTMLScriptElement.supports && HTMLScriptElement.supports('speculationrules'));
	var specCandidates = [];
	var specEl = null;
	var specTimer = null;
	var specIO = null;
	function pkSpecFlush() {
		if (!specSupported) { return; }
		if (!specCandidates.length) {
			if (specEl) { specEl.remove(); specEl = null; }
			return;
		}
		var rules = { prerender: [{ source: 'list', urls: specCandidates.slice(0, 4), eagerness: 'conservative' }] };
		if (!specEl) {
			specEl = document.createElement('script');
			specEl.type = 'speculationrules';
			specEl.id = 'pk-spec-live';
			document.head.appendChild(specEl);
		}
		specEl.textContent = JSON.stringify(rules);
	}
	function pkSpecPush(u) {
		var url = pkSpecClean(u);
		if (!url || !specSupported) { return; }
		specCandidates = [url].concat(specCandidates.filter(function (x) { return x !== url; })).slice(0, 4);
		clearTimeout(specTimer);
		specTimer = setTimeout(pkSpecFlush, 180);
	}
	function pkCollectSpec() {
		if (document.prerendering) { return; }
		specCandidates = [];
		if (specIO) { specIO.disconnect(); specIO = null; }
		var nav = document.getElementById('pk-post-nav');
		if (nav) {
			pkSpecPush(nav.getAttribute('data-next'));
			pkSpecPush(nav.getAttribute('data-prev'));
		}
		var cards = Array.prototype.slice.call(document.querySelectorAll('.feed .card[href]')).slice(0, 8);
		if (window.IntersectionObserver && cards.length) {
			specIO = new IntersectionObserver(function (entries) {
				entries.forEach(function (en) {
					if (en.isIntersecting) {
						var href = en.target.getAttribute('href');
						pkSpecPush(href);
						pkAutoPrefetch(href);
					}
				});
			}, { rootMargin: '180px' });
			cards.forEach(function (c) { specIO.observe(c); });
		}
	}
	document.addEventListener('pointerover', function (e) {
		if (e.pointerType === 'touch' || document.prerendering) { return; }
		var c = e.target && e.target.closest ? e.target.closest('.feed .card[href]') : null;
		if (c) {
			pkSpecPush(c.getAttribute('href'));
			/* 浏览器预渲染的页面 Swup 用不上（拦截点击后不会 activate），
			   真正能消除回源等待的是这份 HTML 预取 + Swup 自己的页面缓存 */
			pkPrefetch(c.getAttribute('href'));
		}
	});

	/* --- 自己的 HTML 预取缓存：Swup 拦截点击时浏览器 prerender 不会被 activate，
	   真正让「滑过去页面已经好了」的是这份缓存，直接拿整页 HTML 交换、零网络等待 --- */
	var prefetchMap = {};
	var prefetchBusy = {};
	var prefetchKeys = [];
	function pkPrefetch(u) {
		var url = pkSpecClean(u);
		if (!url || prefetchMap[url] || prefetchBusy[url]) { return; }
		prefetchBusy[url] = true;
		fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (r) { return r.ok ? r.text() : ''; })
			.then(function (html) {
				if (html && html.indexOf('id="swup-wrapper"') !== -1) {
					prefetchMap[url] = html;
					prefetchKeys.push(url);
					if (prefetchKeys.length > 6) { delete prefetchMap[prefetchKeys.shift()]; }
					/* 同步喂给 Swup 的 page cache：点击时 page:load 直接命中、
					   完全跳过 fetch，hover 过的卡片可以做到零等待换页。
					   key 用绝对 URL，与 visit.to.url 一致。 */
					try {
						if (swup && swup.cache && typeof swup.cache.set === 'function') {
							swup.cache.set(url, { url: url, html: html });
						}
					} catch (eCache) { /* 缓存不可用时照常走网络 */ }
				}
			})
			.catch(function () {})
			.then(function () { delete prefetchBusy[url]; });
	}
	function pkPrefetchAdjacent() {
		if (document.prerendering) { pkWhenActivated(pkPrefetchAdjacent); return; }
		var nav = document.getElementById('pk-post-nav');
		if (!nav) { return; }
		var older = nav.getAttribute('data-prev');
		var newer = nav.getAttribute('data-next');
		var run = function () { pkPrefetch(older); pkPrefetch(newer); };
		if (window.requestIdleCallback) { requestIdleCallback(run, { timeout: 1500 }); }
		else { setTimeout(run, 400); }
	}
	/* 阅读方向约定（0.11.19 修正，手势 / 顶部提示 / 方向键共用这一套）：
	   WordPress 的 previous_post 是「时间更早」的一篇，next_post 是「时间更新」的一篇。
	   读文章是沿时间倒序往下走，所以更早的那篇才是「下一篇」，更新的那篇是「上一篇」。
	   手势：右滑 → 下一篇（更早），左滑 → 上一篇（更新）。 */
	function pkAdjacent() {
		var nav = document.getElementById('pk-post-nav');
		if (!nav) { return null; }
		return {
			older: nav.getAttribute('data-prev') || '',
			olderTitle: nav.getAttribute('data-prev-title') || '',
			newer: nav.getAttribute('data-next') || '',
			newerTitle: nav.getAttribute('data-next-title') || ''
		};
	}

	/* --- 跟手手势翻篇：纯 Pointer Events，橡皮筋阻尼 + 速度判定 --- */
	/* 0.13.24：PC（≥1024）取消左右滑与方向键翻篇，统一走文章底部的
	   「上一篇 / 下一篇」按钮。手机 / 平板保留跟手手势。
	   手势专项在桌面视口跑，注入 window.pkSwipeTestMode = true 显式打开算法验证。 */
	function pkSwipeEnabled() {
		if (!uiOn('swipeOn')) { return false; }
		return !window.matchMedia('(min-width: 1024px)').matches || window.pkSwipeTestMode === true;
	}
	var swipe = null;
	var suppressClick = false;
	var suppressClickTimer = null;
	var swipeHint = document.getElementById('pk-swipe-hint');
	var swipeKind = document.getElementById('pk-swipe-kind');
	var swipeLabel = document.getElementById('pk-swipe-label');
	/* kind = 下一篇 / 上一篇；edge 时只说「没有更多」，所以 kind 传空 */
	function pkSwipeHint(kind, title, on) {
		if (!swipeHint) { return; }
		if (!on) { swipeHint.classList.remove('is-show'); return; }
		if (swipeKind) { swipeKind.textContent = kind || ''; }
		if (swipeLabel) { swipeLabel.textContent = title || ''; }
		swipeHint.classList.toggle('is-edge', !kind);
		swipeHint.classList.add('is-show');
	}
	function pkNavigateSwipe(url) {
		var abs = pkSpecClean(url) || url;
		var cached = prefetchMap[abs] || '';
		/* 翻篇是「原地换一篇」，用 replace 不进历史栈：
		   push 会让「返回列表」要多按几次，且手动 pushState 的条目没有 Swup 的 state，
		   后退时会出现「地址变了、内容没换」。 */
		if (cached) { pkSwapDocument(cached, abs, false); }
		else if (swup) { swup.navigate(abs, { history: 'replace' }); }
		else { window.location.assign(abs); }
	}

	/* 只取「文章内容」两层：头图和正文列。
	   不动 .detail-inner —— transform 会给 position:fixed 的悬浮条造新的包含块，
	   一旦平移整层，dock / 侧栏就会跟着跑。 */
	function pkSwipeParts(detail) {
		var hero = detail.querySelector('.detail-hero');
		return {
			hero: hero,
			body: detail.querySelector('.detail-body'),
			/* 封面图单独取出来做视差；marquee / 引言头图没有 img 时自动跳过 */
			heroImg: hero ? hero.querySelector('img') : null
		};
	}
	/* 0.13.17：跟手翻篇的层次特效。
	   当前页 = 跟手平移 + 轻微缩小 + 被纯黑罩层压暗，退到「后一层」；
	   目标页 = 从略小 / 略淡 / 带圆角的「卡片」放大到与真实页面完全重合，
	   封面图反向微移做视差。全部是 transform / opacity / border-radius 这类
	   合成属性，不用渐变 / 阴影 / 模糊；prefers-reduced-motion 时 k=0 自动退回纯位移。
	   注意：hero 与 body 必须拿到完全相同的 transform 字符串（专项测断言两者相等），
	   且都以 translate3d(<整数>px,0,0) 开头；终点 pane 必须是 translate3d(0px,…)。 */
	function pkSwipePaint(s, x) {
		var w = window.innerWidth || 375;
		var p = Math.min(1, Math.abs(x) / w);
		var k = reduceMotion ? 0 : 1;
		var q = p * k;
		var cur = 'translate3d(' + x + 'px,0,0) scale(' + (1 - 0.06 * q).toFixed(4) + ')';
		if (s.hero) { s.hero.style.transform = cur; }
		if (s.body) { s.body.style.transform = cur; }
		if (s.veil) { s.veil.style.opacity = (0.42 * q).toFixed(3); }
		/* 封面反向微移；scale 留 8% 余量，视差位移不会把边角拉出白色缝隙 */
		if (s.heroImg) {
			s.heroImg.style.transform = 'translate3d(' + (x * 0.10 * k).toFixed(1) + 'px,0,0) scale(' + (1 + 0.08 * k).toFixed(3) + ')';
		}
		if (s.pane) {
			/* 跟手：左滑去下一篇，目标从右侧进来；右滑去上一篇，目标从左侧进来 */
			var px = (x > 0 ? x - w : x + w);
			s.pane.style.transform = 'translate3d(' + px + 'px,0,0) scale(' + (1 - 0.07 * (1 - q)).toFixed(4) + ')';
			s.pane.style.opacity = (1 - 0.45 * (1 - q) * k).toFixed(3);
			s.pane.style.borderRadius = (12 * (1 - q) * k).toFixed(1) + 'px';
			if (s.paneImg) {
				s.paneImg.style.transform = 'translate3d(' + (-px * 0.10 * k).toFixed(1) + 'px,0,0) scale(' + (1 + 0.08 * k).toFixed(3) + ')';
			}
		}
	}
	function pkSwipeClear(s) {
		var clean = function (el) {
			if (!el) { return; }
			el.style.transform = '';
			el.style.opacity = '';
			el.style.borderRadius = '';
		};
		clean(s.hero);
		clean(s.body);
		if (s.heroImg) { s.heroImg.style.transform = ''; }
		if (s.paneImg) { s.paneImg.style.transform = ''; }
		clean(s.pane);
		if (s.veil && s.veil.parentNode) { s.veil.parentNode.removeChild(s.veil); }
		s.veil = null;
		s.paneImg = null;
		if (s.layer && s.layer.parentNode) { s.layer.parentNode.removeChild(s.layer); }
		s.pane = null;
		s.layer = null;
	}
	/* 用预取好的整页 HTML 造一屏「目标文章」，跟手一起进来，翻过去时不再「滑走→跳一下」 */
	function pkSwipePreview(url) {
		var abs = pkSpecClean(url);
		var html = abs ? prefetchMap[abs] || '' : '';
		var host = document.querySelector('.overlay.single-view');
		if (!html || !host) { return null; }
		var doc;
		try { doc = new DOMParser().parseFromString(html, 'text/html'); } catch (err) { return null; }
		var src = doc.querySelector('.detail-inner');
		if (!src) { return null; }
		var clone = src.cloneNode(true);
		/* 预览里不要悬浮条 / 侧栏 / 评论，避免出现两份 dock 或重复的评论占位 */
		var junk = clone.querySelectorAll('.pk-dock,.read-rail,#pk-comments,.pk-swipe-hint');
		for (var i = junk.length - 1; i >= 0; i--) {
			if (junk[i].parentNode) { junk[i].parentNode.removeChild(junk[i]); }
		}
		/* 去掉重复 id，防止 #d-title / #share-toggle 这类选择器命中预览层 */
		var ids = clone.querySelectorAll('[id]');
		for (var j = ids.length - 1; j >= 0; j--) { ids[j].removeAttribute('id'); }
		var layer = document.createElement('div');
		layer.className = 'pk-swipe-layer';
		layer.setAttribute('aria-hidden', 'true');
		var pane = document.createElement('div');
		pane.className = 'pk-swipe-pane';
		var shell = document.createElement('div');
		shell.className = 'detail pk-swipe-detail';
		shell.appendChild(clone);
		pane.appendChild(shell);
		layer.appendChild(pane);
		host.appendChild(layer);
		return { layer: layer, pane: pane, img: clone.querySelector('.detail-hero img') };
	}
	function pkSwipeStart(e) {
		if (!pkSwipeEnabled()) { return; }
		if (cmDrag || document.prerendering || swipe || !e.isPrimary) { return; }
		var t = e.target;
		if (!t || !t.closest) { return; }
		var detail = t.closest('.detail');
		if (!detail || detail.id !== 'detail' || !isSingleUrl(location.href)) { return; }
		if (t.closest('input,textarea,select,[contenteditable="true"],.pk-dock,.detail-comments,.pk-toc,.read-rail,dialog,.pk-island,.pk-lightbox,.pswp,.prose pre,.pk-gallery')) { return; }
		var adj = pkAdjacent();
		if (!adj || (!adj.older && !adj.newer)) { return; }
		var overlay = detail.closest('.overlay');
		var parts = pkSwipeParts(detail);
		if (!parts.body || !overlay) { return; }
		swipe = {
			id: e.pointerId, x0: e.clientX, y0: e.clientY, t0: Date.now(),
			adj: adj, overlay: overlay, hero: parts.hero, body: parts.body, heroImg: parts.heroImg,
			pane: null, layer: null, paneImg: null, veil: null,
			paneDir: '', axis: null, dx: 0, url: '', moved: false
		};
		/* 手指刚落下就开始预热两个方向 */
		pkPrefetch(adj.older); pkPrefetch(adj.newer);
		pkSpecPush(adj.older); pkSpecPush(adj.newer);
	}
	function pkSwipeMove(e) {
		if (!swipe || e.pointerId !== swipe.id) { return; }
		var dx = e.clientX - swipe.x0;
		var dy = e.clientY - swipe.y0;
		if (!swipe.axis) {
			if (Math.abs(dx) < 12 && Math.abs(dy) < 12) { return; }
			swipe.axis = Math.abs(dx) > Math.abs(dy) * 1.2 ? 'x' : 'y';
			if (swipe.axis !== 'x') { swipe = null; return; }
			/* 压暗当前页的纯黑罩层：z-1 在内容之上、目标预览（z-2）之下，
			   目标页滑进来时始终清晰，当前页露出的部分随进度变暗 */
			var veil = document.createElement('div');
			veil.className = 'pk-swipe-veil';
			veil.setAttribute('aria-hidden', 'true');
			swipe.overlay.appendChild(veil);
			swipe.veil = veil;
		}
		var w = window.innerWidth || 375;
		/* 跟手：左滑（内容左移、下一篇从右侧进来）= 下一篇（时间更早的那篇），
		   右滑（内容右移、上一篇从左侧进来）= 上一篇（时间更新的那篇） */
		var toOlder = dx < 0;
		var url = toOlder ? swipe.adj.older : swipe.adj.newer;
		var title = toOlder ? swipe.adj.olderTitle : swipe.adj.newerTitle;
		var kind = toOlder ? '下一篇' : '上一篇';
		var edge = !url;
		var raw = Math.abs(dx);
		/* 橡皮筋：拉得越远阻尼越大；没有目标文章时只留一点弹性 */
		var damped = raw * (1 - Math.min(raw / (w * 1.8), 0.62));
		damped = Math.min(damped, w * 0.42) * (edge ? 0.3 : 1);
		var signed = (dx > 0 ? 1 : -1) * damped;
		/* 方向变了才重建目标预览，同一方向只建一次 */
		var dir = toOlder ? 'older' : 'newer';
		if (url && swipe.paneDir !== dir) {
			swipe.paneDir = dir;
			var oldLayer = swipe.layer;
			var built = pkSwipePreview(url);
			if (built) { swipe.layer = built.layer; swipe.pane = built.pane; swipe.paneImg = built.img; }
			if (oldLayer && oldLayer.parentNode) { oldLayer.parentNode.removeChild(oldLayer); }
		}
		swipe.moved = true;
		swipe.dx = dx;
		swipe.url = url;
		swipe.overlay.classList.add('is-swiping');
		pkSwipePaint(swipe, Math.round(signed));
		/* 到尽头也别闷着：明确告诉用户「没有更多」，比什么都不显示更清楚 */
		pkSwipeHint(edge ? '' : kind, edge ? (toOlder ? '已经是最后一篇' : '已经是最新一篇') : title, raw > 24);
		if (e.cancelable) { e.preventDefault(); }
	}
	function pkSwipeEnd(e) {
		if (!swipe || (e && e.pointerId !== swipe.id)) { return; }
		var s = swipe;
		swipe = null;
		var w = window.innerWidth || 375;
		var dt = Math.max(1, Date.now() - s.t0);
		var v = Math.abs(s.dx) / dt;
		s.overlay.classList.remove('is-swiping');
		s.overlay.classList.add('is-snapping');
		pkSwipeHint('', false);
		var pass = s.url && (Math.abs(s.dx) > w * 0.26 || (v > 0.45 && Math.abs(s.dx) > 48));
		if (pass) {
			/* 手势结束会漏一次 click，capture 阶段吃掉，避免误触正文链接 */
			suppressClick = true;
			if (suppressClickTimer) { clearTimeout(suppressClickTimer); }
			suppressClickTimer = setTimeout(function () { suppressClick = false; suppressClickTimer = null; }, 320);
			var url = s.url;
			/* 正文继续滑出，目标文章从另一侧滑到正中：交换那一刻两者都在 0 位，看不出接缝。
			   交换必须等吸附动画（.26s）走完，否则内容还在半路就被换掉，会看到跳一下。 */
			pkSwipePaint(s, (s.dx > 0 ? 1 : -1) * w);
			setTimeout(function () {
				pkNavigateSwipe(url);
				/* 目标页没有容器 / 交换失败时兜底复位，别把内容留在屏幕外 */
				if (document.contains(s.body)) {
					pkSwipePaint(s, 0);
					pkSwipeClear(s);
					s.overlay.classList.remove('is-snapping');
				}
			}, reduceMotion ? 0 : 300);
		} else {
			pkSwipePaint(s, 0);
			setTimeout(function () {
				s.overlay.classList.remove('is-snapping');
				pkSwipeClear(s);
			}, reduceMotion ? 0 : 320);
		}
	}
	document.addEventListener('pointerdown', pkSwipeStart);
	document.addEventListener('pointermove', pkSwipeMove, { passive: false });
	document.addEventListener('pointerup', pkSwipeEnd);
	document.addEventListener('pointercancel', pkSwipeEnd);
	document.addEventListener('click', function (e) {
		if (!suppressClick) { return; }
		if (suppressClickTimer) { clearTimeout(suppressClickTimer); suppressClickTimer = null; }
		e.preventDefault(); e.stopPropagation(); suppressClick = false;
	}, true);
	/* 桌面键盘左右方向键与手势共用一套相邻数据 */
	document.addEventListener('keydown', function (e) {
		if (!uiOn('keyNavOn')) { return; }
		if (e.altKey || e.ctrlKey || e.metaKey || e.shiftKey) { return; }
		if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') { return; }
		/* 0.13.24：PC 上方向键也不再翻篇，避免与「按钮翻页」并存造成两套入口 */
		if (!pkSwipeEnabled()) { return; }
		if (!isSingleUrl(location.href)) { return; }
		/* 灯箱 / 搜索 / 分享 / 评论这些弹层里方向键有自己的用途，别顺手把文章翻走。
		   PhotoSwipe 是 div 不是 dialog，必须单独判一次，否则左右键会把文章翻走。 */
		if (pkLightboxActive() || document.querySelector('dialog[open]') || commentsOpen()) { return; }
		var t = e.target;
		if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable)) { return; }
		var adj = pkAdjacent();
		if (!adj) { return; }
		/* ← 与左滑同向（下一篇 = 更早），→ 与右滑同向（上一篇 = 更新） */
		var url = e.key === 'ArrowLeft' ? adj.older : adj.newer;
		if (!url) { return; }
		e.preventDefault();
		pkNavigateSwipe(url);
	});

	/* --- 灵动岛胶囊：阅读百分比 / 朗读进度 / 回顶部 / 专注模式 --- */
	var island = document.getElementById('pk-island');
	var islandFill = document.getElementById('pk-island-fill');
	var islandPct = document.getElementById('pk-island-pct');
	var islandTimer = null;
	var islandLastY = 0;
	var speechPct = 0;
	var speech = {
		supported: !!(window.speechSynthesis && window.SpeechSynthesisUtterance),
		chunks: [], idx: 0
	};
	var speechActive = false;
	var speechPaused = false;
	var idleAbort = null;
	function pkIslandScroll() {
		if (!island || island.hidden) { return; }
		var y = detailEl ? detailEl.scrollTop : (window.scrollY || 0);
		var pct = Math.max(0, Math.min(100, Math.round(y / imm.maxScroll * 100)));
		if (islandFill) { islandFill.style.width = pct + '%'; }
		if (islandPct) { islandPct.textContent = speechActive ? '朗读 ' + speechPct + '%' : pct + '%'; }
		if (root.classList.contains('pk-focus')) {
			island.classList.remove('is-hidden'); islandLastY = y; return;
		}
		/* 向下滚隐去，停手 1.1s 淡回；向上滚立刻回来 */
		if (y > islandLastY + 6) {
			island.classList.add('is-hidden');
			clearTimeout(islandTimer);
			islandTimer = setTimeout(function () { island.classList.remove('is-hidden'); }, 1100);
		} else if (y < islandLastY - 4) {
			clearTimeout(islandTimer);
			island.classList.remove('is-hidden');
		}
		islandLastY = y;
	}
	function pkIslandVisible() {
		if (!island) { return; }
		var single = isSingleUrl(location.href) || !!document.querySelector('.overlay.single-view:not([hidden])');
		island.hidden = !single;
		if (!single) {
			pkStopSpeech();
			root.classList.remove('pk-focus');
			island.classList.remove('is-focus', 'is-hidden');
			islandLastY = 0;
			if (islandFill) { islandFill.style.width = '0%'; }
			if (islandPct) { islandPct.textContent = '0%'; }
		}
	}
	function pkSpeechChunks() {
		var prose = document.querySelector('.detail .prose');
		if (!prose) { return []; }
		var txt = (prose.innerText || prose.textContent || '').replace(/\s+/g, ' ').trim();
		var parts = txt.match(/[^。！？.!?；;]+[。！？.!?；;]?/g) || [];
		var chunks = [], buf = '';
		parts.forEach(function (s) {
			if ((buf + s).length > 170) { if (buf.trim()) { chunks.push(buf); } buf = s; }
			else { buf += s; }
		});
		if (buf.trim()) { chunks.push(buf); }
		return chunks;
	}
	function pkSpeakNext() {
		if (!speechActive || speech.idx >= speech.chunks.length) { pkStopSpeech(); return; }
		var u = new SpeechSynthesisUtterance(speech.chunks[speech.idx]);
		u.lang = 'zh-CN';
		u.rate = 1;
		u.onend = function () {
			if (!speechActive) { return; }
			speech.idx++;
			pkSpeakNext();
		};
		u.onboundary = function (ev) {
			if (!ev || typeof ev.charIndex !== 'number') { return; }
			var done = 0, total = 0;
			speech.chunks.forEach(function (c, i) { total += c.length; if (i < speech.idx) { done += c.length; } });
			speechPct = Math.min(99, Math.round((done + ev.charIndex) / Math.max(1, total) * 100));
			pkIslandScroll();
		};
		try { window.speechSynthesis.speak(u); } catch (e) { pkStopSpeech(); }
	}
	function pkStartSpeech() {
		if (!speech.supported) { showToast('当前浏览器不支持朗读'); return; }
		try { window.speechSynthesis.cancel(); } catch (e) {}
		speech.chunks = pkSpeechChunks();
		if (!speech.chunks.length) { showToast('这篇文章没有可朗读的正文'); return; }
		speech.idx = 0;
		speechActive = true;
		speechPaused = false;
		speechPct = 0;
		if (island) { island.classList.add('is-reading'); }
		pkSpeakNext();
		pkStartIdle();
	}
	function pkToggleSpeech() {
		if (!speechActive) { pkStartSpeech(); return; }
		if (speechPaused) {
			try { window.speechSynthesis.resume(); } catch (e) {}
			speechPaused = false;
			if (island) { island.classList.add('is-reading'); }
		} else {
			try { window.speechSynthesis.pause(); } catch (e) {}
			speechPaused = true;
			if (island) { island.classList.remove('is-reading'); }
		}
	}
	function pkStopSpeech() {
		if (speech.supported) { try { window.speechSynthesis.cancel(); } catch (e) {} }
		speechActive = false;
		speechPaused = false;
		speech.idx = 0;
		speechPct = 0;
		if (island) { island.classList.remove('is-reading'); }
		pkStopIdle();
	}
	/* Idle Detection 仅 Chrome：人离开 / 锁屏时自动暂停朗读，权限在点击朗读的手势里申请 */
	function pkStartIdle() {
		if (document.prerendering || !window.IdleDetector || !navigator.permissions) { return; }
		pkStopIdle();
		navigator.permissions.query({ name: 'idle-detection' }).then(function (st) {
			if (st.state !== 'granted' || !speechActive) { return; }
			try {
				idleAbort = new AbortController();
				var det = new IdleDetector();
				det.addEventListener('change', function () {
					if (!speechActive || speechPaused) { return; }
					if (det.userState === 'idle' || det.screenState === 'locked') {
						try { window.speechSynthesis.pause(); } catch (err) {}
						speechPaused = true;
						if (island) { island.classList.remove('is-reading'); }
					}
				});
				det.start({ threshold: 60000, signal: idleAbort.signal }).catch(function () {});
			} catch (err) {}
		}).catch(function () {});
	}
	function pkStopIdle() {
		if (idleAbort) { try { idleAbort.abort(); } catch (e) {} idleAbort = null; }
	}
	document.addEventListener('click', function (e) {
		var t = e.target && e.target.closest ? e.target.closest('[data-pk-island]') : null;
		if (!t) { return; }
		e.preventDefault();
		var act = t.getAttribute('data-pk-island');
		if (act === 'top') {
			scrollReading(0);
			if (island) { island.classList.remove('is-hidden'); }
		} else if (act === 'read') {
			if (!speechActive && window.IdleDetector && typeof IdleDetector.requestPermission === 'function') {
				try { IdleDetector.requestPermission().catch(function () {}); } catch (err) {}
			}
			pkToggleSpeech();
		} else if (act === 'focus') {
			var on = root.classList.toggle('pk-focus');
			if (island) { island.classList.toggle('is-focus', on); island.classList.remove('is-hidden'); }
		}
	});
	/* 目录当前项：IntersectionObserver 互斥高亮，不用 scroll 监听 */
	var tocIO = null;
	function pkTocObserve() {
		if (tocIO) { tocIO.disconnect(); tocIO = null; }
		var list = document.querySelector('.pk-toc-list');
		var heads = document.querySelectorAll('.detail .prose h2[id], .detail .prose h3[id]');
		if (!list || !heads.length || !window.IntersectionObserver) { return; }
		tocIO = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (!en.isIntersecting) { return; }
				Array.prototype.forEach.call(list.querySelectorAll('a.is-current'), function (a) { a.classList.remove('is-current'); });
				var want = '#' + en.target.id;
				var all = list.querySelectorAll('a');
				for (var i = 0; i < all.length; i++) {
					if (all[i].getAttribute('href') === want) { all[i].classList.add('is-current'); break; }
				}
			});
		}, { root: detailEl || null, rootMargin: '-84px 0px -68% 0px', threshold: 0 });
		Array.prototype.forEach.call(heads, function (h) { tocIO.observe(h); });
	}

	function isUserUrl(url) {
		if (!url) { return false; }
		try { return /\/user\/?$/.test(new URL(url, location.href).pathname); }
		catch (e) { return false; }
	}

	/* 登录 / 退出 / 信任设备自动登录都会改变点赞列表与 wp_rest nonce，
	   wp_localize_script 注入的 pkUser 不会随 pjax 重新执行，换页后拉一次最新配置 */
	/* 侧栏底部的登录 / 我的：登录、退出、信任设备自动登录都会换文案，
	   这里只改状态类与文字；头像图片 / 首字由 CSS 按 .is-on / .has-avatar 切换 */
	function pkSideUser(cfg) {
		var box = document.querySelector('.pc-side-user');
		if (!box) { return; }
		var on = !!cfg.loggedIn;
		var name = (cfg.name || '').trim();
		box.classList.toggle('is-on', on);
		/* 0.13.13：配置带 avatar 时把头像圈换成账号真实图片，没有才回退首字。
		   旧缓存页可能没有这个字段，只有明确下发时才改，避免把服务端直出的头像清掉 */
		if (typeof cfg.avatar === 'string') {
			var imgEl = box.querySelector('.pc-side-avatar-img');
			var avatar = cfg.avatar.trim();
			var hasImg = on && !!avatar;
			box.classList.toggle('has-avatar', hasImg);
			if (imgEl) {
				if (hasImg) { imgEl.setAttribute('src', avatar); }
				else { imgEl.removeAttribute('src'); }
			}
		}
		var nameEl = box.querySelector('.pc-side-user-name');
		var subEl = box.querySelector('.pc-side-user-sub');
		var txtEl = box.querySelector('.pc-side-avatar-txt');
		if (nameEl) { nameEl.textContent = on ? (name || '我的') : '登录'; }
		if (subEl) { subEl.textContent = on ? '个人中心' : '登录 / 注册'; }
		if (txtEl) { txtEl.textContent = on && name ? name.charAt(0) : ''; }
		box.setAttribute('aria-label', on ? '个人中心' : '登录');
		box.setAttribute('title', on ? '个人中心' : '登录');
		/* 未登录指到 /login/ 并挂上弹窗标记；登录后回到用户中心 */
		box.setAttribute('href', on ? (cfg.userUrl || '/user/') : (pkFront.authUrl || '/login/'));
		if (on) { box.removeAttribute('data-pk-auth-open'); }
		else { box.setAttribute('data-pk-auth-open', ''); }
	}
	function pkSetUser(cfg) {
		if (!cfg || typeof cfg.loggedIn === 'undefined') { return; }
		pkUser = cfg;
		window.pkUser = cfg;
		pkSideUser(cfg);
		syncSocial();
	}
	function refreshUserState() {
		/* 预渲染页面不能打 admin-ajax：等真正被用户看到再拉，否则会产生假登录态请求 */
		if (document.prerendering) {
			document.addEventListener('prerenderingchange', refreshUserState, { once: true });
			return Promise.resolve();
		}
		if (!ajaxUrl) { return Promise.resolve(); }
		return fetch(ajaxUrl + (ajaxUrl.indexOf('?') === -1 ? '?' : '&') + 'action=pk_user_state', {
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function (r) { return r.ok ? r.json() : null; })
			.then(function (cfg) { if (cfg) { pkSetUser(cfg); } })
			.catch(function () {});
	}

	/* 用新文档的公共区状态刷当前 DOM（容器外的部分）。
	   popstate 时 Swup 可能只给缓存片段、没有完整 document，此时按 URL 兜底判断 */
	/* 侧栏当前页高亮：按路径比对（忽略末尾斜杠与查询串），
	   归档 / 关于 / 联系等页面 pjax 换页后也能跟着换 */
	function pkAsideActive(url) {
		var path = '';
		try { path = new URL(url, location.href).pathname.replace(/\/+$/, '') || '/'; } catch (e) { return; }
		document.querySelectorAll('.pc-nav-link[href], .head-user[href], .pc-side-user[href]').forEach(function (a) {
			var own = '';
			try { own = new URL(a.getAttribute('href'), location.href).pathname.replace(/\/+$/, '') || '/'; }
			catch (e) { own = ''; }
			a.classList.toggle('is-active', !!own && own === path);
		});
	}
	/* ============================================================
	   0.13.22 阅读解锁：每个区块四选一（点赞 / 评论 / 密码 / 入群）。
	   卡片上的 data-pk-unlock-method 是唯一凭据：前端只显示、只提交这一种，
	   服务端也只按这一种判定。鉴权全在服务端，成功后刷新整页拿正文。
	   ============================================================ */
	var pkUnlockTimer = null;
	var pkUnlockBound = false;
	var pkUnlockCopyBound = false;

	/* 0.13.24：复制绑定码在锁定卡片和个人中心「群绑定」里都用同一个委托。
	   它必须独立于「本页有没有锁定卡片」绑定，否则 /user/ 上点复制没反应。 */
	function pkUnlockBindCopy() {
		if (pkUnlockCopyBound) { return; }
		pkUnlockCopyBound = true;
		document.addEventListener('click', function (e) {
			var t = e.target;
			var copy = t && t.closest ? t.closest('[data-pk-unlock-copy]') : null;
			if (!copy) { return; }
			e.preventDefault();
			var code = copy.getAttribute('data-pk-unlock-copy') || '';
			copyText(code).then(function (ok) {
				showToast(ok ? pkUnlockText('copied', '绑定码已复制，发给机器人即可') : '复制失败，请手动复制');
			});
		});
	}

	function pkUnlockCards() {
		return Array.prototype.slice.call(document.querySelectorAll('.pk-unlock[data-pk-unlock-poll]'));
	}

	function pkUnlockText(key, fallback) {
		var t = (window.pkUnlock && window.pkUnlock.text) || {};
		return t[key] || fallback;
	}

	function pkUnlockPostId(card) {
		return parseInt(card.getAttribute('data-pk-unlock-post'), 10) || 0;
	}

	function pkUnlockMethod(card) {
		return card.getAttribute('data-pk-unlock-method') || 'group';
	}

	/* 0.13.23：区块上设了专属密码时，卡片带一个不含明文的不透明锁标识 */
	function pkUnlockLock(card) {
		return card.getAttribute('data-pk-unlock-lock') || '';
	}

	function pkUnlockReload(msg) {
		showToast(msg || pkUnlockText('ok', '已解锁，正在刷新…'));
		if (pkUnlockTimer) { clearInterval(pkUnlockTimer); pkUnlockTimer = null; }
		setTimeout(function () { location.reload(); }, 400);
	}

	function pkUnlockRedeem(card, method, password) {
		var cfg = window.pkUnlock;
		if (!cfg || !cfg.redeemUrl) { return Promise.resolve(null); }
		var body = { method: method, post_id: pkUnlockPostId(card) };
		var lock = pkUnlockLock(card);
		if (lock) { body.lock = lock; }
		if (typeof password === 'string') { body.password = password; }
		return fetch(cfg.redeemUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce || '' },
			body: JSON.stringify(body)
		}).then(function (r) {
			return r.json().then(function (j) { return { status: r.status, json: j }; });
		});
	}

	function pkUnlockFocusComment() {
		var t = document.querySelector('#respond textarea[name="comment"]') || document.querySelector('#commentform textarea');
		if (t) {
			t.scrollIntoView({ behavior: 'smooth', block: 'center' });
			setTimeout(function () { t.focus(); }, 260);
			return;
		}
		var link = document.querySelector('.pk-dock [data-pk-action="comments"]');
		if (link) { link.click(); }
	}

	function pkUnlockRefresh() {
		var cfg = window.pkUnlock;
		if (!cfg || !cfg.enabled || !cfg.statusUrl) { return; }
		var cards = pkUnlockCards();
		if (!cards.length) { return; }
		var lockList = [];
		cards.forEach(function (c) {
			var l = pkUnlockLock(c);
			if (l && lockList.indexOf(l) === -1) { lockList.push(l); }
		});
		var url = cfg.statusUrl + (cfg.statusUrl.indexOf('?') === -1 ? '?' : '&')
			+ 'post_id=' + pkUnlockPostId(cards[0])
			+ (lockList.length ? '&locks=' + lockList.join(',') : '');
		var headers = { 'Accept': 'application/json' };
		if (cfg.nonce) { headers['X-WP-Nonce'] = cfg.nonce; }
		fetch(url, { credentials: 'same-origin', headers: headers, cache: 'no-store' })
			.then(function (r) { return r.ok ? r.json() : null; })
			.then(function (data) {
				if (!data) { return; }
				var reason = data.reason || '';
				var byMethod = data.activeByMethod || {};
				var byLock = data.activeByLock || {};
				var unlocked = false;
				pkUnlockCards().forEach(function (card) {
					var method = pkUnlockMethod(card);
					var lock = pkUnlockLock(card);
					/* 四选一：只用卡片自己那一种方式的判定；有专属密码时按锁单独判，不串其它锁 */
					var byWay = byMethod[method] || (!data.activeByMethod && data.active && data.method === method);
					if (lock ? byLock[lock] === true : byWay) { unlocked = true; }
					if (method !== 'group') { return; }
					card.setAttribute('data-pk-unlock-reason', reason);
					var note = card.querySelector('[data-pk-unlock-note]');
					var text = reason === 'left' ? pkUnlockText('left', '检测到你已不在群内，请重新进群。')
						: reason === 'expired' ? pkUnlockText('expired', '群内状态已过期，请重新进群。') : '';
					if (note && text) { note.textContent = text; }
				});
				/* 有任一卡片按自己选定的方式解锁了，刷新拿正文 */
				if (unlocked) { pkUnlockReload(); }
			})
			.catch(function () { /* 网络失败静默，下轮再试 */ });
	}

	function pkUnlockInit() {
		var cfg = window.pkUnlock;
		pkUnlockBindCopy();
		if (!cfg || !cfg.enabled || !pkUnlockCards().length) {
			if (pkUnlockTimer) { clearInterval(pkUnlockTimer); pkUnlockTimer = null; }
			return;
		}
		if (!pkUnlockBound) {
			pkUnlockBound = true;
			document.addEventListener('click', function (e) {
				var t = e.target;
				var way = t && t.closest ? t.closest('[data-pk-unlock-way]') : null;
				if (!way) { return; }
				e.preventDefault();
				var card = way.closest('.pk-unlock');
				if (!card || way.getAttribute('data-pk-unlock-busy') === '1') { return; }
				var method = way.getAttribute('data-pk-unlock-way') || '';
				/* 四选一：卡片没声明这种方式就不处理，防止被改过的 DOM 越权兑换 */
				if (method !== pkUnlockMethod(card)) { return; }
				way.setAttribute('data-pk-unlock-busy', '1');
				var done = function () { way.removeAttribute('data-pk-unlock-busy'); };
				var fail = function (r) {
					done();
					var msg = r && r.json && (r.json.message || (typeof r.json === 'string' ? r.json : ''));
					if (r && r.status === 401 && typeof authOpen === 'function') { authOpen('login'); }
					showToast(msg || pkUnlockText('failed', '解锁没成功，稍后再试'));
				};
				if (method === 'password') {
					var input = card.querySelector('[data-pk-unlock-pass-input]');
					var pw = input ? input.value.trim() : '';
					if (!pw) {
						done();
						showToast(pkUnlockText('noPassword', '请先输入解锁密码'));
						if (input) { input.focus(); }
						return;
					}
					pkUnlockRedeem(card, 'password', pw).then(function (r) {
						if (r && r.status === 200 && r.json && r.json.unlocked) { pkUnlockReload(); return; }
						done();
						var srv = r && r.json && r.json.message;
						showToast(r && r.status === 429 && srv ? srv : pkUnlockText('badPassword', '解锁密码不正确'));
					}).catch(function () { fail(null); });
					return;
				}
				if (method === 'comment') {
					pkUnlockRedeem(card, 'comment').then(function (r) {
						if (r && r.status === 200 && r.json && r.json.unlocked) { pkUnlockReload(); return; }
						done();
						if (r && r.status === 401) {
							if (typeof authOpen === 'function') { authOpen('login'); }
							showToast(pkUnlockText('needLogin', '请先登录后再用这种方式解锁'));
							return;
						}
						showToast(pkUnlockText('needComment', '先在本文发表一条评论，发布后会自动解锁'));
						pkUnlockFocusComment();
					}).catch(function () { fail(null); });
					return;
				}
				if (method === 'like') {
					pkUnlockRedeem(card, 'like').then(function (r) {
						if (r && r.status === 200 && r.json && r.json.unlocked) { pkUnlockReload('已点赞，正在解锁…'); return; }
						fail(r);
					}).catch(function () { fail(null); });
					return;
				}
				done();
			});
			/* 密码框回车直接提交，不必去点按钮 */
			document.addEventListener('keydown', function (e) {
				if (e.key !== 'Enter' || !e.target || !e.target.matches) { return; }
				if (!e.target.matches('[data-pk-unlock-pass-input]')) { return; }
				e.preventDefault();
				var card = e.target.closest('.pk-unlock');
				var way = card ? card.querySelector('[data-pk-unlock-way="password"]') : null;
				if (way) { way.click(); }
			});
		}
		if (pkUnlockTimer) { clearInterval(pkUnlockTimer); pkUnlockTimer = null; }
		pkUnlockRefresh();
		var ms = parseInt(cfg.pollMs, 10);
		if (!ms || ms < 3000) { ms = 8000; }
		pkUnlockTimer = setInterval(pkUnlockRefresh, ms);
	}

	/* ---------- 作者卡一言签名（0.13.28）----------
	   服务端只在签名来源=api 时输出 data-pk-hitokoto；这里 fetch 接口取一句话替换。
	   同一 URL + 同一接口只拉一次（会话内缓存），Swup 换页回来不重复请求。
	   接口兼容：JSON 对象取 hitokoto/content/text/quote 字段，或直接返回纯文本。 */
	var pkHitokotoCache = {};
	function pkHitokotoScan() {
		var nodes = document.querySelectorAll('[data-pk-hitokoto]');
		for (var i = 0; i < nodes.length; i++) {
			(function (el) {
				var api = el.getAttribute('data-pk-hitokoto');
				if (!api) { return; }
				el.removeAttribute('data-pk-hitokoto'); // 只处理一次，换页回来看缓存
				var fallback = el.textContent;
				if (pkHitokotoCache[api] !== undefined) {
					if (pkHitokotoCache[api]) { el.textContent = pkHitokotoCache[api]; }
					return;
				}
				el.classList.add('is-loading');
				var done = false;
				var timer = setTimeout(function () { finish(''); }, 3000);
				function finish(text) {
					if (done) { return; }
					done = true;
					clearTimeout(timer);
					el.classList.remove('is-loading');
					var out = (text || '').trim();
					if (out) {
						el.textContent = out;
						pkHitokotoCache[api] = out;
					} else {
						pkHitokotoCache[api] = ''; // 失败记住，本次会话不再重试
					}
				}
				function pick(data) {
					if (data && typeof data === 'object') {
						return data.hitokoto || data.content || data.text || data.quote || '';
					}
					return (typeof data === 'string') ? data : '';
				}
				try {
					fetch(api, { credentials: 'omit', cache: 'no-store' })
						.then(function (r) { return r.ok ? r.text() : ''; })
						.then(function (raw) {
							if (!raw) { finish(''); return; }
							var text = raw;
							try {
								var trimmed = raw.trim();
								if (trimmed.charAt(0) === '{' || trimmed.charAt(0) === '[') { text = pick(JSON.parse(trimmed)); }
							} catch (err) { /* 非 JSON，按纯文本用 */ }
							finish(text);
						})
						.catch(function () { finish(''); });
				} catch (err) { finish(''); }
			})(nodes[i]);
		}
	}

	function syncChrome(doc, url, toSingle) {
		/* 0.13.16：pjax / 登录换页后一律回到折叠态，抽屉不会残留盖住新页面正文 */
		pkDrawerOn(false);
		/* 0.13.18：动作条同样回到折叠态，换到新文章不会带着上一条的展开态 */
		pkDockSet(false);
		pkAsideActive(url);
		if (doc) {
			if (doc.body && doc.body.className) { document.body.className = doc.body.className; }
			if (doc.title) { document.title = doc.title; }
			var toHead = doc.querySelector('#site-head');
			if (siteHead && toHead) { siteHead.classList.toggle('is-single', toHead.classList.contains('is-single')); }
		}
		var foot = document.querySelector('[data-pk-foot-wrap]');
		var toFoot = doc ? doc.querySelector('[data-pk-foot-wrap]') : null;
		if (foot) {
			foot.hidden = toFoot ? toFoot.hasAttribute('hidden') : (!!toSingle || isUserUrl(url));
		}
		pkIslandVisible();
		/* 0.13.20：换页后重扫锁定卡片，重开状态轮询 */
		pkUnlockInit();
		/* 0.13.28：换页后重扫作者卡一言签名 */
		pkHitokotoScan();
	}

	/* 登录 / 注册 / 退出走 fetch：302 跟随后的整页 HTML 直接拿来换容器，
	   不触发浏览器跳转，也不用再请求一次目标页（成功 / 失败都能原样渲染） */
	function pkSwapDocument(html, url, push) {
		var doc = new DOMParser().parseFromString(html, 'text/html');
		var inEl = doc.querySelector('#swup-wrapper');
		var outEl = document.getElementById('swup-wrapper');
		if (!inEl || !outEl || !window.pkPageView) { window.location.assign(url); return; }
		outEl.replaceWith(inEl.cloneNode(true));
		try {
			/* 手势翻篇沿用 replace（见 pkNavigateSwipe）；登录 / 退出同样是原地换页 */
			if (push) { history.pushState({}, '', url); pkSwupActive = true; }
			else { history.replaceState(Object.assign({}, history.state || {}, { url: url }), '', url); }
		} catch (e) { /* ignore */ }
		window.pkPageView(url, doc);
	}
	/* ---------- 登录 / 注册：整页 /login/ 与全站弹窗共用一套卡片 ----------
	   弹窗打开时 pushState 到 /login/，地址栏与整页形态完全一致：
	   弹窗里刷新就是整页，整页也能直接分享；× / ESC / 点遮罩 / 后退 都回原地址。 */
	var authDialog = $('pk-auth');
	/* 离站到 /login/、/user/ 时弹窗要摘掉（页面上已有同一张卡片），
	   节点连同事件监听一起在这儿寄存，回到普通页面再挂回去 */
	var authDialogStash = null;
	var authOrigin = '';
	var authClosing = false;
	/* 只改当前这条历史记录的 URL（不压新记录）：Swup 也在这条记录上存了
	   自己的 state，所以合并着写，避免两套 history 互相打架。
	   关闭时原样写回来源地址，后退键交给 popstate 兜底。 */
	function authWriteUrl(url) {
		try {
			var st = Object.assign({}, history.state || {});
			st.pkAuth = 1;
			st.url = url;
			history.replaceState(st, '', url);
		} catch (e) { /* ignore */ }
	}
	function authRestoreUrl() {
		var url = authOrigin || (location.pathname + location.search + location.hash);
		var write = function () {
			try {
				var st = Object.assign({}, history.state || {});
				delete st.pkAuth;
				st.url = url;
				history.replaceState(st, '', url);
			} catch (e) { /* ignore */ }
		};
		write();
		/* Swup 的初始化 visit / 延迟 history 同步可能晚到，把弹窗期间记下的
		   /login/ 又抢写回地址栏（线上慢速回源时尤其明显）。关窗后再校正两次，
		   且只在地址确实仍是登录页、弹窗没重新打开时才动手。 */
		[350, 1400].forEach(function (ms) {
			setTimeout(function () {
				if (!authOrigin) { return; }
				if (authDialog && authDialog.open) { return; }
				if (!authIsUrl(location.href)) { return; }
				write();
			}, ms);
		});
	}

	function authUrlFor(mode) {
		var base = pkFront.authUrl || '/login/';
		if (mode !== 'register') { return base; }
		return base + (base.indexOf('?') === -1 ? '?' : '&') + 'mode=register';
	}
	function authIsUrl(url) {
		var base = (pkFront.authUrl || '/login/').replace(/\/+$/, '');
		try {
			return new URL(url, location.href).pathname.replace(/\/+$/, '') === base;
		} catch (e) { return false; }
	}
	/* 卡片内的 tab 与标题：两种形态共用，切换只动 DOM，不重新渲染 */
	function authSetMode(scope, mode) {
		var card = null;
		if (scope) {
			card = scope.matches && scope.matches('[data-pk-auth-card]') ? scope : scope.querySelector('[data-pk-auth-card]');
		}
		if (!card) { return; }
		var reg = mode === 'register';
		[].forEach.call(card.querySelectorAll('[data-pk-auth-panel]'), function (p) {
			p.hidden = p.getAttribute('data-pk-auth-panel') !== (reg ? 'register' : 'login');
		});
		[].forEach.call(card.querySelectorAll('[data-pk-auth-tab]'), function (a) {
			var on = a.getAttribute('data-pk-auth-tab') === (reg ? 'register' : 'login');
			a.classList.toggle('is-active', on);
			a.setAttribute('aria-selected', on ? 'true' : 'false');
		});
		var tabs = card.querySelector('.auth-tabs');
		if (tabs) { tabs.classList.toggle('is-register', reg); }
		var t = card.querySelector('[data-pk-auth-title]');
		var s = card.querySelector('[data-pk-auth-sub]');
		if (t) { t.textContent = card.getAttribute(reg ? 'data-t-register' : 'data-t-login') || ''; }
		if (s) { s.textContent = card.getAttribute(reg ? 'data-s-register' : 'data-s-login') || ''; }
	}
	function authError(card, text) {
		if (!card) { return; }
		var el = card.querySelector('[data-pk-auth-err]');
		if (!el) { return; }
		el.textContent = text || '';
		el.hidden = !text;
		card.classList.remove('is-shake');
		if (text) { void card.offsetWidth; card.classList.add('is-shake'); }
	}
	function authOpen(mode) {
		if (!authDialog) { location.assign(authUrlFor(mode)); return; }
		authOrigin = location.pathname + location.search + location.hash;
		authClosing = false;
		if (!authDialog.open) {
			if (typeof authDialog.showModal === 'function') { authDialog.showModal(); }
			else { authDialog.setAttribute('open', ''); }
			root.classList.add('pk-auth-open');
		}
		authSetMode(authDialog, mode);
		authWriteUrl(authUrlFor(mode));
		/* 成功后回打开弹窗前那一页；失败时也回同一处显示错误 */
		[].forEach.call(authDialog.querySelectorAll('[data-pk-auth-redirect]'), function (el) {
			el.value = authOrigin;
		});
		[].forEach.call(authDialog.querySelectorAll('[data-pk-auth-from]'), function (el) {
			el.value = authUrlFor('login');
		});
		authDialog.classList.remove('is-closing');
		authError(authDialog, '');
		pkCaptchaScan(authDialog);
		var focus = authDialog.querySelector('.auth-panel:not([hidden]) input');
		if (focus) { setTimeout(function () { try { focus.focus({ preventScroll: true }); } catch (e) { /* ignore */ } }, 80); }
	}
	function authClose(fromHistory) {
		if (!authDialog || !authDialog.open || authClosing) { return; }
		var dlg = authDialog;
		authClosing = true;
		root.classList.remove('pk-auth-open');
		/* 关掉就把地址写回来源页；不开新历史，后退键不会多出一条空记录 */
		if (!fromHistory) { authRestoreUrl(); }
		var done = function () {
			dlg.classList.remove('is-closing');
			if (dlg.open) { dlg.close(); }
			authClosing = false;
		};
		if (reduceMotion) { done(); return; }
		dlg.classList.add('is-closing');
		setTimeout(done, 200);
	}
	/* 弹窗里登录成功：先把地址还原成来源页，再整页刷新。
	   点赞态 / 侧栏文案 / 评论身份全都按新身份重来，局部换容器容易漏。 */
	function authSubmitDone() {
		authClosing = true;
		authRestoreUrl();
		location.reload();
	}
	/* 换页落到 /login/ 或 /user/ 时，页面上已经有同一张卡片了：
	   弹窗留着会重复一份表单（tab / 输入框各两份），把节点摘下来寄存；
	   换回普通页面再挂回去，登录入口依旧是弹窗，不用整页跳。 */
	function syncAuthDialog(url) {
		var target = url || location.href;
		var path = '';
		try { path = new URL(target, location.href).pathname.replace(/\/+$/, ''); } catch (e) { path = ''; }
		var onAuth = authIsUrl(target) || /\/user$/.test(path);
		if (onAuth) {
			if (!authDialog) { return; }
			if (authDialog.open) { authClose(true); }
			authDialog.remove();
			authDialogStash = authDialog;
			authDialog = null;
			return;
		}
		if (authDialog || !authDialogStash) { return; }
		document.body.appendChild(authDialogStash);
		authDialog = authDialogStash;
		authDialogStash = null;
	}

	window.addEventListener('popstate', function () {
		if (!authDialog || !authDialog.open || authClosing) { return; }
		if (authIsUrl(location.href)) {
			authSetMode(authDialog, /mode=register/.test(location.search) ? 'register' : 'login');
			return;
		}
		authClose(true);
	});
	if (authDialog) {
		authDialog.addEventListener('click', function (e) {
			var close = e.target.closest ? e.target.closest('[data-pk-auth-close]') : null;
			if (close) { e.preventDefault(); authClose(); return; }
			/* 点遮罩（dialog 自身）关闭，卡片内部的点击不处理 */
			if (e.target === authDialog) { authClose(); return; }
			var tab = e.target.closest ? e.target.closest('[data-pk-auth-tab]') : null;
			if (!tab) { return; }
			e.preventDefault();
			var mode = tab.getAttribute('data-pk-auth-tab');
			authSetMode(authDialog, mode);
			authWriteUrl(authUrlFor(mode));
			pkCaptchaScan(authDialog);
		});
		/* ESC：拦下原生 close，走同一套收起动画 */
		authDialog.addEventListener('cancel', function (e) {
			e.preventDefault();
			authClose();
		});
	}
	/* 全站登录入口：带 data-pk-auth-open 的链接就地弹窗，不跳页。
	   capture 阶段拦下，避免 Swup 先把它当成一次页面跳转。 */
	document.addEventListener('click', function (e) {
		var link = e.target && e.target.closest ? e.target.closest('[data-pk-auth-open]') : null;
		if (!link) { return; }
		e.preventDefault();
		e.stopPropagation();
		authOpen(/mode=register/.test(link.getAttribute('href') || '') ? 'register' : 'login');
	}, true);

	/* 后端带 pk_ajax=1 时返回 JSON，成功 / 失败都给出目标页地址；
	   前端主动取回目标页 HTML 再换容器，不依赖浏览器跟随 302 */
	function pkSubmitAuth(form) {
		var btn = form.querySelector('.user-submit');
		var card = form.closest ? form.closest('[data-pk-auth-card]') : null;
		var inDialog = !!(authDialog && authDialog.contains(form));
		if (btn) { btn.disabled = true; }
		var data = new FormData(form);
		data.append('pk_ajax', '1');
		fetch(form.getAttribute('action'), { method: 'POST', body: data, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				var d = (res && res.data) || {};
				if (!res || !res.success) {
					if (btn) { btn.disabled = false; }
					var text = d.message || '提交失败，稍后再试';
					if (card) { authError(card, text); } else { showToast(text); }
					/* 票可能已过期 / 用满：滑块复位，让用户能重新验证 */
					var capBox = form.querySelector('[data-pk-cap]');
					if (capBox && capBox.pkCaptchaReset) { capBox.pkCaptchaReset(); }
					return;
				}
				if (d.user) { pkSetUser(d.user); }
				if (inDialog) { authSubmitDone(); return; }
				var target = d.redirect;
				if (!target) { throw new Error('bad auth response'); }
				return fetch(target, { credentials: 'same-origin' }).then(function (r2) {
					return r2.text().then(function (html) { pkSwapDocument(html, r2.url || target); });
				});
			})
			.catch(function () {
				if (btn) { btn.disabled = false; }
				if (card) { authError(card, '网络异常，请重试'); } else { showToast('网络异常，请重试'); }
			});
	}
	function pkLogout(link) {
		var href = link.getAttribute('href');
		var home = pkUser.userUrl || homeUrl;
		/* 退出只关心 Cookie 被清掉，响应体不用管；再取一份用户中心页面换容器 */
		fetch(href, { credentials: 'same-origin' })
			.then(function () { return fetch(home, { credentials: 'same-origin' }); })
			.then(function (r) {
				return r.text().then(function (html) { pkSwapDocument(html, r.url || home); });
			})
			.catch(function () { window.location.assign(href); });
	}

	/* 事件委托：Swup 交换 #swup-wrapper 后表单 / 链接都是新节点 */
	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form || !form.classList) { return; }
		if (form.classList.contains('user-form')) {
			e.preventDefault();
			pkSubmitAuth(form);
			return;
		}
		/* 搜索页的 GET 表单：改成 pjax 导航，避免回车整页刷新 */
		if (form.classList.contains('search-page-form')) {
			e.preventDefault();
			var params = new URLSearchParams(new FormData(form)).toString();
			var action = form.getAttribute('action') || homeUrl;
			var url = params ? action + (action.indexOf('?') === -1 ? '?' : '&') + params : action;
			if (swup) { swup.navigate(url); }
			else { window.location.assign(url); }
		}
	});
	document.addEventListener('click', function (e) {
		var t = e.target;
		var link = t && t.closest ? t.closest('[data-pk-logout]') : null;
		if (!link) { return; }
		e.preventDefault();
		pkLogout(link);
	});

	/* ---------- 说说卡片点赞：按钮常驻卡片底部，走 pianke/v1/social ----------
	   评论功能已整体移除，卡片没有展开面板；点赞按钮 [data-pk-say-like] 直接可点。
	   document 级委托，换页后无需重绑。 */
	document.addEventListener('click', function (e) {
		var btn = e.target && e.target.closest ? e.target.closest('[data-pk-say-like]') : null;
		if (!btn || btn.disabled) { return; }
		e.preventDefault();
		if (!pkUser.loggedIn) { authOpen('login'); return; }
		var id = Number(btn.getAttribute('data-pk-say-like'));
		if (!id || !pkUser.restUrl) { return; }
		btn.disabled = true;
		fetch(pkUser.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': pkUser.restNonce },
			body: JSON.stringify({ post_id: id })
		}).then(function (r) { return r.json(); }).then(function (res) {
			btn.disabled = false;
			if (!res || typeof res.on === 'undefined') {
				showToast((res && res.message) || '操作失败，稍后再试');
				return;
			}
			var on = !!res.on;
			btn.classList.toggle('is-on', on);
			btn.setAttribute('aria-pressed', on ? 'true' : 'false');
			var card = btn.closest('.say-card') || document;
			var cnt = card.querySelector('[data-pk-say-like-count]');
			if (cnt) { cnt.textContent = String(res.count); }
			var likers = card.querySelector('[data-pk-say-likers]');
			if (likers && typeof res.likersHtml === 'string') { likers.innerHTML = res.likersHtml; }
			var list = pkUser.likes || (pkUser.likes = []);
			var at = list.indexOf(id);
			if (on && at === -1) { list.push(id); }
			if (!on && at !== -1) { list.splice(at, 1); }
			if (navigator.vibrate) { try { navigator.vibrate(8); } catch (err) { /* 忽略 */ } }
		}, function () {
			btn.disabled = false;
			showToast('网络异常，稍后再试');
		});
	});

	/* ---------- 0.13.27 友情链接：点卡片弹窗看详情，再点一次直接访问 ----------
	   卡片是 <article data-pk-link-*>（不是链接），第一次点击只弹窗；
	   弹窗里的卡片和「访问站点」都是真实 <a target="_blank">，第二次点击直接跳转，
	   且被 Swup 的 linkSelector 排除，不会触发站内换页。
	   dialog 常驻 footer（#swup-wrapper 外），这里全部用 document 级委托，换页后无需重绑。 */
	var pkLinkTrigger = null;
	function pkLinkDialog() { return document.getElementById('pk-link-dialog'); }
	function pkLinkSet(sel, value) {
		var dlg = pkLinkDialog();
		var el = dlg ? dlg.querySelector(sel) : null;
		if (el) { el.textContent = value; }
	}
	function pkLinkClose() {
		var dlg = pkLinkDialog();
		if (!dlg) { return; }
		try {
			if (dlg.open && typeof dlg.close === 'function') { dlg.close(); }
			else { dlg.removeAttribute('open'); }
		} catch (eClose) { dlg.removeAttribute('open'); }
		if (pkLinkTrigger && document.contains(pkLinkTrigger)) {
			try { pkLinkTrigger.focus(); } catch (eFocus) { /* 旧卡片已随换页移除 */ }
		}
	}
	/* 关掉「友情链接二次确认」时，卡片不是 <a>，委托里必须自己打开外链，
	   否则整张卡会变成点不动。用临时 <a> 保持 target=_blank / noopener 的语义。 */
	function pkLinkDirect(card) {
		var url = card.getAttribute('data-pk-link-url') || '';
		if (!url) { return; }
		var a = document.createElement('a');
		a.href = url;
		a.target = '_blank';
		a.rel = 'noopener noreferrer';
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
	}
	function pkLinkOpen(card) {
		var dlg = pkLinkDialog();
		if (!dlg || !card || card.classList.contains('is-offline')) { return; }
		var url   = card.getAttribute('data-pk-link-url') || '';
		var name  = card.getAttribute('data-pk-link-name') || '';
		var desc  = card.getAttribute('data-pk-link-desc') || '';
		var group = card.getAttribute('data-pk-link-group') || '';
		var host  = card.getAttribute('data-pk-link-host') || '';
		if (!url) { return; }
		pkLinkSet('[data-pk-link-d-name]', name);
		pkLinkSet('[data-pk-link-d-host]', host);
		pkLinkSet('[data-pk-link-d-desc]', desc);
		pkLinkSet('[data-pk-link-d-group]', group);
		pkLinkSet('[data-pk-link-d-initial]', (name || '?').trim().charAt(0).toUpperCase());
		var descRow = dlg.querySelector('[data-pk-link-d-desc]');
		if (descRow) { descRow.hidden = !desc; }
		var groupRow = dlg.querySelector('[data-pk-link-d-group-row]');
		if (groupRow) { groupRow.hidden = !group; }
		var dialogCard = dlg.querySelector('[data-pk-link-dialog-card]');
		var visit = dlg.querySelector('[data-pk-link-visit]');
		if (dialogCard) {
			dialogCard.setAttribute('href', url);
			dialogCard.setAttribute('aria-label', '访问 ' + name);
		}
		if (visit) { visit.setAttribute('href', url); }
		var vLabel = dlg.querySelector('[data-pk-link-visit]');
		if (vLabel) {
			/* 「访问站点」是 <a> 里的文字节点：英文界面下换文案，图标保留在后面 */
			var visitText = '';
			for (var vn = 0; vn < vLabel.childNodes.length; vn++) {
				if (vLabel.childNodes[vn].nodeType === 3) { visitText += vLabel.childNodes[vn].nodeValue; }
			}
			if (visitText) {
				var vIco = vLabel.querySelector('svg');
				vLabel.textContent = visitText.trim() + ' ';
				if (vIco) { vLabel.appendChild(vIco); }
			}
		}
		var cancelBtn = dlg.querySelector('.pk-link-cancel');
		if (cancelBtn && cancelBtn.textContent) { cancelBtn.textContent = cancelBtn.textContent.trim(); }
		pkLinkTrigger = card;
		try {
			if (typeof dlg.showModal === 'function') { dlg.showModal(); }
			else { dlg.setAttribute('open', ''); }
		} catch (eOpen) { dlg.setAttribute('open', ''); }
	}
	document.addEventListener('click', function (e) {
		var t = e.target;
		if (!t || !t.closest) { return; }
		/* 关闭按钮 */
		if (t.closest('[data-pk-link-close]')) { e.preventDefault(); pkLinkClose(); return; }
		var dlg = pkLinkDialog();
		/* 点遮罩空白处：dialog 本身是事件 target */
		if (dlg && dlg.open && t === dlg) { pkLinkClose(); return; }
		/* 弹窗内的卡片 / 按钮是第二次点击：不拦截，直接让浏览器打开外链 */
		if (dlg && dlg.open && dlg.contains(t)) { return; }
		var card = t.closest('.pk-links .pk-link-card[data-pk-link-url]');
		if (card && !card.classList.contains('is-offline')) {
			if (!uiOn('linkDialogOn')) { pkLinkDirect(card); return; }
			e.preventDefault();
			pkLinkOpen(card);
		}
	});
	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Enter' && e.key !== ' ') { return; }
		var t = e.target;
		if (!t || !t.classList || !t.classList.contains('pk-link-card')) { return; }
		if (t.classList.contains('is-offline') || !t.getAttribute('data-pk-link-url')) { return; }
		e.preventDefault();
		if (!uiOn('linkDialogOn')) { pkLinkDirect(t); return; }
		pkLinkOpen(t);
	});
	var pkLinkDlgInit = pkLinkDialog();
	if (pkLinkDlgInit) {
		pkLinkDlgInit.addEventListener('close', function () {
			if (pkLinkTrigger && document.contains(pkLinkTrigger)) {
				try { pkLinkTrigger.focus(); } catch (eF) { /* ignore */ }
			}
		});
	}

	/* ---------- 友链分类筛选 ----------
	   chips 与卡片都在 /links/ 页内，用 document 级委托，Swup 换页后无需重绑。
	   分组标题行与卡片 li 都带 data-pk-group（空组为 ''），"全部" 用 'all'。 */
	document.addEventListener('click', function (e) {
		var btn = e.target && e.target.closest ? e.target.closest('[data-pk-link-filter]') : null;
		if (!btn) { return; }
		var list = document.querySelector('.pk-links-page .pk-links');
		if (!list) { return; }
		e.preventDefault();
		var key = btn.getAttribute('data-pk-link-filter');
		var lis = list.children;
		for (var i = 0; i < lis.length; i++) {
			var li = lis[i];
			var g = li.getAttribute('data-pk-group');
			li.hidden = !(key === 'all' || (g !== null && g === key));
		}
		var chips = document.querySelectorAll('[data-pk-link-filter]');
		for (var j = 0; j < chips.length; j++) {
			var on = chips[j] === btn;
			chips[j].classList.toggle('is-active', on);
			chips[j].setAttribute('aria-pressed', on ? 'true' : 'false');
		}
	});

	/* ---------- 友链申请表单 ----------
	   action / nonce 都是表单里的 hidden（wp_nonce_field('pk_ajax','nonce')），
	   直接 FormData 提交到 pkFront.ajaxUrl；无 JS 时原生 POST 到 admin-ajax.php 也能入库。 */
	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form || form.id !== 'pk-link-apply-form') { return; }
		e.preventDefault();
		var kpkName = form.querySelector('[name="link_name"]');
		var kpkDesc = form.querySelector('[name="link_desc"]');
		var kpkHit = kpkScan(kpkName ? kpkName.value : '') || kpkScan(kpkDesc ? kpkDesc.value : '');
		if (kpkHit) { showToast('内容包含违禁词「' + kpkHit + '」，可能无法通过审核'); }
		var msg = document.getElementById('pk-link-apply-msg');
		var btn = form.querySelector('.pk-apply-submit');
		if (btn && btn.disabled) { return; }
		if (btn) { btn.disabled = true; }
		if (msg) { msg.textContent = '正在提交…'; msg.className = 'pk-apply-msg'; }
		fetch(pkFront.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: new FormData(form) })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (btn) { btn.disabled = false; }
				if (!msg) { return; }
				if (res && res.success) {
					msg.textContent = (res.data && res.data.message) || '申请已提交，等站长审核。';
					msg.className = 'pk-apply-msg is-ok';
					form.reset();
				} else {
					var d = res && res.data;
					msg.textContent = (typeof d === 'string' ? d : (d && d.message)) || '提交失败，请稍后再试。';
					msg.className = 'pk-apply-msg is-err';
				}
			})
			.catch(function () {
				if (btn) { btn.disabled = false; }
				if (msg) { msg.textContent = '网络异常，请稍后再试。'; msg.className = 'pk-apply-msg is-err'; }
			});
	});

	/* ---------- Swup 真实跳转 + 共享元素 morph ----------
	   卡片是真实 <a href="/N/">，由 Swup 拦截做 AJAX 跳转并交换 #swup-wrapper；
	   服务端 single.php 已渲染完整阅读层（含评论 / 悬浮条），无需内联 payload。
	   morph 用原生 View Transitions：旧快照命名卡片、新快照命名 #detail，二者共享 'morph' 形变。 */

	function isSingleUrl(url) {
		if (!url) { return false; }
		try {
			var u = new URL(url, location.href);
			/* /N/ 是文章，/N/talk 是它的评论区，两者都算阅读态 */
			if (/\/(\d+)(\/talk)?\/?$/.test(u.pathname)) { return true; }
			return /[?&]p=(\d+)/.test(u.search);
		} catch (e) { return false; }
	}
	function postIdFromUrl(url) {
		if (!url) { return null; }
		try {
			var u = new URL(url, location.href);
			var m = u.pathname.match(/\/(\d+)\/?/);
			if (m) { return m[1]; }
			var q = u.search.match(/[?&]p=(\d+)/);
			return q ? q[1] : null;
		} catch (e) { return null; }
	}
	function setHeadState(isSingle, doc, showBack) {
		root.classList.toggle('reading', !!showBack);
		if (siteHead) { siteHead.classList.toggle('is-single', isSingle); }
		var ht = $('head-title-text');
		var t = '';
		if (isSingle && doc) {
			var dt = doc.querySelector('#d-title');
			t = dt ? dt.textContent : '';
			/* 0.13.27：/say/N/ 会被 isSingleUrl() 判为阅读态，但说说单页没有 #d-title，
			   别把顶栏标题清空，回退到页面上的「说说」标题。 */
			if (!t && doc.querySelector('.say-single')) {
				var st = doc.querySelector('.say-title');
				t = st ? st.textContent : '说说';
			}
		}
		if (ht) { ht.textContent = t; }
	}
	function clearMorphNames() {
		var els = document.querySelectorAll('[style*="view-transition-name"]');
		Array.prototype.forEach.call(els, function (el) { el.style.viewTransitionName = ''; });
		root.classList.remove('pk-morphing', 'pk-morph-open', 'pk-morph-close');
	}

	/* ---------- 页面切换扫光（非文章） ----------
	   一层毛玻璃斜带在点击换页瞬间从左上角扫到右下角，0.5s 完成；文章（/N/、/N/talk、?p=N）的
	   打开 / 关闭 / 翻篇不触发。层由 JS 建在 body 上，不在 #swup-wrapper 里，
	   Swup 换页不会销毁它。 */
	var pkSweepEl = null;
	var pkSweepTimer = null;
	function pkSweepAllowed(url) {
		if (!url) { return true; }
		try {
			var u = new URL(url, location.href);
			/* 说说单页是普通页面切换，放行 */
			if (/^\/say\/\d+\/?$/.test(u.pathname)) { return true; }
			/* 文章阅读态：/N/、/N/talk、?p=N */
			if (/\/(\d+)(\/talk)?\/?$/.test(u.pathname)) { return false; }
			return !/[?&]p=(\d+)/.test(u.search);
		} catch (e) { return true; }
	}
	function pkSweepRun() {
		if (reduceMotion || !uiOn('sweepOn')) { return; }
		if (!pkSweepEl) {
			pkSweepEl = document.createElement('div');
			pkSweepEl.className = 'pk-sweep';
			pkSweepEl.setAttribute('aria-hidden', 'true');
			pkSweepEl.innerHTML = '<i class="pk-sweep-lens"></i>';
			document.body.appendChild(pkSweepEl);
		}
		pkSweepEl.classList.remove('is-on');
		/* 强制重排：连续两次换页也要能重新播放动画 */
		void pkSweepEl.offsetWidth;
		/* 时长由后台「界面与交互 → 扫光时长」注入，CSS 动画读同一个变量 */
		pkSweepEl.style.setProperty('--pk-sweep-dur', uiSweepMs + 'ms');
		pkSweepEl.classList.add('is-on');
		if (pkSweepTimer) { clearTimeout(pkSweepTimer); }
		/* 斜带扫完立刻收起，结束时带已在屏幕外，恢复清晰无跳变 */
		pkSweepTimer = setTimeout(function () {
			if (pkSweepEl) { pkSweepEl.classList.remove('is-on'); }
		}, uiSweepMs + 20);
	}

	var swup = null;
	var SwupCtor = window.Swup;
	if (typeof SwupCtor === 'function') {
		swup = new SwupCtor({
			containers: ['#swup-wrapper'],
			/* fetch 超时（ms）：回源卡住时触发 visit:fail 走整页加载兜底，
			   绝不停在「URL 已变、内容没换」的半吊子状态 */
			timeout: 10000,
			/* 动画由我们自己的 content:replace 钩子驱动，不需要 Swup 的 CSS 动画等待 */
			animationSelector: '.pk-swup-no-anim',
			/* 排除灯箱 / 外链中转 / 新窗口 / 下载 / 锚点等不应被 Swup 接管的链接。
			   登录卡片的登录/注册 tab 是 <a href="?mode=..."> 但只切 DOM：
			   Swup 在 document capture 阶段会抢先接管，弹窗的 preventDefault 拦不住，
			   结果发起一次真实访问、几秒后把 URL 又写回 /login/（弹窗已关）。
			   这里从选择器层排除，比事后校正 URL 干净。 */
			linkSelector: 'a[href]:not([data-no-swup]):not([data-pk-auth-tab]):not([data-pk-auth-close]):not([target="_blank"]):not([data-pk-lightbox]):not([data-pk-go]):not([download]):not([href^="#"]):not([href^="mailto:"]):not([href^="tel:"])'
		});
		/* 只有真的建出实例才算「pjax 可用」：footer 内联哨兵据此提示，
		   vendor 脚本挂掉时不再静默退化成整页跳转 */
		root.setAttribute('data-pk-swup', '1');

		var homeScroll = 0;
		var restoreScroll = false;
		/* 仅当发生过 Swup 真实跳转时才置真：直接访问文章页不应显示「返回列表」 */
		var pkSwupActive = false;
		/* DOM 交换发生在 VT 回调里（异步），而 Swup 的 page:view 会先于真正交换触发：
		   先挂起 visit，等交换真正执行后再刷新引用 / 应用分类 / 恢复滚动 */
		var swapPending = false;
		var pendingView = null;
		var vtSeq = 0;
		/* 换页加载反馈：Swup 是「先 pushState 再等 HTML」，Cloudflare 回源慢时
		   中间有 1~4 秒地址已变、页面却一动不动。给一条顶部进度条即时反馈。 */
		var navTimer = null;
		var navSeq = 0;
		function endNav() {
			root.classList.remove('pk-nav-loading');
			if (navTimer) { clearTimeout(navTimer); navTimer = null; }
		}

		/* 滚动由 homeScroll 逻辑接管（打开文章保持、返回时复原），禁掉 Swup 的自动跳顶 */
		swup.hooks.on('visit:start', function (visit) {
			if (visit.scroll) { visit.scroll.reset = false; }
			/* 点击 / 导航的瞬间就起扫光：不等 HTML 回源、不等 VT 结束。
			   from 用当前地址更稳（visit.from 此时可能尚未填充）；
			   文章阅读层（打开 / 关闭 / 翻篇）依旧排除。 */
			if (pkSweepAllowed(visit.to && visit.to.url) && pkSweepAllowed(location.href)) {
				pkSweepRun();
			}
			var myNav = ++navSeq;
			root.classList.add('pk-nav-loading');
			if (navTimer) { clearTimeout(navTimer); }
			/* 双保险：Swup 的 fetch timeout 之外，12s 仍没换好 DOM 就整页加载 */
			navTimer = setTimeout(function () {
				if (myNav !== navSeq || !root.classList.contains('pk-nav-loading')) { return; }
				endNav();
				if (visit.to && visit.to.url) { window.location.assign(visit.to.url); }
			}, 12000);
		});
		swup.hooks.on('visit:fail', function (visit) {
			endNav();
			if (visit && visit.to && visit.to.url) { window.location.assign(visit.to.url); }
		});
		swup.hooks.on('visit:end', function () { if (!swapPending) { endNav(); } });

		swup.hooks.replace('content:replace', function (visit, args) {
			var outEl = document.querySelector('#swup-wrapper');
			var inEl = visit.to.document ? visit.to.document.querySelector('#swup-wrapper') : null;
			if (!outEl || !inEl) {
				/* 目标页不是主题模板（后台 / 附件 / 登录页等）：退回整页导航，
				   绝不留下「URL 变了、内容没换」的半吊子状态 */
				endNav();
				if (visit.to && visit.to.url) { window.location.assign(visit.to.url); }
				return;
			}
			pkSwupActive = true;

		var toSingle = isSingleUrl(visit.to.url);
		var fromSingle = isSingleUrl(visit.from.url);
			var trigger = visit.trigger && visit.trigger.el ? visit.trigger.el : null;
			/* 说说归档卡片也参与共享元素 morph */
			var card = trigger && trigger.closest ? trigger.closest('.card, .pk-slide, .say-card') : null;
			var dir = null, oldEl = null, restoreId = null;

			if (toSingle) {
				if (card) { dir = 'open'; oldEl = card; }
			} else if (fromSingle) {
				dir = 'close';
				/* 说说单页没有 #detail：旧快照改用那张放大的卡片，否则 close 转场会断 */
			oldEl = document.getElementById('detail')
				|| document.querySelector('.say-single .say-card');
			restoreId = postIdFromUrl(visit.from.url);
			}

			if (toSingle && !fromSingle) {
				homeScroll = window.scrollY || document.documentElement.scrollTop || 0;
			}
			restoreScroll = fromSingle;

		function finishSwap() {
			swapPending = false;
			endNav();
			if (pendingView) {
				var v = pendingView;
				pendingView = null;
				/* 后处理挂了不能反过来把已经换好的 DOM 判成失败，
				   更不能让 swapPending 永久挂起堵死后续所有 page:view */
				try { doPageView(v); } catch (eView) { console.warn('[pk] page:view failed', eView); }
			}
		}
		function bail(url) {
			swapPending = false;
			pendingView = null;
			endNav();
			/* Swup 此时已经 pushState 过了，必须整页加载一次目标地址，
			   绝不留「路径变了、页面没变」的半吊子状态 */
			window.location.assign(url);
		}
		var swapped = false;
		function swap() {
			if (swapped) { return; }
			swapped = true;
			/* 每次都重新查容器：VT 回调在下一帧才跑，闭包里捕获的 outEl
			   可能已被上一次交换换掉，而脱离文档的节点 replaceWith 会静默失败 */
			var cur = document.querySelector('#swup-wrapper');
			if (!cur || !cur.parentNode) { bail(visit.to.url); return; }
			var node = inEl.cloneNode(true);
			try { cur.replaceWith(node); } catch (eSwap) { bail(visit.to.url); return; }
			/* 交换后校验：容器必须就是刚插进去的那个节点 */
			if (document.getElementById('swup-wrapper') !== node) { bail(visit.to.url); return; }
			if (oldEl) { try { oldEl.style.viewTransitionName = ''; } catch (eOld) { /* 旧节点可能已移除 */ } }
			if (dir === 'open') {
				/* 文章页命名 #detail；说说单页没有阅读层，命名那张放大的卡片 */
				var nd = document.querySelector('#detail')
					|| document.querySelector('.say-single-inner .say-card');
				if (nd) { nd.style.viewTransitionName = 'morph'; }
			} else if (restoreId) {
				/* 回首页找 .card[data-id]；回 /say/ 找 .say-card[data-say-id] */
				var nc = document.querySelector('.card[data-id="' + restoreId + '"]')
					|| document.querySelector('.say-card[data-say-id="' + restoreId + '"]');
				if (nc) { nc.style.viewTransitionName = 'morph'; }
			}
			finishSwap();
		}

		if (supportsVT && !reduceMotion && uiOn('morphOn')) {
			/* 所有站内切页都走原生转场：首页↔文章用共享元素 morph，
			   分类 / 归档 / 分页 / 信息页等走 root 的 300ms 交叉淡化（CSS 已定义）。
			   以前要求 dir 存在才转场，导致这些切页「路径变了却毫无反馈」。 */
			root.classList.remove('pk-morph-open', 'pk-morph-close');
			if (dir) {
				root.classList.add('pk-morphing', dir === 'open' ? 'pk-morph-open' : 'pk-morph-close');
				if (dir === 'open') { setHeadState(true, visit.to.document, true); }
				else { setHeadState(false, null, false); }
				if (oldEl) { oldEl.style.viewTransitionName = 'morph'; }
			} else {
				/* 上一次卡片转场的方向类若还没清，会禁掉 root 交叉淡化 */
				root.classList.remove('pk-morphing');
			}
			swapPending = true;
			var mySeq = ++vtSeq;
			try {
				var vt = document.startViewTransition(function () { swap(); });
				vt.finished.then(function () {}, function () {}).then(function () {
					/* 只有最新一次转场才能清方向标记，避免 open 未结束时 close 的类被误清 */
					if (mySeq === vtSeq) { clearMorphNames(); }
				});
			} catch (eVT) {
				/* 浏览器拒绝建立转场（后台标签 / 上一次未结束等）：同步兜底换 DOM */
				swap();
				if (mySeq === vtSeq) { clearMorphNames(); }
			}
		} else {
			swap();
			/* 不走 VT 时 swap() 里写的 viewTransitionName 没人清，这里补一次 */
			clearMorphNames();
		}
		}, { priority: 1 });

		function doPageView(visit) {
			/* Swup 初始化时也会派发一次 page:view，预渲染页面里它同样会拉评论 / 登录态。
			   整段后处理等真正可见再执行（视觉初始化在预渲染期间不可见，延迟无感）。 */
			if (document.prerendering) {
				pkWhenActivated(function () { doPageView(visit); });
				return;
			}
			var doc = visit.to.document;
			var toSingle = isSingleUrl(visit.to.url);
			detailEl = document.getElementById('detail');
			refreshRefs();
		pkSlidesInit();
		pkTickerInit();
		pkCaptchaScan();
		pkCodeInit();
		pkHelpInit();
		pkAccountInit();
		syncAuthDialog(visit.to.url);
			closeAllAsidePops();
			closeComments(true);
			/* 换页时收起仍开着的友链弹窗；说说已改为卡片内联，随文档一起换掉 */
			pkLinkClose();
			/* 地址栏手输 / 外链落到 /N/talk：换页后自动展开评论区 */
			if (isTalkUrl(visit.to.url)) {
				setTimeout(function () { openComments(true); }, 40);
			}
			parallaxReset(); /* 换页即清视差状态，新头图等下一条陀螺仪读数再接 */
			setHeadState(toSingle, doc, pkSwupActive && toSingle);
			/* 容器外的公共区（导航高亮 / body class / 头部态 / 页脚 / 标题）按新文档同步 */
			syncChrome(doc, visit.to.url, toSingle);
			/* 换页后滚动容器可能从 #detail 变回 window（或反之），重新判一次显隐 */
			pkToTopSync();
			/* 分享按钮已在阅读层内（#swup-wrapper 里），随交换一起更新，无需同步 data-url */
			if (doc && doc.title) { document.title = doc.title; }
			var c = document.getElementById('pk-comments');
			if (c) { loadComments(c.getAttribute('data-pk-post-id'), c); }
			/* 列表页（首页 / 分类归档）换页回来：引用已被换新，重跑筛选建立分页态 */
			if (!toSingle) { applyFilter(); }
			if (!toSingle && restoreScroll) {
				window.scrollTo(0, homeScroll);
			}
			/* 文章页点搜索跳回首页（/#search）：交换后自动展开搜索弹窗并清掉临时 hash */
			if (location.hash === '#search') {
				try { history.replaceState(null, '', location.pathname + location.search); } catch (e) { /* ignore */ }
				setTimeout(function () { openSearch(); }, 60);
			}
			measureHead();
			pkMeasureImmersive();
			pkObserveInner();
			syncImmersive();
			/* Swup 交换后悬浮条是新节点，点赞/收藏态要按当前用户重建一次 */
			syncSocial();
			syncDockSide();
			/* 换页即停朗读 / 退专注，并重新收集预渲染候选与相邻篇缓存 */
			pkStopSpeech();
			root.classList.remove('pk-focus');
			if (island) { island.classList.remove('is-focus'); }
			pkCollectSpec();
			pkPrefetchAdjacent();
			pkTocObserve();
			/* 暂时禁用 syncSegButtons(); */
			/* 登录 / 退出 / 信任设备自动登录后，点赞态与 REST nonce 都要跟上 */
			refreshUserState();
		}
		function pageView(visit) {
			if (swapPending) { pendingView = visit; return; }
			doPageView(visit);
		}
		swup.hooks.on('page:view', pageView);
		/* 登录 / 注册 / 退出是 fetch 拿到的整页 HTML，走同一套换页后处理 */
		window.pkPageView = function (url, doc) {
			doPageView({ to: { url: url, document: doc } });
		};
	}

	/* 进入文章页后 ESC 退回首页（与 Swup 真实跳转一致），弹窗打开时不拦截 */
	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape') { return; }
		if (pkLightboxActive()) { return; }
		if (noticeDialog && noticeDialog.open) { return; }
		if (searchDialog && searchDialog.open) { return; }
		if (cmdkEl && cmdkEl.open) { return; }
		if (shareDialog && shareDialog.open) { return; }
		if (commentsOpen()) { closeComments(); return; }
		if (swup && isSingleUrl(location.href)) {
			e.preventDefault();
			var backUrl = homeUrlAttr || homeUrl;
			try {
				if (window.history.length > 1) { history.back(); }
				else { swup.navigate(backUrl); }
			} catch (err) { swup.navigate(backUrl); }
		}
	});

	/* 首屏（非 Swup 访问）按当前文档初始化阅读态 / 分类 / 评论。
	   整段包在 pkWhenActivated 里：预渲染时不会提前发评论列表 / 登录态 / 预取请求，
	   也不会提前启动朗读或 IdleDetector，直到页面真正被用户看到。 */
	pkWhenActivated(function firstPaint() {
		var toSingle = isSingleUrl(location.href);
		detailEl = document.getElementById('detail');
		refreshRefs();
		pkSlidesInit();
		pkTickerInit();
		pkCaptchaScan();
		pkCodeInit();
		pkHelpInit();
		pkAccountInit();
		noticeInit();
		setHeadState(toSingle, document, false);
		if (toSingle) {
			var c = document.getElementById('pk-comments');
			if (c) { loadComments(c.getAttribute('data-pk-post-id'), c); }
			/* 直接访问 /{id}/talk：服务端注入 talkOpen，首屏就把评论抽屉拉开 */
			/* wp_localize_script 会把数字转成字符串，"0" 也是 truthy，必须显式比 1 */
			if (isTalkUrl(location.href) || (window.pkFront && String(pkFront.talkOpen) === '1')) {
				setTimeout(function () { openComments(true); }, 60);
			}
		}
		measureHead();
		pkMeasureImmersive();
		pkObserveInner();
		syncImmersive();
		syncSocial();
		syncDockSide();
		pkIslandVisible();
		pkCollectSpec();
		pkPrefetchAdjacent();
		pkTocObserve();
		pkUnlockInit();
		pkHitokotoScan();
		/* 暂时禁用 syncSegButtons(); */
	});
})();
