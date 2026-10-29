# Academic Student Reconciliation

## Purpose and scope

This is a **read-only comparison** workspace under Academic. It compares one or more student, class/stream, and invoice registers with each other and with school-scoped ERP student, enrolment, and invoice records for a selected academic year and term. Source labels are hints; recognized workbook fields drive the comparisons. It does not create, update, or delete ERP students, enrolments, invoices, payments, or accounting entries.

The only writes are reconciliation batches, limited normalized source evidence, findings, review notes, and audit events. Uploaded workbooks are parsed from the request's temporary upload and are not copied to public or private storage. Unknown columns are ignored. Matching uses exact school-scoped ERP IDs, admission numbers, and registration numbers first. A unique name-only candidate can surface provisional invoice leads, clearly marked for identity verification; it is not counted as a confirmed match or used in the complete-invoice absence check.

## Access setup

The normal `RolePermissionSeeder` defines the permissions for fresh installs. For a targeted environment setup, run the additive seeder after deployment:

```bash
php artisan db:seed --class=AcademicStudentReconciliationAccessSeeder --force
```

This adds the reconciliation permissions to `super-admin` and `school-admin` and does not replace other role permissions. Access is tenant-scoped. Review actions update reconciliation-finding metadata only and require a note; they never edit a student or financial record.

## Workflow

1. Open **Academic → Student Reconciliation** and start a batch.
2. Select the academic year and term, add up to eight `.xlsx`, `.xls`, `.csv`, or `.txt` files, and choose the closest source label. The label does not prevent fields in that file from being used for invoice comparisons.
3. Select the complete-register checkboxes only when the uploads cover the full relevant student or billed population. Invoice evidence can come from any source type, including `Expected Fees` and a two-row heading such as `EXPECTED` above `TOTAL`. If the expected data or reliable ERP identity is missing, the reconciliation still completes and records why an absence comparison was skipped.
4. Review findings by severity, type, status, or search term. Source file and worksheet row references are retained. Matched student and invoice links open their ordinary ERP screens for separate, manual follow-up.
5. Add an auditable note when marking a finding reviewed or dismissed. These labels describe the review only; they do not resolve or alter ERP records.
6. Print the filtered report page or download the private CSV containing the complete finding set.

## Checks performed

- Identity mismatches, ambiguous identities, duplicate source rows, and possible identity matches requiring verification.
- Source year/term disagreements with the batch scope.
- Source grade/form, class/stream, and residency compared with the active ERP enrolment for the selected year and term.
- ERP enrolment grade compared with the linked class's configured grade.
- Invoice claimed in a source but absent from the selected ERP period; invoice present when a source says it is not invoiced; invoice reference and amount differences; multiple matching invoices. Unique name-only candidates may show warning-level provisional leads, while uncertain identities are never treated as confirmed.
- ERP active enrolments absent from uploads marked as a complete student register, and ERP invoices absent from complete uploads with sufficient invoice evidence and stable identity matches. If that evidence is incomplete, an informational finding explains why the absence check was skipped.
- Differences across uploaded sources for a matched student.

Only stable identifier matches are treated as confirmed for invoice and placement comparison. `Expected Fees`, `Expected Total`, invoice amount, or an explicit billed status are treated as source invoice evidence; for this fee register, a numeric expected amount means the student is claimed as billed. A unique name-only candidate may show a warning-level possible invoice difference to guide human review, but no absence or identity conclusion is made from it. Ambiguous identities stay in the verification queue. The report never guesses a correction or applies one.

## Limits and privacy

Uploads are limited to 8 files per batch, 5 MB and 5,000 data rows per file, and 10,000 rows total. The workbook parser scans the first 30 rows for a recognized header, combines adjacent heading rows, and stores only the recognized identity, placement, residency, period, and invoicing fields needed for comparison. The private CSV report includes student identifiers, names, source references, and differences; share it only with staff authorised to review student records. Audit logging records batch generation, finding review, and report export.
