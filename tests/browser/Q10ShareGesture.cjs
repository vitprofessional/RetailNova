const path = require('node:path');
const { webkit, chromium, devices } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

const root = path.resolve(__dirname, '../..');
const html = `<!doctype html><html lang="bn"><head><title>Receipt SAL-Q10</title></head><body data-rn-document-preview data-rn-paper="thermal80">
<button data-document-open-share>Share</button><nav class="rn-document-toolbar"><button data-document-print>Print</button><button data-document-pdf>PDF</button></nav>
<main><article data-rn-print-document data-paper="thermal80" style="width:72mm"><header class="rn-print-document__brand"><strong>RetailNova</strong></header><h1 class="rn-print-document__title"><span>SAL-Q10</span></h1><p>Demo line ৳840</p></article></main>
<dialog data-rn-document-sheet><form method="dialog"><h2 id="rnDocumentSheetTitle">Paper</h2><fieldset><label><input type="radio" name="paper" value="a4">A4</label><label><input type="radio" name="paper" value="thermal80" checked>80</label><label><input type="radio" name="paper" value="thermal58">58</label></fieldset><fieldset><label><input type="radio" name="share-format" value="pdf" checked>PDF</label><label><input type="radio" name="share-format" value="png">Image</label></fieldset><button type="button" data-document-share data-ready-label="Share" data-building-label="Preparing">Preparing</button><button type="button" data-document-pdf>Save PDF</button><button type="button" data-document-png>Save PNG</button></form></dialog>
<script>window.RNDocumentTools={QRCode:{},html2canvas:async()=>{const c=document.createElement('canvas');c.width=1200;c.height=1000;return c;},jsPDF:class{constructor(){} addPage(){} addImage(){} output(){return new Blob(['pdf'],{type:'application/pdf'});} save(){}}};</script>
</body></html>`;

(async () => {
  if (!devices['iPhone 14 Pro Max']) throw new Error('Playwright device descriptor iPhone 14 Pro Max is unavailable.');
  const browserType = process.env.RN_BROWSER === 'chromium' ? chromium : webkit;
  const browser = await browserType.launch({ headless: true, executablePath: process.env.BROWSER_EXECUTABLE || undefined });
  const context = await browser.newContext({ ...devices['iPhone 14 Pro Max'], locale: 'bn-BD' });
  await context.addInitScript(() => {
    window.__clickTask = 0;
    window.__shareRecord = null;
    document.addEventListener('click', () => { window.__clickTask += 1; }, true);
    Object.defineProperty(navigator, 'canShare', { configurable: true, value: () => true });
    Object.defineProperty(navigator, 'share', { configurable: true, value: payload => {
      window.__shareRecord = { task: window.__clickTask, type: payload.files[0].type, name: payload.files[0].name, title: payload.title };
      return Promise.resolve();
    }});
  });
  const page = await context.newPage();
  await page.setContent(html, { waitUntil: 'load' });
  await page.addScriptTag({ path: path.join(root, 'public/js/rn-mobile-documents.js') });
  await page.locator('[data-document-open-share]').click();
  await page.locator('[data-document-share]').waitFor({ state: 'visible' });
  await page.waitForFunction(() => !document.querySelector('[data-document-share]').disabled);
  await page.locator('[data-document-share]').click();
  const record = await page.evaluate(() => window.__shareRecord);
  if (!record || record.type !== 'application/pdf' || !record.name.endsWith('.pdf')) throw new Error(`Invalid PDF share payload: ${JSON.stringify(record)}`);
  if (record.task !== await page.evaluate(() => window.__clickTask)) throw new Error('navigator.share was not called synchronously during the click task.');
  await page.locator('[data-document-open-share]').click();
  await page.locator('[data-document-share]').waitFor();
  await page.locator('input[name="share-format"][value="png"]').check();
  await page.waitForFunction(() => !document.querySelector('[data-document-share]').disabled);
  await page.locator('[data-document-share]').click();
  const image = await page.evaluate(() => window.__shareRecord);
  if (image.type !== 'image/png' || !image.name.endsWith('.png')) throw new Error(`Invalid image share payload: ${JSON.stringify(image)}`);
  console.log(`PASS: iPhone ${process.env.RN_BROWSER === 'chromium' ? 'Chromium' : 'WebKit'} emulation calls share synchronously with correctly named PDF and PNG Files.`);
  await browser.close();
})().catch(error => { console.error(error); process.exit(1); });
