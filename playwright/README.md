Playwright smoke test for RetailNova
Playwright smoke tests for RetailNova

Prerequisites:
- Node.js (14+)
- npm
- The app running locally: `php artisan serve --host=127.0.0.1 --port=8000`

Install:

```powershell
cd playwright
npm install
npx playwright install
```

Run tests:

```powershell
# from repo root
npx playwright test playwright/tests/paid-due.spec.js
```

Notes:
- Tests assume at least one product exists and the Add Purchase page is at `/purchase/add`.
- If your server runs at a different host/port, modify the URL in the spec.
