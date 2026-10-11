// Offline UI regression check: real sales CSS, row JavaScript and serial modal,
// with fixture HTTP responses. Never contacts Laravel or a production database.
// Run with node; set PLAYWRIGHT_MODULE and BROWSER_EXECUTABLE if using bundled tools.
const fs = require('node:fs');
const path = require('node:path');
const nativeAssert = require('node:assert/strict');
let assertionCount = 0;
const assert = new Proxy(nativeAssert, {
    apply(target, receiver, args) { assertionCount++; return Reflect.apply(target, receiver, args); },
    get(target, key) {
        const value = Reflect.get(target, key);
        return typeof value === 'function' ? (...args) => { assertionCount++; return Reflect.apply(value, target, args); } : value;
    },
});
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = path.resolve(__dirname, '..');
const {translateSource, bridge, catalog} = require('./translation-fixtures.cjs');
const locale = process.env.RN_TEST_LOCALE || 'en';
const read = p => translateSource(fs.readFileSync(path.join(root, p), 'utf8'), locale);
const sales = read('resources/views/sale/newsale.blade.php');
const edit = read('resources/views/sale/editSaleItems.blade.php');
const mobile = read('resources/views/sale/mobile-styles.blade.php');
const styles = [...sales.matchAll(/<style>([\s\S]*?)<\/style>/g)].map(m => m[1]).join('\n');
const css = ['backend-plugin.min.css', 'backende209.css', 'rn-custom.css', 'rn-table-pro.css']
    .map(p => read('public/eshop/assets/css/' + p)).join('\n');
const table = sales.match(/<table class="[^"\n]*rn-sale-mobile-table">[\s\S]*?<\/table>/)[0];
const modal = sales.slice(sales.indexOf('<div class="modal fade" id="saleSerialModal"'));
// Take exactly the serial modal, excluding subsequent Blade/customer markup.
const serialModal = modal.split('<!--')[0].trim();
const script = read('resources/views/scripts/sale-scripts.blade.php');

function fixture(serialized) {
    return `<!doctype html><html lang="${locale}"><head><meta charset="utf-8">${bridge(locale)}<meta name="viewport" content="width=device-width,initial-scale=1">
    <style>${css}\n${styles}</style>${mobile}</head><body>
    <form id="saveSaleForm" data-action-template="/fixture/save">
    <div id="rn-route-seeds" data-newsale="http://sales.test/new/sale"
      data-purchase-by-id-template="/fixture/purchase/__ID__"
      data-purchase-serials-template="/fixture/serials/__ID__"></div>
    <select class="js-sale-product-select"><option value=""></option><option value="purchase_1">Product</option></select>
    <div class="table-responsive rn-table-wrap">${table}</div>
    <div id="saleErrorSummary"></div>
    ${['discountAmount', 'additionalChargeAmount', 'paidAmount', 'totalSaleAmount', 'grandTotal', 'dueAmount', 'prevDue', 'curDue'].map(id => `<input id="${id}" name="${id}" value="0">`).join('')}
    <button type="submit">Save</button></form>${serialModal}
    <script>window.showToast=function(){};</script><script>${script}</script></body></html>`;
}

async function visible(page, selector) {
    assert(await page.locator(selector).isVisible(), `Hidden control: ${selector}`);
}

(async () => {
    const browser = await chromium.launch({ headless: true,
        ...(process.env.BROWSER_EXECUTABLE ? { executablePath: process.env.BROWSER_EXECUTABLE } : {}) });
    let passed = 0;
    try {
        for (const width of [375, 576, 768, 992, 1280]) {
            for (const [serialized, required] of [[true,true], [true,false], [false,true], [false,false]]) {
                let currentRequired = required;
                const page = await browser.newPage({ viewport: { width, height: 900 } });
                const errors = [];
                page.on('pageerror', e => errors.push(e.message));
                await page.route('**/*', async route => {
                    const url = new URL(route.request().url());
                    if (url.hostname !== 'sales.test') return route.abort();
                    if (url.pathname.startsWith('/fixture/purchase/')) {
                        return route.fulfill({ json: { getData: [{ purchaseId: 1, productName: 'Regression Phone',
                            supplierName: 'Fixture Supplier', invoice: 'PUR-1', currentStock: 3,
                            salePriceExVat: 150, buyPrice: 100 }] } });
                    }
                    if (url.pathname.startsWith('/fixture/serials/')) {
                        return route.fulfill({ json: { status: 'ok', selection_required: currentRequired, serialized, serials: serialized
                            ? [1, 2, 3].map(id => ({ id, serialNumber: 'SER-' + id })) : [] } });
                    }
                    if (url.pathname === '/new/sale') return route.fulfill({ contentType: 'text/html', body: fixture(serialized) });
                    return route.abort();
                });
                await page.goto('http://sales.test/new/sale');
                await page.locator('.js-sale-product-select').evaluate(el => { el.disabled = false; });
                await page.selectOption('.js-sale-product-select', 'purchase_1');
                await page.waitForSelector('#productDetails .qty');
                await page.waitForFunction(expected => document.querySelector('#productDetails tr').dataset.serialAvailable === expected, serialized ? '3' : '0');
                for (const selector of ['.qty', '.salePrice', '.totalSale', '.row-stock', '.open-sale-serials', '.serial-count-badge', '.warrantyDays']) {
                    await visible(page, '#productDetails ' + selector);
                }
                const geometry = await page.locator('#productDetails tr').evaluate(el => ({
                    display: getComputedStyle(el).display, right: el.getBoundingClientRect().right,
                    width: document.documentElement.clientWidth,
                }));
                assert.equal(geometry.display, width <= 992 ? 'grid' : 'table-row');
                if (width <= 992) assert(geometry.right <= geometry.width + 1, 'Card exceeds viewport');
                await page.fill('#productDetails .qty', '2');
                await page.fill('#discountAmount', '10');
                await page.fill('#additionalChargeAmount', '5');
                await page.fill('#paidAmount', '100');
                assert.equal(await page.locator('#grandTotal').inputValue(), '295');
                assert.equal(await page.locator('#dueAmount').inputValue(), '195');
                if (serialized) {
                    if(required) {
                        await visible(page, '#productDetails .serial-note');
                        assert(await page.locator('#saveSaleForm button[type="submit"]').isDisabled());
                        assert.equal(await page.locator('#saveSaleForm').evaluate(el => el.dispatchEvent(new Event('submit', { cancelable: true }))), false);
                    } else {
                        await visible(page, '#productDetails .serial-policy-note');
                        assert(!(await page.locator('#saveSaleForm button[type="submit"]').isDisabled()));
                        currentRequired = true;
                        await page.evaluate(() => window.dispatchEvent(new Event('focus')));
                        await page.waitForFunction(() => document.querySelector('#productDetails tr').dataset.serialRequired === '1');
                        assert(await page.locator('#saveSaleForm button[type="submit"]').isDisabled());
                        currentRequired = false;
                        await page.evaluate(() => window.dispatchEvent(new Event('focus')));
                        await page.waitForFunction(() => document.querySelector('#productDetails tr').dataset.serialRequired === '0');
                        assert(!(await page.locator('#saveSaleForm button[type="submit"]').isDisabled()));
                    }
                    await page.click('.open-sale-serials');
                    await page.waitForSelector('#saleSerialModal .serial-pick');
                    await page.locator('.serial-pick').nth(0).check();
                    await page.click('#applySaleSerials');
                    await visible(page, '#saleSerialModal'); // mismatch keeps modal open
                    await page.locator('.serial-pick').nth(1).check();
                    await page.click('#applySaleSerials');
                    assert.equal(await page.locator('.serial-count-badge').textContent(), catalog(locale, 'sales').selected_count.replace(':count','2'));
                    assert(!(await page.locator('.serial-note').isVisible()));
                    if(!required) {
                        await page.click('.open-sale-serials');
                        await page.waitForSelector('#saleSerialModal .serial-pick');
                        await page.locator('.serial-pick').nth(0).uncheck();
                        await page.locator('.serial-pick').nth(1).uncheck();
                        await page.click('#applySaleSerials');
                        assert.equal(await page.locator('.serial-count-badge').textContent(), catalog(locale, 'sales').selected_count.replace(':count','0'));
                        assert(!(await page.locator('#saveSaleForm button[type="submit"]').isDisabled()));
                    }
                }
                assert(!(await page.locator('#saveSaleForm button[type="submit"]').isDisabled()));
                const payload = await page.locator('#saveSaleForm').evaluate(el => [...new FormData(el)]);
                assert.equal(payload.filter(([key]) => key === 'qty[]').length, 1);
                assert.equal(payload.filter(([key]) => key === 'purchaseData[]').length, 1);
                assert.equal(payload.filter(([key]) => key === 'salePrice[]').length, 1);
                assert.deepEqual(payload.filter(([key]) => key === 'serialId[0][]').map(([, value]) => value), serialized && required ? ['1', '2'] : []);
                await page.fill('#productDetails .qty', '4');
                await visible(page, '#productDetails .stock-note');
                assert.equal(await page.locator('#saveSaleForm').evaluate(el => el.dispatchEvent(new Event('submit', { cancelable: true }))), false);
                await page.fill('#productDetails .qty', '2');
                assert.equal(await page.locator('#saveSaleForm').evaluate(el => el.dispatchEvent(new Event('submit', { cancelable: true }))), true);
                await page.click('.remove-row');
                assert.equal(await page.locator('#productDetails tr').count(), 0);
                assert(await page.locator('#saveSaleForm button[type="submit"]').isDisabled());
                assert.deepEqual(errors, []);
                console.log(`PASS create ${width}px ${serialized ? 'serialized' : 'nonserialized'} ${required ? 'mandatory' : 'optional'}: controls, modal, validation, totals, payload, removal`);
                passed++;
                await page.close();
            }
            for (const required of [true, false]) {
            const page = await browser.newPage({ viewport: { width, height: 900 } });
            // Source-derived edit row, with only server-side fixture values substituted.
            const row = edit.match(/<tr data-item-id=[\s\S]*?<\/tr>/)[0]
                .replace(/\{\{[\s\S]*?\}\}/g, '1').replace(/@money\([^)]*\)/g, '150.00');
            const header = edit.match(/<thead[\s\S]*?<\/thead>/)[0];
            await page.route('**/fixture/serials/*', route => route.fulfill({ json: { status:'ok', serialized:true, selection_required:required, serials: [{id: 2, serialNumber: 'SER-2'}] } }));
            const editSerialScript = edit.slice(edit.indexOf('    function qs(sel'), edit.indexOf('    // Expose sold serials'))
                .replace(/\{\{ route\('ajax.purchase.serials.public',[\s\S]*?\}\}/g, 'http://sales.test/fixture/serials/0');
            await page.setContent(`${bridge(locale)}<style>${css}</style>${mobile}<form><table class="table rn-table-pro rn-sale-mobile-table">${header}<tbody>${row}</tbody></table></form>
                <script>window.__soldSerials={1:[{id:1,serialNumber:'SER-1'}]};${editSerialScript}</script>`);
            for (const selector of ['[data-action="toggle-serials"]', '[data-role="qty"]', '[data-role="price"]', '[data-role="line-total"]', '[data-label="'+catalog(locale,'common').current_stock+'"]']) await visible(page, selector);
            assert.equal(await page.locator('tbody tr').evaluate(el => getComputedStyle(el).display), width <= 992 ? 'grid' : 'table-row');
            await page.click('[data-action="toggle-serials"]');
            await page.waitForSelector('.serials-list input[type="checkbox"]');
            assert.equal(await page.locator('.serial-hidden-container input').getAttribute('name'), 'serialIdByItem[1][]');
            assert.equal(await page.locator('.serial-hidden-container input').inputValue(), '1');
            await page.locator('.serials-list input[type="checkbox"]').nth(0).uncheck();
            if(required) await visible(page, '[data-role="serial-error"]');
            else assert(!(await page.locator('[data-role="serial-error"]').isVisible()));
            await page.locator('.serials-list input[type="checkbox"]').nth(1).check();
            assert(!(await page.locator('[data-role="serial-error"]').isVisible()));
            assert.equal(await page.locator('.serial-hidden-container input').inputValue(), '2');
            console.log(`PASS edit ${width}px ${required ? 'mandatory' : 'optional'}: source-derived controls, layout, serial selection, validation and payload`);
            passed++;
            await page.close();
            }
            const settingsPage = await browser.newPage({ viewport: {width, height:900} });
            const settingsSource = read('resources/views/business/salesSettings.blade.php');
            const settingsForm = settingsSource.match(/<form method="POST"[\s\S]*?<\/form>/)[0]
                .replace(/\{\{[\s\S]*?\}\}/g, '1').replace('@csrf', '<input type="hidden" name="_token" value="fixture-token">')
                .replace(/@checked\([^\n]*\)/g, 'checked');
            await settingsPage.setContent(`<style>${css}</style>${settingsForm}`);
            await visible(settingsPage, 'label[for="salesSerialRequired"]');
            assert(await settingsPage.locator('#salesSerialRequired').isChecked());
            await settingsPage.click('label[for="salesSerialRequired"]');
            assert(!(await settingsPage.locator('#salesSerialRequired').isChecked()));
            let settingsData = await settingsPage.locator('form').evaluate(form => [...new FormData(form)]);
            assert.deepEqual(settingsData.filter(([k]) => k === 'sales_serial_selection_required').map(([,v]) => v), ['0']);
            await settingsPage.click('label[for="salesSerialRequired"]');
            settingsData = await settingsPage.locator('form').evaluate(form => [...new FormData(form)]);
            assert.deepEqual(settingsData.filter(([k]) => k === 'sales_serial_selection_required').map(([,v]) => v), ['0','1']);
            assert(settingsData.some(([k,v]) => k === '_token' && v === 'fixture-token'));
            console.log(`PASS settings ${width}px: toggle, default, boolean payload and CSRF field`);
            passed++;
            await settingsPage.close();
        }
        console.log(`${passed} offline browser scenarios passed (${assertionCount} assertions). HTTP authorization and database transactions require PHPUnit.`);
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
