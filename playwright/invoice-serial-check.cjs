// Run after SalesRegressionTest with RN_EXPORT_INVOICE_FIXTURES=1.
// Uses actual isolated Laravel-rendered HTML; strips scripts and blocks all network requests.
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = path.resolve(__dirname, '..');
const {catalog} = require('./translation-fixtures.cjs');
const css = ['backend-plugin.min.css','backende209.css','rn-custom.css','rn-table-pro.css']
  .map(name => fs.readFileSync(path.join(root, 'public/eshop/assets/css', name), 'utf8')).join('\n');
(async () => {
  const browser = await chromium.launch({headless:true,
    ...(process.env.BROWSER_EXECUTABLE ? {executablePath:process.env.BROWSER_EXECUTABLE} : {})});
  let checks = 0;
  try {
    for (const locale of ['en','bn']) {
    for (const width of [375,576,768,992,1280]) {
      for (const printer of ['a4','thermal80','thermal58']) {
        const filename = locale === 'en' ? 'serial-invoice-fixture-'+printer : 'localized-invoice-bn-'+printer;
        const html = fs.readFileSync(path.join(root, 'storage/framework/'+filename+'.html'), 'utf8')
          .replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '')
          .replace(/<link\b[^>]*>/gi, '');
        const page = await browser.newPage({viewport:{width,height:1000}});
        await page.route('**/*', route => route.request().url().endsWith('/fonts/noto-bengali/NotoSansBengali-Regular.ttf')
          ? route.fulfill({contentType:'font/ttf',body:fs.readFileSync(path.join(root,'public/fonts/noto-bengali/NotoSansBengali-Regular.ttf'))}) : route.abort());
        await page.setContent('<style>'+css+'</style>'+html);
        const body = page.locator(printer === 'a4' ? '#invoiceBodyA4' : '#invoiceBodyThermal');
        assert(await body.isVisible()); checks++;
        const serial = body.locator(printer === 'a4' ? '.serial-info' : '.thermal-item-serial');
        assert(await serial.isVisible()); checks++;
        assert((await serial.textContent()).includes(catalog(locale,'common').s_n+': SER-1-0, SER-1-1')); checks++;
        assert.equal(await body.locator(printer === 'a4' ? '.invoice-items-table tbody tr' : '.thermal-items-table tbody tr').first().locator('td').nth(printer === 'a4' ? 3 : 1).textContent(), '2'); checks++;
        if (printer !== 'a4') {
          const layout = await serial.evaluate(el => ({
            right:el.getBoundingClientRect().right,
            tableRight:el.closest('table').getBoundingClientRect().right,
            scroll:el.scrollWidth, width:el.clientWidth
          }));
          assert(layout.right <= layout.tableRight + 1 && layout.scroll <= layout.width + 1); checks++;
          if(printer==='thermal58') {
            assert(await body.locator('.thermal-item-rate').isVisible()); checks++;
            assert(!(await body.locator('.thermal-items-table tbody td:nth-child(3)').first().isVisible())); checks++;
          }
          assert((await body.locator('.thermal-item-warranty').textContent()).includes('365')); checks++;
        }
        await page.emulateMedia({media:'print'});
        assert(await serial.isVisible()); checks++;
        assert.equal(await page.locator('html').getAttribute('lang'),locale); checks++;
        if(locale==='bn') {
          await page.evaluate(()=>document.fonts.load('16px "Noto Bengali"','ক্রয় বিক্রয় সিরিয়াল'));
          assert(await page.evaluate(()=>document.fonts.check('16px "Noto Bengali"','ক্রয় বিক্রয় সিরিয়াল'))); checks++;
          const font = await serial.evaluate(el=>getComputedStyle(el).fontFamily);
          assert(font.includes('Noto Bengali'),font); checks++;
          const words=body.locator(printer==='a4'?'.words-text':'.thermal-amount-words');
          assert((await words.textContent()).includes('দুই শত পঁচানব্বই টাকা মাত্র')); checks++;
          if(width===375) {
            await body.screenshot({path:path.join(root,'storage/logs/phase3-bn-'+printer+'.png')});
            await page.pdf({path:path.join(root,'storage/logs/phase3-bn-'+printer+'.pdf'),printBackground:true,
              ...(printer==='a4'?{format:'A4'}:{width:printer==='thermal58'?'58mm':'80mm',height:'250mm'}),margin:{top:0,right:0,bottom:0,left:0}});
          }
        }
        console.log(`PASS invoice ${locale} ${width}px ${printer}: labels, serials, quantity and print layout`);
        await page.close();
      }
    }
    }
    console.log(`30 isolated bilingual invoice browser scenarios passed (${checks} assertions). No live HTTP requests.`);
  } finally { await browser.close(); }
})().catch(e => { console.error(e); process.exitCode=1; });
