const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

const baseUrl = process.env.RN_DOCUMENT_BASE_URL || 'http://127.0.0.1:8123';
const htmlDir = process.env.RN_DOCUMENT_HTML_DIR;
const casesPath = process.env.RN_DOCUMENT_CASES_FILE;
if (!casesPath || !fs.existsSync(casesPath)) {
  throw new Error('Set RN_DOCUMENT_CASES_FILE to a JSON array of {type,id,expected} document fixtures.');
}
const cases = JSON.parse(fs.readFileSync(casesPath, 'utf8'));
const output = path.resolve(process.env.RN_DOCUMENT_OUTPUT || 'tests-output/q7/docs');
fs.mkdirSync(output, { recursive: true });

(async () => {
  const browser = await chromium.launch({
    headless: true,
    executablePath: process.env.BROWSER_EXECUTABLE,
  });
  const context = await browser.newContext({
    viewport: { width: 390, height: 844 },
    deviceScaleFactor: 1,
    isMobile: true,
    hasTouch: true,
    locale: 'bn-BD',
    userAgent: 'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36',
    storageState: process.env.RN_DOCUMENT_STORAGE_STATE || undefined,
  });
  if (!htmlDir && !process.env.RN_DOCUMENT_STORAGE_STATE) {
    if (!process.env.RN_DOCUMENT_EMAIL || !process.env.RN_DOCUMENT_PASSWORD) {
      throw new Error('Set RN_DOCUMENT_EMAIL and RN_DOCUMENT_PASSWORD for the isolated browser fixture.');
    }
    const login = await context.newPage();
    await login.goto(new URL('/admin/login', baseUrl).href, { waitUntil: 'networkidle' });
    await login.locator('#userMail').fill(process.env.RN_DOCUMENT_EMAIL);
    await login.locator('#password').fill(process.env.RN_DOCUMENT_PASSWORD);
    await login.locator('.rn-sign-in-button').click();
    await login.waitForLoadState('networkidle');
    if (new URL(login.url()).pathname.includes('login')) throw new Error('Isolated browser login did not succeed.');
    await context.storageState({ path: path.join(output, 'storage-state.json') });
    await login.close();
  }
  const failures = [];
  const pdfManifest = [];
  for (const fixture of cases) {
    for (const size of ['a4', 'thermal80', 'thermal58']) {
      for (const autoprint of [false, true]) {
        const page = await context.newPage();
        const browserErrors = [];
        page.on('pageerror', error => browserErrors.push(error.message));
        await page.addInitScript(() => {
          window.__rnPrintCalls = 0;
          window.print = () => {
            window.__rnPrintCalls += 1;
            window.dispatchEvent(new Event('afterprint'));
          };
        });
        const url = htmlDir
          ? new URL(`file:///${path.resolve(htmlDir, `${fixture.type}-${size}.html`).replaceAll('\\', '/')}`)
          : new URL(`/documents/${fixture.type}/${fixture.id}`, baseUrl);
        url.searchParams.set('size', size);
        url.searchParams.set('autoprint', autoprint ? '1' : '0');
        try {
          const response = await page.goto(url.href, { waitUntil: 'networkidle' });
          await page.waitForTimeout(500);
          const article = page.locator('[data-rn-print-document]');
          const bodyText = (await page.locator('body').innerText()).trim();
          if (!htmlDir && !response?.ok()) throw new Error(`HTTP ${response?.status()}`);
          if (!bodyText || !bodyText.includes(fixture.expected)) throw new Error('Preview text is absent.');
          if (!(await article.count())) throw new Error('Document article is absent.');
          if (autoprint && (await page.evaluate(() => window.__rnPrintCalls)) !== 1) throw new Error('Autoprint did not execute.');
          await page.locator('[data-document-open-share]').click();
          const sheet = page.locator('[data-rn-document-sheet]');
          if (!(await sheet.isVisible())) throw new Error('Share sheet did not open.');
          if (await sheet.locator('#rnDocumentSheetTitle').count() !== 1) throw new Error('Share sheet heading is duplicated.');
          for (const label of ['A4', '৮০', '৫৮', 'শেয়ার', 'ছবি']) {
            if (!(await sheet.innerText()).includes(label)) throw new Error(`Share sheet is missing ${label}.`);
          }
          await sheet.locator('.rn-document-sheet__close').click();
          if (!autoprint) {
            await page.locator('.rn-document-toolbar [data-document-print]').click();
            if ((await page.evaluate(() => window.__rnPrintCalls)) !== 1) throw new Error('Toolbar print did not execute.');
          }
          if (!(await article.isVisible()) || !(await article.innerText()).includes(fixture.expected)) throw new Error('Preview disappeared after print/cancel.');
          if (browserErrors.length) throw new Error(`Browser errors: ${browserErrors.join('; ')}`);

          const suffix = `${fixture.type}-${size}-autoprint-${autoprint ? '1' : '0'}`;
          await page.screenshot({ path: path.join(output, `${suffix}.png`), fullPage: true });
          const pdfPath = path.join(output, `${suffix}.pdf`);
          await page.pdf({ path: pdfPath, printBackground: true, preferCSSPageSize: true });
          pdfManifest.push({ path: pdfPath, expected: fixture.expected, type: fixture.type, size, autoprint });
        } catch (error) {
          failures.push(`${fixture.type}/${size}/autoprint=${Number(autoprint)}: ${error.message}`);
        } finally {
          await page.close();
        }
      }
    }
  }
  fs.writeFileSync(path.join(output, 'pdf-manifest.json'), JSON.stringify(pdfManifest, null, 2));
  await browser.close();
  console.log(`Cases: ${cases.length * 3 * 2}; PDFs: ${pdfManifest.length}; Screenshots: ${pdfManifest.length}`);
  if (failures.length) {
    console.error(failures.join('\n'));
    process.exitCode = 1;
  }
})().catch(error => { console.error(error); process.exit(1); });
