const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

async function main() {
  const base = (process.env.RN_DAMAGE_BASE_URL || 'http://127.0.0.1:8124').replace(/\/$/, '');
  const output = path.resolve(process.env.RN_DAMAGE_OUTPUT || 'tests-output/q13');
  fs.mkdirSync(output, { recursive: true });
  const browser = await chromium.launch({ headless: true, executablePath: process.env.BROWSER_EXECUTABLE });
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2,
    isMobile: true, hasTouch: true, locale: 'bn-BD', serviceWorkers: 'block',
    userAgent: 'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36' });
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
  page.on('response', response => { if (response.status() >= 400) errors.push(`${response.status()} ${response.url()}`); });

  await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
  if (/login/i.test(new URL(page.url()).pathname)) {
    await page.locator('#userMail,input[name=userMail],input[type=email],input[name=email],input[name=username]').first().fill(process.env.RN_DAMAGE_EMAIL || 'q12@example.test');
    await page.locator('#password,input[type=password]').first().fill(process.env.RN_DAMAGE_PASSWORD || 'Q12-test-password!');
    await Promise.all([page.waitForLoadState('networkidle'), page.locator('.rn-login-form button[type=submit],.rn-sign-in-button').first().click()]);
  }
  await page.goto(`${base}/damage/product`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(500);
  if (/login/i.test(new URL(page.url()).pathname)) throw new Error('Mobile damage page redirected to login.');
  await page.screenshot({ path: path.join(output, 'damage-empty.png'), fullPage: true });

  await page.locator('[data-damage-open-picker]').click();
  await page.locator('[data-picker-results] .rn-m-picker-card').first().waitFor({ state: 'visible' });
  const names = await page.locator('[data-picker-results] .rn-m-picker-card__name').allTextContents();
  const picks = [...new Set(names)].slice(0, 2);
  if (picks.length < 2) throw new Error(`Need two stocked fixture products; got ${JSON.stringify(picks)}`);
  for (const name of picks) {
    await page.locator('[data-picker-results] .rn-m-picker-card').filter({ hasText: name }).first().click();
    await page.locator('[data-rn-toast],[data-rn-feedback] .rn-m-toast').first().waitFor({ state: 'visible', timeout: 5000 }).catch(() => {});
  }
  await page.locator('[data-damage-close-sheet]').last().click();
  await page.locator('.rn-m-damage-line').nth(1).waitFor({ state: 'visible' });
  await page.screenshot({ path: path.join(output, 'damage-two-products.png'), fullPage: true });

  await page.locator('#damageReference').fill('Q13-DAMAGE');
  await page.locator('[data-damage-submit]').click();
  await page.waitForURL(/damage\/product\/list/, { timeout: 15000 });
  await page.waitForTimeout(500);
  const success = await page.getByText(/নষ্ট পণ্য সংরক্ষিত|Damage saved/i).first().textContent().catch(() => '');
  if (!success) throw new Error('Expected specific damage save toast after submit.');
  await page.screenshot({ path: path.join(output, 'damage-saved.png'), fullPage: true });
  await browser.close();
  const report = { screenshots: ['damage-empty.png','damage-two-products.png','damage-saved.png'].map(file => path.join(output,file)), products: picks, success: success.trim(), errors };
  fs.writeFileSync(path.join(output, 'damage-flow.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report, null, 2));
  if (errors.length) process.exitCode = 1;
}

main().catch(error => { console.error(error); process.exit(1); });
