<?php

namespace Tests\Feature\Authorization;

use App\Models\Account;
use App\Models\Attendance;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\Bill;
use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\Budget;
use App\Models\CareerGuidanceAssessment;
use App\Models\CareerPath;
use App\Models\Cashbook;
use App\Models\Conversation;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\FlashCardSet;
use App\Models\Invoice;
use App\Models\JournalBatch;
use App\Models\Leave;
use App\Models\Loan;
use App\Models\Payroll;
use App\Models\Project;
use App\Models\Receipt;
use App\Models\SchemeOfWork;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantScopedBindingTest extends TestCase
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

        $this->schoolA = School::create(['name' => 'Scoped School A', 'code' => 'SCOPEDA']);
        $this->schoolB = School::create(['name' => 'Scoped School B', 'code' => 'SCOPEDB']);
    }

    public function test_inventory_all_32_parameters_are_explicitly_registered_and_bound(): void
    {
        $expectedMappings = [
            'student' => Student::class,
            'enrollment' => Enrollment::class,
            'invoice' => Invoice::class,
            'fee' => FeeStructure::class,
            'account' => Account::class,
            'journalBatch' => JournalBatch::class,
            'budget' => Budget::class,
            'creditNote' => CreditNote::class,
            'bankReconciliation' => BankReconciliation::class,
            'teacher' => Teacher::class,
            'class' => SchoolClass::class,
            'subject' => Subject::class,
            'attendance' => Attendance::class,
            'cashbook' => Cashbook::class,
            'receipt' => Receipt::class,
            'employee' => Employee::class,
            'leave' => Leave::class,
            'loan' => Loan::class,
            'payroll' => Payroll::class,
            'department' => Department::class,
            'project' => Project::class,
            'bill' => Bill::class,
            'vendor' => Vendor::class,
            'customer' => Customer::class,
            'book' => Book::class,
            'borrowRecord' => BorrowRecord::class,
            'conversation' => Conversation::class,
            'assessment' => CareerGuidanceAssessment::class,
            'path' => CareerPath::class,
            'schemeOfWork' => SchemeOfWork::class,
            'flashCardSet' => FlashCardSet::class,
            'timetable' => Timetable::class,
        ];

        $this->assertCount(32, $expectedMappings);

        $router = app('router');
        $binders = $router->getBindingCallback('student');
        $this->assertNotNull($binders);

        foreach ($expectedMappings as $param => $modelClass) {
            $callback = $router->getBindingCallback($param);
            $this->assertNotNull($callback, "Parameter '{$param}' must have a registered binding callback.");
        }

        // Verify that system parameter 'school' is NOT in the scoped binding map
        $schoolCallback = $router->getBindingCallback('school');
        $this->assertNull($schoolCallback, "Parameter 'school' must not have a tenant-scoped binding callback.");
    }

    public function test_middleware_priority_places_resolve_tenant_before_substitute_bindings(): void
    {
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $priority = $kernel->getMiddlewarePriority();

        $authInterfaceIndex = array_search(\Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class, $priority, true);
        $tenantIndex = array_search(\App\Http\Middleware\ResolveTenant::class, $priority, true);
        $bindingsIndex = array_search(\Illuminate\Routing\Middleware\SubstituteBindings::class, $priority, true);
        $authorizeIndex = array_search(\Illuminate\Auth\Middleware\Authorize::class, $priority, true);

        $this->assertNotFalse($authInterfaceIndex, 'AuthenticatesRequests must be in priority list.');
        $this->assertNotFalse($tenantIndex, 'ResolveTenant must be in priority list.');
        $this->assertNotFalse($bindingsIndex, 'SubstituteBindings must be in priority list.');
        $this->assertNotFalse($authorizeIndex, 'Authorize must be in priority list.');

        $this->assertLessThan($tenantIndex, $authInterfaceIndex, 'Authentication must run before ResolveTenant.');
        $this->assertLessThan($bindingsIndex, $tenantIndex, 'ResolveTenant must run before SubstituteBindings.');
        $this->assertLessThan($authorizeIndex, $bindingsIndex, 'SubstituteBindings must run before Authorize.');
    }

    public function test_fee_structure_domain_bindings_and_policy(): void
    {
        $ownFee = $this->makeFee($this->schoolA, 'FEE-A');
        $foreignFee = $this->makeFee($this->schoolB, 'FEE-B');
        $admin = $this->userWithRole('school-admin', $this->schoolA);

        $this->actingAs($admin)
            ->get(route('admin.fees.show', $ownFee))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.fees.show', $foreignFee))
            ->assertNotFound();

        Role::findByName('school-admin', 'web')->revokePermissionTo('fees.view');

        $this->actingAs($admin)
            ->get(route('admin.fees.show', $ownFee))
            ->assertForbidden();

        Role::findByName('school-admin', 'web')->givePermissionTo('fees.view');
        Role::findByName('school-admin', 'web')->revokePermissionTo('fees.delete');

        $this->actingAs($admin)
            ->delete(route('admin.fees.destroy', $ownFee))
            ->assertForbidden();

        Role::findByName('school-admin', 'web')->givePermissionTo('fees.delete');

        $this->actingAs($admin)
            ->delete(route('admin.fees.destroy', $foreignFee))
            ->assertNotFound();

        $this->actingAs($admin)
            ->delete(route('admin.fees.destroy', $ownFee))
            ->assertRedirect();

        $this->assertDatabaseMissing('fee_structures', ['id' => $ownFee->id]);
    }

    public function test_invoice_domain_bindings_and_policy(): void
    {
        $studentA = $this->makeStudent($this->schoolA, 'John', 'A');
        $studentB = $this->makeStudent($this->schoolB, 'Jane', 'B');

        $ownInvoice = Invoice::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $studentA->id,
            'number' => 'INV-A001',
        ]);

        $foreignInvoice = Invoice::create([
            'school_id' => $this->schoolB->id,
            'student_id' => $studentB->id,
            'number' => 'INV-B001',
        ]);

        $admin = $this->userWithRole('school-admin', $this->schoolA);

        $this->actingAs($admin)
            ->get(route('admin.invoices.show', $ownInvoice))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.invoices.show', $foreignInvoice))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.invoices.print', $ownInvoice))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.invoices.print', $foreignInvoice))
            ->assertNotFound();

        Role::findByName('school-admin', 'web')->revokePermissionTo('invoices.view');

        $this->actingAs($admin)
            ->get(route('admin.invoices.show', $ownInvoice))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.invoices.print', $ownInvoice))
            ->assertForbidden();

        Role::findByName('school-admin', 'web')->givePermissionTo('invoices.view');
        Role::findByName('school-admin', 'web')->revokePermissionTo('invoices.edit');

        $this->actingAs($admin)
            ->post(route('admin.invoices.cancel', $ownInvoice))
            ->assertForbidden();

        Role::findByName('school-admin', 'web')->givePermissionTo('invoices.edit');

        $this->actingAs($admin)
            ->post(route('admin.invoices.cancel', $foreignInvoice))
            ->assertNotFound();
    }

    public function test_account_domain_bindings_and_policy(): void
    {
        $ownAccount = Account::create([
            'school_id' => $this->schoolA->id,
            'code' => '1001-A',
            'name' => 'General Account A',
            'type' => 'asset',
            'category' => 'bank',
        ]);

        $foreignAccount = Account::create([
            'school_id' => $this->schoolB->id,
            'code' => '1001-B',
            'name' => 'General Account B',
            'type' => 'asset',
            'category' => 'bank',
        ]);

        $admin = $this->userWithRole('school-admin', $this->schoolA);

        $this->actingAs($admin)
            ->get(route('admin.accounts.edit', $ownAccount))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.accounts.edit', $foreignAccount))
            ->assertNotFound();

        $this->actingAs($admin)
            ->put(route('admin.accounts.toggle', $ownAccount))
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.accounts.toggle', $foreignAccount))
            ->assertNotFound();

        Role::findByName('school-admin', 'web')->revokePermissionTo('accounts.edit');

        $this->actingAs($admin)
            ->get(route('admin.accounts.edit', $ownAccount))
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('admin.accounts.toggle', $ownAccount))
            ->assertForbidden();
    }

    public function test_budget_domain_bindings_and_policy(): void
    {
        $ownBudget = Budget::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Budget 2026 A',
            'fiscal_year' => '2026',
        ]);

        $foreignBudget = Budget::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Budget 2026 B',
            'fiscal_year' => '2026',
        ]);

        $admin = $this->userWithRole('school-admin', $this->schoolA);

        $this->actingAs($admin)
            ->get(route('admin.budgets.show', $ownBudget))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.budgets.show', $foreignBudget))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.budgets.edit', $ownBudget))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.budgets.edit', $foreignBudget))
            ->assertNotFound();

        Role::findByName('school-admin', 'web')->revokePermissionTo('budgets.view');

        $this->actingAs($admin)
            ->get(route('admin.budgets.show', $ownBudget))
            ->assertForbidden();

        Role::findByName('school-admin', 'web')->revokePermissionTo('budgets.edit');

        $this->actingAs($admin)
            ->get(route('admin.budgets.edit', $ownBudget))
            ->assertForbidden();
    }

    public function test_bank_reconciliation_domain_bindings_and_policy(): void
    {
        $bankAccountA = BankAccount::create([
            'school_id' => $this->schoolA->id,
            'account_name' => 'Bank A',
            'account_number' => '12345-A',
            'bank_name' => 'Test Bank',
        ]);

        $bankAccountB = BankAccount::create([
            'school_id' => $this->schoolB->id,
            'account_name' => 'Bank B',
            'account_number' => '12345-B',
            'bank_name' => 'Test Bank',
        ]);

        $accountA = Account::create([
            'school_id' => $this->schoolA->id,
            'code' => '1002-A',
            'name' => 'Recon Account A',
            'type' => 'asset',
            'category' => 'bank',
        ]);

        $accountB = Account::create([
            'school_id' => $this->schoolB->id,
            'code' => '1002-B',
            'name' => 'Recon Account B',
            'type' => 'asset',
            'category' => 'bank',
        ]);

        $ownRecon = BankReconciliation::create([
            'school_id' => $this->schoolA->id,
            'bank_account_id' => $bankAccountA->id,
            'reconciliation_date' => '2026-08-01',
            'book_balance' => 1000.00,
            'bank_balance' => 1000.00,
            'reconciled_balance' => 1000.00,
        ]);

        $foreignRecon = BankReconciliation::create([
            'school_id' => $this->schoolB->id,
            'bank_account_id' => $bankAccountB->id,
            'reconciliation_date' => '2026-08-01',
            'book_balance' => 2000.00,
            'bank_balance' => 2000.00,
            'reconciled_balance' => 2000.00,
        ]);

        $admin = $this->userWithRole('school-admin', $this->schoolA);

        $this->actingAs($admin)
            ->get(route('admin.bank-reconciliations.show', $ownRecon))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.bank-reconciliations.show', $foreignRecon))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.bank-reconciliations.transactions', $accountA))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.bank-reconciliations.transactions', $accountB))
            ->assertNotFound();

        Role::findByName('school-admin', 'web')->revokePermissionTo('bank-reconciliations.view');

        $this->actingAs($admin)
            ->get(route('admin.bank-reconciliations.show', $ownRecon))
            ->assertForbidden();
    }

    public function test_journal_batch_domain_bindings_and_policy(): void
    {
        $ownJournal = JournalBatch::create([
            'school_id' => $this->schoolA->id,
            'batch_number' => 'JB-A001',
            'transaction_date' => '2026-08-01',
            'description' => 'Test Batch A',
            'source_type' => 'manual',
        ]);

        $foreignJournal = JournalBatch::create([
            'school_id' => $this->schoolB->id,
            'batch_number' => 'JB-B001',
            'transaction_date' => '2026-08-01',
            'description' => 'Test Batch B',
            'source_type' => 'manual',
        ]);

        $admin = $this->userWithRole('school-admin', $this->schoolA);

        $this->actingAs($admin)
            ->get(route('admin.journals.show', $ownJournal))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.journals.show', $foreignJournal))
            ->assertNotFound();

        Role::findByName('school-admin', 'web')->revokePermissionTo('journals.view');

        $this->actingAs($admin)
            ->get(route('admin.journals.show', $ownJournal))
            ->assertForbidden();
    }

    public function test_student_academic_domain_bindings_and_policy(): void
    {
        $ownStudent = $this->makeStudent($this->schoolA, 'Alice', 'Smith');
        $foreignStudent = $this->makeStudent($this->schoolB, 'Bob', 'Jones');

        $admin = $this->userWithRole('school-admin', $this->schoolA);

        $this->actingAs($admin)
            ->get(route('admin.students.show', $ownStudent))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.students.show', $foreignStudent))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.students.edit', $ownStudent))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.students.edit', $foreignStudent))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.students.statement.create', $ownStudent))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.students.statement.create', $foreignStudent))
            ->assertNotFound();

        Role::findByName('school-admin', 'web')->revokePermissionTo('students.view');

        $this->actingAs($admin)
            ->get(route('admin.students.show', $ownStudent))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.students.statement.create', $ownStudent))
            ->assertForbidden();

        Role::findByName('school-admin', 'web')->revokePermissionTo('students.edit');

        $this->actingAs($admin)
            ->get(route('admin.students.edit', $ownStudent))
            ->assertForbidden();
    }

    public function test_class_and_subject_domain_bindings_and_policy(): void
    {
        $ownClass = SchoolClass::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Form 1A',
            'grade' => 'Form 1',
            'academic_year' => '2026',
        ]);

        $foreignClass = SchoolClass::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Form 1B',
            'grade' => 'Form 1',
            'academic_year' => '2026',
        ]);

        $ownSubject = Subject::create([
            'school_id' => $this->schoolA->id,
            'code' => 'ENG-A',
            'name' => 'English A',
        ]);

        $foreignSubject = Subject::create([
            'school_id' => $this->schoolB->id,
            'code' => 'ENG-B',
            'name' => 'English B',
        ]);

        $admin = $this->userWithRole('school-admin', $this->schoolA);

        $this->actingAs($admin)
            ->get(route('admin.classes.show', $ownClass))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.classes.show', $foreignClass))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.subjects.show', $ownSubject))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.subjects.show', $foreignSubject))
            ->assertNotFound();

        Role::findByName('school-admin', 'web')->revokePermissionTo('classes.view');

        $this->actingAs($admin)
            ->get(route('admin.classes.show', $ownClass))
            ->assertForbidden();

        Role::findByName('school-admin', 'web')->revokePermissionTo('subjects.view');

        $this->actingAs($admin)
            ->get(route('admin.subjects.show', $ownSubject))
            ->assertForbidden();
    }

    public function test_teacher_domain_bindings_and_policy(): void
    {
        $ownTeacher = Teacher::create([
            'school_id' => $this->schoolA->id,
            'employee_id' => 'EMP-A001',
            'first_name' => 'David',
            'last_name' => 'Teacher',
            'email' => 'david.teacher@schoola.test',
        ]);

        $foreignTeacher = Teacher::create([
            'school_id' => $this->schoolB->id,
            'employee_id' => 'EMP-B001',
            'first_name' => 'Emma',
            'last_name' => 'Teacher',
            'email' => 'emma.teacher@schoolb.test',
        ]);

        $admin = $this->userWithRole('school-admin', $this->schoolA);

        $this->actingAs($admin)
            ->get(route('admin.teachers.show', $ownTeacher))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.teachers.show', $foreignTeacher))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.teachers.edit', $ownTeacher))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.teachers.edit', $foreignTeacher))
            ->assertNotFound();

        Role::findByName('school-admin', 'web')->revokePermissionTo('teachers.view');

        $this->actingAs($admin)
            ->get(route('admin.teachers.show', $ownTeacher))
            ->assertForbidden();

        Role::findByName('school-admin', 'web')->revokePermissionTo('teachers.edit');

        $this->actingAs($admin)
            ->get(route('admin.teachers.edit', $ownTeacher))
            ->assertForbidden();
    }

    public function test_enrollment_and_attendance_domain_bindings_and_policy(): void
    {
        $studentA = $this->makeStudent($this->schoolA, 'Enroll', 'A');
        $studentB = $this->makeStudent($this->schoolB, 'Enroll', 'B');

        $ownEnrollment = Enrollment::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $studentA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'enrollment_date' => '2026-01-10',
        ]);

        $foreignEnrollment = Enrollment::create([
            'school_id' => $this->schoolB->id,
            'student_id' => $studentB->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'enrollment_date' => '2026-01-10',
        ]);

        $ownAttendance = Attendance::create([
            'school_id' => $this->schoolA->id,
            'attendable_type' => Student::class,
            'attendable_id' => $studentA->id,
            'attendance_date' => '2026-08-01',
            'status' => 'present',
        ]);

        $foreignAttendance = Attendance::create([
            'school_id' => $this->schoolB->id,
            'attendable_type' => Student::class,
            'attendable_id' => $studentB->id,
            'attendance_date' => '2026-08-01',
            'status' => 'present',
        ]);

        $admin = $this->userWithRole('school-admin', $this->schoolA);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.show', $ownEnrollment))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.enrollments.show', $foreignEnrollment))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.enrollments.print', $ownEnrollment))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.enrollments.print', $foreignEnrollment))
            ->assertNotFound();

        $this->actingAs($admin)
            ->put(route('admin.attendance.update', $ownAttendance), ['status' => 'absent'])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.attendance.update', $foreignAttendance), ['status' => 'absent'])
            ->assertNotFound();

        Role::findByName('school-admin', 'web')->revokePermissionTo('enrollments.view');

        $this->actingAs($admin)
            ->get(route('admin.enrollments.show', $ownEnrollment))
            ->assertForbidden();

        Role::findByName('school-admin', 'web')->revokePermissionTo('attendance.edit');

        $this->actingAs($admin)
            ->put(route('admin.attendance.update', $ownAttendance), ['status' => 'absent'])
            ->assertForbidden();
    }

    public function test_receipt_domain_bindings_and_policy(): void
    {
        $ownReceipt = Receipt::create([
            'school_id' => $this->schoolA->id,
            'receipt_number' => 'REC-A001',
            'receipt_date' => '2026-08-01',
            'type' => 'service',
        ]);

        $foreignReceipt = Receipt::create([
            'school_id' => $this->schoolB->id,
            'receipt_number' => 'REC-B001',
            'receipt_date' => '2026-08-01',
            'type' => 'service',
        ]);

        $admin = $this->userWithRole('school-admin', $this->schoolA);

        $this->actingAs($admin)
            ->get(route('admin.receipts.show', $ownReceipt))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.receipts.show', $foreignReceipt))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.receipts.edit', $ownReceipt))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.receipts.edit', $foreignReceipt))
            ->assertNotFound();

        Role::findByName('school-admin', 'web')->revokePermissionTo('receipts.view');

        $this->actingAs($admin)
            ->get(route('admin.receipts.show', $ownReceipt))
            ->assertForbidden();

        Role::findByName('school-admin', 'web')->revokePermissionTo('receipts.edit');

        $this->actingAs($admin)
            ->get(route('admin.receipts.edit', $ownReceipt))
            ->assertForbidden();

        Role::findByName('school-admin', 'web')->revokePermissionTo('receipts.delete');

        $this->actingAs($admin)
            ->delete(route('admin.receipts.destroy', $ownReceipt))
            ->assertForbidden();

        Role::findByName('school-admin', 'web')->givePermissionTo('receipts.delete');

        $this->actingAs($admin)
            ->delete(route('admin.receipts.destroy', $foreignReceipt))
            ->assertNotFound();

        $this->actingAs($admin)
            ->delete(route('admin.receipts.destroy', $ownReceipt))
            ->assertRedirect();

        $this->assertDatabaseMissing('receipts', ['id' => $ownReceipt->id]);
    }

    public function test_school_system_routes_authorization_and_policy(): void
    {
        $superAdmin = $this->userWithRole('super-admin', $this->schoolA);
        $superAdminNoSchool = $this->userWithRole('super-admin', null);
        $schoolAdmin = $this->userWithRole('school-admin', $this->schoolA);
        $headmaster = $this->userWithRole('headmaster', $this->schoolA);
        $teacher = $this->userWithRole('teacher', $this->schoolA);

        // Guest denied
        $this->get(route('admin.schools.index'))->assertRedirect(route('login'));

        // Non-super-admin roles denied
        $this->actingAs($schoolAdmin)->get(route('admin.schools.index'))->assertForbidden();
        $this->actingAs($headmaster)->get(route('admin.schools.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.schools.index'))->assertForbidden();

        // Super-admin allowed (both with and without school)
        $this->actingAs($superAdmin)->get(route('admin.schools.index'))->assertOk();
        $this->actingAs($superAdminNoSchool)->get(route('admin.schools.index'))->assertOk();

        // Super-admin without school cannot access normal tenant routes (no inheritance of exemption)
        $this->actingAs($superAdminNoSchool)->get(route('admin.fees.index'))->assertForbidden();

        // School creation by super-admin
        $this->actingAs($superAdmin)
            ->post(route('admin.schools.store'), [
                'name' => 'New Provisioned School',
                'code' => 'NEWPROV',
                'contact_email' => 'prov@test.test',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('schools', ['code' => 'NEWPROV']);

        $newSchool = School::where('code', 'NEWPROV')->firstOrFail();

        // School update by super-admin
        $this->actingAs($superAdmin)
            ->put(route('admin.schools.update', $newSchool->id), [
                'name' => 'Updated Provisioned School',
                'code' => 'NEWPROV',
                'contact_email' => 'prov-upd@test.test',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('schools', ['name' => 'Updated Provisioned School']);

        // Non-super-admin denied update/destroy
        $this->actingAs($schoolAdmin)
            ->put(route('admin.schools.update', $newSchool->id), [
                'name' => 'Hacked School',
                'code' => 'NEWPROV',
            ])
            ->assertForbidden();

        $this->actingAs($schoolAdmin)
            ->delete(route('admin.schools.destroy', $newSchool->id))
            ->assertForbidden();

        // School deletion by super-admin in isolated test DB
        $this->actingAs($superAdmin)
            ->delete(route('admin.schools.destroy', $newSchool->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('schools', ['id' => $newSchool->id]);
    }

    private function makeFee(School $school, string $code): FeeStructure
    {
        return FeeStructure::create([
            'school_id' => $school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'category' => 'tuition',
            'code' => $code,
            'label' => 'Tuition '.$code,
            'amount' => 100.00,
            'is_optional' => false,
        ]);
    }

    private function makeStudent(School $school, string $firstName, string $lastName): Student
    {
        return Student::create([
            'school_id' => $school->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    private function userWithRole(string $role, ?School $school): User
    {
        $user = User::factory()->create(['school_id' => $school?->id]);
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
