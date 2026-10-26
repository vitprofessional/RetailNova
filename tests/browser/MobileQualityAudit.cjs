#!/usr/bin/env node
/* Mobile quality gate. Requires RN_AUDIT_BASE_URL and authenticated credentials (or storage state). */
const fs = require('node:fs');
const path = require('node:path');

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
const urls = [...new Set(routes.map(route => new URL(route.replace(/^\//, ''), base + '/').toString()))];
const onlyRoutes = (process.env.RN_AUDIT_ONLY || '').split(',').map(value => value.trim()).filter(Boolean);
const auditedUrls = onlyRoutes.length ? urls.filter(url => onlyRoutes.some(route => new URL(url).pathname.includes(route))) : urls;

async function main() {
  fs.mkdirSync(out, { recursive: true });
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
  const { chromium, devices } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
  const browser = await chromium.launch({ headless: true, executablePath: process.env.BROWSER_EXECUTABLE || process.env.CHROME_PATH || undefined });
  const context = await browser.newContext({ ...devices['Pixel 7'], viewport: { width: 390, height: 844 }, locale: 'bn-BD',
    userAgent: 'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36',
    serviceWorkers: 'block', storageState: process.env.RN_AUDIT_STORAGE_STATE || undefined });
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  // The app's documented Font Awesome kit may return 403 from its external host.
  // Only fail on browser errors from this application origin; pageerror still catches JS faults.
  const appOrigin = new URL(base).origin;
  page.on('console', message => {
    if (message.type() !== 'error') return;
    const location = message.location()?.url;
    if (location && new URL(location).origin === appOrigin) errors.push(message.text());
  });
  const findings = [];
  if (!process.env.RN_AUDIT_STORAGE_STATE) {
    const login = await page.goto(base + '/login', { waitUntil: 'networkidle' }).catch(() => null);
    if (!login || /login/i.test(new URL(page.url()).pathname)) {
      const email = process.env.RN_AUDIT_EMAIL;
      const password = process.env.RN_AUDIT_PASSWORD;
      if (!email || !password) throw new Error('Set RN_AUDIT_STORAGE_STATE or RN_AUDIT_EMAIL and RN_AUDIT_PASSWORD.');
      await page.locator('#userMail,input[name=userMail],input[type=email],input[name=email],input[name=mail],input[name=username]').first().fill(email);
      await page.locator('#password,input[type=password]').first().fill(password);
      await Promise.all([page.waitForLoadState('networkidle'), page.locator('.rn-login-form button[type=submit],.rn-sign-in-button').first().click()]);
    }
  } else {
    await page.goto(base + '/dashboard', { waitUntil: 'networkidle' });
  }

  // The server resolves locale from the authenticated admin profile, not Accept-Language.
  // Explicitly switch the isolated audit account to Bengali before capturing the UI baseline.
  if ((await page.locator('html').getAttribute('lang')) !== 'bn') {
    const more = page.locator('[data-rn-tab="more"]');
    if (await more.count()) await more.click();
    const bangla = page.locator('[data-rn-language] button[name="preferred_locale"][value="bn"]');
    if (await bangla.count()) {
      await Promise.all([page.waitForLoadState('networkidle'), bangla.click()]);
    }
  }
  const auditLocale = await page.locator('html').getAttribute('lang');
  if (!String(auditLocale).startsWith('bn')) throw new Error(`Mobile audit locale did not switch to Bengali (actual: ${auditLocale}).`);

  {
    for (let i = 0; i < auditedUrls.length; i++) {
      const url = auditedUrls[i];
      try {
        await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
        await page.waitForTimeout(500);
        if (/\/admin\/login|\/login$/.test(new URL(page.url()).pathname)) {
          findings.push({ page: url, check: 'authenticated-session', actual: 'redirected to login' });
          continue;
        }
        const result = await page.evaluate(() => {
          const visible = element => { const r = element.getBoundingClientRect(), s = getComputedStyle(element); return r.width > 0 && r.height > 0 && s.visibility !== 'hidden' && s.display !== 'none'; };
          const textNodes = [...document.querySelectorAll('body *')].filter(el => el.children.length === 0 && el.textContent.trim() && visible(el));
          const allowed = [13,14,16,17,18,21,30];
          const isIcon = el => el.matches('i,svg,path,[class^="ri-"],[class*=" ri-"],[class^="fa-"],[class*=" fa-"]')
            || /fontawesome|remixicon|line awesome|material icons|bootstrap-icons/i.test(getComputedStyle(el).fontFamily);
          const badFont = textNodes.filter(el => !isIcon(el) && !/^"?Hind Siliguri"?/i.test(getComputedStyle(el).fontFamily.trim())).slice(0, 12).map(el => ({ text: el.textContent.trim().slice(0,40), font: getComputedStyle(el).fontFamily }));
          const badSize = textNodes.filter(el => { const size = parseFloat(getComputedStyle(el).fontSize); return size < 13 || !allowed.some(value => Math.abs(value-size)<0.1); }).slice(0, 20).map(el => ({ text: el.textContent.trim().slice(0,40), size: getComputedStyle(el).fontSize }));
          const overflow = document.documentElement.scrollWidth > 390;
          // Exact component selectors avoid matching titles/tags such as rn-m-card-title.
          const containers = [...document.querySelectorAll('.rn-m-page, .rn-m-card, .rn-m-section, .rn-m-form-section, .rn-m-summary')].filter(visible);
          const nested = [...document.querySelectorAll('.rn-m-card, .rn-m-section, .rn-m-form-section')]
            .some(card => card.querySelector('.rn-m-card, .rn-m-section, .rn-m-form-section'));
          // A page wrapper and full-width headers/sheets are intentional. Content cards/sections
          // must align to the wrapper gutter (16px), rather than add their own side offset.
          const gutters = containers.filter(el => !el.matches('.rn-m-page') && !el.closest('.rn-m-list-header,.rn-m-sheet,.rn-m-header'))
            .map(el => Math.round(el.getBoundingClientRect().left))
            .filter(left => left !== 16 && left !== 0);
          const englishDigits = document.documentElement.lang.startsWith('bn') ? textNodes.filter(el => {
            if (el.matches('input,textarea')) return false;
            const text = el.textContent.trim();
            if (!/[0-9]/.test(text)) return false;
            const englishMonthDate = /\b(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)\s+\d{1,2}(?:,)?\s+\d{4}\b|\b\d{1,2}\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\s+\d{4}\b/i.test(text);
            if (englishMonthDate) return true;
            // Business identifiers and names retain their entered characters; this gate targets
            // formatted numeric UI, not invoice/barcode/SKU/phone values or names containing digits.
            if (/[A-Za-z]/.test(text) || /^[+\d()\-\s]{7,}$/.test(text)) return false;
            if (/^\d{1,3}$/.test(text) && el.closest('tr') && el.closest('td:first-child')) return false;
            return true;
          }).slice(0, 15).map(el => ({ text: el.textContent.trim().slice(0,60), tag: el.tagName, className: typeof el.className === 'string' ? el.className : '', html: el.outerHTML.slice(0,240) })) : [];
          const wrappedNumbers = [...document.querySelectorAll('.rn-m-number,[data-rn-component]')].filter(visible).filter(el => el.getBoundingClientRect().height > parseFloat(getComputedStyle(el).fontSize) * 1.7).map(el => el.textContent.trim().slice(0,40));
          return { badFont, badSize, overflow, nested, gutters: [...new Set(gutters)], englishDigits, wrappedNumbers };
        });
        const slug = String(i+1).padStart(3, '0') + '-' + new URL(url).pathname.replace(/[^a-z0-9]+/gi,'-').replace(/^-|-$/g,'').slice(0,50);
        await page.screenshot({ path: path.join(out, slug + '.png'), fullPage: false });
        for (const check of ['badFont','badSize','gutters','englishDigits']) if (result[check].length) findings.push({ page: url, check, details: result[check] });
        if (result.wrappedNumbers.length) findings.push({ page: url, check: 'wrapped-numbers', details: result.wrappedNumbers });
        if (result.overflow) findings.push({ page: url, check: 'horizontal-overflow' });
        if (result.nested) findings.push({ page: url, check: 'nested-cards' });
      } catch (error) { findings.push({ page: url, check: 'navigation', actual: error.message }); }
    }
    fs.writeFileSync(path.join(out, 'audit.json'), JSON.stringify({ gate: 'full', locale: auditLocale, viewport: { width: 390, height: 844 }, routes: auditedUrls.length, findings, consoleErrors: errors }, null, 2));
  }
  await browser.close();
  console.log(JSON.stringify({ gate: fullGate ? 'full' : 'foundation', routes: fullGate ? auditedUrls.length : undefined, violations: findings.length, consoleErrors: errors.length, report: path.join(out,'audit.json') }, null, 2));
  if (findings.length || errors.length) process.exitCode = 1;
}
main().catch(error => { console.error(error.stack || error); process.exit(1); });
