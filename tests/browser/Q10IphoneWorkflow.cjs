#!/usr/bin/env node
// Authenticated iPhone 14 Pro Max sale and purchase flows against a disposable local DB.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { webkit, chromium, devices } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

const base = (process.env.RN_Q10_BASE_URL || '').replace(/\/$/, '');
const email = process.env.RN_Q10_EMAIL;
const password = process.env.RN_Q10_PASSWORD;
const engine = process.env.RN_BROWSER === 'chromium' ? 'chromium-android' : 'webkit-iphone';
const output = path.resolve(__dirname, `../../tests-output/q11/${engine}`);
const shares = [];
if (!base || !email || !password) throw new Error('Set RN_Q10_BASE_URL, RN_Q10_EMAIL and RN_Q10_PASSWORD for the isolated browser fixture.');

function assertShare(record, expectedType, expectedSuffix) {
  assert.ok(record, 'navigator.share should be called');
  assert.equal(record.sameTask, true, 'share must be called before the originating user-gesture task yields');
  assert.equal(record.userActivation, true, 'iPhone share must retain live browser user activation');
  assert.equal(record.type, expectedType);
  assert.ok(record.name.endsWith(expectedSuffix), `unexpected share filename: ${record.name}`);
}

(async () => {
  fs.mkdirSync(output, { recursive: true });
  const browserType = process.env.RN_BROWSER === 'chromium' ? chromium : webkit;
  const browser = await browserType.launch({ headless: true });
  const device = process.env.RN_BROWSER === 'chromium' ? (devices['Pixel 7'] || devices['Pixel 5']) : devices['iPhone 14 Pro Max'];
  const context = await browser.newContext({ ...device, locale: 'bn-BD', serviceWorkers: 'block' });
  await context.addInitScript(() => {
    window.__rnShareCalls = [];
    window.__isUserGestureTask = false;
    document.addEventListener('click', () => {
      window.__isUserGestureTask = true;
      setTimeout(() => { window.__isUserGestureTask = false; }, 0);
    }, true);
    Object.defineProperty(navigator, 'canShare', { configurable: true, value: payload => Boolean(payload?.files?.length) });
    Object.defineProperty(navigator, 'share', { configurable: true, value: payload => {
      const file = payload.files[0];
      window.__rnSharedFile = file;
      window.__rnShareCalls.push({ sameTask: window.__isUserGestureTask,
        userActivation: navigator.userActivation?.isActive === true,
        type: file.type, name: file.name, title: payload.title });
      return Promise.resolve();
    }});
  });
  const page = await context.newPage();
  const pageErrors = [];
  const consoleErrors = [];
  page.on('pageerror', error => pageErrors.push(error.message));
  page.on('console', message => {
    if (message.type() === 'error' && !/Font Awesome|403/.test(message.text())) consoleErrors.push(message.text());
  });
  try {
    await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
    await page.locator('#userMail').fill(email);
    await page.locator('#password').fill(password);
    await Promise.all([
      page.waitForURL(url => !/\/(?:login|admin\/login)$/.test(url.pathname), { waitUntil: 'networkidle' }),
      page.locator('.rn-sign-in-button').click(),
    ]);

    // Sale: add, assert toast, save checkout, open receipt and share a PDF.
    await page.goto(`${base}/new/sale`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    // Sale is customer-first: only products returned for the selected customer
    // exist in the legacy sale selector. Select walk-in before choosing a card.
    const walkInButton = page.locator('#useWalkingCustomerBtn');
    if (await walkInButton.count()) await page.evaluate(() => document.getElementById('useWalkingCustomerBtn').click());
    await page.waitForFunction(() => {
      const select = document.querySelector('#productName');
      return select && !select.disabled && [...select.options].some(option => option.value);
    });
    const availableSaleIds = await page.locator('#productName option').evaluateAll(options => options.map(option => option.value).filter(Boolean));
    const saleCard = page.locator(`.rn-m-picker-card:not([disabled]):not(.is-unavailable):has([data-picker-count="${availableSaleIds[0]}"])`).first();
    await saleCard.waitFor({ state: 'visible' });
    await saleCard.click();
    const saleToast = page.locator('[data-rn-toast-message]');
    await saleToast.waitFor({ state: 'visible' });
    const saleToastText = (await saleToast.innerText()).trim();
    assert.match(saleToastText, /যোগ হয়েছে/);
    await page.screenshot({ path: path.join(output, 'sale-toast.png'), fullPage: true });
    await page.locator('tr[data-product-summary]').first().waitFor({ state: 'attached' });
    await page.locator('[data-rn-pos-checkout]').click();
    await page.locator('[data-rn-checkout]').waitFor({ state: 'visible' });
    await page.locator('[data-rn-quick="exact"]').click();
    const saleInvoice = page.waitForURL(url => /\/(?:generate\/invoice\/|documents\/sale-invoice\/)/.test(url.pathname), { timeout: 30000 });
    await page.locator('[data-rn-checkout-complete]').click();
    await saleInvoice;
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    await page.screenshot({ path: path.join(output, 'sale-invoice.png'), fullPage: true });
    const shareOpen = page.locator('[data-receipt-action="share"]');
    await shareOpen.click();
    const shareButton = page.locator('[data-document-share]');
    await shareButton.waitFor({ state: 'visible' });
    await page.screenshot({ path: path.join(output, 'sale-share-preparing.png'), fullPage: true });
    try {
      await page.waitForFunction(() => !document.querySelector('[data-document-share]').disabled, { timeout: 30000 });
    } catch (error) { throw new Error(`sale share file preparation did not finish: ${JSON.stringify({ consoleErrors, pageErrors, message: error.message })}`); }
    await page.screenshot({ path: path.join(output, 'sale-share-ready.png'), fullPage: true });
    await shareButton.click();
    const saleShareRecord = (await page.evaluate(() => window.__rnShareCalls)).at(-1);
    assertShare(saleShareRecord, 'application/pdf', '.pdf');
    shares.push({ flow: 'sale', ...saleShareRecord });

    // Purchase: supplier, add product, save purchase, open invoice and share a PNG.
    await page.goto(`${base}/add/purchase`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    await page.locator('[data-q6-open-supplier]').click();
    await page.locator('[data-q6-supplier]').first().click();
    await page.locator('[data-q6-open-picker]').click();
    const purchaseCard = page.locator('.rn-q6-product-sheet .rn-m-picker-card:not([disabled])').first();
    await purchaseCard.waitFor({ state: 'visible' });
    await purchaseCard.click();
    const purchaseToast = page.locator('[data-rn-toast-message]');
    await purchaseToast.waitFor({ state: 'visible' });
    const purchaseToastText = (await purchaseToast.innerText()).trim();
    assert.match(purchaseToastText, /যোগ হয়েছে/);
    await page.screenshot({ path: path.join(output, 'purchase-toast.png'), fullPage: true });
    await page.locator('[data-q6-picker-done]').click();
    await page.locator('[data-q6-open-payment]').click();
    await page.locator('[data-q6-method="cash"]').click();
    await page.locator('[data-q6-paid-fraction="1"]').click();
    const purchaseInvoice = page.waitForURL(url => /\/purchase\/invoices\/\d+/.test(url.pathname), { timeout: 30000 });
    await page.locator('[data-q6-submit]').click();
    await purchaseInvoice;
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    await page.screenshot({ path: path.join(output, 'purchase-invoice.png'), fullPage: true });
    await page.locator('[data-receipt-action="share"]').click();
    const purchaseShare = page.locator('[data-document-share]');
    await purchaseShare.waitFor({ state: 'visible' });
    await page.locator('input[name="share-format"][value="png"]').check();
    await page.waitForFunction(() => !document.querySelector('[data-document-share]').disabled);
    await page.screenshot({ path: path.join(output, 'purchase-share-ready.png'), fullPage: true });
    await purchaseShare.click();
    const purchaseShareRecord = (await page.evaluate(() => window.__rnShareCalls)).at(-1);
    assertShare(purchaseShareRecord, 'image/png', '.png');
    const pngWidth = await page.evaluate(async () => (await createImageBitmap(window.__rnSharedFile)).width);
    assert.ok(pngWidth >= 900, `thermal 80mm PNG must be at least 900px wide; got ${pngWidth}px`);
    shares.push({ flow: 'purchase', ...purchaseShareRecord });

    assert.deepEqual([...pageErrors, ...consoleErrors], [], `WebKit app errors: ${[...pageErrors, ...consoleErrors].join('; ')}`);
    console.log(JSON.stringify({ browser: engine, device: process.env.RN_BROWSER === 'chromium' ? 'Pixel 7' : 'iPhone 14 Pro Max', saleToast: saleToastText,
      purchaseToast: purchaseToastText, pngWidth, shares, screenshots: output,
      applicationErrors: pageErrors }, null, 2));
  } finally {
    await context.close();
    await browser.close();
  }
})().catch(error => { console.error(error.stack || error); process.exit(1); });
