# GrantPlatform

**Empowering Education Through Transparent Funding**

HCM Foundation's higher-education grant management platform for financially vulnerable tertiary students.

## Status
Repository preparation. The Laravel application has not been generated or tested yet. Features below describe the intended platform, not implemented functionality.

## Confirmed stack
Laravel, Inertia.js, React, MySQL, Tailwind CSS, shadcn/ui, and Spatie Laravel Permission. TypeScript is recommended for React. Exact package versions must match the HCM development environment.

One Laravel repository will contain backend routes, business actions, React pages, migrations and tests.

## Roles
| Role | Responsibility |
|---|---|
| Student | Submit applications and evidence; view own awards; submit semester results |
| Donor | Define own fund criteria; monitor contributions and anonymous beneficiary progress |
| Admin | Verify applicants and donors, match beneficiaries, assign awards, record payments, verify results |
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

## Documentation
- [Local setup guide](docs/SETUP.md)
- [Architecture and implementation plan](docs/ARCHITECTURE.md)
- [Database schema](docs/database/HCM_Grant_Platform.dbml)
- [Database design notes](docs/database/README.md)

## Security
Enforce role permissions and record ownership in Laravel. Keep uploads private. Maintain append-only audit and financial histories. Never commit real student information, credentials, bank details, or uploaded evidence.

## Team and licensing
HCM Foundation — Software Development Fellowship Capstone Team.
Project licensing and intellectual property arrangements remain pending. Preserve third-party license notices when adding the starter kit and dependencies.
