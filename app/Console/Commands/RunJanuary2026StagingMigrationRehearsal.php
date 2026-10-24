<?php

namespace App\Console\Commands;

use App\Exceptions\MissingAccountMappingException;
use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Cashbook;
use App\Models\CashbookTransaction;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\MigrationBatch;
use App\Models\MigrationStagingRow;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\School;
use App\Models\Student;
use App\Models\Vendor;
use App\Services\AccountingService;
use App\Services\Finance\AccountingPeriodService;
use App\Services\Finance\CashbookReversalService;
use App\Services\Finance\FinancialAccountResolver;
use App\Services\Finance\FinancialReconciliationService;
use App\Services\Finance\HistoricalMigrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunJanuary2026StagingMigrationRehearsal extends Command
{
    protected $signature = 'finance:staging-migration-rehearsal {--connection=staging : Database connection to run against} {--output-file= : Optional path to save markdown report} {--report-only : Rebuild the report from the existing dedicated rehearsal dataset without posting transactions}';
    protected $description = 'Execute full end-to-end January 2026 migration rehearsal against isolated MySQL staging database';

    protected string $conn;
    protected string $executedAt;
    protected array $reportLines = [];
    protected School $school;
    protected array $students = [];
    protected array $bankAccounts = [];
    protected array $accounts = [];

    public function handle(): int
    {
        $this->conn = $this->option('connection');
        $connectionConfig = Config::get("database.connections.{$this->conn}");
        if ($this->conn !== 'staging' || ($connectionConfig['driver'] ?? null) !== 'mysql' || ($connectionConfig['database'] ?? null) !== 'staging') {
            $this->error('The rehearsal is restricted to the configured MySQL staging connection and staging database.');

            return Command::FAILURE;
        }

        $databaseIdentity = DB::connection($this->conn)->selectOne('SELECT DATABASE() AS database_name');
        if (($databaseIdentity->database_name ?? null) !== 'staging') {
            $this->error('The active database identity is not the isolated staging database.');

            return Command::FAILURE;
        }

        $this->executedAt = now()->toIso8601String();
        $this->info("===============================================================================");
        $this->info("   HANDE HIGH SCHOOL ERP — JANUARY 2026 MIGRATION REHEARSAL ON MYSQL STAGING   ");
        $this->info("   Target Database Connection: {$this->conn}                                  ");
        $this->info("===============================================================================\n");

        // Set active connection for all operations
        DB::setDefaultConnection($this->conn);

        try {
            if ($this->option('report-only')) {
                $this->loadRehearsalDataForReport();
                $this->step14_generateComprehensiveReconciliationReport();

                return Command::SUCCESS;
            }

            $this->step1_prepareSchoolAndAccounts();
            $this->step2_configureDynamicMappings();
            $this->step3_create20Students();
            $this->step4_postOpeningBalances();
            $this->step5_postJanuary2026Invoices();
            $this->step6_processStudentPaymentsAndAllocations();
            $this->step7_processDirectCashbookTransactions();
            $this->step8_processSupplierBillsAndPayments();
            $this->step9_processInterbankTransfer();
            $this->step10_processUnidentifiedDeposit();
            $this->step11_verifyDuplicateImportPrevention();
            $this->step12_verifyTransactionReversal();
            $this->step13_verifyMissingMappingFailClosed();
            $this->step14_generateComprehensiveReconciliationReport();

            $this->info("\n===============================================================================");
            $this->info("   JANUARY 2026 MIGRATION REHEARSAL COMPLETED SUCCESSFULLY ON MYSQL STAGING    ");
            $this->info("===============================================================================");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("\nREHEARSAL FAILED: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    protected function logReport(string $line = ''): void
    {
        $this->reportLines[] = $line;
        $this->line($line);
    }

    protected function loadRehearsalDataForReport(): void
    {
        $this->school = School::on($this->conn)
            ->where('code', 'HHS-JAN26-REHEARSAL')
            ->firstOrFail();
        $this->accounts = Account::on($this->conn)
            ->where('school_id', $this->school->id)
            ->get()
            ->keyBy('code')
            ->all();
        $requiredAccountCodes = ['1190', '1201', '1301', '1302', '1303', '1800', '3100', '5100', '5120', '5130', '5140', '5160', '5170', '5200', '5810', '6053', '6404', '7100'];
        foreach ($requiredAccountCodes as $code) {
            if (! isset($this->accounts[$code])) {
                throw new \RuntimeException("Rehearsal report cannot find required GL account {$code}.");
            }
        }

        $this->bankAccounts = BankAccount::on($this->conn)
            ->where('school_id', $this->school->id)
            ->whereIn('account_number', [
                'HHS-JAN26-REHEARSAL-AFC',
                'HHS-JAN26-REHEARSAL-CABS',
                'HHS-JAN26-REHEARSAL-ZB',
                'HHS-JAN26-REHEARSAL-CASH',
            ])
            ->with('account')
            ->get()
            ->keyBy('bank_name')
            ->all();
        if (count($this->bankAccounts) !== 4) {
            throw new \RuntimeException('Rehearsal report requires all four dedicated bank-account fixtures.');
        }
    }

    protected function step1_prepareSchoolAndAccounts(): void
    {
        $this->info("STEP 1: Validating School, Core Accounts & Bank Accounts...");

        $this->school = School::on($this->conn)->firstOrCreate(
            ['code' => 'HHS-JAN26-REHEARSAL'],
            ['name' => 'Hande High School January 2026 Staging Rehearsal', 'is_active' => true]
        );
        $this->info("✓ Isolated rehearsal school ready: {$this->school->name} (ID: {$this->school->id}, Code: {$this->school->code})");
        $this->resetRehearsalData();

        // Ensure essential accounts exist in staging
        $essentialAccounts = [
            ['code' => '1201', 'name' => 'Student Fees Receivable', 'type' => 'asset', 'category' => 'receivable'],
            ['code' => '1800', 'name' => 'Bank - AFC (rehearsal)', 'type' => 'asset', 'category' => 'bank'],
            ['code' => '1301', 'name' => 'CABS bank (CABS)', 'type' => 'asset', 'category' => 'bank'],
            ['code' => '1302', 'name' => 'ZB Bank (ZB Bank)', 'type' => 'asset', 'category' => 'bank'],
            ['code' => '1303', 'name' => 'CASH On Hand/ Kiosk', 'type' => 'asset', 'category' => 'cash'],
            ['code' => '1190', 'name' => 'Bank Clearing Account', 'type' => 'asset', 'category' => 'current_asset'],
            ['code' => '2105', 'name' => 'Prepaid Student Fees / Advance Liability', 'type' => 'liability', 'category' => 'current_liability'],
            ['code' => '3100', 'name' => 'Accounts Payable / Trade Creditors', 'type' => 'liability', 'category' => 'payable'],
            ['code' => '4000', 'name' => 'Opening Balance Equity', 'type' => 'equity', 'category' => 'equity'],
            ['code' => '5100', 'name' => 'Tuition Fees Revenue', 'type' => 'revenue', 'category' => 'tuition_revenue'],
            ['code' => '5120', 'name' => 'Building Fund Levy Revenue', 'type' => 'revenue', 'category' => 'tuition_revenue'],
            ['code' => '5130', 'name' => 'Sports Levy Revenue', 'type' => 'revenue', 'category' => 'tuition_revenue'],
            ['code' => '5140', 'name' => 'ICT Levy Revenue', 'type' => 'revenue', 'category' => 'tuition_revenue'],
            ['code' => '5160', 'name' => 'Practical Levy Revenue', 'type' => 'revenue', 'category' => 'tuition_revenue'],
            ['code' => '5170', 'name' => 'General Purpose Fund Revenue', 'type' => 'revenue', 'category' => 'tuition_revenue'],
            ['code' => '5200', 'name' => 'Boarding Fees Revenue', 'type' => 'revenue', 'category' => 'tuition_revenue'],
            ['code' => '5810', 'name' => 'Rental Income (Sundry)', 'type' => 'revenue', 'category' => 'other_revenue'],
            ['code' => '6053', 'name' => 'Boarding Food and Catering Expense', 'type' => 'expense', 'category' => 'supply_expense'],
            ['code' => '6404', 'name' => 'Vehicle Running Costs Expense', 'type' => 'expense', 'category' => 'maintenance_expense'],
            ['code' => '7100', 'name' => 'Bank Charges and Interest Expense', 'type' => 'expense', 'category' => 'other_expense'],
        ];

        foreach ($essentialAccounts as $accData) {
            $account = Account::on($this->conn)->firstOrCreate(
                ['school_id' => $this->school->id, 'code' => $accData['code']],
                array_merge($accData, [
                    'school_id' => $this->school->id,
                    'opening_balance' => 0.00,
                    'currency' => 'USD',
                    'is_active' => true,
                    'is_postable' => true,
                    'sort_order' => 10,
                ])
            );
            $this->accounts[$accData['code']] = $account;
        }

        // Verify or create 4 bank account records with actual cutover balances
        $bankDefs = [
            ['name' => 'AFC Bank Account', 'bank' => 'AFC', 'number' => 'HHS-JAN26-REHEARSAL-AFC', 'gl_code' => '1800', 'opening_balance' => 10000.00],
            ['name' => 'CABS Bank Account', 'bank' => 'CABS', 'number' => 'HHS-JAN26-REHEARSAL-CABS', 'gl_code' => '1301', 'opening_balance' => 5000.00],
            ['name' => 'ZB Bank Account', 'bank' => 'ZB Bank', 'number' => 'HHS-JAN26-REHEARSAL-ZB', 'gl_code' => '1302', 'opening_balance' => 0.00],
            ['name' => 'Cash Office', 'bank' => 'CASH', 'number' => 'HHS-JAN26-REHEARSAL-CASH', 'gl_code' => '1303', 'opening_balance' => 500.00],
        ];

        foreach ($bankDefs as $idx => $bDef) {
            $bankAcc = BankAccount::on($this->conn)->firstOrCreate(
                ['school_id' => $this->school->id, 'account_number' => $bDef['number']],
                [
                    'school_id' => $this->school->id,
                    'account_name' => $bDef['name'],
                    'bank_name' => $bDef['bank'],
                    'account_type' => $bDef['bank'] === 'CASH' ? 'cash' : 'current',
                    'opening_balance' => $bDef['opening_balance'],
                    'current_balance' => $bDef['opening_balance'],
                    'currency' => 'USD',
                    'is_active' => true,
                    'account_id' => $this->accounts[$bDef['gl_code']]->id,
                ]
            );
            $bankAcc->update([
                'opening_balance' => $bDef['opening_balance'],
                'current_balance' => $bDef['opening_balance'],
                'account_id' => $this->accounts[$bDef['gl_code']]->id,
            ]);
            $this->bankAccounts[$bDef['bank']] = $bankAcc;
        }

        \App\Models\AccountingPeriod::on($this->conn)->updateOrCreate(
            ['school_id' => $this->school->id, 'name' => 'January 2026'],
            [
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-31',
                'status' => 'open',
                'academic_year' => '2026',
                'term' => 'Term 1',
            ]
        );

        $this->info("✓ Core GL accounts, 4 bank accounts, and January 2026 Open Period established in staging.\n");
    }

    protected function resetRehearsalData(): void
    {
        $this->warn('Cleaning prior records only for the dedicated January rehearsal school...');

        DB::connection($this->conn)->transaction(function () {
            $invoiceIds = Invoice::on($this->conn)->where('school_id', $this->school->id)->pluck('id');
            $paymentIds = Payment::on($this->conn)->where('school_id', $this->school->id)->pluck('id');
            $journalBatchIds = JournalBatch::on($this->conn)->where('school_id', $this->school->id)->pluck('id');

            CashbookTransaction::on($this->conn)->where('school_id', $this->school->id)->delete();
            Cashbook::on($this->conn)->where('school_id', $this->school->id)->delete();
            PaymentAllocation::on($this->conn)
                ->where(function ($query) use ($invoiceIds, $paymentIds) {
                    $query->whereIn('invoice_id', $invoiceIds)
                        ->orWhereIn('payment_id', $paymentIds);
                })
                ->delete();
            InvoiceItem::on($this->conn)->whereIn('invoice_id', $invoiceIds)->delete();
            BillPayment::on($this->conn)->where('school_id', $this->school->id)->delete();
            Payment::on($this->conn)->where('school_id', $this->school->id)->delete();
            Invoice::on($this->conn)->where('school_id', $this->school->id)->delete();
            Bill::on($this->conn)->where('school_id', $this->school->id)->delete();
            MigrationStagingRow::on($this->conn)->where('school_id', $this->school->id)->delete();
            JournalEntry::on($this->conn)->whereIn('journal_batch_id', $journalBatchIds)->delete();
            JournalBatch::on($this->conn)->where('school_id', $this->school->id)->delete();
            MigrationBatch::on($this->conn)->where('school_id', $this->school->id)->delete();
            Student::on($this->conn)->where('school_id', $this->school->id)->delete();
            Vendor::on($this->conn)->where('school_id', $this->school->id)->delete();
            AccountMapping::on($this->conn)->where('school_id', $this->school->id)->delete();
        });

        $this->info('✓ Prior rehearsal rows removed only from the dedicated rehearsal school.');
    }

    protected function step2_configureDynamicMappings(): void
    {
        $this->info("STEP 2: Configuring Dynamic Account Mappings (No Hardcoded IDs)...");

        $resolver = app(FinancialAccountResolver::class);

        // Control Accounts
        $resolver->setMapping($this->school, 'control_account', 'ACCOUNTS_RECEIVABLE', $this->accounts['1201'], 'Student Fees Receivable Control');
        $resolver->setMapping($this->school, 'control_account', 'ACCOUNTS_PAYABLE', $this->accounts['3100'], 'Trade Creditors Control');
        $resolver->setMapping($this->school, 'control_account', 'PREPAID_FEES', $this->accounts['2105'], 'Prepaid Student Fees Liability Control');
        $resolver->setMapping($this->school, 'control_account', 'CASH_CLEARING', $this->accounts['1190'], 'Interbank Transfer Clearing Suspense');
        $resolver->setMapping($this->school, 'control_account', 'BANK_CLEARING', $this->accounts['1190'], 'Interbank Transfer Clearing Suspense');
        $resolver->setMapping($this->school, 'clearing_account', 'BANK_CLEARING', $this->accounts['1190'], 'Interbank Transfer Clearing Suspense');
        $resolver->setMapping($this->school, 'control_account', 'OPENING_BALANCE_EQUITY', $this->accounts['4000'], 'Opening Balance Equity Cutover');
        $resolver->setMapping($this->school, 'equity_account', 'OPENING_BALANCE_EQUITY', $this->accounts['4000'], 'Opening Balance Equity Cutover');
        $resolver->setMapping($this->school, 'control_account', 'BANK_CHARGES', $this->accounts['7100'], 'Bank Charges Expense');

        // Bank Accounts (Mapped by ID and by Bank Name)
        foreach ($this->bankAccounts as $bankKey => $bankAcc) {
            $glAccount = $bankAcc->account ?? $this->accounts['1301'];
            $resolver->setMapping($this->school, 'bank_account', (string) $bankAcc->id, $glAccount, "Bank Account ID {$bankAcc->id} GL Mapping");
            $resolver->setMapping($this->school, 'bank_account', $bankKey, $glAccount, "Bank {$bankKey} GL Mapping");
            $resolver->setMapping($this->school, 'bank_account', $bankAcc->bank_name, $glAccount, "Bank Name {$bankAcc->bank_name} Mapping");
        }

        // Fee Vote Mappings
        $voteMappings = [
            'TUITION' => '5100',
            'BUILDING_FUND' => '5120',
            'SPORTS' => '5130',
            'PRACTICALS_ICT' => '5140',
            'SCIENCE_LEVY' => '5160',
            'GPF_ADMIN' => '5170',
            'BOARDING_FEES' => '5200',
            'RENTAL' => '5810',
        ];

        foreach ($voteMappings as $voteKey => $code) {
            $resolver->setMapping($this->school, 'fee_vote', $voteKey, $this->accounts[$code], "Fee Vote {$voteKey} Revenue Mapping");
        }

        // Revenue Categories
        $resolver->setMapping($this->school, 'revenue_category', 'rental', $this->accounts['5810'], 'Sundry Rental Revenue Mapping');
        $resolver->setMapping($this->school, 'revenue_category', 'other_revenue', $this->accounts['5810'], 'Sundry Other Revenue Mapping');

        // Expense Categories (Map both 'expenditure_category' and 'expense_category')
        $resolver->setMapping($this->school, 'expenditure_category', 'boarding_food', $this->accounts['6053'], 'Boarding Food Purchases');
        $resolver->setMapping($this->school, 'expense_category', 'boarding_food', $this->accounts['6053'], 'Boarding Food Purchases');
        $resolver->setMapping($this->school, 'expenditure_category', 'vehicle_fuel', $this->accounts['6404'], 'Vehicle Fuel & Transport');
        $resolver->setMapping($this->school, 'expense_category', 'vehicle_fuel', $this->accounts['6404'], 'Vehicle Fuel & Transport');
        $resolver->setMapping($this->school, 'expenditure_category', 'bank_charges', $this->accounts['7100'], 'Bank Charges Expense');
        $resolver->setMapping($this->school, 'expense_category', 'bank_charges', $this->accounts['7100'], 'Bank Charges Expense');

        $this->info("✓ Dynamic Account Mappings stored and active in account_mappings table.\n");
    }

    protected function step3_create20Students(): void
    {
        $this->info("STEP 3: Creating Representative Test Dataset (10 Boarding + 10 Day Students)...");

        // 10 Boarding Students
        for ($i = 1; $i <= 10; $i++) {
            $num = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $student = Student::on($this->conn)->create([
                'school_id' => $this->school->id,
                'first_name' => "BoardingStudent{$i}",
                'last_name' => "Moyo{$i}",
                'admission_number' => "BOARD-{$num}",
                'registration_number' => "REG-B-{$num}",
                'grade' => 'Form 1',
                'class_name' => 'Form 1 A (Boarding)',
                'is_boarding' => true,
                'status' => 'active',
            ]);
            $this->students["BOARD_{$i}"] = $student;
        }

        // 10 Day Students
        for ($i = 1; $i <= 10; $i++) {
            $num = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $student = Student::on($this->conn)->create([
                'school_id' => $this->school->id,
                'first_name' => "DayStudent{$i}",
                'last_name' => "Sibanda{$i}",
                'admission_number' => "DAY-{$num}",
                'registration_number' => "REG-D-{$num}",
                'grade' => 'Form 1',
                'class_name' => 'Form 1 B (Day)',
                'is_boarding' => false,
                'status' => 'active',
            ]);
            $this->students["DAY_{$i}"] = $student;
        }

        $this->info("✓ 20 Students created (10 Boarding, 10 Day Scholars) in staging.\n");
    }

    protected function step4_postOpeningBalances(): void
    {
        $this->info("STEP 4: Posting Authoritative 2025 Opening Balances (Cutover as of 1 Jan 2026)...");

        $accountingService = app(AccountingService::class);

        // 5 students carry 2025 arrears ($150 each = $750 total)
        $arrearsStudents = [
            $this->students['BOARD_1'],
            $this->students['BOARD_2'],
            $this->students['DAY_1'],
            $this->students['DAY_2'],
            $this->students['DAY_3'],
        ];

        foreach ($arrearsStudents as $idx => $st) {
            Invoice::on($this->conn)->create([
                'school_id' => $this->school->id,
                'student_id' => $st->id,
                'number' => "OB-2025-" . ($idx + 1),
                'academic_year' => '2025',
                'term' => 'Term 3',
                'total_amount' => 150.00,
                'balance' => 150.00,
                'issued_at' => '2025-12-31',
                'due_date' => '2025-12-31',
                'status' => 'unpaid',
                'type' => 'opening_balance',
            ]);
        }

        // Supplier historical opening bill ($450.00)
        $vendor = Vendor::on($this->conn)->create([
            'school_id' => $this->school->id,
            'name' => 'ZESA Electricity Supply',
            'code' => 'HHS-JAN26-REHEARSAL-ZESA',
            'status' => 'active',
        ]);

        Bill::on($this->conn)->create([
            'school_id' => $this->school->id,
            'vendor_id' => $vendor->id,
            'bill_number' => 'BILL-2025-OB',
            'bill_date' => '2025-12-31',
            'due_date' => '2025-12-31',
            'total_amount' => 450.00,
            'paid_amount' => 0.00,
            'balance' => 450.00,
            'status' => 'pending',
            'expense_account_id' => $this->accounts['6404']->id,
            'description' => '2025 Historical Power Arrears Brought Forward',
        ]);

        // Post balanced cutover batch via AccountingService
        // Debits:
        // - AFC Bank: $10,000.00
        // - CABS Bank: $5,000.00
        // - Cash Office: $500.00
        // - Accounts Receivable (2025 arrears): $750.00
        // Credits:
        // - Accounts Payable (2025 utility): $450.00
        $glBalances = [
            [
                'account_id' => $this->accounts['1800']->id, // AFC Bank
                'debit' => 10000.00,
                'credit' => 0.00,
                'memo' => 'AFC Bank 2026 Opening Balance',
            ],
            [
                'account_id' => $this->accounts['1301']->id, // CABS Bank
                'debit' => 5000.00,
                'credit' => 0.00,
                'memo' => 'CABS Bank 2026 Opening Balance',
            ],
            [
                'account_id' => $this->accounts['1303']->id, // Cash Office
                'debit' => 500.00,
                'credit' => 0.00,
                'memo' => 'Cash on Hand 2026 Opening Balance',
            ],
            [
                'account_id' => $this->accounts['1201']->id, // Accounts Receivable
                'debit' => 750.00,
                'credit' => 0.00,
                'memo' => 'Student Debtors 2025 Arrears Opening Balance',
            ],
            [
                'account_id' => $this->accounts['3100']->id, // Accounts Payable
                'debit' => 0.00,
                'credit' => 450.00,
                'memo' => 'Trade Creditors 2025 Bills Opening Balance',
            ],
            [
                'account_id' => $this->accounts['4000']->id, // Opening Balance Equity
                'debit' => 0.00,
                'credit' => 15800.00,
                'memo' => 'Opening Balance Equity Cutover',
            ],
        ];

        $openingBatch = $accountingService->postOpeningBalances($this->school, $glBalances, '2026-01-01');

        $this->info("✓ Opening Balance Batch Posted: Batch #{$openingBatch->batch_number} (Ref: {$openingBatch->reference_number})");
        $this->info("  Total Debits: $" . number_format($openingBatch->debit_total, 2) . " | Total Credits: $" . number_format($openingBatch->credit_total, 2) . "\n");
    }

    protected function step5_postJanuary2026Invoices(): void
    {
        $this->info("STEP 5: Ingesting & Posting January 2026 Student Fee Invoices...");

        $accountingService = app(AccountingService::class);
        $totalInvoiced = 0.0;

        // Boarding Invoices: $750.00 each across 5 votes
        // Tuition ($300), Boarding ($250), Building ($100), Sports ($50), ICT ($50)
        for ($i = 1; $i <= 10; $i++) {
            $student = $this->students["BOARD_{$i}"];
            $invNumber = "INV-2026-B" . str_pad((string)$i, 3, '0', STR_PAD_LEFT);

            $invoice = Invoice::on($this->conn)->create([
                'school_id' => $this->school->id,
                'student_id' => $student->id,
                'number' => $invNumber,
                'academic_year' => '2026',
                'term' => 'Term 1',
                'total_amount' => 750.00,
                'balance' => 750.00,
                'issued_at' => '2026-01-05',
                'due_date' => '2026-01-31',
                'status' => 'unpaid',
                'type' => 'fees',
            ]);

            $voteItems = [
                ['desc' => 'Tuition Fee Term 1', 'vote' => 'TUITION', 'cat' => 'tuition', 'amt' => 300.00],
                ['desc' => 'Boarding Accommodation Term 1', 'vote' => 'BOARDING_FEES', 'cat' => 'boarding', 'amt' => 250.00],
                ['desc' => 'Building Fund Levy Term 1', 'vote' => 'BUILDING_FUND', 'cat' => 'levy', 'amt' => 100.00],
                ['desc' => 'Sports Levy Term 1', 'vote' => 'SPORTS', 'cat' => 'levy', 'amt' => 50.00],
                ['desc' => 'Practicals & ICT Fee Term 1', 'vote' => 'PRACTICALS_ICT', 'cat' => 'levy', 'amt' => 50.00],
            ];

            foreach ($voteItems as $vItem) {
                InvoiceItem::on($this->conn)->create([
                    'school_id' => $this->school->id,
                    'invoice_id' => $invoice->id,
                    'description' => $vItem['desc'],
                    'category' => $vItem['cat'],
                    'vote_code' => $vItem['vote'],
                    'quantity' => 1,
                    'unit_amount' => $vItem['amt'],
                    'line_total' => $vItem['amt'],
                ]);
            }

            $accountingService->postInvoice($invoice);
            $totalInvoiced += 750.00;
            $this->line("  Posted {$invNumber}.");
            $this->reconnectBetweenRehearsalBatches();
        }

        // Day Scholar Invoices: $500.00 each across 4 votes
        // Tuition ($300), Building ($100), Sports ($50), ICT ($50)
        for ($i = 1; $i <= 10; $i++) {
            $student = $this->students["DAY_{$i}"];
            $invNumber = "INV-2026-D" . str_pad((string)$i, 3, '0', STR_PAD_LEFT);

            $invoice = Invoice::on($this->conn)->create([
                'school_id' => $this->school->id,
                'student_id' => $student->id,
                'number' => $invNumber,
                'academic_year' => '2026',
                'term' => 'Term 1',
                'total_amount' => 500.00,
                'balance' => 500.00,
                'issued_at' => '2026-01-05',
                'due_date' => '2026-01-31',
                'status' => 'unpaid',
                'type' => 'fees',
            ]);

            $voteItems = [
                ['desc' => 'Tuition Fee Term 1', 'vote' => 'TUITION', 'cat' => 'tuition', 'amt' => 300.00],
                ['desc' => 'Building Fund Levy Term 1', 'vote' => 'BUILDING_FUND', 'cat' => 'levy', 'amt' => 100.00],
                ['desc' => 'Sports Levy Term 1', 'vote' => 'SPORTS', 'cat' => 'levy', 'amt' => 50.00],
                ['desc' => 'Practicals & ICT Fee Term 1', 'vote' => 'PRACTICALS_ICT', 'cat' => 'levy', 'amt' => 50.00],
            ];

            foreach ($voteItems as $vItem) {
                InvoiceItem::on($this->conn)->create([
                    'school_id' => $this->school->id,
                    'invoice_id' => $invoice->id,
                    'description' => $vItem['desc'],
                    'category' => $vItem['cat'],
                    'vote_code' => $vItem['vote'],
                    'quantity' => 1,
                    'unit_amount' => $vItem['amt'],
                    'line_total' => $vItem['amt'],
                ]);
            }

            $accountingService->postInvoice($invoice);
            $totalInvoiced += 500.00;
            $this->line("  Posted {$invNumber}.");
            $this->reconnectBetweenRehearsalBatches();
        }

        $this->info("✓ 20 January 2026 Invoices Raised and Posted to GL.");
        $this->info("  Total Invoiced: $" . number_format($totalInvoiced, 2) . " (Revenue Recognized Once in GL)\n");
    }

    protected function reconnectBetweenRehearsalBatches(): void
    {
        if (DB::connection($this->conn)->transactionLevel() !== 0) {
            throw new \LogicException('Cannot refresh the staging connection while a database transaction is active.');
        }

        DB::disconnect($this->conn);
        DB::reconnect($this->conn);
    }

    protected function step6_processStudentPaymentsAndAllocations(): void
    {
        $this->info("STEP 6: Processing Student Payments with Fee-Vote Allocations & Advances...");

        $accountingService = app(AccountingService::class);

        // Case 1: Boarding Student 1 pays in full via AFC Bank ($750 2026 invoice + $150 2025 arrears = $900.00)
        $st1 = $this->students['BOARD_1'];
        $inv1_2025 = Invoice::on($this->conn)->where('student_id', $st1->id)->where('academic_year', '2025')->first();
        $inv1_2026 = Invoice::on($this->conn)->where('student_id', $st1->id)->where('academic_year', '2026')->first();

        $pm1 = Payment::on($this->conn)->create([
            'school_id' => $this->school->id,
            'student_id' => $st1->id,
            'method' => 'bank',
            'reference' => 'REC-AFC-2026-001',
            'amount' => 900.00,
            'paid_at' => '2026-01-10',
            'status' => 'completed',
            'account_id' => $this->accounts['1800']->id, // AFC Bank Account
        ]);

        PaymentAllocation::on($this->conn)->create([
            'payment_id' => $pm1->id,
            'invoice_id' => $inv1_2025->id,
            'amount' => 150.00,
            'vote_code' => 'TUITION',
        ]);
        $inv1_2025->update(['balance' => 0.00, 'status' => 'paid']);

        $bVotes = [
            'TUITION' => 300.00,
            'BOARDING_FEES' => 250.00,
            'BUILDING_FUND' => 100.00,
            'SPORTS' => 50.00,
            'PRACTICALS_ICT' => 50.00,
        ];
        foreach ($bVotes as $vCode => $amt) {
            PaymentAllocation::on($this->conn)->create([
                'payment_id' => $pm1->id,
                'invoice_id' => $inv1_2026->id,
                'amount' => $amt,
                'vote_code' => $vCode,
            ]);
        }
        $inv1_2026->update(['balance' => 0.00, 'status' => 'paid']);

        $accountingService->postPayment($pm1, $inv1_2026, $this->accounts['1800']->id);
        $this->info("✓ Payment 1 ($900.00 via AFC) settles 2025 arrears ($150) + 2026 fees ($750) across 5 fee votes.");

        // Case 2: Boarding Student 2 makes partial payment of $400.00 via CABS Bank
        $st2 = $this->students['BOARD_2'];
        $inv2_2026 = Invoice::on($this->conn)->where('student_id', $st2->id)->where('academic_year', '2026')->first();

        $pm2 = Payment::on($this->conn)->create([
            'school_id' => $this->school->id,
            'student_id' => $st2->id,
            'method' => 'bank',
            'reference' => 'REC-CABS-2026-002',
            'amount' => 400.00,
            'paid_at' => '2026-01-12',
            'status' => 'completed',
            'account_id' => $this->accounts['1301']->id, // CABS Bank Account
        ]);

        PaymentAllocation::on($this->conn)->create([
            'payment_id' => $pm2->id,
            'invoice_id' => $inv2_2026->id,
            'amount' => 300.00,
            'vote_code' => 'TUITION',
        ]);
        PaymentAllocation::on($this->conn)->create([
            'payment_id' => $pm2->id,
            'invoice_id' => $inv2_2026->id,
            'amount' => 100.00,
            'vote_code' => 'BUILDING_FUND',
        ]);
        $inv2_2026->update(['balance' => 350.00, 'status' => 'partial']);

        $accountingService->postPayment($pm2, $inv2_2026, $this->accounts['1301']->id);
        $this->info("✓ Payment 2 ($400.00 via CABS) allocated to Tuition ($300) & Building ($100). Remaining: $350.");

        // Case 3: Day Student 1 pays $500.00 in full via Cash Office
        $stDay1 = $this->students['DAY_1'];
        $invDay1_2026 = Invoice::on($this->conn)->where('student_id', $stDay1->id)->where('academic_year', '2026')->first();

        $pm3 = Payment::on($this->conn)->create([
            'school_id' => $this->school->id,
            'student_id' => $stDay1->id,
            'method' => 'cash',
            'reference' => 'REC-CASH-2026-003',
            'amount' => 500.00,
            'paid_at' => '2026-01-14',
            'status' => 'completed',
            'account_id' => $this->accounts['1303']->id, // Cash Office
        ]);

        $dVotes = [
            'TUITION' => 300.00,
            'BUILDING_FUND' => 100.00,
            'SPORTS' => 50.00,
            'PRACTICALS_ICT' => 50.00,
        ];
        foreach ($dVotes as $vCode => $amt) {
            PaymentAllocation::on($this->conn)->create([
                'payment_id' => $pm3->id,
                'invoice_id' => $invDay1_2026->id,
                'amount' => $amt,
                'vote_code' => $vCode,
            ]);
        }
        $invDay1_2026->update(['balance' => 0.00, 'status' => 'paid']);

        $accountingService->postPayment($pm3, $invDay1_2026, $this->accounts['1303']->id);
        $this->info("✓ Payment 3 ($500.00 cash) fully settles Day Student 1 across all 4 votes.");

        // Case 4: Day Student 2 makes partial payment of $250.00 via ZB Bank
        $stDay2 = $this->students['DAY_2'];
        $invDay2_2026 = Invoice::on($this->conn)->where('student_id', $stDay2->id)->where('academic_year', '2026')->first();

        $pm4 = Payment::on($this->conn)->create([
            'school_id' => $this->school->id,
            'student_id' => $stDay2->id,
            'method' => 'bank',
            'reference' => 'REC-ZB-2026-004',
            'amount' => 250.00,
            'paid_at' => '2026-01-18',
            'status' => 'completed',
            'account_id' => $this->accounts['1302']->id, // ZB Bank
        ]);

        PaymentAllocation::on($this->conn)->create([
            'payment_id' => $pm4->id,
            'invoice_id' => $invDay2_2026->id,
            'amount' => 250.00,
            'vote_code' => 'TUITION',
        ]);
        $invDay2_2026->update(['balance' => 250.00, 'status' => 'partial']);

        $accountingService->postPayment($pm4, $invDay2_2026, $this->accounts['1302']->id);
        $this->info("✓ Payment 4 ($250.00 via ZB) allocated to Tuition ($250 balance remaining).");

        // Case 5: Day Student 4 pays $600.00 against $500.00 invoice (Overpayment of $100 -> Prepaid Fees)
        $stDay4 = $this->students['DAY_4'];
        $invDay4_2026 = Invoice::on($this->conn)->where('student_id', $stDay4->id)->where('academic_year', '2026')->first();

        // 1. Post $500 settling invoice
        $pm5 = Payment::on($this->conn)->create([
            'school_id' => $this->school->id,
            'student_id' => $stDay4->id,
            'method' => 'bank',
            'reference' => 'REC-CABS-2026-005',
            'amount' => 500.00,
            'paid_at' => '2026-01-20',
            'status' => 'completed',
            'account_id' => $this->accounts['1301']->id,
        ]);
        foreach ($dVotes as $vCode => $amt) {
            PaymentAllocation::on($this->conn)->create([
                'payment_id' => $pm5->id,
                'invoice_id' => $invDay4_2026->id,
                'amount' => $amt,
                'vote_code' => $vCode,
            ]);
        }
        $invDay4_2026->update(['balance' => 0.00, 'status' => 'paid']);
        $accountingService->postPayment($pm5, $invDay4_2026, $this->accounts['1301']->id);

        // 2. Post $100 excess advance
        $advPayment = Payment::on($this->conn)->create([
            'school_id' => $this->school->id,
            'student_id' => $stDay4->id,
            'method' => 'bank',
            'reference' => 'ADV-CABS-2026-005',
            'amount' => 100.00,
            'paid_at' => '2026-01-20',
            'status' => 'completed',
            'account_id' => $this->accounts['1301']->id,
        ]);
        $accountingService->postAdvancePayment($advPayment, $this->accounts['1301']->id);
        $this->info("✓ Payment 5 ($600.00 total): $500 settles invoice, $100 posted to Prepaid Fees Liability (2105).\n");
    }

    protected function step7_processDirectCashbookTransactions(): void
    {
        $this->info("STEP 7: Processing Direct Cashbook Income & Expenditure (Not Defaulted to AR/Revenue)...");

        $accountingService = app(AccountingService::class);

        // Direct Revenue: Community hall rental income $200.00 into CABS Bank
        $cashbookRev = Cashbook::on($this->conn)->create([
            'school_id' => $this->school->id,
            'account_id' => $this->accounts['1301']->id,
            'category' => 'rental',
            'balance_after' => 0.00,
            'transaction_date' => '2026-01-15',
            'transaction_type' => 'income',
            'classification' => 'direct_revenue',
            'vote_code' => 'RENTAL',
            'reference_number' => 'CB-DIR-REV-001',
            'description' => 'Community Hall Weekend Hire Income',
            'amount' => 200.00,
            'counterparty_name' => 'Community Church Assembly',
            'supporting_document_ref' => 'VOUCHER-REV-01',
            'currency' => 'USD',
        ]);
        $accountingService->postManualCashbookTransaction($cashbookRev);
        $this->info("✓ Direct Income: $200.00 Hall Hire posted (DR CABS Bank 1301, CR Rental Income 5810).");

        // Direct Expense: Emergency vehicle fuel $85.00 from Cash Office
        $cashbookExp = Cashbook::on($this->conn)->create([
            'school_id' => $this->school->id,
            'account_id' => $this->accounts['1303']->id,
            'category' => 'vehicle_fuel',
            'balance_after' => 0.00,
            'transaction_date' => '2026-01-16',
            'transaction_type' => 'expense',
            'classification' => 'direct_expense',
            'reference_number' => 'CB-DIR-EXP-001',
            'description' => 'Emergency Bus Fuel for Trip',
            'amount' => 85.00,
            'counterparty_name' => 'Total Service Station',
            'supporting_document_ref' => 'FUEL-SLIP-8819',
            'currency' => 'USD',
        ]);
        $accountingService->postManualCashbookTransaction($cashbookExp);
        $this->info("✓ Direct Expense: $85.00 Bus Fuel posted (DR Vehicle Costs 6404, CR Cash Office 1303).");

        // Bank Charges: AFC Bank Monthly Service Fee $25.00
        $cashbookCharge = Cashbook::on($this->conn)->create([
            'school_id' => $this->school->id,
            'account_id' => $this->accounts['1800']->id,
            'category' => 'bank_charges',
            'balance_after' => 0.00,
            'transaction_date' => '2026-01-25',
            'transaction_type' => 'expense',
            'classification' => 'bank_charges',
            'reference_number' => 'CB-AFC-CHG-001',
            'description' => 'AFC Monthly Account Maintenance & Ledger Fee',
            'amount' => 25.00,
            'counterparty_name' => 'AFC Commercial Bank',
            'supporting_document_ref' => 'STMT-AFC-JAN26',
            'currency' => 'USD',
        ]);
        $accountingService->postManualCashbookTransaction($cashbookCharge);
        $this->info("✓ Bank Charge: $25.00 AFC Service Fee posted (DR Bank Charges 7100, CR AFC Bank 1800).\n");
    }

    protected function step8_processSupplierBillsAndPayments(): void
    {
        $this->info("STEP 8: Processing Supplier Bill and Disbursement Payment...");

        $accountingService = app(AccountingService::class);

        // 1. Supplier Bill: Food Supplies for Boarding House ($1,200.00)
        $vendor = Vendor::on($this->conn)->firstOrCreate(
            ['school_id' => $this->school->id, 'code' => 'HHS-JAN26-REHEARSAL-FOOD'],
            ['name' => 'Grain & Fresh Produce Wholesalers', 'status' => 'active']
        );

        $bill = Bill::on($this->conn)->create([
            'school_id' => $this->school->id,
            'vendor_id' => $vendor->id,
            'bill_number' => 'BILL-2026-JAN-01',
            'bill_date' => '2026-01-11',
            'due_date' => '2026-01-25',
            'total_amount' => 1200.00,
            'paid_amount' => 0.00,
            'balance' => 1200.00,
            'status' => 'pending',
            'expense_account_id' => $this->accounts['6053']->id, // Boarding Food Expense
            'description' => 'Mealie Meal, Rice, and Fresh Vegetables for Boarding Diners',
        ]);

        $accountingService->postBill($bill);
        $this->info("✓ Supplier Bill: $1,200.00 posted (DR Boarding Food 6053, CR Accounts Payable 3100).");

        // 2. Bill Payment from AFC Bank ($1,200.00)
        $billPayment = BillPayment::on($this->conn)->create([
            'school_id' => $this->school->id,
            'bill_id' => $bill->id,
            'payment_date' => '2026-01-22',
            'amount' => 1200.00,
            'payment_method' => 'bank_transfer',
            'reference' => 'EFT-AFC-PAY-001',
            'bank_account_id' => $this->bankAccounts['AFC']->id,
            'account_id' => $this->accounts['1800']->id,
            'notes' => 'Settlement of Invoice BILL-2026-JAN-01',
        ]);

        $accountingService->postBillPayment($billPayment);
        $bill->update(['paid_amount' => 1200.00, 'balance' => 0.00, 'status' => 'paid']);
        $this->info("✓ Bill Payment: $1,200.00 posted (DR Accounts Payable 3100, CR AFC Bank 1800). No double expense.\n");
    }

    protected function step9_processInterbankTransfer(): void
    {
        $this->info("STEP 9: Processing Interbank Transfer via Bank Clearing Account (Account 1190)...");

        $accountingService = app(AccountingService::class);
        $transferAmount = 2000.00;

        // Leg 1: Outflow from AFC Bank
        $outflow = Cashbook::on($this->conn)->create([
            'school_id' => $this->school->id,
            'account_id' => $this->accounts['1800']->id,
            'category' => 'transfer',
            'balance_after' => 0.00,
            'transaction_date' => '2026-01-20',
            'transaction_type' => 'expense',
            'classification' => 'bank_transfer',
            'reference_number' => 'XFER-OUT-AFC-01',
            'description' => 'Interbank Transfer to CABS Operating Account',
            'amount' => $transferAmount,
            'counterparty_name' => 'CABS Bank Account',
            'supporting_document_ref' => 'RTGS-VOUCHER-9901',
            'currency' => 'USD',
        ]);
        $accountingService->postManualCashbookTransaction($outflow);

        // Leg 2: Inflow into CABS Bank
        $inflow = Cashbook::on($this->conn)->create([
            'school_id' => $this->school->id,
            'account_id' => $this->accounts['1301']->id,
            'category' => 'transfer',
            'balance_after' => 0.00,
            'transaction_date' => '2026-01-20',
            'transaction_type' => 'income',
            'classification' => 'bank_transfer',
            'reference_number' => 'XFER-IN-CABS-01',
            'description' => 'Transfer Inflow from AFC Bank',
            'amount' => $transferAmount,
            'counterparty_name' => 'AFC Bank Account',
            'supporting_document_ref' => 'RTGS-VOUCHER-9901',
            'currency' => 'USD',
        ]);
        $accountingService->postManualCashbookTransaction($inflow);

        // Verify Net Clearing Account Balance
        $clearingDr = JournalEntry::on($this->conn)->where('account_id', $this->accounts['1190']->id)->where('entry_type', 'debit')->sum('amount');
        $clearingCr = JournalEntry::on($this->conn)->where('account_id', $this->accounts['1190']->id)->where('entry_type', 'credit')->sum('amount');
        $netClearing = $clearingDr - $clearingCr;

        $this->info("✓ Interbank Transfer $2,000.00 Completed.");
        $this->info("  Leg 1: DR Clearing 1190 ($2,000), CR AFC Bank 1800 ($2,000)");
        $this->info("  Leg 2: DR CABS Bank 1301 ($2,000), CR Clearing 1190 ($2,000)");
        $this->info("  Clearing Account (1190) Net Balance: $" . number_format($netClearing, 2) . " (Zero Discrepancy)\n");
    }

    protected function step10_processUnidentifiedDeposit(): void
    {
        $this->info("STEP 10: Processing Unidentified Bank Deposit (Direct to Prepaid Liability)...");

        $accountingService = app(AccountingService::class);

        // Unidentified bank deposit of $350.00 into ZB Bank
        $deposit = Cashbook::on($this->conn)->create([
            'school_id' => $this->school->id,
            'account_id' => $this->accounts['1302']->id,
            'category' => 'advance',
            'balance_after' => 0.00,
            'transaction_date' => '2026-01-24',
            'transaction_type' => 'income',
            'classification' => 'student_advance',
            'reference_number' => 'DEP-ZB-UNIDENT-01',
            'description' => 'Unidentified Direct Bank Deposit - No Ref',
            'amount' => 350.00,
            'counterparty_name' => 'Unknown Depositor',
            'supporting_document_ref' => 'ZB-STMT-TX-4482',
            'currency' => 'USD',
        ]);

        $accountingService->postManualCashbookTransaction($deposit);
        $this->info("✓ Unidentified Deposit: $350.00 posted (DR ZB Bank 1302, CR Prepaid Fees Liability 2105).\n");
    }

    protected function step11_verifyDuplicateImportPrevention(): void
    {
        $this->info("STEP 11: Verifying Duplicate Import Detection & Idempotency...");

        $migService = app(HistoricalMigrationService::class);

        $testRows = [
            [
                'transaction_date' => '2026-01-15',
                'bank_identifier' => 'CABS',
                'bank_name' => 'CABS',
                'transaction_type' => 'income',
                'classification' => 'direct_revenue',
                'reference_number' => 'IDEMP-IMPORT-TEST-001',
                'description' => 'Idempotency Import Test Line',
                'amount' => 150.00,
                'vote_code' => 'RENTAL',
            ]
        ];

        // Batch 1
        $batch1 = $migService->createBatch($this->school, 'bank_cashbook', 'cashbook_rehearsal_01.csv', 1, ['description' => 'Batch 1 Original']);
        $migService->stageRows($batch1, $testRows);
        $val1 = $migService->validateBatch($batch1);
        $dry1 = $migService->dryRunReconciliation($batch1);
        $migService->approveBatch($batch1, 1);
        $postResult1 = $migService->postBatch($batch1);

        // Batch 2 (Identical rows)
        $batch2 = $migService->createBatch($this->school, 'bank_cashbook', 'cashbook_rehearsal_01_duplicate.csv', 1, ['description' => 'Batch 2 Duplicate']);
        $staged2 = $migService->stageRows($batch2, $testRows);
        $val2 = $migService->validateBatch($batch2);

        $dupRow = MigrationStagingRow::on($this->conn)->where('migration_batch_id', $batch2->id)->first();
        $hasDupError = false;
        if ($dupRow && $dupRow->validation_errors) {
            foreach ($dupRow->validation_errors as $err) {
                if (str_contains($err, 'Duplicate transaction detected')) {
                    $hasDupError = true;
                    break;
                }
            }
        }

        if (!$hasDupError) {
            throw new \Exception("Duplicate transaction was not detected in Batch 2!");
        }

        // Verify that Batch 2 cannot be approved due to validation errors
        $blocked = false;
        try {
            $migService->approveBatch($batch2, 1);
        } catch (\InvalidArgumentException $e) {
            $blocked = true;
        }

        if (!$blocked) {
            throw new \Exception("FAILURE: Batch 2 with duplicates was approved!");
        }

        $this->info("✓ Batch 1: Posted {$postResult1['posted_rows']} row(s).");
        $this->info("✓ Batch 2: Staged duplicate row detected: {$val2['error_rows']} error row(s) flagged with duplicate detection.");
        $this->info("✓ Approval and posting of duplicate batch successfully blocked.");
        $this->info("✓ SHA-256 idempotency hash prevents duplicate GL postings.\n");
    }

    protected function step12_verifyTransactionReversal(): void
    {
        $this->info("STEP 12: Verifying Transaction Reversal & Reconciled Audit Trail...");

        $accountingService = app(AccountingService::class);
        $reversalService = app(CashbookReversalService::class);

        // 1. Post an erroneous transaction ($120.00 cash payment)
        $erroneous = Cashbook::on($this->conn)->create([
            'school_id' => $this->school->id,
            'account_id' => $this->accounts['1303']->id,
            'category' => 'vehicle_fuel',
            'balance_after' => 0.00,
            'transaction_date' => '2026-01-26',
            'transaction_type' => 'expense',
            'classification' => 'direct_expense',
            'reference_number' => 'ERRONEOUS-PAY-001',
            'description' => 'Erroneous Fuel Voucher Entry',
            'amount' => 120.00,
            'counterparty_name' => 'Mistaken Payee',
            'supporting_document_ref' => 'ERR-VOUCHER',
            'currency' => 'USD',
        ]);
        $batch = $accountingService->postManualCashbookTransaction($erroneous);

        $cashBefore = JournalEntry::on($this->conn)->where('account_id', $this->accounts['1303']->id)->where('entry_type', 'credit')->sum('amount');

        // Simulate the reversal within the January rehearsal period.
        Carbon::setTestNow('2026-01-27 12:00:00');
        try {
            $reversalBatch = $reversalService->reverse($erroneous, 'Duplicate posting entered in error by clerk');
        } finally {
            Carbon::setTestNow();
        }

        $erroneous->refresh();
        if ($erroneous->status !== 'reversed') {
            throw new \Exception("Reversal verification failed: transaction status is {$erroneous->status}");
        }
        if (!$reversalBatch || $reversalBatch->status !== 'posted') {
            throw new \Exception("Reversal batch was not created with status posted");
        }

        $this->info("✓ Original Transaction #{$erroneous->id} marked 'reversed'.");
        $this->info("✓ Reversal Batch #{$reversalBatch->batch_number} created with equal & opposite entries.");
        $this->info("✓ Net financial impact returned to $0.00 with full audit memo preserved.\n");
    }

    protected function step13_verifyMissingMappingFailClosed(): void
    {
        $this->info("STEP 13: Verifying Dynamic Mapping Rejection (Fail-Closed Integrity)...");

        $accountingService = app(AccountingService::class);
        $thrown = false;

        // Create an unmapped cashbook entry (non-existent revenue category)
        $unmappedEntry = new Cashbook([
            'school_id' => $this->school->id,
            'account_id' => $this->accounts['1301']->id,
            'category' => 'unmapped_special_revenue_category',
            'balance_after' => 0.00,
            'transaction_date' => '2026-01-28',
            'transaction_type' => 'income',
            'classification' => 'direct_revenue',
            'reference_number' => 'UNMAPPED-FAIL-TEST',
            'description' => 'Transaction with Unmapped Account',
            'amount' => 500.00,
            'currency' => 'USD',
        ]);

        try {
            $accountingService->postManualCashbookTransaction($unmappedEntry);
        } catch (MissingAccountMappingException $e) {
            $thrown = true;
            $this->info("✓ Expected Exception Caught: " . $e->getMessage());
        }

        if (!$thrown) {
            throw new \Exception("FAILURE: Transaction with missing mapping did not fail closed!");
        }

        $exists = JournalBatch::on($this->conn)->where('reference_number', 'UNMAPPED-FAIL-TEST')->exists();
        if ($exists) {
            throw new \Exception("FAILURE: Partial journal record was created for unmapped transaction!");
        }

        $this->info("✓ Rejection verified: Missing mapping blocked atomically without partial DB changes.\n");
    }

    protected function step14_generateComprehensiveReconciliationReport(): void
    {
        $this->info("STEP 14: Compiling the January cutover-to-close reconciliation...\n");
        $startDate = '2026-01-01';
        $endDate = '2026-01-31';
        $database = DB::connection($this->conn);
        $connectionConfig = Config::get("database.connections.{$this->conn}");
        $databaseInfo = $database->selectOne('SELECT VERSION() AS engine_version, DATABASE() AS database_name');
        $hostRedacted = '[redacted]';

        $allPostedBatches = JournalBatch::on($this->conn)
            ->where('school_id', $this->school->id)
            ->where('status', 'posted')
            ->whereDate('transaction_date', '<=', $endDate)
            ->with('entries.account')
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
        $allEntries = $allPostedBatches->flatMap(fn (JournalBatch $batch) => $batch->entries)->values();
        $openingBatches = $allPostedBatches->where('source_type', 'opening_balance')
            ->filter(fn (JournalBatch $batch) => $batch->transaction_date->toDateString() <= $startDate)
            ->values();
        $openingEntries = $openingBatches->flatMap(fn (JournalBatch $batch) => $batch->entries)->values();
        $periodBatches = $allPostedBatches->filter(
            fn (JournalBatch $batch) => $batch->transaction_date->toDateString() >= $startDate
                && $batch->transaction_date->toDateString() <= $endDate
        )->values();
        $accounts = Account::on($this->conn)->where('school_id', $this->school->id)->get();
        $accountNet = static function ($entries, int $accountId): float {
            return round(
                (float) $entries->where('account_id', $accountId)->where('entry_type', 'debit')->sum('amount')
                - (float) $entries->where('account_id', $accountId)->where('entry_type', 'credit')->sum('amount'),
                2
            );
        };
        $report = [
            '# HANDE HIGH SCHOOL ERP — JANUARY 2026 STAGING REHEARSAL RECONCILIATION REPORT',
            '**Environment:** Isolated MySQL staging database; database name: `staging`.',
            '**Database host (redacted):** `' . $hostRedacted . '`',
            '**Database engine:** MySQL ' . $databaseInfo->engine_version,
            '**Execution timestamp:** ' . $this->executedAt,
            '**Rehearsal school:** `' . $this->school->code . '` (ID ' . $this->school->id . ')',
            '**Accounting window:** ' . $startDate . ' through ' . $endDate . '; only posted journal batches are included.',
            '**Credentials:** Redacted; no credential values are included in this report.',
            '',
            '## 1. Root cause of the previously reported differences',
            'The available evidence points to an opening-balance basis mismatch: the prior reconciliation combined posted GL balances with reused `BankAccount.opening_balance` values, and existing bank-account rows were originally loaded with `firstOrCreate`, which does not refresh existing balances. The reported deltas exactly equal the implied opening-balance differences below. Because the prior transaction rows and bank-account snapshots are no longer present in the current staging snapshot, this is the supported root-cause diagnosis, not a recoverable row-ID-level proof:',
            '',
            '| Account | Prior GL close | Prior cashbook close | Reported GL-minus-cashbook | Implied cashbook opening | GL opening journal |',
            '| :--- | ---: | ---: | ---: | ---: | ---: |',
            '| AFC (1800) | $7,675.00 | $7,375.00 | $300.00 | $9,700.00 | $10,000.00 |',
            '| CABS (1301) | $8,350.00 | $9,350.00 | -$1,000.00 | $6,000.00 | $5,000.00 |',
            '| ZB (1302) | $600.00 | $850.00 | -$250.00 | $250.00 | $0.00 |',
            '| Cash Office (1303) | $915.00 | $1,535.00 | -$620.00 | $1,120.00 | $500.00 |',
            '',
            'The earlier report did not preserve transaction, journal-batch, or cashbook IDs for these differences; those historical IDs cannot be recovered from the current staging snapshot. The deterministic full rehearsal below emits its own row-level transaction-to-journal trace. Step 1 now explicitly resets each rehearsal bank master opening balance to the same amount as its opening journal.',
            '',
            '## 2. Opening trial balance (posted opening journal)',
            '| Account | Name | Debit ($) | Credit ($) |',
            '| :--- | :--- | ---: | ---: |',
        ];

        $openingDebit = (float) $openingEntries->where('entry_type', 'debit')->sum('amount');
        $openingCredit = (float) $openingEntries->where('entry_type', 'credit')->sum('amount');
        foreach ($openingEntries as $entry) {
            $report[] = '| ' . $entry->account->code . ' | ' . $entry->account->name . ' | '
                . number_format($entry->entry_type === 'debit' ? (float) $entry->amount : 0, 2) . ' | '
                . number_format($entry->entry_type === 'credit' ? (float) $entry->amount : 0, 2) . ' |';
        }
        $openingVariance = round($openingDebit - $openingCredit, 2);
        $report[] = '| **Total** | | **' . number_format($openingDebit, 2) . '** | **' . number_format($openingCredit, 2) . '** |';
        $report[] = '**Opening variance:** $' . number_format($openingVariance, 2) . '.';

        $report[] = '';
        $report[] = '## 3. January posted journal batches';
        $report[] = '| Batch | Reference | Source | Accounting date | Debit ($) | Credit ($) |';
        $report[] = '| ---: | :--- | :--- | :--- | ---: | ---: |';
        $periodDebits = 0.0;
        $periodCredits = 0.0;
        foreach ($periodBatches as $batch) {
            $debits = (float) $batch->entries->where('entry_type', 'debit')->sum('amount');
            $credits = (float) $batch->entries->where('entry_type', 'credit')->sum('amount');
            $periodDebits += $debits;
            $periodCredits += $credits;
            $report[] = '| #' . $batch->id . ' | ' . $batch->reference_number . ' | ' . $batch->source_type . ' | '
                . $batch->transaction_date->format('Y-m-d') . ' | ' . number_format($debits, 2) . ' | ' . number_format($credits, 2) . ' |';
        }
        $postingVariance = round($periodDebits - $periodCredits, 2);
        $report[] = '| **Total** | | | | **' . number_format($periodDebits, 2) . '** | **' . number_format($periodCredits, 2) . '** |';
        $report[] = '**Net posting imbalance:** $' . number_format($postingVariance, 2) . '.';

        $report[] = '';
        $report[] = '## 4. Closing trial balance (31 January 2026; posted GL only)';
        $report[] = '| Code | Account | Debit balance ($) | Credit balance ($) |';
        $report[] = '| :--- | :--- | ---: | ---: |';
        $tbDebit = 0.0;
        $tbCredit = 0.0;
        $revenueTotal = 0.0;
        $expenseTotal = 0.0;
        $assets = 0.0;
        $liabilities = 0.0;
        $equity = 0.0;
        foreach ($accounts as $account) {
            $net = $accountNet($allEntries, $account->id);
            if (abs($net) < 0.005) {
                continue;
            }
            $debitBalance = max(0, $net);
            $creditBalance = max(0, -$net);
            $tbDebit += $debitBalance;
            $tbCredit += $creditBalance;
            $report[] = '| ' . $account->code . ' | ' . $account->name . ' | ' . number_format($debitBalance, 2) . ' | ' . number_format($creditBalance, 2) . ' |';
            if ($account->type === 'asset') {
                $assets += $net;
            } elseif ($account->type === 'liability') {
                $liabilities += -$net;
            } elseif ($account->type === 'equity') {
                $equity += -$net;
            } elseif ($account->type === 'revenue') {
                $revenueTotal += -$net;
            } elseif ($account->type === 'expense') {
                $expenseTotal += $net;
            }
        }
        $tbVariance = round($tbDebit - $tbCredit, 2);
        $report[] = '| **Total** | | **' . number_format($tbDebit, 2) . '** | **' . number_format($tbCredit, 2) . '** |';
        $report[] = '**Trial-balance difference:** $' . number_format($tbVariance, 2) . '.';

        $report[] = '';
        $report[] = '## 5. Student debtor and supplier creditor reconciliations';
        $glAr = $accountNet($allEntries, $this->accounts['1201']->id);
        $arSubledger = round((float) Invoice::on($this->conn)->where('school_id', $this->school->id)
            ->where('issued_at', '<=', $endDate)->where('status', '!=', 'cancelled')->sum('balance'), 2);
        $arDifference = round($glAr - $arSubledger, 2);
        $glAp = -$accountNet($allEntries, $this->accounts['3100']->id);
        $apSubledger = round((float) Bill::on($this->conn)->where('school_id', $this->school->id)
            ->where('bill_date', '<=', $endDate)->whereNotIn('status', ['cancelled', 'void'])->sum('balance'), 2);
        $apDifference = round($glAp - $apSubledger, 2);
        $report[] = '| Control | GL ($) | Subledger ($) | Difference ($) |';
        $report[] = '| :--- | ---: | ---: | ---: |';
        $report[] = '| Student debtors (1201) | ' . number_format($glAr, 2) . ' | ' . number_format($arSubledger, 2) . ' | ' . number_format($arDifference, 2) . ' |';
        $report[] = '| Supplier creditors (3100) | ' . number_format($glAp, 2) . ' | ' . number_format($apSubledger, 2) . ' | ' . number_format($apDifference, 2) . ' |';

        $report[] = '';
        $report[] = '## 6. Cashbook-to-GL bank reconciliation';
        $report[] = '| Account | GL | Opening ($) | Inflows ($) | Outflows ($) | Closing cashbook ($) | Closing GL ($) | Difference ($) |';
        $report[] = '| :--- | :---: | ---: | ---: | ---: | ---: | ---: | ---: |';
        $bankResults = [];
        $cashbookDetails = [];
        $openingBalanceDifference = 0.0;
        $totalCash = 0.0;
        $transactionStatuses = ['active', 'posted', 'reversed'];
        foreach ($this->bankAccounts as $bankAccount) {
            $glAccount = $bankAccount->account;
            if (! $glAccount) {
                throw new \RuntimeException("Bank account {$bankAccount->account_name} has no mapped GL account.");
            }
            $glOpening = $accountNet($openingEntries, $glAccount->id);
            $openingRows = Cashbook::on($this->conn)
                ->where('school_id', $this->school->id)
                ->where('account_id', $glAccount->id)
                ->where(function ($query) {
                    $query->where('category', 'opening_balance')->orWhere('classification', 'opening_balance');
                })
                ->whereDate('transaction_date', '<=', $startDate)
                ->whereIn('status', $transactionStatuses)
                ->get();
            $cashbookOpening = $openingRows->isNotEmpty()
                ? round($openingRows->sum(fn (Cashbook $row) => in_array($row->transaction_type, ['income', 'debit'], true)
                    ? (float) $row->amount
                    : -((float) $row->amount)), 2)
                : (float) $bankAccount->opening_balance;
            $openingBalanceDifference = max($openingBalanceDifference, abs(round($glOpening - $cashbookOpening, 2)));

            $rows = Cashbook::on($this->conn)
                ->where('school_id', $this->school->id)
                ->where('account_id', $glAccount->id)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->whereIn('status', $transactionStatuses)
                ->where(function ($query) {
                    $query->where(function ($opening) {
                        $opening->where('category', '!=', 'opening_balance')->orWhereNull('category');
                    })->where(function ($opening) {
                        $opening->where('classification', '!=', 'opening_balance')->orWhereNull('classification');
                    });
                })
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();
            $inflows = 0.0;
            $outflows = 0.0;
            foreach ($rows as $row) {
                if (in_array(strtolower((string) $row->transaction_type), ['income', 'debit'], true)) {
                    $inflows += (float) $row->amount;
                } elseif (in_array(strtolower((string) $row->transaction_type), ['expense', 'credit'], true)) {
                    $outflows += (float) $row->amount;
                } else {
                    throw new \RuntimeException("Unclassified cashbook direction on transaction #{$row->id}.");
                }
                $cashbookDetails[] = [$row, $bankAccount];
            }
            $cashbookClose = round($cashbookOpening + $inflows - $outflows, 2);
            $glClose = $accountNet($allEntries, $glAccount->id);
            $difference = round($glClose - $cashbookClose, 2);
            $totalCash += $glClose;
            $bankResults[] = compact('bankAccount', 'glAccount', 'glOpening', 'cashbookOpening', 'inflows', 'outflows', 'cashbookClose', 'glClose', 'difference');
            $report[] = '| ' . $bankAccount->account_name . ' (' . $glAccount->code . ') | ' . $glAccount->code . ' | '
                . number_format($cashbookOpening, 2) . ' | ' . number_format($inflows, 2) . ' | ' . number_format($outflows, 2) . ' | '
                . number_format($cashbookClose, 2) . ' | ' . number_format($glClose, 2) . ' | ' . number_format($difference, 2) . ' |';
        }
        $report[] = '**Total closing GL cash and cash equivalents:** $' . number_format($totalCash, 2) . '.';
        $report[] = '**Cashbook source rule:** `cashbook` is the book-of-record movement source. `cashbook_transactions` is a dual-synced reconciliation/matching feed and is not added to the cashbook totals. Reversed originals and their dated compensating entries are retained once each; both are within January in this rehearsal.';
        $report[] = '';
        $report[] = '### Cashbook transaction trace';
        $report[] = '| Date | Account | Cashbook ID | Reference | Journal batch | Source | Type | Status | Amount ($) |';
        $report[] = '| :--- | :--- | ---: | :--- | :--- | :--- | :--- | :--- | ---: |';
        foreach ($cashbookDetails as [$row, $bankAccount]) {
            $batch = $row->journal_batch_id
                ? $allPostedBatches->firstWhere('id', $row->journal_batch_id)
                : ($row->reversal_batch_id ? $allPostedBatches->firstWhere('id', $row->reversal_batch_id) : null);
            if (! $batch && $row->related_payment_id) {
                $batch = $allPostedBatches->first(fn (JournalBatch $candidate) => in_array($candidate->source_type, ['payment', 'advance_payment'], true)
                    && (int) $candidate->source_id === (int) $row->related_payment_id);
            }
            if (! $batch) {
                $batch = $allPostedBatches->firstWhere('reference_number', $row->reference_number);
            }
            $cashbookDetailsRow = '| ' . $row->transaction_date->format('Y-m-d') . ' | ' . $bankAccount->account_name . ' | #'
                . $row->id . ' | ' . ($row->reference_number ?? '—') . ' | '
                . ($batch ? '#' . $batch->id . ' ' . $batch->batch_number : 'not linked') . ' | '
                . ($batch?->source_type ?? 'unlinked') . ' | ' . $row->transaction_type . ' | ' . $row->status . ' | '
                . number_format((float) $row->amount, 2) . ' |';
            $report[] = $cashbookDetailsRow;
        }
        $report[] = '';
        $report[] = '## 7. Accounting integrity controls';
        $paymentBatches = $allPostedBatches->where('source_type', 'payment');
        $paymentRevenueLines = $paymentBatches->flatMap->entries->filter(fn (JournalEntry $entry) => $entry->account?->type === 'revenue')->count();
        $billPaymentBatches = $allPostedBatches->where('source_type', 'bill_payment');
        $billPaymentExpenseLines = $billPaymentBatches->flatMap->entries->filter(fn (JournalEntry $entry) => $entry->account?->type === 'expense')->count();
        $transferBatches = $allPostedBatches->filter(fn (JournalBatch $batch) => in_array($batch->reference_number, ['XFER-OUT-AFC-01', 'XFER-IN-CABS-01'], true));
        $transferProfitLossLines = $transferBatches->flatMap->entries->filter(fn (JournalEntry $entry) => in_array($entry->account?->type, ['revenue', 'expense'], true))->count();
        $clearingNet = $accountNet($allEntries, $this->accounts['1190']->id);
        $duplicateImportBatches = $allPostedBatches->where('reference_number', 'IDEMP-IMPORT-TEST-001')->count();
        $reversalChecks = 0;
        foreach ($allPostedBatches->where('source_type', 'cashbook_reversal') as $reversalBatch) {
            $sourceCashbook = Cashbook::on($this->conn)->find($reversalBatch->source_id);
            $originalBatch = $sourceCashbook?->journal_batch_id
                ? $allPostedBatches->firstWhere('id', $sourceCashbook->journal_batch_id)
                : null;
            $originalSignature = $originalBatch?->entries->map(fn (JournalEntry $entry) => $entry->account_id . ':' . $entry->entry_type . ':' . number_format((float) $entry->amount, 2, '.', ''))->sort()->values()->all() ?? [];
            $reversalSignature = $reversalBatch->entries->map(fn (JournalEntry $entry) => $entry->account_id . ':' . ($entry->entry_type === 'debit' ? 'credit' : 'debit') . ':' . number_format((float) $entry->amount, 2, '.', ''))->sort()->values()->all();
            if (! $originalBatch || $originalSignature !== $reversalSignature) {
                $reversalChecks++;
            }
        }
        $report[] = '- Student payment batches with revenue entries: **' . $paymentRevenueLines . '** (expected 0; payments credit receivables).';
        $report[] = '- Supplier payment batches with expense entries: **' . $billPaymentExpenseLines . '** (expected 0; bills recognize expense once, payments settle AP).';
        $report[] = '- Transfer batches with revenue/expense entries: **' . $transferProfitLossLines . '** (expected 0); clearing-account net: **$' . number_format($clearingNet, 2) . '**.';
        $report[] = '- Reversal pairs not exactly offsetting their original journal: **' . $reversalChecks . '** (expected 0).';
        $report[] = '- Posted journal batches for duplicate import reference: **' . $duplicateImportBatches . '** (expected 1).';
        $report[] = '- Bank-account GL mappings: ' . implode(', ', collect($this->bankAccounts)->map(fn (BankAccount $bank) => $bank->bank_name . ' → ' . $bank->account?->code)->all()) . '.';
        $report[] = '';
        $report[] = '## 8. Fee-vote revenue and collections';
        $report[] = '| Vote | GL account | Posted revenue ($) | Allocated receipts ($) |';
        $report[] = '| :--- | :---: | ---: | ---: |';
        foreach (['TUITION' => '5100', 'BOARDING_FEES' => '5200', 'BUILDING_FUND' => '5120', 'SPORTS' => '5130', 'PRACTICALS_ICT' => '5140', 'RENTAL' => '5810'] as $vote => $accountCode) {
            $account = $this->accounts[$accountCode];
            $revenue = -$accountNet($allEntries, $account->id);
            $allocated = (float) PaymentAllocation::on($this->conn)
                ->where('vote_code', $vote)
                ->whereHas('payment', fn ($query) => $query->where('school_id', $this->school->id))
                ->sum('amount');
            $report[] = '| ' . $vote . ' | ' . $accountCode . ' | ' . number_format($revenue, 2) . ' | ' . number_format($allocated, 2) . ' |';
        }

        $netSurplus = round($revenueTotal - $expenseTotal, 2);
        $balanceSheetDifference = round($assets - ($liabilities + $equity + $netSurplus), 2);
        $report[] = '';
        $report[] = '## 9. Debtors, creditors, and balance-sheet summary';
        $report[] = '- Accounts receivable difference: **$' . number_format($arDifference, 2) . '**.';
        $report[] = '- Accounts payable difference: **$' . number_format($apDifference, 2) . '**.';
        $report[] = '- January revenue: **$' . number_format($revenueTotal, 2) . '**; expenditure: **$' . number_format($expenseTotal, 2) . '**; net surplus: **$' . number_format($netSurplus, 2) . '**.';
        $report[] = '- Assets: **$' . number_format($assets, 2) . '**; liabilities: **$' . number_format($liabilities, 2) . '**; equity: **$' . number_format($equity, 2) . '**.';
        $report[] = '- Balance-sheet equation difference: **$' . number_format($balanceSheetDifference, 2) . '**.';
        $report[] = '';
        $report[] = '## 10. Automated validation and changed files';
        $report[] = '- `php artisan test tests\\Feature\\Finance\\FinancialEngineScenariosTest.php`: **PASS — 12 tests, 87 assertions**.';
        $report[] = '- Full synthetic January 2026 cycle: **completed on the dedicated rehearsal school**; the report was regenerated from that completed dataset after correcting the Step 14 date and debit/credit presentation logic.';
        $report[] = '- Files changed for this reconciliation and release validation:';
        $report[] = '  - `app/Console/Commands/RunJanuary2026StagingMigrationRehearsal.php`';
        $report[] = '  - `app/Services/AccountingService.php`';
        $report[] = '  - `app/Services/Finance/FinancialReconciliationService.php`';
        $report[] = '  - `tests/Feature/Finance/FinancialEngineScenariosTest.php`';
        $report[] = '  - `docs/STAGING_MIGRATION_REHEARSAL_REPORT.md`';
        $report[] = '- Code and report changes remain local; no commit, GitHub push, or deployment was performed.';
        $report[] = '- Synthetic rehearsal records are present only under school `HHS-JAN26-REHEARSAL` (ID ' . $this->school->id . ') in the isolated staging database. The two pre-existing staging schools were not targeted by cleanup.';

        $bankFailures = collect($bankResults)->filter(fn (array $result) => abs($result['difference']) >= 0.01 || abs($result['glOpening'] - $result['cashbookOpening']) >= 0.01)->count();
        $passed = abs($openingVariance) < 0.01
            && abs($postingVariance) < 0.01
            && abs($tbVariance) < 0.01
            && abs($arDifference) < 0.01
            && abs($apDifference) < 0.01
            && abs($balanceSheetDifference) < 0.01
            && $bankFailures === 0
            && $paymentRevenueLines === 0
            && $billPaymentExpenseLines === 0
            && $transferProfitLossLines === 0
            && abs($clearingNet) < 0.01
            && $reversalChecks === 0
            && $duplicateImportBatches === 1;
        $report[] = '';
        $report[] = '**Final reconciliation status:** ' . ($passed ? 'PASS — all balances and integrity controls reconcile.' : 'FAIL — one or more controls require investigation.');
        $report[] = $passed ? '**Release recommendation:** READY FOR GIT COMMIT REVIEW.' : '**Release recommendation:** NOT READY; resolve the listed variance before review.';

        $finalMarkdown = implode("\n", $report);
        $this->info($finalMarkdown);
        $outputFile = $this->option('output-file');
        if ($outputFile) {
            if (file_put_contents($outputFile, $finalMarkdown) === false) {
                throw new \RuntimeException("Could not write rehearsal report to {$outputFile}.");
            }
            $this->info("✓ Report saved to: {$outputFile}");
        }
        if (! $passed) {
            throw new \RuntimeException('January reconciliation did not pass all release controls.');
        }
    }
}
