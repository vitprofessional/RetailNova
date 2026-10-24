# UI-3 Progress Checkpoint

Repository branch: `ui-2-mobile-pages`
Checkpoint HEAD before this document commit: `747d9c7a633034aaf63e2824e68a776b9cf3b0c1`
Local verification only; no production access or data changes.

## Completed and committed

- UI-3.0 desktop fixes: `a56738d72b4329d2dd5e78d9b1f896b5e1cef56f`
- UI-3.2a mobile product form: `35efad9aeb984c43b30455b4b06a8c838a852577`
- UI-3.2b mobile purchase form: `18d64827e8510ffbb782ef61bfb2c04ca91a2bbd`
- UI-3.2c mobile purchase invoice edit: `8613564d8a7e51f25fc981e2ee94758b5d534284`
- UI-3.3 mobile sale/purchase returns: `f95c36811ffa488b15960a5d8e715b8a7fd1804c`
- UI-3.4 mobile quotations: `0b2c9d7a900482d754ca8a1f39cd8c25baa1b5f1`
- UI-3.5 mobile service create: `5d8cc4f35507782fc46edd4ecdbec2738f7899d5`
- UI-3.6 mobile expense forms: `529cbc09a40ea47a34418260435a5055d96dc0d2`
- UI-3.7 mobile damage form: `8658f4dc4f8a2df1a141c7672269deea0eb40f1d`
- UI-3.8 mobile sale edit: `a94c802ef00773d843482e7b51c5fa8a57c5194a`
- UI-3.9 mobile sale item editor: `fc26355f6058634beb9b29db71b066d28b8ad45f`
- UI-3.10 mobile RMA forms: `bef6c9928251757e984a98e02d56b2ae3bb8c450`
- UI-3.11 mobile accounts: `ae12616089a4a9fbf0a8d24231dea74c47975379`
- UI-3.12 mobile business locations: `26cd77c171b30e0745bc95d9283c83123eeb44a5`
- UI-3.13 mobile expense categories: `747d9c7a633034aaf63e2824e68a776b9cf3b0c1`

UI-2 was compared with approved mockups 09/09b/10; no additional UI-2 source corrections were needed.

## Latest verification

- Full isolated SQLite suite: 324 tests, 10,378 assertions, 0 failures, 6 PHPUnit deprecations.
- Focused account, location, and expense-category mobile form tests pass.
- Browser screenshots for account, location, and expense-category create/edit screens are under ignored `tests-output/ui-3/7-accounts/` at 390x844 and 1440x900.
- Authenticated local browser checks returned HTTP 200 with no horizontal overflow at 390px; desktop actions remained static at 1440px.
- Browser reported failed-resource 403 entries (no page JavaScript exceptions); investigate their exact external asset source before claiming a clean console.

## Remaining inventory to review

Primary UI-3 transactional forms have mobile coverage: product, purchase entry/invoice editing, sale create/edit/items, returns, quotations, service create, expenses, damage, RMA, account entry, business locations, and expense categories. Customer/supplier forms are UI-2.

Secondary/administrative forms not adapted in this checkpoint include business setup, admin profile/user management, Super Admin screens, business language/sales settings, brand/category/unit/service master edit/list forms, and service edit. The legacy `/purchase/edit/{id}` handler still rejects updates by design and should not be given a new UI without resolving that separate workflow decision.

## Working tree note

Preserve untracked `docs/AGENT_NOTES.md`; it was intentionally not staged. Browser screenshots/scripts and local fixtures under `tests-output/ui-3/` are ignored.

## UI-3R refinement checkpoint

Branch: `ui-2-mobile-pages`; work resumed from `1ab5c1e`. The three requested refinement commits are complete. Actual Laravel screenshots were captured at 390×844 with the documented isolated SQLite scratchpad and Android Mobile user agent, then visually reviewed against mockups 09/09b/10. Screenshots and comparison notes are ignored under `tests-output/ui-3R/`.

- UI-3R.1 form rules: `1dd7e0d0fa3de03c4699611e86d8c76df14ab4d4` — shared one-column form rules, file picker, compact row removal, optional-field omission, and form regression tests.
- UI-3R.2 list pattern: `b8cc1aafda62ce4156e282a91df1b7fd88638051` — six UI-2 lists use a teal search/period header, date groups, transaction card hierarchy and chip/date filter sheet.
- UI-3R.3 wording: `ddede68dba551898623c3fe98be371700fa2222c` — Bangla labels for general walk-in customer, expense, unit, and save actions; English walk-in labels corrected and quotation product placeholder uses a language key.
- UI-3R.3 full isolated SQLite suite: 328 tests, 10,415 assertions, 0 failures; six PHPUnit deprecations.

Refreshed 390px screenshots: all six lists and their filters returned HTTP 200 with no horizontal overflow. Updated mobile forms were recaptured and visually checked; browser console reports the documented Font Awesome kit 403 responses, without page JavaScript exceptions.

The prior checkpoint's remaining setup/admin/settings/catalog forms have now been adapted to the shared mobile form rules: business setup, admin profile/user, one-time Super Admin, language/sales settings, brand/category/unit, and service create/edit. They were captured at 390×844 and compared with the shared form pattern; screenshots and details are in ignored `tests-output/ui-3R/forms/secondary/` and `tests-output/ui-3R/capture-notes.md`. The normal local scratchpad correctly keeps first-run Super Admin provisioning closed; the screenshot was taken from an empty isolated SQLite fixture without submitting it. Master-data forms preserve their existing legacy tables below the mobile form, which remain visually dense at narrow widths.

The secondary-form focused mobile rules/list tests pass (15 tests, 338 assertions). The latest full isolated SQLite suite passes (329 tests, 10,465 assertions, 0 failures, six PHPUnit deprecations). Blade view cache compilation and `git diff --check` pass. Final secondary-form code and test changes are grouped in a separate reviewable follow-up commit. The `docs/AGENT_NOTES.md` file remains untracked and is not part of any commit.

## UI-3 remaining refinements — Phase A

Resumed at `2a7477c` on `ui-2-mobile-pages`; committed as `90f749c` (`UI-3R.5: polish`). Phase A changes cover sale-item editor summary and mobile add-row behavior, POS-style purchase/quotation item pickers, customer/supplier transaction metadata, localized mobile date/time helpers, and filter/stock labels. Browser captures from the authenticated local Laravel app (390×844, Android mobile user agent, `networkidle` plus 500 ms) are in ignored `tests-output/ui-3-final/A/`; the visual comparison is in that directory's `capture-notes.md`.

- Latest full isolated SQLite suite: 330 tests, 10,499 assertions, 0 failures, 6 PHPUnit deprecations.
- Focused mobile lists/forms/purchase tests: previously 18 tests, 456 assertions, passing. Re-run for final phase gate.
- Browser pages captured: sale item editor, customer/supplier list and filter sheets, new purchase, quotation create/edit. See capture results for individual HTTP status and overflow checks.
- Remaining known visual/behavior details are recorded in capture notes.

Next: finish and commit UI-3R.5, then work through master-data forms (UI-3.4b), details (UI-3.D), remaining lists (UI-3.L), reports (UI-3.R), and Super Admin lists (UI-3.S) in separate reviewed increments. The untracked `docs/AGENT_NOTES.md` remains preserved and unstaged.

## UI-3.4b — Master-data mobile lists and forms

Implemented mobile card lists with compact action menus and a “new” FAB/form transition for brand, category, unit, and service. Expense-category listing now has matching mobile cards with its existing create/edit/delete routes. Desktop tables remain the desktop markup. Screenshots and visual notes are in ignored `tests-output/ui-3-final/B/`.

- Browser: all five list routes returned HTTP 200 at 390×844, no viewport overflow; real screenshots were reviewed against mockups 09/10.
- The first expense-category capture tried a wrong URI and got 404; route:list confirmed `/expenses/categories`, and the corrected recapture passed.
- Focused `MobileMasterDataListsTest`: 3 tests, 31 assertions, passing before the final full-suite gate.
- Commit: `2149dc7` (`UI-3.4b: mobile master-data lists`). Full suite at the phase gate: 333 tests, 10,556 assertions, 0 failures, 6 PHPUnit deprecations. Focused master-data/tenant tests: 23 tests, 150 assertions. Blade cache compilation and `git diff --check` passed.
- Remaining lists, reports, and Super Admin lists are still not started.

## UI-3.D — Mobile detail screens (current phase)

Added screen-only mobile summary blocks and labeled line cards to sale invoice, purchase invoice, quotation detail, service invoice, and sale-return detail. Mobile transaction dates use `MobileDateTime`; existing printer stylesheet/print media behavior is untouched. The mobile bottom navigation no longer overlays these detail screens. Capture and comparison notes are in ignored `tests-output/ui-3-final/C/`.

- Five available authenticated GET detail routes returned HTTP 200 at 390×844 with no horizontal overflow or page exceptions after adding local-only fixture rows to the isolated screenshot database. Final screenshots were opened and visually reviewed against mockup 04.
- Focused `MobileDetailLayoutTest` and `InvoiceCodeUnitTest`: 3 tests, 28 assertions, passing. The initial full suite found one brittle assertion expecting `<td class="text-center">` exactly; the new accessible `data-label` attribute changed tag attribute order. Updated the assertion to verify the warranty label and value together without weakening the check.
- Latest complete isolated SQLite suite: 335 tests, 10,620 assertions, 0 failures, 6 PHPUnit deprecations. Blade cache compilation and `git diff --check` pass.
- Mockup 04 comparison remains partial: sale invoice retains its full print-oriented A4/thermal structure, unsupported SMS/share/PDF actions were not invented, and no separate customer/supplier detail routes exist. See capture notes.
- Next: commit this detail increment with these differences recorded; then proceed to remaining lists, reports, and Super Admin lists.
