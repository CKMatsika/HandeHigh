<?php

namespace Tests\Feature\Leadership;

use App\Models\School;
use App\Models\User;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Enrollment;
use App\Models\Teacher;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Account;
use App\Models\Cashbook;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadershipDashboardReportsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $headmaster;
    private User $deputyHeadmaster;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        $this->school = School::create([
            'name' => 'Hande High School',
            'code' => 'HHS',
        ]);

        $this->headmaster = User::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'Dr. Headmaster',
            'email' => 'headmaster@handehigh.test',
        ]);
        $this->headmaster->assignRole('headmaster');

        $this->deputyHeadmaster = User::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'Mrs. Deputy',
            'email' => 'deputy@handehigh.test',
        ]);
        $this->deputyHeadmaster->assignRole('deputy-headmaster');

        $this->seedReportData();
    }

    private function seedReportData(): void
    {
        // 1. Create Classes & Streams
        $classForm1North = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'North',
            'grade' => 'Form 1',
            'academic_year' => '2026',
        ]);

        $classForm1South = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'South',
            'grade' => 'Form 1',
            'academic_year' => '2026',
        ]);

        // 2. Create Students & Enrollments
        // Male, Form 1 North, Boarder
        $studentBoy = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Tinashe',
            'last_name' => 'Moyo',
            'gender' => 'male',
            'grade' => 'Form 1',
            'class_name' => 'North',
            'is_boarding' => true,
            'status' => 'active',
            'admission_number' => 'ADM-001',
        ]);
        Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $studentBoy->id,
            'class_id' => $classForm1North->id,
            'academic_year' => '2026',
            'term' => '1',
            'grade' => 'Form 1',
            'class_name' => 'North',
            'is_boarding' => true,
            'student_type' => 'boarding',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        // Female, Form 1 North, Day Scholar
        $studentGirl = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Ruvimbo',
            'last_name' => 'Chikore',
            'gender' => 'female',
            'grade' => 'Form 1',
            'class_name' => 'North',
            'is_boarding' => false,
            'status' => 'active',
            'admission_number' => 'ADM-002',
        ]);
        Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $studentGirl->id,
            'class_id' => $classForm1North->id,
            'academic_year' => '2026',
            'term' => '1',
            'grade' => 'Form 1',
            'class_name' => 'North',
            'is_boarding' => false,
            'student_type' => 'day',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        // Female, Form 1 South, Day Scholar
        $studentGirl2 = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Chipo',
            'last_name' => 'Sibanda',
            'gender' => 'female',
            'grade' => 'Form 1',
            'class_name' => 'South',
            'is_boarding' => false,
            'status' => 'active',
            'admission_number' => 'ADM-003',
        ]);
        Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $studentGirl2->id,
            'class_id' => $classForm1South->id,
            'academic_year' => '2026',
            'term' => '1',
            'grade' => 'Form 1',
            'class_name' => 'South',
            'is_boarding' => false,
            'student_type' => 'day',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        // 3. Teachers
        Teacher::create([
            'school_id' => $this->school->id,
            'first_name' => 'Simba',
            'last_name' => 'Gumbo',
            'email' => 'simba@handehigh.test',
            'employee_id' => 'TCH-001',
            'gender' => 'male',
            'specialization' => 'Mathematics',
            'qualification' => 'BSc Mathematics',
            'experience_years' => 8,
            'status' => true,
        ]);

        Teacher::create([
            'school_id' => $this->school->id,
            'first_name' => 'Kuda',
            'last_name' => 'Ncube',
            'email' => 'kuda@handehigh.test',
            'employee_id' => 'TCH-002',
            'gender' => 'female',
            'specialization' => 'Sciences',
            'qualification' => 'MSc Chemistry',
            'experience_years' => 5,
            'status' => true,
        ]);

        // 4. Department & Employees
        $deptAdmin = Department::create([
            'school_id' => $this->school->id,
            'name' => 'Administration & Security',
        ]);

        Employee::create([
            'school_id' => $this->school->id,
            'department_id' => $deptAdmin->id,
            'first_name' => 'Farai',
            'last_name' => 'Mutasa',
            'email' => 'farai@handehigh.test',
            'phone' => '+26377111222',
            'employee_id' => 'EMP-001',
            'position' => 'Senior Administrator',
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'date_of_birth' => '1985-05-12',
            'hire_date' => '2020-01-15',
            'salary' => 1200.00,
        ]);

        // 5. Bank and Cash Accounts
        $bankAccount = Account::create([
            'school_id' => $this->school->id,
            'code' => '1301',
            'name' => 'CBZ Main Operations Account',
            'type' => 'asset',
            'category' => 'bank',
            'currency' => 'USD',
            'opening_balance' => 25000.00,
            'is_active' => true,
            'is_postable' => true,
            'description' => 'Bank: CBZ Bank | Acc No: 01123456789 | Branch: Harare Main',
        ]);

        Cashbook::create([
            'school_id' => $this->school->id,
            'account_id' => $bankAccount->id,
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'income',
            'category' => 'fees',
            'amount' => 1500.00,
            'balance_after' => 26500.00,
            'reference_number' => 'REC-1001',
            'description' => 'Term 1 Tuition Fees Deposit',
        ]);
    }

    public function test_headmaster_can_view_dashboard_with_all_reports_and_drilldowns(): void
    {
        $response = $this->actingAs($this->headmaster)->get(route('admin.dashboard.headmaster'));

        $response->assertOk();
        $response->assertSee('Headmaster Dashboard');
        // Report Sections
        $response->assertSee('Enrollment Reports');
        $response->assertSee('Teachers Reports');
        $response->assertSee('Employees Reports');
        $response->assertSee('Bank Balance Summary');

        // Check specific data points
        $response->assertSee('Form 1');
        $response->assertSee('North');
        $response->assertSee('South');
        $response->assertSee('Mathematics');
        $response->assertSee('Sciences');
        $response->assertSee('CBZ Bank');
        $response->assertSee('Administration & Security');

        // Drilldown hooks
        $response->assertSee('leadership-drilldown-modal');
        $response->assertSee('LeadershipDrilldown.open');
    }

    public function test_deputy_headmaster_can_view_dashboard_with_all_reports_and_drilldowns(): void
    {
        $response = $this->actingAs($this->deputyHeadmaster)->get(route('admin.dashboard.deputy-headmaster'));

        $response->assertOk();
        $response->assertSee('Deputy Headmaster Dashboard');
        // Report Sections
        $response->assertSee('Enrollment Reports');
        $response->assertSee('Teachers Reports');
        $response->assertSee('Employees Reports');
        $response->assertSee('Bank Balance Summary');

        // Drilldown hooks
        $response->assertSee('leadership-drilldown-modal');
    }

    public function test_drilldown_api_returns_filtered_students(): void
    {
        $response = $this->actingAs($this->headmaster)->getJson(route('admin.dashboard.drilldown', [
            'type' => 'students',
            'grade' => 'Form 1',
            'stream' => 'North',
            'gender' => 'male',
        ]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'count' => 1,
        ]);
        $response->assertJsonFragment(['name' => 'Tinashe Moyo']);
        $response->assertJsonMissing(['name' => 'Ruvimbo Chikore']);
    }

    public function test_drilldown_api_returns_filtered_teachers(): void
    {
        $response = $this->actingAs($this->headmaster)->getJson(route('admin.dashboard.drilldown', [
            'type' => 'teachers',
            'specialization' => 'Mathematics',
        ]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'count' => 1,
        ]);
        $response->assertJsonFragment(['name' => 'Simba Gumbo']);
    }

    public function test_drilldown_api_returns_filtered_employees(): void
    {
        $dept = Department::where('school_id', $this->school->id)->first();

        $response = $this->actingAs($this->headmaster)->getJson(route('admin.dashboard.drilldown', [
            'type' => 'employees',
            'department_id' => $dept->id,
        ]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'count' => 1,
        ]);
        $response->assertJsonFragment(['name' => 'Farai Mutasa']);
    }

    public function test_drilldown_api_returns_bank_account_transactions(): void
    {
        $acc = Account::where('school_id', $this->school->id)->first();

        $response = $this->actingAs($this->headmaster)->getJson(route('admin.dashboard.drilldown', [
            'type' => 'bank_account',
            'account_id' => $acc->id,
        ]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'count' => 1,
        ]);
        $response->assertJsonFragment(['description' => 'Term 1 Tuition Fees Deposit']);
    }

    public function test_student_and_enrollment_index_pages_support_drilldown_filters(): void
    {
        // Filter students by grade, stream, and gender
        $response = $this->actingAs($this->headmaster)->get(route('admin.students.index', [
            'grade' => 'Form 1',
            'stream' => 'North',
            'gender' => 'female',
        ]));

        $response->assertOk();
        $response->assertSee('Ruvimbo');
        $response->assertDontSee('Tinashe');

        // Filter enrollments by stream and gender
        $enrResponse = $this->actingAs($this->headmaster)->get(route('admin.enrollments.index', [
            'grade' => 'Form 1',
            'stream' => 'North',
            'gender' => 'male',
        ]));

        $enrResponse->assertOk();
        $enrResponse->assertSee('Tinashe');
        $enrResponse->assertDontSee('Chipo');
    }
}
