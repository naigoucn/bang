/**
 * Bang 阅读解锁 · 奶狗 WeBot 插件（插件系统 v4.0）
 *
 * 对应 WordPress 主题 Bang 的 inc/unlock.php。
 *
 * 流程：
 *   1. 用户在网站登录 → 锁定卡片显示 8 位绑定码；
 *   2. 用户在私聊或群里把绑定码发给机器人（也支持「绑定 AB12CD34」）；
 *   3. 插件取 msg.user_id 作为 bot_uid，HMAC 签名回调 /unlock/bind 完成绑定；
 *   4. 群里每收到该用户一条消息，回调 /unlock/heartbeat 续期（5 分钟节流）；
 *   5. 站内判定 = 已绑定 + 心跳在有效期内；退群后心跳停止，到期自动重新上锁（兜底）；
 *   6. 平台一旦开放退群能力，以下三种任选其一即可立即上锁，插件侧都已就绪：
 *      a) 退群事件钩子：hooks.on(leave_event)，payload 带 userId / groupId；
 *      b) 退群用户 ID 增量接口：member_check_mode=leaves，GET 返回最近退群 ID 列表；
 *      c) 全量群成员接口：member_check_mode=members，定时比对差集；
 *      也支持平台直接把退群事件 POST 到站点 /unlock/leave（见后台面板签名说明）。
 *   7. v1.2.0：绑定 / 心跳顺带回传群内昵称与进群时间（nickname / joined_at），
 *      站点「个人中心 → 群绑定」会展示这些资料；带资料的请求使用扩展 HMAC 签名，
 *      不带资料的旧请求仍按五段签名校验，向后兼容。
 *
 * 发现退群一律回调 /unlock/leave（幂等），并可选给该用户发一条「请重新进群」提示。
 * 插件不依赖任何第三方 npm 包（axios 由框架提供）。
 *
 * 安装：把本文件放到 plugins/bang-unlock/index.js（目录名必须与 meta.id 一致），
 * 或在插件市场直接上传这个 .js，然后在后台安装启用、填写设置项。
 */

const crypto = require('crypto');
const axios = require('axios');

const PLUGIN_ID = 'bang-unlock';
const DEFAULT_REST_BASE = '/wp-json/pianke/v1/unlock';
const DEFAULT_LEAVE_EVENTS = [
	'group_member_leave', 'member_leave', 'group_leave',
	'group_member_removed', 'quit_group', 'leave_group',
];
const MAP_KEY = 'bind_map_json';
const POLL_KEY = '__bangUnlockPollTimer';
const SEEN_KEY = '__bangUnlockSeen';
const KNOWN_KEY = '__bangUnlockKnown';
const LAST_KEY = '__bangUnlockLastPoll';
/* 0.13.24：首次入群事件里缓存昵称 / 进群时间，等用户发绑定码时一起回传 */
const FIRST_KEY = '__bangUnlockFirstSeen';

/* 兼容两种安装位置：plugins/<id>/index.js 与 plugins/market/mp-xxx/index.js */
function loadLib(name) {
	const candidates = ['../../lib/' + name, '../../../lib/' + name, '../../' + name];
	for (let i = 0; i < candidates.length; i++) {
		try { return require(candidates[i]); } catch (_) { /* next */ }
	}
	return null;
}

const db = loadLib('db');
const hooks = loadLib('hooks');
const plugins = loadLib('plugins');

if (!global[SEEN_KEY]) { global[SEEN_KEY] = new Map(); }
if (!global[KNOWN_KEY]) { global[KNOWN_KEY] = null; }
if (!global[LAST_KEY]) { global[LAST_KEY] = 0; }
if (!global[FIRST_KEY]) { global[FIRST_KEY] = new Map(); }

/* ---------------------------------------------------------------- 配置 */
async function getSetting(botId, key, fallback) {
	if (!db || !db.row) { return fallback; }
	try {
		const row = await db.row(
			'SELECT config_value FROM plugin_settings WHERE bot_id=? AND plugin_id=? AND config_key=?',
			[botId, PLUGIN_ID, key]
		);
		if (row && row.config_value != null && String(row.config_value).trim() !== '') {
			return String(row.config_value).trim();
		}
	} catch (e) { /* 表不存在等，静默回退 */ }
	return fallback;
}

async function loadCfg(botId) {
	/* 优先走框架的配置读取（含全局兜底），失败再逐个直读 */
	let cfg = {};
	if (plugins && typeof plugins.loadPluginConfig === 'function') {
		try { cfg = (await plugins.loadPluginConfig(botId, PLUGIN_ID)) || {}; } catch (_) { /* ignore */ }
	}
	const pick = async (key, fallback) => {
		const v = cfg[key];
		return (v != null && String(v).trim() !== '') ? String(v).trim() : getSetting(botId, key, fallback);
	};
	return {
		wp_base: await pick('wp_base', ''),
		wp_secret: await pick('wp_secret', ''),
		wp_rest_base: await pick('wp_rest_base', DEFAULT_REST_BASE),
		group_id: await pick('group_id', ''),
		heartbeat: await pick('heartbeat', 'on'),
		welcome: await pick('welcome', '欢迎进群！在网站锁定卡片上复制绑定码，发给我即可解锁全文。'),
		bind_ok: await pick('bind_ok', '绑定成功，回到网页刷新一下就能阅读全文啦。'),
		bind_fail: await pick('bind_fail', '绑定失败：'),
		member_check_api: await pick('member_check_api', ''),
		member_check_mode: await pick('member_check_mode', 'members'),
		member_check_token: await pick('member_check_token', ''),
		member_check_since: await pick('member_check_since', 'on'),
		member_check_interval: parseInt(await pick('member_check_interval', '5'), 10) || 5,
		heartbeat_interval: parseInt(await pick('heartbeat_interval', '5'), 10) || 5,
		/* 平台开放的退群事件名，逗号分隔；可按实际钩子名覆盖 */
		leave_event: await pick('leave_event', DEFAULT_LEAVE_EVENTS.join(',')),
		/* 检测到退群时，若平台还能私聊/@ 该用户，补一条「请重新进群」 */
		leave_notify: await pick('leave_notify', 'off'),
		leave_notify_text: await pick('leave_notify_text', '检测到你已退出交流群，网站内容已重新上锁，请重新进群后再阅读。'),
	};
}

/* ---------------------------------------------------------------- 签名与请求 */
function restUrl(cfg, action) {
	const base = String(cfg.wp_base || '').replace(/\/+$/, '');
	let prefix = String(cfg.wp_rest_base || DEFAULT_REST_BASE).trim();
	if (prefix.charAt(0) !== '/') { prefix = '/' + prefix; }
	/* 未开启固定链接的站点：/?rest_route=/pianke/v1/unlock/bind */
	if (prefix.indexOf('?') !== -1) { return base + prefix + '/' + action; }
	return base + prefix.replace(/\/+$/, '') + '/' + action;
}

function makeSign(cfg, action, code, botUid, groupId, ts, nickname, joinedAt) {
	/* 0.13.24：带群资料时末尾追加 nickname|joined_at，与主题 pk_unlock_sign() 完全一致；
	   不带资料时保持 0.13.20 的五段格式，旧站点 / 旧插件互不干扰。 */
	const parts = [action, code || '', botUid || '', groupId || '', ts];
	if (nickname || joinedAt) { parts.push(nickname || '', joinedAt || 0); }
	return crypto.createHmac('sha256', String(cfg.wp_secret || '')).update(parts.join('|')).digest('hex');
}

async function callWp(cfg, action, payload) {
	if (!cfg.wp_base) { throw new Error('未配置 wp_base'); }
	if (!cfg.wp_secret) { throw new Error('未配置 wp_secret'); }
	const ts = Math.floor(Date.now() / 1000);
	const code = String((payload && payload.code) || '').toUpperCase();
	const botUid = String((payload && payload.bot_uid) || '');
	const groupId = String((payload && payload.group_id) || '');
	const nickname = cleanNick(payload && payload.nickname);
	const joinedAt = normTs(payload && payload.joined_at);
	const body = {
		code: code,
		bot_uid: botUid,
		group_id: groupId,
		ts: ts,
		sign: makeSign(cfg, action, code, botUid, groupId, ts, nickname, joinedAt),
	};
	if (nickname) { body.nickname = nickname; }
	if (joinedAt) { body.joined_at = joinedAt; }
	const resp = await axios.post(restUrl(cfg, action), body, {
		timeout: 8000,
		headers: { 'Content-Type': 'application/json' },
	});
	return resp.data;
}

function errText(e) {
	if (e && e.response && e.response.data) {
		const d = e.response.data;
		if (d.message) { return d.message; }
		if (typeof d === 'string') { return d.slice(0, 120); }
	}
	return e && e.message ? e.message : '未知错误';
}

/* ---------------------------------------------------------------- 绑定码解析 */
function pickCode(text) {
	let s = String(text || '').trim();
	/* 兼容「绑定 AB12CD34」「绑定：AB12CD34」「/bind AB12CD34」「解锁 AB12CD34」 */
	s = s.replace(/^\s*(?:\/bind|\/unlock|绑定|解锁|bind|unlock|#)\s*[:：]?\s*/i, '');
	const m = s.match(/([A-Za-z0-9]{8})/);
	return m ? m[1].toUpperCase() : null;
}

/* ---------------------------------------------------------------- 本地 uid→code 记录（可选，便于排查） */
async function rememberBind(botId, botUid, code, groupId) {
	if (!db || !db.row || !db.exec || !botUid) { return; }
	try {
		const row = await db.row(
			'SELECT id, config_value FROM plugin_settings WHERE bot_id=? AND plugin_id=? AND config_key=?',
			[botId, PLUGIN_ID, MAP_KEY]
		);
		let map = {};
		if (row && row.config_value) {
			try { map = JSON.parse(row.config_value) || {}; } catch (_) { map = {}; }
		}
		map[botUid] = { code: code, group_id: groupId, at: Date.now() };
		/* 只留最近 2000 条，避免无限膨胀 */
		const keys = Object.keys(map);
		if (keys.length > 2000) {
			keys.sort((a, b) => (map[a].at || 0) - (map[b].at || 0));
			keys.slice(0, keys.length - 2000).forEach((k) => delete map[k]);
		}
		const json = JSON.stringify(map);
		if (row) {
			await db.exec('UPDATE plugin_settings SET config_value=? WHERE id=?', [json, row.id]);
		} else {
			await db.exec(
				'INSERT INTO plugin_settings (bot_id, plugin_id, config_key, config_value) VALUES (?,?,?,?)',
				[botId, PLUGIN_ID, MAP_KEY, json]
			);
		}
	} catch (e) {
		console.error('[' + PLUGIN_ID + '] 记录绑定关系失败：' + errText(e));
	}
}

/* ---------------------------------------------------------------- 平台字段兼容 */
function firstKey(obj, keys) {
	for (let i = 0; i < keys.length; i++) {
		const v = obj[keys[i]];
		if (v != null && String(v).trim() !== '') { return String(v).trim(); }
	}
	return '';
}

/* ---------------------------------------------------------------- 0.13.24 群资料 */
/** 与 PHP sanitize_text_field() 对齐：去标签、折叠空白、限量，保证两端签名原文一致 */
function cleanNick(v) {
	return String(v == null ? '' : v)
		.replace(/<[^>]*>/g, '')
		.replace(/\s+/g, ' ')
		.trim()
		.slice(0, 64);
}

/** 秒 / 毫秒 / 日期字符串统一成 Unix 秒；越界返回 0 */
function normTs(v) {
	if (v == null || v === '') { return 0; }
	let n;
	const s = String(v).trim();
	if (typeof v === 'number') { n = v; }
	else if (/^\d+$/.test(s)) { n = parseInt(s, 10); }
	else { n = Math.floor(Date.parse(s) / 1000); }
	if (!isFinite(n) || n <= 0) { return 0; }
	if (n > 2000000000) { n = Math.floor(n / 1000); }
	if (n < 946684800 || n > 4102444800) { return 0; }
	return Math.floor(n);
}

/** 群内昵称：平台字段名不统一，兼容一层嵌套 */
function pickNickname(obj) {
	if (!obj || typeof obj !== 'object') { return ''; }
	const keys = ['nickname', 'group_nick', 'groupNick', 'group_card', 'card', 'user_name', 'username', 'nick', 'display_name', 'name', 'sender_name'];
	let v = firstKey(obj, keys);
	const nested = ['sender', 'user', 'member', 'msg', 'from', 'group_member'];
	for (let i = 0; i < nested.length && !v; i++) {
		if (obj[nested[i]] && typeof obj[nested[i]] === 'object') { v = firstKey(obj[nested[i]], keys); }
	}
	return cleanNick(v);
}

/** 进群时间：平台字段名不统一，兼容一层嵌套 */
function pickJoinedAt(obj) {
	if (!obj || typeof obj !== 'object') { return 0; }
	const keys = ['joined_at', 'join_time', 'joinTime', 'join_at', 'joinAt', 'enter_time', 'enterTime', 'join_date', 'joinDate', 'member_since'];
	let v = firstKey(obj, keys);
	const nested = ['member', 'user', 'sender', 'msg', 'group_member'];
	for (let i = 0; i < nested.length && !v; i++) {
		if (obj[nested[i]] && typeof obj[nested[i]] === 'object') { v = firstKey(obj[nested[i]], keys); }
	}
	return normTs(v);
}

/** 退群事件 payload 里取用户 ID（字段名各平台不统一，含一层嵌套） */
function pickLeaveUid(payload) {
	if (!payload || typeof payload !== 'object') { return ''; }
	let v = firstKey(payload, ['user_id', 'userId', 'uid', 'uin', 'qq', 'memberId', 'member_id', 'targetId', 'target_id']);
	if (!v && payload.user && typeof payload.user === 'object') { v = firstKey(payload.user, ['user_id', 'userId', 'id', 'uin']); }
	if (!v && payload.member && typeof payload.member === 'object') { v = firstKey(payload.member, ['user_id', 'userId', 'id', 'uin']); }
	if (!v && payload.msg && typeof payload.msg === 'object') { v = firstKey(payload.msg, ['user_id', 'userId', 'uid']); }
	/* 退群统计里也可能叫 leaver_id / quit_user_id */
	if (!v) { v = firstKey(payload, ['leaver_id', 'leaverId', 'quit_user_id', 'quitUserId']); }
	return v === '0' ? '' : v;
}

/** 取群 ID */
function pickLeaveGid(payload) {
	if (!payload || typeof payload !== 'object') { return ''; }
	let v = firstKey(payload, ['group_id', 'groupId', 'gid', 'chatId', 'chat_id', 'roomId', 'room_id']);
	if (!v && payload.group && typeof payload.group === 'object') { v = firstKey(payload.group, ['id', 'group_id', 'groupId']); }
	if (!v && payload.msg && typeof payload.msg === 'object') { v = firstKey(payload.msg, ['group_id', 'groupId']); }
	return v;
}

/** 把平台返回的任意列表变成 user_id 字符串数组 */
function toUidList(list) {
	if (!Array.isArray(list)) { return null; }
	const ids = [];
	list.forEach((m) => {
		if (m == null) { return; }
		if (typeof m === 'string' || typeof m === 'number') { ids.push(String(m)); return; }
		if (typeof m === 'object') {
			const v = firstKey(m, ['user_id', 'userId', 'uid', 'uin', 'qq', 'id', 'memberId', 'leaver_id']);
			if (v) { ids.push(v); }
		}
	});
	return ids;
}

/** 全量成员列表：兼容 members / list / data / data.list */
function extractMembers(data) {
	let list = data;
	if (!Array.isArray(list) && data && typeof data === 'object') {
		list = data.members || data.list || data.data || (data.data && data.data.list);
	}
	return toUidList(list);
}

/** 退群增量列表：兼容 left / leaves / quit / removed / 直接数组 / data.left */
function extractLeaveIds(data) {
	if (Array.isArray(data)) { return toUidList(data); }
	if (!data || typeof data !== 'object') { return null; }
	const names = ['left', 'leaves', 'leave_list', 'leaveList', 'quit', 'quits', 'removed', 'exits', 'leave_users', 'records'];
	for (let i = 0; i < names.length; i++) {
		const ids = toUidList(data[names[i]]);
		if (ids) { return ids; }
	}
	const inner = data.data || data.result;
	if (inner && typeof inner === 'object') {
		for (let i = 0; i < names.length; i++) {
			const ids = toUidList(inner[names[i]]);
			if (ids) { return ids; }
		}
		if (Array.isArray(inner)) { return toUidList(inner); }
	}
	return null;
}

/* ---------------------------------------------------------------- 退群统一上报 */
/**
 * /unlock/leave 是幂等的：重复上报不会出错，所以事件与轮询可以并存。
 * 站内把 pk_unlock_state 置 left，前台 8 秒内轮询到就会换「请重新进群」文案并刷新。
 */
async function reportLeave(cfg, uid, gid, ctx, via) {
	if (!uid) { return false; }
	if (cfg.group_id && gid && String(cfg.group_id) !== String(gid)) { return false; }
	try {
		await callWp(cfg, 'leave', { code: '', bot_uid: uid, group_id: gid || cfg.group_id || '' });
		console.log('[' + PLUGIN_ID + '] ' + via + ' → 已上锁 uid=' + uid + ' group=' + (gid || '-'));
	} catch (e) {
		console.error('[' + PLUGIN_ID + '] ' + via + ' 回调失败 uid=' + uid + '：' + errText(e));
		return false;
	}
	/* 平台若还能私聊/@ 该用户，补一句提示；没这个能力就静默跳过 */
	if (cfg.leave_notify === 'on' && ctx && typeof ctx.sendText === 'function' && cfg.leave_notify_text) {
		try { await ctx.sendText(cfg.leave_notify_text); } catch (_) { /* 退群后通常已无法私聊 */ }
	}
	return true;
}

async function memberCheck(cfg, botId) {
	const headers = {};
	if (cfg.member_check_token) { headers.Authorization = 'Bearer ' + cfg.member_check_token; }
	let url = cfg.member_check_api;
	/* 平台接口若支持 since，就带上次轮询时间，只取增量退群记录 */
	if (cfg.member_check_since === 'on' && global[LAST_KEY]) {
		url += (url.indexOf('?') === -1 ? '?' : '&') + 'since=' + global[LAST_KEY];
	}
	const resp = await axios.get(url, { timeout: 8000, headers: headers });

	if (cfg.member_check_mode === 'leaves') {
		/* 平台已有「退群用户 ID 统计」：返回的直接就是退群名单 */
		const ids = extractLeaveIds(resp.data);
		global[LAST_KEY] = Math.floor(Date.now() / 1000);
		if (!ids || !ids.length) { return; }
		const uniq = Array.from(new Set(ids));
		let done = 0;
		for (let i = 0; i < uniq.length; i++) {
			if (await reportLeave(cfg, uniq[i], cfg.group_id, null, '退群ID统计')) { done++; }
		}
		if (done) { console.log('[' + PLUGIN_ID + '] 退群统计命中 ' + done + ' 人，已回推上锁'); }
		return;
	}

	/* 全量成员列表：与上次快照比对差集 */
	const ids = extractMembers(resp.data);
	global[LAST_KEY] = Math.floor(Date.now() / 1000);
	if (!ids) { return; }
	const now = new Set(ids);
	const prev = global[KNOWN_KEY];
	global[KNOWN_KEY] = now;
	/* 首次只建立基线，不误判历史成员为「退群」 */
	if (!prev || !prev.size) { return; }
	const jobs = [];
	prev.forEach((uid) => {
		if (now.has(uid)) { return; }
		jobs.push(reportLeave(cfg, uid, cfg.group_id, null, '成员差集'));
	});
	if (!jobs.length) { return; }
	const done = (await Promise.all(jobs)).filter(Boolean).length;
	if (done) { console.log('[' + PLUGIN_ID + '] 检测到 ' + done + ' 位成员退群，已回推上锁'); }
}

function startMemberPolling(cfg, botId) {
	if (global[POLL_KEY]) { clearInterval(global[POLL_KEY]); global[POLL_KEY] = null; }
	if (!cfg.member_check_api) { return; }
	const ms = Math.max(60000, cfg.member_check_interval * 60000);
	global[POLL_KEY] = setInterval(() => {
		memberCheck(cfg, botId).catch((e) => {
			console.error('[' + PLUGIN_ID + '] 群成员轮询失败：' + errText(e));
		});
	}, ms);
	if (global[POLL_KEY].unref) { global[POLL_KEY].unref(); }
	console.log('[' + PLUGIN_ID + '] 群成员轮询已启动，间隔 ' + cfg.member_check_interval + ' 分钟');
}

/* ---------------------------------------------------------------- 钩子 */
if (hooks && typeof hooks.on === 'function') {
	/* 群内消息 → 心跳续期。WordPress 侧用 bot_uid 反查，未绑定的成员会被静默忽略 */
	hooks.on('group_message', async ({ msg, ctx, bot }) => {
		try {
			if (!msg || !msg.user_id) { return; }
			const botId = (bot && bot.id) || (ctx && ctx.bot && ctx.bot.id) || 0;
			const cfg = await loadCfg(botId);
			if (!cfg.wp_base || !cfg.wp_secret || cfg.heartbeat === 'off') { return; }
			const gid = String(msg.group_id || '');
			if (cfg.group_id && gid && String(cfg.group_id) !== gid) { return; }
			const uid = String(msg.user_id);
			const seen = global[SEEN_KEY];
			const gap = cfg.heartbeat_interval * 60000;
			const last = seen.get(uid) || 0;
			if (Date.now() - last < gap) { return; }
			seen.set(uid, Date.now());
			/* 群名片可能改过，心跳顺带把最新昵称回传；不带入群时间以免反复覆盖 */
			await callWp(cfg, 'heartbeat', {
				code: '', bot_uid: uid, group_id: gid,
				nickname: pickNickname(msg),
			});
		} catch (e) {
			console.error('[' + PLUGIN_ID + '] 心跳回调失败：' + errText(e));
		}
	});

	/* 新成员被识别 → 提示发绑定码 */
	hooks.on('user_first_seen', async (payload) => {
		try {
			/* 文档里 user_first_seen 的 payload 是 { botId, groupId, userId, nickname }，
			   不一定带 ctx；有发送能力才欢迎，没有就静默跳过，避免抛错影响其它订阅者。 */
			const ctx = payload && payload.ctx;
			const botId = payload && payload.botId;
			const groupId = payload && payload.groupId;
			/* 0.13.24：先缓存首次入群的昵称与时间，等该用户发绑定码时一起回传。
			   平台没给入群时间时以「首次在群内被识别」为准，站点会标注来源。 */
			const firstUid = String((payload && (payload.userId || payload.user_id || payload.uin)) || '');
			if (firstUid && global[FIRST_KEY]) {
				global[FIRST_KEY].set(firstUid, {
					nickname: pickNickname(payload),
					joined_at: pickJoinedAt(payload) || Math.floor(Date.now() / 1000),
					at: Date.now(),
				});
			}
			if (!ctx || typeof ctx.sendText !== 'function') { return; }
			const cfg = await loadCfg(botId || 0);
			if (cfg.group_id && groupId && String(cfg.group_id) !== String(groupId)) { return; }
			if (cfg.welcome) { await ctx.sendText(cfg.welcome); }
		} catch (e) {
			console.error('[' + PLUGIN_ID + '] 欢迎语失败：' + errText(e));
		}
	});
}

/* 平台开放「退群事件」后，订阅一组常见事件名；事件名可用 leave_event 配置覆盖/追加。
   注册放在全局 Set 里，配置热重载不会重复绑定；/leave 幂等，重复触发也无副作用。 */
const LEAVE_BOUND_KEY = '__bangUnlockLeaveBound';
if (!global[LEAVE_BOUND_KEY]) { global[LEAVE_BOUND_KEY] = new Set(); }

function registerLeaveHooks(cfg) {
	if (!hooks || typeof hooks.on !== 'function') { return; }
	const names = String(cfg.leave_event || '').split(',').map((s) => s.trim()).filter(Boolean);
	DEFAULT_LEAVE_EVENTS.forEach((n) => { if (names.indexOf(n) === -1) { names.push(n); } });
	names.forEach((name) => {
		if (global[LEAVE_BOUND_KEY].has(name)) { return; }
		global[LEAVE_BOUND_KEY].add(name);
		hooks.on(name, async (payload, second) => {
			try {
				const ctx = (payload && payload.ctx)
					|| (second && typeof second.sendText === 'function' ? second : null);
				const botId = (payload && (payload.botId || payload.bot_id))
					|| (ctx && ctx.bot && ctx.bot.id) || 0;
				const live = await loadCfg(botId);
				const uid = pickLeaveUid(payload);
				const gid = pickLeaveGid(payload);
				if (!uid) { return; }
				await reportLeave(live, uid, gid, ctx, '事件 ' + name);
			} catch (e) {
				console.error('[' + PLUGIN_ID + '] 退群事件处理失败：' + errText(e));
			}
		});
	});
}

/* ---------------------------------------------------------------- 插件导出 */
const meta = {
	id: PLUGIN_ID,
	name: 'Bang 阅读解锁',
	version: '1.2.0',
	author: 'naigou',
	category: '工具',
	description: '进群发送绑定码解锁 WordPress 文章；支持退群事件 / 退群 ID 统计即时上锁，群内心跳 TTL 兜底。',
	entry: 'bang-unlock/index.js',
	usage: '私聊或群里发送网站上的 8 位绑定码即可完成绑定',
	channels: ['wechat', 'qq'],
	commandPrefix: ['绑定', '解锁', '/bind', '/unlock'],
	configurable: true,
	settingsSchema: [
		{ key: 'wp_base', label: 'WordPress 站点地址', type: 'text', placeholder: 'https://bang.ink', help: '必须是机器人服务器能访问到的公网地址，结尾不要带斜杠。' },
		{ key: 'wp_secret', label: '回调密钥', type: 'password', help: '与 Bang 设置 → 阅读解锁 → 机器人回调密钥 完全一致。' },
		{ key: 'wp_rest_base', label: 'REST 前缀', type: 'text', placeholder: DEFAULT_REST_BASE, help: '站点开启了固定链接就用默认值；没开固定链接填 /?rest_route=/pianke/v1/unlock 。' },
		{ key: 'group_id', label: '目标群 ID', type: 'text', help: '只对该群生效。留空表示接受任意群的消息，多群站点建议填。' },
		{
			key: 'heartbeat', label: '群内心跳', type: 'select',
			options: [{ value: 'on', label: '开启（推荐）' }, { value: 'off', label: '关闭' }],
			help: '群内每收到一条该用户的消息就续期一次，是判定「仍在群内」的依据。',
		},
		{ key: 'heartbeat_interval', label: '心跳节流（分钟）', type: 'number', help: '同一用户在这个时间内只回推一次，默认 5。' },
		{
			key: 'leave_event', label: '退群事件名', type: 'text',
			placeholder: DEFAULT_LEAVE_EVENTS.join(','),
			help: '平台开放退群事件后填这里（逗号分隔可填多个）。payload 需带 userId / groupId，插件会立即回调 /leave 上锁。留空也会订阅一组常见事件名。',
		},
		{
			key: 'member_check_api', label: '退群统计 / 群成员接口（可选）', type: 'text',
			help: '平台已有退群用户 ID 统计时，填返回退群名单的 HTTP 接口并把模式选「退群名单」；若平台只给全量群成员列表，就选「成员差集」。留空则只用心跳有效期兜底。',
		},
		{
			key: 'member_check_mode', label: '接口返回模式', type: 'select',
			options: [
				{ value: 'leaves', label: '退群名单（推荐，平台已有统计）' },
				{ value: 'members', label: '成员差集（全量群成员列表）' },
			],
			help: '退群名单：接口返回 left/leaves/records 等 ID 列表，逐个上锁；成员差集：接口返回全量成员 ID，与上次快照比对出退群者。',
		},
		{ key: 'member_check_token', label: '接口 Bearer Token（可选）', type: 'password', help: '平台接口需要鉴权时填，会作为 Authorization: Bearer <token> 发出。' },
		{
			key: 'member_check_since', label: '带 since 增量参数', type: 'select',
			options: [{ value: 'on', label: '开启（推荐）' }, { value: 'off', label: '关闭' }],
			help: '轮询时附加 ?since=<上次轮询 Unix 秒>，平台可只返回这段时间的退群记录；不支持忽略即可。',
		},
		{ key: 'member_check_interval', label: '接口轮询间隔（分钟）', type: 'number', help: '默认 5，最小 1。仅在填写接口后生效。' },
		{ key: 'leave_notify', label: '退群时私聊提醒', type: 'select', options: [{ value: 'off', label: '关闭' }, { value: 'on', label: '开启' }], help: '需要平台允许退群后仍能私聊/@ 该用户；没有该能力就保持关闭，站内页面会自动换成「请重新进群」提示。' },
		{ key: 'leave_notify_text', label: '退群提醒文案', type: 'text' },
		{ key: 'welcome', label: '新成员欢迎语', type: 'textarea', help: '识别到新成员时发送，留空则不发。' },
		{ key: 'bind_ok', label: '绑定成功回复', type: 'text' },
		{ key: 'bind_fail', label: '绑定失败前缀', type: 'text' },
	],
};

async function onMessage(msg, ctx) {
	const code = pickCode(msg && msg.content);
	if (!code) { return false; }

	const botId = (ctx && ctx.bot && ctx.bot.id) || 0;
	const cfg = await loadCfg(botId);
	if (!cfg.wp_base || !cfg.wp_secret) {
		await ctx.sendText('机器人还没配置站点地址或回调密钥，请联系站长。');
		return true;
	}
	const gid = String((msg && msg.group_id) || '');
	if (cfg.group_id && gid && String(cfg.group_id) !== gid) {
		return false; /* 别的群的消息交回其它插件 */
	}
	const botUid = String((msg && msg.user_id) || '');
	if (!botUid) {
		await ctx.sendText('拿不到你的机器人 ID，请在群里直接发送绑定码。');
		return true;
	}
	try {
		/* 0.13.24：优先用本次消息里的群名片 / 入群时间，缺的用首次入群事件缓存补齐 */
		const first = (global[FIRST_KEY] && global[FIRST_KEY].get(botUid)) || {};
		await callWp(cfg, 'bind', {
			code: code, bot_uid: botUid, group_id: gid,
			nickname: pickNickname(msg) || first.nickname || '',
			joined_at: pickJoinedAt(msg) || first.joined_at || 0,
		});
		await rememberBind(botId, botUid, code, gid);
		await ctx.sendText(cfg.bind_ok);
	} catch (e) {
		await ctx.sendText((cfg.bind_fail || '绑定失败：') + errText(e));
	}
	return true;
}

async function verifyConnection(cfg) {
	if (!cfg || !cfg.wp_base) { return { success: false, message: '请先填写 WordPress 站点地址' }; }
	if (!cfg.wp_secret) { return { success: false, message: '请先填写回调密钥' }; }
	try {
		const r = await axios.get(restUrl({ wp_base: cfg.wp_base, wp_rest_base: cfg.wp_rest_base || DEFAULT_REST_BASE }, 'ping'), { timeout: 8000 });
		if (r.data && r.data.ok) {
			return { success: true, message: '连接成功，主题版本 ' + (r.data.version || '?') + (r.data.enabled ? '，解锁已开启' : '，但站点尚未开启阅读解锁') };
		}
		return { success: false, message: '站点返回异常：' + JSON.stringify(r.data).slice(0, 100) };
	} catch (e) {
		return { success: false, message: '连接失败：' + errText(e) };
	}
}

async function applyConfigChange(botId) {
	try {
		const cfg = await loadCfg(botId || 0);
		registerLeaveHooks(cfg);
		startMemberPolling(cfg, botId || 0);
		/* 配置变更时清空节流，让下一次群消息立刻续期 */
		global[SEEN_KEY] = new Map();
		global[LAST_KEY] = 0;
	} catch (e) {
		console.error('[' + PLUGIN_ID + '] 配置热重载失败：' + errText(e));
	}
}

/* 模块加载时也启动一次（server.js 启动后会加载插件） */
loadCfg(0).then((cfg) => {
	registerLeaveHooks(cfg);
	startMemberPolling(cfg, 0);
}).catch((e) => {
	console.error('[' + PLUGIN_ID + '] 初始化失败：' + errText(e));
});

module.exports = { meta, onMessage, verifyConnection, applyConfigChange };
