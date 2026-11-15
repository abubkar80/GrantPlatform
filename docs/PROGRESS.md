# Progress tracker (deadline: 20 Nov 2026)

Update the checkboxes as you go; commit this file with each milestone.

## 1. Foundation (11-12 Nov)
- [ ] MySQL installed; database `grant_platform` and dedicated user created
- [ ] Starter kit generated in a temp folder and copied into the repo (feature branch)
- [ ] `composer require spatie/laravel-permission` + published migration
- [ ] Domain migrations copied in; `php artisan migrate:fresh` succeeds on MySQL
- [ ] `npm run build` and `php artisan test` pass

## 2. Identity and applications (13-14 Nov)
- [ ] Four roles + permissions seeder; public registration cannot create Admin/Super Admin
- [ ] Donor approval separate from login
- [ ] Student profile, application draft/submit, private evidence upload
- [ ] Admin review (approve / decline / needs information)
- [ ] Versioned scoring service with boundary tests (35, 60, caps, missing inputs)

## 3. Funds, matching, awards (15-16 Nov)
- [ ] Funds + fund rule versions
- [ ] Matching with explanations
- [ ] Award creation: transaction + row locks + `active_award_slots`

## 4. Money (17 Nov)
- [ ] Contribution verification -> ledger event
- [ ] Disbursement Scheduled -> Released -> Confirmed (release debits once)
- [ ] Anonymous donor dashboard

## 5. Academics and oversight (18 Nov)
- [ ] Semester result submit + Admin verify
- [ ] Benchmark check, flags, suspension
- [ ] Audit viewer; Super Admin access management

## 6. Hardening (19 Nov)
- [ ] Concurrency tests (double award, duplicate release)
- [ ] Demo script and seeded demo data (3 funds)
- [ ] Database backup tested

Out of scope for the demo (label as future work): email delivery, MFA, malware scanning, refunds/reversals, case reassignment, CSV import.
