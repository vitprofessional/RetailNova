// Real SQLite-rendered purchase page/API fixtures; every request intercepted.
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
const root=path.resolve(__dirname,'../..'),publicRoot=path.join(root,'public');
const bundle=fs.readFileSync(path.join(publicRoot,'eshop/assets/js/backend-bundle.min.js'),'utf8');
const end=bundle.indexOf(',function(t,e){"object"==typeof exports',bundle.indexOf('.noConflict'));assert.ok(end>0);
const jquery=bundle.slice(0,end)+';';let cases=0,checks=0;
function check(v,m){assert.ok(v,m);checks++;}
(async()=>{const browser=await chromium.launch({headless:true,executablePath:process.env.CHROME_PATH});try{
for(const locale of ['en','bn'])for(const width of [320,360,375,390,430,576,768,992,1280,1440]){
 const page=await browser.newPage({viewport:{width,height:900}}),errors=[],posts=[];
 const stem=path.join(root,'storage/logs','sprint04-purchase-'+locale),html=fs.readFileSync(stem+'.html','utf8'),fixture=JSON.parse(fs.readFileSync(stem+'.json'));
 page.on('pageerror',e=>errors.push(e.message));page.on('dialog',dialog=>dialog.dismiss());
 await page.route('**/*',async route=>{
  const req=route.request(),url=new URL(req.url());
  if(url.hostname==='ajax.googleapis.com')return route.fulfill({contentType:'application/javascript',body:jquery});
  if(url.hostname==='cdn.jsdelivr.net'&&url.pathname==='/npm/sweetalert2@11')return route.fulfill({contentType:'application/javascript',body:fs.readFileSync(path.join(publicRoot,'vendor/sweetalert/sweetalert.all.js'))});
  if(url.hostname!=='localhost')return route.abort();
  const normalized=url.pathname.replace(/^\/RetailNova\//,'/');
  if(normalized==='/add/purchase')return route.fulfill({contentType:'text/html',body:html});
  if(normalized==='/purchase/list')return route.fulfill({contentType:'text/html',body:'<h1>QA purchase history</h1>'});
  if(req.method()==='POST'){posts.push(new URLSearchParams(req.postData()));return route.fulfill({contentType:'text/html',body:'<h1>QA captured purchase</h1>'});}
  if(fixture.responses[normalized])return route.fulfill({contentType:'application/json',body:JSON.stringify(fixture.responses[normalized])});
  const file=path.resolve(publicRoot,'.'+normalized.replace(/^\/public\//,'/'));
  if(file.startsWith(publicRoot+path.sep)&&fs.existsSync(file)&&fs.statSync(file).isFile())return route.fulfill({contentType:{'.js':'application/javascript','.css':'text/css','.woff2':'font/woff2','.ttf':'font/ttf','.svg':'image/svg+xml','.png':'image/png'}[path.extname(file)]||'application/octet-stream',body:fs.readFileSync(file)});
  return route.abort();
 });
 await page.goto('http://localhost/RetailNova/add/purchase');await page.locator('#loading').waitFor({state:'hidden'});
 check(await page.locator('html').getAttribute('lang')===locale,'Locale mismatch');
 check(await page.locator('#purchaseEmptyState').isVisible(),'No purchase empty state');
 check(!await page.locator('#productName').isEnabled(),'Supplier gate bypassed');
 check(!await page.locator('[data-purchase-debug]').isVisible(),'Debug visible');
 const emptyGeometry=await page.locator('#savePurchase').evaluate(f=>{
  const rect=e=>{const r=e.getBoundingClientRect();return {left:r.left,right:r.right,top:r.top,bottom:r.bottom,width:r.width,height:r.height}};
  const info=f.querySelector('.rn-purchase-information'),items=f.querySelector('#purchaseItemsTitle').closest('.card'),empty=f.querySelector('#purchaseEmptyState'),summary=f.querySelector('.rn-purchase-summary');
  const required=['#businessId','#date','#supplierName','[data-target="#supplier"]','#invoice','#reference'].map(s=>f.querySelector(s));
  const summaryFields=['#discountStatus','#discountAmount','#discountPercent','#grandTotal','#paidAmount','#dueAmount','#specialNote','#purchaseSaveButton','[data-purchase-leave]'].map(s=>f.querySelector(s));
  return {info:rect(info),required:required.map(rect),requiredVisible:required.every(e=>e&&e.getClientRects().length),items:rect(items),empty:rect(empty),emptyVisible:getComputedStyle(empty).display!=='none',summary:rect(summary),summaryVisible:summaryFields.map(e=>e&&e.getClientRects().length>0),fixedSections:[info,items,summary].map(e=>({position:getComputedStyle(e).position,maxHeight:getComputedStyle(e).maxHeight,scrollHeight:e.scrollHeight,clientHeight:e.clientHeight}))};
 });
 check(emptyGeometry.requiredVisible&&emptyGeometry.required.every(r=>r.left>=emptyGeometry.info.left-1&&r.right<=emptyGeometry.info.right+1),'Mobile purchase information clipped');
 check(emptyGeometry.emptyVisible&&emptyGeometry.empty.bottom<=emptyGeometry.items.bottom+1,'Empty state clipped by item card');
 check(emptyGeometry.summaryVisible.every(Boolean),'Purchase summary control clipped');
 if(width<1200)check(emptyGeometry.summary.top>=emptyGeometry.items.bottom-1,'Summary overlaps empty Purchased Items');
 check(emptyGeometry.fixedSections.every(s=>s.position!=='absolute'&&s.maxHeight==='none'&&s.scrollHeight<=s.clientHeight+1),'Content section constrained/clipped');
 const baseLayout=await page.locator('.rn-purchase-layout').evaluate(e=>({cols:getComputedStyle(e).gridTemplateColumns.split(' ').length,overflow:document.documentElement.scrollWidth>innerWidth}));
 check(baseLayout.cols===(width>=1200?2:1),'Summary placement mismatch');check(!baseLayout.overflow,'Empty purchase overflow');
 check(await page.locator('[data-purchase-leave]').count()===2,'Back/Cancel missing');
 await page.locator('#supplierName').selectOption(String(fixture.supplierId));check(await page.locator('#productName').isEnabled(),'Supplier did not enable products');
 const selectedProductHelp=locale==='en'?'Product selected. Select Add To List to include it.':'পণ্য নির্বাচন করা হয়েছে। তালিকায় যোগ করতে বোতামটি চাপুন।';
 const chooseProductHelp=locale==='en'?'Supplier selected. Choose a product to add.':'মহাজন নির্বাচন করা হয়েছে। যোগ করতে একটি পণ্য বেছে নিন।';
 check(await page.locator('[data-purchase-supplier-help]').innerText()===(await page.locator('#productName').inputValue()?selectedProductHelp:chooseProductHelp),'Supplier helper did not follow actual selection state');
 for(const id of fixture.productIds){
  await page.locator('#productName').selectOption(String(id));
  check(await page.locator('[data-purchase-supplier-help]').innerText()===(locale==='en'?'Product selected. Select Add To List to include it.':'পণ্য নির্বাচন করা হয়েছে। তালিকায় যোগ করতে বোতামটি চাপুন।'),'Product helper did not follow selection state');
  await page.locator('#addProductRow').press('Enter');
 }
 await page.waitForFunction(()=>document.querySelectorAll('#productDetails tr').length===2);
 const rows=page.locator('#productDetails tr');
 for(let i=0;i<2;i++){
  const row=rows.nth(i);await row.locator('.quantity').fill(i===0?'2':'3');await row.locator('input[name^="buyPrice"]').fill(i===0?'10':'20');await row.locator('input[name^="salePriceExVat"]').fill(i===0?'15':'25');
 }
 await rows.first().locator('.include-vat').check();await rows.first().locator('.vat-percent').fill('10');
 check(Number(await rows.first().locator('.sale-price-inc').inputValue())===16.5,'VAT sale price changed');
 check(Number(await page.locator('#grandTotal').inputValue())===80,'Purchase cost includes sale VAT');
 await page.locator('#discountStatus').selectOption('2');await page.locator('#discountPercent').fill('10');await page.locator('#paidAmount').fill('30');await page.locator('#paidAmount').press('Tab');
 check(Number(await page.locator('#grandTotal').inputValue())===72,'Percentage discount changed');check(Number(await page.locator('#dueAmount').inputValue())===42,'Paid/due changed');
 await rows.first().locator('.open-serials').press('Enter');await page.locator('#serialModal').waitFor({state:'visible'});
 await page.locator('#serialNumberBox input[name="serialNumber[]"]').first().fill('SPRINT04-BROWSER-A1');await page.locator('#add-serial').click();await page.locator('#serialNumberBox input[name="serialNumber[]"]').last().fill('SPRINT04-BROWSER-A2');
 await page.locator('#saveSerials').click();await page.locator('#serialModal').waitFor({state:'hidden'});
 check(await rows.first().locator('input[data-serial]').count()===2,'Serial payload lost');
 check(await rows.first().locator('.rn-purchase-serial-count').innerText().then(s=>s.startsWith('2 ')),'Selected serial count stale');
 check(await rows.last().locator('input[data-serial]').count()===0,'Optional empty row acquired serials');
 check(!await page.locator('#purchaseEmptyState').isVisible(),'Empty state remained populated');
 const layout=await page.locator('#savePurchase').evaluate(f=>{const rect=e=>{const r=e.getBoundingClientRect();return {left:r.left,right:r.right,top:r.top,bottom:r.bottom,width:r.width,height:r.height}};const info=f.querySelector('.rn-purchase-information'),items=f.querySelector('#purchaseItemsTitle').closest('.card'),summary=f.querySelector('.rn-purchase-summary'),required=['#businessId','#date','#supplierName','[data-target="#supplier"]','#invoice','#reference'].map(s=>f.querySelector(s));return {overflow:document.documentElement.scrollWidth>innerWidth,card:getComputedStyle(f.querySelector('#productDetails tr')).display,clip:[...required,...f.querySelectorAll('.rn-purchase-summary input,.rn-purchase-information input,.rn-purchase-information select')].some(e=>e.getBoundingClientRect().left<0||e.getBoundingClientRect().right>innerWidth+1),requiredInside:required.every(e=>{const r=rect(e),p=rect(info);return r.left>=p.left-1&&r.right<=p.right+1}),itemBottom:rect(items).bottom,summaryTop:rect(summary).top,summaryTitle:rect(f.querySelector('#purchaseSummaryTitle')),cards:[...f.querySelectorAll('.rn-purchase-workspace>.card')].map(rect)};});
 check(!layout.overflow&&!layout.clip,'Populated purchase overflow/clipping');check(layout.card===(width<=992?'grid':'table-row'),'Item breakpoint mismatch');check(layout.cards.every((c,i)=>i===0||c.top>=layout.cards[i-1].bottom-1),'Purchase cards overlap');
 check(layout.requiredInside,'Populated purchase information controls escaped their card');
 if(width<1200)check(layout.summaryTop>=layout.itemBottom-1,'Summary overlaps populated Purchased Items');
 check(layout.summaryTitle.height>0,'Purchase Summary title is hidden');
 const snapshot=()=>page.locator('#savePurchase').evaluate(f=>JSON.stringify([...new FormData(f)]));const before=await snapshot();
 await rows.last().locator('.remove-row').press('Enter');await page.locator('.swal2-popup').waitFor({state:'visible'});check(await snapshot()===before,'Removed before confirmation');await page.locator('.swal2-cancel').click();await page.locator('.swal2-container').waitFor({state:'hidden'});check(await snapshot()===before,'Cancel mutated purchase');
 await page.locator('[data-purchase-leave]').last().click();await page.locator('.swal2-popup').waitFor({state:'visible'});check(await page.locator('.swal2-title').innerText().then(t=>t=== (locale==='en'?'Unsaved purchase':'কেনা সংরক্ষিত হয়নি')),'Unsaved warning missing/localization');await page.locator('.swal2-cancel').click();await page.locator('.swal2-container').waitFor({state:'hidden'});check(await snapshot()===before,'Navigation cancel mutated purchase');
 await rows.last().locator('.remove-row').press('Enter');await page.locator('.swal2-confirm').click();await page.locator('.swal2-container').waitFor({state:'hidden'});check(await rows.count()===1,'Confirm did not remove row');check(Number(await page.locator('#grandTotal').inputValue())===18,'Removal discount recalculation changed');
 await page.locator('#paidAmount').fill('10');await page.locator('#paidAmount').press('Tab');
 const payload=await page.locator('#savePurchase').evaluate(f=>[...new FormData(f)]);
 for(const field of ['grandTotal','paidAmount','discountStatus','supplierName'])check(payload.filter(([name])=>name===field).length===1,'Duplicate authoritative '+field);
 check(payload.filter(([name])=>name.startsWith('serialNumber[')).length===2,'Serial association payload changed');
 check(errors.length===0,'Browser errors: '+errors.join(';'));
 if([375,1440].includes(width)){await page.evaluate(()=>scrollTo(0,0));await page.screenshot({path:path.join(root,'storage/logs',`sprint04-offline-${locale}-${width}.png`),fullPage:true});}
 await page.locator('#purchaseSaveButton').click();await page.waitForFunction(()=>document.querySelector('h1')?.textContent==='QA captured purchase');
 check(posts.length===1,'Save did not submit once');check(posts[0].get('grandTotal')==='18'&&posts[0].get('paidAmount')==='10','Saved financial values changed');check([...posts[0].keys()].filter(k=>k.startsWith('serialNumber[')).length===2,'Saved serial payload changed');
 cases++;await page.close();
}
console.log(JSON.stringify({cases,checks,pass:true,type:'isolated SQLite HTML/API fixtures'}));
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1;});
