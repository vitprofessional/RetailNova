# Mobile quality work progress

Branch: `quality-v2`
Checkpoint HEAD: `a49fb0d3a7f3bf3873919f720863f4e74f3a4795`
Resume backup: `tests-output/resume-backup.patch` (tracked diff only; untracked mockups are preserved in place).

## Q stage status

| Stage | Status | Commit / notes |
|---|---|---|
| Q0 — spec and purchase picker mockups | Complete | `5993a30` |
| Q1 — font, tokens, money components, audit | Complete | `f503688` |
| Q2 — tenant backfill and admin invoice access | Complete | `1a81be0` |
| Q3 — mobile type, spacing, number rules | Complete | `a49fb0d` |
| Q4 — documents, print sizes, PDF and image share | Complete | Shared templates for sale/purchase invoices, quotations, service invoices, returns and ledgers; A4/thermal80/thermal58; fixed PNG canvas export; local QR and file share fallback. |
| Q5 — shared image product picker | Not started | |
| Q6 — mobile purchase flow | Not started | Mockups 13–15 are present and README index rows were added. |

## Resume inventory

- Q4 changes: business default-paper setting, mobile receipt action sheet, local browser document bundle, translations, shared print controller/templates, migration, and regression tests. Browser tests follow legacy print routes to the shared templates.
- Q6 references: `docs/mockups/13-purchase-main.png`, `14-purchase-line-sheet.png`, `15-purchase-payment.png`; `docs/mockups/README.md` adds their index entries without removing previous rows.
- No stash entries were present. No source change was discarded.

## Verification checkpoint

- Q3 full isolated SQLite suite: 370 passed, 11,409 assertions, 0 failures; 6 legacy risky tests.
- Q3 MobileQualityAudit: 45 routes, 0 findings, 0 page errors (external Font Awesome 403 responses are documented).
- Q4 focused tests: 14 tests, 158 assertions passed; related invoice/branding/payment set: 31 tests, 372 assertions passed.
- Q4 full isolated SQLite suite: 378 tests, 11,474 assertions, 0 failures; 6 legacy risky tests and 6 PHPUnit deprecations.
- Q4 MobileQualityAudit: 45 routes, 0 violations, 0 console errors (`tests-output/quality/q4-final/audit.json`).
- Q4 Chrome PDF matrix: all 8 document types × 3 paper sizes returned 200 and produced one correctly sized page; no app chrome in the print DOM. The matrix PDFs and the actual PNG/PDF/share-fallback downloads are under ignored `tests-output/q4-browser/`. Visual checks included the generated thermal PNG and A4/thermal print renders.
- Q4 corrected a browser-discovered PNG export failure (`canvas.toBlob` callback signature) and thermal export CSS that constrained the business name and left a QR block in the raster output.

Next: commit Q4 after reviewing its staged scope. Then implement Q5 and Q6 as separate commits, updating this file after each commit. Merge/push only after all required gates pass.
