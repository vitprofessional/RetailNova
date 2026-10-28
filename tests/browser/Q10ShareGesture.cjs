const path = require('node:path');
const { webkit, chromium, devices } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

const root = path.resolve(__dirname, '../..');
const html = `<!doctype html><html lang="bn"><head><title>Receipt SAL-Q11</title></head><body data-rn-document-preview data-rn-paper="thermal80" data-document-pdf-url="http://localhost/documents/sale-invoice/1.pdf">
<button data-document-open-share>Share</button><nav class="rn-document-toolbar"><button data-document-print>Print</button><button data-document-pdf>PDF</button></nav>
<main><article data-rn-print-document data-paper="thermal80" style="width:72mm"><header class="rn-print-document__brand"><strong>RetailNova</strong></header><h1 class="rn-print-document__title"><span>SAL-Q11</span></h1><p>Demo line ৳840</p></article></main>
<dialog data-rn-document-sheet><form method="dialog"><h2 id="rnDocumentSheetTitle">Paper</h2><fieldset><label><input type="radio" name="paper" value="a4">A4</label><label><input type="radio" name="paper" value="thermal80" checked>80</label><label><input type="radio" name="paper" value="thermal58">58</label></fieldset><fieldset><label><input type="radio" name="share-format" value="pdf" checked>PDF</label><label><input type="radio" name="share-format" value="png">Image</label></fieldset><button type="button" data-document-share data-ready-label="Share" data-building-label="Preparing">Preparing</button><button type="button" data-document-pdf>Save PDF</button><button type="button" data-document-png>Save PNG</button></form></dialog>
</body></html>`;

(async () => {
  if (!devices['iPhone 14 Pro Max']) throw new Error('Playwright device descriptor iPhone 14 Pro Max is unavailable.');
  const browserType = process.env.RN_BROWSER === 'chromium' ? chromium : webkit;
  const browser = await browserType.launch({ headless: true, executablePath: process.env.BROWSER_EXECUTABLE || undefined });
  const context = await browser.newContext({ ...devices['iPhone 14 Pro Max'], locale: 'bn-BD', serviceWorkers: 'block' });
  await context.addInitScript(() => {
    window.__isUserGestureTask = false;
    window.__shareRecord = null;
    Object.defineProperty(navigator, 'canShare', { configurable: true, value: () => true });
    Object.defineProperty(navigator, 'share', { configurable: true, value: payload => {
      window.__sharedFile = payload.files[0];
      window.__shareRecord = { sameTask: window.__isUserGestureTask,
        userActivation: navigator.userActivation?.isActive === true,
        type: payload.files[0].type, name: payload.files[0].name, title: payload.title };
      return Promise.resolve();
    }});
    window.RNPdfJs = {
      GlobalWorkerOptions: {},
      getDocument: () => ({ promise: Promise.resolve({
        getPage: async () => ({
          getViewport: ({ scale }) => ({ width: 226.7716535 * scale, height: 300 * scale }),
          render: ({ canvasContext, viewport }) => {
            canvasContext.fillStyle = '#fff';
            canvasContext.fillRect(0, 0, viewport.width, viewport.height);
            return { promise: Promise.resolve() };
          }, cleanup() {},
        }), destroy: async () => {},
      }) }),
    };
  });
  const page = await context.newPage();
  page.on('pageerror', error => console.error('PAGEERROR', error.message));
  page.on('console', message => { if (message.type() === 'error') console.error('CONSOLE', message.text()); });
  await page.route('**/*.pdf*', route => route.fulfill({ status: 200, contentType: 'application/pdf', body: '%PDF-1.7 q11 fixture' }));
  await page.setContent(html, { waitUntil: 'load' });
  await page.evaluate(() => document.addEventListener('click', () => {
    window.__isUserGestureTask = true;
    setTimeout(() => { window.__isUserGestureTask = false; }, 0);
  }, true));
  await page.addScriptTag({ path: path.join(root, 'public/js/rn-mobile-documents.js') });
  await page.locator('[data-document-open-share]').click();
  const shareButton = page.locator('[data-document-share]');
  await page.waitForFunction(() => document.querySelector('[data-rn-document-sheet]').open);
  await shareButton.waitFor({ state: 'visible' });
  await page.waitForFunction(() => !document.querySelector('[data-document-share]').disabled);
  await shareButton.click();
  const pdf = await page.evaluate(() => window.__shareRecord);
  if (!pdf || pdf.type !== 'application/pdf' || !pdf.name.endsWith('.pdf') || !pdf.sameTask || !pdf.userActivation) {
    throw new Error(`Invalid synchronous PDF share: ${JSON.stringify(pdf)}`);
  }
  await page.locator('[data-document-open-share]').click();
  await page.locator('input[name="share-format"][value="png"]').check();
  await page.waitForFunction(() => !document.querySelector('[data-document-share]').disabled);
  await shareButton.click();
  const image = await page.evaluate(() => window.__shareRecord);
  if (!image || image.type !== 'image/png' || !image.name.endsWith('.png') || !image.sameTask || !image.userActivation) {
    throw new Error(`Invalid synchronous PNG share: ${JSON.stringify(image)}`);
  }
  const pngWidth = await page.evaluate(async () => (await createImageBitmap(window.__sharedFile)).width);
  if (pngWidth < 900) throw new Error(`80mm PNG should be at least 900px wide, received ${pngWidth}px.`);
  console.log(`PASS: ${process.env.RN_BROWSER === 'chromium' ? 'Chromium Android' : 'WebKit iPhone'} share keeps the originating click task for PDF and PNG; PNG width ${pngWidth}px.`);
  await browser.close();
})().catch(error => { console.error(error); process.exit(1); });
