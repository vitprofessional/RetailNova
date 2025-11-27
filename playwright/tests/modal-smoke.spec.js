const { test, expect } = require('@playwright/test');

// NOTE: This is a smoke test intended to run against a local dev server.
// Start the Laravel server first: `php artisan serve --host=127.0.0.1 --port=8000`

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000';

test.describe('Modal smoke tests', () => {
  test('Supplier modal submits, closes and updates supplier select', async ({ page }) => {
    await page.goto(`${BASE}/add/purchase`);
    // Open supplier modal using the button that triggers it
    await page.waitForSelector('button[data-target="#supplier"]', { timeout: 5000 });
    await page.click('button[data-target="#supplier"]');

    // Wait for modal to be visible
    const supplierModal = page.locator('#supplier');
    await expect(supplierModal).toBeVisible({ timeout: 5000 });

    // Fill form fields (field names expected by controller)
    await page.fill('#fullName', 'Test Supplier Playwright');
    await page.fill('#userMail', `pw-supplier+${Date.now()}@example.com`);
    await page.fill('#mobile', '0123456789');
    await page.fill('#country', 'Xland');
    await page.fill('#state', 'Stateland');
    await page.fill('#city', 'Cityville');
    await page.fill('#area', 'Area 51');

    // Submit the form by clicking the button (it is a submit button after our patch)
    await page.click('#add-supplier');

    // Wait for modal to be hidden - check not visible
    await expect(supplierModal).toBeHidden({ timeout: 7000 });

    // Ensure supplier select has at least one option (updated list)
    // If AJAX returned options, the select should have >0 options
    const options = await page.$$eval('#supplierName option', opts => opts.map(o => o.value));
    expect(options.length).toBeGreaterThan(0);
  });
});
