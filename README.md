# GrantPlatform

**Empowering Education Through Transparent Funding**

HCM Foundation's higher-education grant management platform for financially vulnerable tertiary students.

## Status

A working, tested vertical slice of the platform: **registration and role-based access, donor approval, the student application, and Admin review with need scoring**. The funding side (funds, matching, awards, payments, ledger) and academic monitoring are designed in the database schema but **not built yet**; see [What is not built yet](#what-is-not-built-yet).

## What works today

| Area | Implemented |
| --- | --- |
| Accounts | Login, password reset, public registration for **Student and Donor only** (Admin and Super Admin can never self-register), recorded data-use consent |
| Access control | Four roles with a permission matrix (Spatie), deny-by-default routes. Super Admin has oversight only: no approval, matching or payment authority |
| Donors | Registration stays **pending** until an Admin approves or declines with a reason; each decision is audited and notifies the donor |
| Students | Profile (institution, programme, level, origin LGA), application draft, **private evidence upload**, submit into an **immutable versioned snapshot**, and resubmit when an Admin asks for more information |
| Admin review | Applications queue, assigned review, secure evidence access (every view audited), **need assessment** from verified evidence only, and Approve / Decline / Ask for more information with an audited decision. Approval confirms enrolment |
| Need scoring | Pure, unit-tested service: income 30, costs 25, support 15, dependants 10, circumstances 10 (capped), risk 10. Unverified evidence leaves the assessment **incomplete, never zero** |
| Data design | 46 domain migrations generated from the DBML schema; money stored as integer kobo; unique and foreign-key constraints enforced by MySQL |
| Quality | Automated tests (Pest) on MySQL, Pint, PHPStan, TypeScript and frontend lint/format, all run by GitHub Actions on every pull request |

## Quick start

Requirements: PHP 8.3+ (developed on 8.5), Composer, Node 22+, MySQL 8.4.

```bash
git clone https://github.com/abubkar80/GrantPlatform.git
cd GrantPlatform
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
```

Create the databases (once), then set the `DB_*` values in `.env` (see [docs/SETUP.md](docs/SETUP.md)):

```sql
CREATE DATABASE grant_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE grant_platform_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'grant_platform'@'localhost' IDENTIFIED BY 'choose_a_password';
GRANT ALL PRIVILEGES ON grant_platform.* TO 'grant_platform'@'localhost';
GRANT ALL PRIVILEGES ON grant_platform_test.* TO 'grant_platform'@'localhost';
```

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=grant_platform
DB_USERNAME=grant_platform
DB_PASSWORD=choose_a_password
SESSION_DRIVER=database
QUEUE_CONNECTION=database
MAIL_MAILER=log
```

```bash
php artisan migrate --seed    # schema + roles + reference data + demo accounts
npm install
npm run build
php artisan serve             # http://localhost:8000
```

## Demo accounts (local and demo use only)

`php artisan db:seed` creates one account per role. The seeder **refuses to run in production**. All use the password `Demo-Passw0rd!`.

| Role | Email |
| --- | --- |
| Admin | `admin@grantplatform.test` |
| Super Admin | `superadmin@grantplatform.test` |
| Donor (approved) | `donor@grantplatform.test` |
| Student | `student@grantplatform.test` |

A step-by-step walkthrough is in [docs/DEMO_SCRIPT.md](docs/DEMO_SCRIPT.md). To reset the demo at any time, run `php artisan migrate:fresh --seed`.

## Quality checks

```bash
php artisan test                                  # needs the grant_platform_test database
composer lint                                     # Pint (PHP style)
vendor/bin/phpstan analyse --memory-limit=1G      # static analysis
npm run check                                     # frontend format + lint
npm run types:check                               # TypeScript
```

`composer ci:check` runs all of them in order and is what GitHub Actions runs against a MySQL service.

## Confirmed stack

Laravel, Inertia.js, React, MySQL, Tailwind CSS, shadcn/ui, and Spatie Laravel Permission. TypeScript is recommended for React. Exact package versions must match the HCM development environment.

One Laravel repository will contain backend routes, business actions, React pages, migrations and tests.

## Roles

| Role        | Responsibility                                                                                      |
| ----------- | --------------------------------------------------------------------------------------------------- |
| Student     | Submit applications and evidence; view own awards; submit semester results                          |
| Donor       | Define own fund criteria; monitor contributions and anonymous beneficiary progress                  |
| Admin       | Verify applicants and donors, match beneficiaries, assign awards, record payments, verify results   |
| Super Admin | Manage Admin access and oversee reports/audit history; no application approval or payment authority |

There is no institution portal. Donors never receive student identities, including in exports.

## Accepted operating rules

- Need score: income 30, education costs 25, existing support 15, dependants 10, combined circumstances 10, discontinuation risk 10.
- Orphan status is Yes/No. Combined circumstances earn at most 10 points.
- The 35-point cutoff and High 60–100 / Moderate 35–59 / Low below 35 categories remain provisional demo settings.
- Admins verify inputs; missing evidence stays pending.
- External scholarship recipients may qualify for verified unmet costs. One active platform award per student.
- Donors transfer externally to HCM. Admins verify incoming receipts before money becomes available.
- Admins pay institutions/suppliers externally on students' behalf. No direct student payments.
- Outgoing workflow: Scheduled → Released → Confirmed. Confirmation requires payee acknowledgement recorded by Admin.
- Release reduces cash and unpaid commitment once. Confirmation never deducts again.
- Students submit semester evidence and structured academic values. Only Admin-verified results affect benchmarks.
- New entrants can use admission evidence for funds explicitly allowing applicants without CGPA.

## What is not built yet

The database schema (46 tables) already covers these areas, but there is no screen or workflow for them yet:

- Donor funds with eligibility criteria and versioned rules, contribution recording and receipt verification
- Matching of approved students to fund criteria, awards and fund reservation (one active award per student)
- Payments (Scheduled, Released, Confirmed), the funds ledger and refunds/reversals
- Semester results, benchmark checks, academic flags and award suspension
- Anonymous donor reporting and exports, the audit-log viewer, Super Admin account management
- In-app notification screen (notifications are written to the database), email delivery, multi-factor authentication, malware scanning of uploads (files are stored privately and marked `pending`)

These are tracked in [docs/PROGRESS.md](docs/PROGRESS.md).

## Documentation

- [Local setup guide](docs/SETUP.md)
- [Architecture and implementation plan](docs/ARCHITECTURE.md)
- [Database schema](docs/database/HCM_Grant_Platform.dbml)
- [Database design notes](docs/database/README.md)
- [Demo script](docs/DEMO_SCRIPT.md)
- [Progress tracker](docs/PROGRESS.md)

## Security

Enforce role permissions and record ownership in Laravel. Keep uploads private. Maintain append-only audit and financial histories. Never commit real student information, credentials, bank details, or uploaded evidence.

## Team and licensing

HCM Foundation — Software Development Fellowship Capstone Team.
Project licensing and intellectual property arrangements remain pending. Preserve third-party license notices when adding the starter kit and dependencies.
