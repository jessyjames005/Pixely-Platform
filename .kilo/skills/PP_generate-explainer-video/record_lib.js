// Reusable recorder for explanatory videos of a LOCAL app: headless Chromium (Playwright) with
// recordVideo, caption bar (subtitles), visible cursor, simulated confirm() popin.
// Login runs in an UNRECORDED context (credentials from env MB_LOGIN / MB_PWD, never in the video).
// Usage from a scenario script: const r = await newRecorder('name', '/tmp/out'); await r.cap('text', 2500); ...
const { chromium } = require('playwright');
const http = require('http');

const BASE = process.env.MB_BASE || 'http://localhost/pixely platform/';
const HELPER = process.env.MB_HELPER || 'http://172.17.0.1:8099';  // optional local SQL helper (see SKILL.md)
const VIEWPORT = { width: 1500, height: 844 };
const HOST_IP = process.env.MB_HOST_IP || '172.17.0.1';  // docker bridge gateway = machine hosting the app
const ARGS = ['--no-sandbox', '--host-resolver-rules=MAP localhost ' + HOST_IP];

function helper(path) {
  return new Promise((resolve, reject) => {
    http.get(HELPER + path, (res) => {
      let d = ''; res.on('data', c => d += c); res.on('end', () => res.statusCode === 200 ? resolve(d) : reject(new Error(path + ' -> ' + res.statusCode + ' ' + d)));
    }).on('error', reject);
  });
}

// Runs in every page: caption bar, visible cursor, simulated confirm() popin.
function initScript() {
  const build = () => {
    if (document.getElementById('__cap') || !document.documentElement) return;
    const bar = document.createElement('div');
    bar.id = '__cap';
    bar.style.cssText = 'position:fixed;left:0;right:0;bottom:0;z-index:2147483647;background:rgba(10,15,40,.9);color:#fff;font:600 21px/1.35 "DejaVu Sans",Arial,sans-serif;padding:12px 28px;text-align:center;pointer-events:none;display:none';
    document.documentElement.appendChild(bar);
    const cur = document.createElement('div');
    cur.id = '__cur';
    cur.style.cssText = 'position:fixed;width:24px;height:24px;border-radius:50%;background:rgba(255,60,60,.5);border:2px solid #fff;z-index:2147483646;pointer-events:none;left:-60px;top:-60px;transform:translate(-50%,-50%)';
    document.documentElement.appendChild(cur);
    document.addEventListener('mousemove', e => { cur.style.left = e.clientX + 'px'; cur.style.top = e.clientY + 'px'; }, true);
    document.addEventListener('mousedown', () => { cur.style.background = 'rgba(255,220,0,.95)'; setTimeout(() => cur.style.background = 'rgba(255,60,60,.5)', 300); }, true);
  };
  setInterval(() => {
    build();
    const bar = document.getElementById('__cap');
    if (!bar) return;
    let t = '';
    try { t = localStorage.getItem('__cap') || ''; } catch (e) {}
    if (bar.textContent !== t) { bar.textContent = t; bar.style.display = t ? 'block' : 'none'; }
  }, 120);
  window.confirm = (msg) => {
    const box = document.createElement('div');
    box.style.cssText = 'position:fixed;top:90px;left:50%;transform:translateX(-50%);z-index:2147483647;background:#fff;color:#222;border:1px solid #888;border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,.45);padding:18px 22px;font:16px "DejaVu Sans",Arial,sans-serif;min-width:420px';
    box.innerHTML = '<div style="font-size:13px;color:#666;margin-bottom:8px">Popin du navigateur (confirm)</div><div style="margin-bottom:14px">' + String(msg).replace(/</g, '&lt;') + '</div><div style="text-align:right"><span style="padding:6px 14px;border:1px solid #aaa;border-radius:6px;margin-right:8px">Annuler</span><span style="padding:6px 18px;background:#2b63d9;color:#fff;border-radius:6px">OK</span></div>';
    (document.body || document.documentElement).appendChild(box);
    setTimeout(() => box.remove(), 3200);
    window.__confirms = (window.__confirms || []).concat([String(msg)]);
    return true;
  };
}

async function newRecorder(name, outDir) {
  const browser = await chromium.launch({ headless: true, args: ARGS });
  // 1) login in an unrecorded context: credentials never appear in the video
  const loginCtx = await browser.newContext({ viewport: VIEWPORT });
  const lp = await loginCtx.newPage();
  await lp.goto(BASE + 'gui/login');
  await lp.locator('input[name="username"]').fill(process.env.MB_LOGIN);
  await lp.locator('input[name="password"]').fill(process.env.MB_PWD);
  await lp.locator('.AuthPageLoginWrapper-submit button[type="submit"]').click();
  await lp.waitForURL(u => !u.pathname.endsWith('/gui/login'), { timeout: 30000 });
  const state = await loginCtx.storageState();
  await loginCtx.close();
  // 2) recorded context
  const ctx = await browser.newContext({ viewport: VIEWPORT, storageState: state, recordVideo: { dir: outDir, size: VIEWPORT } });
  await ctx.addInitScript(initScript);
  const page = await ctx.newPage();
  page.setDefaultTimeout(20000);

  const api = {
    page, browser, ctx,
    async cap(text, ms = 2500) {
      await page.evaluate(t => { try { localStorage.setItem('__cap', t); } catch (e) {} }, text);
      await page.waitForTimeout(ms);
    },
    confirmCount: 0,
    async modalCap(yes, no, ms = 3500) {
      const has = await page.evaluate(() => document.body.innerText.includes('Choix du poste et du concentrateur'));
      console.log('modalCap:', has ? 'MODAL' : 'no modal', '|', (has ? yes : no).slice(0, 60));
      await api.cap(has ? yes : no, ms);
      return has;
    },
    async popinCap(yes, no, ms = 3800) {
      const n = await page.evaluate(() => (window.__confirms || []).length);
      const got = n > api.confirmCount;
      api.confirmCount = n;
      console.log('popinCap:', got ? 'POPIN' : 'no popin', '|', (got ? yes : no).slice(0, 60));
      await api.cap(got ? yes : no, ms);
      return got;
    },
    async goto(url, ready) { api.confirmCount = 0; await page.goto(BASE + url); if (ready) { await page.waitForSelector(ready, { timeout: 40000 }); } await page.waitForTimeout(2500); },
    async set(form, field, value, tries = 150) {
      await page.waitForTimeout(1200);
      const ok = await page.evaluate(async ([f, n, v, t]) => {
        for (let i = 0; i < t; i++) {
          const fm = document.forms[f];
          if (fm && fm[n]) { $V(fm[n], v); return true; }
          await new Promise(r => setTimeout(r, 200));
        }
        return false;
      }, [form, field, value, tries]);
      if (!ok) { throw new Error('set: field not found ' + form + '.' + field); }
    },
    async click(selector) {
      const l = page.locator(selector).first();
      await l.scrollIntoViewIfNeeded();
      await l.hover();
      await page.waitForTimeout(350);
      await l.click();
    },
    async closeModal() { await page.evaluate(() => { if (window.Control && Control.Modal) Control.Modal.close(); }); await page.waitForTimeout(600); },
    async hasModal(text) { return page.evaluate(t => document.body.innerText.includes(t), text); },
    async finish() {
      const v = page.video();
      await ctx.close();
      const p = await v.path();
      await browser.close();
      return p;
    },
  };
  return api;
}

module.exports = { newRecorder, helper, BASE };

