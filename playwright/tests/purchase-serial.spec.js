const { test, expect } = require('@playwright/test');

// Smoke: login, open Add Purchase, add product row, open serial modal, add serial, save, verify hidden serials and buy-price total
test('purchase serial modal and buy-price total smoke', async ({ page }) => {
  const base = process.env.BASE_URL || 'http://localhost:8000';

  // 1) Login
  await page.goto(base + '/login');
  // Fill credentials used by local test seeder (adjust if different)
  await page.fill('input[name="userMail"]', 'virtualitprofessional@gmail.com');
  await page.fill('input[name="password"]', '11223344');
  await Promise.all([
    page.click('button[type="submit"]'),
    page.waitForNavigation({ waitUntil: 'networkidle' }),
  ]);

  // 2) Open Add Purchase page
  await page.goto(base + '/add/purchase');
  await expect(page).toHaveURL(/add\/purchase/);

  // 3) Ensure a product can be selected
  const productSelect = page.locator('#productName');
  await expect(productSelect).toBeVisible();

  // Choose the first non-empty option
  const options = await productSelect.locator('option').all();
  let chosen = false;
  for (const opt of options) {
    const val = await opt.getAttribute('value');
    if (val && val.trim() !== '') { await productSelect.selectOption(val); chosen = true; break; }
  }
  test.skip(!chosen, 'No product available to add in test DB');

  // 4) Click Add To List
  await page.click('#addProductRow');
  // Wait for product row to appear
  await page.waitForSelector('tr.product-row[data-idx]');
  const row = await page.locator('tr.product-row').first();
  const idx = await row.getAttribute('data-idx');
  expect(idx).toBeTruthy();

  // 5) Open serial modal
  await row.locator('.open-serials').click();
  await page.waitForSelector('#serialModal', { state: 'visible' });

  // 6) Add serial input and fill
  await page.click('#add-serial');
  await page.fill('#serialNumberBox input[name="serialNumber[]"]', 'SN-PLAY-' + Date.now());

  // 7) Save serials
  await page.click('#saveSerials');
  // Allow small delay for sync
  await page.waitForTimeout(300);

  // 8) Assert hidden serial inputs exist in the row
  const hiddenSerials = await page.locator('tr.product-row[data-idx="' + idx + '"] input[data-serial]');
  await expect(hiddenSerials).toHaveCountGreaterThan(0);

  // 9) Test buy-price total calculation: set buyPrice and qty and expect total = buy*qty
  const buySel = 'tr.product-row[data-idx="' + idx + '"] input[id^="buyPrice"]';
  const qtySel = 'tr.product-row[data-idx="' + idx + '"] input.quantity';
  const totalSel = 'tr.product-row[data-idx="' + idx + '"] input[id^="totalAmount"]';

  await page.fill(buySel, '12.5');
  await page.fill(qtySel, '3');
  // allow handlers to run
  await page.waitForTimeout(200);
  const totalVal = await page.locator(totalSel).inputValue();
  expect(parseFloat(totalVal)).toBeCloseTo(37.5, 2);
});
