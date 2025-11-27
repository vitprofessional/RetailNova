const { test, expect } = require('@playwright/test');

// Smoke: verify grand total and due update correctly when Buy Price, Qty and Paid are edited
// Assumptions: app running at http://127.0.0.1:8000 and a seeded product exists selectable on Add Purchase page

test.describe('Purchase paid/due calculation', () => {
  test('updates grand total and due from buy-price × qty and paid amount', async ({ page }) => {
    // Adjust URL if your app is served on a different host/port
    await page.goto('http://127.0.0.1:8000/purchase/add');

    // Wait for product select to appear
    await page.waitForSelector('.js-product-select, #productName');

    // Select first product (assumes there is at least one)
    const prodSel = await page.$('.js-product-select') || await page.$('#productName');
    const firstVal = await prodSel.evaluate(s => s.options && s.options[1] ? s.options[1].value : s.value);
    await prodSel.selectOption(firstVal);

    // Click Add to list
    await page.click('#addProductRow');

    // Wait for a product-row to appear
    await page.waitForSelector('tr.product-row');

    // Set buy price and quantity on the first row
    const buyInput = await page.$('input[id^="buyPrice"]');
    const qtyInput = await page.$('input.quantity');
    await buyInput.fill('12.5');
    await qtyInput.fill('3');

    // Trigger input events
    await buyInput.dispatchEvent('input');
    await qtyInput.dispatchEvent('input');

    // Give client-side recalcs a moment
    await page.waitForTimeout(250);

    // Check total base (buy × qty) shows in totalSaleAmount and grandTotal
    const base = 12.5 * 3;
    const totalSale = parseFloat(await (await page.$('#totalSaleAmount')).inputValue());
    const grand = parseFloat(await (await page.$('#grandTotal')).inputValue());
    expect(totalSale).toBeCloseTo(base, 2);
    expect(grand).toBeCloseTo(base, 2);

    // Now set paid amount and verify due updates
    const paid = await page.$('#paidAmount');
    await paid.fill('20');
    await paid.dispatchEvent('input');

    await page.waitForTimeout(150);

    const due = parseFloat(await (await page.$('#dueAmount')).inputValue());
    expect(due).toBeCloseTo(Math.max(0, base - 20), 2);
  });
});
