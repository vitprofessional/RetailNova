# RetailNova mobile QA checklist

Use this checklist on a real phone after deploying the approved build to a local or staging environment. Use a non-production test business and record the device, OS, browser, app build, language, and result for each run. Do not use production data for test transactions.

## Before testing

- [ ] Sign in with an authorized test account and select the intended business.
- [ ] Confirm the bottom navigation and More menu show only routes the account may use.
- [ ] Switch English ↔ বাংলা; verify labels, dates, currency, and navigation update without logging out.
- [ ] Rotate or resize between mobile and desktop layouts; confirm the active page remains usable and no horizontal page overflow appears.
- [ ] Open and close the More sheet with its control, backdrop, and Escape where a hardware keyboard is available.

## Transactions

- [ ] Create a sale paid fully by cash. Verify product stock, invoice totals, payment, and receipt.
- [ ] Create a sale paid by bKash. Verify the selected payment method and saved amount.
- [ ] Create a sale with an outstanding balance. Verify due amount, customer ledger, and invoice.
- [ ] Edit a sale quantity and selling price, then save. Verify totals and actual stock allocation.
- [ ] Create a purchase with multiple products and quantities. Verify supplier, lot stock, cost, and payment.
- [ ] Return a sale item and verify returned quantity and remaining stock.
- [ ] Add an expense with category and payment method; verify it appears once in the expense report.
- [ ] Open a customer and supplier ledger; confirm transaction rows and totals match their source invoices.
- [ ] Check due collection only if an authorized collection action is present. The current application has no dedicated collection endpoint in this mobile flow; do not simulate collection by editing ledger data.

## Reports and navigation

- [ ] Open the Reports tab and each available report. Try Today, 7 days, This month, and a custom date range where offered.
- [ ] Download a report and confirm the CSV opens with the expected localized headings and values.
- [ ] Check the Business Overview cards, seven-day chart, and top products against the same business's source transactions.
- [ ] Confirm the stock report's total, low-stock, and out-of-stock counts against the inventory list.
- [ ] Verify unauthorized report links are absent or denied by the server.
- [ ] Switch to desktop layout and back to mobile; confirm filters, table/card rows, pagination, and route state remain correct.

## Record results

For every failed item, record the route, steps, expected and actual result, business role, language, viewport, and a screenshot with personal data removed. Recheck tenant isolation using two dedicated test businesses and non-sensitive fixtures.
