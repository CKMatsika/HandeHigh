# Financial Engine Stabilisation — Local Commit Report

Date: 24 October 2026 (Africa/Harare).
Branch: release/financial-engine-stabilisation.
Commit message: Stabilise financial posting, account mappings and historical migration framework.
Commit identity: use `git log -1 --format=fuller -- docs/LOCAL_FINANCIAL_COMMIT_REPORT.md` after this report is committed. A commit cannot embed its own final hash.

Scope and preparation:
- Reused the already-existing dedicated release branch; preserved the current work.
- Included both financial migrations, mappings, historical migration and reconciliation services, controllers, routes, views, tests and documentation.
- Removed the extra closing brace in the first financial migration and trailing whitespace from release files.
- Replaced a bank-account number in the synthetic rehearsal account label and report with a neutral label.
- Updated the tenant-isolation success fixture to configure the newly required revenue mapping; retained its original isolation assertions.

Validation:
- Finance directory: 111 tests, 631 assertions, no errors or failures; 32 PHPUnit deprecations.
- FinancialEngineScenariosTest: 12 tests, 87 assertions, no errors or failures (included above).
- FinancialPostingStabilisationTest: 7 tests, 64 assertions, no errors or failures (included above).
- FinancialTenantIsolationTest: 14 tests, 70 assertions, no errors or failures.
- Staged PHP source syntax checks passed. Working-tree and staged git diff --check passed.
- Tests used the PHPUnit SQLite :memory: configuration. No production or staging migration command or rehearsal was executed in this review.
- Earlier runs exposed the migration syntax error and a missing revenue mapping in the tenant test fixture; the final runs above supersede those results.
- The historical MySQL rehearsal evidence is retained as previously supplied; it was not independently rerun here.

Migration safety:
- Both up() methods add schema structures without dropping, resetting or truncating existing tables or student records.
- The first migration also updates only previously empty bank_accounts.account_id values from same-school account-code matches. This is a data backfill, not a schema-only migration; bank-name/code assumptions require production review.
- down() removes the introduced tables and columns. Do not roll back populated structures without a reviewed preservation plan.

Excluded and preserved locally:
- analyze_rehearsal_reconciliation.php
- check_bacc_rel.php
- check_processlist.php
- check_staging_state.php
- check_staging_status.php
- debug_step_by_step_reconciliation.php
- test_rehearsal_run.php
- Ignored environment files, local databases, dependencies, runtime caches and logs, including the local JUnit output.
- No new credential literal, database export or live student dataset was identified in the staged release files. Existing tracked files outside this release were not added or altered merely to clean historical repository contents.

Remaining risks before deployment or real historical imports:
- CashbookController::update can still alter financial fields on posted records without a correcting journal; posted-record immutability needs enforcement.
- HistoricalMigrationService::postBatch does not enforce approved status, does not lock the batch/rows against concurrent import posting, and supplier_bills is validated but unsupported by its posting switch.
- Historical student-receipt imports select the first active bank and post against the first unpaid invoice without completing allocation/balance updates; real receipts need a reviewed allocation workflow.
- Default account mapping uses heuristic codes/names and fallbacks, and bank-reconciliation posting still uses hardcoded category codes; management must approve explicit mappings before use.
- Reversal matching falls back to account/date/amount and lacks a row lock, so ambiguous matches and simultaneous reversal requests need further protection.
- Debtor/creditor reconciliation uses current subledger balances, so historical as-of results after later settlements require additional validation.
- The test called concurrent repeated posting executes sequential calls; passing it does not prove concurrent MySQL idempotency.
- Full application tests, production schema compatibility, Laravel Cloud deployment settings and MySQL concurrency have not been certified by this local review.
- The 32 PHPUnit deprecations remain.

Local-only boundary:
- No push, merge, deployment, production database access or production migration was performed.
- This commit preserves the approved local work and review findings; it is not production deployment approval.

Included file manifest:
- app/Console/Commands/AuditAndRemediateFinancialDiscrepancies.php
- app/Console/Commands/RunJanuary2026StagingMigrationRehearsal.php
- app/Exceptions/MissingAccountMappingException.php
- app/Http/Controllers/Admin/BillController.php
- app/Http/Controllers/Admin/BillPaymentController.php
- app/Http/Controllers/Admin/CashbookController.php
- app/Http/Controllers/Admin/FinancialReportController.php
- app/Models/AccountMapping.php
- app/Models/BankAccount.php
- app/Models/BillPayment.php
- app/Models/Cashbook.php
- app/Models/CashbookTransaction.php
- app/Models/JournalEntry.php
- app/Models/MigrationBatch.php
- app/Models/MigrationStagingRow.php
- app/Models/ReceiptItem.php
- app/Services/AccountingService.php
- app/Services/BankReconciliationService.php
- app/Services/Finance/CashbookReversalService.php
- app/Services/Finance/FeeRevenueAccountResolver.php
- app/Services/Finance/FinancialAccountResolver.php
- app/Services/Finance/FinancialReconciliationService.php
- app/Services/Finance/HistoricalMigrationService.php
- config/database.php
- database/migrations/2026_10_23_000001_stabilise_financial_posting_and_bank_mappings.php
- database/migrations/2026_10_24_000001_create_account_mappings_and_migration_staging.php
- docs/FINANCIAL_ENGINE_STABILISATION_REPORT.md
- docs/STAGING_MIGRATION_REHEARSAL_REPORT.md
- resources/views/admin/bills/payments/create.blade.php
- resources/views/admin/bills/show.blade.php
- resources/views/admin/cashbook/create.blade.php
- routes/web.php
- tests/Feature/Authorization/FinancialTenantIsolationTest.php
- tests/Feature/Finance/FinancialEngineScenariosTest.php
- tests/Feature/Finance/FinancialPostingStabilisationTest.php
- docs/LOCAL_FINANCIAL_COMMIT_REPORT.md
