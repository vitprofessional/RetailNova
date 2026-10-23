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
      for (const appPrefix of ['', '/RetailNova']) for (const width of [320, 360, 375, 390, 430, 576, 768, 992, 1280, 1440]) {
        // Exercise identical Laravel route seeds at a domain root and under XAMPP's mount path.
        const pageHtml = html.replace(/http:\/\/localhost\//g, 'http://localhost'+appPrefix+'/')
          .replace(/data-products-template="([^"]+)"/, '$& data-previous-due-template="http://localhost'+appPrefix+'/ajax/public/customer/__ID__/previous-due" data-product-edit-template="http://localhost'+appPrefix+'/product/edit/__ID__"');
        const page = await browser.newPage({viewport: {width, height: 900}});
        const posts = [], pageErrors = [], previousDueUrls = [];
        let changePolicy = false, failPolicy = false, failDue = false, delayWalking = false;
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
          if (normalized === '/new/sale') return route.fulfill({contentType: 'text/html', body: pageHtml});
          if (normalized.endsWith('/previous-due')) previousDueUrls.push(request.url());
          if (/\/customer\/999999\/(products|previous-due)$/.test(normalized)) return route.fulfill({status: 404, contentType: 'application/json', body: JSON.stringify({status: 'error'})});
          if (failDue && normalized.endsWith('/previous-due')) return route.fulfill({status: 503, contentType: 'application/json', body: JSON.stringify({status: 'error'})});
          const htmlOption = (id, label, stock) => `<option value="${id}" data-product-id="${id}" data-current-stock="${stock}">${label} — Stock: ${stock}</option>`;
          if (normalized === '/ajax/public/brands') {
            return route.fulfill({contentType:'application/json', body:JSON.stringify({data:`<option value="">All Brands</option><option value="${fixture.brandAId}">Sprint03 QA Brand A</option><option value="${fixture.brandBId}">Sprint03 QA Brand B</option>`})});
          }
          if (normalized === '/ajax/public/categories') {
            const brandId = url.searchParams.get('brandId');
            const categoryId = brandId === String(fixture.brandAId) ? fixture.categoryAId
              : brandId === String(fixture.brandBId) ? fixture.categoryBId : null;
            const choices = categoryId
              ? `<option value="${categoryId}">Sprint03 QA Category ${categoryId === fixture.categoryAId ? 'A' : 'B'}</option>`
              : `<option value="${fixture.categoryAId}">Sprint03 QA Category A</option><option value="${fixture.categoryBId}">Sprint03 QA Category B</option>`;
            return route.fulfill({contentType:'application/json', body:JSON.stringify({data:`<option value="">All Categories</option>${choices}`})});
          }
          if (/\/ajax\/public\/customer\/\d+\/products$/.test(normalized)) {
            const brandId = url.searchParams.get('brandId'), categoryId = url.searchParams.get('categoryId');
            let options = '<option value="">Select</option>';
            if ((!brandId || brandId === String(fixture.brandAId)) && (!categoryId || categoryId === String(fixture.categoryAId))) options += htmlOption(fixture.productId, 'Sprint03 QA Serialized', 3);
            if ((!brandId || brandId === String(fixture.brandBId)) && (!categoryId || categoryId === String(fixture.categoryBId))) options += htmlOption(fixture.plainProductId, 'Sprint03 QA Plain', 6);
            return route.fulfill({contentType:'application/json', body:JSON.stringify({data:options,outOfStock:[]})});
          }
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
        await page.goto('http://localhost'+appPrefix+'/new/sale');
        await page.locator('#loading').waitFor({state: 'hidden'});
        check(await page.locator('#customerName').inputValue() === '', 'Customer silently preselected');
        for (const id of ['saleProductSearch','barcodeInput','searchBarcodeBtn','brandSelect','categorySelect','productName','outOfStockBtn']) check(!await page.locator('#'+id).isEnabled(), 'Customer gate missing: '+id);
        await page.locator('#barcodeInput').evaluate(input=>{input.value='SPRINT03-QA-SCAN';input.dispatchEvent(new KeyboardEvent('keydown',{key:'Enter',bubbles:true}));});
        check(await page.locator('#productDetails tr').count()===0,'Scanner bypassed initial customer gate');
        check(await page.locator('html').getAttribute('lang') === locale, 'Wrong locale');
        check(await page.locator('#saleEmptyState').isVisible(), 'Empty state missing');
        check(!await page.locator('#saleSaveButton').isEnabled() && !await page.locator('#saleSavePrintButton').isEnabled(), 'Empty sale enabled submission');
        check(await page.locator('[data-sale-back]').getAttribute('href').then(h => h.endsWith('/Sale/list')), 'Back route changed');
        await page.locator('#customerName').evaluate(select => { select.add(new Option('Invalid customer', '999999')); select.value = '999999'; select.dispatchEvent(new Event('change', {bubbles: true})); });
        await page.waitForFunction(() => document.querySelector('#customerName').dataset.productLookupError === '1');
        for (const id of ['saleProductSearch','barcodeInput','searchBarcodeBtn','brandSelect','categorySelect','productName']) check(!await page.locator('#'+id).isEnabled(), 'Invalid customer enabled '+id);
        await page.locator('#customerName').selectOption('');
        await page.locator('#useWalkingCustomerBtn').click();
        await page.waitForFunction(()=>document.querySelector('#customerName').dataset.customerReady==='1');
        check(await page.locator('#barcodeInput').isEnabled()&&await page.locator('#saleProductSearch').isEnabled(),'Explicit Walk-in did not enable product entry');
        await page.locator('#customerName').selectOption(String(fixture.customerId));
        await page.waitForFunction(() => !document.getElementById('productName').disabled && document.getElementById('productName').options.length === 3);
        await page.waitForFunction(() => document.getElementById('prevDue').value === '35.00');
        await page.waitForFunction(() => !document.getElementById('brandSelect').disabled && document.getElementById('brandSelect').options.length === 3);
        const cartContract = await page.locator('#newSaleItemsTable').evaluate(table => ({headings:[...table.querySelectorAll('thead th')].map(th=>th.textContent.trim()),hiddenLotPayload:!!table.querySelector('#saleAllocationRows[hidden]')}));
        check(cartContract.headings.length===8 && cartContract.hiddenLotPayload,'Product-centric cart columns or hidden lot payload contract changed');
        const assertCartTotalsSynced = async (label) => {
          const totals = await page.locator('#saveSaleForm').evaluate(form => {
            const lines=[...form.querySelectorAll('#productDetails tr[data-product-summary] .rn-sale-line-total')]
              .reduce((sum,el)=>sum+(Number(el.textContent.replace(/,/g,''))||0),0);
            const allocations=[...form.querySelectorAll('#saleAllocationRows .totalSale')]
              .reduce((sum,input)=>sum+(Number(input.value)||0),0);
            const subtotal=Number(form.querySelector('#totalSaleAmount').value)||0;
            const grand=Number(form.querySelector('#grandTotal').value)||0;
            const paid=Number(form.querySelector('#paidAmount').value)||0;
            const due=Number(form.querySelector('#dueAmount').value)||0;
            const expectedGrand=Math.max(0,subtotal-(Number(form.querySelector('#discountAmount').value)||0)+(Number(form.querySelector('#additionalChargeAmount').value)||0));
            return {lines,allocations,subtotal,grand,paid,due,expectedGrand,expectedDue:Math.max(0,expectedGrand-paid)};
          });
          check(Math.abs(totals.lines-totals.subtotal)<0.01, `${label}: visible cart lines and Subtotal diverged (${totals.lines} vs ${totals.subtotal})`);
          check(Math.abs(totals.allocations-totals.subtotal)<0.01, `${label}: allocation totals and Subtotal diverged (${totals.allocations} vs ${totals.subtotal})`);
          check(Math.abs(totals.grand-totals.expectedGrand)<0.01, `${label}: Grand Total did not follow subtotal/discount/charge`);
          check(Math.abs(totals.due-totals.expectedDue)<0.01, `${label}: Due Amount did not follow Grand Total/Paid Amount`);
        };
        const entryLayout = await page.locator('.rn-sale-entry-grid').evaluate(grid => {
          const fields=['.rn-sale-product-search','.rn-sale-barcode-search','.rn-sale-brand-field','.rn-sale-category-field','.rn-sale-select-field'].map(selector=>{
            const r=grid.querySelector(selector).getBoundingClientRect();
            return {x:r.left,right:r.right,top:r.top,bottom:r.bottom,width:r.width};
          });
          const intersects=(a,b)=>a.left<b.right-1&&a.right>b.left+1&&a.top<b.bottom-1&&a.bottom>b.top+1;
          return {twoColumns:getComputedStyle(grid).gridTemplateColumns.trim().split(' ').filter(Boolean).length>1,fields,overlap:fields.some((a,i)=>fields.slice(i+1).some(b=>intersects(a,b))),gridWidth:grid.getBoundingClientRect().width};
        });
        check(!entryLayout.overlap,'Product entry fields overlap at '+width+'px');
        if(entryLayout.twoColumns){
          check(Math.abs(entryLayout.fields[0].top-entryLayout.fields[1].top)<1,'Product search and barcode are not on row one');
          check(Math.abs(entryLayout.fields[2].top-entryLayout.fields[3].top)<1,'Brand and Category are not aligned on row two');
          check(entryLayout.fields[4].top>=Math.max(entryLayout.fields[2].bottom,entryLayout.fields[3].bottom)-1,'Product selection does not follow Brand/Category');
          check(Math.abs(entryLayout.fields[4].width-entryLayout.gridWidth)<2,'Product selection does not span the entry row');
        } else {
          check(entryLayout.fields.every(field=>Math.abs(field.width-entryLayout.gridWidth)<2),'Single-column product form field was clipped');
        }
        await page.locator('#brandSelect').selectOption(String(fixture.brandAId));
        await page.waitForFunction((id) => document.querySelector('#categorySelect').options.length === 2 && document.querySelector(`#categorySelect option[value="${id}"]`), fixture.categoryAId);
        check(await page.locator(`#productName option[value="${fixture.productId}"]`).count() === 1 && await page.locator(`#productName option[value="${fixture.plainProductId}"]`).count() === 0, 'Brand filter did not constrain products');
        await page.locator('#categorySelect').selectOption(String(fixture.categoryAId));
        await page.waitForFunction(() => document.querySelectorAll('#productName option').length === 2);
        await page.locator('#brandSelect').selectOption(String(fixture.brandBId));
        await page.waitForFunction((id) => document.querySelector('#categorySelect').value === '' && document.querySelector('#categorySelect').options.length === 2 && document.querySelector(`#categorySelect option[value="${id}"]`), fixture.categoryBId);
        check(await page.locator(`#productName option[value="${fixture.productId}"]`).count() === 0 && await page.locator(`#productName option[value="${fixture.plainProductId}"]`).count() === 1, 'Changing brand did not reset category and product options');
        await page.locator('#categorySelect').selectOption(String(fixture.categoryBId));
        await page.waitForFunction(() => document.querySelectorAll('#productName option').length === 2);
        await page.locator('#productName').evaluate((select, id) => { select.value = id; }, String(fixture.plainProductId));
        await page.locator('#categorySelect').selectOption('');
        await page.waitForFunction(() => document.querySelector('#productName').value === '' && document.querySelectorAll('#productName option').length === 3);
        await page.locator('#categorySelect').selectOption(String(fixture.categoryBId));
        await page.waitForFunction(() => document.querySelectorAll('#productName option').length === 2);
        const duePath = new URL(previousDueUrls[previousDueUrls.length - 1]).pathname;
        check(duePath === appPrefix+'/ajax/public/customer/'+encodeURIComponent(String(fixture.customerId))+'/previous-due', 'Previous due URL was malformed: '+duePath);
        check(!duePath.includes('/new/ajax/') && duePath.split('/RetailNova/').length <= 2, 'Previous due URL contains an inferred or duplicated base segment: '+duePath);
        check(await page.locator('#prevDueDisplay').innerText().then(t=>t.includes('35.00')),'Customer previous due not displayed');
        await page.waitForFunction((id)=>document.querySelector(`#productName option[value="${id}"]`)?.dataset.currentStock==='6',String(fixture.plainProductId));
        check(await page.locator(`#productName option[value="${fixture.plainProductId}"]`).count()===1,'Purchase lots were not aggregated to one product option');
        await page.locator('#saleProductSearch').fill('Plain');
        check(await page.locator('#productName option:not([hidden])').count() === 2, 'Product name filtering failed');
        await page.locator('#saleProductSearch').fill('no-match-qa');
        check(await page.locator('#productName option:not([hidden])').count() === 1, 'No-results filter failed');
        await page.locator('#saleProductSearch').fill('');
        if ([375, 1440].includes(width) && policy === 'required') await page.screenshot({path: path.join(root, 'storage/logs', `sprint03-empty-${locale}-${width}.png`), fullPage: true});

        // Exercise the actual scanner -> batch selector -> shared sales row handler.
        await page.locator('#barcodeInput').fill('SPRINT03-QA-SCAN');
        await page.locator('#barcodeInput').press('Enter');
        await page.waitForFunction(() => document.querySelectorAll('#productDetails tr').length === 1 && document.querySelector('#productDetails tr').dataset.serialPolicyError === '0');
        await assertCartTotalsSynced('serialized product added');
        // Shared page helpers must delegate to the sale allocation calculator instead
        // of replacing Subtotal from the unrelated #productDetails input convention.
        await page.evaluate(() => window.recalcFinancials());
        await assertCartTotalsSynced('shared financial helper delegated');
        // A stale hidden row total used to let the visible line and Subtotal disagree.
        await page.locator('#saleAllocationRows .totalSale').evaluate(input=>{input.value='0';});
        await page.locator('#paidAmount').fill('0');
        await assertCartTotalsSynced('stale allocation total repaired');
        check(await page.locator('.rn-sale-product-name').innerText() === 'Sprint03 QA Serialized', 'Barcode batch selection failed');
        const cartCellOrder=await page.locator('#productDetails tr[data-product-summary="1"]').evaluate(row=>{
          const selectors=[
          '.rn-sale-product-name','.rn-product-stock','.open-sale-serials','.warrantyDays',
          '.rn-sale-quantity','.salePrice','.rn-sale-line-total','.remove-product-row'
          ];
          const indexes=selectors.map(selector=>[...row.querySelectorAll('td')].findIndex(td=>td.querySelector(selector)));
          const cell=row.querySelector('td:last-child').getBoundingClientRect(),button=row.querySelector('.remove-product-row').getBoundingClientRect();
          return {indexes,removeFits:button.left>=cell.left-1&&button.right<=cell.right+1};
        });
        check(cartCellOrder.indexes.join(',')==='0,1,2,3,4,5,6,7','Visible product row fields are missing or out of order');
        check(cartCellOrder.removeFits,'Remove action is clipped by the product row cell');
        const visibleProductText = await page.locator('#productDetails tr[data-product-summary]').innerText();
        check(!/supplier|purchase batch|purchase reference/i.test(visibleProductText), 'Supplier or purchase details appeared in the visible product row');
        await page.locator('[data-sale-qty="1"]').click();
        await assertCartTotalsSynced('serialized quantity changed');
        check(await page.locator('#productDetails .qty').inputValue() === '2', 'Quantity increment failed');
        check(await page.locator('.totalSale').inputValue() === '300', 'Line calculation changed');
        check(await page.locator('#saleSaveButton').isEnabled() === (policy === 'optional'), 'Policy not reflected by Save');
        check(await page.locator('#saleSavePrintButton').isEnabled() === (policy === 'optional'), 'Policy not reflected by Save & Print');
        const preservedCart=await page.locator('#saveSaleForm').evaluate(f=>({rows:f.querySelectorAll('#productDetails tr').length,total:f.querySelector('#grandTotal').value}));
        await page.locator('#paidAmount').fill('25');
        await page.locator('#customerName').selectOption('');
        check(!await page.locator('#saleSaveButton').isEnabled()&&!await page.locator('#saleSavePrintButton').isEnabled(),'Cleared customer allowed saving');
        check(await page.locator('#productDetails tr').count()===preservedCart.rows&&(await page.locator('#paidAmount').inputValue())==='25','Customer clearing destroyed cart/payment');
        for(const id of ['saleProductSearch','barcodeInput','searchBarcodeBtn','brandSelect','categorySelect','productName']) check(!await page.locator('#'+id).isEnabled(),'Cleared customer enabled '+id);
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
        await assertCartTotalsSynced('serialized selection applied');
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
        await page.locator('#productDetails .qty').fill('4');
        check(await page.locator('.stock-note').isVisible(), 'Stock validation missing near quantity');
        const blocked = await page.locator('#saveSaleForm').evaluate(form => {
          const e = new Event('submit', {bubbles: true, cancelable: true}); form.dispatchEvent(e); return e.defaultPrevented;
        });
        check(blocked && posts.length === 0, 'Invalid stock quantity submitted');
        await page.locator('#productDetails .qty').fill('2');
        if (policy === 'required') {
          changePolicy = true; await page.evaluate(() => window.dispatchEvent(new Event('focus')));
          await page.waitForFunction(() => document.querySelector('#productDetails tr').dataset.serialRequired === '0');
          check(await page.locator('.serial-policy-note').innerText().then(t => t.length > 0), 'Open-form policy refresh failed');
          changePolicy = false; await page.evaluate(() => window.dispatchEvent(new Event('focus')));
          await page.waitForFunction(() => document.querySelector('#productDetails tr').dataset.serialRequired === '1');
        }
        await page.locator('#productName').selectOption(String(fixture.plainProductId));
        await page.waitForFunction(() => document.querySelectorAll('#productDetails tr').length === 2 && document.querySelectorAll('#productDetails tr')[1].dataset.serialized === '0');
        await assertCartTotalsSynced('nonserialized product added');
        check(await page.locator('#saleSaveButton').isEnabled(), 'Nonserialized row incorrectly blocked sale');
        await page.locator('#discountAmount').fill('10'); await page.locator('#additionalChargeName').fill('QA Delivery'); await page.locator('#additionalChargeAmount').fill('5'); await page.locator('#paidAmount').fill('100');
        await assertCartTotalsSynced('discount, charge and payment edited');
        check(await page.locator('#totalSaleAmount').inputValue() === '450', 'Subtotal changed');
        check(await page.locator('#grandTotal').inputValue() === '445', 'Discount/charge calculation changed');
        check(await page.locator('#dueAmount').inputValue() === '345', 'Paid/due calculation changed');
        await page.locator('#rnReceiveAmountBtn').click(); check(await page.locator('#dueAmount').inputValue() === '0', 'Receive Amount did not recalculate due'); await page.locator('#paidAmount').fill('100');
        await assertCartTotalsSynced('Full Payment toggled');
        const metrics = await page.evaluate(() => ({overflow: document.documentElement.scrollWidth > innerWidth, card: getComputedStyle(document.querySelector('#productDetails tr')).display, columns: getComputedStyle(document.querySelector('.rn-sale-layout')).gridTemplateColumns.split(' ').length, availableWidth:document.getElementById('saveSaleForm').clientWidth}));
        check(!metrics.overflow, `Page overflow ${locale}/${policy}/${width}`);
        check(await page.locator('#discountAmount').isEditable(), 'Bill-level discount must remain editable');
        check(await page.locator('#productDetails [name*="discount" i]').count() === 0, 'Per-product discount controls must not be added');
        check(await page.locator('#paymentDetails.rn-sale-summary').count() === 1 && await page.locator('.rn-sale-additional').count() === 1, 'Billing and additional information cards missing');
        const flow=await page.locator('#saveSaleForm').evaluate(f=>{ const cards=[...f.querySelectorAll('.rn-sale-panel')]; const bounds=cards.map(c=>c.getBoundingClientRect()); return {overlap:bounds.some((r,i)=>i>0&&r.top<bounds[i-1].bottom-1),fixed:cards.some(c=>['absolute','fixed'].includes(getComputedStyle(c).position)),clipped:[...f.querySelectorAll('input:not([type=hidden]),select,textarea')].some(c=>c.getBoundingClientRect().right>f.getBoundingClientRect().right+1)}; });
        if(width<=992) check(!flow.overlap&&!flow.fixed&&!flow.clipped,'Mobile cards overlap or clip controls');
        check(metrics.card === (width <= 992 ? 'grid' : 'table-row'), 'Card/table breakpoint mismatch');
        check(metrics.columns === (width >= 1280 && metrics.availableWidth >= 900 ? 2 : 1), 'Billing sidebar breakpoint mismatch');
        const names = await page.locator('#saveSaleForm').evaluate(form => {const data = new FormData(form); return {qty: data.getAll('qty[]'), purchases: data.getAll('purchaseData[]'), buy: data.getAll('buyPrice[]'), paid: data.getAll('paidAmount'), grand: data.getAll('grandTotal')};});
        check(names.qty.join(',') === '2,1' && names.purchases.join(',') === fixture.purchaseId + ',' + fixture.plainPurchaseId && names.buy.length === 2, 'Duplicate or missing authoritative item fields');
        check(names.paid.length === 1 && names.grand.length === 1, 'Duplicate authoritative financial inputs');
        if ([375, 1440].includes(width) && policy === 'required') {await page.evaluate(() => window.scrollTo(0, 0)); await page.screenshot({path: path.join(root, 'storage/logs', `sprint03-populated-${locale}-${width}.png`), fullPage: true});}
        check(await page.locator('#totalOutstandingDisplay').innerText().then(t=>t.includes('380')),'Previous plus current due incorrect');
        // Capture real browser form submissions offline and test the second submit is blocked.
        {
          const removalSnapshot = () => page.locator('.remove-product-row').last().evaluate(button => {const form=button.closest('form');return JSON.stringify([...new FormData(form)]);});
      const beforeRemoval=await removalSnapshot(),beforeCount=await page.locator('#productDetails tr').count();
          await page.locator('.remove-product-row').last().click();await page.locator('.swal2-popup').waitFor({state:'visible'});
      check(await page.locator('#productDetails tr').count()===beforeCount,'Removed before confirmation');
      check(await removalSnapshot()===beforeRemoval,'Mutated payload or totals before confirmation');
      await page.locator('.swal2-cancel').click();await page.locator('.swal2-popup').waitFor({state:'hidden'});
      check(await removalSnapshot()===beforeRemoval,'Cancel changed form or serial payload');
          await page.locator('.remove-product-row').last().click();await page.locator('.swal2-confirm').click();
      await page.waitForFunction(()=>document.querySelectorAll('#productDetails tr').length===1);
      check(await page.locator('#productDetails tr').count()===1,'Confirmed removal failed');
          await page.locator('#productName').selectOption(String(fixture.plainProductId));
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
        // A balance lookup outage must not keep the validated product controls locked,
        // and must still block both financial submission actions.
        await page.evaluate(() => window.dispatchEvent(new Event('pageshow')));
        const rowsBeforeBalanceFailure = await page.locator('#productDetails tr').count();
        await page.locator('#customerName').selectOption('');
        check(await page.locator('#productDetails tr').count() === rowsBeforeBalanceFailure, 'Clearing customer removed existing cart rows');
        for (const id of ['saleProductSearch','barcodeInput','searchBarcodeBtn','brandSelect','categorySelect','productName']) check(!await page.locator('#'+id).isEnabled(), 'Cleared customer left '+id+' enabled');
        failDue = true;
        await page.locator('#customerName').selectOption(String(fixture.customerId));
        await page.waitForFunction(() => document.querySelector('#customerName').dataset.customerReady === '1' && document.querySelector('#customerName').dataset.dueReady === '0' && !document.querySelector('#productName').disabled);
        for (const id of ['saleProductSearch','barcodeInput','searchBarcodeBtn','brandSelect','categorySelect','productName']) check(await page.locator('#'+id).isEnabled(), 'Validated customer did not enable '+id+' while balance lookup failed');
        check(!await page.locator('#saleSaveButton').isEnabled() && !await page.locator('#saleSavePrintButton').isEnabled(), 'Balance lookup failure allowed submission');
        const blockedByDue = await page.locator('#saveSaleForm').evaluate(form => { const button = document.querySelector('#saleSaveButton'); const event = new SubmitEvent('submit', {bubbles: true, cancelable: true, submitter: button}); form.dispatchEvent(event); return event.defaultPrevented; });
        check(blockedByDue, 'Form submit handler allowed a sale without verified previous balance');
        check(await page.locator('#saleCustomerGateMessage').innerText() === await page.evaluate(() => window.RNTranslate('sale_customer_balance_failed')), 'Balance failure feedback was missing or not localized');
        check(await page.locator('#productDetails tr').count() === rowsBeforeBalanceFailure, 'Customer switch changed existing cart rows');
        failDue = false;
        await page.locator('#customerName').selectOption('');
        await page.locator('#customerName').selectOption(String(fixture.customerId));
        await page.waitForFunction(() => document.querySelector('#customerName').dataset.customerReady === '1' && document.querySelector('#customerName').dataset.dueReady === '1');
        check(await page.locator('#saleCustomerGateMessage').innerText() === '', 'Clear-and-reselect did not recover customer readiness');
        await page.locator('#productName').selectOption(String(fixture.plainProductId));
        await page.waitForFunction(() => document.querySelectorAll('#productDetails tr[data-product-summary]').length === 1);
        await page.locator('#productDetails tr[data-product-summary] .warrantyDays').fill('365');
        await page.locator('#productDetails tr[data-product-summary] .qty').fill('4');
        await page.waitForFunction(() => document.querySelectorAll('#saleAllocationRows tr[data-product-group]').length === 2);
        check(await page.locator('#productDetails tr[data-product-summary]').count()===1,'Multi-batch product rendered more than one visible cart row');
        const allocations = await page.locator('#saleAllocationRows tr[data-product-group]').evaluateAll(rows => rows.map(row => ({purchase:row.dataset.purchaseId,qty:row.querySelector('.qty').value,warranty:row.querySelector('.warrantyDays').value})));
        check(allocations.map(row=>row.purchase).join(',')===fixture.plainPurchaseId+','+fixture.plainExtraPurchaseId,'Aggregate product quantity did not allocate FIFO across purchase lots');
        check(allocations.map(row=>row.qty).join(',')==='3,1','Aggregate product quantity was not split to available lot stock');
        check(allocations.map(row=>row.warranty).join(',')==='365,365','Warranty value was not preserved across lot allocation');
        await page.locator('#productDetails tr[data-product-summary] .salePrice').fill('200');
        await assertCartTotalsSynced('multi-lot selling price edited');
        check(await page.locator('#saleAllocationRows tr[data-product-group] .salePrice').evaluateAll(inputs=>inputs.every(input=>input.value==='200')), 'Selling price did not propagate across purchase lots');
        check(await page.locator('#productDetails tr[data-product-summary] .rn-sale-line-total').innerText()==='800.00', 'Edited price did not recalculate the product line total');
        await page.locator('#paidAmount').fill('1200');
        await page.locator('#productDetails tr[data-product-summary] .remove-product-row').click();
        await page.locator('.swal2-confirm').click();
        await page.locator('#saleEmptyState').waitFor({state:'visible'});
        await assertCartTotalsSynced('last product removed');
        check(await page.locator('#productDetails tr[data-product-summary]').count()===0,'Last cart row remained after confirmed removal');
        const emptyPayment=await page.locator('#paymentDetails').evaluate(card=>({subtotal:card.querySelector('#totalSaleAmount').value,grand:card.querySelector('#grandTotal').value,paid:card.querySelector('#paidAmount').value,due:card.querySelector('#dueAmount').value,save:document.querySelector('#saleSaveButton').disabled,print:document.querySelector('#saleSavePrintButton').disabled}));
        check(emptyPayment.subtotal==='0'&&emptyPayment.grand==='0'&&emptyPayment.paid==='1200'&&emptyPayment.due==='0','Removing the last item left stale subtotal/grand/due values or changed entered payment');
        check(emptyPayment.save&&emptyPayment.print,'An empty cart remained submittable with a retained payment amount');
        check(pageErrors.length===0,'Browser script errors: '+pageErrors.join(';'));
        cases++;results.push({locale,policy,width,appPrefix,pass:true}); await page.close();
      }
    }
    const report={type:'isolated SQLite HTML/API browser tests; no local database writes',cases,checks,results};
    fs.writeFileSync(path.join(root,'storage/logs/sprint03-browser-results.json'),JSON.stringify(report,null,2));
    console.log(JSON.stringify({cases,checks,pass:true}));
  } finally { await browser.close(); }
})().catch(e=>{console.error(e);process.exitCode=1;});
