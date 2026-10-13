// Existing Playwright/Chrome only; SQLite-rendered HTML/API responses, all requests intercepted.
// Export with RN_EXPORT_SALE_FIXTURES=1 php artisan test --filter=SalesRegressionTest.
const fs = require('node:fs'), path = require('node:path'), assert = require('node:assert/strict');
const {chromium} = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = path.resolve(__dirname, '../..'), publicRoot = path.join(root, 'public');
const bundle = fs.readFileSync(path.join(publicRoot, 'eshop/assets/js/backend-bundle.min.js'), 'utf8');
const jqueryEnd = bundle.indexOf(',function(t,e){"object"==typeof exports', bundle.indexOf('.noConflict'));
assert.ok(jqueryEnd > 0, 'Installed jQuery boundary unavailable');
const installedJquery = bundle.slice(0, jqueryEnd) + ';';
let cases = 0, checks = 0;
const results = [];
function check(value, message) { assert.ok(value, message); checks++; }
(async () => {
  const browser = await chromium.launch({headless: true, executablePath: process.env.CHROME_PATH});
  try {
    for (const locale of ['en', 'bn']) for (const policy of ['required', 'optional']) {
      const stem = path.join(root, 'storage/logs', `sprint03-sale-${locale}-${policy}`);
      const html = fs.readFileSync(stem + '.html', 'utf8'), fixture = JSON.parse(fs.readFileSync(stem + '.json'));
      for (const width of [320, 360, 375, 390, 430, 576, 768, 992, 1280, 1440]) {
        const page = await browser.newPage({viewport: {width, height: 900}});
        const posts = [], pageErrors = [];
        let changePolicy = false, failPolicy = false, delayWalking = false;
        page.on('pageerror', e => pageErrors.push(e.message));
        await page.route('**/*', async route => {
          const request = route.request(), url = new URL(request.url());
          // Supply the already-installed bundle's jQuery during the layout's early CDN load.
          if (url.hostname === 'ajax.googleapis.com' && url.pathname === '/ajax/libs/jquery/3.7.1/jquery.min.js') return route.fulfill({contentType: 'application/javascript', body: installedJquery});
          if (url.hostname === 'cdn.jsdelivr.net' && url.pathname === '/npm/sweetalert2@11') return route.fulfill({contentType: 'application/javascript', body: fs.readFileSync(path.join(publicRoot, 'vendor/sweetalert/sweetalert.all.js'))});
          if (url.hostname !== 'localhost') return route.abort();
          const normalized = url.pathname.replace(/^\/RetailNova\//, '/');
          if (request.method() === 'POST' && normalized === '/sale/save/data') {
            posts.push(new URLSearchParams(request.postData()));
            return route.fulfill({contentType: 'text/html', body: '<html><body>QA captured submission</body></html>'});
          }
          if (normalized === '/new/sale') return route.fulfill({contentType: 'text/html', body: html});
          const response = fixture.responses[normalized];
          if (response) {
            if(delayWalking && /\/customer\//.test(normalized) && !normalized.includes('/customer/'+fixture.customerId+'/')) await new Promise(resolve=>setTimeout(resolve,180));
            if (failPolicy && normalized.endsWith('/serials')) return route.fulfill({status:404, contentType:'application/json', body:JSON.stringify({status:'error',serials:[]})});
            let json = structuredClone(response);
            if (changePolicy && normalized.endsWith('/serials')) json.selection_required = policy !== 'required';
            return route.fulfill({contentType: 'application/json', body: JSON.stringify(json)});
          }
          const file = path.resolve(publicRoot, '.' + normalized.replace(/^\/public\//, '/'));
          if (file.startsWith(publicRoot + path.sep) && fs.existsSync(file) && fs.statSync(file).isFile()) {
            const type = {'.js': 'application/javascript', '.css': 'text/css', '.woff2': 'font/woff2', '.ttf': 'font/ttf', '.png': 'image/png', '.jpg': 'image/jpeg', '.svg': 'image/svg+xml'}[path.extname(file)] || 'application/octet-stream';
            return route.fulfill({contentType: type, body: fs.readFileSync(file)});
          }
          return route.abort();
        });
        await page.goto('http://localhost/RetailNova/new/sale');
        await page.locator('#loading').waitFor({state: 'hidden'});
        check(await page.locator('#customerName').inputValue() === '', 'Customer silently preselected');
        for (const id of ['saleProductSearch','barcodeInput','searchBarcodeBtn','categorySelect','productName','outOfStockBtn']) check(!await page.locator('#'+id).isEnabled(), 'Customer gate missing: '+id);
        await page.locator('#barcodeInput').evaluate(input=>{input.value='SPRINT03-QA-SCAN';input.dispatchEvent(new KeyboardEvent('keydown',{key:'Enter',bubbles:true}));});
        check(await page.locator('#productDetails tr').count()===0,'Scanner bypassed initial customer gate');
        check(await page.locator('html').getAttribute('lang') === locale, 'Wrong locale');
        check(await page.locator('#saleEmptyState').isVisible(), 'Empty state missing');
        check(!await page.locator('#saleSaveButton').isEnabled() && !await page.locator('#saleSavePrintButton').isEnabled(), 'Empty sale enabled submission');
        check(await page.locator('[data-sale-back]').getAttribute('href').then(h => h.endsWith('/Sale/list')), 'Back route changed');
        await page.locator('#useWalkingCustomerBtn').click();
        await page.waitForFunction(()=>document.querySelector('#customerName').dataset.customerReady==='1');
        check(await page.locator('#barcodeInput').isEnabled()&&await page.locator('#saleProductSearch').isEnabled(),'Explicit Walk-in did not enable product entry');
        await page.locator('#customerName').selectOption(String(fixture.customerId));
        await page.waitForFunction(() => !document.getElementById('productName').disabled && document.getElementById('productName').options.length === 3);
        await page.waitForFunction(() => document.getElementById('prevDue').value === '35.00');
        check(await page.locator('#prevDueDisplay').innerText().then(t=>t.includes('35.00')),'Customer previous due not displayed');
        await page.locator('#categorySelect').selectOption(String(fixture.categoryId));
        await page.locator('#saleProductSearch').fill('Serialized');
        check(await page.locator('#productName option:not([hidden])').count() === 2, 'Product name filtering failed');
        await page.locator('#saleProductSearch').fill('no-match-qa');
        check(await page.locator('#productName option:not([hidden])').count() === 1, 'No-results filter failed');
        await page.locator('#saleProductSearch').fill('');
        if ([375, 1440].includes(width) && policy === 'required') await page.screenshot({path: path.join(root, 'storage/logs', `sprint03-empty-${locale}-${width}.png`), fullPage: true});

        // Exercise the actual scanner -> batch selector -> shared sales row handler.
        await page.locator('#barcodeInput').fill('SPRINT03-QA-SCAN');
        await page.locator('#barcodeInput').press('Enter');
        await page.waitForFunction(() => document.querySelectorAll('#productDetails tr').length === 1 && document.querySelector('#productDetails tr').dataset.serialPolicyError === '0');
        check(await page.locator('.rn-sale-product-name').innerText() === 'Sprint03 QA Serialized', 'Barcode batch selection failed');
        await page.locator('[data-sale-qty="1"]').click();
        check(await page.locator('.qty').inputValue() === '2', 'Quantity increment failed');
        check(await page.locator('.totalSale').inputValue() === '300', 'Line calculation changed');
        check(await page.locator('#saleSaveButton').isEnabled() === (policy === 'optional'), 'Policy not reflected by Save');
        check(await page.locator('#saleSavePrintButton').isEnabled() === (policy === 'optional'), 'Policy not reflected by Save & Print');
        const preservedCart=await page.locator('#saveSaleForm').evaluate(f=>({rows:f.querySelectorAll('#productDetails tr').length,total:f.querySelector('#grandTotal').value}));
        await page.locator('#paidAmount').fill('25');
        await page.locator('#customerName').selectOption('');
        check(!await page.locator('#saleSaveButton').isEnabled()&&!await page.locator('#saleSavePrintButton').isEnabled(),'Cleared customer allowed saving');
        check(await page.locator('#productDetails tr').count()===preservedCart.rows&&(await page.locator('#paidAmount').inputValue())==='25','Customer clearing destroyed cart/payment');
        for(const id of ['saleProductSearch','barcodeInput','searchBarcodeBtn','categorySelect','productName']) check(!await page.locator('#'+id).isEnabled(),'Cleared customer enabled '+id);
        await page.locator('#useWalkingCustomerBtn').click();
        await page.waitForFunction(()=>document.querySelector('#customerName').dataset.customerReady==='1');
        check(await page.locator('#prevDue').inputValue()==='0.00','Walk-in retained stale due');
        await page.locator('#customerName').selectOption(String(fixture.customerId));
        await page.waitForFunction(()=>document.querySelector('#customerName').dataset.customerReady==='1'&&document.querySelector('#prevDue').value==='35.00');
        check(await page.locator('#grandTotal').inputValue()===preservedCart.total,'Customer switch changed cart totals');
        delayWalking=true;
        await page.locator('#useWalkingCustomerBtn').click();
        await page.locator('#customerName').selectOption(String(fixture.customerId));
        await page.waitForFunction(()=>document.querySelector('#customerName').dataset.customerReady==='1'&&document.querySelector('#prevDue').value==='35.00');
        await page.waitForTimeout(220);
        check(await page.locator('#prevDue').inputValue()==='35.00'&&await page.locator('#productName').isEnabled(),'Stale previous-customer response overwrote current state');
        delayWalking=false;
        await page.locator('#paidAmount').fill('0');
        failPolicy = true;
        await page.evaluate(() => window.dispatchEvent(new Event('focus')));
        await page.waitForFunction(() => document.querySelector('#productDetails tr').dataset.serialPolicyError === '1');
        check(!await page.locator('#saleSaveButton').isEnabled() && !await page.locator('#saleSavePrintButton').isEnabled(), 'Unverified policy allowed submission');
        const failureNote = await page.locator('.serial-note').innerText();
        check((await page.locator('#saleErrorSummary').innerText()).includes(failureNote), 'Authorization/policy failure mislabeled as missing serials');
        failPolicy = false;
        await page.evaluate(() => window.dispatchEvent(new Event('focus')));
        await page.waitForFunction(() => document.querySelector('#productDetails tr').dataset.serialPolicyError === '0');
        check(await page.locator('#saleSaveButton').isEnabled() === (policy === 'optional'), 'Policy retry did not restore correct optional validation');
        await page.locator('.open-sale-serials').click();
        await page.locator('#saleSerialModal').waitFor({state: 'visible'});
        await page.waitForFunction(() => document.querySelectorAll('#saleSerialList .serial-pick').length === 3);
        await page.locator('.serial-pick').nth(0).check();
        await page.locator('.serial-pick').nth(1).check();
        if (width === 375 && policy === 'required') await page.screenshot({path: path.join(root, 'storage/logs', `sprint03-serial-${locale}-${width}.png`)});
        await page.locator('#applySaleSerials').click();
        await page.locator('#saleSerialModal').waitFor({state: 'hidden'});
        check(await page.locator('input[name="serialId[0][]"]').count() === 2, 'Serial payload count mismatch');
        check(await page.locator('#saleSaveButton').isEnabled() && await page.locator('#saleSavePrintButton').isEnabled(), 'Exact serial selection did not enable actions');
        check(await page.locator('.serial-count-badge').innerText().then(t => t.startsWith('2')), 'Selected count incorrect');
        if (policy === 'optional') {
          await page.locator('.open-sale-serials').click(); await page.locator('#saleSerialModal').waitFor({state: 'visible'});
          await page.locator('#saleSerialClear').click(); await page.locator('#applySaleSerials').click(); await page.locator('#saleSerialModal').waitFor({state: 'hidden'});
          check(await page.locator('input[name="serialId[0][]"]').count() === 0, 'Optional empty selection not preserved');
          check(await page.locator('#saleSaveButton').isEnabled(), 'Optional allocation blocked');
        }
        // Quantity errors must remain visible on the same row and must not lock valid retries.
        await page.locator('.qty').fill('4');
        check(await page.locator('.stock-note').isVisible(), 'Stock validation missing near quantity');
        const blocked = await page.locator('#saveSaleForm').evaluate(form => {
          const e = new Event('submit', {bubbles: true, cancelable: true}); form.dispatchEvent(e); return e.defaultPrevented;
        });
        check(blocked && posts.length === 0, 'Invalid stock quantity submitted');
        await page.locator('.qty').fill('2');
        if (policy === 'required') {
          changePolicy = true; await page.evaluate(() => window.dispatchEvent(new Event('focus')));
          await page.waitForFunction(() => document.querySelector('#productDetails tr').dataset.serialRequired === '0');
          check(await page.locator('.serial-policy-note').innerText().then(t => t.length > 0), 'Open-form policy refresh failed');
          changePolicy = false; await page.evaluate(() => window.dispatchEvent(new Event('focus')));
          await page.waitForFunction(() => document.querySelector('#productDetails tr').dataset.serialRequired === '1');
        }
        await page.locator('#productName').selectOption('purchase_' + fixture.plainPurchaseId);
        await page.waitForFunction(() => document.querySelectorAll('#productDetails tr').length === 2 && document.querySelectorAll('#productDetails tr')[1].dataset.serialized === '0');
        check(await page.locator('#saleSaveButton').isEnabled(), 'Nonserialized row incorrectly blocked sale');
        await page.locator('#discountAmount').fill('10'); await page.locator('#additionalChargeName').fill('QA Delivery'); await page.locator('#additionalChargeAmount').fill('5'); await page.locator('#paidAmount').fill('100');
        check(await page.locator('#totalSaleAmount').inputValue() === '450', 'Subtotal changed');
        check(await page.locator('#grandTotal').inputValue() === '445', 'Discount/charge calculation changed');
        check(await page.locator('#dueAmount').inputValue() === '345', 'Paid/due calculation changed');
        await page.locator('#rnReceiveAmountBtn').click(); check(await page.locator('#dueAmount').inputValue() === '0', 'Receive Amount did not recalculate due'); await page.locator('#paidAmount').fill('100');
        const metrics = await page.evaluate(() => ({overflow: document.documentElement.scrollWidth > innerWidth, card: getComputedStyle(document.querySelector('#productDetails tr')).display, columns: getComputedStyle(document.querySelector('.rn-sale-layout')).gridTemplateColumns.split(' ').length, availableWidth:document.getElementById('saveSaleForm').clientWidth}));
        check(!metrics.overflow, `Page overflow ${locale}/${policy}/${width}`);
        const flow=await page.locator('#saveSaleForm').evaluate(f=>{ const cards=[...f.querySelectorAll('.rn-sale-panel')]; const bounds=cards.map(c=>c.getBoundingClientRect()); return {overlap:bounds.some((r,i)=>i>0&&r.top<bounds[i-1].bottom-1),fixed:cards.some(c=>['absolute','fixed'].includes(getComputedStyle(c).position)),clipped:[...f.querySelectorAll('input:not([type=hidden]),select,textarea')].some(c=>c.getBoundingClientRect().right>f.getBoundingClientRect().right+1)}; });
        if(width<=992) check(!flow.overlap&&!flow.fixed&&!flow.clipped,'Mobile cards overlap or clip controls');
        check(metrics.card === (width <= 992 ? 'grid' : 'table-row'), 'Card/table breakpoint mismatch');
        check(metrics.columns === (width >= 1400 && metrics.availableWidth >= 1180 ? 2 : 1), 'Billing sidebar breakpoint mismatch');
        const names = await page.locator('#saveSaleForm').evaluate(form => {const data = new FormData(form); return {qty: data.getAll('qty[]'), purchases: data.getAll('purchaseData[]'), buy: data.getAll('buyPrice[]'), paid: data.getAll('paidAmount'), grand: data.getAll('grandTotal')};});
        check(names.qty.join(',') === '2,1' && names.purchases.join(',') === fixture.purchaseId + ',' + fixture.plainPurchaseId && names.buy.length === 2, 'Duplicate or missing authoritative item fields');
        check(names.paid.length === 1 && names.grand.length === 1, 'Duplicate authoritative financial inputs');
        if ([375, 1440].includes(width) && policy === 'required') {await page.evaluate(() => window.scrollTo(0, 0)); await page.screenshot({path: path.join(root, 'storage/logs', `sprint03-populated-${locale}-${width}.png`), fullPage: true});}
        check(await page.locator('#totalOutstandingDisplay').innerText().then(t=>t.includes('380')),'Previous plus current due incorrect');
        // Capture real browser form submissions offline and test the second submit is blocked.
        {
          const removalSnapshot = () => page.locator('.remove-row').last().evaluate(button => {const form=button.closest('form');return JSON.stringify([...new FormData(form)]);});
      const beforeRemoval=await removalSnapshot(),beforeCount=await page.locator('#productDetails tr').count();
      await page.locator('.remove-row').last().click();await page.locator('.swal2-popup').waitFor({state:'visible'});
      check(await page.locator('#productDetails tr').count()===beforeCount,'Removed before confirmation');
      check(await removalSnapshot()===beforeRemoval,'Mutated payload or totals before confirmation');
      await page.locator('.swal2-cancel').click();await page.locator('.swal2-popup').waitFor({state:'hidden'});
      check(await removalSnapshot()===beforeRemoval,'Cancel changed form or serial payload');
      await page.locator('.remove-row').last().click();await page.locator('.swal2-confirm').click();
      await page.waitForFunction(()=>document.querySelectorAll('#productDetails tr').length===1);
      check(await page.locator('#productDetails tr').count()===1,'Confirmed removal failed');
          await page.locator('#productName').selectOption('purchase_'+fixture.plainPurchaseId);
          await page.waitForFunction(()=>document.querySelectorAll('#productDetails tr').length===2);
        }
        const action = width === 375 || width === 1280 ? 'save_print' : 'save';
        const guard = await page.locator('#saveSaleForm').evaluate((form, action) => {
          const decisions=[];document.addEventListener('submit',e=>{decisions.push(e.defaultPrevented);e.preventDefault();});
          const button=document.querySelector(action==='save'?'#saleSaveButton':'#saleSavePrintButton');
          form.dispatchEvent(new SubmitEvent('submit',{bubbles:true,cancelable:true,submitter:button}));
          const body=new URLSearchParams(new FormData(form)).toString();
          form.dispatchEvent(new SubmitEvent('submit',{bubbles:true,cancelable:true,submitter:button}));
          return {decisions,body,disabled:[...form.querySelectorAll('button[type="submit"]')].every(b=>b.disabled)};
        },action);
        check(guard.decisions.join(',')==='false,true'&&guard.disabled, 'Duplicate submit guard failed');
        const captured=new URLSearchParams(guard.body);check(captured.get('saleAction')===action,'Disabled submitter lost Save intent');
        check(captured.get('grandTotal')==='445'&&captured.getAll('qty[]').join(',')==='2,1','Submission totals/payload changed');
        check(pageErrors.length===0,'Browser script errors: '+pageErrors.join(';'));
        cases++;results.push({locale,policy,width,pass:true}); await page.close();
      }
    }
    const report={type:'isolated SQLite HTML/API browser tests; no local database writes',cases,checks,results};
    fs.writeFileSync(path.join(root,'storage/logs/sprint03-browser-results.json'),JSON.stringify(report,null,2));
    console.log(JSON.stringify({cases,checks,pass:true}));
  } finally { await browser.close(); }
})().catch(e=>{console.error(e);process.exitCode=1;});
