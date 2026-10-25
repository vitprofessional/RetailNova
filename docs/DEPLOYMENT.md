# RetailNova: প্রস্তুতি ও ডেপ্লয়মেন্ট নির্দেশিকা

এই নির্দেশিকা cPanel/WHM-এ PHP-FPM/Apache-সহ RetailNova চালুর জন্য। production বা staging সার্ভারে কাজ করার আগে একই release ও একই ধরনের MySQL-এ staging-এ rehearsal করুন। `scripts/deploy.sh` সার্ভার অপারেটরের জন্য; এই পরিবর্তন তৈরি করার সময় কোনো সার্ভারে এটি চালানো হয়নি।

## ১. সার্ভারের প্রয়োজন

- Laravel 12-এর জন্য PHP 8.2 বা নতুন সমর্থিত সংস্করণ লাগবে। RetailNova-এর Composer constraint `^8.2`; সার্ভার ও CLI—দুটিতেই একই PHP major/minor রাখুন। বর্তমানে EA4-এ উপলভ্য সংস্করণ WHM-এ যাচাই করুন।
- cPanel → **MultiPHP Manager**-এ domain/subdomain-এর PHP version ও PHP-FPM নির্বাচন করুন। এই পেজ extension install করে না। WHM → **Software → EasyApache 4 → Customize → PHP Extensions**-এ সংশ্লিষ্ট `ea-phpXX`-এর extension install/enable করুন; CLI-তে একই version-এর package মিলিয়ে নিন। cPanel-এর [MultiPHP Manager](https://docs.cpanel.net/cpanel/software/multiphp-manager-for-cpanel/) ও [EA4 PHP version/extension নির্দেশনা](https://docs.cpanel.net/ea4/php/how-to-locate-and-install-a-php-version-or-extension/) দেখুন।
- আবশ্যক PHP extension: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `gd`, `exif`, `intl`, `zip`, `bcmath`। Composer/Laravel package-এর platform check-ও চালাতে হবে।
- Composer 2, Git, MySQL/MariaDB client-এর `mysql` ও `mysqldump`, এবং cron প্রয়োজন। Shared hosting-এ এগুলোর CLI path hosting provider-এর কাছ থেকে নিন।

## ২. Web root ও ফাইল অনুমতি

সেরা ব্যবস্থা: domain-এর document root সরাসরি release-এর `public/` directory-তে দিন, যেমন `/home/ACCOUNT/retailnova/current/public`। `.env`, `app/`, `vendor/`, `storage/` ও অন্যান্য project source web root-এর বাইরে থাকবে।

যদি cPanel-এ document root বদলানো না যায়, project-এর **শুধু `public/`-এর contents** `public_html/`-এ প্রকাশ করুন; front controller-এর Laravel path-গুলো private project directory-র দিকে সঠিকভাবে নির্দেশ করান। root `.htaccess`-এর deny rules অতিরিক্ত প্রতিরক্ষা, document root-এ `public/` ব্যবহারের বিকল্প নয়। root-এ Laravel-এর `index.php` রেখে source tree web-এ উন্মুক্ত করবেন না। `.env`, dump, log, `vendor`, `storage`, `tests`, `docs` ও `.git` web থেকে অপ্রাপ্য কি না আলাদা করে HTTP দিয়ে পরীক্ষা করুন।

Deploy user-কে `storage/`, `bootstrap/cache/`, এবং `public/uploads/`-এ প্রয়োজনীয় write permission দিন; সাধারণত directory 775 ও ফাইল 664, তবে hosting-এর ownership policy অনুসরণ করুন। `public/uploads/.htaccess`-এর script execution নিষেধাজ্ঞা অক্ষত রাখুন। `chmod -R 777` ব্যবহার করবেন না।

## ৩. Database তৈরি

1. cPanel → **MySQL Database Wizard**-এ আলাদা production database ও সীমিত-অধিকার user তৈরি করুন। staging-এ আলাদা database/user/password নিন। cPanel prefix-সহ সম্পূর্ণ নাম লিখুন।
2. শুধু ওই database-এ `SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES` privilege দিন, কারণ migration-এ schema পরিবর্তন লাগে। shared server-এর নীতি অনুযায়ী privilege সামঞ্জস্য করুন।
3. password কেবল `.env` বা secret manager-এ রাখুন; Git, shell history, deploy output বা ticket-এ নয়। database user-কে অন্য database-এ access দেবেন না।

## ৪. `.env` কনফিগারেশন

`.env.example` কপি করে private project root-এ `.env` বানান। নিচের মানগুলো উদাহরণ, secret নয়। `APP_KEY` server-এ প্রথমবার `php artisan key:generate` দিয়ে তৈরি করে backup-এ সুরক্ষিত রাখুন; চালু হওয়ার পর এটি বদলাবেন না—এতে session/ciphertext অকার্যকর হতে পারে।

### Production উদাহরণ

```dotenv
APP_NAME="RetailNova"
APP_ENV=production
APP_KEY=base64:GENERATE_A_UNIQUE_KEY_ON_SERVER
APP_DEBUG=false
APP_URL=https://pos.example.com
APP_LOCALE=bn
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=Asia/Dhaka

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=CPANELPREFIX_retailnova
DB_USERNAME=CPANELPREFIX_rnuser
DB_PASSWORD=REPLACE_WITH_UNIQUE_SECRET

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14
CACHE_STORE=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
QUEUE_CONNECTION=sync
SETUP_TOKEN=TEMPORARY_LONG_RANDOM_VALUE_REMOVE_AFTER_SETUP
```

### Staging উদাহরণ

```dotenv
APP_NAME="RetailNova Staging"
APP_ENV=staging
APP_KEY=base64:GENERATE_A_DIFFERENT_STAGING_KEY
APP_DEBUG=false
APP_URL=https://staging-pos.example.com
APP_LOCALE=bn
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=Asia/Dhaka

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=CPANELPREFIX_rn_staging
DB_USERNAME=CPANELPREFIX_rnstage
DB_PASSWORD=REPLACE_WITH_DIFFERENT_STAGING_SECRET

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=debug
LOG_DAILY_DAYS=7
CACHE_STORE=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
QUEUE_CONNECTION=sync
SETUP_TOKEN=TEMPORARY_STAGING_ONLY_RANDOM_VALUE
```

`APP_ENV=staging`-এর জন্য server `.env`-এ `APP_DEBUG=false` রাখুন। Production ও staging-এর APP_KEY, DB, URL, cookie domain/path এবং backup আলাদা রাখুন। `SESSION_DOMAIN`/`SESSION_PATH` subdomain বা উপপথ অনুযায়ী ঠিক করুন; default root domain এবং `/` সাধারণত সঠিক। `DB_HOST`, DB ব্যবহারকারী ও পাসওয়ার্ড host অনুযায়ী বদলাবে। `SETUP_TOKEN` প্রথম Super Admin provision-এর পর মুছে ফেলুন। `MAIL_*` প্রয়োজন হলে provider অনুযায়ী যোগ করুন।

| Key | ব্যবহার |
| --- | --- |
| `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` | অ্যাপের নাম/পরিবেশ, encryption key, error detail বন্ধ রাখা এবং canonical HTTPS URL। |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_TIMEZONE` | ডিফল্ট ভাষা, অনুবাদ fallback এবং business/server-এর তারিখ-সময় অঞ্চল। |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Laravel database driver ও cPanel MySQL সংযোগ। Production/staging credential আলাদা। |
| `LOG_CHANNEL`, `LOG_STACK`, `LOG_LEVEL`, `LOG_DAILY_DAYS` | log destination/stack, কত detail log হবে এবং দৈনিক log কতদিন থাকবে। |
| `CACHE_STORE` | Laravel cache ও প্রথম-admin bootstrap lock-এর backend। একাধিক app node হলে shared atomic-lock সমর্থিত backend (Redis/database) configure করুন; local file cache এক node-এ সীমাবদ্ধ। |
| `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_SECURE_COOKIE`, `SESSION_SAME_SITE` | session storage, timeout, HTTPS-only cookie ও cross-site policy। |
| `QUEUE_CONNECTION` | queued jobs-এর backend; worker চালু না থাকলে app-এ সমর্থিত synchronous কাজের জন্য `sync`। |
| `SETUP_TOKEN` | প্রথম Super Admin bootstrap-এর সাময়িক shared secret; provision শেষে unset করতে হবে। |

`MAIL_*` কেবল outbound email ফিচার configure করলে লাগে। `FILESYSTEM_DISK`/object-storage key-গুলো app deployment-এ storage backend পাল্টালে আলাদাভাবে configure ও backup করতে হবে।

## ৫. প্রথমবার চালু

1. private directory-তে release checkout করুন; web root-এ কেবল `public/` দিন। `.env` লিখুন এবং permissions ঠিক করুন।
2. Composer dependencies: `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader`।
3. `php artisan key:generate` কেবল প্রথম setup-এ; তারপর `.env` backup করে private রাখুন।
4. DB ও upload path-এর backup ব্যবস্থা নিশ্চিত করুন।
5. `php artisan migrate --force`; এরপর `php artisan optimize` এবং `php artisan retailnova:preflight` চালান। প্রতিটি `✗` ঠিক না করা পর্যন্ত public traffic চালু করবেন না।
6. প্রথম Super Admin-এর জন্য সাময়িক শক্তিশালী `SETUP_TOKEN` সেট করে `/setup/superadmin`-এ একবারের provisioning সম্পন্ন করুন। এই flow-তে `FirstAdminBootstrap` transaction ও lock-এর মধ্যে প্রথম business, `superadmin`, এবং `BusinessProvisioner`-এর default units/accounts/expense categories তৈরি করে। এরপর token সরিয়ে config cache পুনর্নির্মাণ করুন (`php artisan optimize:clear`, তারপর `php artisan optimize`)। Setup route-কে public registration হিসেবে ব্যবহার করবেন না।
7. Super Admin দিয়ে login করে ব্যবসার নাম, যোগাযোগ, currency, logo, users/roles, units, accounts, expense categories ও settings যাচাই করুন। পরবর্তী shop কেবল অনুমোদিত Super Admin-এর Business Setup UI দিয়ে তৈরি করুন; `BusinessProvisioner` দরকার হলে app-এর service-কে ওই business ID দিয়ে চালান—request-এর ID কখনো বিশ্বাস করবেন না।
8. HTTPS, login, setup-route বন্ধ হওয়া, dashboard, sale/purchase, invoice (A4/thermal), image upload এবং business isolation smoke-test করুন।

## ৬. আপডেট এবং rollback

1. deploy window ঠিক করুন। একই সময়ের DB **এবং** `public/uploads/` + private receipt/storage backup নিন। backup project directory-র বাইরে রাখুন, আকার/exit status দেখুন এবং restore পরীক্ষা করা backup ব্যবহার করুন।
2. `scripts/deploy.sh --dry-run` দিয়ে command sequence পর্যালোচনা করুন। সার্ভারের project checkout-এ script চালানোর আগে `.env` থেকে DB values নিরাপদে environment-এ export করুন; script `.env` shell হিসেবে source করে না।
3. `scripts/deploy.sh` mysqldump, maintenance mode, `git pull --ff-only origin main`, Composer install, migration, optimization ও maintenance off চালায়। ব্যর্থ হলে script থামে এবং maintenance mode-এ রাখে; output-এ দেখানো backup ধরে অপারেটর কারণ নির্ণয় করবেন।
4. সফল deploy-এর পরে `php artisan retailnova:preflight`, HTTP smoke tests, login, totals, due, stock ও print test করুন। নতুন request/write শুরু হওয়ার আগে pre/post-upgrade snapshot তুলুন।
5. **Rollback:** maintenance চালু রেখে পূর্বের application commit/release-এ ফিরুন। migration rollback অন্ধভাবে করবেন না—ডেটা বদলানো migration-এর `down()` পুরোনো অর্থ ফেরত নাও দিতে পারে। schema/data incompatibility হলে maintenance অবস্থায় deploy-এর পরের DB backup নয়, deploy-এর ঠিক আগের একই সময়ের verified DB backup এবং uploads/private-files backup restore করুন; এতে deploy-পরবর্তী নতুন transaction হারাতে পারে, তাই restore অনুমোদন ও সময়সীমা ছাড়া করবেন না। পুরোনো code-এর সঙ্গে নতুন DB চালু করার আগে compatibility যাচাই করুন। restore/rollback শেষে preflight ও business totals মিলিয়ে তারপর `artisan up`।

Deploy script নিজে application code ফিরিয়ে দেয় না বা DB restore করে না। Migration ব্যর্থ হলে maintenance বন্ধ করার জন্য script স্বয়ংক্রিয় `artisan up` চালায় না।

## ৭. Cron, TLS ও backup

- cPanel → **Cron Jobs**-এ প্রতি মিনিটে scheduler চালান (PHP path cPanel MultiPHP/EA4-এর CLI path হতে হবে):

  ```cron
  * * * * * cd /home/ACCOUNT/retailnova/current && /usr/local/bin/ea-php83 artisan schedule:run >> /dev/null 2>&1
  ```

  WHM/host অনুযায়ী PHP path বদলান। `retailnova:preflight` scheduler heartbeat গত ৩ মিনিটে না থাকলে `✗` দেখাবে।
- WHM → **Manage AutoSSL**-এ provider/domain চালু করুন; cPanel → **SSL/TLS Status**-এ domain-এ certificate আছে নিশ্চিত করুন। HTTP→HTTPS redirect, renewal, canonical `APP_URL` যাচাই করুন।
- প্রতিদিন project root-ээс બહાર dated DB dump ও upload/private receipt archive নিন. উদাহরণ:

  ```cron
  15 2 * * * /usr/local/bin/mysqldump --defaults-extra-file=/home/ACCOUNT/.my-retailnova.cnf --single-transaction --quick --routines --triggers CPANELPREFIX_retailnova > /home/ACCOUNT/backups/retailnova-$(date +\%F).sql
  45 2 * * * tar -czf /home/ACCOUNT/backups/retailnova-files-$(date +\%F).tar.gz -C /home/ACCOUNT/retailnova/current public/uploads storage/app/private
  ```

  backup directory/credentials 0700/0600 রাখুন, command exit code/zero-length file/আকার monitor করুন, retention policy (যেমন daily 14, monthly 6) নির্ধারণ করুন। একই সার্ভারে backup যথেষ্ট নয়: encrypted `rclone`/`rsync`/provider backup দিয়ে আলাদা account/region-এ কপি করুন, transfer result alert করুন এবং নিয়মিত restore drill করুন। private receipt archive-এ customer-sensitive data থাকতে পারে; access সীমিত ও encryption at rest প্রয়োগ করুন।

## ৮. পুরোনো release থেকে আপগ্রেডের সতর্কতা

Migration চালানোর আগে source migration list পর্যালোচনা করুন, DB+uploads backup নিন এবং সম্ভব হলে `php artisan retailnova:verify-upgrade snapshot --output=before.json` চালান; আপগ্রেডের পরে `snapshot --output=after.json`, তারপর `compare before.json after.json`। JSON-এ business-wise sales, customer due, stock quantity ও business-owned table record counts থাকে; difference নিজে থেকেই ভুল প্রমাণ করে না, প্রতিটি পার্থক্য ব্যবসায়িকভাবে ব্যাখ্যা করতে হবে।

বিশেষভাবে data পরিবর্তন/ঝুঁকির migration:

- `2025_11_21_140000_convert_money_columns_to_decimal`: খালি string amount-কে `NULL` করে, monetary columns decimal-এ বদলায়।
- `2025_11_22_000002_convert_provide_services_amount_to_decimal`: service amount trim/decimal করে; invalid legacy value database driver অনুযায়ী `0.00` হতে পারে।
- `2025_11_22_000003_backfill_provide_services_rate`: service rate-এ amount/quantity backfill করে।
- `2026_10_23_100000_isolate_tenant_data_by_business` (C7): tenant `businessId` backfill করে; shared master records business অনুযায়ী copy/split হতে পারে, unresolved legacy rows null থাকতে পারে, uniqueness business scope-এ বদলায়।
- `2026_10_25_100000_backfill_admin_users_for_single_business`: একটিমাত্র business থাকলে null `businessId` tenant admin-দের সেখানে যুক্ত করে; শুধু role null/খালি থাকলে ID-তে প্রথম eligible non-superadmin `admin`, পরের missing-role user-রা `sales` হয়। আগে থেকে থাকা role বদলায় না। একাধিক business হলে কিছু বদলায় না, warning log হয়। এর `down()` ইচ্ছাকৃত no-op; inferred access assignments স্বয়ংক্রিয়ভাবে ফেরত নেওয়া নিরাপদ নয়।
- `2026_10_23_130000_add_cancellation_to_sale_products_and_invoice_items`: cancellation/soft-delete metadata; rollback করলে cancelled records আবার দৃশ্যমান হতে পারে।
- `2026_10_21_000000_change_business_location_and_invoice_footer_to_text_on_business_setups`: string field প্রশস্ত করে; rollback-এ বড় value truncate/fail হতে পারে।
- VAT/payment method/image/businessId migration-গুলো সাধারণত নতুন nullable/default column যোগ করে; actual installed migration files ও target branch মিলিয়ে যাচাই করুন।

Release-এর আগে/পরে প্রতি business-এ বিক্রির grand total, cancellation বাদ দিয়ে; customer opening balance + unpaid live sale due; stock lot/current quantity; sale/purchase/serial counts, orphan/NULL `businessId`, duplicate records, products/brands/categories/units ownership, users/roles, purchase cost/COGS, returns, taxes/discounts, payment totals, invoice/serial allocations এবং uploads/logo/receipt reachability মিলিয়ে দেখুন। নতুন admin backfill-এর পর `php artisan retailnova:preflight`-এ non-superadmin `admin_users` ID list খালি এবং প্রতিটি admin-এর role/business assignment সঠিক কি না যাচাই করুন। C7 বা admin backfill-এর unresolved rows নিজে অনুমান করে assign করবেন না। `migrate:rollback`-কে backup restore-এর বদলি হিসেবে ব্যবহার করবেন না।

`retailnova:verify-upgrade` হলো read-only snapshot/compare, migration নয়; snapshots-এ aggregate ও record counts থাকে, ব্যক্তিগত customer data থাকে না।
