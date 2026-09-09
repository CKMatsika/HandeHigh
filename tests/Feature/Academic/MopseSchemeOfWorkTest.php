<?php

namespace Tests\Feature\Academic;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchemeOfWork;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MopseSchemeOfWorkTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $teacherUser;
    protected Teacher $teacher;
    protected User $adminUser;
    protected Subject $subject;
    protected SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'headmaster', 'guard_name' => 'web']);

        $this->school = School::create([
            'name' => 'Hande High School',
            'code' => 'HHS001',
            'slug' => 'hande-high',
            'is_active' => true,
        ]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'C. Matsika',
            'email' => 'matsika@handehigh.ac.zw',
            'password' => bcrypt('Password123!'),
        ]);
        $this->teacherUser->assignRole('teacher');

        $this->teacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacherUser->id,
            'employee_id' => 'EMP-001',
            'first_name' => 'C.',
            'last_name' => 'Matsika',
            'email' => 'matsika@handehigh.ac.zw',
            'phone' => '+263771234567',
            'gender' => 'male',
            'status' => true,
            'hire_date' => '2020-01-01',
        ]);

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Headmaster Moyo',
            'email' => 'headmaster@handehigh.ac.zw',
            'password' => bcrypt('Password123!'),
        ]);
        $this->adminUser->assignRole('super-admin');

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Art and Design',
            'code' => 'ART-F3',
            'status' => 'active',
        ]);

        $this->class = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Form 3A',
            'grade' => 'Form 3',
            'academic_year' => '2026',
            'term' => 'Term 1',
        ]);
    }

    /** @test */
    public function teacher_can_create_scheme_of_work_with_mopse_8_column_standard()
    {
        $payload = [
            'title' => 'Form 3 Art & Design - Term 1 Scheme-Cum Plan',
            'general_topic' => 'Art and Technology',
            'description' => 'Comprehensive Form 3 Art Curriculum Scheme',
            'aims' => "• To develop appreciation of the role of art in a wider culture and society.\n• To establish competencies in Art Technology systems.",
            'syllabus_reference' => 'National Syllabus p. 21 / School Syllabus',
            'cross_cutting_themes' => ['Heritage Studies', 'Environmental Issues', 'Financial Literacy'],
            'subject_id' => $this->subject->id,
            'school_class_id' => $this->class->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'action' => 'preview',
            'items' => [
                [
                    'week_number' => 1,
                    'week_ending' => '2026-01-16',
                    'day_of_week' => 'Friday',
                    'topic' => 'The development of Art technology in Zimbabwe',
                    'sub_topic' => 'Pre-colonial tools and materials',
                    'objectives' => "• Identify tools and materials used during the pre-colonial era in Zimbabwe.\n• Make artworks using pre-colonial tools.",
                    'competencies_skills' => 'Critical thinking, Problem solving, Rock art paintings, Basketry, Pottery',
                    'som_media' => 'National Syllabus p. 21, ICT Tools, Resource persons',
                    'facility_equipment' => 'Projector, Laptop, Markers, Manila, Art room/Studio, Chisel',
                    'methods_activities' => 'Listing art tools, Describing art tools, Creating artworks using pre-colonial tools',
                    'evaluation' => 'Learners actively participated in pre-colonial tool identification.',
                ],
                [
                    'week_number' => 2,
                    'week_ending' => '2026-01-23',
                    'day_of_week' => 'Friday',
                    'topic' => 'Graphic Design',
                    'sub_topic' => 'Principles of design in animation & sculpture',
                    'objectives' => '• Apply principles of design on sculpture and crafts.',
                    'competencies_skills' => 'Design thinking, Creativity, Modeling',
                    'som_media' => 'Textbooks, Internet, Digital portfolio',
                    'facility_equipment' => 'Drawing board, Fine liners, Cartridge paper',
                    'methods_activities' => 'Studio demonstration, Practical design exercises',
                    'evaluation' => '',
                ],
            ],
        ];

        $response = $this->actingAs($this->teacherUser)
            ->post(route('teacher.schemes-of-work.store'), $payload);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('schemes_of_work', [
            'school_id' => $this->school->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Form 3 Art & Design - Term 1 Scheme-Cum Plan',
            'general_topic' => 'Art and Technology',
            'status' => 'preview',
        ]);

        $scheme = SchemeOfWork::first();
        $this->assertEquals(2, $scheme->items()->count());

        $item1 = $scheme->items()->where('week_number', 1)->first();
        $this->assertNotNull($item1);
        $this->assertEquals('2026-01-16', $item1->week_ending->format('Y-m-d'));
        $this->assertStringContainsString('Pre-colonial tools', $item1->sub_topic);
        $this->assertStringContainsString('Critical thinking', $item1->competencies_skills);
        $this->assertStringContainsString('Projector', $item1->facility_equipment);
    }

    /** @test */
    public function teacher_can_update_scheme_and_submit_for_review()
    {
        $scheme = SchemeOfWork::create([
            'school_id' => $this->school->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'school_class_id' => $this->class->id,
            'title' => 'Initial Draft Scheme',
            'general_topic' => 'Art History',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'status' => 'draft',
        ]);

        $scheme->items()->create([
            'week_number' => 1,
            'week_ending' => '2026-01-16',
            'day_of_week' => 'Friday',
            'topic' => 'Intro',
            'objectives' => 'Understand basics',
            'sort_order' => 0,
        ]);

        $updatePayload = [
            'title' => 'Updated Form 3 Art Scheme',
            'general_topic' => 'Art and Technology',
            'aims' => 'Updated Aims',
            'subject_id' => $this->subject->id,
            'school_class_id' => $this->class->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'action' => 'save',
            'items' => [
                [
                    'week_number' => 1,
                    'week_ending' => '2026-01-16',
                    'day_of_week' => 'Friday',
                    'topic' => 'Updated Topic 1',
                    'objectives' => 'Updated SMART Objectives',
                    'competencies_skills' => 'Creativity',
                ]
            ]
        ];

        $response = $this->actingAs($this->teacherUser)
            ->put(route('teacher.schemes-of-work.update', $scheme), $updatePayload);

        $response->assertRedirect(route('teacher.schemes-of-work.index'));

        $this->assertDatabaseHas('schemes_of_work', [
            'id' => $scheme->id,
            'title' => 'Updated Form 3 Art Scheme',
            'general_topic' => 'Art and Technology',
        ]);

        // Submit for review
        $submitResponse = $this->actingAs($this->teacherUser)
            ->post(route('teacher.schemes-of-work.submit', $scheme));

        $submitResponse->assertRedirect(route('teacher.schemes-of-work.index'));
        $this->assertEquals('submitted', $scheme->fresh()->status);
        $this->assertNotNull($scheme->fresh()->submitted_at);
    }

    /** @test */
    public function admin_can_review_approve_or_reject_mopse_scheme()
    {
        $scheme = SchemeOfWork::create([
            'school_id' => $this->school->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'school_class_id' => $this->class->id,
            'title' => 'Submitted Scheme',
            'general_topic' => 'Art and Technology',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // View admin show page
        $showResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.schemes-of-work.show', $scheme));
        $showResponse->assertOk();
        $showResponse->assertSee('Scheme-Cum Plan Matrix (8 Columns)');

        // Approve scheme
        $approveResponse = $this->actingAs($this->adminUser)
            ->post(route('admin.schemes-of-work.approve', $scheme), [
                'review_notes' => 'Compliant with MoPSE Form 1-4 syllabus standards.'
            ]);

        $approveResponse->assertRedirect();
        $this->assertEquals('approved', $scheme->fresh()->status);
        $this->assertEquals($this->adminUser->id, $scheme->fresh()->reviewed_by);
        $this->assertEquals('Compliant with MoPSE Form 1-4 syllabus standards.', $scheme->fresh()->review_notes);
    }

    /** @test */
    public function teacher_and_admin_can_access_mopse_print_layout()
    {
        $scheme = SchemeOfWork::create([
            'school_id' => $this->school->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'school_class_id' => $this->class->id,
            'title' => 'Form 3 Art Print Scheme',
            'general_topic' => 'Art and Technology',
            'aims' => 'To develop appreciation of the role of art.',
            'syllabus_reference' => 'National Syllabus p. 21',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'status' => 'approved',
        ]);

        $scheme->items()->create([
            'week_number' => 1,
            'week_ending' => '2026-01-16',
            'day_of_week' => 'Friday',
            'topic' => 'Art Tech in Zimbabwe',
            'objectives' => 'Identify tools and materials',
            'competencies_skills' => 'Critical thinking, Basketry',
            'som_media' => 'National Syllabus',
            'facility_equipment' => 'Projector, Markers',
            'methods_activities' => 'Studio demonstration',
            'evaluation' => 'Satisfactory performance',
            'sort_order' => 0,
        ]);

        // Teacher Print Route
        $teacherPrint = $this->actingAs($this->teacherUser)
            ->get(route('teacher.schemes-of-work.print', $scheme));
        $teacherPrint->assertOk();
        $teacherPrint->assertSee('MINISTRY OF PRIMARY AND SECONDARY EDUCATION');
        $teacherPrint->assertSee('SCHEME OF WORK / SCHEME-CUM PLAN');
        $teacherPrint->assertSee('WEEK ENDING');
        $teacherPrint->assertSee('FACILITY / EQUIPMENT');

        // Admin Print Route
        $adminPrint = $this->actingAs($this->adminUser)
            ->get(route('admin.schemes-of-work.print', $scheme));
        $adminPrint->assertOk();
        $adminPrint->assertSee('MINISTRY OF PRIMARY AND SECONDARY EDUCATION');
    }

    /** @test */
    public function multi_tenant_isolation_is_strictly_enforced_for_schemes()
    {
        $otherSchool = School::create([
            'name' => 'Other Secondary School',
            'code' => 'OSS002',
            'slug' => 'other-sec',
            'is_active' => true,
        ]);

        $otherTeacherUser = User::create([
            'school_id' => $otherSchool->id,
            'name' => 'Other Teacher',
            'email' => 'other@other.ac.zw',
            'password' => bcrypt('Password123!'),
        ]);
        $otherTeacherUser->assignRole('teacher');

        $otherTeacher = Teacher::create([
            'school_id' => $otherSchool->id,
            'user_id' => $otherTeacherUser->id,
            'employee_id' => 'EMP-002',
            'first_name' => 'Other',
            'last_name' => 'Teacher',
            'email' => 'other@other.ac.zw',
            'phone' => '+263779876543',
            'gender' => 'female',
            'status' => true,
            'hire_date' => '2021-01-01',
        ]);

        $scheme = SchemeOfWork::create([
            'school_id' => $this->school->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'school_class_id' => $this->class->id,
            'title' => 'Hande High Private Scheme',
            'general_topic' => 'Art',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'status' => 'submitted',
        ]);

        // Other school teacher cannot view or edit Hande High scheme
        $showRes = $this->actingAs($otherTeacherUser)
            ->get(route('teacher.schemes-of-work.show', $scheme));
        $this->assertTrue(in_array($showRes->status(), [403, 404]));

        $printRes = $this->actingAs($otherTeacherUser)
            ->get(route('teacher.schemes-of-work.print', $scheme));
        $this->assertTrue(in_array($printRes->status(), [403, 404]));
    }
}
