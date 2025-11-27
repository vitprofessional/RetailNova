// Playwright config for a small local smoke test
/** @type {import('@playwright/test').PlaywrightTestConfig} */
const config = {
  use: {
    headless: true,
    viewport: { width: 1280, height: 800 },
    actionTimeout: 10000,
    navigationTimeout: 20000,
  },
  testDir: './tests',
  timeout: 120000,
};
module.exports = config;
