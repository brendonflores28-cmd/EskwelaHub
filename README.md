# EskwelaHub

Integrated school portal for **Cawayan National High School** (Bongabong, Oriental Mindoro).
Capstone project. Student records are personal data of minors, so the app follows RA 10173:
private file storage, role-based access, two-factor authentication for staff and a full audit log.

**Status:** Phase 2 of 5 done (document requests, PDF with QR code, notifications).

| Phase | Scope | State |
|---|---|---|
| 1 | Setup, auth, roles, student records, search, audit log | Done |
| 2 | Document requests, PDF with QR code, notifications | Done |
| 3 | Online enrollment, OCR upload | |
| 4 | Duplicate detection, survey with sentiment, FAQ chatbot | |
| 5 | Principal dashboard, forecast, Excel export | |

## Stack

Laravel 13 (PHP 8.3 or higher), Inertia 3, Vue 3 + TypeScript, Tailwind, MySQL 8.
Spatie Permission (roles) and Spatie Activitylog (audit log). Fortify two-factor authentication.
Queue, session: `database`. Cache: `file`. No Redis, Octane, Horizon or Reverb, so it runs on shared hosting.
`composer.json` pins `platform.php` to 8.3 so packages stay compatible with the host.

## Run it locally (Laragon)

```powershell
composer install
npm install
copy .env.example .env        # then: php artisan key:generate
# create the database "eskwelahub" (utf8mb4) in HeidiSQL or MySQL
php artisan migrate --seed    # roles + FAKE students (1999 to now) + FAKE demo accounts
npm run dev                   # in one terminal
php artisan serve             # in another, then open http://127.0.0.1:8000
```

Demo accounts (fake, created by the local seeder only; password is `SEED_PASSWORD`, default `password`):

| Account | Role |
|---|---|
| `admin@eskwelahub.test` | Principal / Admin |
| `staff@eskwelahub.test` | Registrar / Staff |
| `parent@eskwelahub.test` | Student / Parent (one approved link) |
| `alumni@eskwelahub.test` | Student / Alumni (one pending link) |

Staff and admin must turn on two-factor authentication (Settings, Security) before they can use the staff portal.
For quick local tests set `REQUIRE_STAFF_2FA=false` in `.env`. Keep it `true` in production.

Tests: `./vendor/bin/pest` (SQLite in memory). Code style: `./vendor/bin/pint`. Types: `npm run types:check`.

## What Phase 1 contains

- **Roles and policies:** `student_parent`, `staff`, `admin`. Parents and students see only records that staff linked to their account (`StudentPolicy`).
- **Student records:** identity in `students`, one `student_enrollments` row per school year, `graduations`. LRN is optional because old records have none. Grade level is stored as 7 to 12 for both curricula (old 1st to 4th year = 7 to 10).
- **Search:** by LRN, by name (typo tolerant) and by school year. See `app/Services/Search/StudentSearch.php` and `app/Support/NameIndex.php`.
  The database narrows candidates with plain LIKE queries, PHP ranks them by edit distance. No raw SQL.
- **Account linking:** a parent or student asks to link an LRN and birthdate, staff approve (`student_links`).
- **Audit log:** every view of a record and every change is stored with user, time and IP (`/admin/audit-log`).
- **Security basics:** login and 2FA rate limits, staff 2FA gate (`EnsureTwoFactorEnabled`), soft delete for records, audit retention of 5 years.

## What Phase 2 adds

- **Request workflow** (`app/Services/Requests/RequestWorkflow.php`, the only code that changes a status):
  `submitted -> under_review -> approved -> processing -> ready -> released`, or `rejected` (a reason is required).
  Invalid changes throw `InvalidRequestTransition`; the row is locked while it changes. Every change is saved in
  `request_status_histories` and in the audit log.
- **Who can request:** only an account with an approved link to that student (parent, student, alumnus).
- **PDF:** when staff approve, the queued job `GenerateDocumentPdf` builds the PDF with DomPDF (`resources/views/pdf/document.blade.php`),
  prints the request number and a QR code, and stores it in `storage/app/private/documents/` (disk `private`, never in `public/`).
- **Secure download:** `/documents/request/{id}` checks the policy (the requester only after release) and redirects to a
  signed link valid for 2 minutes. The file route checks the signature and the policy again and logs the download.
- **QR code:** opens `/verify/{token}`, a public page that says if the document is genuine and shows only a masked name.
- **Notifications:** `RequestStatusChanged` (queued mail and in-app record) on every status the requester cares about.

Local testing: the queue is the `database` driver, so run the scheduler in a terminal (same as the production cron):

```powershell
php artisan schedule:work
```

Emails are written to `storage/logs/laravel.log` while `MAIL_MAILER=log`.

## Folder map

```
app/Enums, Models, Policies, Support/NameIndex.php
app/Services/Search, Services/Audit
app/Http/Controllers/{Staff,Portal,Admin}, Http/Requests/Staff, Http/Resources
resources/js/pages/{staff,portal,admin}   Vue pages
database/seeders                          RolePermissionSeeder (safe anywhere), fake data seeders (local only)
tests/Feature, tests/Unit                 Pest tests
```

## Create the first admin on a real server

```bash
php artisan db:seed --class=RolePermissionSeeder --force
php artisan eskwelahub:create-user principal@example.com admin --name="Full Name"
```

See [DEPLOY.md](DEPLOY.md) for the Hostinger steps.

## Data rules

Seeders create fake learners only. Never import or commit real student data. `.env` is never committed.
