<?php

namespace Tests\Feature\Finance;

use App\Models\Account;
use App\Models\BankTransaction;
use App\Models\Cashbook;
use App\Models\CashbookTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\BankReconciliationService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankAccountsAndReconciliationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $bursar;
    private Student $student;
    private AccountingService $accountingService;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        $this->school = School::create([
            'name' => 'Hande High School',
            'code' => 'HHS',
        ]);

        $this->bursar = User::create([
            'school_id' => $this->school->id,
            'name' => 'Bursar User',
            'email' => 'bursar@handehigh.test',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->bursar->assignRole('bursar');

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Tariro',
            'last_name' => 'Chikore',
            'admission_number' => 'ADM-2026-700',
            'grade' => 'Form 1',
            'class_name' => 'Form 1A',
            'status' => 'active',
        ]);

        $this->accountingService = app(AccountingService::class);
        $this->accountingService->ensureChartOfAccountsExist($this->school);
    }

    public function test_can_create_bank_account_in_chart_of_accounts_without_500_error(): void
    {
        $response = $this->actingAs($this->bursar)->post(route('admin.accounts.store'), [
            'code' => '1388',
            'name' => 'Stanbic Operating Account',
            'type' => 'asset',
            'category' => 'Bank Account', // Human-friendly input that previously triggered 500
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $response->assertRedirect(route('admin.accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'school_id' => $this->school->id,
            'code' => '1388',
            'name' => 'Stanbic Operating Account',
            'category' => 'bank',
        ]);
    }

    public function test_can_create_bank_account_via_bank_accounts_controller(): void
    {
        $response = $this->actingAs($this->bursar)->post(route('admin.bank-accounts.store'), [
            'account_name' => 'CBZ Tuition Account',
            'bank_name' => 'CBZ Bank',
            'account_number' => '9876543210',
            'branch_name' => 'Harare Central',
            'account_type' => 'current',
            'currency' => 'USD',
            'opening_balance' => 2500.00,
        ]);

        $response->assertRedirect(route('admin.bank-accounts.index'));

        $account = Account::where('school_id', $this->school->id)
            ->where('name', 'like', 'CBZ Tuition Account%')
            ->first();

        $this->assertNotNull($account);
        $this->assertEquals('asset', $account->type);
        $this->assertEquals('bank', $account->category);
        $this->assertStringStartsWith('13', $account->code);

        // Verify opening balance created in both cashbook and cashbook_transactions
        $this->assertDatabaseHas('cashbook', [
            'school_id' => $this->school->id,
            'account_id' => $account->id,
            'amount' => 2500.00,
            'category' => 'opening_balance',
        ]);

        $this->assertDatabaseHas('cashbook_transactions', [
            'school_id' => $this->school->id,
            'account_id' => $account->id,
            'amount' => 2500.00,
            'transaction_type' => 'debit',
            'category' => 'income',
        ]);
    }

    public function test_record_payment_for_invoice_deposited_to_specific_bank_account(): void
    {
        // 1. Create a bank account
        $bankAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1305',
            'name' => 'Stanbic School Fees Account',
            'type' => 'asset',
            'category' => 'bank',
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        // 2. Create student invoice
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'number' => 'INV-2026-TEST01',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 500.00,
            'balance' => 500.00,
            'issued_at' => now()->toDateString(),
            'status' => 'issued',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Tuition Fees',
            'quantity' => 1,
            'unit_amount' => 500.00,
            'line_total' => 500.00,
            'category' => 'tuition',
        ]);

        $this->accountingService->postInvoice($invoice);

        // 3. Post payment with account_id pointing to the bank account
        $paymentData = [
            'amount' => 300.00,
            'method' => 'bank',
            'account_id' => $bankAccount->id,
            'reference' => 'STANBIC-DEP-9988',
            'paid_at' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->bursar)
            ->post(route('admin.payments.store', $invoice), $paymentData);

        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $response->assertSessionHas('receipt_url');

        // Verify payment record
        $payment = Payment::where('school_id', $this->school->id)
            ->where('reference', 'STANBIC-DEP-9988')
            ->first();

        $this->assertNotNull($payment);
        $this->assertEquals($bankAccount->id, $payment->account_id);
        $this->assertEquals(300.00, (float) $payment->amount);

        // Verify invoice balance updated
        $invoice->refresh();
        $this->assertEquals(200.00, (float) $invoice->balance);
        $this->assertEquals('partial', $invoice->status);

        // Verify Cashbook dual sync: entry in legacy Cashbook
        $this->assertDatabaseHas('cashbook', [
            'school_id' => $this->school->id,
            'account_id' => $bankAccount->id,
            'related_payment_id' => $payment->id,
            'amount' => 300.00,
            'category' => 'fees',
        ]);

        // Verify Cashbook dual sync: entry in CashbookTransaction for Bank Reconciliation
        $this->assertDatabaseHas('cashbook_transactions', [
            'school_id' => $this->school->id,
            'account_id' => $bankAccount->id,
            'amount' => 300.00,
            'reference_number' => 'STANBIC-DEP-9988',
            'transaction_type' => 'debit',
            'category' => 'income',
            'status' => 'unmatched',
        ]);
    }

    public function test_can_view_and_print_official_student_fee_receipt(): void
    {
        $bankAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1306',
            'name' => 'FBC School Fees Account',
            'type' => 'asset',
            'category' => 'bank',
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'number' => 'INV-2026-REC01',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'total_amount' => 450.00,
            'balance' => 0.00,
            'issued_at' => now()->toDateString(),
            'status' => 'paid',
        ]);

        $payment = Payment::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'account_id' => $bankAccount->id,
            'method' => 'bank',
            'reference' => 'FBC-SLIP-7711',
            'amount' => 450.00,
            'paid_at' => now()->toDateString(),
            'status' => 'completed',
        ]);

        \App\Models\PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 450.00,
        ]);

        $response = $this->actingAs($this->bursar)
            ->get(route('admin.payments.receipt', $payment));

        $response->assertOk();
        $response->assertSee('Official Fee Receipt');
        $response->assertSee('Tariro Chikore');
        $response->assertSee('FBC School Fees Account');
        $response->assertSee('FBC-SLIP-7711');
        $response->assertSee('450.00');
        $response->assertSee('INV-2026-REC01');
    }

    public function test_bank_reconciliation_sees_cashbook_transactions_and_auto_matches(): void
    {
        $bankAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1307',
            'name' => 'CBZ Main Bank Account',
            'type' => 'asset',
            'category' => 'bank',
            'currency' => 'USD',
            'is_active' => true,
            'is_postable' => true,
        ]);

        // Record a CashbookTransaction (as generated by student fee payment)
        $cashbookTx = CashbookTransaction::create([
            'school_id' => $this->school->id,
            'account_id' => $bankAccount->id,
            'transaction_date' => now()->toDateString(),
            'reference_number' => 'TX-RECON-100',
            'description' => 'Fee Payment for invoice INV-2026-0099 - Tariro Chikore',
            'amount' => 350.00,
            'transaction_type' => 'debit',
            'category' => 'income',
            'status' => 'unmatched',
        ]);

        // Record a Bank Statement transaction (as imported by bank statement)
        $bankTx = BankTransaction::create([
            'school_id' => $this->school->id,
            'account_id' => $bankAccount->id,
            'transaction_date' => now()->toDateString(),
            'reference_number' => 'TX-RECON-100',
            'description' => 'Fee Payment for invoice INV-2026-0099',
            'amount' => 350.00,
            'transaction_type' => 'credit', // Bank statement credit = money into bank
            'source' => 'bank_statement',
            'status' => 'unmatched',
        ]);

        $reconService = app(BankReconciliationService::class);
        $result = $reconService->autoMatchTransactions($bankAccount->id);

        $this->assertGreaterThanOrEqual(1, $result['matches_found']);

        $cashbookTx->refresh();
        $bankTx->refresh();

        $this->assertEquals('matched', $cashbookTx->status);
        $this->assertEquals('matched', $bankTx->status);
    }
}
