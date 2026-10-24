# Retail Nova — যেকোনো AI সহকারীর (Claude / ChatGPT / Codex) জন্য কাজের নিয়ম

কাজ শুরুর আগে এই ফাইল, `docs/MOBILE_DESIGN.md`, `docs/mockups/README.md` আর `docs/UI3_PROGRESS.md` (থাকলে) পড়বে।

## সাধারণ নিয়ম
- প্রতিটা ধাপ শেষে কমিট; কমিট ছাড়া কাজ ফেলে রাখবে না। `git add` ফাইল ধরে ধরে, `git add -A` নয়।
- কমিট করবে না: `*.sql`, `*.rar`, `exit`, `tests-output/`, `.env*`।
- push/merge শুধু বলা হলে।
- ডেস্কটপের view/আচরণ বদলাবে না (বলা না থাকলে)। রাউট, ঘরের `name`, ভ্যালিডেশন, ব্যবসার নিয়ম অপরিবর্তিত।
- সব কোয়েরি business scope মেনে (`ScopesByBusiness`, `BusinessContext`)। নতুন endpoint শুধু-পড়ার হলে একই middleware।
- রং শুধু `public/css/rn-tokens.css`-এর টোকেন থেকে; মোবাইলের স্টাইল `public/css/rn-m-components.css`-এ — পাতার নিজস্ব CSS ফাইল বানাবে না।
- বাংলা লেখা `lang/bn`, ইংরেজি `lang/en`, একই key। নিষিদ্ধ শব্দ: "মাল", "বেচা", "ক্রয়"।
- লিমিট বা সেশন শেষ হওয়ার আগে চলমান কাজ কমিট করে `docs/UI3_PROGRESS.md`-এ লিখবে: কোন ধাপ শেষ, কোনটা চলছে, পরের কাজ কী।

## মোবাইল ভার্সন
- `App\Support\MobileView` User-Agent দেখে ঠিক করে; cookie `rn_view` (mobile/desktop) দিয়ে বদলানো যায়।
- view-তে `@mobile … @endmobile` / `@desktop … @enddesktop`।
- টেস্টে মোবাইল দেখাতে `->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 13) Mobile')`।

## টেস্ট
- `php artisan test` (SQLite, `phpunit.xml`)। SQLite-এ `YEAR()`/`MONTH()` নেই, তাই `reports.business` টেস্টে 500 দেয় — এটা পুরোনো সমস্যা, regression নয়।

## স্ক্রিনশট (লগইনসহ, ডেভ ডাটাবেসে হাত না দিয়ে)
- Playwright রিপোতে ইনস্টল নেই; একটা কপি আছে:
  `C:/Users/Administrator/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright`
  এটা `PLAYWRIGHT_MODULE` env-এ দাও, সঙ্গে `BROWSER_EXECUTABLE=C:/Program Files/Google/Chrome/Application/chrome.exe`।
- আলাদা ডেটা: scratchpad-এ খালি SQLite ফাইল বানিয়ে
  `DB_CONNECTION=sqlite DB_DATABASE=<file> php artisan migrate --force`, তারপর একটা ডেমো দোকান আর admin seed
  (পাসওয়ার্ড `Hash::make`)। config cache নেই, তাই env দিয়ে .env-কে ওভাররাইড করা যায়।
- সার্ভার **PowerShell** থেকে চালাবে (Git Bash `/`-কে বদলে দেয়):
  `SESSION_PATH=/`, `SESSION_DRIVER=cookie`, `CACHE_STORE=array`, `APP_URL=http://127.0.0.1:8123`, তারপর
  `php artisan serve --port=8123`। খালি অবস্থার জন্য আরেকটা ফাঁকা SQLite দিয়ে :8124।
- লগইন: POST `/admin/login`, ঘর `userMail` আর `password` (selector `#userMail`, `#password`, `.rn-sign-in-button`)।
- Playwright-এ `isMobile` User-Agent বদলায় না — `userAgent`-এ "Mobile"/"Android" স্পষ্ট দিতে হবে, না হলে ডেস্কটপ ভার্সন আসে।
- মাপ ৩৯০×৮৪৪ (প্রয়োজনে ৩৬০×৭৪০)। ছবি `tests-output/<কাজ>/`-এ (gitignore করা)।
- Font Awesome kit 127.0.0.1-এ 403 দেয় — এটা স্বাভাবিক।
- স্ক্রিনশট নেওয়ার পর মকআপের সাথে নিজে মিলিয়ে দেখবে; শুধু টেস্ট পাস যথেষ্ট নয়।
