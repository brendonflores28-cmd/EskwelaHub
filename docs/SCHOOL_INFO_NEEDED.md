# Information needed from Cawayan National High School

Use this list when you visit or call the school office. Every item says where the answer goes in the system.
Anything marked **Found** came from public sources and still needs the school to confirm it.

## 1. What is already known (public sources)

| Item | Value | Source | Used in |
|---|---|---|---|
| School name | Cawayan National High School (Facebook name: DepEd Cawayan National High School) | Official Facebook page | everywhere |
| Address | Cawayan, Bongabong, Oriental Mindoro 5211 | Facebook page, About | home page, PDFs, chatbot |
| Mobile | 0963 783 4433 | Facebook page, About | home page, chatbot |
| Email | 301624@deped.gov.ph | Facebook page, About | home page, chatbot |
| School ID | 301624 (the number in the DepEd email address; **confirm**) | inferred from the email | PDFs, footer |
| District | Bongabong South District | school seal | home page, PDFs |
| Established | Republic Act No. 8431, December 15, 1997 | Philippine law library (lawphil.net) | footer |
| Seal / logo | official seal (green, with sun, torch, two students, DepEd mark) | Facebook profile picture | whole system |

These values live in `config/eskwelahub.php` (or `SCHOOL_*` variables in `.env`), so the school can correct them without changing code.

## 2. What the public sources did **not** show (ask the school)

The Facebook page is public only in part (logged-out visitors see very little), and no website or DepEd listing gave these.

### A. People and signatures
- [ ] Full name and title of the **school principal** (signs diplomas and certifications) - needed on PDFs and the principal account
- [ ] Full name and designation of the **registrar / records officer** (signs Form 137) - the PDF now says only "School Registrar"
- [ ] Names, positions and work emails of everyone who will use the staff portal (clerks, administrative officer, guidance, ICT coordinator)
- [ ] Name of the **Data Protection Officer** of the school (required by the Data Privacy Act, RA 10173)
- [ ] Specimen signatures and whether PDFs may carry a printed e-signature, or must be signed by hand after printing

### B. What the school offers
- [ ] Junior high only (Grades 7 to 10), or also **senior high** (Grades 11 and 12)? Which tracks and strands (STEM, ABM, HUMSS, GAS, TVL...)? *The system has grades 7 to 12 but no strand field yet.*
- [ ] Special programs (for example SPA, STE, SPJ) and whether learners must be tagged with them
- [ ] School year calendar: start, end, enrollment period, graduation / moving-up dates
- [ ] How sections are named in each grade (heroes, flowers, numbers?), usual class size and the maximum per section
- [ ] How many learners are enrolled per grade this school year (to check the dashboard numbers)

### C. Enrollment
- [ ] Exact requirements for **new Grade 7** learners (PSA birth certificate, Form 138 / report card, others?)
- [ ] Exact requirements for **transferees** (Form 137 request to the old school, good moral certificate, others?)
- [ ] Other cases: Balik-Aral (returning learners), ALS, learners with special needs, late enrollees
- [ ] Is the **LRN** required before enrollment, and who assigns one when a learner has none?
- [ ] Who approves enrollment (registrar alone, or also the principal)?
- [ ] Does the school still use paper forms (for example enrollment form, Form 137, SF9, SF10)? Which ones must stay on paper?

### D. School records and documents
- [ ] List of documents learners and alumni can request, with **requirements, fee (if any), processing days and who approves** each: Form 137 / SF10, Form 138 / SF9, diploma copy, certificate of graduation, certificate of enrollment, good moral, honorable dismissal, others
- [ ] Rules for who may claim a document (parent, learner, authorized person with a letter and ID)
- [ ] Wording the school wants on each certificate, and the official letterhead
- [ ] Where old records (1999 to now) are kept today: paper folders, Excel files, the DepEd **LIS / EBEIS**, or old computers? About how many records per school year?
- [ ] Permission to scan a few **sample pages** (with names covered) so the scanned-text reader can be tested on the school's real forms
- [ ] Can the school export the current learner list from LIS as an Excel or CSV file? *The system cannot import a list yet; this decides whether we build an importer.*

### E. Privacy and rules
- [ ] The school's **privacy notice** and consent form text (to show at registration)
- [ ] Written approval from the principal (and the Schools Division Office, if required) to use this system for student records
- [ ] How long records and uploaded documents may be kept, and who may see them
- [ ] How the school handles a parent who is not the biological parent (guardian papers)

### F. Contact answers for the chatbot
The chatbot answers only what is written in its FAQ table. Please give the school's own answers for:
- [ ] Office hours and days
- [ ] Fees (none, or which ones) and how to pay
- [ ] Enrollment dates and where to line up if the portal is not used
- [ ] Average processing time of each document
- [ ] Who to contact for complaints and for technical problems
- [ ] Any announcement channel besides Facebook

### G. Technology
- [ ] Who will **host and maintain** the system (the school's ICT coordinator, the capstone group, a company)?
- [ ] Hosting plan on Hostinger, the **PHP version** offered (needs 8.3 or higher), and the web address (subdomain) the school wants
- [ ] An **email account** for notifications (for example `noreply@...`) and its SMTP settings
- [ ] Internet speed at the registrar's office, and which computers and browsers staff use
- [ ] Phones that will hold the authenticator app for two-step login (every registrar and the principal need one)
- [ ] Who makes **backups** and where they are stored (see DEPLOY.md, section 7)

## 3. After the school answers

| Answer | Where to put it |
|---|---|
| Name, address, phone, email, district, school ID | `.env` (`SCHOOL_*`) or `config/eskwelahub.php` |
| Principal and registrar names | PDF signature block in `resources/views/pdf/document.blade.php` (we can add them to `.env`) |
| Document list, fees, processing days | the document types in `app/Enums/DocumentType.php` and the FAQ |
| FAQ answers | table `faq_entries` (`database/seeders/FaqSeeder.php`) |
| Enrollment requirements | `app/Http/Requests/EnrollmentFormRequest.php` and the form page |
| Strands / programs | new field on the enrollment record (ask us to add it) |
| Staff accounts | `php artisan eskwelahub:create-user email role --name="..."` |
| Current school year | `php artisan eskwelahub:school-year 2026 --current` |
