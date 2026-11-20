# EskwelaHub

Integrated school portal for **Cawayan National High School** (Bongabong, Oriental Mindoro).
Capstone project. Student records are personal data of minors, so the app follows RA 10173:
private file storage, role-based access, two-factor authentication for staff and a full audit log.

**Status:** all 5 phases built and tested.

| Phase | Scope | State |
|---|---|---|
| 1 | Setup, auth, roles, student records, search, audit log | Done |
| 2 | Document requests, PDF with QR code, notifications | Done |
| 3 | Online enrollment, OCR digitization of old records | Done |
| 4 | Duplicate and incomplete record alerts, satisfaction survey with sentiment, FAQ chatbot | Done |
| 5 | Principal dashboard, enrollment forecast, Excel export | Done |

## Stack

Laravel 13 (PHP 8.3 or higher), Inertia 3, Vue 3 + TypeScript, Tailwind, MySQL 8.
Spatie Permission (roles) and Spatie Activitylog (audit log), Fortify two-factor authentication,
DomPDF + chillerlan QR code, Maatwebsite Excel, Tesseract.js (browser OCR).
Queue and session: `database`. Cache: `file`. No Redis, Octane, Horizon or Reverb, so it runs on shared hosting.
`composer.json` pins `platform.php` to 8.3 so packages stay compatible with the host.

## Run it locally (Laragon)

```powershell
composer install
npm install
copy .env.example .env        # then: php artisan key:generate
# create the database "eskwelahub" (utf8mb4) in HeidiSQL or MySQL
php artisan migrate --seed    # roles, FAQ, FAKE students (1999 to now), FAKE demo accounts
npm run build                 # or "npm run dev" while you edit the Vue pages
php artisan schedule:work     # in a second terminal: runs the queue like the production cron
```

Open `http://eskwelahub.test` (Laragon) or run `php artisan serve`. In Laragon the virtual host must point to the
`public` folder (edit the `auto.*.conf` file and reload), never to the project root.

Demo accounts (fake, created by the local seeder only; password is `SEED_PASSWORD`, default `password`):

| Account | Role |
|---|---|
| `admin@eskwelahub.test` | Principal / Admin |
| `staff@eskwelahub.test` | Registrar / Staff |
| `parent@eskwelahub.test` | Student / Parent (one approved link) |
| `alumni@eskwelahub.test` | Student / Alumni (one pending link) |

Staff and admin must turn on two-factor authentication (Settings, Security) before they can use the staff portal.
For quick local tests set `REQUIRE_STAFF_2FA=false` in `.env`. Keep it `true` in production.

### Tests

```powershell
./vendor/bin/pest                       # all tests, SQLite in memory (about 1 minute)
npm run types:check ; ./vendor/bin/pint --test
```

The same suite also passes on MySQL (SQLite hides some differences). Create a throw-away database once, then run:

```powershell
$env:DB_CONNECTION='mysql'; $env:DB_DATABASE='eskwelahub_test'; ./vendor/bin/pest
```

The page tests read the built assets, so run `npm run build` after changing any Vue file.

## What each part does

| Part | Where | Notes |
|---|---|---|
| Audit trail | `app/Services/Audit/AuditLogger.php`, `/admin/audit-log` | What people DO inside the system: viewed, changed, requested, approved, released, downloaded. Principal only. |
| Session logs | `app/Listeners/RecordSessionEvent.php`, `/admin/session-logs` | Sign-in events ONLY: login, logout, registration, failed login, lockout, password reset, two-step codes. Passwords are never stored. Kept one year, then deleted by the daily job. Principal only. |
| Roles and policies | `app/Policies`, `database/seeders/RolePermissionSeeder.php` | `student_parent`, `staff`, `admin`. Parents see only records staff linked to their account. |
| Student records and search | `app/Services/Search`, `app/Support/NameIndex.php` | LRN, name (typo tolerant), school year. Plain query builder, ranking in PHP. |
| Document requests | `app/Services/Requests/RequestWorkflow.php` | `submitted â†’ under_review â†’ approved â†’ processing â†’ ready â†’ released` or `rejected`. History, audit log, email. |
| PDF with QR | `app/Services/Documents`, `resources/views/pdf` | Built in the queue, stored in `storage/app/private`. QR opens the public `/verify/{token}` page. |
| Secure files | `SecureFileUrl`, `DocumentDownloadController` | Policy check, then a 2-minute signed link, both logged. No file is ever in `public/`. |
| Class sections | `app/Http/Controllers/Staff/SectionController.php` | Sections per grade and school year with adviser and optional capacity, class lists (printable), move learners in and out, pick a section when approving an online enrollment. A full section refuses more learners. |
| Senior high strands | `app/Enums/Strand.php`, `SCHOOL_STRANDS` | STEM, ABM, HUMSS, GAS, TVL, Sports, Arts and Design. Required for Grades 11 and 12 (enrollment, class sections, scans), forbidden below. A strand section only takes learners of its strand. The dashboard and Excel file count learners per strand. |
| Curriculum | `app/Enums/Curriculum.php` | Every new enrollment is **K to 12** (DepEd's current curriculum). BEC exists only for old records: Grade 7 entrants before school year 2012-2013. Staff can leave the curriculum empty and it is worked out. |
| Online enrollment | `app/Services/Enrollment` | Grade 7 and transferees, document upload, registrar approval creates or links the student and the parent's account. |
| OCR digitization | `app/Contracts/OcrEngine.php`, `app/Services/Ocr` | Default: Tesseract.js in the staff member's browser (nothing sent to a third party). Optional: OCR.space API (`OCR_DRIVER=ocr_space`). Staff always check the fields before a record exists. |
| Duplicate and incomplete records | `app/Services/Duplicates` | Same birthdate and similar name. Nightly job, "Check now" button, merge keeps all history. |
| Survey and sentiment | `app/Contracts/SentimentAnalyzer.php`, `resources/lexicon` | English and Tagalog word lists, negation and intensifiers. Replace the class to use an AI API. |
| FAQ chatbot (floating assistant) | `resources/js/components/ChatWidget.vue`, `app/Contracts/ChatbotResponder.php`, `FaqSeeder` | Rule based, English and Tagalog (replies in the language asked, tolerates typos, polite small talk and off-topic replies). Starts step-by-step guides (`config/guides.php`) that highlight where to click. Optional language model: `CHATBOT_DRIVER=llm` + `ANTHROPIC_API_KEY` (falls back to the FAQ). **The school must review the FAQ answers.** |
| Guided tour (desktop and phone) | `resources/js/lib/tour.ts`, `TourGuide.vue` | Starts on the first dashboard visit per role; "Tour" button in the header replays it. Elements are marked with `data-tour`. |
| Principal dashboard | `app/Services/Reports` | Enrollment, requests, graduates, satisfaction, forecast. |
| Forecast | `app/Support/LinearRegression.php`, `EnrollmentForecaster` | Least squares in PHP over the last 10 finished school years (at least 3 needed). |
| Excel export | `app/Exports` | One workbook, one sheet per topic. Principal only, logged. |

Pages for visitors: home and `/verify/{token}`. A floating help assistant (round button, bottom right) is on every page. Everything else needs a login.

## Create the first admin on a real server

```bash
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=FaqSeeder --force
php artisan eskwelahub:create-user principal@example.com admin --name="Full Name"
```

See [DEPLOY.md](DEPLOY.md) for the Hostinger steps.

## For the school and your group

See [docs/SCHOOL_INFO_NEEDED.md](docs/SCHOOL_INFO_NEEDED.md) for what is already known about the school and the checklist of information the school office still has to give.

## Data rules

Seeders create fake learners only. Never import or commit real student data. `.env` is never committed.
Scans and uploads of minors stay in `storage/app/private` and must be included in your backups.
