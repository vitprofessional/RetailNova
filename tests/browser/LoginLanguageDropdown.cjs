// Responsive overlay regression for the guest login language picker.
// Run with the repository's existing Playwright and Chrome installation:
// node tests/browser/LoginLanguageDropdown.cjs
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

const baseUrl = process.env.LOGIN_URL || 'http://localhost/RetailNova/login';
const viewports = [
  { width: 1366, height: 768 },
  { width: 1440, height: 900 },
  { width: 390, height: 844 },
  { width: 474, height: 800 },
];
let checks = 0;

function check(condition, message) {
  assert.ok(condition, message);
  checks++;
}

(async () => {
  const browser = await chromium.launch({
    headless: true,
    ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}),
  });

  try {
    for (const viewport of viewports) {
      const page = await browser.newPage({ viewport });
      const pageErrors = [];
      page.on('pageerror', error => pageErrors.push(error.message));
      await page.goto(baseUrl, { waitUntil: 'networkidle' });
      await page.locator('[data-language-picker]').waitFor({ state: 'visible' });

      const before = await page.evaluate(() => {
        const rect = element => {
          const box = element.getBoundingClientRect();
          return { top: box.top, left: box.left, right: box.right, bottom: box.bottom, height: box.height };
        };
        return {
          card: rect(document.querySelector('.rn-login-card')),
          header: rect(document.querySelector('.rn-login-topbar')),
          viewport: { width: innerWidth, height: innerHeight },
          document: { width: document.documentElement.scrollWidth, height: document.documentElement.scrollHeight },
          hasHorizontalOverflow: document.documentElement.scrollWidth > innerWidth,
        };
      });

      await page.locator('.rn-language-trigger').click();
      const opened = await page.evaluate(() => {
        const rect = element => {
          const box = element.getBoundingClientRect();
          return { top: box.top, left: box.left, right: box.right, bottom: box.bottom, height: box.height };
        };
        const picker = document.querySelector('[data-language-picker]');
        const trigger = document.querySelector('.rn-language-trigger');
        const menu = document.querySelector('.rn-language-menu');
        const menuRect = menu.getBoundingClientRect();
        const point = document.elementFromPoint(menuRect.left + menuRect.width / 2, menuRect.top + menuRect.height / 2);
        return {
          open: picker.open,
          expanded: trigger.getAttribute('aria-expanded'),
          trigger: rect(trigger),
          menu: rect(menu),
          header: rect(document.querySelector('.rn-login-topbar')),
          card: rect(document.querySelector('.rn-login-card')),
          overlapsCard: menuRect.left < document.querySelector('.rn-login-card').getBoundingClientRect().right
            && menuRect.right > document.querySelector('.rn-login-card').getBoundingClientRect().left
            && menuRect.top < document.querySelector('.rn-login-card').getBoundingClientRect().bottom
            && menuRect.bottom > document.querySelector('.rn-login-card').getBoundingClientRect().top,
          menuPosition: getComputedStyle(menu).position,
          wrapperPosition: getComputedStyle(picker).position,
          menuHitTest: menu.contains(point),
          viewport: { width: innerWidth, height: innerHeight },
          document: { width: document.documentElement.scrollWidth, height: document.documentElement.scrollHeight },
          hasHorizontalOverflow: document.documentElement.scrollWidth > innerWidth,
        };
      });

      const prefix = `${viewport.width}x${viewport.height}`;
      check(opened.open && opened.expanded === 'true', `${prefix}: menu did not open accessibly`);
      check(Math.abs(opened.card.top - before.card.top) <= 1 && Math.abs(opened.card.left - before.card.left) <= 1,
        `${prefix}: opening menu moved the login card`);
      check(Math.abs(opened.header.height - before.header.height) <= 1,
        `${prefix}: opening menu changed header height`);
      check(opened.menuPosition === 'absolute' && opened.wrapperPosition === 'relative',
        `${prefix}: menu is not anchored as a floating overlay`);
      check(opened.menu.top >= opened.trigger.bottom - 1 && opened.menu.left >= 0 && opened.menu.right <= opened.viewport.width + 1,
        `${prefix}: menu overlaps trigger or leaves viewport horizontally`);
      check(opened.menuHitTest, `${prefix}: menu is visually underneath adjacent content`);
      check(opened.overlapsCard, `${prefix}: menu does not overlay the login card area`);
      check(opened.document.width === before.document.width && opened.document.height === before.document.height,
        `${prefix}: opening menu changed document dimensions`);
      check(!opened.hasHorizontalOverflow && !before.hasHorizontalOverflow,
        `${prefix}: page has horizontal overflow`);

      await page.locator('.rn-language-trigger').press('Escape');
      await page.waitForFunction(() => !document.querySelector('[data-language-picker]').open);
      const closed = await page.evaluate(() => {
        const card = document.querySelector('.rn-login-card').getBoundingClientRect();
        const header = document.querySelector('.rn-login-topbar').getBoundingClientRect();
        return {
          expanded: document.querySelector('.rn-language-trigger').getAttribute('aria-expanded'),
          card: { top: card.top, left: card.left },
          headerHeight: header.height,
          focusRestored: document.activeElement.classList.contains('rn-language-trigger'),
        };
      });
      check(closed.expanded === 'false' && closed.focusRestored, `${prefix}: Escape did not close and restore focus`);
      check(Math.abs(closed.card.top - before.card.top) <= 1 && Math.abs(closed.card.left - before.card.left) <= 1,
        `${prefix}: closing menu moved the login card`);
      check(Math.abs(closed.headerHeight - before.header.height) <= 1,
        `${prefix}: closing menu changed header height`);
      check(pageErrors.length === 0, `${prefix}: browser errors: ${pageErrors.join('; ')}`);

      console.log(`PASS ${prefix}: overlay and stable layout (${checks} checks total)`);
      await page.close();
    }

    console.log(`OK: ${viewports.length} viewports, ${checks} checks`);
  } finally {
    await browser.close();
  }
})().catch(error => {
  console.error(error);
  process.exitCode = 1;
});
