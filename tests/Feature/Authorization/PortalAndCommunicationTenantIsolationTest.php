<?php

namespace Tests\Feature\Authorization;

use App\Models\Book;
use App\Models\Conversation;
use App\Models\FlashCardItem;
use App\Models\FlashCardSet;
use App\Models\FlashCardStudySession;
use App\Models\Guardian;
use App\Models\Result;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PortalAndCommunicationTenantIsolationTest extends TestCase
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

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        $this->schoolA = School::create(['name' => 'Portal School A', 'code' => 'PSA']);
        $this->schoolB = School::create(['name' => 'Portal School B', 'code' => 'PSB']);
    }

    private function userWithRole(string $role, ?School $school): User
    {
        $user = User::factory()->create(['school_id' => $school?->id]);
        $user->assignRole(Role::findByName($role, 'web'));
        return $user;
    }

    public function test_communication_conversation_creation_rejects_foreign_participants(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);
        $userB = $this->userWithRole('teacher', $this->schoolB);

        $response = $this->actingAs($adminA)
            ->post(route('admin.conversation.create'), [
                'name' => 'Cross-School Conversation',
                'type' => 'direct',
                'participants' => (string) $userB->id,
            ]);

        $response->assertSessionHasErrors(['participants']);
        $this->assertDatabaseMissing('conversations', [
            'name' => 'Cross-School Conversation',
        ]);
    }

    public function test_librarian_cannot_borrow_book_with_foreign_student_or_foreign_book(): void
    {
        $librarianA = $this->userWithRole('librarian', $this->schoolA);

        $studentA = Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'John',
            'last_name' => 'A',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $studentB = Student::create([
            'school_id' => $this->schoolB->id,
            'first_name' => 'Jane',
            'last_name' => 'B',
            'gender' => 'female',
            'status' => 'active',
        ]);

        $bookA = Book::create([
            'school_id' => $this->schoolA->id,
            'title' => 'Math 101',
            'author' => 'Math Author',
            'category' => 'Mathematics',
            'isbn' => '1234567890',
            'available_copies' => 5,
            'total_copies' => 5,
        ]);

        $bookB = Book::create([
            'school_id' => $this->schoolB->id,
            'title' => 'Physics 101',
            'author' => 'Physics Author',
            'category' => 'Physics',
            'isbn' => '0987654321',
            'available_copies' => 5,
            'total_copies' => 5,
        ]);

        // Attempt to borrow foreign student with own book
        $response = $this->actingAs($librarianA)
            ->post(route('librarian.borrow.store'), [
                'student_id' => $studentB->id,
                'book_id' => $bookA->id,
                'due_date' => now()->addDays(7)->toDateString(),
            ]);

        $response->assertSessionHasErrors(['student_id']);

        // Attempt to borrow own student with foreign book
        $response2 = $this->actingAs($librarianA)
            ->post(route('librarian.borrow.store'), [
                'student_id' => $studentA->id,
                'book_id' => $bookB->id,
                'due_date' => now()->addDays(7)->toDateString(),
            ]);

        $response2->assertSessionHasErrors(['book_id']);

        $this->assertDatabaseMissing('borrow_records', [
            'school_id' => $this->schoolA->id,
        ]);
    }

    public function test_teacher_scheme_of_work_rejects_foreign_subject_or_class(): void
    {
        $teacherUserA = $this->userWithRole('teacher', $this->schoolA);
        $teacherA = Teacher::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $teacherUserA->id,
            'employee_id' => 'EMP-A-001',
            'first_name' => 'Alice',
            'last_name' => 'Teacher',
            'email' => 'alice@schoola.test',
            'status' => 'active',
        ]);

        $classA = SchoolClass::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Form 1A',
            'grade' => 'Form 1',
            'academic_year' => '2026',
        ]);

        $subjectB = Subject::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Foreign Subject',
            'code' => 'FSUB',
        ]);

        $response = $this->actingAs($teacherUserA)
            ->post(route('teacher.schemes-of-work.store'), [
                'title' => 'Biology Scheme',
                'description' => 'Test',
                'subject_id' => $subjectB->id,
                'school_class_id' => $classA->id,
                'academic_year' => '2026',
                'term' => 'Term 1',
                'items' => [
                    [
                        'week_number' => 1,
                        'day_of_week' => 'Monday',
                        'topic' => 'Cells',
                        'objectives' => 'Learn cells',
                    ],
                ],
            ]);

        $response->assertSessionHasErrors(['subject_id']);
        $this->assertDatabaseMissing('schemes_of_work', [
            'title' => 'Biology Scheme',
        ]);
    }

    public function test_student_flashcard_study_session_rejects_foreign_item_submission(): void
    {
        $teacherUserA = $this->userWithRole('teacher', $this->schoolA);
        $teacherA = Teacher::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $teacherUserA->id,
            'employee_id' => 'EMP-A-002',
            'first_name' => 'Bob',
            'last_name' => 'Teacher',
            'email' => 'bob@schoola.test',
            'status' => 'active',
        ]);

        $setA = FlashCardSet::create([
            'school_id' => $this->schoolA->id,
            'teacher_id' => $teacherA->id,
            'title' => 'Chemistry Flashcards',
            'status' => 'published',
            'source_type' => 'manual',
        ]);

        $itemA = FlashCardItem::create([
            'flash_card_set_id' => $setA->id,
            'front_text' => 'H2O',
            'back_text' => 'Water',
            'sort_order' => 0,
        ]);

        $teacherUserB = $this->userWithRole('teacher', $this->schoolB);
        $teacherB = Teacher::create([
            'school_id' => $this->schoolB->id,
            'user_id' => $teacherUserB->id,
            'employee_id' => 'EMP-B-001',
            'first_name' => 'Charlie',
            'last_name' => 'Teacher',
            'email' => 'charlie@schoolb.test',
            'status' => 'active',
        ]);

        $setB = FlashCardSet::create([
            'school_id' => $this->schoolB->id,
            'teacher_id' => $teacherB->id,
            'title' => 'Foreign Flashcards',
            'status' => 'published',
            'source_type' => 'manual',
        ]);

        $itemB = FlashCardItem::create([
            'flash_card_set_id' => $setB->id,
            'front_text' => 'CO2',
            'back_text' => 'Carbon Dioxide',
            'sort_order' => 0,
        ]);

        $studentUserA = $this->userWithRole('student', $this->schoolA);
        $studentA = Student::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $studentUserA->id,
            'first_name' => 'Sam',
            'last_name' => 'Student',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $session = FlashCardStudySession::create([
            'user_id' => $studentUserA->id,
            'flash_card_set_id' => $setA->id,
            'started_at' => now(),
        ]);

        // Attempt to submit result with foreign flashcard item
        $response = $this->actingAs($studentUserA)
            ->postJson(route('student.flash-cards.submit-result'), [
                'session_id' => $session->id,
                'item_id' => $itemB->id,
                'confidence' => 'know',
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('flash_card_item_results', [
            'study_session_id' => $session->id,
            'flash_card_item_id' => $itemB->id,
        ]);
    }

    public function test_parent_dashboard_renders_results_without_error(): void
    {
        $parentUserA = $this->userWithRole('parent', $this->schoolA);
        $guardianA = Guardian::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $parentUserA->id,
            'first_name' => 'Jane',
            'last_name' => 'Guardian',
        ]);

        $classA = SchoolClass::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Form 1A',
            'grade' => 'Form 1',
            'academic_year' => '2026',
        ]);

        $studentA = Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Tommy',
            'last_name' => 'Student',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $guardianA->students()->attach($studentA->id, ['relationship' => 'mother']);

        $subjectA = Subject::create([
            'school_id' => $this->schoolA->id,
            'name' => 'English',
            'code' => 'ENG',
        ]);

        \Illuminate\Support\Facades\DB::table('academic_years')->insertOrIgnore([
            'id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Result::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $studentA->id,
            'class_id' => $classA->id,
            'subject_id' => $subjectA->id,
            'total_score' => 85,
            'max_total_score' => 100,
            'average' => 85,
            'grade' => 'A',
            'term' => 'Term 1',
            'academic_year' => 1,
        ]);

        $response = $this->actingAs($parentUserA)
            ->get(route('parent.dashboard'));

        $response->assertOk();
    }
}
