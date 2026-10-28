#!/usr/bin/env node
/* Mobile HTML-required fields must use the persistent app banner and retain form input. */
const { chromium, devices } = require(process.env.PLAYWRIGHT_MODULE);

async function main() {
  const base = (process.env.RN_MOBILE_BASE_URL || 'http://127.0.0.1:8124').replace(/\/$/, '');
  const email = process.env.RN_MOBILE_EMAIL;
  const password = process.env.RN_MOBILE_PASSWORD;
  if (!email || !password) throw new Error('Set RN_MOBILE_EMAIL and RN_MOBILE_PASSWORD for the disposable mobile fixture.');

  const browser = await chromium.launch({ headless: true, executablePath: process.env.BROWSER_EXECUTABLE });
  const context = await browser.newContext({
    ...devices['Pixel 7'], viewport: { width: 390, height: 844 }, locale: 'bn-BD',
    userAgent: 'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 Chrome/130.0.0.0 Mobile Safari/537.36',
    serviceWorkers: 'block',
  });
  const page = await context.newPage();
  try {
    await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
    await page.locator('#userMail').fill(email);
    await page.locator('#password').fill(password);
    await Promise.all([page.waitForLoadState('networkidle'), page.locator('.rn-sign-in-button').click()]);
    await page.goto(`${base}/new/product`, { waitUntil: 'networkidle' });
    const form = page.locator('form').filter({ has: page.locator('input[name="name"]') }).first();
    await form.locator('input[name="barCode"]').fill('Q12-KEEP-001');
    await form.locator('button[type="submit"]').last().click();
    const banner = page.locator('[data-rn-error-banner]');
    await banner.waitFor({ state: 'visible' });
    const result = {
      url: page.url(),
      banner: (await page.locator('[data-rn-error-message]').textContent()).trim(),
      retainedInput: await form.locator('input[name="barCode"]').inputValue(),
    };
    if (!result.banner.includes('পণ্যের নাম') || result.retainedInput !== 'Q12-KEEP-001') {
      throw new Error(`Required-field banner/input regression: ${JSON.stringify(result)}`);
    }
    if (process.env.RN_MOBILE_SCREENSHOT) await page.screenshot({ path: process.env.RN_MOBILE_SCREENSHOT, fullPage: true });
    console.log(JSON.stringify(result, null, 2));
  } finally {
    await context.close();
    await browser.close();
  }
}

main().catch(error => { console.error(error.stack || error); process.exit(1); });
