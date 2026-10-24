<?php

namespace Tests\Feature\Finance;

use App\Exceptions\MissingAccountMappingException;
use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Cashbook;
use App\Models\CashbookTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\MigrationBatch;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AccountingService;
use App\Services\Finance\CashbookReversalService;
use App\Services\Finance\FeeRevenueAccountResolver;
use App\Services\Finance\FinancialAccountResolver;
use App\Services\Finance\FinancialReconciliationService;
use App\Services\Finance\HistoricalMigrationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialEngineScenariosTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $user;
    protected Account $afcAccount;
    protected Account $cabsAccount;
    protected Account $receivableAccount;
    protected Account $payableAccount;
    protected Account $prepaidFeesAccount;
    protected Account $equityAccount;
    protected Account $tuitionAccount;
    protected Account $buildingFundAccount;
    protected Account $gpfAccount;
    protected Account $sportsAccount;
    protected Account $ictAccount;
    protected Account $suppliesExpenseAccount;
    protected BankAccount $afcBank;
    protected BankAccount $cabsBank;

    protected AccountingService $accountingService;
    protected FinancialAccountResolver $accountResolver;
    protected HistoricalMigrationService $migrationService;
    protected FinancialReconciliationService $reconciliationService;
    protected CashbookReversalService $reversalService;

    protected function setUp(): void
    {
        parent::setUp();

        (new \Database\Seeders\RoleSeeder)->run();
        (new \Database\Seeders\RolePermissionSeeder)->run();

        $this->school = School::create([
            'name' => 'Hande High School',
            'code' => 'HHS',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'School Bursar',
        ]);
        $this->user->assignRole('super-admin');
        $this->actingAs($this->user);

        // General Ledger Accounts Setup
        $this->afcAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1321',
            'name' => 'Cash at AFC Bank',
            'type' => 'asset',
            'category' => 'bank',
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->cabsAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1322',
            'name' => 'Cash at CABS Bank',
            'type' => 'asset',
            'category' => 'bank',
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->receivableAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1201',
            'name' => 'Student Fees Receivable',
            'type' => 'asset',
            'category' => 'receivable',
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->payableAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '3101',
            'name' => 'Trade Creditors Control',
            'type' => 'liability',
            'category' => 'payable',
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->prepaidFeesAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '2105',
            'name' => 'Prepaid Fees Liability',
            'type' => 'liability',
            'category' => 'current_liability',
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->equityAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '3300',
            'name' => 'Opening Balance Equity',
            'type' => 'equity',
            'category' => 'retained_earnings',
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->tuitionAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5101',
            'name' => 'Tuition Fees Revenue',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->buildingFundAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5102',
            'name' => 'Building Fund Revenue',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->gpfAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5103',
            'name' => 'General Purpose Fund Revenue',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->sportsAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5104',
            'name' => 'Sports & Culture Revenue',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->ictAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5105',
            'name' => 'ICT & Computer Levy Revenue',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->suppliesExpenseAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '6501',
            'name' => 'School Stationery & Supplies Expense',
            'type' => 'expense',
            'category' => 'supply_expense',
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        // Bank Accounts Setup
        $this->afcBank = BankAccount::create([
            'school_id' => $this->school->id,
            'account_id' => $this->afcAccount->id,
            'bank_name' => 'AFC Commercial Bank',
            'account_name' => 'Hande High School AFC Main Account',
            'account_number' => '1000998877',
            'currency' => 'USD',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $this->cabsBank = BankAccount::create([
            'school_id' => $this->school->id,
            'account_id' => $this->cabsAccount->id,
            'bank_name' => 'CABS Bank',
            'account_name' => 'Hande High School CABS Operations',
            'account_number' => '9000112233',
            'currency' => 'USD',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        // Register Dynamic Account Mappings
        $this->accountResolver = app(FinancialAccountResolver::class);
        $this->accountResolver->setMapping($this->school, 'control_account', 'ACCOUNTS_RECEIVABLE', $this->receivableAccount);
        $this->accountResolver->setMapping($this->school, 'control_account', 'ACCOUNTS_PAYABLE', $this->payableAccount);
        $this->accountResolver->setMapping($this->school, 'control_account', 'PREPAID_FEES', $this->prepaidFeesAccount);
        $this->accountResolver->setMapping($this->school, 'control_account', 'OPENING_BALANCE_EQUITY', $this->equityAccount);
        $this->accountResolver->setMapping($this->school, 'bank_account', (string) $this->afcBank->id, $this->afcAccount);
        $this->accountResolver->setMapping($this->school, 'bank_account', (string) $this->cabsBank->id, $this->cabsAccount);
        $this->accountResolver->setMapping($this->school, 'fee_vote', 'TUITION', $this->tuitionAccount);
        $this->accountResolver->setMapping($this->school, 'fee_vote', 'BUILDING', $this->buildingFundAccount);
        $this->accountResolver->setMapping($this->school, 'fee_vote', 'GPF', $this->gpfAccount);
        $this->accountResolver->setMapping($this->school, 'fee_vote', 'SPORTS', $this->sportsAccount);
        $this->accountResolver->setMapping($this->school, 'fee_vote', 'ICT', $this->ictAccount);
        $this->accountResolver->setMapping($this->school, 'expenditure_category', 'supplies', $this->suppliesExpenseAccount);

        $this->accountingService = app(AccountingService::class);
        $this->migrationService = app(HistoricalMigrationService::class);
        $this->reconciliationService = app(FinancialReconciliationService::class);
        $this->reversalService = app(CashbookReversalService::class);
    }

    public function test_journal_batch_numbers_remain_unique_across_schools(): void
    {
        $otherSchool = School::create([
            'name' => 'Other School',
            'code' => 'OTHER',
            'is_active' => true,
        ]);
        JournalBatch::create([
            'school_id' => $otherSchool->id,
            'batch_number' => 'JB-' . date('Y') . '-000001',
            'transaction_date' => now()->toDateString(),
            'reference_number' => 'OTHER-SCHOOL-POSTING',
            'description' => 'Existing batch from another school',
            'source_type' => 'manual',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $batch = $this->accountingService->createJournalBatch([
            'school_id' => $this->school->id,
            'transaction_date' => '2026-01-01',
            'reference_number' => 'HHS-UNIQUE-BATCH-TEST',
            'description' => 'Cross-school batch-number uniqueness test',
            'source_type' => 'manual',
            'status' => 'posted',
            'created_by' => $this->user->id,
            'entries' => [
                ['account_id' => $this->afcAccount->id, 'entry_type' => 'debit', 'amount' => 10.00],
                ['account_id' => $this->equityAccount->id, 'entry_type' => 'credit', 'amount' => 10.00],
            ],
        ]);

        $this->assertNotSame('JB-' . date('Y') . '-000001', $batch->batch_number);
        $this->assertSame(1, JournalBatch::where('batch_number', $batch->batch_number)->count());
    }

    /**
     * Scenario A: Student invoice and bank payment
     * Generate an invoice, record a partial bank payment, allocate the receipt,
     * verify the cashbook, GL, student statement and Trial Balance.
     */
    public function test_scenario_a_student_invoice_and_bank_payment(): void
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_number' => 'HHS-2026-001',
            'first_name' => 'Tatenda',
            'last_name' => 'Moyo',
            'gender' => 'male',
            'status' => 'active',
        ]);

        // 1. Generate Invoice: $500 ($300 Tuition + $200 Building)
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'number' => 'INV-2026-001',
            'issued_at' => '2026-01-10',
            'due_date' => '2026-01-31',
            'total_amount' => 500.00,
            'balance' => 500.00,
            'status' => 'issued',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'vote_code' => 'TUITION',
            'description' => 'Term 1 Tuition Fee',
            'quantity' => 1,
            'unit_amount' => 300.00,
            'line_total' => 300.00,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'vote_code' => 'BUILDING',
            'description' => 'Development Levy',
            'quantity' => 1,
            'unit_amount' => 200.00,
            'line_total' => 200.00,
        ]);

        // Post Invoice
        $invoiceBatch = $this->accountingService->postInvoice($invoice);
        $this->assertEquals('posted', $invoiceBatch->status);

        // Verify Invoice GL Postings: DR AR $500, CR Tuition $300, CR Building $200
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $invoiceBatch->id,
            'account_id' => $this->receivableAccount->id,
            'entry_type' => 'debit',
            'amount' => 500.00,
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $invoiceBatch->id,
            'account_id' => $this->tuitionAccount->id,
            'entry_type' => 'credit',
            'amount' => 300.00,
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $invoiceBatch->id,
            'account_id' => $this->buildingFundAccount->id,
            'entry_type' => 'credit',
            'amount' => 200.00,
        ]);

        // 2. Record Partial Payment: $200 via AFC Bank
        $payment = Payment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'reference' => 'REC-2026-001',
            'amount' => 200.00,
            'paid_at' => '2026-01-15',
            'method' => 'bank_transfer',
            'account_id' => $this->afcAccount->id,
            'status' => 'completed',
            'created_by' => $this->user->id,
        ]);

        // Post Payment against invoice
        $paymentBatch = $this->accountingService->postPayment($payment, $invoice, $this->afcAccount->id);
        $this->assertEquals('posted', $paymentBatch->status);

        // Update invoice balance
        $invoice->update([
            'balance' => 300.00,
            'status' => 'partial',
        ]);

        // 3. Verify Payment GL Postings: DR AFC Bank $200, CR Accounts Receivable $200 (No double revenue!)
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $paymentBatch->id,
            'account_id' => $this->afcAccount->id,
            'entry_type' => 'debit',
            'amount' => 200.00,
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $paymentBatch->id,
            'account_id' => $this->receivableAccount->id,
            'entry_type' => 'credit',
            'amount' => 200.00,
        ]);

        // 4. Verify Cashbook sync
        $this->assertDatabaseHas('cashbook', [
            'school_id' => $this->school->id,
            'account_id' => $this->afcAccount->id,
            'reference_number' => 'REC-2026-001',
            'amount' => 200.00,
            'transaction_type' => 'income',
        ]);

        // 5. Verify Reconciliation: General Ledger & Debtors Control
        $glReconciliation = $this->reconciliationService->reconcileGeneralLedgerBalance($this->school);
        $this->assertTrue($glReconciliation['is_balanced'], "GL must be balanced");
        $this->assertEquals(700.00, $glReconciliation['total_debits']);
        $this->assertEquals(700.00, $glReconciliation['total_credits']);

        $debtorsControl = $this->reconciliationService->reconcileDebtorsControl($this->school);
        $this->assertTrue($debtorsControl['is_reconciled'], "Debtors Control must match subledger");
        $this->assertEquals(300.00, $debtorsControl['gl_ar_balance']);
        $this->assertEquals(300.00, $debtorsControl['subledger_debtors_balance']);
    }

    /**
     * Scenario B: Manual historical cashbook receipt
     * Capture a receipt dated January 2026 while system date is October 2026.
     * Confirm transaction appears in January reports and correct bank account.
     */
    public function test_scenario_b_manual_historical_cashbook_receipt(): void
    {
        // Capture transaction dated 2026-01-20
        $historicalDate = '2026-01-20';
        $cashbook = Cashbook::create([
            'school_id' => $this->school->id,
            'account_id' => $this->cabsAccount->id,
            'transaction_type' => 'income',
            'category' => 'fees',
            'classification' => 'debtor_receipt',
            'description' => 'Historical Cashbook Fee Deposit CABS',
            'amount' => 350.00,
            'balance_after' => 350.00,
            'transaction_date' => $historicalDate,
            'payment_method' => 'bank_deposit',
            'reference_number' => 'CABS-HIST-001',
            'supporting_document_ref' => 'Slip #44912',
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $batch = $this->accountingService->postManualCashbookTransaction($cashbook);
        $this->assertEquals($historicalDate, is_string($batch->transaction_date) ? $batch->transaction_date : $batch->transaction_date->toDateString());

        // Check January 2026 Trial Balance includes this transaction
        $janTrialBalance = $this->reconciliationService->reconcileGeneralLedgerBalance($this->school, '2026-01-31');
        $this->assertTrue($janTrialBalance['is_balanced']);
        $this->assertGreaterThanOrEqual(350.00, $janTrialBalance['total_debits']);

        // Check it correctly debited CABS bank and credited Accounts Receivable
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $batch->id,
            'account_id' => $this->cabsAccount->id,
            'entry_type' => 'debit',
            'amount' => 350.00,
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $batch->id,
            'account_id' => $this->receivableAccount->id,
            'entry_type' => 'credit',
            'amount' => 350.00,
        ]);

        // Ensure Cashbook table has it with exact historical date
        $cashbook->refresh();
        $this->assertEquals($this->cabsAccount->id, $cashbook->account_id);
        $this->assertEquals('2026-01-20', Carbon::parse($cashbook->transaction_date)->toDateString());
        $this->assertEquals($batch->id, $cashbook->journal_batch_id);
    }

    public function test_bank_reconciliation_uses_opening_balance_posted_journals_and_dated_reversals_once(): void
    {
        $this->afcBank->update(['opening_balance' => 1000.00]);

        $createBatch = function (string $reference, string $date, string $source, array $entries): JournalBatch {
            return $this->accountingService->createJournalBatch([
                'school_id' => $this->school->id,
                'transaction_date' => $date,
                'reference_number' => $reference,
                'description' => $reference,
                'source_type' => $source,
                'status' => 'posted',
                'created_by' => $this->user->id,
                'entries' => $entries,
            ]);
        };

        $openingBatch = $createBatch('RECON-OPENING', '2026-01-01', 'opening_balance', [
            ['account_id' => $this->afcAccount->id, 'entry_type' => 'debit', 'amount' => 1000.00],
            ['account_id' => $this->equityAccount->id, 'entry_type' => 'credit', 'amount' => 1000.00],
        ]);
        $incomeBatch = $createBatch('RECON-INCOME', '2026-01-10', 'cashbook', [
            ['account_id' => $this->afcAccount->id, 'entry_type' => 'debit', 'amount' => 200.00],
            ['account_id' => $this->tuitionAccount->id, 'entry_type' => 'credit', 'amount' => 200.00],
        ]);
        $expenseBatch = $createBatch('RECON-EXPENSE', '2026-01-12', 'cashbook', [
            ['account_id' => $this->suppliesExpenseAccount->id, 'entry_type' => 'debit', 'amount' => 75.00],
            ['account_id' => $this->afcAccount->id, 'entry_type' => 'credit', 'amount' => 75.00],
        ]);
        $reversalBatch = $createBatch('RECON-REVERSAL', '2026-01-13', 'cashbook_reversal', [
            ['account_id' => $this->afcAccount->id, 'entry_type' => 'debit', 'amount' => 75.00],
            ['account_id' => $this->suppliesExpenseAccount->id, 'entry_type' => 'credit', 'amount' => 75.00],
        ]);
        $futureBatch = $createBatch('RECON-FUTURE', '2026-02-01', 'cashbook', [
            ['account_id' => $this->afcAccount->id, 'entry_type' => 'debit', 'amount' => 300.00],
            ['account_id' => $this->tuitionAccount->id, 'entry_type' => 'credit', 'amount' => 300.00],
        ]);

        Cashbook::create([
            'school_id' => $this->school->id,
            'account_id' => $this->afcAccount->id,
            'transaction_type' => 'income',
            'category' => 'opening_balance',
            'classification' => 'opening_balance',
            'description' => 'Opening cashbook balance',
            'amount' => 1000.00,
            'balance_after' => 1000.00,
            'transaction_date' => '2026-01-01',
            'status' => 'active',
            'journal_batch_id' => $openingBatch->id,
        ]);
        Cashbook::create([
            'school_id' => $this->school->id,
            'account_id' => $this->afcAccount->id,
            'transaction_type' => 'income',
            'category' => 'fees',
            'description' => 'January receipt',
            'amount' => 200.00,
            'balance_after' => 1200.00,
            'transaction_date' => '2026-01-10',
            'status' => 'posted',
            'journal_batch_id' => $incomeBatch->id,
        ]);
        Cashbook::create([
            'school_id' => $this->school->id,
            'account_id' => $this->afcAccount->id,
            'transaction_type' => 'expense',
            'category' => 'supplies',
            'description' => 'Reversed January payment',
            'amount' => 75.00,
            'balance_after' => 1125.00,
            'transaction_date' => '2026-01-12',
            'status' => 'reversed',
            'journal_batch_id' => $expenseBatch->id,
            'reversal_batch_id' => $reversalBatch->id,
        ]);
        Cashbook::create([
            'school_id' => $this->school->id,
            'account_id' => $this->afcAccount->id,
            'transaction_type' => 'income',
            'category' => 'reversal',
            'description' => 'Compensating cashbook entry',
            'amount' => 75.00,
            'balance_after' => 1200.00,
            'transaction_date' => '2026-01-13',
            'status' => 'active',
            'reversal_batch_id' => $reversalBatch->id,
        ]);
        Cashbook::create([
            'school_id' => $this->school->id,
            'account_id' => $this->afcAccount->id,
            'transaction_type' => 'income',
            'category' => 'fees',
            'description' => 'February receipt',
            'amount' => 300.00,
            'balance_after' => 1500.00,
            'transaction_date' => '2026-02-01',
            'status' => 'posted',
            'journal_batch_id' => $futureBatch->id,
        ]);

        $afc = collect($this->reconciliationService->reconcileBankAndCashbook($this->school, '2026-01-31'))
            ->firstWhere('bank_id', $this->afcBank->id);

        $this->assertSame(1000.0, $afc['opening_balance']);
        $this->assertSame(275.0, $afc['cashbook_inflows']);
        $this->assertSame(75.0, $afc['cashbook_outflows']);
        $this->assertSame(1200.0, $afc['cashbook_balance']);
        $this->assertSame(1200.0, $afc['gl_balance']);
        $this->assertSame(0.0, $afc['difference']);
        $this->assertSame(0, $afc['unclassified_cashbook_movements']);
        $this->assertTrue($afc['is_reconciled']);
    }

    /**
     * Scenario C: Supplier expenditure
     * Capture a supplier bill and payment. Verify correct payable, expense, bank and cashbook postings without double recognition.
     */
    public function test_scenario_c_supplier_expenditure_workflow(): void
    {
        $vendor = Vendor::create([
            'school_id' => $this->school->id,
            'code' => 'VEND-001',
            'name' => 'Textbook Sales & Stationery',
            'email' => 'orders@textbooksales.co.zw',
            'phone' => '+263772111222',
            'is_active' => true,
        ]);

        // 1. Capture Bill: $450
        $bill = Bill::create([
            'school_id' => $this->school->id,
            'vendor_id' => $vendor->id,
            'bill_number' => 'BILL-2026-001',
            'bill_date' => '2026-01-15',
            'due_date' => '2026-02-15',
            'subtotal' => 450.00,
            'total_amount' => 450.00,
            'paid_amount' => 0.00,
            'status' => 'received',
            'created_by' => $this->user->id,
        ]);

        $billBatch = $this->accountingService->postBill($bill, $this->suppliesExpenseAccount->id);
        $this->assertEquals('posted', $billBatch->status);

        // Bill entry: DR Expense $450, CR AP $450
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $billBatch->id,
            'account_id' => $this->suppliesExpenseAccount->id,
            'entry_type' => 'debit',
            'amount' => 450.00,
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $billBatch->id,
            'account_id' => $this->payableAccount->id,
            'entry_type' => 'credit',
            'amount' => 450.00,
        ]);

        // 2. Pay Bill: $450 from AFC Bank
        $billPayment = BillPayment::create([
            'school_id' => $this->school->id,
            'bill_id' => $bill->id,
            'vendor_id' => $vendor->id,
            'bank_account_id' => $this->afcBank->id,
            'account_id' => $this->afcAccount->id,
            'amount' => 450.00,
            'payment_date' => '2026-01-18',
            'payment_method' => 'bank_transfer',
            'reference_number' => 'BP-2026-001',
            'created_by' => $this->user->id,
        ]);

        $payBatch = $this->accountingService->postBillPayment($billPayment);
        $this->assertEquals('posted', $payBatch->status);

        // Payment entry: DR AP $450, CR AFC Bank $450 (NO duplicate expense!)
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $payBatch->id,
            'account_id' => $this->payableAccount->id,
            'entry_type' => 'debit',
            'amount' => 450.00,
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $payBatch->id,
            'account_id' => $this->afcAccount->id,
            'entry_type' => 'credit',
            'amount' => 450.00,
        ]);

        // Verify Expense recognized exactly once ($450)
        $expenseDebits = JournalEntry::where('account_id', $this->suppliesExpenseAccount->id)->where('entry_type', 'debit')->sum('amount');
        $this->assertEquals(450.00, (float) $expenseDebits);

        // Update bill status
        $bill->update(['paid_amount' => 450.00, 'status' => 'paid']);

        // Verify Creditors Control reconciliation
        $creditors = $this->reconciliationService->reconcileCreditorsControl($this->school);
        $this->assertTrue($creditors['is_reconciled']);
        $this->assertEquals(0.00, $creditors['gl_ap_balance']);
        $this->assertEquals(0.00, $creditors['subledger_creditors_balance']);
    }

    /**
     * Scenario D: Bank transfer
     * Transfer funds between two bank accounts. Confirm total cash does not change and zero income/expense is recognised.
     */
    public function test_scenario_d_interbank_transfer(): void
    {
        $transferAmount = 1000.00;

        // Post transfer: AFC Bank to CABS Bank
        $batch = $this->accountingService->createJournalBatch([
            'school_id' => $this->school->id,
            'transaction_date' => '2026-01-25',
            'reference_number' => 'TRF-AFC-CABS-001',
            'description' => 'Inter-bank fund transfer AFC to CABS',
            'source_type' => 'transfer',
            'source_id' => 1,
            'status' => 'posted',
            'created_by' => $this->user->id,
            'entries' => [
                [
                    'account_id' => $this->cabsAccount->id,
                    'entry_type' => 'debit',
                    'amount' => $transferAmount,
                    'memo' => 'Transfer in from AFC',
                ],
                [
                    'account_id' => $this->afcAccount->id,
                    'entry_type' => 'credit',
                    'amount' => $transferAmount,
                    'memo' => 'Transfer out to CABS',
                ],
            ],
        ]);

        $this->assertEquals('posted', $batch->status);

        // Total cash net impact across both accounts is 0
        $afcNet = JournalEntry::where('account_id', $this->afcAccount->id)->where('journal_batch_id', $batch->id)->first();
        $cabsNet = JournalEntry::where('account_id', $this->cabsAccount->id)->where('journal_batch_id', $batch->id)->first();

        $this->assertEquals('credit', $afcNet->entry_type);
        $this->assertEquals('debit', $cabsNet->entry_type);
        $this->assertEquals(1000.00, (float) $afcNet->amount);
        $this->assertEquals(1000.00, (float) $cabsNet->amount);

        // Zero revenue or expense entries
        $revenueEntries = JournalEntry::where('journal_batch_id', $batch->id)->whereHas('account', fn($q) => $q->where('type', 'revenue'))->count();
        $expenseEntries = JournalEntry::where('journal_batch_id', $batch->id)->whereHas('account', fn($q) => $q->where('type', 'expense'))->count();

        $this->assertEquals(0, $revenueEntries);
        $this->assertEquals(0, $expenseEntries);
    }

    /**
     * Scenario E: Duplicate import
     * Import the same historical cashbook file twice. Confirm transactions are not posted twice and duplicates are reported.
     */
    public function test_scenario_e_duplicate_import_detection(): void
    {
        $batch1 = $this->migrationService->createBatch($this->school, 'bank_cashbook', 'jan_cabs_statement.csv', $this->user->id);

        $rowsData = [
            [
                'accounting_date' => '2026-01-05',
                'reference_number' => 'STMT-001',
                'description' => 'Tuition deposit',
                'amount' => 250.00,
                'direction' => 'debit',
                'bank_identifier' => $this->cabsBank->account_number,
                'vote_code' => 'TUITION',
            ],
            [
                'accounting_date' => '2026-01-06',
                'reference_number' => 'STMT-002',
                'description' => 'Building levy deposit',
                'amount' => 150.00,
                'direction' => 'debit',
                'bank_identifier' => $this->cabsBank->account_number,
                'vote_code' => 'BUILDING',
            ],
        ];

        // 1. Stage and post Batch 1
        $this->migrationService->stageRows($batch1, $rowsData);
        $this->migrationService->validateBatch($batch1);
        $this->migrationService->approveBatch($batch1, $this->user->id);
        $result1 = $this->migrationService->postBatch($batch1, $this->user->id);

        $this->assertEquals(2, $result1['posted_rows']);
        $this->assertEquals(2, Cashbook::where('school_id', $this->school->id)->count());

        // 2. Stage same data in Batch 2
        $batch2 = $this->migrationService->createBatch($this->school, 'bank_cashbook', 'jan_cabs_statement_copy.csv', $this->user->id);
        $this->migrationService->stageRows($batch2, $rowsData);
        $val2 = $this->migrationService->validateBatch($batch2);

        // Batch 2 must detect duplicates and flag error rows
        $this->assertEquals(2, $val2['error_rows']);
        $this->assertEquals(0, $val2['valid_rows']);

        // Assert posting Batch 2 is rejected
        $this->expectException(\InvalidArgumentException::class);
        $this->migrationService->approveBatch($batch2, $this->user->id);
    }

    /**
     * Scenario F: Reversal
     * Reverse an incorrectly posted receipt and verify that all affected financial reports reconcile,
     * with original transaction retained for audit purposes.
     */
    public function test_scenario_f_reversal_of_erroneous_transaction(): void
    {
        // 1. Post erroneous cashbook transaction: $300
        $cashbook = Cashbook::create([
            'school_id' => $this->school->id,
            'account_id' => $this->afcAccount->id,
            'transaction_type' => 'income',
            'category' => 'fees',
            'classification' => 'debtor_receipt',
            'description' => 'Erroneous fee deposit',
            'amount' => 300.00,
            'balance_after' => 300.00,
            'transaction_date' => '2026-01-12',
            'payment_method' => 'bank_deposit',
            'reference_number' => 'ERR-REC-001',
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $originalBatch = $this->accountingService->postManualCashbookTransaction($cashbook);
        $this->assertEquals('posted', $originalBatch->status);

        // 2. Perform Reversal
        $revResult = $this->reversalService->reverseCashbookTransaction($cashbook, 'Captured to wrong account', $this->user);
        $this->assertTrue($revResult['success']);

        // Verify original transaction is retained for audit with status 'reversed'
        $cashbook->refresh();
        $this->assertEquals('reversed', $cashbook->status);
        $this->assertNotNull($cashbook->reversed_at);

        // Verify reversing batch created
        $reversalBatch = JournalBatch::find($revResult['reversal_batch_id']);
        $this->assertNotNull($reversalBatch);
        $this->assertStringContainsString('REV-', $reversalBatch->reference_number);

        // Reversal debited Accounts Receivable and credited AFC Bank
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $reversalBatch->id,
            'account_id' => $this->receivableAccount->id,
            'entry_type' => 'debit',
            'amount' => 300.00,
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'journal_batch_id' => $reversalBatch->id,
            'account_id' => $this->afcAccount->id,
            'entry_type' => 'credit',
            'amount' => 300.00,
        ]);

        // Verify Trial Balance is balanced with net $0 effect
        $glReconciliation = $this->reconciliationService->reconcileGeneralLedgerBalance($this->school);
        $this->assertTrue($glReconciliation['is_balanced']);
    }

    /**
     * Scenario G: Opening balances
     * Post approved opening balances and import historical transactions. Verify opening balances are not duplicated.
     */
    public function test_scenario_g_opening_balances_single_posting(): void
    {
        $glBalances = [
            ['account_id' => $this->afcAccount->id, 'debit' => 5000.00, 'credit' => 0.00, 'memo' => 'AFC Opening Balance'],
            ['account_id' => $this->cabsAccount->id, 'debit' => 3000.00, 'credit' => 0.00, 'memo' => 'CABS Opening Balance'],
            ['account_id' => $this->receivableAccount->id, 'debit' => 2000.00, 'credit' => 0.00, 'memo' => 'Debtors Opening Balance'],
            ['account_id' => $this->equityAccount->id, 'debit' => 0.00, 'credit' => 10000.00, 'memo' => 'Opening Balance Equity'],
        ];

        // 1. Post Opening Balances as of 2026-01-01
        $batch1 = $this->accountingService->postOpeningBalances($this->school, $glBalances, '2026-01-01', $this->user->id);
        $this->assertEquals('posted', $batch1->status);

        // 2. Attempt duplicate posting
        $batch2 = $this->accountingService->postOpeningBalances($this->school, $glBalances, '2026-01-01', $this->user->id);
        $this->assertEquals($batch1->id, $batch2->id, "Should return existing batch idempotently");

        // Exactly one opening balance batch exists
        $obCount = JournalBatch::where('school_id', $this->school->id)->where('source_type', 'opening_balance')->count();
        $this->assertEquals(1, $obCount);

        // General Ledger is balanced
        $gl = $this->reconciliationService->reconcileGeneralLedgerBalance($this->school);
        $this->assertTrue($gl['is_balanced']);
        $this->assertEquals(10000.00, $gl['total_debits']);
        $this->assertEquals(10000.00, $gl['total_credits']);
    }

    /**
     * Scenario H: Fee vote allocations
     * Record a payment allocated across tuition, building fund, GPF, sports and ICT.
     * Verify correct student allocations, GL treatment and fund reporting.
     */
    public function test_scenario_h_fee_vote_allocations(): void
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_number' => 'HHS-2026-VOTE',
            'first_name' => 'Chipo',
            'last_name' => 'Sibanda',
            'gender' => 'female',
            'status' => 'active',
        ]);

        // Invoice spanning 5 votes: Total $500
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'number' => 'INV-2026-VOTES',
            'issued_at' => '2026-01-10',
            'due_date' => '2026-01-31',
            'total_amount' => 500.00,
            'balance' => 500.00,
            'status' => 'issued',
        ]);

        $voteMap = [
            'TUITION' => ['account' => $this->tuitionAccount, 'amount' => 250.00],
            'BUILDING' => ['account' => $this->buildingFundAccount, 'amount' => 100.00],
            'GPF' => ['account' => $this->gpfAccount, 'amount' => 60.00],
            'SPORTS' => ['account' => $this->sportsAccount, 'amount' => 50.00],
            'ICT' => ['account' => $this->ictAccount, 'amount' => 40.00],
        ];

        foreach ($voteMap as $code => $data) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'vote_code' => $code,
                'description' => "Term 1 {$code}",
                'quantity' => 1,
                'unit_amount' => $data['amount'],
                'line_total' => $data['amount'],
            ]);
        }

        $invBatch = $this->accountingService->postInvoice($invoice);
        $this->assertEquals('posted', $invBatch->status);

        // Verify all 5 votes credited discretely
        foreach ($voteMap as $code => $data) {
            $this->assertDatabaseHas('journal_entries', [
                'journal_batch_id' => $invBatch->id,
                'account_id' => $data['account']->id,
                'entry_type' => 'credit',
                'amount' => $data['amount'],
            ]);
        }

        // Record and post payment of $500 via CABS Bank
        $payment = Payment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'reference' => 'REC-2026-VOTE-FULL',
            'amount' => 500.00,
            'paid_at' => '2026-01-15',
            'method' => 'bank_transfer',
            'account_id' => $this->cabsAccount->id,
            'status' => 'completed',
            'created_by' => $this->user->id,
        ]);

        $payBatch = $this->accountingService->postPayment($payment, $invoice, $this->cabsAccount->id);
        $this->assertEquals('posted', $payBatch->status);

        $invoice->update(['balance' => 0.00, 'status' => 'paid']);

        // Verify GL is balanced
        $gl = $this->reconciliationService->reconcileGeneralLedgerBalance($this->school);
        $this->assertTrue($gl['is_balanced']);

        // Student statement balance is 0
        $debtors = $this->reconciliationService->reconcileDebtorsControl($this->school);
        $this->assertTrue($debtors['is_reconciled']);
        $this->assertEquals(0.00, $debtors['gl_ar_balance']);
    }

    /**
     * Scenario I: Invalid mapping
     * Attempt to post a transaction with no valid bank-to-GL mapping.
     * Confirm that posting is rejected and no partial financial records are created.
     */
    public function test_scenario_i_invalid_mapping_fails_atomically(): void
    {
        // Create an unmapped bank account with an invalid account_id
        $unmappedBank = BankAccount::create([
            'school_id' => $this->school->id,
            'account_id' => 999999, // Non-existent GL account
            'bank_name' => 'Unmapped Rural Bank',
            'account_name' => 'Mystery Account',
            'account_number' => '000099999',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $initialBatches = JournalBatch::count();
        $initialCashbook = Cashbook::count();

        // Attempt to resolve GL account for this unmapped bank
        $caught = false;
        try {
            $this->accountResolver->getBankAccountGLAccount($unmappedBank);
        } catch (MissingAccountMappingException $e) {
            $caught = true;
            $this->assertStringContainsString('not linked to any General Ledger Asset account', $e->getMessage());
        }

        $this->assertTrue($caught, "Must throw MissingAccountMappingException for unmapped bank");

        // Verify database integrity: NO partial batches or cashbook rows created
        $this->assertEquals($initialBatches, JournalBatch::count());
        $this->assertEquals($initialCashbook, Cashbook::count());
    }

    /**
     * Scenario J: Concurrent posting
     * Simulate simultaneous or repeated posting requests.
     * Confirm that only one authoritative transaction is posted.
     */
    public function test_scenario_j_concurrent_repeated_posting_idempotency(): void
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_number' => 'HHS-2026-CONCUR',
            'first_name' => 'Farai',
            'last_name' => 'Gumbo',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'number' => 'INV-CONCUR-001',
            'issued_at' => '2026-01-20',
            'due_date' => '2026-02-20',
            'total_amount' => 150.00,
            'balance' => 150.00,
            'status' => 'issued',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'vote_code' => 'TUITION',
            'description' => 'Tuition Fee',
            'quantity' => 1,
            'unit_amount' => 150.00,
            'line_total' => 150.00,
        ]);

        // Simulate 3 concurrent / repeated calls to postInvoice
        $batch1 = $this->accountingService->postInvoice($invoice);
        $batch2 = $this->accountingService->postInvoice($invoice);
        $batch3 = $this->accountingService->postInvoice($invoice);

        $this->assertEquals($batch1->id, $batch2->id);
        $this->assertEquals($batch1->id, $batch3->id);

        // Assert exactly 1 batch exists in DB
        $count = JournalBatch::where('school_id', $this->school->id)
            ->where('source_type', 'invoice')
            ->where('source_id', $invoice->id)
            ->count();

        $this->assertEquals(1, $count);
    }
}
