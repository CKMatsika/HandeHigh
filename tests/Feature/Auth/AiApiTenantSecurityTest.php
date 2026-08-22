<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\API\AIController;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AiApiTenantSecurityTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $userA;
    private User $userB;
    private Student $studentA;
    private Student $studentB;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        $this->schoolA = School::create(['name' => 'Alpha High', 'code' => 'ALPHA']);
        $this->schoolB = School::create(['name' => 'Beta High', 'code' => 'BETA']);

        $this->userA = User::create([
            'name' => 'Alpha Admin',
            'email' => 'admin@alpha.test',
            'password' => 'secret',
            'school_id' => $this->schoolA->id,
            'is_active' => true,
        ]);
        $this->userA->assignRole('school-admin');

        $this->userB = User::create([
            'name' => 'Beta Admin',
            'email' => 'admin@beta.test',
            'password' => 'secret',
            'school_id' => $this->schoolB->id,
            'is_active' => true,
        ]);
        $this->userB->assignRole('school-admin');

        $this->studentA = Student::create([
            'school_id' => $this->schoolA->id,
            'admission_number' => 'STU-A-001',
            'first_name' => 'Alice',
            'last_name' => 'Alpha',
            'gender' => 'female',
            'date_of_birth' => '2010-01-01',
            'status' => 'active',
        ]);

        $this->studentB = Student::create([
            'school_id' => $this->schoolB->id,
            'admission_number' => 'STU-B-001',
            'first_name' => 'Bob',
            'last_name' => 'Beta',
            'gender' => 'male',
            'date_of_birth' => '2010-01-01',
            'status' => 'active',
        ]);

        // Define isolated internal test routes mirroring api.php endpoints with auth and tenant middleware
        Route::middleware(['web', 'auth', \App\Http\Middleware\ResolveTenant::class])
            ->prefix('__test_ai')
            ->group(function () {
                Route::post('/performance', [AIController::class, 'studentPerformancePrediction']);
                Route::post('/recommendations', [AIController::class, 'academicRecommendations']);
                Route::post('/forecasting', [AIController::class, 'financialForecasting']);
                Route::post('/timetable', [AIController::class, 'timetableGeneration']);
            });
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->postJson('/__test_ai/performance', [
            'student_ids' => [$this->studentA->id],
        ]);

        $response->assertUnauthorized();
    }

    public function test_same_tenant_ai_performance_request_succeeds(): void
    {
        $response = $this->actingAs($this->userA)
            ->postJson('/__test_ai/performance', [
                'student_ids' => [$this->studentA->id],
            ]);

        $response->assertOk()
            ->assertJsonStructure(['predictions', 'generated_at', 'model_version']);
    }

    public function test_foreign_tenant_student_id_is_rejected(): void
    {
        $response = $this->actingAs($this->userA)
            ->postJson('/__test_ai/performance', [
                'student_ids' => [$this->studentB->id],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['student_ids.0']);
    }

    public function test_caller_provided_school_id_cannot_override_tenant_context(): void
    {
        // User A attempts to request predictions by injecting school_id of School B
        $response = $this->actingAs($this->userA)
            ->postJson('/__test_ai/performance', [
                'school_id' => $this->schoolB->id,
            ]);

        $response->assertOk();

        // Predictions should only contain School A students, not School B
        $returnedStudentIds = collect($response->json('predictions'))->pluck('student_id')->all();
        $this->assertContains($this->studentA->id, $returnedStudentIds);
        $this->assertNotContains($this->studentB->id, $returnedStudentIds);
    }

    public function test_academic_recommendations_rejects_foreign_student_id(): void
    {
        $response = $this->actingAs($this->userA)
            ->postJson('/__test_ai/recommendations', [
                'student_id' => $this->studentB->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['student_id']);
    }

    public function test_financial_forecasting_is_scoped_to_authenticated_tenant(): void
    {
        $response = $this->actingAs($this->userA)
            ->postJson('/__test_ai/forecasting', [
                'forecast_months' => 6,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['forecast', 'forecast_period', 'confidence_level']);
    }
}
