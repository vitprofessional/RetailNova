#!/usr/bin/env node
// Isolated mobile e2e for picker -> persisted cart -> checkout -> stock error -> sale receipt.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

const base = (process.env.RN_Q9_BASE_URL || '').replace(/\/$/, '');
const email = process.env.RN_Q9_EMAIL;
const password = process.env.RN_Q9_PASSWORD;
const output = path.resolve(__dirname, '../../tests-output/q9');
const fixtureDb = process.env.RN_Q9_FIXTURE_DB;
if (!base || !email || !password) throw new Error('Set RN_Q9_BASE_URL, RN_Q9_EMAIL and RN_Q9_PASSWORD for the isolated browser fixture.');
if (!fixtureDb) throw new Error('Set RN_Q9_FIXTURE_DB to the disposable SQLite browser fixture.');

function setFixtureStock(quantity) {
  execFileSync(process.env.PHP_EXECUTABLE || 'C:\\xampp\\php\\php.exe', [path.join(__dirname, 'Q9FixtureStock.php'), fixtureDb, String(quantity)], { stdio: 'inherit' });
}

(async () => {
  fs.mkdirSync(output, { recursive: true });
  const browser = await chromium.launch({ headless: true, executablePath: process.env.BROWSER_EXECUTABLE });
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true,
    userAgent: 'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 Chrome/130.0.0.0 Mobile Safari/537.36', serviceWorkers: 'block' });
  const page = await context.newPage();
  const consoleErrors = [];
  let externalFontKit403s = 0;
  page.on('pageerror', error => consoleErrors.push(error.message));
  page.on('console', message => {
    if (message.type() !== 'error') return;
    if (message.text().includes('Failed to load resource: the server responded with a status of 403')) {
      externalFontKit403s += 1;
      return;
    }
    consoleErrors.push(message.text());
  });
  try {
    await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
    await page.locator('#userMail').fill(email);
    await page.locator('#password').fill(password);
    await Promise.all([page.waitForURL(url => !/\/(?:login|admin\/login)$/.test(url.pathname), { waitUntil: 'networkidle' }), page.locator('.rn-sign-in-button').click()]);
    await page.goto(`${base}/new/sale`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    const card = page.locator('.rn-m-picker-card:not([disabled])').first();
    await card.waitFor({ state: 'visible' });
    const productName = (await card.locator('.rn-m-picker-card__name').innerText()).trim();
    const productId = await card.evaluate(node => node.querySelector('[data-picker-count]')?.dataset.pickerCount);
    await card.click();
    await page.waitForFunction(id => {
      const badge = document.querySelector(`[data-picker-count="${id}"]`);
      return badge && !badge.hidden && Boolean(badge.textContent.trim());
    }, productId);
    await page.locator('tr[data-product-summary]').first().waitFor({ state: 'attached' });
    await page.screenshot({ path: path.join(output, 'picker-badge.png'), fullPage: true });

    await page.reload({ waitUntil: 'networkidle' });
    await page.locator('tr[data-product-summary]').first().waitFor({ state: 'attached', timeout: 15000 });
    await page.waitForFunction(() => !document.querySelector('[data-rn-pos-checkout]')?.disabled);
    const cartKey = await page.locator('[data-rn-product-picker]').getAttribute('data-cart-key');
    const cartAfterReload = await page.evaluate(key => JSON.parse(localStorage.getItem(key) || '{}'), cartKey);
    assert.equal(cartAfterReload.lines?.length, 1, 'cart line should survive reload');
    await page.screenshot({ path: path.join(output, 'cart-after-reload.png'), fullPage: true });

    const checkout = page.locator('[data-rn-pos-checkout]');
    await checkout.click();
    await page.locator('[data-rn-checkout]').waitFor({ state: 'visible' });
    await page.screenshot({ path: path.join(output, 'checkout.png'), fullPage: true });

    // Simulate concurrent depletion after selection. The server must reject the
    // checkout and retain the cart, even though it was valid when the item was picked.
    setFixtureStock(0);
    await page.locator('[data-rn-checkout-complete]').click();
    await page.waitForLoadState('networkidle');
    const errorBanner = page.locator('[data-rn-error-banner]:not([hidden])');
    await errorBanner.waitFor({ state: 'visible', timeout: 15000 });
    const errorText = (await errorBanner.innerText()).trim();
    await page.screenshot({ path: path.join(output, 'error-banner.png'), fullPage: true });
    const failedCart = await page.evaluate(key => JSON.parse(localStorage.getItem(key) || '{}'), cartKey);
    assert.equal(failedCart.lines?.length, 1, 'cart remains after stock validation failure');

    setFixtureStock(10);
    await page.reload({ waitUntil: 'networkidle' });
    await page.locator('tr[data-product-summary]').first().waitFor({ state: 'attached', timeout: 15000 });
    await page.locator('[data-rn-pos-checkout]').click();
    await page.locator('[data-rn-checkout]').waitFor({ state: 'visible' });
    await page.locator('[data-rn-quick="exact"]').click();
    const invoiceNavigation = page.waitForURL(url => /\/(?:generate\/invoice\/|documents\/sale-invoice\/)/.test(url.pathname), { timeout: 20000 });
    await page.locator('[data-rn-checkout-complete]').click();
    await invoiceNavigation;
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    const success = page.locator('[data-rn-toast]:not([hidden])');
    const successText = (await success.innerText().catch(() => '')).trim();
    await page.screenshot({ path: path.join(output, 'invoice.png'), fullPage: true });
    if (successText) await page.screenshot({ path: path.join(output, 'success-toast.png'), fullPage: true });
    assert.ok(successText.includes('বিক্রি সম্পন্ন — চালান SAL'), `expected invoice-specific success message, got: ${successText}`);
    await page.goto(`${base}/add/purchase`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    await page.screenshot({ path: path.join(output, 'purchase.png'), fullPage: true });
    assert.deepEqual(consoleErrors, [], 'browser should not report application console errors');
    console.log(JSON.stringify({ productName, productId, cartAfterReload: cartAfterReload.lines.length, errorText,
      successText, screenshots: output, applicationConsoleErrors: consoleErrors, externalFontKit403s }, null, 2));
  } finally {
    await context.close();
    await browser.close();
  }
})().catch(error => { console.error(error.stack || error); process.exit(1); });
