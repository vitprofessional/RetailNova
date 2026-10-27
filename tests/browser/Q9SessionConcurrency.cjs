#!/usr/bin/env node
// Browser regression: admin session survives overlapping legacy POS AJAX requests.
const { chromium, devices } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

const base = (process.env.RN_Q9_BASE_URL || '').replace(/\/$/, '');
const email = process.env.RN_Q9_EMAIL;
const password = process.env.RN_Q9_PASSWORD;
const customerId = process.env.RN_Q9_CUSTOMER_ID;
const businessId = process.env.RN_Q9_BUSINESS_ID;

if (!base || !email || !password || !customerId || !businessId) {
  throw new Error('Set RN_Q9_BASE_URL, RN_Q9_EMAIL, RN_Q9_PASSWORD, RN_Q9_CUSTOMER_ID and RN_Q9_BUSINESS_ID for the isolated browser fixture.');
}

(async () => {
  const browser = await chromium.launch({
    headless: true,
    executablePath: process.env.BROWSER_EXECUTABLE || process.env.CHROME_PATH || undefined,
  });
  const context = await browser.newContext({
    ...devices['Pixel 7'],
    viewport: { width: 390, height: 844 },
    userAgent: 'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36',
    serviceWorkers: 'block',
  });
  const page = await context.newPage();
  await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
  await page.locator('#userMail').fill(email);
  await page.locator('#password').fill(password);
  await Promise.all([
    page.waitForURL(url => !/\/login$/.test(url.pathname), { waitUntil: 'networkidle' }),
    page.locator('.rn-sign-in-button').click(),
  ]);

  const sessionCookie = async () => {
    const cookies = await context.cookies(base);
    const cookie = cookies.find(item => /session/i.test(item.name));
    if (!cookie) throw new Error('No session cookie was issued after admin login.');
    return { name: cookie.name, value: cookie.value };
  };
  const before = await sessionCookie();
  const results = await page.evaluate(async ({ base, customerId, businessId }) => {
    const query = `?businessId=${encodeURIComponent(businessId)}`;
    const paths = [
      `/ajax/public/customer/${encodeURIComponent(customerId)}/products${query}`,
      `/ajax/public/customer/${encodeURIComponent(customerId)}/previous-due${query}`,
    ];
    return Promise.all(paths.map(async path => {
      const response = await fetch(new URL(path, base), { credentials: 'same-origin', headers: { Accept: 'application/json' } });
      return { path, status: response.status };
    }));
  }, { base, customerId, businessId });
  const after = await sessionCookie();
  await page.goto(`${base}/dashboard`, { waitUntil: 'networkidle' });
  const authenticatedAfterRequests = !/\/(?:login|admin\/login)$/.test(new URL(page.url()).pathname);
  await browser.close();

  const failed = results.filter(result => result.status !== 200);
  // The screenshot fixture uses Laravel's cookie session driver, so the
  // encrypted cookie payload is expected to change as session data is saved.
  // The feature test asserts that the actual server-side session ID is not
  // regenerated; here we assert both AJAX responses and authenticated reuse.
  if (before.name !== after.name || !after.value || failed.length || !authenticatedAfterRequests) {
    throw new Error(JSON.stringify({ before, after, results }, null, 2));
  }
  console.log(JSON.stringify({ requests: results, sessionCookiePresentAfterRequests: true, authenticatedAfterRequests }, null, 2));
})().catch(error => {
  console.error(error.stack || error);
  process.exit(1);
});
