const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium, devices } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

(async () => {
  const base = (process.env.RN_AUDIT_BASE_URL || 'http://127.0.0.1:8123').replace(/\/$/, '');
  const browser = await chromium.launch({ headless: true, executablePath: process.env.BROWSER_EXECUTABLE || undefined });
  const context = await browser.newContext({ ...devices['Pixel 7'], viewport: { width: 390, height: 844 },
    userAgent: 'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 Chrome/130.0.0.0 Mobile Safari/537.36', serviceWorkers: 'block' });
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto(base + '/login', { waitUntil: 'networkidle' });
  await page.locator('#userMail').fill(process.env.RN_AUDIT_EMAIL || 'q8-audit@example.test');
  await page.locator('#password').fill(process.env.RN_AUDIT_PASSWORD || 'Q8-Audit-Only-Password-9281');
  await Promise.all([page.waitForLoadState('networkidle'), page.locator('.rn-sign-in-button').click()]);
  const output = path.resolve(__dirname, '../../tests-output/q9/thermal');
  fs.mkdirSync(output, { recursive: true });
  const result = {};
  for (const paper of ['thermal80', 'thermal58']) {
    await page.goto(`${base}/documents/sale-invoice/1?size=${paper}`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    await page.emulateMedia({ media: 'print' });
    const sizes = await page.evaluate(() => {
      const px = selector => parseFloat(getComputedStyle(document.querySelector(selector)).fontSize);
      return {
        name: px('.rn-print-document__items td:first-child > strong'),
        detail: px('.rn-print-document__thermal-detail'),
        label: px('.rn-print-document__totals div:not(.is-total) dt'),
        money: px('.rn-print-document__totals div:not(.is-total) dd'),
        total: px('.rn-print-document__totals .is-total dd'),
        footer: px('.rn-print-document__footer'),
        thermalRow: getComputedStyle(document.querySelector('.rn-print-document__thermal-detail')).display,
        paper: document.body.dataset.rnPaper,
      };
    });
    const expected = paper === 'thermal80'
      ? { name:12, detail:10.6667, label:12, money:12, total:14.6667, footer:10.6667 }
      : { name:10.6667, detail:9.3333, label:10.6667, money:10.6667, total:13.3333, footer:9.3333 };
    for (const key of Object.keys(expected)) assert.ok(Math.abs(sizes[key] - expected[key]) < 0.1, `${paper} ${key}: expected ${expected[key]}px, got ${sizes[key]}px`);
    assert.equal(sizes.thermalRow, 'block');
    assert.equal(sizes.paper, paper);
    await page.emulateMedia({ media: 'screen' });
    await page.screenshot({ path: path.join(output, `${paper}.png`), fullPage: true });
    await page.emulateMedia({ media: 'print' });
    await page.screenshot({ path: path.join(output, `${paper}-print.png`), fullPage: true });
    await page.pdf({ path: path.join(output, `${paper}.pdf`), printBackground: true, preferCSSPageSize: true });
    result[paper] = sizes;
    await page.emulateMedia({ media: 'screen' });
  }
  assert.deepEqual(errors, []);
  console.log(JSON.stringify({ papers: result, errors, pdfDirectory: output }, null, 2));
  await browser.close();
})().catch(error => { console.error(error); process.exit(1); });
