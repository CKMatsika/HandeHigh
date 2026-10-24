# Scholarship category implementation report

Date: 24 October 2026. Local implementation for management review.

Baseline: `release/financial-engine-stabilisation`, commit `75133f2a71508d3281347ca18280879ea18a1b1a`. This feature is uncommitted. No production access, migrations, push, merge or deployment has been performed.

## Existing functionality reused

No scholarship award, bursary, sponsor funding or fee-remission workflow existed in the inspected application. A clearance-screen example mentioned scholarships, but it did not implement funding. The implementation reuses:

- `students.is_boarding`, enrollment `student_type` / `is_boarding`, effective-residency lookup and the existing bed-allocation service.
- Boarding/day fee packages and `InvoiceFeeSelectionService`, fee-vote codes and the normal gross invoice posting.
- `FinancialAccountResolver`, existing tenant-specific `account_mappings`, and `AccountingService::createJournalBatch` for every new journal.
- Existing `AuditLog` for award changes, approval, posting, closure, corrections and funding-status changes. No separate audit-log table was introduced.
- Existing payment allocations, student register/export, cashbook and bank reconciliation feed.

## Final data model and migration

The additive migration is `2026_10_24_000002_add_student_scholarships.php`.

| Structure | Purpose |
| --- | --- |
| `students.funding_status` | `regular` by default, or `scholarship`; separate from residency |
| `student_scholarships` | Tenant/student, unique award reference per student, name, sponsor, full/partial coverage, percentage/amount basis, approved value, selected votes, effective dates, document reference, approval status/reference and approving user/time |
| `scholarship_applications` | Award/invoice coverage, immutable item/vote amounts and award snapshot, accounting date, original and reversal journal references; unique award/invoice pair |
| `scholarship_sponsor_receipts` | Actual money received to settle sponsor debt, with unique tenant receipt reference, bank/cash account, date, amount and reversal trail |

The forward migration does not rename, reset, truncate or delete student/finance tables, does not recalculate existing fees and does not populate awards for existing students. It adds the funding column with its regular default and creates new tables with restrictive deletion rules. Its rollback intentionally refuses to discard financial history: rollback requires a reviewed preservation plan.

The production count of 955 students is management-provided context, not a count queried during this work. No production records were accessed or modified.

## Registration and editing

Both Students and Enrollments offer Boarding, Day and Scholarship. Scholarship requires a second boarding/day residency choice. The shared category service normalizes these choices; `scholarship` is never written as residency.

1. Save the student/enrollment with the category and actual residency.
2. Open **Scholarships** from the student's profile.
3. Record a pending award, selected fee votes, coverage, dates, sponsor if applicable and supporting document reference.
4. Management configures approved accounts and supplies an approval reference. Approval validates overlap and policy but posts no money or invoice adjustment.
5. New invoices use approved, date-eligible coverage. Applying an award to an existing invoice is an explicit, dated action.

Pending terms can be edited with an audit record. Approved terms are frozen; close the award and create a new reference for replacement terms. Closing stops future use and preserves existing coverage. Corrections use dated compensating journals. An invoice with scholarship history cannot be deleted, cancelled or rewritten through the normal invoice-edit path.

Scholarship is a classification, not proof of an active award: pending, expired and closed award history remains visible. Existing students are not automatically reclassified. Current approved awards must be closed before deliberately changing funding status to regular.

## Fee structures, coverage and payments

- Existing fee packages remain unchanged: boarding with one science subject totals **$470**, day with one science subject totals **$135**.
- Scholarship status does not alter normal fee selection. Residency, subjects, optional services and existing fee rules determine the gross invoice first.
- Full coverage means 100% of the **selected votes**, not all fees. Percentage coverage applies to selected gross vote amounts, capped by unpaid item/invoice balances.
- An amount award has a total cap across all invoices under that award. Allocation is deterministic in invoice-item order, and all arithmetic affecting coverage is rounded to cents.
- Uncovered votes and unfunded portions remain payable. Invoice gross totals and original revenue journals are preserved; scholarship credits settle only approved coverage.
- Overlapping approved awards for the same student, vote and date range are rejected, even where both are partial. Disjoint dates/votes are allowed.
- Repeat application of the same award to the same invoice returns the recorded application. A reversed application is not silently replayed; a new approved reference is needed for a replacement.
- Student payments after coverage are restricted to unpaid votes and cannot consume an already funded line. Invoice locks protect coverage/payment balance updates.
- Existing invoice-level payments without item allocations, or applied generic credit notes, require a fee-vote allocation review before scholarship application. Generic credit-note application to invoices with scholarship history is blocked rather than risking a second credit through the legacy workflow.
- Fully covered invoices use the application's existing `paid` settlement status. This status does not imply cash: scholarship and cash are reported separately.

## Accounting policy and postings

No scholarship accounts or accounting policy are guessed or automatically seeded. Awards can remain `unconfigured` while policy is pending. Approval fails with a validation message until the relevant explicit mapping exists.

| Event | Debit | Credit | Cash created? |
| --- | --- | --- | --- |
| Normal invoice | Student receivables | Normal fee-vote revenue | No |
| School-funded remission | Management-approved scholarship expense or contra-revenue account | Student receivables | No |
| Recognized sponsor undertaking | Separate sponsor receivable asset | Student receivables | No |
| Actual sponsor payment | Selected bank/cash asset | Original sponsor receivable account | Yes, only after explicit receipt confirmation |
| Correction | Exact opposite of original entries, on the correction date | Corresponding opposite entry | Only receipt corrections affect cashbook |

Use mapping type `scholarship` with keys `SCHOOL_REMISSION` and `SPONSOR_RECEIVABLE`. Sponsor receivables must be separate from student debtors. Mappings and awards are tenant-specific. Mapping approval references are audited; posted applications retain original accounts and award snapshots if mappings change later.

Every new posting uses the financial engine, with a source record, journal linkage and atomic transaction. Awards never create `Payment`, cash `Receipt` or cashbook records. Actual sponsor receipts use a dedicated sponsor subledger, balanced journals, the existing cashbook and its reconciliation feed. They do not create another student payment or recognize fee revenue again. Sponsor outstanding balances and receipt history are shown against each sponsored invoice. Receipt reversals must precede reversing settled scholarship coverage.

Student profiles, financial statements and the debtor-aging service distinguish scholarship credits from cash. Admin statement views/print/PDF/email and parent statements include dated scholarship credits and reversals. Student GL history includes scholarship postings. Scholarship lines retain their original posting date when later reversed.

## Hostel and residency

The existing bed-allocation service remains authoritative for residency, tenant, gender, availability and capacity checks. Scholarship boarders remain eligible; scholarship day students cannot be allocated a bed. Profile/import changes to Day are blocked while a current bed exists; use the existing release/transition workflow first. Funding status does not create or release beds.

## Reporting and imports

Student lists support the three category choices plus separate residency and funding filters. Boarding/day category filters include both funding statuses; select Regular or Scholarship to narrow them. The student register supports residency/funding combinations, and exports append category, funding, award references, sponsors, coverage, dates, treatment and approval details while preserving the existing columns.

The enrollment XLSX template retains the original 13 columns first and appends:

`student_category`, `residency`, `funding_status`, `scholarship_reference`, `scholarship_name`, `scholarship_sponsor`, `scholarship_coverage_type`, `scholarship_coverage_basis`, `scholarship_coverage_value`, `scholarship_covered_votes`, `scholarship_starts_on`, `scholarship_ends_on`, `scholarship_accounting_treatment`, `scholarship_supporting_reference`.

Legacy files still use `is_boarding=yes/no`. When scholarship fields are absent, existing funding metadata is preserved and new students default to regular. Scholarship rows must explicitly state residency or `is_boarding`. Conflicting category/residency/funding values are rejected. Votes are pipe-separated, for example `TUITION|SCIENCE_LEVY`.

Award fields are optional as a group: category-only scholarship imports can be completed through the profile. If an award reference is supplied, its name, coverage, votes and dates are required. Imported awards are always pending; spreadsheet values cannot approve or rewrite an existing award. Duplicate award references fail and the file transaction rolls back. Row processing also uses a transaction. Importing alone never posts scholarship journals.

## Access controls

- School administrators, headmasters, deputies, bursars and accountants can view/create pending awards and apply approved coverage.
- Only super-admin, school-admin and headmaster roles can approve/close awards, configure management mappings or reverse coverage/receipts.
- Accounts clerks and unrelated roles cannot manage scholarships.
- Tenant-scoped route bindings and explicit student/award/invoice checks apply even to super-admin users operating in a school context.

## Validation

The new integration suite covers four registration combinations; full/partial and amount-cap awards; selected and uncovered votes; debtor/GL/statement reconciliation; unchanged original revenue journals; no fictitious cash; actual sponsor receipts/reversals; hostel residency; legacy and scholarship imports; transactional import failure; automatic new-invoice coverage; frozen approved terms; reporting/export fields; and tenant/role access restrictions.

- Full application run during implementation: **509 tests, 2,888 assertions; 508 passed and one failed**. The failure exposed a pre-existing duplicated/unclosed payment-history Blade block in `resources/views/admin/bills/show.blade.php` from the stabilisation baseline. Removed the malformed duplicate block, retaining the complete account-aware payment history.
- After that fix, the supplier accounting suite passed **3 tests, 39 assertions**.
- Final affected regression run: **254 tests, 1,497 assertions, zero failures/errors**, covering all Finance, Admin and Authorization suites plus enrollment imports, academic reporting and supplier accounting. It reported **32 PHPUnit deprecations** in legacy tests.
- Final scholarship integration run after adding invoice screen/print rendering checks: **27 tests, 126 assertions, zero failures/errors**.
- Financial engine baseline: **12 scenarios / 87 assertions** and **7 stabilisation tests / 64 assertions**, both passing in the final regression run.
- Student enrollment sync: **4 tests / 17 assertions**; legacy enrollment import suite: **10 tests / 30 assertions**; financial tenant isolation: **14 tests / 70 assertions**, all passing.
- All changed PHP source files pass syntax checks; `git diff --check` passes. Test logs are local ignored files under `storage/logs`.
- The initial full run reported 37 legacy PHPUnit deprecations. A second complete application run was not repeated after the isolated bill-view fix; the affected regression suites and the final feature suite were rerun as recorded above. No latest targeted failures remain.

## Decisions for management before operational use

1. Approve expense versus contra-revenue treatment for school-funded remission and the exact ledger accounts.
2. Approve when sponsor undertakings qualify for receivable recognition. Until approved, retain the award as pending/unconfigured; a promise must not be treated as cash.
3. Confirm percentage coverage and the lifetime amount-cap convention, invoice-item allocation order, school-ledger currency and whether future policy requires per-term caps or multi-currency awards.
4. Confirm approval authority, supporting-document retention and whether a separate maker/checker requirement is needed. The current flow restricts approvers by role but does not require a different individual from the creator.
5. Approve any retrospective scholarship applications and the accounting dates/periods to use. Nothing automatically rewrites historical invoices when an award is approved or changed.

## Remaining technical limits

Validation uses synthetic SQLite in-memory databases. This is not a MySQL concurrency or Laravel Cloud deployment certification. Existing historical as-of subledger limitations and other risks recorded in `LOCAL_FINANCIAL_COMMIT_REPORT.md` remain separate baseline issues. Run a controlled isolated MySQL rehearsal and review migration/backup plans before deployment. No deployment or production migration is authorised by this report.

The pre-existing seven diagnostic PHP scripts remain untracked and untouched. Environment files, databases, test logs and runtime caches are not release artifacts. The financial stabilisation commit remains the local HEAD; no scholarship commit has been created.

## Changed file manifest

- app/Exports/StudentRegisterExport.php
- app/Http/Controllers/Admin/AcademicReportController.php
- app/Http/Controllers/Admin/CashbookController.php
- app/Http/Controllers/Admin/CreditNoteController.php
- app/Http/Controllers/Admin/EnrollmentController.php
- app/Http/Controllers/Admin/InvoiceController.php
- app/Http/Controllers/Admin/PaymentController.php
- app/Http/Controllers/Admin/ScholarshipController.php
- app/Http/Controllers/Admin/StatementController.php
- app/Http/Controllers/Admin/StudentController.php
- app/Http/Controllers/Portal/ParentController.php
- app/Imports/EnrollmentsImport.php
- app/Models/Invoice.php
- app/Models/InvoiceItem.php
- app/Models/ScholarshipApplication.php
- app/Models/ScholarshipSponsorReceipt.php
- app/Models/Student.php
- app/Models/StudentScholarship.php
- app/Providers/AppServiceProvider.php
- app/Services/Academic/AcademicReportingService.php
- app/Services/AccountingService.php
- app/Services/Finance/FinanceReportingService.php
- app/Services/Finance/ScholarshipService.php
- app/Services/StudentCategoryService.php
- database/migrations/2026_10_24_000002_add_student_scholarships.php
- docs/SCHOLARSHIP_IMPLEMENTATION_REPORT.md
- resources/views/admin/bills/show.blade.php
- resources/views/admin/enrollments/create.blade.php
- resources/views/admin/enrollments/edit.blade.php
- resources/views/admin/invoices/print.blade.php
- resources/views/admin/invoices/show.blade.php
- resources/views/admin/reports/student-register.blade.php
- resources/views/admin/students/_category-fields.blade.php
- resources/views/admin/students/_scholarship-fields.blade.php
- resources/views/admin/students/create.blade.php
- resources/views/admin/students/edit.blade.php
- resources/views/admin/students/index.blade.php
- resources/views/admin/students/scholarships.blade.php
- resources/views/admin/students/show.blade.php
- routes/web.php
- tests/Feature/Finance/ScholarshipIntegrationTest.php
