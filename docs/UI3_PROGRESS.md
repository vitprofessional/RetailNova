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

Branch: `ui-2-mobile-pages`; work resumes from `1ab5c1e`. The UI-3R shared mobile form and list patterns are being implemented in three reviewable commits. Form rules and list pattern changes are locally verified at 390×844 using the documented isolated SQLite scratchpad and Android Mobile user agent. Screenshots and mockup comparison notes are ignored under `tests-output/ui-3R/`.

- UI-3R.1 form rules: shared one-column form rules, file picker, compact row removal, optional-field omission, and form regression tests. Focused tests: 27 tests, 542 assertions, 0 failures. Full suite: 328 tests, 10,415 assertions, 0 failures; six existing PHPUnit deprecations.
- UI-3R.2 list pattern: six UI-2 lists use a teal search/period header, date groups, transaction card hierarchy and chip/date filter sheet. The six actual 390px list screenshots and filter screenshots are under `tests-output/ui-3R/lists/`.
- UI-3R.3 wording: update Bangla labels for general walk-in customer, expense/cost, unit, and save actions; replace remaining English quotation product placeholder with the language key.

Secondary X pages from the prior checkpoint remain for business setup, admin profile/user, Super Admin, settings, and brand/category/unit/service master forms. These will continue after the three UI-3R commits.