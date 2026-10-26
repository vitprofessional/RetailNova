# Mobile quality work progress

Branch: `quality-v2`
Checkpoint HEAD: `d686250` (Q6 committed; all Q stages complete)
Resume backup: `tests-output/resume-backup.patch` (tracked diff only; untracked mockups are preserved in place).

## Q stage status

| Stage | Status | Commit / notes |
|---|---|---|
| Q0 — spec and purchase picker mockups | Complete | `5993a30` |
| Q1 — font, tokens, money components, audit | Complete | `f503688` |
| Q2 — tenant backfill and admin invoice access | Complete | `1a81be0` |
| Q3 — mobile type, spacing, number rules | Complete | `a49fb0d` |
| Q4 — documents, print sizes, PDF and image share | Complete | Shared templates for sale/purchase invoices, quotations, service invoices, returns and ledgers; A4/thermal80/thermal58; fixed PNG canvas export; local QR and file share fallback. |
| Q5 — shared image product picker | Complete | Commit `4b9c8fb`; sale, purchase, quotation and purchase-return screens use the tenant-scoped server picker. |
| Q6 — mobile purchase flow | Complete | Commit `d686250` (`Q6: professional mobile purchase flow`). |

## Resume inventory

- Q4 changes: business default-paper setting, mobile receipt action sheet, local browser document bundle, translations, shared print controller/templates, migration, and regression tests. Browser tests follow legacy print routes to the shared templates.
- Q6 references: `docs/mockups/13-purchase-main.png`, `14-purchase-line-sheet.png`, `15-purchase-payment.png`; `docs/mockups/README.md` adds their index entries without removing previous rows.
- No stash entries were present at the Q restart check. No source change was discarded.

## Verification checkpoint

- Q3 full isolated SQLite suite: 370 passed, 11,409 assertions, 0 failures; 6 legacy risky tests.
- Q3 MobileQualityAudit: 45 routes, 0 findings, 0 page errors (external Font Awesome 403 responses are documented).
- Q4 focused tests: 14 tests, 158 assertions passed; related invoice/branding/payment set: 31 tests, 372 assertions passed.
- Q4 full isolated SQLite suite: 378 tests, 11,474 assertions, 0 failures; 6 legacy risky tests and 6 PHPUnit deprecations.
- Q4 MobileQualityAudit: 45 routes, 0 violations, 0 console errors (`tests-output/quality/q4-final/audit.json`).
- Q4 Chrome PDF matrix: all 8 document types × 3 paper sizes returned 200 and produced one correctly sized page; no app chrome in the print DOM. The matrix PDFs and the actual PNG/PDF/share-fallback downloads are under ignored `tests-output/q4-browser/`. Visual checks included the generated thermal PNG and A4/thermal print renders.
- Q4 corrected a browser-discovered PNG export failure (`canvas.toBlob` callback signature) and thermal export CSS that constrained the business name and left a QR block in the raster output.
- Q5 implementation: reusable `x-m.product-picker` with tenant-scoped name/barcode/brand search, combined category and brand filters, 20-item pagination, product thumbnails/fallbacks, purchase quantity/price sheet, and eligible-lot purchase-return selection. Existing sale/purchase/quotation field names and server validation remain authoritative.
- Q5 focused feature tests: 3 tests, 52 assertions passed. Related mobile shell/form/purchase regression group: 25 tests, 718 assertions passed.
- Q5 full isolated SQLite suite after the final cost-visibility restriction: 381 tests, 11,549 assertions, 0 failures/errors; 6 legacy risky tests and 6 PHPUnit deprecations.
- Q5 MobileQualityAudit after final changes: Bengali Android UA at 390×844, 45 routes, 0 findings, 0 console errors (`tests-output/quality/q5/audit.json`).
- Q5 visual/browser checks: sale, purchase and purchase-return picker screens and purchase sheet captured under ignored `tests-output/q5/`; 20 products loaded, the purchase sheet populated the existing row (qty 1, buy 26, sale 46), and selecting a return lot opened the existing return form. Comparison notes are in `tests-output/q5/capture-notes.md`.
- Q6 implementation: mobile purchase main flow, supplier search sheet, date/invoice chips and more-information sheet, shared product picker sheet, editable line sheet (serial/VAT/warranty), discount/payment sheet, desktop payment-method selectors, server persistence and legacy-safe payment-method migration. Nullable warranty days are stored in a separate migration because the payment migration had already run in the isolated browser database.
- Q6 focused purchase tests: 30 tests, 507 assertions passed.
- Q6 full isolated SQLite suite: 384 tests, 11,705 assertions, 0 failures/errors; 6 PHPUnit deprecations and 6 existing risky output-buffer tests.
- Q6 MobileQualityAudit: Bengali Android UA at 390×844, 45 routes, 0 violations, 0 application console errors (`tests-output/quality/q6-final/audit.json`).
- Q6 screenshots: empty purchase, three lines, line sheet and payment step under ignored `tests-output/q6/`; comparison notes at `tests-output/q6/capture-notes.md`. The 390px screenshots were opened and reviewed against mockups 13–15; the date chip width was adjusted so the full Bengali date is visible. Sample fixture thumbnails are synthetic color tiles, not production product photography. A known external Font Awesome kit request returns HTTP 403, with no app-origin JavaScript errors.

Next: merge `quality-v2` into `main`, then push after verifying the final commit and branch state.
