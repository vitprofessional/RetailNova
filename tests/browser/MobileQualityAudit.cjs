#!/usr/bin/env node
/* Mobile quality gate. Requires RN_AUDIT_BASE_URL and authenticated credentials (or storage state). */
const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');

const root = path.resolve(__dirname, '../..');
const out = path.resolve(root, process.env.RN_AUDIT_OUTPUT || 'tests-output/quality');
const base = (process.env.RN_AUDIT_BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const fullGate = !process.argv.includes('--gate=foundation');
const inventory = fs.readFileSync(path.join(root, 'docs/MOBILE_PAGE_INVENTORY.md'), 'utf8');
const routes = inventory.split(/\r?\n/).flatMap(line => {
  if (!line.startsWith('|')) return [];
  const cells = line.split('|').map(cell => cell.trim());
  return cells[5] && /^UI-/.test(cells[5]) && !/legacy statement contract unresolved/i.test(cells[5]) && cells[1] ? [cells[1].replace(/^`|`$/g, '')] : [];
})
  .filter(route => !/[?*]/.test(route))
  .map(route => route.replace(/\{[^}]+\}/g, '1'));
function auditDocumentIds() {
  if (process.env.RN_AUDIT_DOCUMENT_IDS) return JSON.parse(process.env.RN_AUDIT_DOCUMENT_IDS);
  const fixturePath = process.env.RN_AUDIT_FIXTURE_DB;
  if (!fixturePath) return {};
  const outputRoot = path.resolve(root, 'tests-output') + path.sep;
  const absoluteFixturePath = path.resolve(fixturePath);
  if (!absoluteFixturePath.startsWith(outputRoot)) throw new Error('RN_AUDIT_FIXTURE_DB must point inside tests-output/.');
  const stdout = execFileSync(process.env.PHP_EXECUTABLE || 'C:\\xampp\\php\\php.exe', [
    path.join(__dirname, 'SeedMobileQualityAudit.php'),
  ], { encoding: 'utf8', env: { ...process.env, APP_ENV: 'local', DB_CONNECTION: 'sqlite', DB_DATABASE: absoluteFixturePath } });
  const jsonLine = stdout.trim().split(/\r?\n/).at(-1);
  return JSON.parse(jsonLine);
}
const documentIds = auditDocumentIds();
const documentRoutes = ['sale-invoice','purchase-invoice','quotation','service-invoice','sale-return','purchase-return','customer-ledger','supplier-ledger']
  .map(type => `documents/${type}/${documentIds[type] || 1}`);
const urls = [...new Set([...routes, ...documentRoutes].map(route => new URL(route.replace(/^\//, ''), base + '/').toString()))];
const onlyRoutes = (process.env.RN_AUDIT_ONLY || '').split(',').map(value => value.trim()).filter(Boolean);
const auditedUrls = onlyRoutes.length ? urls.filter(url => onlyRoutes.some(route => new URL(url).pathname.includes(route))) : urls;
  const scrollEndRoutes = (process.env.RN_AUDIT_SCROLL_END_ROUTES || '').split(',').map(value => value.trim()).filter(Boolean);

function scanAppNativeDialogCalls() {
  const roots = ['public/js', 'resources/views'];
  const files = [];
  const walk = directory => {
    if (!fs.existsSync(directory)) return;
    for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
      if (entry.name === 'vendor' || entry.name === 'node_modules') continue;
      const full = path.join(directory, entry.name);
      if (entry.isDirectory()) walk(full);
      else if (/\.(?:js|blade\.php)$/i.test(entry.name)) files.push(full);
    }
  };
  roots.forEach(relative => walk(path.join(root, relative)));
  const findings = [];
  for (const file of files) {
    const source = fs.readFileSync(file, 'utf8');
    const pattern = /\b(?:window\s*\.\s*)?(?:alert|confirm|prompt)\s*\(/gi;
    for (const match of source.matchAll(pattern)) findings.push({ file: path.relative(root, file).replaceAll('\\', '/'), offset: match.index, call: match[0] });
  }
  return findings;
}

async function inspectPage(page) {
  return page.evaluate(async () => {
    const visible = element => {
      const r = element.getBoundingClientRect(), s = getComputedStyle(element);
      return r.width > 0 && r.height > 0 && s.visibility !== 'hidden' && s.display !== 'none'
        && (!element.closest('details:not([open])') || Boolean(element.closest('summary')));
    };
    const leaves = [...document.querySelectorAll('body *')].filter(el => el.children.length === 0 && el.textContent.trim() && visible(el));
    const isDocument = /\/documents\//.test(location.pathname);
    const allowed = [13,14,16,17,18,21,30];
    const isIcon = el => el.matches('i,svg,path,[class^="ri-"],[class*=" ri-"],[class^="fa-"],[class*=" fa-"]')
      || /fontawesome|remixicon|line awesome|material icons|bootstrap-icons/i.test(getComputedStyle(el).fontFamily);
    const badFont = isDocument ? [] : leaves.filter(el => !isIcon(el) && !/^"?Hind Siliguri"?/i.test(getComputedStyle(el).fontFamily.trim())).slice(0, 12).map(el => ({ text: el.textContent.trim().slice(0,40), font: getComputedStyle(el).fontFamily }));
    const badSize = isDocument ? [] : leaves.filter(el => { const size = parseFloat(getComputedStyle(el).fontSize); return size < 13 || !allowed.some(value => Math.abs(value-size)<0.1); }).slice(0, 20).map(el => ({ text: el.textContent.trim().slice(0,40), size: getComputedStyle(el).fontSize }));
    const overflow = document.documentElement.scrollWidth > innerWidth;
    const desktopOnlyVisible = [...document.querySelectorAll('[data-desktop-only],.d-none.d-md-block,.d-none.d-lg-block,.d-none.d-xl-block,.d-none.d-xxl-block')]
      .filter(visible).slice(0, 12).map(el => ({ tag: el.tagName.toLowerCase(), className: String(el.className || ''), text: el.textContent.trim().slice(0, 60) }));
    const cardLike = el => {
      const s = getComputedStyle(el);
      if (el.matches('input,select,textarea,button,label,details,summary,option,table,thead,tbody,tfoot,tr,td,th')) return false;
      if (el.matches('.card') && el.querySelector('.rn-m-master-card')) return false;
      const namedCard = el.matches('.card,.rn-m-card,article');
      const radius = parseFloat(s.borderTopLeftRadius) > 0;
      const border = parseFloat(s.borderTopWidth) > 0 && s.borderTopStyle !== 'none';
      const background = s.backgroundColor !== 'rgba(0, 0, 0, 0)' || s.backgroundImage !== 'none';
      const padding = parseFloat(s.paddingTop) >= 8 && parseFloat(s.paddingRight) >= 8 && parseFloat(s.paddingBottom) >= 8 && parseFloat(s.paddingLeft) >= 8;
      const block = ['block','flow-root','flex','grid','list-item'].includes(s.display);
      return visible(el) && (namedCard || (block && border && radius && background && padding));
    };
    const cardNodes = [...document.querySelectorAll('body *')].filter(cardLike);
    const nestedCards = cardNodes.flatMap(el => {
      let parent = el.parentElement;
      while (parent && parent !== document.body) {
        if (cardLike(parent)) return [{ child: el.tagName.toLowerCase()+'.'+String(el.className).replace(/\s+/g,'.'), parent: parent.tagName.toLowerCase()+'.'+String(parent.className).replace(/\s+/g,'.') }];
        parent = parent.parentElement;
      }
      return [];
    }).slice(0, 12);
    const latinDigits = document.documentElement.lang.startsWith('bn') ? leaves.filter(el => {
      if (el.matches('input,textarea') || el.closest('[data-latin-ok]') || el.closest('[aria-hidden="true"]')) return false;
      return /[0-9]/.test(el.textContent);
    }).slice(0, 30).map(el => ({ text: el.textContent.trim().slice(0,80), tag: el.tagName.toLowerCase(), className: typeof el.className === 'string' ? el.className : '' })) : [];
    const dangerProbe = document.createElement('span');
    dangerProbe.style.color = 'var(--rn-color-danger)';
    document.body.appendChild(dangerProbe);
    const danger = getComputedStyle(dangerProbe).color;
    dangerProbe.remove();
    const negativeMoney = [...document.querySelectorAll('.rn-m-number,[data-rn-component="money"]')].filter(el => visible(el) && /(?:−|-)\s*(?:৳|[$€£])/.test(el.textContent.trim()))
      .filter(el => getComputedStyle(el).color !== danger)
      .slice(0, 12).map(el => ({ text: el.textContent.trim(), color: getComputedStyle(el).color, expected: danger }));
    const emptyAvatars = [...document.querySelectorAll('.rn-m-card-avatar,.rn-m-ledger-avatar,.rn-m-picker-card__image,.rn-product-thumb,.rn-m-product-thumb,[data-rn-avatar]')]
      .filter(visible).filter(el => !el.querySelector('img[src]') && !el.textContent.trim())
      .slice(0, 12).map(el => ({ tag: el.tagName.toLowerCase(), className: typeof el.className === 'string' ? el.className : '' }));
    const headingGutter = [...document.querySelectorAll('h2,h3')].filter(visible).filter(el => el.getBoundingClientRect().left < 16)
      .slice(0, 12).map(el => ({ text: el.textContent.trim().slice(0,60), left: Math.round(el.getBoundingClientRect().left) }));
    const nativeDialogs = (window.__rnNativeDialogCalls || []).map(call => ({ method: call.method }));
    const actionIconCandidates = [...document.querySelectorAll('button i,button svg,a i,a svg,button [class*="ri-"],a [class*="ri-"],button [class*="la-"],a [class*="la-"],button [class*="fa-"],a [class*="fa-"]')];
    const brokenActionIcons = actionIconCandidates
      .filter(icon => {
        const style = getComputedStyle(icon);
        return visible(icon.parentElement) && style.display !== 'none' && style.visibility !== 'hidden';
      }).filter(icon => {
        const rect = icon.getBoundingClientRect();
        const pseudo = getComputedStyle(icon, '::before');
        const hasPseudoGlyph = pseudo.content && pseudo.content !== 'none' && pseudo.content !== 'normal' && pseudo.content !== '""';
        return rect.width < 2 || (!icon.matches('svg') && !icon.querySelector('svg') && !hasPseudoGlyph);
      }).slice(0, 20).map(icon => ({ className: typeof icon.className === 'string' ? icon.className : icon.getAttribute('class'), width: Math.round(icon.getBoundingClientRect().width), glyph: getComputedStyle(icon, '::before').content }));
    // Check action/control overlap at the furthest scroll position. Fixed action bars
    // naturally pass over fields while scrolling, but must never hide a field that
    // the user has brought to the end of the form.
    const originalScrollTop = window.scrollY;
    document.scrollingElement.scrollTop = 0;
    const contentRoot = document.querySelector('body.rn-app .content-page');
    const isInFlowContent = element => {
      let current = element;
      while (current && current !== contentRoot) {
        if (['fixed','sticky','absolute'].includes(getComputedStyle(current).position)) return false;
        current = current.parentElement;
      }
      return current === contentRoot;
    };
    const contentAtTopBottom = contentRoot ? [...contentRoot.querySelectorAll('*')].filter(el => {
      const style = getComputedStyle(el), rect = el.getBoundingClientRect();
      return visible(el) && isInFlowContent(el) && rect.width > 0 && rect.height > 0
        && (style.backgroundColor !== 'rgba(0, 0, 0, 0)' || style.backgroundImage !== 'none'
          || (parseFloat(style.borderTopWidth) > 0 && style.borderTopStyle !== 'none')
          || (el.children.length === 0 && Boolean(el.textContent.trim())) || el.matches('img,canvas,svg,video'));
    }).reduce((max, el) => Math.max(max, el.getBoundingClientRect().bottom), 0) : 0;
    const fixedBottom = [...document.querySelectorAll('.rn-m-tabbar,.rn-m-sticky-actions,.rn-m-admin-actions,.rn-q6-sticky,.rn-q6-payment-footer,.rn-m-fab,.rn-m-master-fab,.rn-m-pos-bar')]
      .filter(el => visible(el) && getComputedStyle(el).position === 'fixed')
      .map(el => ({ el, top: el.getBoundingClientRect().top, className: String(el.className || '') }))
      .sort((a, b) => a.top - b.top);
    const usableBottom = fixedBottom[0]?.top ?? innerHeight;
    const shortPageScroll = !isDocument && contentAtTopBottom <= usableBottom + 1 && document.scrollingElement.scrollHeight > innerHeight + 1
      ? [{ scrollHeight: document.scrollingElement.scrollHeight, viewport: innerHeight, contentBottom: Math.round(contentAtTopBottom), usableBottom: Math.round(usableBottom) }]
      : [];
    document.scrollingElement.scrollTop = document.scrollingElement.scrollHeight;
    await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
    const paintedContent = contentRoot ? [...contentRoot.querySelectorAll('*')].filter(el => {
      if (!visible(el) || !isInFlowContent(el)) return false;
      const style = getComputedStyle(el), rect = el.getBoundingClientRect();
      const hasPaint = style.backgroundColor !== 'rgba(0, 0, 0, 0)' || style.backgroundImage !== 'none'
        || (parseFloat(style.borderTopWidth) > 0 && style.borderTopStyle !== 'none');
      const hasText = Boolean(el.textContent.trim()) && (el.children.length === 0 || el.matches('summary'));
      const isMedia = el.matches('img,canvas,svg,video');
      return rect.width > 0 && rect.height > 0 && (hasPaint || hasText || isMedia);
    }) : [];
    const lastContent = paintedContent
      .filter(el => {
        const rect = el.getBoundingClientRect();
        return rect.bottom > 0 && rect.top < innerHeight;
      })
      .sort((a, b) => b.getBoundingClientRect().bottom - a.getBoundingClientRect().bottom)[0];
    const lastContentBottom = lastContent ? lastContent.getBoundingClientRect().bottom : 0;
    const fixedTop = fixedBottom[0] || null;
    const allowedGap = fixedTop && /rn-m-(?:master-)?fab/.test(fixedTop.className) ? 72 : 24;
    const gap = fixedTop && lastContent ? fixedTop.top - lastContentBottom : null;
    const bottomSpacing = document.scrollingElement.scrollHeight > innerHeight + 1
      && gap !== null && (gap > allowedGap || gap < -2)
      ? [{ gap: Math.round(gap), allowed: allowedGap, lastContent: lastContent.tagName.toLowerCase()+'.'+String(lastContent.className || '').replace(/\s+/g,'.'), fixed: fixedTop.className }]
      : [];
    const overlappingControls = [];
    const controls = [...document.querySelectorAll('input,select,textarea')].filter(visible);
    const actionButtons = [...document.querySelectorAll('button')].filter(visible);
    for (const button of actionButtons) {
      const buttonRect = button.getBoundingClientRect();
      for (const control of controls) {
        const rect = control.getBoundingClientRect();
        const overlapX = Math.min(buttonRect.right, rect.right) - Math.max(buttonRect.left, rect.left);
        const overlapY = Math.min(buttonRect.bottom, rect.bottom) - Math.max(buttonRect.top, rect.top);
        if (overlapX > 2 && overlapY > 2) overlappingControls.push({ button: button.textContent.trim().slice(0,40), control: control.name || control.id || control.tagName.toLowerCase(), overlap: { x: Math.round(overlapX), y: Math.round(overlapY) } });
      }
    }
    const stickyCoveredFields = [];
    for (const footer of [...document.querySelectorAll('.rn-m-sticky-actions,.rn-q6-sticky,.rn-q6-payment-footer,[data-rn-sticky-actions]')].filter(visible)) {
      const footerRect = footer.getBoundingClientRect();
      for (const field of controls) {
        const rect = field.getBoundingClientRect();
        const overlapY = Math.min(footerRect.bottom, rect.bottom) - Math.max(footerRect.top, rect.top);
        const overlapX = Math.min(footerRect.right, rect.right) - Math.max(footerRect.left, rect.left);
        if (overlapX > 2 && overlapY > 2) stickyCoveredFields.push({ footer: String(footer.className || footer.tagName), field: field.name || field.id || field.tagName.toLowerCase(), overlap: { x: Math.round(overlapX), y: Math.round(overlapY) } });
      }
    }
    window.scrollTo(0, originalScrollTop);
    const unwrappedFlash = [...document.querySelectorAll('.alert,.alert-success,.alert-danger,.alert-warning,[data-flash-message]')]
      .filter(visible).filter(el => !el.closest('[data-rn-feedback],[data-rn-toast],[data-rn-error-banner]'))
      .slice(0, 12).map(el => ({ text: el.textContent.trim().slice(0,100), className: typeof el.className === 'string' ? el.className : '' }));
    const wrappedNumbers = [...document.querySelectorAll('.rn-m-number,[data-rn-component]')].filter(visible).filter(el => {
      const range = document.createRange();
      range.selectNodeContents(el);
      return range.getClientRects().length > 1 || el.scrollWidth > el.clientWidth;
    }).map(el => el.textContent.trim().slice(0,40));
    return { badFont, badSize, overflow, desktopOnlyVisible, nestedCards, latinDigits, negativeMoney, emptyAvatars, headingGutter, nativeDialogs, brokenActionIcons, overlappingControls, stickyCoveredFields, bottomSpacing, shortPageScroll, unwrappedFlash, wrappedNumbers, isDocument };
  });
}

async function main() {
  fs.mkdirSync(out, { recursive: true });
  if (process.argv.includes('--scan-native-dialogs')) {
    const calls = scanAppNativeDialogCalls();
    fs.writeFileSync(path.join(out, 'native-dialog-source-scan.json'), JSON.stringify(calls, null, 2));
    console.log(JSON.stringify({ filesScanned: 'public/js + resources/views (app-owned only)', calls: calls.length, report: path.join(out, 'native-dialog-source-scan.json') }, null, 2));
    if (calls.length) process.exitCode = 1;
    return;
  }
  if (!fullGate) {
    const tokenCss = fs.readFileSync(path.join(root, 'public/css/rn-tokens.css'), 'utf8');
    const localization = fs.readFileSync(path.join(root, 'resources/views/partials/localization.blade.php'), 'utf8');
    const saleCss = fs.readFileSync(path.join(root, 'public/css/new-sale.css'), 'utf8');
    const shellCss = fs.readFileSync(path.join(root, 'public/css/retailnova-shell.css'), 'utf8');
    const loginCss = fs.readFileSync(path.join(root, 'public/css/login.css'), 'utf8');
    const layout = fs.readFileSync(path.join(root, 'resources/views/include.blade.php'), 'utf8');
    const requiredTokens = ['--rn-fs-hero','--rn-fs-title','--rn-fs-amount','--rn-fs-heading','--rn-fs-body','--rn-fs-label','--rn-fs-tag', '--rn-space-1','--rn-space-2','--rn-space-3','--rn-space-4','--rn-space-5','--rn-space-6','--rn-space-8'];
    const findings = [];
    if (!tokenCss.includes('"Hind Siliguri"')) findings.push('Hind Siliguri missing from shared tokens');
    requiredTokens.filter(token => !tokenCss.includes(token)).forEach(token => findings.push(`Missing token ${token}`));
    if (/html\[lang="bn"\].*(body|input|select|button|textarea).*font-family/i.test(localization)) findings.push('Localization font override remains');
    if (/font-family\s*:\s*["']?(?:Noto|Inter|Lato)/i.test(saleCss + shellCss)) findings.push('New Sale or shell has a competing font');
    if (!/@media\s*\(max-width:\s*767px\)[\s\S]*?body\.rn-login-page\s*\{\s*font-family:\s*var\(--rn-font-family\)/i.test(loginCss)) findings.push('Mobile login does not use the shared font');
    if (!layout.includes('<div class="rn-m-page">')) findings.push('Mobile page wrapper missing');
    for (const component of ['money','qty','date']) if (!fs.existsSync(path.join(root, `resources/views/components/m/${component}.blade.php`))) findings.push(`Missing x-m.${component} component`);
    const report = { gate: 'foundation', findings, fontOverridesRemoved: true, pagesAudited: 0, note: 'Static Q1 foundation gate; full authenticated page audit is the Q3 acceptance gate.' };
    fs.writeFileSync(path.join(out, 'audit-foundation.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify({ gate: 'foundation', violations: findings.length, report: path.join(out,'audit-foundation.json') }, null, 2));
    if (findings.length) process.exitCode = 1;
    return;
  }
  const { chromium, webkit, devices } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
  const engineName = process.env.RN_AUDIT_ENGINE === 'webkit' ? 'webkit' : 'chromium';
  const browserType = engineName === 'webkit' ? webkit : chromium;
  const browser = await browserType.launch({ headless: true, ...(engineName === 'chromium' ? { executablePath: process.env.BROWSER_EXECUTABLE || process.env.CHROME_PATH || undefined } : {}) });
  const device = engineName === 'webkit' ? devices['iPhone 14 Pro Max'] : devices['Pixel 7'];
  const context = await browser.newContext({ ...device, viewport: { width: 390, height: 844 }, locale: 'bn-BD',
    ...(engineName === 'chromium' ? { userAgent: 'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36' } : {}),
    serviceWorkers: 'block', storageState: process.env.RN_AUDIT_STORAGE_STATE || undefined });
  const page = await context.newPage();
  await page.addInitScript(() => {
    const setSafeBottom = () => document.documentElement.style.setProperty('--rn-safe-bottom', '34px');
    if (document.documentElement) setSafeBottom();
    else document.addEventListener('DOMContentLoaded', setSafeBottom, { once: true });
  });
  await page.addInitScript(() => {
    const calls = [];
    Object.defineProperty(window, '__rnNativeDialogCalls', { value: calls, configurable: false, writable: true });
    for (const method of ['alert', 'confirm', 'prompt']) {
      window[method] = function (...args) {
        calls.push({ method });
        return method === 'confirm' ? false : (method === 'prompt' ? null : undefined);
      };
    }
  });
  page.on('dialog', dialog => dialog.dismiss());
  const errors = [];
  const networkFlakes = [];
  let activeAuditUrl = null;
  let routeNetworkFailures = [];
  page.on('requestfailed', request => {
    const error = request.failure()?.errorText || '';
    if (/ERR_NO_BUFFER_SPACE|ERR_CONNECTION_RESET|ERR_NETWORK_CHANGED|ERR_TIMED_OUT|ECONNRESET|ETIMEDOUT/i.test(error)) {
      routeNetworkFailures.push({ url: request.url(), error });
    }
  });
  page.on('pageerror', error => errors.push({ url: page.url(), message: error.message }));
  // The app's documented Font Awesome kit may return 403 from its external host.
  // Only fail on browser errors from this application origin; pageerror still catches JS faults.
  const appOrigin = new URL(base).origin;
  page.on('console', message => {
    if (message.type() !== 'error') return;
    if (/ERR_NO_BUFFER_SPACE|ERR_CONNECTION_RESET|ERR_NETWORK_CHANGED|ERR_TIMED_OUT|ECONNRESET|ETIMEDOUT/i.test(message.text())) {
      routeNetworkFailures.push({ url: message.location()?.url || page.url(), error: message.text() });
      return;
    }
    const location = message.location()?.url;
    if (location && new URL(location).origin === appOrigin) errors.push({ url: page.url(), message: message.text(), source: location });
  });
  const findings = [];
  // Source candidates are reported separately; only executed native dialogs fail the browser audit.
  const staticDialogCalls = scanAppNativeDialogCalls();
  if (process.argv.includes('--self-test')) {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.setContent(`<html lang="bn">
      <head><meta name="viewport" content="width=device-width, initial-scale=1"><style>
        :root{--rn-color-danger:#c4320a}
        body{margin:0;font:14px Arial}
        .content-page{padding-bottom:400px}
        .card{border:1px solid #aaa;border-radius:8px;background:white;padding:8px}
        .rn-m-number{color:#111}
        .rn-m-card-avatar{display:inline-block;width:44px;height:44px}
        .test-sticky{position:fixed;bottom:0;left:0;width:100%;height:30px;background:#ddd}
        .test-overlap{position:absolute;top:0;left:0;width:100px;height:40px}
        #overlap-input{position:absolute;top:10px;left:10px;width:120px;height:40px}
        #covered-input{position:fixed;bottom:0;left:4px;width:80px;height:28px}
      </style></head>
      <body class="rn-app"><div class="content-page">
        <h2>শিরোনাম</h2><div class="card"><div class="card">nested</div></div>
        <span>মোট 123</span><span class="rn-m-number">−৳১২</span>
        <span class="rn-m-card-avatar" data-rn-avatar></span><div class="alert">flash outside feedback</div><div data-desktop-only>desktop cart</div>
        <button><i class="las la-plus"></i>যোগ</button><button class="test-overlap">ওভারল্যাপ</button>
        <input id="overlap-input"><input id="covered-input" name="covered">
        <div style="height:900px"></div><p>শেষ কনটেন্ট</p>
        <div class="test-sticky rn-m-sticky-actions">sticky</div>
      </div></body></html>`);
    await page.evaluate(() => {
      window.__rnNativeDialogCalls = [];
      for (const method of ['alert', 'confirm', 'prompt']) {
        const native = window[method];
        window[method] = function (...args) { window.__rnNativeDialogCalls.push({ method }); return Reflect.apply(native, window, args); };
      }
      alert('bad'); confirm('bad'); prompt('bad');
    });
    const result = await inspectPage(page);
    await page.setContent(`<html lang="bn"><head><meta name="viewport" content="width=device-width, initial-scale=1"><style>body{margin:0} body.rn-app .content-page{height:40px;padding-bottom:1000px} .rn-m-tabbar{position:fixed;inset:auto 0 0;height:60px;background:#ddd}</style></head><body class="rn-app"><main class="content-page"><p>ছোট পাতা</p></main><nav class="rn-m-tabbar">ট্যাব</nav></body></html>`);
    const shortResult = await inspectPage(page);
    const catches = { nested: result.nestedCards.length > 0, latinDigits: result.latinDigits.length > 0, negativeMoney: result.negativeMoney.length > 0, emptyAvatar: result.emptyAvatars.length > 0, headingGutter: result.headingGutter.length > 0, nativeDialogs: result.nativeDialogs.length === 3, brokenActionIcons: result.brokenActionIcons.length > 0, overlappingControls: result.overlappingControls.length > 0, stickyCoveredFields: result.stickyCoveredFields.length > 0, bottomSpacing: result.bottomSpacing.length > 0, shortPageScroll: shortResult.shortPageScroll.length > 0, desktopOnlyVisible: result.desktopOnlyVisible.length > 0, unwrappedFlash: result.unwrappedFlash.length > 0 };
    fs.writeFileSync(path.join(out, 'audit-selftest.json'), JSON.stringify({ caught: catches, findings: result, shortPageFindings: shortResult.shortPageScroll }, null, 2));
    await browser.close();
    const missed = Object.entries(catches).filter(([, caught]) => !caught).map(([name]) => name);
    console.log(JSON.stringify({ selfTest: true, caught: catches, missed, report: path.join(out, 'audit-selftest.json') }, null, 2));
    if (missed.length) process.exitCode = 1;
    return;
  }
  if (!process.env.RN_AUDIT_STORAGE_STATE) {
    const login = await page.goto(base + '/login', { waitUntil: 'networkidle' }).catch(() => null);
    if (!login || /login/i.test(new URL(page.url()).pathname)) {
      const email = process.env.RN_AUDIT_EMAIL;
      const password = process.env.RN_AUDIT_PASSWORD;
      if (!email || !password) throw new Error('Set RN_AUDIT_STORAGE_STATE or RN_AUDIT_EMAIL and RN_AUDIT_PASSWORD.');
      await page.locator('form.rn-login-form #userMail,form.rn-login-form input[name=userMail],form.rn-login-form input[type=email],form.rn-login-form input[name=email],form.rn-login-form input[name=mail],form.rn-login-form input[name=username]').first().fill(email);
      await page.locator('form.rn-login-form #password,form.rn-login-form input[type=password]').first().fill(password);
      await Promise.all([page.waitForLoadState('networkidle'), page.locator('form.rn-login-form button[type=submit]').first().click()]);
    }
  } else {
    await page.goto(base + '/dashboard', { waitUntil: 'networkidle' });
  }
  // Complete authentication/navigation before inspecting the account locale or
  // mobile shell; WebKit can resolve the form POST before its redirect settles.
  await page.goto(base + '/dashboard', { waitUntil: 'networkidle' });
  await page.waitForTimeout(500);

  // The server resolves locale from the authenticated admin profile, not Accept-Language.
  // Explicitly switch the isolated audit account to Bengali before capturing the UI baseline.
  if ((await page.locator('html').getAttribute('lang')) !== 'bn') {
    const more = page.locator('[data-rn-tab="more"]');
    if (await more.count()) {
      await more.click();
      await page.waitForFunction(() => {
        const sheet = document.querySelector('[data-rn-more]');
        return sheet && getComputedStyle(sheet).display !== 'none' && sheet.getBoundingClientRect().height > 0;
      }, { timeout: 5000 });
    }
    const bangla = page.locator('[data-rn-language] button[name="preferred_locale"][value="bn"]');
    if (await bangla.count()) {
      await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), bangla.click()]);
      await page.goto(base + '/dashboard', { waitUntil: 'networkidle' });
      await page.waitForTimeout(500);
    }
  }
  // Locale selection can leave the More sheet open. Close it before page checks so
  // drawer headings do not contaminate typography and gutter measurements.
  await page.keyboard.press('Escape').catch(() => {});
  const moreTab = page.locator('[data-rn-tab="more"]');
  if (await moreTab.count() && await moreTab.getAttribute('aria-expanded') === 'true') await moreTab.click();
  const auditLocale = await page.locator('html').getAttribute('lang');
  if (!String(auditLocale).startsWith('bn')) throw new Error(`Mobile audit locale did not switch to Bengali (actual: ${auditLocale}).`);

  // Reachability graph: dashboard tabs/shortcuts/More are the first edge; list
  // FABs and report-hub links are the second. Validate the actual rendered hrefs.
  await page.goto(base + '/dashboard', { waitUntil: 'networkidle' });
  await page.waitForTimeout(500);
  const reachabilityData = await page.locator('[data-rn-reachability]').getAttribute('data-rn-reachability');
  const reachability = reachabilityData ? JSON.parse(reachabilityData) : null;
  const reachabilityFindings = [];
  if (!reachability) {
    reachabilityFindings.push({ reason: 'missing-reachability-map' });
  } else {
    const dashboardLinks = await page.locator('[data-rn-tabbar] a[href], [data-rn-home] a[href]').evaluateAll(links => links.map(link => link.href));
    const moreTrigger = page.locator('[data-rn-tab="more"]');
    if (await moreTrigger.count()) {
      await moreTrigger.click();
      await page.waitForFunction(() => {
        const sheet = document.querySelector('[data-rn-more]');
        return sheet && getComputedStyle(sheet).display !== 'none' && sheet.getBoundingClientRect().height > 0;
      }, { timeout: 5000 }).catch(() => reachabilityFindings.push({ reason: 'more-sheet-did-not-open' }));
    }
    const moreLinks = await page.locator('[data-rn-more] a[href]').evaluateAll(links => links.map(link => link.href));
    const rootLinks = new Set([...dashboardLinks, ...moreLinks]
      .filter(url => !url.startsWith('javascript:') && !url.endsWith('#rn-more-closed')));
    await page.locator('[data-rn-more-close]').click().catch(() => {});
    const reachable = new Set(rootLinks);
    for (const url of reachability.mobileRootLinks) {
      if (!rootLinks.has(url)) reachabilityFindings.push({ url, reason: 'expected-mobile-root-link-not-rendered' });
    }
    const candidates = new Set([...reachability.fabEdges.map(edge => edge.parent), reachability.reportsHub].filter(Boolean));
    for (const url of candidates) {
      if (!rootLinks.has(url)) continue;
      try {
        await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
        await page.waitForTimeout(500);
        const fabHrefs = await page.locator('a.rn-m-fab[href]').evaluateAll(links => links.map(link => link.href));
        fabHrefs.forEach(href => reachable.add(href));
        if (url === reachability.reportsHub) {
          const reportHrefs = await page.locator('main a[href], [data-rn-report-hub] a[href]').evaluateAll(links => links.map(link => link.href));
          reportHrefs.forEach(href => reachable.add(href));
        }
      } catch (error) {
        reachabilityFindings.push({ url, reason: 'route-load-failed', message: String(error.message || error) });
      }
    }
    for (const edge of reachability.fabEdges) {
      if (!rootLinks.has(edge.parent) || !reachable.has(edge.destination)) {
        reachabilityFindings.push({ route: edge.name, reason: 'fab-destination-unreachable-within-3-steps', parent: edge.parent, destination: edge.destination });
      }
    }
    for (const route of reachability.desktopRoutes) {
      if (!reachable.has(route.url)) reachabilityFindings.push({ route: route.name, url: route.url, reason: 'desktop-route-unreachable-within-3-steps' });
    }
  }
  if (reachabilityFindings.length) findings.push({ page: base + '/dashboard', reachability: reachabilityFindings });

  {
    for (let i = 0; i < auditedUrls.length; i++) {
      const url = auditedUrls[i];
      try {
        if (await page.evaluate(() => Boolean(window.__rnNativeDialogCalls))) await page.evaluate(() => { window.__rnNativeDialogCalls.length = 0; });
        activeAuditUrl = url;
        let response = null;
        let navigationFailed = null;
        for (let attempt = 0; attempt < 2; attempt++) {
          routeNetworkFailures = [];
          try {
            response = await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
            await page.waitForLoadState('networkidle', { timeout: 30000 });
            await page.waitForTimeout(500);
            if (routeNetworkFailures.length && attempt === 0) {
              networkFlakes.push({ page: url, attempt: 1, failures: routeNetworkFailures });
              continue;
            }
            if (routeNetworkFailures.length) networkFlakes.push({ page: url, attempt: 2, failures: routeNetworkFailures });
            navigationFailed = null;
            break;
          } catch (error) {
            const message = String(error.message || error);
            if (/ERR_|net::|ECONNRESET|ETIMEDOUT|timeout/i.test(message)) {
              networkFlakes.push({ page: url, attempt: attempt + 1, error: message });
              navigationFailed = error;
              if (attempt === 0) continue;
              break;
            }
            throw error;
          }
        }
        if (navigationFailed) {
          fs.writeFileSync(path.join(out, 'audit.json'), JSON.stringify({ gate: 'full', locale: auditLocale,
            viewport: { width: 390, height: 844 }, routes: auditedUrls.length, findings, consoleErrors: errors,
            networkFlakes, sourceDialogCandidates: staticDialogCalls.length }, null, 2));
          continue;
        }
        if (response && response.status() >= 400) findings.push({ page: url, check: 'http-status', actual: response.status() });
        if (/\/admin\/login|\/login$/.test(new URL(page.url()).pathname)) {
          findings.push({ page: url, check: 'authenticated-session', actual: 'redirected to login' });
          continue;
        }
        const result = await inspectPage(page);
        const slug = String(i+1).padStart(3, '0') + '-' + new URL(url).pathname.replace(/[^a-z0-9]+/gi,'-').replace(/^-|-$/g,'').slice(0,50);
        await page.screenshot({ path: path.join(out, slug + '.png'), fullPage: false });
        if (scrollEndRoutes.some(route => new URL(url).pathname.includes(route))) {
          await page.evaluate(() => { document.scrollingElement.scrollTop = document.scrollingElement.scrollHeight; return new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))); });
          fs.mkdirSync(path.join(out, 'scroll-end'), { recursive: true });
          await page.screenshot({ path: path.join(out, 'scroll-end', slug + '.png'), fullPage: false });
          await page.evaluate(() => window.scrollTo(0, 0));
        }
        for (const check of ['badFont','badSize','nestedCards','latinDigits','negativeMoney','emptyAvatars','headingGutter','nativeDialogs','brokenActionIcons','overlappingControls','stickyCoveredFields','bottomSpacing','shortPageScroll','desktopOnlyVisible','unwrappedFlash']) if (result[check].length) findings.push({ page: url, check, details: result[check] });
        if (result.wrappedNumbers.length) findings.push({ page: url, check: 'wrapped-numbers', details: result.wrappedNumbers });
        if (result.overflow) findings.push({ page: url, check: 'horizontal-overflow' });
        if (result.isDocument) {
          await page.emulateMedia({ media: 'print' });
          const printSmall = await page.evaluate(() => [...document.querySelectorAll('body *')].filter(el => el.children.length === 0 && el.textContent.trim()).filter(el => {
            const style = getComputedStyle(el), rect = el.getBoundingClientRect();
            return rect.width > 0 && rect.height > 0 && style.display !== 'none' && parseFloat(style.fontSize) < 9.34;
          }).slice(0, 20).map(el => ({ text: el.textContent.trim().slice(0, 70), sizePx: getComputedStyle(el).fontSize })));
          if (printSmall.length) findings.push({ page: url, check: 'document-print-font-below-7pt', details: printSmall });
          await page.emulateMedia({ media: 'screen' });
        }
      } catch (error) { findings.push({ page: url, check: 'navigation', actual: error.message }); }
    }
    fs.writeFileSync(path.join(out, 'audit.json'), JSON.stringify({ gate: 'full', engine: engineName, safeBottom: '34px', locale: auditLocale, viewport: { width: 390, height: 844 }, routes: auditedUrls.length, findings, consoleErrors: errors, networkFlakes, sourceDialogCandidates: staticDialogCalls.length, documentIds }, null, 2));
  }
  await browser.close();
  console.log(JSON.stringify({ gate: fullGate ? 'full' : 'foundation', routes: fullGate ? auditedUrls.length : undefined, violations: findings.length, consoleErrors: errors.length, networkFlakes: networkFlakes.length, report: path.join(out,'audit.json') }, null, 2));
  if (findings.length || errors.length) process.exitCode = 1;
}
main().catch(error => { console.error(error.stack || error); process.exit(1); });
