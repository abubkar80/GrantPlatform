# Demo script (about 10 minutes)

Goal: show the real, working path from a registration to an Admin decision, and the rules that protect it.

## Before you start (2 minutes)

```bash
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Open http://localhost:8000. Keep two browser windows (or one normal and one private) so you can switch between a student and an Admin quickly. Demo password for every account: `Demo-Passw0rd!`.

## 1. Safe registration (1 minute)

1. Open **Register**. Point out: only **Student** and **Donor** can be chosen, and consent is required.
2. Register a new **Donor** ("Hope Trust"). You land on a dashboard that says **Awaiting Admin approval**.

Say: Admin and Super Admin accounts can never be created from this form; the server checks the role against an allow-list, and tests prove it.

## 2. Admin approves a donor (1.5 minutes)

1. Log in as `admin@grantplatform.test`. The dashboard shows **1 donor awaiting approval**; click the card.
2. On **Donor approvals**, **Decline** with a reason once (use a second test donor if you want), then **Approve** Hope Trust.
3. Switch back to the donor: the dashboard now shows **Registration: Approved**.

Say: the decision is a single conditional update, so two Admins cannot both decide, and every decision writes an audit row and a notification.

Optional proof of the audit trail:

```sql
SELECT action, subject_type, subject_id, outcome, reason FROM audit_events ORDER BY id DESC LIMIT 5;
```

## 3. Student applies (3 minutes)

1. Log in as `student@grantplatform.test`.
2. **My profile**: choose Gombe State University, then a programme, add a matric number, level, local government and phone. Save.
3. **My application** then **Start application**. Fill in: income 50000, tuition 150000, earlier fees 20000, dependants 3, no other aid, single-parent: Yes, others No. Choose "Semester result: Available".
4. Upload a small PDF as **Semester result**. **Save draft**, refresh to show it persists.
5. **Submit application**. Point out the success banner with the reference, and that the answers are now **locked**. The dashboard card counts it as **1 submitted**; a draft never counted.

Say: submitting freezes an immutable snapshot (version 1) with money in kobo, evidence is stored on a private disk with a hash, and nothing is reachable by a public URL.

## 4. Admin review and scoring (3 minutes)

1. As the Admin, open **Applications**, then the new application, then **Start review**.
2. Open the evidence (**Open**). Mention that each view is recorded in the audit log.
3. In **Need assessment**, tick every indicator **except Household income**, set risk **High** with a reason, and **Run assessment**. Result: **incomplete**; the unverified indicator shows "not verified", and approval is blocked.
4. Tick income too and run again. Result: **80 / 100, high need** (income 30, costs 10, support 15, dependants 5, circumstances 10, risk 10).
5. Click **Ask for more information** with a reason. Switch to the student: the banner shows the note and **Update my application**. Reopen it (answers and documents come back), change an amount, and **Submit** again, which creates version 2.
6. As the Admin, start the review again, verify, run the assessment, and **Approve**.
7. Switch to the student: the banner shows **approved**; the profile shows enrolment **active**.

Say: missing evidence is never a zero score; each assessment is a new row so history is never overwritten; approval requires a complete assessment.

## 5. Guardrails and quality (1.5 minutes)

1. Log in as `superadmin@grantplatform.test`. The dashboard shows account counts only. Visit `/admin/applications`: **403 Forbidden**. Super Admin has oversight, not approval power.
2. In a terminal run `php artisan test` and show the passing suite (about 130 tests; the 3 skipped are the disabled two-factor tests).
3. Show the GitHub pull request with the green **tests / ci** check (Pint, PHPStan, TypeScript, lint and tests against MySQL).

## Closing (30 seconds)

- Built: access control, donor approval, application and evidence, Admin review and need scoring, audited and tested.
- Designed but not built: funds and matching, awards, payments and ledger, academic monitoring, reporting (see the README and PROGRESS.md).
- Next: funds with versioned criteria, then matching and awards with fund reservation.

## If something goes wrong

- Reset everything: `php artisan migrate:fresh --seed`.
- A white or black page usually means a stale build: delete `public/hot` if it exists, then run `npm run build`.
- MySQL not running: start the service (`MySQL84` on Windows) from an Administrator PowerShell.
- Fallback: run `php artisan test` and walk through the test names; they describe each rule in plain English.
