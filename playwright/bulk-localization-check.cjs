// Exercise the real bulk-action script without network requests or data changes.
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const {chromium} = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const {bridge, catalog} = require('./translation-fixtures.cjs');
const source = fs.readFileSync(path.join(__dirname, '../resources/views/partials/bulk-actions-script.blade.php'), 'utf8');
(async () => {
  const browser = await chromium.launch({headless: true,
    ...(process.env.BROWSER_EXECUTABLE ? {executablePath: process.env.BROWSER_EXECUTABLE} : {})});
  let checks = 0;
  try {
    for (const locale of ['en', 'bn']) {
      const page = await browser.newPage();
      await page.route('**/*', route => route.abort());
      await page.setContent(`<meta charset="utf-8"><div id="bulkActionsWrapper"></div>
        <table><tr><td><input class="bulk-select" type="checkbox" value="42"></td></tr></table>
        <form id="bulkPrintForm"></form><form id="bulkDeleteForm"></form>
        <script>window.alerts=[];window.alert=t=>alerts.push(t);window.open=()=>null;</script>
        ${bridge(locale)}${source}`);
      for (const [id, key] of [['bulkPrintForm', 'client_please_select_at_least_one_item_to_print'],
        ['bulkDeleteForm', 'client_please_select_at_least_one_item_to_delete']]) {
        const result = await page.evaluate(id => {
          const event = new Event('submit', {cancelable:true});
          document.getElementById(id).dispatchEvent(event);
          return {message: alerts.pop(), prevented: event.defaultPrevented};
        }, id);
        assert.equal(result.message, catalog(locale, 'common')[key]); checks++;
        assert.equal(result.prevented, true); checks++;
      }
      await page.locator('.bulk-select').check();
      const selected = await page.evaluate(() => {
        const form = document.getElementById('bulkPrintForm');
        form.submit = () => {window.submitted = true;};
        form.dispatchEvent(new Event('submit', {cancelable:true}));
        return {submitted: window.submitted, value: form.querySelector('input[name="selected[]"]').value};
      });
      assert.equal(selected.submitted, true); checks++;
      assert.equal(selected.value, '42'); checks++;
      await page.close();
    }
    console.log(`6 bilingual bulk-action scenarios passed (${checks} assertions). No live requests.`);
  } finally {await browser.close();}
})().catch(error => {console.error(error); process.exitCode = 1;});
