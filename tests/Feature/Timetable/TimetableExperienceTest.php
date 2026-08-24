<?php

namespace Tests\Feature\Timetable;

use App\Models\Enrollment;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolPeriod;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\Timetable\Operations\TimetableTodayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TimetableExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected School $schoolA;
    protected School $schoolB;
    protected User $adminUser;
    protected User $teacherUser;
    protected User $studentUser;
    protected Teacher $teacher;
    protected Student $student;
    protected SchoolClass $schoolClass;
    protected Subject $mathSubject;
    protected Room $room101;
    protected SchoolPeriod $period1;
    protected SchoolPeriod $period2;
    protected Timetable $timetableA;

    protected function setUp(): void
    {
        parent::setUp();

        (new \Database\Seeders\RoleSeeder)->run();
        (new \Database\Seeders\RolePermissionSeeder)->run();

        $this->schoolA = School::create([
            'name' => 'Harare High School',
            'code' => 'HARARE',
            'email' => 'admin@hararehigh.ac.zw',
        ]);
        $this->schoolB = School::create([
            'name' => 'Bulawayo Academy',
            'code' => 'BULAWAYO',
            'email' => 'admin@bulawayo.ac.zw',
        ]);

        // Admin User
        $this->adminUser = User::factory()->create(['school_id' => $this->schoolA->id]);
        $this->adminUser->assignRole('school-admin');

        // Teacher User & Model
        $this->teacherUser = User::factory()->create(['school_id' => $this->schoolA->id]);
        $this->teacherUser->assignRole('teacher');
        $this->teacher = Teacher::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $this->teacherUser->id,
            'employee_id' => 'EMP-EXP-01',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@schoola.com',
            'status' => true,
            'specialization' => 'Mathematics',
        ]);

        // Student User, Model & Class
        $this->studentUser = User::factory()->create(['school_id' => $this->schoolA->id]);
        $this->studentUser->assignRole('student');
        $this->schoolClass = SchoolClass::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Form 4A',
            'grade' => 'Form 4',
            'academic_year' => '2026',
            'term' => 'Term 1',
        ]);
        $this->student = Student::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $this->studentUser->id,
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'admission_number' => 'ADM001',
            'class_name' => 'Form 4A',
        ]);
        Enrollment::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $this->student->id,
            'class_id' => $this->schoolClass->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        $this->mathSubject = Subject::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Mathematics',
            'code' => 'MATH101',
        ]);

        $this->room101 = Room::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Room 101',
            'code' => 'R101',
            'type' => 'classroom',
            'capacity' => 35,
            'is_active' => true,
        ]);

        $this->period1 = SchoolPeriod::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Period 1',
            'period_sequence' => 1,
            'period_type' => 'lesson',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'is_active' => true,
        ]);

        $this->period2 = SchoolPeriod::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Period 2',
            'period_sequence' => 2,
            'period_type' => 'lesson',
            'start_time' => '08:45:00',
            'end_time' => '09:30:00',
            'is_active' => true,
        ]);

        $this->timetableA = Timetable::create([
            'school_id' => $this->schoolA->id,
            'name' => '2026 Term 1 Master Schedule',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'status' => 'published',
            'revision' => 1,
            'is_operational' => true,
            'published_at' => now(),
        ]);

        TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->schoolClass->id,
            'subject_id' => $this->mathSubject->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room101->id,
            'school_period_id' => $this->period1->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'status' => 'scheduled',
            'slot_type' => 'lesson',
        ]);
    }

    public function test_teacher_can_view_own_timetable(): void
    {
        $response = $this->actingAs($this->teacherUser)
            ->get(route('teacher.timetable.index'));

        $response->assertStatus(200);
        $response->assertSee('My Teaching Timetable');
        $response->assertSee('John Doe');
        $response->assertSee('Mathematics');
        $response->assertSee('Form 4A');
        $response->assertSee('Room 101');
    }

    public function test_teacher_can_query_today_api(): void
    {
        $response = $this->actingAs($this->teacherUser)
            ->getJson(route('teacher.timetable.today', ['date' => '2026-08-24'])); // Monday

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'teacher' => ['id', 'name'],
            'date',
            'day',
            'lessons',
            'free_periods_count',
        ]);
        $response->assertJsonPath('teacher.name', 'John Doe');
    }

    public function test_student_can_view_class_timetable(): void
    {
        $response = $this->actingAs($this->studentUser)
            ->get(route('student.timetable.index'));

        $response->assertStatus(200);
        $response->assertSee('My Class Timetable');
        $response->assertSee('Form 4A');
        $response->assertSee('Mathematics');
        $response->assertSee('John Doe');
    }

    public function test_student_can_query_today_api(): void
    {
        $response = $this->actingAs($this->studentUser)
            ->getJson(route('student.timetable.today', ['date' => '2026-08-24'])); // Monday

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'class' => ['id', 'name'],
            'date',
            'day',
            'lessons',
        ]);
        $response->assertJsonPath('class.name', 'Form 4A');
    }

    public function test_admin_can_view_master_operations_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.timetables.operations'));

        $response->assertStatus(200);
        $response->assertSee('Master Timetable Operations');
        $response->assertSee('Live Operational Mode');
        $response->assertSee('Form 4A');
        $response->assertSee('Mathematics');
    }

    public function test_today_service_calculates_current_and_next_lesson(): void
    {
        $todayService = app(TimetableTodayService::class);
        $teacherData = $todayService->getTeacherToday($this->teacher, $this->timetableA, Carbon::parse('2026-08-24 08:15:00'));

        $this->assertNotNull($teacherData['current_lesson']);
        $this->assertEquals($this->mathSubject->id, $teacherData['current_lesson']->subject_id);
        $this->assertCount(1, $teacherData['free_periods']); // Period 2 is free
    }

    public function test_cross_tenant_isolation_denies_unauthorized_access(): void
    {
        $otherSchoolTeacherUser = User::factory()->create(['school_id' => $this->schoolB->id]);
        $otherSchoolTeacherUser->assignRole('teacher');

        // Teacher from School B cannot query School A's class schedule via API
        \Laravel\Sanctum\Sanctum::actingAs($otherSchoolTeacherUser, ['*']);
        $response = $this->getJson("/api/timetable/class/{$this->schoolClass->id}");

        $response->assertStatus(404);
    }
}
