// Uses existing Playwright/Chrome only. Generate test-only HTML with
// RN_EXPORT_BROWSER_FIXTURES=1 php artisan test --filter=Sprint02NavigationTest.
// All requests are intercepted: no live database, application session, or external services.
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
const root=path.resolve(__dirname,'../..'),publicRoot=path.join(root,'public');
let cases=0,checks=0;const results=[];
function check(value,message){assert.ok(value,message);checks++;}
(async()=>{
 const browser=await chromium.launch({headless:true,...(process.env.CHROME_PATH?{executablePath:process.env.CHROME_PATH}:{})});
 try{
  for(const locale of ['en','bn']){
   for(const name of ['purchase-new','purchase-list','purchase-view','purchase-edit','customer']){
    const page=await browser.newPage();let posts=0;
    if(['purchase-list','purchase-view'].includes(name))await page.addInitScript(()=>{window.__purchasePrints=0;window.print=()=>{window.dispatchEvent(new Event('beforeprint'));window.__purchasePrints++;window.dispatchEvent(new Event('afterprint'));};});
    const fixture=fs.readFileSync(path.join(root,'storage/framework',`sprint02-${name}-${locale}.html`),'utf8');
    await page.route('**/*',async route=>{
     const url=new URL(route.request().url());
     if(route.request().method()==='POST')posts++;
     if(url.hostname==='cdn.jsdelivr.net'&&url.pathname==='/npm/sweetalert2@11')return route.fulfill({contentType:'application/javascript',body:fs.readFileSync(path.join(publicRoot,'vendor/sweetalert/sweetalert.all.js'))});
     if(url.hostname==='retailnova.test'&&url.pathname==='/fixture'){return route.fulfill({contentType:'text/html',body:fixture});}
     if(['localhost','retailnova.test'].includes(url.hostname)){
      let asset=url.pathname.replace(/^\/RetailNova\//,'/').replace(/^\/public\//,'/');
      const file=path.resolve(publicRoot,'.'+asset);
      if(file.startsWith(publicRoot+path.sep)&&fs.existsSync(file)&&fs.statSync(file).isFile()){
       const mime={'.css':'text/css','.js':'application/javascript','.png':'image/png','.jpg':'image/jpeg','.svg':'image/svg+xml','.woff':'font/woff','.woff2':'font/woff2','.ttf':'font/ttf'}[path.extname(file)]||'application/octet-stream';
       return route.fulfill({body:fs.readFileSync(file),contentType:mime});
      }
     }
     return route.abort();
    });
    await page.goto('http://retailnova.test/fixture',{waitUntil:'load'});
    for(const width of [375,390,576,768,992,1280]){
     await page.setViewportSize({width,height:900});
     // Open the rendered sidebar using its actual existing toggle.
     if(!['purchase-new','purchase-edit','purchase-list','purchase-view'].includes(name)&&await page.locator('.iq-sidebar').evaluate(el=>el.getBoundingClientRect().left<0)){
      await page.locator('.iq-top-navbar .wrapper-menu').click();
      await page.waitForFunction(()=>Math.abs(document.querySelector('.iq-sidebar').getBoundingClientRect().left)<1);
     }
     const metrics=await page.evaluate(()=>({locale:document.documentElement.lang,overflow:document.documentElement.scrollWidth>innerWidth,
      labels:[...document.querySelectorAll('.rn-business-navigation a>span')].filter(el=>el.clientWidth>0&&el.scrollWidth>el.clientWidth+1).map(el=>el.textContent),settings:document.querySelectorAll('#settings').length}));
     check(metrics.locale===locale,'Locale mismatch');check(!metrics.overflow,`${name} page overflow at ${width}`);check(!metrics.labels.length,'Sidebar label overflow');check(metrics.settings===1,'Duplicate Settings');
     if(name==='purchase-view'){check(await page.locator('#purchaseInvoicePrint .rn-invoice-actions').isVisible(),'Invoice actions hidden on screen');}

     if(['purchase-view','purchase-edit'].includes(name)){
      check(await page.locator('[data-invoice-back]').getAttribute('href').then(h=>h.endsWith('/purchase/list')),'Back must use list route');
      check(await page.locator('.rn-invoice-breadcrumb [aria-current="page"]').count()===1,'Invoice breadcrumb missing');
      check(await page.locator('.rn-invoice-header h1').isVisible(),'Invoice title missing');
      if(name==='purchase-view'){
       check(await page.locator('.rn-invoice-summary').innerText().then(t=>t.includes('120.00')),'View totals missing');
       await page.locator('[data-invoice-serials] summary').click();check(await page.locator('.rn-invoice-serial').isVisible(),'Serial details unavailable');
       await page.locator('[data-invoice-serials] summary').click();
       await page.locator('#purchaseInvoicePrintButton').click();check(await page.evaluate(()=>window.__purchasePrints>0),'Invoice print failed');
       check(await page.locator('[data-invoice-serials]').evaluate(e=>!e.open),'Print must restore collapsed serials');
      }else{
       check(await page.locator('[data-invoice-cancel]').getAttribute('href').then(h=>h.endsWith('/purchase/invoices/1')),'Cancel must use invoice route');
       check(await page.locator('#purchaseInvoiceSaveButton').getAttribute('type')==='submit','Save contract missing');
       check(await page.locator('#purchaseInvoiceSubtotal').innerText()==='120.00','Subtotal preview incorrect');
       check(await page.locator('#purchaseInvoiceDuePreview').innerText()==='120.00','Due preview incorrect');
      }
      if([375,1280].includes(width)){await page.evaluate(()=>window.scrollTo(0,0));await page.screenshot({path:path.join(root,'storage/framework',`sprint02-${name}-${locale}-${width}.png`),fullPage:true});}
     }
     if(name==='purchase-list'){
      check(await page.locator('[data-purchase-invoice]').count()===1,'Header duplicated');check(await page.locator('form[method="GET"]').isVisible(),'Invoice filters hidden');
      check(!(await page.locator('#purchaseBulkDeleteButton').isEnabled()),'Bulk delete enabled without selection');
      const fields=await page.locator('.rn-purchase-filters .form-control').evaluateAll(els=>els.map(e=>({height:e.getBoundingClientRect().height,contained:e.getBoundingClientRect().right<=innerWidth})));
      check(fields.length===7&&fields.every(e=>e.height===40&&e.contained),'Filter sizing inconsistent');
      check(await page.locator('.rn-purchase-header a').getAttribute('href').then(h=>h.endsWith('/add/purchase')),'New Purchase link changed');
      check(await page.locator('#purchaseLegacyRecords').evaluate(e=>!e.open),'Legacy section should initially collapse');
      await page.locator('#purchaseLegacyRecords summary').click();check(await page.locator('[data-legacy-purchase]').isVisible(),'Legacy compatibility unavailable');
      await page.locator('#purchaseLegacyRecords summary').click();
      await page.locator('#purchaseSelectPage').check();check(await page.locator('#purchaseBulkDeleteButton').isEnabled(),'Bulk selection control failed');
      const selection=await page.locator('#purchaseInvoiceBulkForm').evaluate(e=>Array.from(new FormData(e).getAll('ids[]')));
      const invoiceId=await page.locator('[data-purchase-invoice]').getAttribute('data-purchase-invoice');check(selection.length===1&&selection[0]===invoiceId,'Bulk IDs are not invoice headers');
      check(await page.locator('#purchaseSelectionCount').innerText().then(t=>t.endsWith(': 1')),'Selection count wrong');
      await page.locator('#purchaseBulkDeleteButton').click();await page.locator('.swal2-popup').waitFor({state:'visible'});check(await page.locator('.swal2-popup').isVisible(),'Delete confirmation missing');
      await page.locator('.swal2-cancel').click();await page.locator('.swal2-popup').waitFor({state:'hidden'});check(posts===0,'Cancelled delete sent a request');
      await page.locator('#purchaseSelectPage').uncheck();check(!(await page.locator('#purchaseBulkDeleteButton').isEnabled()),'Cleared selection enabled bulk delete');
      await page.locator('#purchaseListPrintButton').click();check(await page.evaluate(()=>window.__purchasePrints===1),'Print toolbar failed');
      check(await page.locator('#purchaseLegacyRecords').evaluate(e=>!e.open),'Print changed legacy expansion state');
      await page.evaluate(()=>{window.__purchasePrints=0;});
      const action=page.locator('.rn-purchase-row-actions');const actionMetrics=await action.evaluate(e=>({left:e.getBoundingClientRect().left,right:e.getBoundingClientRect().right,width:innerWidth,position:getComputedStyle(e.parentElement).position,parent:e.parentElement.getBoundingClientRect().toJSON(),scroll:e.closest('.table-responsive').getBoundingClientRect().toJSON()}));check(actionMetrics.right<=actionMetrics.width&&actionMetrics.left>=0,'Sticky View/Edit actions inaccessible: '+JSON.stringify(actionMetrics));
      const money=await page.locator('.rn-purchase-money').allTextContents();check(money.length===3&&money[0].includes('120.00')&&money[1].includes('0.00')&&money[2].includes('120.00'),'Invoice totals changed');
      if(width===375){check(await page.locator('[data-purchase-invoice]').evaluate(e=>getComputedStyle(e).display==='grid'),'Mobile invoice cards missing');check(await page.locator('[data-purchase-invoice] td[data-label]').evaluateAll(es=>es.length===9&&es.every(e=>getComputedStyle(e).display!=='none'&&e.scrollWidth<=e.clientWidth+1)),'Essential mobile data clipped');}
      check(await page.locator('.rn-purchase-table-scroll').getAttribute('aria-describedby')===(width<=575?'purchaseMobileHint':'purchaseScrollHint'),'Responsive instructions mismatch');
      if([375,1280].includes(width)){await page.evaluate(()=>window.scrollTo(0,0));await page.screenshot({path:path.join(root,'storage/framework',`sprint02-purchase-list-${locale}-${width}.png`),fullPage:true});}
     }

     if(name==='purchase-new'){
      await page.locator('#supplierName').selectOption({index:1});
      await page.locator('#productName').selectOption({index:0});
      await page.locator('#addProductRow').press('Enter');
      await page.mouse.move(width-10,850);
      const row=page.locator('#productDetails tr');
      await row.locator('.quantity').fill('2');await row.locator('input[name^="buyPrice"]').fill('10');await row.locator('input[name^="salePriceExVat"]').fill('15');
      await row.locator('input[name^="salePriceExVat"]').press('Tab');
      const snapshot=()=>page.locator('#savePurchase').evaluate(f=>JSON.stringify([...new FormData(f)]));
      await page.evaluate(()=>{window.__originalFinancials ||= window.recalcFinancials;window.__removalRecalculations=0;window.recalcFinancials=()=>{window.__removalRecalculations++;window.__originalFinancials();};});
      const before=await snapshot();
      check(Number(await page.locator('#grandTotal').inputValue())===20,'Purchase total changed');
      await page.locator('.remove-row').press('Enter');await page.locator('.swal2-popup').waitFor({state:'visible'});
      check(await row.count()===1&&await snapshot()===before,'Purchase mutated before confirmation');
      check(await page.evaluate(()=>window.__removalRecalculations===0),'Recalculated before confirmation');
      await page.locator('.swal2-cancel').click();await page.locator('.swal2-popup').waitFor({state:'hidden'});
      check(await snapshot()===before,'Purchase cancel changed payload');
      check(await page.evaluate(()=>window.__removalRecalculations===0),'Cancel recalculated totals');
      const layout=await row.evaluate(e=>({width:innerWidth,right:e.getBoundingClientRect().right,cards:getComputedStyle(e).display,inputs:[...e.querySelectorAll('input:not([type="hidden"])')].map(x=>({width:x.clientWidth,scroll:x.scrollWidth}))}));
      if(width<=992)check(layout.cards==='grid'&&layout.right<=width&&layout.inputs.every(x=>x.width>0),'Purchase mobile card invalid');
      check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'Populated purchase page overflow');
      await page.locator('.remove-row').press('Enter');await page.locator('.swal2-confirm').click();await page.waitForFunction(()=>!document.querySelector('#productDetails tr'));await page.locator('.swal2-container').waitFor({state:'hidden'});
      check(Number(await page.locator('#grandTotal').inputValue())===0,'Purchase confirmed removal total stale');
      check(await page.evaluate(()=>window.__removalRecalculations===1),'Confirmed removal recalculated more than once');
     }
     if(name==='purchase-edit'){
      check(await page.locator('#purchaseInvoiceEditTable tbody>tr').count()===1,'Edit must use table rows');
      check(await page.locator('textarea[name$="[serials]"]').count()===0,'CSV editor returned');
      await page.locator('[data-edit-serials]').click();await page.locator('#serialModal').waitFor({state:'visible'});
      check(await page.locator('#serialModal').isVisible(),'Original modal not visible');
      check(await page.locator('#serialNumberBox input').first().inputValue()==='QA-SERIAL','Existing serial not preloaded');
      await page.locator('#add-serial').click();await page.locator('#serialNumberBox input').last().fill('BROWSER-A');
      await page.locator('#add-serial').click();await page.locator('#serialNumberBox input').last().fill('BROWSER-B');
      if(locale==='bn'&&width===375){await page.waitForFunction(()=>getComputedStyle(document.querySelector('#serialModal')).opacity==='1');await page.screenshot({path:path.join(root,'storage/framework/sprint02-purchase-modal-bn-375.png')});}
      await page.locator('#saveSerials').click();await page.locator('#serialModal').waitFor({state:'hidden'});
      const values=await page.locator('#purchaseInvoiceRows [data-new-serial]').evaluateAll(els=>els.map(el=>el.value));
      check(values.join(',')==='BROWSER-A,BROWSER-B','Multiple serial payload lost');
      await page.locator('[data-edit-serials]').click();await page.locator('#serialModal').waitFor({state:'visible'});check(await page.locator('#serialNumberBox input').count()===3,'Popup reopen lost serials');
      await page.locator('#serialNumberBox .serial-input-row:not([data-id]) .remove-serial').first().click();
      await page.locator('#serialNumberBox .serial-input-row:not([data-id]) .remove-serial').first().click();
      await page.locator('#saveSerials').click();await page.locator('#serialModal').waitFor({state:'hidden'});
      check(await page.locator('[data-new-serial]').count()===0,'Remove new serial failed');
      check(await page.locator('[data-existing-serial]').count()===1,'Empty optional serials removed history');
      await page.locator('#purchaseInvoiceAddRow').click();check(await page.locator('#purchaseInvoiceRows>tr').count()===2,'Add table row failed');
      await page.locator('[data-edit-serials]').last().click();await page.locator('#serialModal').waitFor({state:'visible'});
      await page.locator('#serialNumberBox input').fill('NEW-ROW-SERIAL');await page.locator('#saveSerials').click();await page.locator('#serialModal').waitFor({state:'hidden'});
      const association=await page.locator('#purchaseInvoiceRows>tr').last().evaluate(el=>({key:el.dataset.key,name:el.querySelector('[data-new-serial]').name}));
      check(association.name===`rows[${association.key}][serials][]`,'New row serial key mismatch');
      const removalSnapshot = () => page.locator('[data-remove-item]').last().evaluate(button => {const form=button.closest('form');return JSON.stringify([...new FormData(form)]);});
      const beforeRemoval=await removalSnapshot(),beforeCount=await page.locator('#purchaseInvoiceRows>tr').count();
      await page.locator('[data-remove-item]').last().click();await page.locator('.swal2-popup').waitFor({state:'visible'});
      check(await page.locator('#purchaseInvoiceRows>tr').count()===beforeCount,'Removed before confirmation');
      check(await removalSnapshot()===beforeRemoval,'Mutated payload or totals before confirmation');
      await page.locator('.swal2-cancel').click();await page.locator('.swal2-popup').waitFor({state:'hidden'});
      check(await removalSnapshot()===beforeRemoval,'Cancel changed form or serial payload');
      await page.locator('[data-remove-item]').last().click();await page.locator('.swal2-confirm').click();
      await page.waitForFunction(()=>document.querySelectorAll('#purchaseInvoiceRows>tr').length===1);
      check(await page.locator('#purchaseInvoiceRows>tr').count()===1,'Confirmed removal failed');
      check(await page.locator('[data-new-serial]').count()===0,'Removed row leaked serial payload');

     }
     if(name==='customer'){check(!(await page.locator('#mail').getAttribute('required')),'Email required');}
     if(locale==='bn'&&width===375&&name==='purchase-list'){await page.evaluate(()=>window.scrollTo(0,0));await page.screenshot({path:path.join(root,'storage/framework/sprint02-navigation-bn-375.png')});}
     cases++;results.push({locale,name,width,pass:true});
    }
    if(name==='purchase-edit'){
     const guard=await page.evaluate(()=>{
      const form=document.getElementById('purchaseInvoiceEdit'),qty=form.querySelector('[data-qty]'),save=document.getElementById('purchaseInvoiceSaveButton');
      const original=qty.value;qty.value='0';qty.dispatchEvent(new Event('input',{bubbles:true}));
      const invalid=new Event('submit',{bubbles:true,cancelable:true});form.dispatchEvent(invalid);
      const invalidSafe=invalid.defaultPrevented&&!save.disabled;
      qty.value=original;qty.dispatchEvent(new Event('input',{bubbles:true}));
      const decisions=[];document.addEventListener('submit',e=>{decisions.push(e.defaultPrevented);e.preventDefault();});
      form.dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));
      form.dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));
      return {invalidSafe,decisions,disabled:save.disabled};
     });
     check(guard.invalidSafe,'Serial validation must not lock Save');check(guard.decisions.join(',')==='false,true'&&guard.disabled,'Duplicate submit guard failed');check(posts===0,'Guard tests must not submit to any database');
     cases++;results.push({locale,name,submissionGuard:true,pass:true});
    }
    if(['purchase-view','purchase-list'].includes(name)){
     await page.emulateMedia({media:'print'});
     const table=page.locator(name==='purchase-view'?'#purchaseInvoicePrint table':'#purchaseInvoiceTable');
     check(await table.isVisible(),'Purchase table hidden during printing');
     check(await table.evaluate(el=>getComputedStyle(el).visibility==='visible'),'Print contents invisible');
     check(!(await page.locator('.rn-purchase-no-print').first().isVisible()),'Print-only controls visible');
     cases++;results.push({locale,name,print:true,pass:true});
    }
    await page.close();
   }
  }
  const report={type:'offline isolated SQLite-rendered fixtures',cases,checks,results};
  fs.writeFileSync(path.join(root,'storage/logs/sprint02-browser.json'),JSON.stringify(report,null,2));
  console.log(JSON.stringify({cases,checks,pass:true,type:report.type}));
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});
