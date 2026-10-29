# Academic Student Reconciliation

## Purpose and scope

This is a **read-only comparison** workspace under Academic. It compares one or more student, class/stream, and invoice registers with each other and with school-scoped ERP student, enrolment, and invoice records for a selected academic year and term. It does not create, update, or delete ERP students, enrolments, invoices, payments, or accounting entries.

The only writes are reconciliation batches, limited normalized source evidence, findings, review notes, and audit events. Uploaded workbooks are parsed from the request's temporary upload and are not copied to public or private storage. Unknown columns are ignored. Matching uses exact school-scoped ERP IDs, admission numbers, and registration numbers first. Name-only or name/date-of-birth matches remain flagged for verification and do not trigger invoice comparisons.

## Access setup

The normal `RolePermissionSeeder` defines the permissions for fresh installs. For a targeted environment setup, run the additive seeder after deployment:

```bash
php artisan db:seed --class=AcademicStudentReconciliationAccessSeeder --force
```

This adds the reconciliation permissions to `super-admin` and `school-admin` and does not replace other role permissions. Access is tenant-scoped. Review actions update reconciliation-finding metadata only and require a note; they never edit a student or financial record.

## Workflow

1. Open **Academic → Student Reconciliation** and start a batch.
2. Select the academic year and term, add up to eight `.xlsx`, `.xls`, `.csv`, or `.txt` files, and classify each as a student register, class/stream register, or invoice register.
3. Select the complete-register checkboxes only when the uploads cover the full relevant student or invoice population. Otherwise absence from a file is not treated as a finding.
4. Review findings by severity, type, status, or search term. Source file and worksheet row references are retained. Matched student and invoice links open their ordinary ERP screens for separate, manual follow-up.
5. Add an auditable note when marking a finding reviewed or dismissed. These labels describe the review only; they do not resolve or alter ERP records.
6. Print the filtered report page or download the private CSV containing the complete finding set.

## Checks performed

- Identity mismatches, ambiguous identities, duplicate source rows, and possible identity matches requiring verification.
- Source year/term disagreements with the batch scope.
- Source grade/form, class/stream, and residency compared with the active ERP enrolment for the selected year and term.
- ERP enrolment grade compared with the linked class's configured grade.
- Invoice claimed in a source but absent from the selected ERP period; invoice present when a source says it is not invoiced; invoice reference and amount differences; multiple matching invoices.
- ERP active enrolments absent from uploads marked as a complete student register, and ERP invoices absent from uploads marked as a complete invoice register.
- Differences across uploaded sources for a matched student.

Only stable identifier matches are treated as confirmed for invoice and placement comparison. Ambiguous and tentative identities stay in the verification queue. The report never guesses a correction or applies one.

## Limits and privacy

Uploads are limited to 8 files per batch, 5 MB and 5,000 data rows per file, and 10,000 rows total. The workbook parser scans the first 30 rows for a recognized header and stores only the recognized identity, placement, residency, period, and invoicing fields needed for comparison. The private CSV report includes student identifiers, names, source references, and differences; share it only with staff authorised to review student records. Audit logging records batch generation, finding review, and report export.
