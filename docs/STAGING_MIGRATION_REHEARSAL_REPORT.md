# HANDE HIGH SCHOOL ERP — JANUARY 2026 STAGING REHEARSAL RECONCILIATION REPORT
**Environment:** Isolated MySQL staging database; database name: `staging`.
**Database host (redacted):** `[redacted]`
**Database engine:** MySQL 8.4.7-7
**Execution timestamp:** 2026-10-24T12:32:14+02:00
**Rehearsal school:** `HHS-JAN26-REHEARSAL` (ID 3)
**Accounting window:** 2026-01-01 through 2026-01-31; only posted journal batches are included.
**Credentials:** Redacted; no credential values are included in this report.

## 1. Root cause of the previously reported differences
The available evidence points to an opening-balance basis mismatch: the prior reconciliation combined posted GL balances with reused `BankAccount.opening_balance` values, and existing bank-account rows were originally loaded with `firstOrCreate`, which does not refresh existing balances. The reported deltas exactly equal the implied opening-balance differences below. Because the prior transaction rows and bank-account snapshots are no longer present in the current staging snapshot, this is the supported root-cause diagnosis, not a recoverable row-ID-level proof:

| Account | Prior GL close | Prior cashbook close | Reported GL-minus-cashbook | Implied cashbook opening | GL opening journal |
| :--- | ---: | ---: | ---: | ---: | ---: |
| AFC (1800) | $7,675.00 | $7,375.00 | $300.00 | $9,700.00 | $10,000.00 |
| CABS (1301) | $8,350.00 | $9,350.00 | -$1,000.00 | $6,000.00 | $5,000.00 |
| ZB (1302) | $600.00 | $850.00 | -$250.00 | $250.00 | $0.00 |
| Cash Office (1303) | $915.00 | $1,535.00 | -$620.00 | $1,120.00 | $500.00 |

The earlier report did not preserve transaction, journal-batch, or cashbook IDs for these differences; those historical IDs cannot be recovered from the current staging snapshot. The deterministic full rehearsal below emits its own row-level transaction-to-journal trace. Step 1 now explicitly resets each rehearsal bank master opening balance to the same amount as its opening journal.

## 2. Opening trial balance (posted opening journal)
| Account | Name | Debit ($) | Credit ($) |
| :--- | :--- | ---: | ---: |
| 1800 | Bank - AFC (rehearsal) | 10,000.00 | 0.00 |
| 1301 | CABS bank (CABS) | 5,000.00 | 0.00 |
| 1303 | CASH On Hand/ Kiosk | 500.00 | 0.00 |
| 1201 | Student Fees Receivable | 750.00 | 0.00 |
| 3100 | Accounts Payable / Trade Creditors | 0.00 | 450.00 |
| 4000 | Opening Balance Equity | 0.00 | 15,800.00 |
| **Total** | | **16,250.00** | **16,250.00** |
**Opening variance:** $0.00.

## 3. January posted journal batches
| Batch | Reference | Source | Accounting date | Debit ($) | Credit ($) |
| ---: | :--- | :--- | :--- | ---: | ---: |
| #38 | OPENING-2026-01-01-3 | opening_balance | 2026-01-01 | 16,250.00 | 16,250.00 |
| #73 | IDEMP-IMPORT-TEST-001 | cashbook | 2026-01-01 | 150.00 | 150.00 |
| #39 | INV-2026-B001 | invoice | 2026-01-05 | 750.00 | 750.00 |
| #40 | INV-2026-B002 | invoice | 2026-01-05 | 750.00 | 750.00 |
| #41 | INV-2026-B003 | invoice | 2026-01-05 | 750.00 | 750.00 |
| #42 | INV-2026-B004 | invoice | 2026-01-05 | 750.00 | 750.00 |
| #43 | INV-2026-B005 | invoice | 2026-01-05 | 750.00 | 750.00 |
| #44 | INV-2026-B006 | invoice | 2026-01-05 | 750.00 | 750.00 |
| #45 | INV-2026-B007 | invoice | 2026-01-05 | 750.00 | 750.00 |
| #46 | INV-2026-B008 | invoice | 2026-01-05 | 750.00 | 750.00 |
| #47 | INV-2026-B009 | invoice | 2026-01-05 | 750.00 | 750.00 |
| #48 | INV-2026-B010 | invoice | 2026-01-05 | 750.00 | 750.00 |
| #49 | INV-2026-D001 | invoice | 2026-01-05 | 500.00 | 500.00 |
| #50 | INV-2026-D002 | invoice | 2026-01-05 | 500.00 | 500.00 |
| #51 | INV-2026-D003 | invoice | 2026-01-05 | 500.00 | 500.00 |
| #52 | INV-2026-D004 | invoice | 2026-01-05 | 500.00 | 500.00 |
| #53 | INV-2026-D005 | invoice | 2026-01-05 | 500.00 | 500.00 |
| #54 | INV-2026-D006 | invoice | 2026-01-05 | 500.00 | 500.00 |
| #55 | INV-2026-D007 | invoice | 2026-01-05 | 500.00 | 500.00 |
| #56 | INV-2026-D008 | invoice | 2026-01-05 | 500.00 | 500.00 |
| #57 | INV-2026-D009 | invoice | 2026-01-05 | 500.00 | 500.00 |
| #58 | INV-2026-D010 | invoice | 2026-01-05 | 500.00 | 500.00 |
| #59 | REC-AFC-2026-001 | payment | 2026-01-10 | 900.00 | 900.00 |
| #68 | BILL-2026-JAN-01 | bill | 2026-01-11 | 1,200.00 | 1,200.00 |
| #60 | REC-CABS-2026-002 | payment | 2026-01-12 | 400.00 | 400.00 |
| #61 | REC-CASH-2026-003 | payment | 2026-01-14 | 500.00 | 500.00 |
| #65 | CB-DIR-REV-001 | cashbook | 2026-01-15 | 200.00 | 200.00 |
| #66 | CB-DIR-EXP-001 | cashbook | 2026-01-16 | 85.00 | 85.00 |
| #62 | REC-ZB-2026-004 | payment | 2026-01-18 | 250.00 | 250.00 |
| #63 | REC-CABS-2026-005 | payment | 2026-01-20 | 500.00 | 500.00 |
| #64 | ADV-CABS-2026-005 | payment | 2026-01-20 | 100.00 | 100.00 |
| #70 | XFER-OUT-AFC-01 | cashbook | 2026-01-20 | 2,000.00 | 2,000.00 |
| #71 | XFER-IN-CABS-01 | cashbook | 2026-01-20 | 2,000.00 | 2,000.00 |
| #69 | EFT-AFC-PAY-001 | bill_payment | 2026-01-22 | 1,200.00 | 1,200.00 |
| #72 | DEP-ZB-UNIDENT-01 | cashbook | 2026-01-24 | 350.00 | 350.00 |
| #67 | CB-AFC-CHG-001 | cashbook | 2026-01-25 | 25.00 | 25.00 |
| #74 | ERRONEOUS-PAY-001 | cashbook | 2026-01-26 | 120.00 | 120.00 |
| #75 | REV-JB-2026-000039 | cashbook_reversal | 2026-01-27 | 120.00 | 120.00 |
| **Total** | | | | **38,850.00** | **38,850.00** |
**Net posting imbalance:** $0.00.

## 4. Closing trial balance (31 January 2026; posted GL only)
| Code | Account | Debit balance ($) | Credit balance ($) |
| :--- | :--- | ---: | ---: |
| 1201 | Student Fees Receivable | 10,700.00 | 0.00 |
| 1301 | CABS bank (CABS) | 8,350.00 | 0.00 |
| 1302 | ZB Bank (ZB Bank) | 600.00 | 0.00 |
| 1303 | CASH On Hand/ Kiosk | 915.00 | 0.00 |
| 1800 | Bank - AFC (rehearsal) | 7,675.00 | 0.00 |
| 2105 | Prepaid Student Fees / Advance Liability | 0.00 | 450.00 |
| 3100 | Accounts Payable / Trade Creditors | 0.00 | 450.00 |
| 4000 | Opening Balance Equity | 0.00 | 15,800.00 |
| 5100 | Tuition Fees Revenue | 0.00 | 6,000.00 |
| 5120 | Building Fund Levy Revenue | 0.00 | 2,000.00 |
| 5130 | Sports Levy Revenue | 0.00 | 1,000.00 |
| 5140 | ICT Levy Revenue | 0.00 | 1,000.00 |
| 5200 | Boarding Fees Revenue | 0.00 | 2,500.00 |
| 5810 | Rental Income (Sundry) | 0.00 | 350.00 |
| 6053 | Boarding Food and Catering Expense | 1,200.00 | 0.00 |
| 6404 | Vehicle Running Costs Expense | 85.00 | 0.00 |
| 7100 | Bank Charges and Interest Expense | 25.00 | 0.00 |
| **Total** | | **29,550.00** | **29,550.00** |
**Trial-balance difference:** $0.00.

## 5. Student debtor and supplier creditor reconciliations
| Control | GL ($) | Subledger ($) | Difference ($) |
| :--- | ---: | ---: | ---: |
| Student debtors (1201) | 10,700.00 | 10,700.00 | 0.00 |
| Supplier creditors (3100) | 450.00 | 450.00 | 0.00 |

## 6. Cashbook-to-GL bank reconciliation
| Account | GL | Opening ($) | Inflows ($) | Outflows ($) | Closing cashbook ($) | Closing GL ($) | Difference ($) |
| :--- | :---: | ---: | ---: | ---: | ---: | ---: | ---: |
| AFC Bank Account (1800) | 1800 | 10,000.00 | 900.00 | 3,225.00 | 7,675.00 | 7,675.00 | 0.00 |
| CABS Bank Account (1301) | 1301 | 5,000.00 | 3,350.00 | 0.00 | 8,350.00 | 8,350.00 | 0.00 |
| ZB Bank Account (1302) | 1302 | 0.00 | 600.00 | 0.00 | 600.00 | 600.00 | 0.00 |
| Cash Office (1303) | 1303 | 500.00 | 620.00 | 205.00 | 915.00 | 915.00 | 0.00 |
**Total closing GL cash and cash equivalents:** $17,540.00.
**Cashbook source rule:** `cashbook` is the book-of-record movement source. `cashbook_transactions` is a dual-synced reconciliation/matching feed and is not added to the cashbook totals. Reversed originals and their dated compensating entries are retained once each; both are within January in this rehearsal.

### Cashbook transaction trace
| Date | Account | Cashbook ID | Reference | Journal batch | Source | Type | Status | Amount ($) |
| :--- | :--- | ---: | :--- | :--- | :--- | :--- | :--- | ---: |
| 2026-01-10 | AFC Bank Account | #1 | REC-AFC-2026-001 | #59 JB-2026-000024 | payment | income | active | 900.00 |
| 2026-01-20 | AFC Bank Account | #11 | XFER-OUT-AFC-01 | #70 JB-2026-000035 | cashbook | expense | posted | 2,000.00 |
| 2026-01-22 | AFC Bank Account | #10 | EFT-AFC-PAY-001 | #69 JB-2026-000034 | bill_payment | expense | active | 1,200.00 |
| 2026-01-25 | AFC Bank Account | #9 | CB-AFC-CHG-001 | #67 JB-2026-000032 | cashbook | expense | posted | 25.00 |
| 2026-01-01 | CABS Bank Account | #14 | IDEMP-IMPORT-TEST-001 | #73 JB-2026-000038 | cashbook | income | posted | 150.00 |
| 2026-01-12 | CABS Bank Account | #2 | REC-CABS-2026-002 | #60 JB-2026-000025 | payment | income | active | 400.00 |
| 2026-01-15 | CABS Bank Account | #7 | CB-DIR-REV-001 | #65 JB-2026-000030 | cashbook | income | posted | 200.00 |
| 2026-01-20 | CABS Bank Account | #5 | REC-CABS-2026-005 | #63 JB-2026-000028 | payment | income | active | 500.00 |
| 2026-01-20 | CABS Bank Account | #6 | ADV-CABS-2026-005 | #64 JB-2026-000029 | payment | income | active | 100.00 |
| 2026-01-20 | CABS Bank Account | #12 | XFER-IN-CABS-01 | #71 JB-2026-000036 | cashbook | income | posted | 2,000.00 |
| 2026-01-18 | ZB Bank Account | #4 | REC-ZB-2026-004 | #62 JB-2026-000027 | payment | income | active | 250.00 |
| 2026-01-24 | ZB Bank Account | #13 | DEP-ZB-UNIDENT-01 | #72 JB-2026-000037 | cashbook | income | posted | 350.00 |
| 2026-01-14 | Cash Office | #3 | REC-CASH-2026-003 | #61 JB-2026-000026 | payment | income | active | 500.00 |
| 2026-01-16 | Cash Office | #8 | CB-DIR-EXP-001 | #66 JB-2026-000031 | cashbook | expense | posted | 85.00 |
| 2026-01-26 | Cash Office | #15 | ERRONEOUS-PAY-001 | #74 JB-2026-000039 | cashbook | expense | reversed | 120.00 |
| 2026-01-27 | Cash Office | #16 | REV-ERRONEOUS-PAY-001 | #75 JB-2026-000040 | cashbook_reversal | income | active | 120.00 |

## 7. Accounting integrity controls
- Student payment batches with revenue entries: **0** (expected 0; payments credit receivables).
- Supplier payment batches with expense entries: **0** (expected 0; bills recognize expense once, payments settle AP).
- Transfer batches with revenue/expense entries: **0** (expected 0); clearing-account net: **$0.00**.
- Reversal pairs not exactly offsetting their original journal: **0** (expected 0).
- Posted journal batches for duplicate import reference: **1** (expected 1).
- Bank-account GL mappings: AFC → 1800, CABS → 1301, ZB Bank → 1302, CASH → 1303.

## 8. Fee-vote revenue and collections
| Vote | GL account | Posted revenue ($) | Allocated receipts ($) |
| :--- | :---: | ---: | ---: |
| TUITION | 5100 | 6,000.00 | 1,600.00 |
| BOARDING_FEES | 5200 | 2,500.00 | 250.00 |
| BUILDING_FUND | 5120 | 2,000.00 | 400.00 |
| SPORTS | 5130 | 1,000.00 | 150.00 |
| PRACTICALS_ICT | 5140 | 1,000.00 | 150.00 |
| RENTAL | 5810 | 350.00 | 0.00 |

## 9. Debtors, creditors, and balance-sheet summary
- Accounts receivable difference: **$0.00**.
- Accounts payable difference: **$0.00**.
- January revenue: **$12,850.00**; expenditure: **$1,310.00**; net surplus: **$11,540.00**.
- Assets: **$28,240.00**; liabilities: **$900.00**; equity: **$15,800.00**.
- Balance-sheet equation difference: **$0.00**.

## 10. Automated validation and changed files
- `php artisan test tests\Feature\Finance\FinancialEngineScenariosTest.php`: **PASS — 12 tests, 87 assertions**.
- Full synthetic January 2026 cycle: **completed on the dedicated rehearsal school**; the report was regenerated from that completed dataset after correcting the Step 14 date and debit/credit presentation logic.
- Files changed for this reconciliation and release validation:
  - `app/Console/Commands/RunJanuary2026StagingMigrationRehearsal.php`
  - `app/Services/AccountingService.php`
  - `app/Services/Finance/FinancialReconciliationService.php`
  - `tests/Feature/Finance/FinancialEngineScenariosTest.php`
  - `docs/STAGING_MIGRATION_REHEARSAL_REPORT.md`
- Code and report changes remain local; no commit, GitHub push, or deployment was performed.
- Synthetic rehearsal records are present only under school `HHS-JAN26-REHEARSAL` (ID 3) in the isolated staging database. The two pre-existing staging schools were not targeted by cleanup.

**Final reconciliation status:** PASS — all balances and integrity controls reconcile.
**Release recommendation:** READY FOR GIT COMMIT REVIEW.
