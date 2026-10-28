#!/usr/bin/env node
/* Verify mobile catalog quick-create selects the new option and uses a specific toast. */
const { chromium, devices } = require(process.env.PLAYWRIGHT_MODULE);

async function main() {
  const base = (process.env.RN_MOBILE_BASE_URL || 'http://127.0.0.1:8124').replace(/\/$/, '');
  const email = process.env.RN_MOBILE_EMAIL;
  const password = process.env.RN_MOBILE_PASSWORD;
  if (!email || !password) throw new Error('Set disposable mobile fixture credentials.');
  const browser = await chromium.launch({ headless: true, executablePath: process.env.BROWSER_EXECUTABLE });
  const context = await browser.newContext({ ...devices['Pixel 7'], viewport: { width: 390, height: 844 }, locale: 'bn-BD',
    userAgent: 'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 Chrome/130.0.0.0 Mobile Safari/537.36', serviceWorkers: 'block' });
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  try {
    await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
    await page.locator('#userMail').fill(email);
    await page.locator('#password').fill(password);
    await Promise.all([page.waitForLoadState('networkidle'), page.locator('.rn-sign-in-button').click()]);
    await page.goto(`${base}/new/product`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    if (process.env.RN_PRODUCT_NEW_SCREENSHOT) await page.screenshot({ path: process.env.RN_PRODUCT_NEW_SCREENSHOT, fullPage: true });

    const action = page.locator('.rn-m-field-action-button[data-target="#categoryModal"]');
    const actionRect = await action.boundingBox();
    const selectRect = await page.locator('#categoryList').boundingBox();
    if (!actionRect || !selectRect || actionRect.y + actionRect.height > selectRect.y - 2) {
      throw new Error(`Category action overlaps or touches its select: ${JSON.stringify({ actionRect, selectRect })}`);
    }
    await action.click();
    await page.locator('#categoryModal.show').waitFor({ state: 'visible' });
    await page.waitForTimeout(500);
    if (process.env.RN_CATEGORY_SHEET_SCREENSHOT) await page.screenshot({ path: process.env.RN_CATEGORY_SHEET_SCREENSHOT, fullPage: true });
    const categoryName = `Q13 ক্যাটাগরি ${Date.now()}`;
    await page.locator('#categoryModal input[name="name"]').fill(categoryName);
    await page.locator('#categoryModal button[type="submit"]').click();
    await page.locator('#categoryModal.show').waitFor({ state: 'hidden' });
    await page.waitForFunction(name => {
      const select = document.querySelector('#categoryList');
      return select && select.selectedOptions.length && select.selectedOptions[0].textContent === name;
    }, categoryName);
    const toast = page.locator('[data-rn-toast]:not([hidden])');
    await toast.waitFor({ state: 'visible' });
    const toastText = (await page.locator('[data-rn-toast-message]').textContent()).trim();
    if (toastText !== `ক্যাটাগরি যোগ হয়েছে — ${categoryName}`) throw new Error(`Unexpected category toast: ${toastText}`);
    const selectedCategory = await page.locator('#categoryList').inputValue();

    await page.locator('#name').fill(`Q13 সম্পাদনা পণ্য ${Date.now()}`);
    await page.locator('form[data-rn-product-form] button[type="submit"]').last().click();
    await page.waitForURL(/product\/list|product-list/i, { timeout: 15000 });

    const purchase = await page.goto(`${base}/add/purchase`, { waitUntil: 'networkidle' });
    if (!purchase || purchase.status() >= 400) throw new Error(`Purchase form failed: ${purchase?.status()}`);
    const purchaseFields = await page.locator('#newProduct .rn-product-modal-mobile-fields .rn-m-field-action-button').count();
    if (purchaseFields !== 3) throw new Error(`Expected 3 shared mobile action-slot fields in purchase product modal, got ${purchaseFields}`);

    await page.goto(`${base}/product/list`, { waitUntil: 'networkidle' });
    const editHref = await page.locator('a[href*="/product/edit/"]').first().getAttribute('href').catch(() => null);
    if (!editHref) throw new Error('No editable product exists in the disposable fixture.');
    await page.goto(new URL(editHref, base).toString(), { waitUntil: 'networkidle' });
    await page.locator('[data-rn-product-form]').waitFor({ state: 'visible' });
    if (process.env.RN_PRODUCT_EDIT_SCREENSHOT) await page.screenshot({ path: process.env.RN_PRODUCT_EDIT_SCREENSHOT, fullPage: true });
    if (errors.length) throw new Error(`Browser errors: ${JSON.stringify(errors)}`);
    console.log(JSON.stringify({ categoryName, selectedCategory, toast: toastText, purchaseActionFields: purchaseFields, productEdit: page.url(), errors }, null, 2));
  } finally {
    await context.close();
    await browser.close();
  }
}

main().catch(error => { console.error(error.stack || error); process.exit(1); });
