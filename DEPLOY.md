# Deploying EskwelaHub to Hostinger shared hosting

Target: a subdomain (for example `portal.yourschool.example`) on Hostinger **shared** hosting.
No VPS, no Redis, no always-running workers. Everything below uses the Hostinger panel (hPanel) and, if your plan has it, SSH.

> Laravel 13 needs **PHP 8.3 or higher**. Open hPanel, Advanced, PHP Configuration, and pick 8.3 or 8.4 for the subdomain.
> If only 8.2 is offered, the app cannot run on that plan.

## 0. Before you start

PHP extensions to enable in hPanel (PHP Configuration, Extensions): `openssl`, `pdo_mysql`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`, `zip`, `bcmath`, `curl`.

Build everything on your own computer, because shared hosting often has no Node:

```powershell
composer install --no-dev --optimize-autoloader   # vendor/ without test tools
npm ci
npm run build                                     # creates public/build (not in git)
```

Keep a copy of your normal dev `vendor/` (run `composer install` again afterwards for development).

## 1. Create the database

hPanel, Databases, MySQL Databases: create a database, a user and a strong password. Note the three values. Hostinger prefixes names (for example `u123456789_eskwela`).

## 2. Choose your folder layout

The goal: the web server serves **only** the `public/` folder. Everything else (`.env`, `app/`, `storage/`, `vendor/`) must stay outside the web root.

### Case A: the subdomain's document root can point to `public/` (preferred)

hPanel, Websites, Subdomains (or Advanced, Subdomains): create the subdomain and set its **document root / custom folder** to the app's `public` folder.

1. Upload the whole project (zip, then extract with the File Manager) to a folder outside `public_html`, for example `/home/u123456789/eskwelahub/`.
   Include `vendor/` and `public/build/`. Leave out `node_modules/`, `.git/` and your local `.env`.
2. Set the subdomain document root to `/home/u123456789/eskwelahub/public`.
3. Done: no file edits are needed.

### Case B: the web root is fixed to `public_html` (or `public_html/subdomain-name`)

You cannot change the document root, so split the project in two:

1. Upload the project (without the `public/` folder content) to a folder **outside** the web root, for example `/home/u123456789/eskwelahub/`.
2. Copy the **contents of `public/`** (`index.php`, `.htaccess`, `build/`, `favicon.ico`, `robots.txt`) into the web root, for example `/home/u123456789/domains/yourschool.example/public_html/portal/`.
3. Edit `index.php` in the web root and point the two paths to the app folder:

```php
// before
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
// after (adjust the path to your account)
require '/home/u123456789/eskwelahub/vendor/autoload.php';
$app = require_once '/home/u123456789/eskwelahub/bootstrap/app.php';
```

   If `index.php` also has a maintenance line (`__DIR__.'/../storage/framework/maintenance.php'`), change it the same way.
4. Every time you run `npm run build`, copy the new `public/build/` folder into the web root again.
5. Check in the browser that `https://portal.yourschool.example/.env` and `/storage/` return 403 or 404.

## 3. Configure `.env` on the server

Create `/home/u123456789/eskwelahub/.env` from `.env.example` (File Manager, or `cp .env.example .env` over SSH). Never upload your local `.env`.

```ini
APP_NAME=EskwelaHub
APP_ENV=production
APP_DEBUG=false
APP_URL=https://portal.yourschool.example
APP_TIMEZONE=Asia/Manila

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_eskwela
DB_USERNAME=u123456789_eskwela
DB_PASSWORD=the-password-from-step-1

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=file

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=noreply@yourschool.example
MAIL_PASSWORD=mailbox-password
MAIL_FROM_ADDRESS=noreply@yourschool.example
MAIL_FROM_NAME="${APP_NAME}"

REQUIRE_STAFF_2FA=true
```

`APP_URL` must be the real https address: passkeys and signed download links depend on it.
`APP_DEBUG` must stay `false` in production. Never commit `.env`.

## 4. First install (SSH)

Hostinger plans with SSH: hPanel, Advanced, SSH Access. Then:

```bash
cd ~/eskwelahub
php -v                       # must say 8.3 or higher; if not, use the PHP path from hPanel
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force   # roles and permissions only, no fake data
php artisan db:seed --class=FaqSeeder --force              # starter chatbot answers (the school must review them)
php artisan eskwelahub:school-year 2026 --current          # the school year that is open for enrollment (change the year)
php artisan eskwelahub:create-user principal@yourschool.example admin --name="Full Name"
php artisan storage:link     # only needed if you later publish files in storage/app/public; the app keeps records in private storage
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Never run `php artisan db:seed` with the fake seeders on production. `DatabaseSeeder` already skips them when `APP_ENV=production`.

**No SSH on your plan?** Use a temporary cron job instead: in hPanel, Cron Jobs, add a job that runs one command (for example `/usr/bin/php /home/u123456789/eskwelahub/artisan migrate --force`), wait for it to run, then delete the job. Repeat for each command above. `eskwelahub:create-user` asks for a password interactively, so it needs SSH; without SSH, ask Hostinger support to enable it for the first install. Do not run commands locally against the production database.

## 5. Cron job (queue and schedule)

Shared hosting cannot keep a queue worker alive. The app runs the queue from the Laravel scheduler once a minute (`routes/console.php`).

hPanel, Advanced, Cron Jobs, add one job, **every minute**:

```
* * * * * /usr/bin/php /home/u123456789/eskwelahub/artisan schedule:run >> /dev/null 2>&1
```

The PHP path differs by plan. Use the PHP 8.3+ path shown in hPanel (for example `/opt/alt/php83/usr/bin/php`). Test it with `which php` over SSH, or run the command once by hand.

Queued work (emails, PDFs from Phase 2 onward) is processed within about a minute.

## 6. Updating the app later

1. Locally: `git pull`, `composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run build`.
2. Upload changed files (and `vendor/` plus `public/build/` when they changed). In Case B also copy `public/build/` and any changed `public/` files to the web root.
3. On the server:

```bash
php artisan down
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
php artisan up
```

## 7. Backups

Student records are the most important data in the system.

- **Database:** hPanel has automatic backups on most plans; also keep your own. A daily cron (set a password-less `~/.my.cnf` first, or use hPanel's backup download):

```
0 2 * * * mysqldump --single-transaction -u DBUSER DBNAME | gzip > /home/u123456789/backups/db-$(date +\%F).sql.gz
```

- **Files:** copy `storage/app/private/` (uploads and generated documents) to a second place regularly.
- **Offsite:** download a backup to the school's computer or a cloud drive weekly. Test a restore at least once.
- Delete old backups after 30 days and protect the backup folder (it contains personal data of minors).
- Keep `.env` in the school's password manager, not in the backups folder.

## 8. Security checklist before go-live

- [ ] `APP_DEBUG=false`, `APP_ENV=production`, `APP_URL` uses https
- [ ] `https://portal.../.env` and `https://portal.../storage/` are not reachable
- [ ] No demo accounts exist (`admin@eskwelahub.test` and the others are local only)
- [ ] The admin and every staff account turned on two-factor authentication
- [ ] Cron job runs every minute (check `storage/logs` for errors)
- [ ] Backups run and a restore was tested
- [ ] Only real staff have the `staff` or `admin` role
- [ ] Privacy notice and data processing agreement are ready (RA 10173)

## Troubleshooting

| Symptom | Check |
|---|---|
| 500 error, blank page | `storage/logs/laravel.log`; folder permissions on `storage/` and `bootstrap/cache/` (775) |
| Page without styles | `public/build/` missing or outdated in the web root; `APP_URL` wrong |
| "Class not found" after upload | upload `vendor/` again; run `composer dump-autoload -o` |
| Emails never arrive | SMTP values; the cron job (emails are queued); `jobs` and `failed_jobs` tables |
| Cannot reach staff pages | the account needs 2FA turned on (Settings, Security) |

## 9. Features that need extra care on shared hosting

| Feature | What to know |
|---|---|
| **School years** | Enrollment needs one current school year. At the start of each school year run `php artisan eskwelahub:school-year 2027 --current` (use the new start year). |
| **Queue and cron** | PDFs, emails and the nightly duplicate check run from the queue. If the cron job stops, nothing is generated and no email is sent. Check `jobs` and `failed_jobs` in phpMyAdmin when something seems stuck. |
| **OCR (digitizing old records)** | The default engine, Tesseract.js, runs in the staff member's browser and downloads its language data from a public CDN the first time, so the staff computer needs internet. Nothing from the scan is sent to a third party. Set `OCR_DRIVER=ocr_space` and `OCR_SPACE_KEY` only if the school agrees to send scans to that service. |
| **PDF fonts** | PDFs use the built-in Helvetica font, so they are small (about 5 to 20 KB). |
| **Excel export** | Needs the PHP extensions `zip`, `xml` and `gd` (listed in section 0). If the download fails with a 500 error, check them in hPanel. |
| **FAQ chatbot** | Answers live in the table `faq_entries`. The school must review them before go-live: fees, dates and office hours are intentionally not included. To let a language model answer (optional, paid by the school): set `CHATBOT_DRIVER=llm` and `ANTHROPIC_API_KEY` in `.env`; only the typed question and the FAQ text are sent. |
| **Private files** | Uploads, scans and PDFs are in `storage/app/private`. Include this folder in every backup (section 7) and never move it into `public`. |
| **Unique job locks** | The PDF job uses a lock that expires after 5 minutes, so a crashed worker cannot block a request for ever. After 5 minutes the staff "Generate PDF again" button works again. |
