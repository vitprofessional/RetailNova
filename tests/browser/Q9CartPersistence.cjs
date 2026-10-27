const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

const root = path.resolve(__dirname, '../..');
const script = path.join(root, 'public/js/rn-product-picker.js');

(async () => {
  const browser = await chromium.launch({ headless: true, executablePath: process.env.CHROME_PATH });
  try {
    const context = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, serviceWorkers: 'block',
      userAgent: 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 Chrome/128.0.0.0 Mobile Safari/537.36' });
    const page = await context.newPage();
    page.on('console', message => console.log('browser:', message.text()));
    page.on('pageerror', error => console.log('pageerror:', error.message));
    await page.addInitScript(() => {
      const nativeFetch = window.fetch.bind(window);
      window.fetch = (input, init) => String(input).includes('/picker-products')
        ? Promise.resolve(new Response(JSON.stringify({ items: [{ product_id: 41, name: 'চাল', stock: 5, sale_price: 100, buy_price: 80, brand: '', unit: 'কেজি' }], categories: [], brands: [], has_more: false, page: 1 }), { status: 200, headers: { 'Content-Type': 'application/json' } }))
        : nativeFetch(input, init);
    });
    await page.route('http://localhost/q9-cart', route => route.fulfill({ contentType: 'text/html', body: `<html lang="bn"><body><div data-rn-product-picker data-mode="sale" data-cart-key="rn-cart-sale-test" data-url="http://localhost/picker-products" data-labelAllCategory="সব" data-labelAllBrand="সব" data-currency="৳" data-labelStock="স্টক __COUNT__" data-labelNoPrice="দাম নেই" data-labelOutOfStock="স্টক শেষ"><input data-picker-search><div data-picker-chips></div><div data-picker-results></div><p data-picker-empty hidden></p><button data-picker-more hidden></button><p data-picker-error hidden></p><div data-picker-sheet hidden></div></div></body></html>` }));
    await page.goto('http://localhost/q9-cart');
    await page.evaluate(() => localStorage.setItem('rn-cart-sale-test', JSON.stringify({ counts: {}, lines: [{ product_id: '41', quantity: 2, sale_price: '100' }], customer_id: '1' })));
    await page.addScriptTag({ path: script });
    await page.waitForFunction(() => document.querySelector('.rn-m-picker-card') || !document.querySelector('[data-picker-error]')?.hidden);
    assert.ok(await page.locator('.rn-m-picker-card').count(), await page.locator('[data-picker-error]').innerText());
    await page.locator('.rn-m-picker-card').click();
    const stored = await page.evaluate(() => JSON.parse(localStorage.getItem('rn-cart-sale-test')));
    assert.equal(stored.counts['41'], 1, 'picker badge count persisted in shared cart state');
    assert.equal(stored.lines.length, 1, 'picker update preserved checkout line state');
    await page.reload();
    await page.addScriptTag({ path: script });
    await page.waitForFunction(() => document.querySelector('.rn-m-picker-card') || !document.querySelector('[data-picker-error]')?.hidden);
    assert.ok(await page.locator('.rn-m-picker-card').count(), await page.locator('[data-picker-error]').innerText());
    assert.equal(await page.locator('[data-picker-count="41"]').innerText(), '১', 'badge restored after reload');
    const afterReload = await page.evaluate(() => JSON.parse(localStorage.getItem('rn-cart-sale-test')));
    assert.equal(afterReload.lines[0].quantity, 2, 'cart line survived reload');
    console.log(JSON.stringify({ passed: 5, viewport: '390x844', mobileUA: true, serviceWorker: 'blocked', cartKey: 'rn-cart-sale-test' }));
    await context.close();
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
