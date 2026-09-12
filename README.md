# Lab Automation System

A laboratory operations platform for engineering test labs: register products, run them through
structured multi-step testing workflows, record precision measurements against configurable
tolerance bands, and generate compliance reports — with full audit trails.

Live demo: [fzlabautomation.kesug.com](https://fzlabautomation.kesug.com/)

Stack: **PHP + MySQL**, no framework — plain PHP with a small set of shared includes
(`includes/`) for auth, permissions, CSRF protection, and helpers.

## What it does

- **Role-based access** across five roles: administrator, quality manager, testing engineer,
  lab technician, and auditor.
- **Structured testing workflow**: register a product, assign a testing type, record
  measurements against min/nominal/max tolerance bands, submit for review, and get a
  pass/fail decision — every step timestamped and attributed.
- **Configurable testing types & parameters** — define what gets measured, in what unit, and
  within what tolerance, per test type.
- **Compliance & audit trail** — every state change is logged (`audit_log`), and CPRI
  (certificate/decision) records are kept per product.
- **Email notifications** via SMTP (with a backup SMTP provider and a Brevo API fallback), plus
  in-app notifications.

## Project structure

```
config/       App, database and mail configuration (reads from .env)
includes/     Shared auth, permissions, CSRF, helpers, layout partials
auth/         Login, registration, email verification, password reset
admin/        User, department, testing-type and settings management
products/     Product registration and detail views
testing/      Testing record workflow (assign → measure → submit → review)
reports/      Reporting views
database/     Full schema + demo seed data (lab_automation_complete.sql)
api/          Lightweight JSON endpoints (heartbeat, notifications, downloads)
assets/       CSS/JS
```

## Setup

1. Create a MySQL database and import `database/lab_automation_complete.sql`.
2. Copy `.env.example` to `.env` and fill in your database and SMTP details.
3. Point your webserver's document root at this folder (Apache/`.htaccess` included; PHP 8+).
4. Demo accounts (see the database seed) all use the password `ChangeMe123!` — change these
   before using the app for anything real.

**Never commit your real `.env`.** `config/*.php` reads settings via an `env()` helper with
sensible local defaults, so the app also runs against a local MySQL install with a mostly-empty
`.env`.

## Security notes

- Secrets (DB credentials, SMTP password, mail API key, password pepper) live only in `.env`,
  which is gitignored.
- Passwords are hashed with PHP's `password_hash()` (bcrypt) plus an app-level pepper.
- CSRF tokens are enforced on state-changing requests (`includes/csrf.php`).
- Route access is gated per-role (`includes/permissions.php`, `includes/auth_guard.php`).
