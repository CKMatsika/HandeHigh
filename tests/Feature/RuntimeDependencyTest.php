<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\School;
use App\Models\User;
use App\Notifications\FeeReminderNotification;
use App\Services\DuckDuckGoSearchService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RuntimeDependencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_valid_login_regenerates_the_session(): void
    {
        $school = School::create(['name' => 'Synthetic School', 'code' => 'LOGIN']);
        $user = User::factory()->create([
            'email' => 'login@example.test',
            'password' => 'password',
            'school_id' => $school->id,
        ]);

        $this->get('/login');
        $oldSessionId = session()->getId();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirectToRoute('dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldSessionId, session()->getId());
    }

    public function test_invalid_credentials_do_not_authenticate(): void
    {
        $this->post('/login', [
            'email' => 'missing@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_invalidates_the_authenticated_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $sessionId = session()->getId();

        $this->post('/logout')->assertRedirectToRoute('login');

        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
    }

    public function test_guest_protected_access_and_existing_role_middleware_remain_enforced(): void
    {
        $this->get('/dashboard')->assertRedirectToRoute('login');

        $school = School::create(['name' => 'Synthetic School', 'code' => 'ROLE']);
        $user = User::factory()->create(['school_id' => $school->id]);
        $role = \Spatie\Permission\Models\Role::create(['name' => 'teacher', 'guard_name' => 'web']);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.enrollments.bulk-create'))
            ->assertForbidden();
    }

    public function test_web_post_route_keeps_csrf_middleware_in_the_stack(): void
    {
        $route = Route::getRoutes()->getByName('login');
        $middleware = $route->gatherMiddleware();

        $this->assertContains('web', $middleware);
    }

    public function test_named_routes_and_missing_parameters_fail_safely(): void
    {
        $this->assertSame('http://127.0.0.1:8000/login', route('login'));
        $this->get('/admin/students/999999')->assertRedirectToRoute('login');
    }

    public function test_external_http_search_handles_success_timeout_and_failure_with_fakes(): void
    {
        Http::fake([
            'https://openlibrary.org/*' => Http::response(['docs' => [['title' => 'Synthetic Book']]], 200),
            'https://en.wikipedia.org/*' => Http::response(['query' => ['search' => []]], 200),
            'https://en.wikibooks.org/*' => Http::response(['query' => ['search' => []]], 200),
        ]);

        $results = app(DuckDuckGoSearchService::class)->search('synthetic-success-' . uniqid());
        $this->assertNotEmpty($results);
        Http::assertSentCount(3);

        Http::fake([
            '*' => Http::failedConnection(),
        ]);
        $this->assertSame([], app(DuckDuckGoSearchService::class)->search('synthetic-failure-' . uniqid()));

        Http::fake([
            '*' => Http::response([], 504),
        ]);
        $this->assertSame([], app(DuckDuckGoSearchService::class)->search('synthetic-timeout-' . uniqid()));
    }

    public function test_fee_reminder_mail_message_renders_for_synthetic_data(): void
    {
        $user = User::factory()->create(['email' => 'parent@example.test']);
        $school = School::create(['name' => 'Synthetic School', 'code' => 'MAIL']);
        $student = new \App\Models\Student([
            'first_name' => 'Synthetic',
            'last_name' => 'Learner',
            'school_id' => $school->id,
        ]);
        $student->setRelation('user', $user);
        $invoice = new Invoice([
            'balance' => 100,
            'due_date' => Carbon::tomorrow(),
        ]);
        $invoice->setRelation('student', $student);

        $mail = (new FeeReminderNotification($invoice))->toMail($user);

        $this->assertSame('Upcoming Fee Payment Due', $mail->subject);
        $this->assertSame('emails.fee-reminder', $mail->markdown);
    }
}
