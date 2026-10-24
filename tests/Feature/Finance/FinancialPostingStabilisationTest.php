<?php

namespace Tests\Feature\Finance;

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Cashbook;
use App\Models\CashbookTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalBatch;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AccountingService;
use App\Services\BankReconciliationService;
use App\Services\Finance\CashbookReversalService;
use App\Services\Finance\FeeRevenueAccountResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FinancialPostingStabilisationTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $user;
    protected Account $afcAccount;
    protected Account $cabsAccount;
    protected Account $receivableAccount;
    protected Account $payableAccount;
    protected Account $tuitionAccount;
    protected Account $buildingFundAccount;
    protected Account $gpfAccount;
    protected Account $sportsAccount;
    protected Account $ictAccount;
    protected Account $scienceAccount;
    protected Account $boardingAccount;
    protected Account $bankChargesAccount;
    protected Account $otherRevenueAccount;

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
            'name' => 'School Admin',
        ]);
        $this->user->assignRole('super-admin');

        $this->actingAs($this->user);

        // Set up Chart of Accounts
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
            'code' => '3100',
            'name' => 'Accounts Payable',
            'type' => 'liability',
            'category' => 'payable',
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->tuitionAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5100',
            'name' => 'Tuition Fees',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->buildingFundAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5120',
            'name' => 'Building Fund Levy',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->sportsAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5130',
            'name' => 'Sports Levy',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->ictAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5140',
            'name' => 'Practical/ICT Levy',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->scienceAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5160',
            'name' => 'Science Levy',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->gpfAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5170',
            'name' => 'General Purpose Fund',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->boardingAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5200',
            'name' => 'Boarding Fees',
            'type' => 'revenue',
            'category' => 'tuition_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->bankChargesAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '7100',
            'name' => 'Bank Charges Expense',
            'type' => 'expense',
            'category' => 'other_expense',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->otherRevenueAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '5801',
            'name' => 'Other Operating Revenue',
            'type' => 'revenue',
            'category' => 'other_revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $this->afcBank = BankAccount::create([
            'school_id' => $this->school->id,
            'account_id' => $this->afcAccount->id,
            'bank_name' => 'AFC Commercial Bank',
            'account_name' => 'Hande High School AFC Operations',
            'account_number' => '1000289412',
            'opening_balance' => 5000.00,
            'current_balance' => 5000.00,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $this->cabsBank = BankAccount::create([
            'school_id' => $this->school->id,
            'account_id' => $this->cabsAccount->id,
            'bank_name' => 'CABS Bank',
            'account_name' => 'Hande High School CABS Development',
            'account_number' => '2000394821',
            'opening_balance' => 3000.00,
            'current_balance' => 3000.00,
            'currency' => 'USD',
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: BankReconciliationService debit and credit directions
     */
    public function test_bank_reconciliation_service_posts_correct_debit_and_credit_directions()
    {
        $recService = app(BankReconciliationService::class);

        // Case A: Cashbook Debit = Money Received into Bank (Deposit)
        // Bank (Asset) MUST BE DEBITED; Offset (Revenue/Receivable) MUST BE CREDITED.
        $depositTx = CashbookTransaction::create([
            'school_id' => $this->school->id,
            'account_id' => $this->afcAccount->id,
            'transaction_date' => '2026-02-10',
            'reference_number' => 'DEP-001',
            'description' => 'Interest Received',
            'amount' => 150.00,
            'transaction_type' => 'debit',
            'category' => 'interest',
        ]);

        $entryId = $recService->postCashbookTransactionToLedger($depositTx);
        $this->assertNotNull($entryId);

        $depositTx->refresh();
        $this->assertEquals($entryId, $depositTx->journal_entry_id);

        $batch = JournalBatch::where('source_type', 'reconciliation')
            ->where('source_id', $depositTx->id)
            ->first();
        $this->assertNotNull($batch);

        $debitEntry = $batch->entries->firstWhere('entry_type', 'debit');
        $creditEntry = $batch->entries->firstWhere('entry_type', 'credit');

        // Asset MUST be debited (cash in bank increases)
        $this->assertEquals($this->afcAccount->id, $debitEntry->account_id);
        $this->assertEquals(150.00, (float) $debitEntry->amount);

        // Revenue/Offset MUST be credited
        $this->assertNotEquals($this->afcAccount->id, $creditEntry->account_id);
        $this->assertEquals(150.00, (float) $creditEntry->amount);

        // Case B: Cashbook Credit = Money Outflow (e.g. Bank Charge)
        // Offset (Expense) MUST BE DEBITED; Bank (Asset) MUST BE CREDITED.
        $chargeTx = CashbookTransaction::create([
            'school_id' => $this->school->id,
            'account_id' => $this->cabsAccount->id,
            'transaction_date' => '2026-02-12',
            'reference_number' => 'CHG-001',
            'description' => 'Monthly Ledger Maintenance Fee',
            'amount' => 25.00,
            'transaction_type' => 'credit',
            'category' => 'bank_charge',
        ]);

        $chargeEntryId = $recService->postCashbookTransactionToLedger($chargeTx);
        $this->assertNotNull($chargeEntryId);

        $chargeBatch = JournalBatch::where('source_type', 'reconciliation')
            ->where('source_id', $chargeTx->id)
            ->first();

        $chargeDebit = $chargeBatch->entries->firstWhere('entry_type', 'debit');
        $chargeCredit = $chargeBatch->entries->firstWhere('entry_type', 'credit');

        // Expense MUST be debited
        $this->assertEquals($this->bankChargesAccount->id, $chargeDebit->account_id);
        $this->assertEquals(25.00, (float) $chargeDebit->amount);

        // Bank (Asset) MUST be credited (money in bank decreases)
        $this->assertEquals($this->cabsAccount->id, $chargeCredit->account_id);
        $this->assertEquals(25.00, (float) $chargeCredit->amount);
    }

    /**
     * Test 2: Bank Account to GL Account Mapping
     */
    public function test_bank_accounts_map_to_chart_of_accounts_including_afc_and_cabs()
    {
        $this->assertEquals('1321', $this->afcBank->account->code);
        $this->assertEquals('1322', $this->cabsBank->account->code);
        $this->assertEquals($this->afcAccount->id, $this->afcBank->account_id);
        $this->assertEquals($this->cabsAccount->id, $this->cabsBank->account_id);

        // Also test creating another bank (e.g. ZB Bank mapped to 1323)
        $zbAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1323',
            'name' => 'Cash at ZB Bank',
            'type' => 'asset',
            'category' => 'bank',
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $zbBank = BankAccount::create([
            'school_id' => $this->school->id,
            'account_id' => $zbAccount->id,
            'bank_name' => 'ZB Bank',
            'account_name' => 'Hande High School ZB Account',
            'account_number' => '3000491823',
            'opening_balance' => 1000.00,
            'current_balance' => 1000.00,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $this->assertEquals('1323', $zbBank->account->code);
        $this->assertEquals($zbAccount->id, $zbBank->account_id);
    }

    /**
     * Test 3: Idempotency for payments, receipts, bill payments and bank rec
     */
    public function test_financial_postings_are_strictly_idempotent()
    {
        $accountingService = app(AccountingService::class);

        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_number' => 'ADM-2026-001',
            'first_name' => 'Tendai',
            'last_name' => 'Moyo',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'number' => 'INV-2026-001',
            'issue_date' => '2026-01-10',
            'due_date' => '2026-01-31',
            'total_amount' => 470.00,
            'paid_amount' => 0,
            'balance' => 470.00,
            'status' => 'issued',
        ]);

        $payment = Payment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'account_id' => $this->afcAccount->id,
            'amount' => 200.00,
            'paid_at' => '2026-01-15',
            'method' => 'bank',
            'reference' => 'PAY-HHS-001',
        ]);

        // Post payment 1st time
        $batch1 = $accountingService->postPayment($payment, $invoice);
        // Post payment 2nd time (simulating accidental re-trigger or concurrent duplicate)
        $batch2 = $accountingService->postPayment($payment, $invoice);

        $this->assertEquals($batch1->id, $batch2->id);
        $this->assertEquals(1, JournalBatch::where('source_type', 'payment')->where('source_id', $payment->id)->count());

        // Test Receipt Idempotency
        $receipt = Receipt::create([
            'school_id' => $this->school->id,
            'type' => 'other',
            'bank_account_id' => $this->cabsBank->id,
            'receipt_number' => 'REC-GEN-001',
            'receipt_date' => '2026-01-18',
            'payment_method' => 'bank_transfer',
            'grand_total' => 350.00,
            'customer_name' => 'Ministry of Primary Education',
        ]);

        $rBatch1 = $accountingService->postReceipt($receipt);
        $rBatch2 = $accountingService->postReceipt($receipt);

        $this->assertEquals($rBatch1->id, $rBatch2->id);
        $this->assertEquals(1, JournalBatch::where('source_type', 'receipt')->where('source_id', $receipt->id)->count());
    }

    /**
     * Test 4: Complete supplier bill payment workflow with GL and cashbook integration
     */
    public function test_complete_supplier_bill_payment_workflow_with_gl_and_cashbook_sync()
    {
        $vendor = Vendor::create([
            'school_id' => $this->school->id,
            'code' => 'VEND-001',
            'name' => 'Chiredzi Stationers',
            'is_active' => true,
        ]);

        $bill = Bill::create([
            'school_id' => $this->school->id,
            'vendor_id' => $vendor->id,
            'bill_number' => 'BILL-2026-101',
            'bill_date' => '2026-02-01',
            'due_date' => '2026-02-28',
            'total_amount' => 500.00,
            'paid_amount' => 0.00,
            'balance' => 500.00,
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        // Post bill payment via controller
        $response = $this->post(route('admin.bills.payments.store', $bill), [
            'amount' => 300.00,
            'payment_date' => '2026-02-05',
            'payment_method' => 'bank_transfer',
            'account_id' => $this->cabsAccount->id,
            'reference' => 'CABS-TRF-88291',
            'notes' => 'Part payment for stationery supplies',
        ]);

        $response->assertRedirect(route('admin.bills.show', $bill));
        $response->assertSessionHas('status');

        $bill->refresh();
        $this->assertEquals(300.00, (float) $bill->paid_amount);
        $this->assertEquals(200.00, (float) $bill->balance);
        $this->assertEquals('partial', $bill->status);

        $payment = BillPayment::where('bill_id', $bill->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals($this->cabsAccount->id, $payment->account_id);
        $this->assertNotNull($payment->journal_batch_id);

        // Verify GL Journal Batch
        $batch = JournalBatch::find($payment->journal_batch_id);
        $this->assertNotNull($batch);
        $this->assertEquals('bill_payment', $batch->source_type);

        $debitEntry = $batch->entries->firstWhere('entry_type', 'debit');
        $creditEntry = $batch->entries->firstWhere('entry_type', 'credit');

        // Accounts Payable (3100) debited
        $this->assertEquals($this->payableAccount->id, $debitEntry->account_id);
        $this->assertEquals(300.00, (float) $debitEntry->amount);

        // CABS Bank (1322) credited
        $this->assertEquals($this->cabsAccount->id, $creditEntry->account_id);
        $this->assertEquals(300.00, (float) $creditEntry->amount);

        // Verify Cashbook expense record
        $cbEntry = Cashbook::where('school_id', $this->school->id)
            ->where('account_id', $this->cabsAccount->id)
            ->where('reference_number', 'CABS-TRF-88291')
            ->first();
        $this->assertNotNull($cbEntry);
        $this->assertEquals('expense', $cbEntry->transaction_type);
        $this->assertEquals(300.00, (float) $cbEntry->amount);

        // Verify CashbookTransaction credit record
        $cbTx = CashbookTransaction::where('school_id', $this->school->id)
            ->where('account_id', $this->cabsAccount->id)
            ->where('reference_number', 'CABS-TRF-88291')
            ->first();
        $this->assertNotNull($cbTx);
        $this->assertEquals('credit', $cbTx->transaction_type);
        $this->assertEquals('expense', $cbTx->category);
        $this->assertEquals(300.00, (float) $cbTx->amount);
    }

    /**
     * Test 5: Safe cashbook reversal
     */
    public function test_safe_reversal_of_posted_cashbook_transactions()
    {
        $cashbook = Cashbook::create([
            'school_id' => $this->school->id,
            'account_id' => $this->afcAccount->id,
            'transaction_type' => 'expense',
            'category' => 'supplies',
            'description' => 'Erroneous Office Stationers charge',
            'amount' => 120.00,
            'balance_after' => 880.00,
            'transaction_date' => '2026-02-15',
            'payment_method' => 'bank_transfer',
            'reference_number' => 'ERR-001',
            'voucher_number' => 'VOUCH-001',
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // Post original transaction to GL
        app(AccountingService::class)->postManualCashbookTransaction($cashbook);

        // Safe reversal via service
        $reversalService = app(CashbookReversalService::class);
        $revBatch = $reversalService->reverse($cashbook, 'Duplicate voucher entry identified by auditor');

        $cashbook->refresh();
        $this->assertEquals('reversed', $cashbook->status);
        $this->assertNotNull($cashbook->reversed_at);
        $this->assertEquals($this->user->id, $cashbook->reversed_by);
        $this->assertNotNull($cashbook->reversal_batch_id);

        // Check reversal journal batch
        $this->assertNotNull($revBatch);
        $this->assertEquals('cashbook_reversal', $revBatch->source_type);
        $this->assertEquals('posted', $revBatch->status);

        // Offset cashbook entry was created to restore balance
        $offsetCb = Cashbook::where('school_id', $this->school->id)
            ->where('category', 'reversal')
            ->where('reference_number', 'REV-ERR-001')
            ->first();
        $this->assertNotNull($offsetCb);
        $this->assertEquals('income', $offsetCb->transaction_type);
        $this->assertEquals(120.00, (float) $offsetCb->amount);

        // Attempting to reverse again throws exception
        $this->expectException(\InvalidArgumentException::class);
        $reversalService->reverse($cashbook);
    }

    /**
     * Test 6: Consistent accounting-date filtering on Trial Balance
     */
    public function test_trial_balance_filters_strictly_by_accounting_transaction_date()
    {
        $accountingService = app(AccountingService::class);

        // Batch 1: Backdated transaction on 2026-01-05
        $batch1 = $accountingService->createJournalBatch([
            'school_id' => $this->school->id,
            'transaction_date' => '2026-01-05',
            'description' => 'January Bank Deposit',
            'status' => 'posted',
            'created_by' => $this->user->id,
            'entries' => [
                ['account_id' => $this->afcAccount->id, 'entry_type' => 'debit', 'amount' => 1000.00],
                ['account_id' => $this->otherRevenueAccount->id, 'entry_type' => 'credit', 'amount' => 1000.00],
            ],
        ]);

        // Batch 2: Later transaction on 2026-02-15
        $batch2 = $accountingService->createJournalBatch([
            'school_id' => $this->school->id,
            'transaction_date' => '2026-02-15',
            'description' => 'February Bank Deposit',
            'status' => 'posted',
            'created_by' => $this->user->id,
            'entries' => [
                ['account_id' => $this->afcAccount->id, 'entry_type' => 'debit', 'amount' => 2000.00],
                ['account_id' => $this->otherRevenueAccount->id, 'entry_type' => 'credit', 'amount' => 2000.00],
            ],
        ]);

        // Request Trial Balance as of 2026-01-31
        $reportController = app(\App\Http\Controllers\Admin\FinancialReportController::class);
        $request = Request::create(route('admin.reports.trial-balance'), 'GET', ['date' => '2026-01-31']);

        $view = $reportController->trialBalance($request);
        $rows = $view->getData()['rows'];

        $afcRow = $rows->firstWhere('account.id', $this->afcAccount->id);
        $this->assertNotNull($afcRow);
        // Only the $1,000 from Jan 5th should be present; the $2,000 from Feb 15th must be excluded!
        $this->assertEquals(1000.00, $afcRow['debit']);
    }

    /**
     * Test 7: Fee Revenue Account Resolver maps Hande High vote codes
     */
    public function test_fee_revenue_account_resolver_maps_vote_codes_to_discrete_gl_accounts()
    {
        $resolver = app(FeeRevenueAccountResolver::class);

        $votes = [
            'BUILDING_FUND' => '5120',
            'GPF_ADMIN' => '5170',
            'SPORTS' => '5130',
            'PRACTICALS_ICT' => '5140',
            'SCIENCE_LEVY' => '5160',
            'BOARDING_FEES' => '5200',
            'TUITION' => '5100',
        ];

        foreach ($votes as $voteCode => $expectedCode) {
            $code = $resolver->resolveCode('levy', null, 'Levy item', $voteCode);
            $this->assertEquals($expectedCode, $code, "Vote code {$voteCode} did not resolve to expected GL code {$expectedCode}");
        }
    }
}
