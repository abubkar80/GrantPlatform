# Progress tracker

Last updated: 22 November 2026. Tick items as they land on `main`.

## Foundation
- [x] MySQL, Laravel 13 React starter kit, Spatie roles and permissions
- [x] 46 domain migrations from the DBML schema (MySQL, with foreign keys and unique constraints)
- [x] CI: Pint, PHPStan, TypeScript, frontend lint/format and tests on MySQL
- [x] Brand theme (orange, yellow, slate) and role-based navigation

## Identity and access
- [x] Four roles and permission matrix; Super Admin has no approval, matching or payment authority
- [x] Registration limited to Student and Donor, with recorded consent
- [x] Donor approval by an Admin (approve, or decline with reason), audited, notification written
- [x] Role dashboards with summary cards and icons

## Students and applications
- [x] Student profile with institution, programme, level and origin LGA
- [x] Application draft, private evidence upload, submit as an immutable version (money in kobo)
- [x] Resubmit after "needs information" (creates the next version)

## Admin review
- [x] Applications queue, assigned review, secure evidence access (audited)
- [x] Need assessment from verified evidence only (incomplete is never zero)
- [x] Approve, decline, ask for more information; approval confirms enrolment

## Funding (schema ready, not built)
- [ ] Donor funds and versioned eligibility criteria
- [ ] Contribution recording and Admin receipt verification
- [ ] Matching with pass/fail explanations
- [ ] Awards with atomic fund reservation and one active award per student

## Payments and ledger (schema ready, not built)
- [ ] Disbursements: Scheduled, Released, Confirmed (release debits once)
- [ ] Funds ledger and idempotent financial commands
- [ ] Refunds and reversals (policy decision pending)

## Academic monitoring (schema ready, not built)
- [ ] Semester results and Admin verification
- [ ] Benchmark checks, flags and award suspension

## Oversight and reporting (not built)
- [ ] Audit-log viewer and Super Admin account management
- [ ] Anonymous donor dashboard and exports
- [ ] In-app notifications screen, email, MFA, malware scanning

## Release
- [x] README, setup guide and demo script
- [ ] Final fresh-clone check on a clean machine
