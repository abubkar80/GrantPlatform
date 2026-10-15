# Architecture and delivery plan

Use a modular Laravel application with Inertia React pages. Laravel owns routes, validation, sessions, authorization and business transactions; React owns presentation. MySQL is the system of record.

## Intended locations
- app/Actions: application review, assessments, matching, awards, disbursements and academic decisions, grouped by domain.
- app/Policies: record ownership, case scope and operational role checks.
- app/Http/Requests: validated input.
- app/Models: Eloquent relationships.
- resources/js/pages: Student, Donor, Admin and Super Admin interfaces.
- database/migrations and database/seeders: ordered schema changes and explicit permissions.
- tests/Feature: permissions, workflows and MySQL financial concurrency checks.

These are intended locations, not existing application modules.

## Financial integrity
Use integer NGN kobo. Cash = sum(cash_delta); reserved = sum(reserved_delta); available = cash - reserved.
Verified receipt increases cash. Award commitment increases reserved. Release reduces both cash and reserved. Confirmation posts no debit.
Use consistent row locks, transactions, unique command keys and source-event keys. Test concurrent commitments and duplicate releases.

## Privacy and audit
Project donor views through explicit anonymous fields; never serialize full student models. Store evidence privately and authorize downloads. Audit role changes, reviews, awards, financial actions, academic decisions and sensitive access. audit_events describes actions; ledger_events calculates balances. Redact sensitive payloads.

## Implementation order
1. Compatible official starter kit, MySQL, environment setup and automated checks.
2. Spatie permissions, four roles, public registration restrictions and donor approval.
3. Profiles, private evidence, application versions and review.
4. Versioned scoring, funds, matching and awards.
5. Contributions, payees, disbursements, ledger and reconciliation.
6. Verified semester results, flags, anonymous dashboards and audit viewer.
7. Access-control review, backup/restore testing and deployment.

## Open design decisions
Keep refund handling, suspended reservations, detailed evidence/access policy, funding-gap calculation and criteria-change policy explicitly pending. The DBML's award-mediated application-to-fund link is an implementation proposal. Do not treat the diagram as production-ready migrations.
