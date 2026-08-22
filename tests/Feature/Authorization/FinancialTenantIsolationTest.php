<?php

namespace Tests\Feature\Authorization;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use App\Models\Bill;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Cashbook;
use App\Models\CashbookTransaction;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\Loan;
use App\Models\Payroll;
use App\Models\Project;
use App\Models\Receipt;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinancialTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;

    protected function setUp(): void
    {
        parent::setUp();

        if (! \Illuminate\Support\Facades\Schema::hasTable('academic_years')) {
            \Illuminate\Support\Facades\Schema::create('academic_years', function ($table) {
                $table->id();
                $table->timestamps();
            });
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('school_classes')) {
            \Illuminate\Support\Facades\Schema::create('school_classes', function ($table) {
                $table->id();
                $table->timestamps();
            });
        }

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        $this->schoolA = School::create(['name' => 'Finance School A', 'code' => 'FINA']);
        $this->schoolB = School::create(['name' => 'Finance School B', 'code' => 'FINB']);
    }

    public function test_bank_reconciliation_rejects_foreign_account(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);
        $foreignAccountB = $this->makeAccount($this->schoolB, '1001-B', 'bank');

        $this->actingAs($adminA)
            ->post(route('admin.bank-reconciliations.store'), [
                'bank_account_id' => $foreignAccountB->id,
                'reconciliation_date' => '2026-08-01',
                'book_balance' => 1000,
                'bank_balance' => 1000,
            ])
            ->assertSessionHasErrors('bank_account_id');

        $this->assertDatabaseMissing('bank_reconciliations', [
            'bank_account_id' => $foreignAccountB->id,
        ]);
    }

    public function test_bank_matching_and_unmatching_rejects_foreign_transaction(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);
        $accountA = $this->makeAccount($this->schoolA, '1002-A', 'bank');
        $accountB = $this->makeAccount($this->schoolB, '1002-B', 'bank');

        $bankTxA = BankTransaction::create([
            'school_id' => $this->schoolA->id,
            'account_id' => $accountA->id,
            'transaction_date' => '2026-08-01',
            'description' => 'Tx A',
            'amount' => 500,
            'transaction_type' => 'debit',
            'source' => 'manual',
            'status' => 'unmatched',
        ]);

        $cashbookTxB = CashbookTransaction::create([
            'school_id' => $this->schoolB->id,
            'account_id' => $accountB->id,
            'transaction_date' => '2026-08-01',
            'description' => 'Tx B',
            'amount' => 500,
            'transaction_type' => 'debit',
            'category' => 'other',
            'status' => 'unmatched',
        ]);

        // Attempt matching School A bank tx with School B cashbook tx
        $this->actingAs($adminA)
            ->post(route('admin.bank-reconciliations.manual-match'), [
                'bank_transaction_id' => $bankTxA->id,
                'cashbook_transaction_id' => $cashbookTxB->id,
                'match_amount' => 500,
            ])
            ->assertSessionHasErrors('cashbook_transaction_id');

        $this->assertDatabaseMissing('transaction_matches', [
            'bank_transaction_id' => $bankTxA->id,
            'cashbook_transaction_id' => $cashbookTxB->id,
        ]);

        // Attempt unmatching with foreign tx ID
        $this->actingAs($adminA)
            ->post(route('admin.bank-reconciliations.unmatch'), [
                'bank_transaction_id' => $bankTxA->id,
                'cashbook_transaction_id' => $cashbookTxB->id,
            ])
            ->assertSessionHasErrors('cashbook_transaction_id');
    }

    public function test_cashbook_create_and_update_rejects_foreign_accounts(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);
        $accountA = $this->makeAccount($this->schoolA, '1003-A', 'bank');
        $foreignAccountB = $this->makeAccount($this->schoolB, '1003-B', 'bank');

        $foreignBankAccB = BankAccount::create([
            'school_id' => $this->schoolB->id,
            'account_name' => 'Foreign Bank B',
            'account_number' => 'ACC-B01',
            'bank_name' => 'Bank B',
        ]);

        // Create with foreign account
        $this->actingAs($adminA)
            ->post(route('admin.cashbook.store'), [
                'account_id' => $foreignAccountB->id,
                'transaction_type' => 'income',
                'category' => 'other',
                'description' => 'Cashbook Entry',
                'amount' => 200,
                'transaction_date' => '2026-08-01',
                'payment_method' => 'cash',
            ])
            ->assertSessionHasErrors('account_id');

        // Create valid cashbook
        $cashbookA = Cashbook::create([
            'school_id' => $this->schoolA->id,
            'account_id' => $accountA->id,
            'transaction_type' => 'income',
            'category' => 'other',
            'description' => 'Valid Entry',
            'amount' => 200,
            'balance_after' => 200,
            'transaction_date' => '2026-08-01',
            'payment_method' => 'cash',
        ]);

        // Update with foreign bank account
        $this->actingAs($adminA)
            ->put(route('admin.cashbook.update', $cashbookA), [
                'bank_account_id' => $foreignBankAccB->id,
                'transaction_type' => 'income',
                'category' => 'other',
                'description' => 'Updated Entry',
                'amount' => 300,
                'transaction_date' => '2026-08-01',
                'payment_method' => 'cash',
            ])
            ->assertSessionHasErrors('bank_account_id');
    }

    public function test_interbank_transfer_rejects_foreign_accounts_and_preserves_atomicity(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);
        $accountA1 = $this->makeAccount($this->schoolA, '1301-A', 'bank');
        $accountA2 = $this->makeAccount($this->schoolA, '1302-A', 'bank');
        $foreignAccountB = $this->makeAccount($this->schoolB, '1301-B', 'bank');

        // Reject foreign source account
        $this->actingAs($adminA)
            ->post(route('admin.interbank-transfers.store'), [
                'transfer_date' => '2026-08-01',
                'from_bank_account_id' => $foreignAccountB->id,
                'to_bank_account_id' => $accountA2->id,
                'amount' => 500,
            ])
            ->assertSessionHasErrors('from_bank_account_id');

        // Reject foreign destination account
        $this->actingAs($adminA)
            ->post(route('admin.interbank-transfers.store'), [
                'transfer_date' => '2026-08-01',
                'from_bank_account_id' => $accountA1->id,
                'to_bank_account_id' => $foreignAccountB->id,
                'amount' => 500,
            ])
            ->assertSessionHasErrors('to_bank_account_id');

        // Valid same-tenant transfer succeeds atomically
        $this->actingAs($adminA)
            ->post(route('admin.interbank-transfers.store'), [
                'transfer_date' => '2026-08-01',
                'from_bank_account_id' => $accountA1->id,
                'to_bank_account_id' => $accountA2->id,
                'amount' => 500,
                'description' => 'Same-tenant Transfer',
            ])
            ->assertRedirect(route('admin.interbank-transfers.index'));

        $this->assertDatabaseHas('interbank_transfers', [
            'school_id' => $this->schoolA->id,
            'from_bank_account_id' => $accountA1->id,
            'to_bank_account_id' => $accountA2->id,
            'amount' => 500,
        ]);
    }

    public function test_journal_creation_rejects_foreign_account_in_nested_lines_and_creates_no_records(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);
        $accountA = $this->makeAccount($this->schoolA, '1004-A', 'bank');
        $foreignAccountB = $this->makeAccount($this->schoolB, '1004-B', 'bank');

        $initialBatches = JournalBatch::count();
        $initialEntries = JournalEntry::count();

        $this->actingAs($adminA)
            ->post(route('admin.journals.store'), [
                'transaction_date' => '2026-08-01',
                'description' => 'Cross-tenant Journal Attempt',
                'entries' => [
                    [
                        'account_id' => $accountA->id,
                        'entry_type' => 'debit',
                        'amount' => 1000,
                    ],
                    [
                        'account_id' => $foreignAccountB->id,
                        'entry_type' => 'credit',
                        'amount' => 1000,
                    ],
                ],
            ])
            ->assertSessionHasErrors('entries.1.account_id');

        // Verify no batch or entries were created
        $this->assertEquals($initialBatches, JournalBatch::count());
        $this->assertEquals($initialEntries, JournalEntry::count());
    }

    public function test_budget_lines_reject_foreign_account_department_and_project(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);
        $budgetA = Budget::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Budget Isolation A',
            'fiscal_year' => 2026,
            'status' => 'draft',
        ]);

        $accountA = $this->makeAccount($this->schoolA, '1005-A', 'other_expense');
        $deptA = Department::create(['school_id' => $this->schoolA->id, 'name' => 'Science A']);
        $foreignAccountB = $this->makeAccount($this->schoolB, '1005-B', 'other_expense');
        $foreignDeptB = Department::create(['school_id' => $this->schoolB->id, 'name' => 'Science B']);
        $foreignProjB = Project::create(['school_id' => $this->schoolB->id, 'name' => 'Project B', 'code' => 'PRJ-B']);

        // Reject foreign account
        $this->actingAs($adminA)
            ->post(route('admin.budgets.lines.store', $budgetA), [
                'account_id' => $foreignAccountB->id,
                'cost_center_id' => $deptA->id,
                'budgeted_amount' => 5000,
            ])
            ->assertSessionHasErrors('account_id');

        // Reject foreign department
        $this->actingAs($adminA)
            ->post(route('admin.budgets.lines.store', $budgetA), [
                'account_id' => $accountA->id,
                'cost_center_id' => $foreignDeptB->id,
                'budgeted_amount' => 5000,
            ])
            ->assertSessionHasErrors('cost_center_id');

        // Reject foreign project
        $this->actingAs($adminA)
            ->post(route('admin.budgets.lines.store', $budgetA), [
                'account_id' => $accountA->id,
                'project_id' => $foreignProjB->id,
                'budgeted_amount' => 5000,
            ])
            ->assertSessionHasErrors('project_id');

        // Cross-budget line deletion rejected (404)
        $budgetB = Budget::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Budget B',
            'fiscal_year' => 2026,
            'status' => 'draft',
        ]);
        $lineB = BudgetLine::create([
            'budget_id' => $budgetB->id,
            'account_id' => $foreignAccountB->id,
            'budgeted_amount' => 1000,
        ]);

        $this->actingAs($adminA)
            ->delete(route('admin.budgets.lines.destroy', ['budget' => $budgetA, 'line' => $lineB]))
            ->assertNotFound();
    }

    public function test_credit_note_rejects_foreign_customer_student_and_invoice(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);

        $customerA = Customer::create([
            'school_id' => $this->schoolA->id,
            'code' => 'CUST-A',
            'name' => 'Customer A',
            'customer_type' => 'individual',
            'payment_terms' => 'net_30',
        ]);

        $studentA = Student::create(['school_id' => $this->schoolA->id, 'first_name' => 'Sam', 'last_name' => 'A']);
        $foreignCustomerB = Customer::create([
            'school_id' => $this->schoolB->id,
            'code' => 'CUST-B',
            'name' => 'Customer B',
            'customer_type' => 'individual',
            'payment_terms' => 'net_30',
        ]);
        $foreignStudentB = Student::create(['school_id' => $this->schoolB->id, 'first_name' => 'Sally', 'last_name' => 'B']);
        $foreignInvoiceB = Invoice::create([
            'school_id' => $this->schoolB->id,
            'student_id' => $foreignStudentB->id,
            'number' => 'INV-FOREIGN',
        ]);

        // Reject foreign customer
        $this->actingAs($adminA)
            ->post(route('admin.credit-notes.store'), [
                'type' => 'customer',
                'customer_id' => $foreignCustomerB->id,
                'total_amount' => 100,
                'credit_note_date' => '2026-08-01',
                'reason' => 'Test',
                'status' => 'draft',
            ])
            ->assertSessionHasErrors('customer_id');

        // Reject foreign student
        $this->actingAs($adminA)
            ->post(route('admin.credit-notes.store'), [
                'type' => 'student',
                'student_id' => $foreignStudentB->id,
                'total_amount' => 100,
                'credit_note_date' => '2026-08-01',
                'reason' => 'Test',
                'status' => 'draft',
            ])
            ->assertSessionHasErrors('student_id');

        // Reject foreign invoice
        $this->actingAs($adminA)
            ->post(route('admin.credit-notes.store'), [
                'type' => 'customer',
                'customer_id' => $customerA->id,
                'invoice_id' => $foreignInvoiceB->id,
                'total_amount' => 100,
                'credit_note_date' => '2026-08-01',
                'reason' => 'Test',
                'status' => 'draft',
            ])
            ->assertSessionHasErrors('invoice_id');
    }

    public function test_invoice_and_receipt_submitted_foreign_ids_are_rejected(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);
        $foreignStudentB = Student::create(['school_id' => $this->schoolB->id, 'first_name' => 'Foreign', 'last_name' => 'Student']);
        $foreignCustomerB = Customer::create([
            'school_id' => $this->schoolB->id,
            'code' => 'CUST-B2',
            'name' => 'Customer B2',
            'customer_type' => 'individual',
            'payment_terms' => 'net_30',
        ]);
        $foreignBankAccB = BankAccount::create([
            'school_id' => $this->schoolB->id,
            'account_name' => 'Bank B2',
            'account_number' => '1234-B2',
            'bank_name' => 'Bank B',
        ]);

        // Invoice store rejects foreign student
        $this->actingAs($adminA)
            ->post(route('admin.invoices.store'), [
                'student_id' => $foreignStudentB->id,
                'academic_year' => '2026',
                'term' => 'Term 1',
                'issued_at' => '2026-08-01',
                'items' => [
                    ['description' => 'Tuition', 'quantity' => 1, 'unit_amount' => 500],
                ],
            ])
            ->assertSessionHasErrors('student_id');

        // Receipt store rejects foreign customer and foreign bank account
        $this->actingAs($adminA)
            ->post(route('admin.receipts.store'), [
                'receipt_date' => '2026-08-01',
                'type' => 'sale',
                'customer_id' => $foreignCustomerB->id,
                'payment_method' => 'cash',
                'items' => [
                    ['description' => 'Uniform', 'quantity' => 1, 'unit_price' => 50],
                ],
            ])
            ->assertSessionHasErrors('customer_id');

        $this->actingAs($adminA)
            ->post(route('admin.receipts.store'), [
                'receipt_date' => '2026-08-01',
                'type' => 'sale',
                'payment_method' => 'bank_transfer',
                'bank_account_id' => $foreignBankAccB->id,
                'items' => [
                    ['description' => 'Uniform', 'quantity' => 1, 'unit_price' => 50],
                ],
            ])
            ->assertSessionHasErrors('bank_account_id');
    }

    public function test_bill_payroll_and_loan_submitted_foreign_ids_are_rejected(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);
        $foreignVendorB = Vendor::create([
            'school_id' => $this->schoolB->id,
            'code' => 'VEND-B',
            'name' => 'Vendor B',
            'vendor_type' => 'supplier',
            'payment_terms' => 'net_30',
        ]);
        $foreignEmployeeB = Employee::create([
            'school_id' => $this->schoolB->id,
            'employee_id' => 'EMP-B01',
            'first_name' => 'Foreign',
            'last_name' => 'Employee',
            'email' => 'emp.b@test.test',
            'phone' => '1234567890',
            'position' => 'Teacher',
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'date_of_birth' => '1990-01-01',
            'hire_date' => '2025-01-01',
            'salary' => 1000,
            'status' => 'active',
        ]);

        // Bill rejects foreign vendor
        $this->actingAs($adminA)
            ->post(route('admin.bills.store'), [
                'vendor_id' => $foreignVendorB->id,
                'bill_number' => 'BILL-001',
                'bill_date' => '2026-08-01',
                'due_date' => '2026-08-30',
                'total_amount' => 1200,
            ])
            ->assertSessionHasErrors('vendor_id');

        // Payroll rejects foreign employee in batch
        $this->actingAs($adminA)
            ->post(route('admin.payrolls.process'), [
                'period_month' => 8,
                'period_year' => 2026,
                'processed_date' => '2026-08-25',
                'employees' => [
                    ['id' => $foreignEmployeeB->id, 'basic_salary' => 1000],
                ],
            ])
            ->assertSessionHasErrors('employees.0.id');

        // Loan rejects foreign employee
        $this->actingAs($adminA)
            ->post(route('admin.loans.store'), [
                'employee_id' => $foreignEmployeeB->id,
                'loan_type' => 'school',
                'loan_amount' => 2000,
                'interest_rate' => 5,
                'repayment_period_months' => 12,
                'disbursed_date' => '2026-08-01',
            ])
            ->assertSessionHasErrors('employee_id');
    }

    public function test_request_school_id_cannot_override_financial_mutation_tenant(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);
        $accountA = $this->makeAccount($this->schoolA, '1006-A', 'bank');

        // Attempt injecting School B's ID in payload
        $this->actingAs($adminA)
            ->post(route('admin.cashbook.store'), [
                'school_id' => $this->schoolB->id,
                'account_id' => $accountA->id,
                'transaction_type' => 'income',
                'category' => 'other',
                'description' => 'Injection Test',
                'amount' => 500,
                'transaction_date' => '2026-08-01',
                'payment_method' => 'cash',
            ])
            ->assertRedirect(route('admin.cashbook.index'));

        // Verify entry was saved under School A, NOT School B
        $this->assertDatabaseHas('cashbook', [
            'description' => 'Injection Test',
            'school_id' => $this->schoolA->id,
        ]);
        $this->assertDatabaseMissing('cashbook', [
            'description' => 'Injection Test',
            'school_id' => $this->schoolB->id,
        ]);
    }

    public function test_super_admin_cannot_associate_foreign_tenant_financial_records(): void
    {
        $superAdminA = $this->userWithRole('super-admin', $this->schoolA);
        $foreignAccountB = $this->makeAccount($this->schoolB, '1007-B', 'bank');

        // Super-admin associated with School A attempts linking School B account
        $this->actingAs($superAdminA)
            ->post(route('admin.bank-reconciliations.store'), [
                'bank_account_id' => $foreignAccountB->id,
                'reconciliation_date' => '2026-08-01',
                'book_balance' => 500,
                'bank_balance' => 500,
            ])
            ->assertSessionHasErrors('bank_account_id');
    }

    public function test_validation_failure_messages_do_not_reveal_foreign_school_details(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);
        $foreignAccountB = $this->makeAccount($this->schoolB, '1008-B', 'bank');

        $response = $this->actingAs($adminA)
            ->post(route('admin.bank-reconciliations.store'), [
                'bank_account_id' => $foreignAccountB->id,
                'reconciliation_date' => '2026-08-01',
                'book_balance' => 500,
                'bank_balance' => 500,
            ]);

        $response->assertSessionHasErrors([
            'bank_account_id' => 'The selected bank_account_id is invalid.',
        ]);

        $errors = session('errors')->get('bank_account_id');
        $this->assertStringNotContainsString($this->schoolB->name, implode(' ', $errors));
        $this->assertStringNotContainsString((string) $this->schoolB->id, implode(' ', $errors));
    }

    public function test_valid_same_tenant_financial_operations_succeed_and_retain_results(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);

        // Seed basic accounts for School A so accounting postings succeed
        Account::create(['school_id' => $this->schoolA->id, 'code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'category' => 'receivable', 'is_active' => true]);
        Account::create(['school_id' => $this->schoolA->id, 'code' => '5100', 'name' => 'Fee Revenue', 'type' => 'revenue', 'category' => 'tuition_revenue', 'is_active' => true]);
        Account::create(['school_id' => $this->schoolA->id, 'code' => '2100', 'name' => 'Accounts Payable', 'type' => 'liability', 'category' => 'payable', 'is_active' => true]);
        Account::create(['school_id' => $this->schoolA->id, 'code' => '6500', 'name' => 'Supplies Expense', 'type' => 'expense', 'category' => 'supply_expense', 'is_active' => true]);
        Account::create(['school_id' => $this->schoolA->id, 'code' => '1102', 'name' => 'Cash on Hand', 'type' => 'asset', 'category' => 'cash', 'is_active' => true]);
        Account::create(['school_id' => $this->schoolA->id, 'code' => '5800', 'name' => 'Other Revenue', 'type' => 'revenue', 'category' => 'other_revenue', 'is_active' => true]);

        $studentA = Student::create(['school_id' => $this->schoolA->id, 'first_name' => 'Valid', 'last_name' => 'Student']);
        $vendorA = Vendor::create([
            'school_id' => $this->schoolA->id,
            'code' => 'VEND-VALID-A',
            'name' => 'Vendor Valid A',
            'vendor_type' => 'supplier',
            'payment_terms' => 'net_30',
        ]);
        $customerA = Customer::create([
            'school_id' => $this->schoolA->id,
            'code' => 'CUST-VALID-A',
            'name' => 'Customer Valid A',
            'customer_type' => 'individual',
            'payment_terms' => 'net_30',
        ]);

        // 1. Valid Invoice creation
        $this->actingAs($adminA)
            ->post(route('admin.invoices.store'), [
                'student_id' => $studentA->id,
                'academic_year' => '2026',
                'term' => 'Term 1',
                'issued_at' => '2026-08-01',
                'items' => [
                    ['description' => 'Tuition Fee', 'quantity' => 1, 'unit_amount' => 1200],
                ],
            ])
            ->assertRedirect(route('admin.invoices.index'));

        $this->assertDatabaseHas('invoices', [
            'school_id' => $this->schoolA->id,
            'student_id' => $studentA->id,
            'total_amount' => 1200,
        ]);

        // 2. Valid Bill creation
        $this->actingAs($adminA)
            ->post(route('admin.bills.store'), [
                'vendor_id' => $vendorA->id,
                'bill_number' => 'BILL-VALID-01',
                'bill_date' => '2026-08-01',
                'due_date' => '2026-08-31',
                'total_amount' => 800,
            ])
            ->assertRedirect(route('admin.bills.index'));

        $this->assertDatabaseHas('bills', [
            'school_id' => $this->schoolA->id,
            'vendor_id' => $vendorA->id,
            'bill_number' => 'BILL-VALID-01',
        ]);

        // 3. Valid Receipt creation
        $this->actingAs($adminA)
            ->post(route('admin.receipts.store'), [
                'receipt_date' => '2026-08-01',
                'type' => 'sale',
                'customer_id' => $customerA->id,
                'payment_method' => 'cash',
                'items' => [
                    ['description' => 'Textbook', 'quantity' => 2, 'unit_price' => 25],
                ],
            ])
            ->assertRedirect(route('admin.receipts.index'));

        $this->assertDatabaseHas('receipts', [
            'school_id' => $this->schoolA->id,
            'customer_id' => $customerA->id,
            'total_amount' => 50,
        ]);
    }

    public function test_users_without_financial_permissions_are_forbidden(): void
    {
        $teacherA = $this->userWithRole('teacher', $this->schoolA);
        $accountA = $this->makeAccount($this->schoolA, '1009-A', 'bank');

        // Teacher cannot access accounting / journals
        $this->actingAs($teacherA)
            ->get(route('admin.journals.index'))
            ->assertForbidden();

        // Teacher cannot access bank reconciliations
        $this->actingAs($teacherA)
            ->get(route('admin.bank-reconciliations.index'))
            ->assertForbidden();

        // Teacher cannot create cashbook entry
        $this->actingAs($teacherA)
            ->post(route('admin.cashbook.store'), [
                'account_id' => $accountA->id,
                'transaction_type' => 'income',
                'category' => 'other',
                'description' => 'Unauthorized Entry',
                'amount' => 100,
                'transaction_date' => '2026-08-01',
                'payment_method' => 'cash',
            ])
            ->assertForbidden();
    }

    private function makeAccount(School $school, string $code, string $category): Account
    {
        return Account::create([
            'school_id' => $school->id,
            'code' => $code,
            'name' => "Account {$code}",
            'type' => $category === 'other_expense' ? 'expense' : 'asset',
            'category' => $category,
            'is_active' => true,
        ]);
    }

    private function userWithRole(string $role, ?School $school): User
    {
        $user = User::factory()->create(['school_id' => $school?->id]);
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
