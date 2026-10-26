# Term 1 2026 controlled import

This is an invoice-only, approval-gated import path for the original Term 1 2026 fee register. It never imports receipts. Term 2 and Term 3 are outside its manifest format and execution scope.

## Offline dry run

The default command validates two JSON inputs without querying or changing the database. It writes its report under `storage/app/private/term1-import/`:

```powershell
php artisan finance:term1-controlled-import `
  --manifest=storage/app/private/term1-import/approved-manifest.json `
  --snapshot=storage/app/private/term1-import/current-reconciliation-export.json `
  --report=term1-2026-dry-run.json
```

The manifest format is `hande-term1-import-v1`. Each positive original charge needs a verified ERP student ID (or separately confirmed absence), a before-value snapshot for existing students, charge evidence, the original category, one or more source-supported fee-vote lines, and explicit school-owned postable USD mappings. Vote lines must sum to the source charge. Blank-fee scholarship rows are held without creating zero-value invoices or sponsor receivables. The `payments` field must remain empty.

## Execution prerequisites

Execution requires all of the following, checked again inside a single database transaction:

- A current private production reconciliation snapshot, no older than 24 hours, with an explicitly open 1 January 2026 accounting period, current tenant mappings, and the actual database driver.
- The live database driver and production deployment commit confirmed by the school from the production environment. The command refuses a mismatch.
- A recoverable production backup with a recent successful isolated restore verification. The command accepts this only as maker/checker evidence; it cannot independently inspect Laravel Cloud backups or prove a restore test.
- Two different active school administrators or super administrators from the same tenant. The authorization file binds maker, checker, exact manifest hash, exact snapshot hash, Cloud branch/commit, database driver, backup reference and the selected charge-reference list.
- Only references whose preview status is `READY` may be selected. The command rechecks the full manifest against the current database, locks the school, students and account mappings, calls the existing AccountingService, verifies posted journal amounts and dates, records migration staging/audit evidence, and rolls back the selected transaction on an error.

No local command can establish the production branch, deployed commit, live database driver, or backup/restore result without an authorized current Cloud export and operator evidence. A local rehearsal does not satisfy those production controls.

## Read-only production verification

Use the existing school-admin Reports export to obtain the live database facts. No database credentials or environment-variable values are needed:

1. In Laravel Cloud, open the Hande High application and select the environment whose URL is `handehigh-production-mbxi6c.laravel.cloud`. In that environment's deployment/source settings, record the repository, configured branch, and latest successful deployed commit. Compare these with the repository and release before any push. Management previously identified `main` as the deployment branch, but verify it in the current environment before remote operations.
2. In the production ERP, sign in as an active school administrator or super administrator and open **Reports → Student Master Register** (`/admin/reports/student-register`). Use **Private Term 1 reconciliation export**, enter academic year `2026`, and download the JSON through the authenticated, audited form. Do not copy environment settings, database connection details, or credentials.
3. Keep the downloaded JSON in an access-controlled local folder. Its allowlisted fields include the runtime database driver, school ID/code, students and enrolments, accounting periods, accounts and mappings, invoices, and journal batches/entries. Confirm the export timestamp and school identity before review. The request is tenant-scoped to the signed-in user's school; no public storage URL is created.
4. Run the controlled-import command locally in its default mode with the private manifest and downloaded snapshot. This mode does not query a database or write student/financial records; it creates a private dry-run report. Re-run after any mapping, period, identity, or source-evidence change.
5. In Cloud's database resource, verify a completed backup and its recovery point. Verify recoverability by restoring a copy into an isolated non-production database/environment and checking that the copy opens and its schema/row controls match the backup evidence. Never restore over production for the verification test. If the Cloud dashboard does not offer an isolated restore path, ask Laravel Cloud support for the supported recovery-test procedure; a successful backup indicator alone is not a restore verification.

Laravel Cloud publicly documents an environment **Commands** interface for running Artisan commands and managed database backups. This does not establish that a backup has been restored successfully, nor does it prove the current environment's branch or commit; record those from the selected environment's live settings and deployment history. See [Laravel Cloud overview](https://marketing.cloud.laravel.com/) and [Laravel Cloud deployment setup](https://marketing.cloud.laravel.com/migrate-vapor-cloud).

## Accounting configuration proposal for review

The 24 October export contains zero `account_mappings`. Its active revenue accounts include code `5100` Tuition Fees, `5015` Tuition Day - Form 1, `5200` Boarding Fees, and `5010` Boarding Fees - Form 1, plus levy accounts. These are candidate accounts only: the export omits `is_postable`, and the source register supplies no approved student-level fee-vote split. The old Term 1 structures have no vote on Boarding 01 and no explicit mapping for it; Tuition 01 points to account 2 but is a configured fee structure, not proof of the original historical composition. Do not configure mappings from these candidates until the bursar/management approves the original vote-to-account schedule. Record any approved mapping change as a separate reviewed configuration change and export a fresh snapshot afterward.

## Future approved command

After deployment of this code, a fresh export, verified backup/restore, final dry run, and two-person approval, the exact command shape is:

```powershell
php artisan finance:term1-controlled-import `
  --manifest=storage/app/private/term1-import/approved-manifest.json `
  --snapshot=storage/app/private/term1-import/current-reconciliation-export.json `
  --execute `
  --approval=storage/app/private/term1-import/term1-approval.json `
  --operator=<authorized-maker-user-id> `
  --deployed-commit=<confirmed-40-character-production-commit> `
  --report=term1-2026-approved-execution.json
```

Do not run this command while any selected row is held or any execution prerequisite is unverified. The approval file's `cleared_references` list must contain only independently approved, ready source references. The report and inputs contain student financial data and must stay in private storage with access limited to authorized administrators.

## Current rehearsal status

The available production export was captured on 24 October 2026. It reports MySQL, 955 students, 1,910 October-dated Term 1 enrolment records, zero invoices, zero account mappings, and no exported accounting-period rows. Its account export does not include `is_postable`. The locally prepared source manifest has 1,022 source rows: 194 blank-fee scholarship exceptions, 96 unconfirmed potential-new students, and 217 identity-review rows. It preserves all 955 current students and does not create enrolments or receipts.

The 24 October snapshot is stale for production execution. No production driver, live deployment commit, backup, isolated restore, or current ledger reconciliation has been verified. The rehearsal therefore clears zero invoice rows and USD 0.00; no production write is authorized by this report.
