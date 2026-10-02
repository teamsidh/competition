# DezignBank Architecture Student Competition registration

A standalone PHP 8 registration flow for the **registration phase only**. It includes a responsive form, server-side validation, MySQL storage, and a printable confirmation. It does not include logins, payments, uploads, teams, judging, or an admin panel.

The visual theme uses the current terracotta color published by `dezignbank.net` (`#A65A3A`) and an original decorative illustration of historic architectural arches. The small `LOGO` square in the header is a **replaceable placeholder**, not an official logo. Replace it with an approved logo before launch if one is available.

## Folder structure

```text
.
├── .gitignore
├── README.md
├── schema.sql                 # Import with phpMyAdmin; do not upload publicly
└── app/                       # Upload the contents of this folder to the site directory
    ├── .htaccess              # Blocks private files and directory listings
    ├── index.php              # Registration page
    ├── register.php           # POST handler
    ├── confirmation.php       # Session-only printable confirmation
    ├── assets/
    │   ├── style.css
    │   ├── form.js
    │   └── heritage-architecture.svg
    └── private/
        ├── .htaccess          # Denies direct web access
        ├── bootstrap.php
        ├── functions.php
        ├── database.php
        └── config.example.php # Copy to config.php; never commit it
```

## Local setup

1. Use PHP 8 with `pdo_mysql` and a MySQL/MariaDB server. XAMPP or an equivalent local stack is sufficient; no Composer, Node runtime, or build step is needed.
2. Create a separate database for this competition, for example `dezignbank_competition`. Import `schema.sql` into it through phpMyAdmin. The schema uses InnoDB and a unique index on normalized email, so concurrent duplicate submissions are rejected by the database.
3. Copy `app/private/config.example.php` to `app/private/config.php`. Set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD`. For local testing set `APP_URL` to `http://localhost:8000` and `BASE_PATH` to an empty string.
4. Serve `app` as the document root with `php -S localhost:8000 -t app` and open `http://localhost:8000/`. The PHP development server does not apply `.htaccess`; use Apache for access-control checks.
5. To use an Apache subdirectory such as `http://localhost/competition/`, put the contents of `app` in that directory and set `APP_URL` to `http://localhost`, `BASE_PATH` to `/competition`.

`REGISTRATION_OPEN` in `config.php` controls whether the form accepts registrations. Set it to `false` to show the closed state and reject POSTs. Edit the data-use notice in `app/index.php` before launch to match your final policy. No marketing consent is included.

## GitHub

`app/private/config.php` is ignored by Git. Review `git status` before committing; do not add actual database credentials, exports, backups, or logs. Push the source repository to the GitHub owner and repository you control. The production `config.php` is created on the host after upload.

## InfinityFree deployment

1. In the InfinityFree control panel, create a **new MySQL database** for this registration flow. Keep it separate from any DezignBank platform database. Copy the exact database hostname, database name, username, and password from the control panel. The hostname is usually an InfinityFree SQL host, **not** `localhost`.
2. Open phpMyAdmin for that database and import `schema.sql`. Confirm the `registrations` table and its two unique indexes exist.
3. In the hosting File Manager or with FTP, find the **document root/`htdocs` directory for the domain you are using**. Upload the *contents* of `app` there for a subdomain, or into `htdocs/competition/` for an existing host that genuinely serves that path. Include `.htaccess` and the `private` directory. Do not upload `schema.sql`, the repository `.git` directory, or a database export.
4. On the host, copy `private/config.example.php` to `private/config.php` and fill in the production database settings. Set `APP_URL` to the HTTPS origin only (for example `https://competition.dezignbank.com`) and set `BASE_PATH` to `''` for a domain root or `'/competition'` for that subdirectory. Keep the form action and handler on this same origin. Do not use a cross-origin API, webhook, SSH task, cron job, or background worker.
5. Enable HTTPS for the domain in InfinityFree and verify the page actually loads over HTTPS before accepting registrations. The session cookie is marked `Secure` when the request uses HTTPS; it is always `HttpOnly` and `SameSite=Lax`.
6. Check that `https://your-host/private/config.example.php` returns **403**, directory listing is unavailable, and no SQL, log, backup, or repository metadata is accessible. If `.htaccess` is not honored, do not open registration until private files are outside the web root or access is blocked by the host.
7. Set `REGISTRATION_OPEN` to `true` only after a live test registration succeeds. Remove that test record in phpMyAdmin if appropriate, then test duplicate email behavior.

The desired `dezignbank.com/competition` URL in the brief requires compatible routing on the existing `dezignbank.com` host (a real directory or a correctly configured reverse proxy) and must be tested with InfinityFree's browser checks. DNS alone cannot route `/competition` to a different host. `competition.dezignbank.com` is the fallback deployment address. A redirect from `/competition` to the subdomain changes the browser URL. Use the `.net` visual theme independently of whichever approved domain serves this app.

## Viewing, exporting, and backing up registrations

Use InfinityFree's phpMyAdmin for the competition database. Select `registrations` and use **Browse** to view records. Use **Export** to download a CSV or SQL backup; store exports securely because they contain student contact details. Use **Export → SQL** for a full database backup before changing the schema. There is deliberately no public list or custom export endpoint.

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
