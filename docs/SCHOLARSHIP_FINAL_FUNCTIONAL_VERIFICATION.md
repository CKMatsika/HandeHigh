# Final scholarship functional verification

Date: 24 October 2026. Result: **PASS within the isolated test scope below**.

Environment: Laravel HTTP feature requests, synthetic records and SQLite `:memory:` only. The two new verification tests assert the connection and database identity. No production/staging connection was opened. No application implementation was changed during this verification; only focused verification tests and this report were added.

## Screens and routes exercised

| Screen / action | Route | Result |
| --- | --- | --- |
| Create | GET/POST `/admin/students/create`, `/admin/students` | Three options extracted from rendered HTML: Boarding, Day, Scholarship; all five synthetic profiles registered successfully |
| Edit | GET/PUT `/admin/students/{student}/edit`, `/admin/students/{student}` | Each profile rendered and saved, retaining the selected funding/residency combination |
| View | GET `/admin/students/{student}` | All five profiles rendered with the correct category/residency label |
| Listing | GET `/admin/students?category=scholarship&boarding=yes` | Exactly the two scholarship boarders returned; regular boarder and scholarship day student excluded |
| Scholarship details | GET/POST `/admin/students/{student}/scholarships` | Name/reference, sponsor, full/partial, basis/value, selected votes, dates, accounting treatment and supporting reference fields rendered; three awards submitted successfully |
| Approval/application | POST `/admin/students/{student}/scholarships/{scholarship}/approve` and `/apply` | Pending → approved; no balance change on award/approval; explicit application changed only covered balances |
| Invoice / statement | GET `/admin/invoices/{invoice}`; POST `/admin/students/{student}/statement` | Rendered successfully; statements matched each remaining balance |
| Hostel allocation | POST `/admin/students/{student}/assign-bed` | Scholarship boarder allocated; scholarship day student rejected with the residency validation error |

The exact `onchange` JavaScript extracted from the rendered form was executed in Node: Boarding and Day hide the scholarship residency field and make it optional; Scholarship shows it and makes it required. Both scholarship residency choices were exercised through actual registration requests.

**Workflow clarification:** Create/Edit captures category and residency. Detailed scholarship awards are entered through the student's **Scholarships** page after saving the profile; the registration form does not display inline award-detail fields. This is the existing implementation, not a newly added workflow.

These checks verify rendered HTTP interfaces, the actual form handler and controller/database/posting flows. They do not constitute a live browser click-through or visual layout audit.

## Actual configured fee calculations

The fixture populated the existing `HandeFeeVotesService` schedule in the disposable database. `InvoiceFeeSelectionService` selected fee records by actual residency; their amounts were submitted to the invoice route. Example totals appear only as test expectations, not new application calculation logic.

| Vote | Boarding assessment | Day assessment | $300 boarder award allocation | Selected-vote day award allocation |
| --- | ---: | ---: | ---: | ---: |
| Tuition | 20.00 | 20.00 | 20.00 | 10.00 |
| Building Fund | 15.00 | 15.00 | 15.00 | 0.00 |
| Sports | 10.00 | 10.00 | 10.00 | 0.00 |
| ICT | 15.00 | 15.00 | 15.00 | 0.00 |
| General Purpose Fund | 10.00 | 10.00 | 10.00 | 0.00 |
| Science — one subject | 20.00 | 20.00 | 20.00 | 10.00 |
| Boarding | 380.00 | — | 210.00 | — |
| Food/lunch | — | 45.00 | — | 0.00 |
| **Total** | **470.00** | **135.00** | **300.00** | **20.00** |

| Synthetic scenario | Invoice | Gross assessment | Applied scholarship | Student payable | Sponsor receivable |
| --- | --- | ---: | ---: | ---: | ---: |
| Regular boarding | INV-2026-000001 | 470.00 | 0.00 | **470.00** | 0.00 |
| Regular day | INV-2026-000002 | 135.00 | 0.00 | **135.00** | 0.00 |
| Full scholarship boarding | INV-2026-000003 | 470.00 | 470.00 | **0.00** | 0.00 |
| $300 scholarship boarding | INV-2026-000004 | 470.00 | 300.00 | **170.00** | 0.00 |
| Partial scholarship day | INV-2026-000005 | 135.00 | 20.00 | **115.00** | **20.00** |

The full boarder award covered all seven boarding votes at 100%. The $300 award used the existing deterministic invoice-item order: the six non-boarding votes used $90, then $210 covered boarding, leaving $170 boarding payable. The day award covered 50% of Tuition and Science only; every other vote remained payable.

## Journal and reconciliation evidence

All original assessments were posted before creating awards. Each award remained pending until its separate synthetic approval. Award creation and approval themselves produced no coverage journal or change in student debt. Application generated:

| Reference | Debit | Credit |
| --- | --- | --- |
| Original invoices, aggregate | Student Debtors `1201`: 1,680.00 | Fee-vote revenue: 1,680.00 |
| SCH-1 — full boarding | Synthetic remission expense `SCH-EXP`: 470.00 | Student Debtors `1201`: 470.00 |
| SCH-2 — $300 boarding | Synthetic remission expense `SCH-EXP`: 300.00 | Student Debtors `1201`: 300.00 |
| SCH-3 — selected day votes | Synthetic sponsor receivable `SCH-AR`: 20.00 | Student Debtors `1201`: 20.00 |

Original revenue credits, by configured vote account: Tuition `5100` $100; Building `5120` $75; Sports `5130` $50; ICT `5140` $75; GPF `5170` $50; Science `5160` $100; Boarding `5200` $1,140; Food `5803` $90. **Total revenue remains $1,680.00.** The expense/sponsor accounts and approval references above are synthetic test policy, not a management policy decision.

- Journal movement totals: **debits $2,470.00 = credits $2,470.00; difference $0.00**.
- Student Debtors GL: **$890.00**; outstanding invoice balances: **$890.00; difference $0.00**.
- Closing debit balances: Student Debtors $890 + Sponsor Receivable $20 + Remission Expense $770 = **$1,680**, matching closing revenue credits of **$1,680**.
- Sponsor receivable for the selected-vote day student: **$20.00**, unpaid; no sponsor money was recorded as received.
- Payment records: **0**; cash Receipt records: **0**; Cashbook records: **0**.
- Repeating all three scholarship applications produced exactly **three** scholarship journals. Original invoice journal entry IDs were unchanged, and revenue was not credited again.

## Existing records and supplier-view fix

The additive scholarship migration was applied to a reconstructed pre-feature student schema containing **955 synthetic legacy students**, all inside SQLite memory. Every original field and record ID matched before/after; all new funding values defaulted to `regular`; no awards were created. The before/after SHA-256 fingerprints were identical. This verifies the additive path against a 955-record representative dataset; the actual production records and MySQL schema were not inspected or certified.

The supplier defect was in `resources/views/admin/bills/show.blade.php`: the baseline contained an older Payment History table beginning with `@if($bill->payments->count())` at original line 106, followed at original line 134 by a second Payment History block, without closing the first conditional. Blade failed with **unexpected end of file, expecting elseif/else/endif**, causing the bill view to return HTTP 500. The proposed fix removes the obsolete duplicate block (original lines 106–133) and retains the complete account-aware history table. It changes markup only, with no supplier posting or financial engine logic change.

Fresh financial engine and supplier accounting tests below pass against the proposed changes. The Financial Engine Stabilisation baseline commit is still `75133f2a71508d3281347ca18280879ea18a1b1a`.

## Focused results

| Suite/check | Tests | Assertions | Result |
| --- | ---: | ---: | --- |
| Final functional scenarios and 955-record migration preservation | 2 | 182 | PASS |
| FinancialEngineScenariosTest | 12 | 87 | PASS |
| FinancialPostingStabilisationTest | 7 | 64 | PASS |
| SupplierInvoiceAccountingTest | 3 | 39 | PASS |
| **PHPUnit total** | **24** | **372** | **0 failures, 0 errors** |
| Rendered category JavaScript handler | 3 category changes | visibility + required checks | PASS |

`git diff --check` passes. The complete application/development exercise was not repeated. No application code was changed during this verification. The Node handler check initially encountered a local sandbox filesystem restriction; the identical local check succeeded when rerun with approved filesystem access.

Reproducible commands:

```text
php vendor/phpunit/phpunit/phpunit tests/Feature/Finance/ScholarshipFinalVerificationTest.php tests/Feature/Finance/FinancialEngineScenariosTest.php tests/Feature/Finance/FinancialPostingStabilisationTest.php tests/Feature/Procurement/SupplierInvoiceAccountingTest.php --log-junit storage/logs/scholarship-focused-verification.xml
node tests/Support/verify-scholarship-category.cjs
```

Synthetic raw evidence and JUnit output remain ignored under `storage/logs`; they are not proposed release files.

## Outstanding management decisions

1. School-funded remission: approve expense versus contra-revenue treatment and exact account mappings.
2. Sponsorship: approve when a sponsor commitment qualifies as a receivable and the separate sponsor control account. A promise is never cash.
3. Confirm award-wide amount caps versus per-term caps, allocation order, ledger currency and retrospective posting rules.
4. Confirm approval authority, supporting-document retention and whether creator/approver separation is mandatory.

Awards remain unconfigured/pending until policy and mappings are explicitly approved. This verification does not authorise production use or deployment.

## Proposed commit boundary

All changes remain local, unstaged and uncommitted on `release/financial-engine-stabilisation`. No push, merge, deployment or production migration was performed. The seven pre-existing diagnostic scripts and ignored environment/database/runtime files remain excluded. The exact proposed source, migration, view, test and documentation manifest follows.

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
- docs/SCHOLARSHIP_FINAL_FUNCTIONAL_VERIFICATION.md
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
- tests/Feature/Finance/ScholarshipFinalVerificationTest.php
- tests/Feature/Finance/ScholarshipIntegrationTest.php
- tests/Support/verify-scholarship-category.cjs
