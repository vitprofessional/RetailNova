const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium, devices } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

(async () => {
  const browser = await chromium.launch({ headless: true, executablePath: process.env.BROWSER_EXECUTABLE || undefined });
  const context = await browser.newContext({ ...devices['Pixel 7'], viewport: { width: 390, height: 844 },
    userAgent: 'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 Chrome/130.0.0.0 Mobile Safari/537.36', serviceWorkers: 'block' });
  const page = await context.newPage();
  await page.setContent('<!doctype html><html lang="bn"><body></body></html>');
  await page.addScriptTag({ path: path.resolve(__dirname, '../../public/js/rn-avatar.js') });
  const values = await page.evaluate(() => ['12V 65Ah Dry Cell Battery','10KΩ Resistor','স্ক্রু ড্রাইভার','123'].map(name => ({ name, initial: window.RNAvatar.initial(name), tone: window.RNAvatar.toneIndex(name) })));
  assert.deepEqual(values.map(value => value.initial), ['D','R','স্',null]);
  assert.ok(values.every(value => value.tone >= 0 && value.tone < 8));
  console.log(JSON.stringify({ checks: values.length + 1, values }, null, 2));
  await browser.close();
})().catch(error => { console.error(error); process.exit(1); });
