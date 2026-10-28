<?php

namespace Tests\Feature\Admin;

use App\Models\FeeStructure;
use App\Models\Enrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\Finance\InvoiceFeeSelectionService;
use App\Services\StudentClassMappingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentClassMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_a_student_keeps_the_same_student_record_and_syncs_class_grade(): void
    {
        $this->seed(RoleSeeder::class);
        $school = School::create(['name' => 'Student Edit Test School', 'code' => 'STU-EDIT']);
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        SchoolClass::create([
            'school_id' => $school->id,
            'name' => 'Lower 6 Arts',
            'grade' => '5',
            'academic_year' => '2026',
        ]);
        $student = Student::create([
            'school_id' => $school->id,
            'first_name' => 'Leonita',
            'last_name' => 'Baloyi',
            'grade' => '6',
            'class_name' => 'Lower 6',
            'status' => 'active',
            'is_boarding' => true,
        ]);

        $editPage = $this->get(route('admin.students.edit', $student))->assertOk();
        $dom = new \DOMDocument();
        @$dom->loadHTML($editPage->getContent());
        $xpath = new \DOMXPath($dom);
        $forms = $xpath->query('//form');
        foreach ($forms as $form) {
            $this->assertSame(0, $xpath->query('ancestor::form', $form)->length, 'Student update and deactivate forms must not nest.');
        }
        $updateForm = $xpath->query('//form[.//button[contains(normalize-space(.), "Update Student")]]')->item(0);
        $deactivateForm = $xpath->query('//form[.//button[contains(normalize-space(.), "Deactivate Student")]]')->item(0);
        $this->assertNotNull($updateForm);
        $this->assertNotNull($deactivateForm);
        $this->assertSame('PUT', $xpath->query('./input[@name="_method"]', $updateForm)->item(0)?->getAttribute('value'));
        $this->assertSame(0, $xpath->query('./input[@name="_method" and @value="DELETE"]', $updateForm)->length);
        $this->assertSame('DELETE', $xpath->query('./input[@name="_method"]', $deactivateForm)->item(0)?->getAttribute('value'));

        $this->put(route('admin.students.update', $student), [
            'first_name' => 'Leonita',
            'last_name' => 'Baloyi',
            'grade' => '6',
            'class_name' => 'Lower 6 Arts',
            'is_boarding' => true,
            'has_transport' => false,
            'status' => 'active',
            'guardian_ids' => [],
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.students.show', $student));

        $this->assertSame(1, Student::where('school_id', $school->id)->count());
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'school_id' => $school->id,
            'grade' => '5',
            'class_name' => 'Lower 6 Arts',
            'status' => 'active',
        ]);
    }

    public function test_class_register_grade_is_authoritative_and_stream_fees_stay_with_their_class(): void
    {
        $school = School::create(['name' => 'Mapping Test School', 'code' => 'MAP-TEST']);
        SchoolClass::create([
            'school_id' => $school->id,
            'name' => 'Lower 6 Arts',
            'grade' => '5',
            'academic_year' => '2026',
        ]);
        SchoolClass::create([
            'school_id' => $school->id,
            'name' => 'Upper 6 Arts',
            'grade' => '6',
            'academic_year' => '2026',
        ]);

        $mapping = app(StudentClassMappingService::class);
        $lowerSix = $mapping->resolveClass($school->id, 'Lower 6 Arts', '6', '2026');

        $this->assertNotNull($lowerSix);
        $this->assertSame('5', $lowerSix->grade);
        $this->assertSame('5', $mapping->canonicalGrade($school->id, '6', 'Lower 6 Arts', '2026'));

        $gradeFee = FeeStructure::create([
            'school_id' => $school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => '5',
            'category' => 'tuition',
            'code' => 'LOWER-GRADE',
            'label' => 'Lower grade tuition',
            'amount' => 100,
        ]);
        $streamFee = FeeStructure::create([
            'school_id' => $school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Lower 6 Arts',
            'category' => 'levy',
            'code' => 'LOWER-ARTS',
            'label' => 'Lower 6 Arts levy',
            'amount' => 25,
        ]);
        $upperFee = FeeStructure::create([
            'school_id' => $school->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => '6',
            'category' => 'tuition',
            'code' => 'UPPER-GRADE',
            'label' => 'Upper grade tuition',
            'amount' => 200,
        ]);

        $selected = app(InvoiceFeeSelectionService::class)->select(
            collect([$gradeFee, $streamFee, $upperFee]),
            $lowerSix->grade,
            false,
            false,
            false,
            matchGrade: true,
            studentClassName: $lowerSix->name,
        );

        $this->assertSame([$gradeFee->id, $streamFee->id], $selected->pluck('id')->sort()->values()->all());
        $this->assertEquals(125, $selected->sum('amount'));
    }

    public function test_student_and_enrollment_grade_filters_follow_linked_class_not_stale_grade_text(): void
    {
        $this->seed(RoleSeeder::class);
        $school = School::create(['name' => 'Placement Filter Test School', 'code' => 'PLACEMENT-TEST']);
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        $class = SchoolClass::create([
            'school_id' => $school->id,
            'name' => 'Lower 6 Arts',
            'grade' => '5',
            'academic_year' => '2026',
        ]);
        $student = Student::create([
            'school_id' => $school->id,
            'first_name' => 'Synthetic',
            'last_name' => 'LowerSix',
            'grade' => '6',
            'class_name' => 'Lower 6',
            'status' => 'active',
        ]);
        Enrollment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => '6',
            'class_name' => 'Lower 6',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        $this->get(route('admin.students.index', ['grade' => '5']))
            ->assertOk()->assertSee('Synthetic LowerSix');
        $this->get(route('admin.students.index', ['grade' => '6']))
            ->assertOk()->assertDontSee('Synthetic LowerSix');
        $this->get(route('admin.enrollments.index', ['grade' => '5']))
            ->assertOk()->assertSee('Synthetic LowerSix');
        $this->get(route('admin.enrollments.index', ['grade' => '6']))
            ->assertOk()->assertDontSee('Synthetic LowerSix');
    }
}
