# Term 1 2026 student and AFC import controls

These tools are local implementation work on `feature/term1-student-afc-import`. They are not deployed and have not been run against production. They do not authorize a live import.

## Student master records

`Term1StudentReconciliationPlanner` compares a candidate manifest with the current private ERP snapshot. A record is marked `READY_CREATE` only when it has a source reference, stable admission number, explicit absence confirmation, identity evidence, grade/class/residency and no matching admission number, registration number, or same-name candidate in the snapshot. Existing ERP identities and same-name candidates are held for manual resolution.

`Term1StudentReconciliationService` rechecks that decision inside a transaction, locks the school, verifies the current snapshot hash, production database driver/commit and two distinct active same-tenant administrators, plus recent backup-and-isolated-restore and management approval attestations. It creates the absent student and a completed historical Term 1 2026 enrolment. It never edits existing students or their current class/enrolment records. Source references are idempotency keys and each creation is audited.

Read-only local plan:

```powershell
php artisan finance:term1-student-import `
  --manifest=storage/app/private/term1-import/students.json `
  --snapshot=storage/app/private/term1-import/current-reconciliation-export.json
```

Execution is intentionally distinct and requires approval evidence, fresh snapshot, backup/restore attestation and confirmed deployment data:

```powershell
php artisan finance:term1-student-import `
  --manifest=storage/app/private/term1-import/students.json `
  --snapshot=storage/app/private/term1-import/current-reconciliation-export.json `
  --execute `
  --approval=storage/app/private/term1-import/student-approval.json `
  --operator=<maker-user-id> `
  --deployed-commit=<confirmed-full-production-commit>
```

## AFC cashbook

The AFC planner is scoped to explicit source dates in January-April 2026. It requires source references, descriptions, evidence, positive amounts, stable account mappings and a transaction classification. Expenses and other income require explicit same-school active postable offset accounts of the appropriate account type. Transfers require a paired reference and two distinct same-school cash/bank accounts. Student receipts require a stable ERP admission number, an existing Term 1 2026 invoice, original receipt number, supported payment method and amount no greater than the current invoice balance.

Unmatched student receipts, missing dates, out-of-scope dates, unsupported account mappings and source-only bank statement movements are held. Bank statement evidence never creates a fictitious receipt. Approved student receipts create one payment and invoice allocation and use the existing payment journal path; approved expenses/income use the existing manual cashbook posting path; transfers create balanced entries in both accounts. All posting is transactional, audited and keyed by the source date, reference, amount and type. Term 2 and Term 3 dates are refused.

Read-only plan and, only after separate management approval, gated execution:

```powershell
php artisan finance:afc-cashbook-import `
  --manifest=storage/app/private/term1-import/afc-jan-apr.json `
  --snapshot=storage/app/private/term1-import/current-reconciliation-export.json

php artisan finance:afc-cashbook-import `
  --manifest=storage/app/private/term1-import/afc-jan-apr.json `
  --snapshot=storage/app/private/term1-import/current-reconciliation-export.json `
  --execute `
  --approval=storage/app/private/term1-import/afc-approval.json `
  --operator=<maker-user-id> `
  --deployed-commit=<confirmed-full-production-commit>
```

Both commands restrict inputs and reports to `storage/app/private/term1-import/`, use exclusive file creation and mode `0600`, and do not print student/payment payloads in command output. Store source workbooks and JSON files only in access-controlled private storage. Do not include them in Git.

## Current readiness

The previous production JSON was captured 24 October 2026 and is stale. A current administrator export, verified live deployment commit/database driver, reviewed fee/account mappings, and recoverable backup with isolated restore evidence are still required before any execution. These services do not parse the AFC workbook or invent incomplete dates, vote splits, receipts, deposits, or sponsor classifications; source-to-manifest preparation and human exception review remain required. No production database writes, invoices, receipts, transfers, migrations or seeders have been run by this work.
