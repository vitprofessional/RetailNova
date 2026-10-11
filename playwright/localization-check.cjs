// Synthetic Laravel-rendered fixtures only. All network calls are blocked except
// the bundled font, served from disk. No live authentication or database writes.
const fs=require('node:fs');
const path=require('node:path');
const vm=require('node:vm');
const assert=require('node:assert/strict');
const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
const root=path.resolve(__dirname,'..');
const read=name=>fs.readFileSync(path.join(root,'storage/framework',name+'.html'),'utf8');
const css=['backend-plugin.min.css','backende209.css','rn-custom.css','rn-table-pro.css'].map(n=>fs.readFileSync(path.join(root,'public/eshop/assets/css',n),'utf8')).join('\n');
let checks=0;
for(const name of ['localized-settings-en','localized-settings-bn','localized-sales-create-bn','localized-sales-edit-bn',
  ...['a4','thermal80','thermal58'].map(p=>'localized-invoice-bn-'+p)]) {
  const html=read(name);
  for(const match of html.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/gi)) {
    if(/\bsrc=|type=["'](?:module|application\/json|text\/template)/.test(match[1])||!match[2].trim())continue;
    new vm.Script(match[2],{filename:name});checks++;
  }
}
(async()=>{
 const browser=await chromium.launch({headless:true,...(process.env.BROWSER_EXECUTABLE?{executablePath:process.env.BROWSER_EXECUTABLE}:{})});
 let scenarios=0;
 try {
  for(const locale of ['en','bn']) for(const width of [375,576,768,992,1280]) {
   const html=read('localized-settings-'+locale).replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi,'').replace(/<link\b[^>]*>/gi,'');
   const page=await browser.newPage({viewport:{width,height:1000}});
   await page.route('**/*',r=>r.request().url().endsWith('/fonts/noto-bengali/NotoSansBengali-Regular.ttf')?
    r.fulfill({contentType:'font/ttf',body:fs.readFileSync(path.join(root,'public/fonts/noto-bengali/NotoSansBengali-Regular.ttf'))}):r.abort());
   await page.setContent('<style>'+css+'</style>'+html);
   // Equivalent to opening the existing mobile Bootstrap navigation menu.
   await page.locator('#navbarSupportedContent').evaluate(el=>el.classList.add('show'));
   assert.equal(await page.locator('html').getAttribute('lang'),locale);checks++;
   assert(await page.locator('#preferredLocale').isVisible());checks++;
   assert(await page.locator('#defaultLocale').isVisible());checks++;
   assert.deepEqual(await page.locator('#preferredLocale option').evaluateAll(o=>o.map(e=>e.value)),['','en','bn']);checks++;
   assert.deepEqual(await page.locator('#defaultLocale option').evaluateAll(o=>o.map(e=>e.value)),['en','bn']);checks++;
   await page.locator('#preferredLocale').evaluate(el=>{el.form.submit=function(){};});
   await page.selectOption('#preferredLocale','bn');
   const preference=await page.locator('#preferredLocale').evaluate(el=>Object.fromEntries(new FormData(el.form)));
   assert.equal(preference.preferred_locale,'bn');checks++;
   assert(preference._token);checks++;
   assert(!('userId' in preference));checks++;
   await page.selectOption('#defaultLocale','bn');
   const business=await page.locator('#defaultLocale').evaluate(el=>Object.fromEntries(new FormData(el.form)));
   assert.equal(business.default_locale,'bn');checks++;
   assert.equal(business.businessId,'1');checks++;
   assert(business._token);checks++;
   assert(!('sales_serial_selection_required' in business));checks++;
   const bounds=await page.locator('#defaultLocale').evaluate(el=>({right:el.getBoundingClientRect().right,width:innerWidth}));
   assert(bounds.right<=bounds.width+1);checks++;
   await page.close();scenarios++;
  }
  console.log(`${scenarios} language-settings browser scenarios and rendered inline-JS syntax checks passed (${checks} checks). No live HTTP requests.`);
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
