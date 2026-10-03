# DezignBank Architecture Student Competition registration

A standalone PHP 8 site for the **registration phase**. It includes a competition landing page, solo/team registration, server-side validation, MySQL storage, a printable confirmation, and a private owner dashboard for viewing and exporting registrations and managing sponsor logos. It does not include payments, design submissions, or judging workflows.

The visual theme uses the DezignBank navy, white and orange palette. The hero includes a custom generated award/certificate image with a Zaha Hadid quote verified by the [Zaha Hadid Foundation](https://www.zhfoundation.com/collections/the-world-89-degrees/). Heritage photos: [Adalaj Stepwell by Shivajidesai29](https://commons.wikimedia.org/wiki/File:Adalaj_Stepwell-Adalaj_Ahmedabad-Gujarat-IMG_1021.jpg) and [Hawa Mahal by Aarshi Joshi](https://commons.wikimedia.org/wiki/File:Hawa_Mahal_at_Jaipur,_Rajasthan.jpg), [CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0/). Jury portraits and professional details come from [Amity](https://www.amity.edu/faculty-detail.aspx?facultyID=3313), [Jamia Millia Islamia](https://jmi.ac.in/ACADEMICS/Departments/Department-Of-Architecture/Faculty-Members/2990/Mohammad_Ziauddin), and [Apeejay/Apeejay Newsroom](https://apeejay.news/teaching-is-internalising-learning/). No jury contact details are published.

## Folder structure

```text
.
├── .gitignore
├── README.md
├── schema.sql                 # Fresh database setup; do not upload publicly
├── admin-migration.sql        # Add admin and recovery tables to an existing deployment
├── competition-migration.sql  # Add team and sponsor data to existing deployment
└── app/                       # Upload the contents of this folder to the site directory
    ├── .htaccess              # Blocks private files and directory listings
    ├── index.php              # Public competition landing page
    ├── apply.php              # Solo/team registration form
    ├── register.php           # POST handler
    ├── confirmation.php       # Session-only printable confirmation
    ├── admin/                 # Private owner dashboard, settings, email recovery, CSV export
    ├── assets/
    │   ├── style.css
    │   ├── admin.css
    │   ├── form.js
    │   ├── dezignbank-mark.svg
    │   ├── adalaj-stepwell.jpg
    │   └── heritage-architecture.svg
    └── private/
        ├── .htaccess          # Denies direct web access
        ├── bootstrap.php
        ├── functions.php
        ├── database.php
        ├── admin_functions.php
        ├── mail.php           # Hostinger SMTP via PHPMailer
        ├── vendor/phpmailer/  # PHPMailer 7.1.1 and its license
        └── config.example.php # Copy to config.php; never commit it
```

## Local setup

1. Use PHP 8 with `pdo_mysql` and a MySQL/MariaDB server. XAMPP or an equivalent local stack is sufficient; no Composer, Node runtime, or build step is needed.
2. Create a separate database for this competition, for example `dezignbank_competition`. Import `schema.sql` into it through phpMyAdmin. The schema uses InnoDB and a unique index on normalized email, so concurrent duplicate submissions are rejected by the database.
3. Copy `app/private/config.example.php` to `app/private/config.php`. Set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD`. For local testing set `APP_URL` to `http://localhost:8000` and `BASE_PATH` to an empty string.
4. Serve `app` as the document root with `php -S localhost:8000 -t app` and open `http://localhost:8000/`. The PHP development server does not apply `.htaccess`; use Apache for access-control checks.
5. To use an Apache subdirectory such as `http://localhost/competition/`, put the contents of `app` in that directory and set `APP_URL` to `http://localhost`, `BASE_PATH` to `/competition`.

`REGISTRATION_OPEN` in `config.php` controls whether the form accepts registrations. Set it to `false` to show the closed state and reject POSTs. Review the data-use notice in `app/apply.php` against your final privacy policy. No marketing consent is included. Landing page dates and rules are explicitly marked draft/tentative in `app/index.php`.

## GitHub

`app/private/config.php` is ignored by Git. Review `git status` before committing; do not add actual database credentials, exports, backups, or logs. Push the source repository to the GitHub owner and repository you control. The production `config.php` is created on the host after upload.

## InfinityFree deployment

The live site is [competition.dezignbank.com](https://competition.dezignbank.com/) (also reachable through [the InfinityFree address](https://dezignbank-competition.infinityfreeapp.com/)), hosted in InfinityFree account `if0_43068035`. The production configuration is stored only in `/htdocs/private/config.php` on the host and is not in this repository. A live registration and database insert were verified on 2 October 2026; the synthetic test row was then deleted. On an existing installation, import `competition-migration.sql` **before** uploading the new PHP files. It adds entry type, teammate names, and sponsors. Import `admin-migration.sql` only if the admin tables are not already present.

1. In the InfinityFree control panel, create a **new MySQL database** for this registration flow. Keep it separate from any DezignBank platform database. Copy the exact database hostname, database name, username, and password from the control panel. The hostname is usually an InfinityFree SQL host, **not** `localhost`.
2. Open phpMyAdmin for that database and import `schema.sql`. Confirm the `registrations` table and its two unique indexes exist.
3. In the hosting File Manager or with FTP, find the **document root/`htdocs` directory for the domain you are using**. Upload the *contents* of `app` there for a subdomain, or into `htdocs/competition/` for an existing host that genuinely serves that path. Include `.htaccess` and the `private` directory. Do not upload `schema.sql`, the repository `.git` directory, or a database export.
4. On the host, copy `private/config.example.php` to `private/config.php` and fill in the production database settings. Set `APP_URL` to the HTTPS origin only (for example `https://competition.dezignbank.com`) and set `BASE_PATH` to `''` for a domain root or `'/competition'` for that subdirectory. Keep the form action and handler on this same origin. Do not use a cross-origin API, webhook, SSH task, cron job, or background worker.
5. Enable HTTPS for the domain in InfinityFree and verify the page actually loads over HTTPS before accepting registrations. The session cookie is marked `Secure` when the request uses HTTPS; it is always `HttpOnly` and `SameSite=Lax`.
6. Check that `https://your-host/private/config.example.php` returns **403**, directory listing is unavailable, and no SQL, log, backup, or repository metadata is accessible. If `.htaccess` is not honored, do not open registration until private files are outside the web root or access is blocked by the host.
7. Set `REGISTRATION_OPEN` to `true` only after a live test registration succeeds. Remove that test record in phpMyAdmin if appropriate, then test duplicate email behavior.

`competition.dezignbank.com` is the dedicated competition host. The main `dezignbank.com` site and its database remain separate. A `dezignbank.com/competition` path would require routing on the main site's host; DNS alone cannot route a path to InfinityFree.

## Admin dashboard

Open [the private admin section](https://dezignbank-competition.infinityfreeapp.com/admin/). The admin ID is `admin`. On the first visit, copy the PASSWORD field under Account Details in the InfinityFree hosting account (different from the InfinityFree dashboard login) and choose a separate admin password of at least 12 characters. Enter both directly on the HTTPS site; do not commit either password to Git. The existing password is checked once against the host-only configuration. Only a salted hash of the admin password is saved in `admin_auth`. Setup closes automatically after the first owner account is created.

Sign in with that admin ID and password to view totals, search submissions, page through results, and download all or matching entries as CSV. The table shows each lead's reference, entry type, teammate names, received time, contact, college, city and year. CSV also includes consent time. The **Sponsors** link lets you add, replace, reorder and remove logos. PNG/JPEG/WebP only, 2 MB maximum; uploads are stored under `assets/sponsors/` with random filenames. Dashboard and export share a separate, private admin session that expires after 30 minutes of inactivity. Three wrong password attempts from one IP block sign-in for 15 minutes. Export uses POST and CSRF validation. CSV cells are guarded against spreadsheet formula injection. Sign out when finished, and keep downloaded contact data private.

Use **Change admin password** and **Change admin ID** in admin settings to replace temporary credentials. To enable **Forgot password**, connect the `info@dezignbank.com` Hostinger mailbox from the same settings page by entering its mailbox password directly on the HTTPS site. The app sends a test message to that mailbox before saving the password encrypted with AES-256-GCM. The encryption key is derived from the host-only database password; the mailbox password is never put in Git. If the database password changes, reconnect the mailbox. Recovery then sends an eight-digit one-time code to `info@dezignbank.com` through Hostinger SMTP (`smtp.hostinger.com`, port 587, STARTTLS). Codes expire after 10 minutes, allow three incorrect entries, and are stored only as keyed hashes. Send requests are rate-limited. If the mailbox is not connected or SMTP fails, recovery clearly reports that and does not pretend an email was delivered. Manual reset of `admin_auth` in phpMyAdmin remains a fallback for the hosting owner.

## Database backup and fallback access

Sign in to the DezignBank InfinityFree account and open [MySQL Databases for the competition site](https://dash.infinityfree.com/accounts/if0_43068035/domains/dezignbank-competition.infinityfreeapp.com/databases). In the row for `if0_43068035_competition`, click **phpMyAdmin**. Click the `registrations` table, then **Browse** to view form submissions directly if the dashboard is unavailable. New records appear as soon as a registration succeeds. The table started empty because the live synthetic test entry was removed.

For a full database backup, open the database's **Export** tab in phpMyAdmin and choose SQL. The dashboard CSV is for reviewing and working with registrations. Store exports securely because they contain student contact details. There is no public list or unprotected export endpoint.

## How the flow works

- PHP validates all required fields, trims and checks lengths, normalizes email and mobile numbers, and requires consent. Indian 10-digit mobile numbers are saved with `+91`; valid international E.164 numbers are also accepted.
- PDO prepared statements insert the record. The database's unique email constraint safely handles simultaneous submissions. References are random `DBAC-` values with a unique index.
- A CSRF token, hidden honeypot, and server-side per-session limit of eight POST attempts per 15 minutes provide basic abuse protection. The session limit is intentionally modest and is not a substitute for host-level rate limiting during a large public launch.
- Successful submission uses POST/Redirect/GET. The confirmation reference is stored only in the current session; no student record is exposed via URL parameters. Refreshing the confirmation does not resubmit.
- Database errors are hidden from visitors. The form preserves valid input on validation or storage errors.

## Test checklist

- [ ] Complete registration with a fresh email; verify one database row and a printable `DBAC-` reference.
- [ ] Submit blank, malformed, overlong, and invalid-year data; verify inline errors and preserved valid input.
- [ ] Enter a 10-digit Indian mobile and `+91` format; verify both normalize to `+91...` in the database.
- [ ] Submit the same email with different capitalization; verify only one row exists and the duplicate message appears.
- [ ] Double-click Submit or send simultaneous POSTs; verify the unique index permits only one registration.
- [ ] Submit an absent or incorrect CSRF token; verify rejection and no new row.
- [ ] Fill the hidden `website` field; verify rejection and no new row.
- [ ] Disconnect the database or use invalid credentials; verify a generic error, preserved input, and no credentials/SQL shown.
- [ ] Set `REGISTRATION_OPEN` to `false`; verify the closed page and POST rejection.
- [ ] Check the form, focus states, errors, and confirmation at mobile widths; verify the print view.
- [ ] Verify `/private/`, `.sql`, `.log`, backups, and `.git` paths cannot be read publicly.
