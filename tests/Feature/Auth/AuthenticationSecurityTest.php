<?php

namespace Tests\Feature\Auth;

use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationSecurityTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $activeUser;
    private User $inactiveUser;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        RateLimiter::clear('active@handehigh.test|127.0.0.1');
        RateLimiter::clear('inactive@handehigh.test|127.0.0.1');
        RateLimiter::clear('other@handehigh.test|127.0.0.1');

        $this->school = School::create(['name' => 'Hande High School', 'code' => 'HHS']);

        $this->activeUser = User::create([
            'name' => 'Active Admin',
            'email' => 'active@handehigh.test',
            'password' => Hash::make('password123'),
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);
        $this->activeUser->assignRole('school-admin');

        $this->inactiveUser = User::create([
            'name' => 'Inactive Teacher',
            'email' => 'inactive@handehigh.test',
            'password' => Hash::make('password123'),
            'school_id' => $this->school->id,
            'is_active' => false,
        ]);
        $this->inactiveUser->assignRole('teacher');
    }

    public function test_active_user_can_authenticate_and_redirect_to_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'active@handehigh.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->activeUser);
    }

    public function test_inactive_user_cannot_authenticate_and_receives_generic_error(): void
    {
        $response = $this->post('/login', [
            'email' => 'inactive@handehigh.test',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'The provided credentials do not match our records.']);
        $this->assertGuest();
    }

    public function test_invalid_credentials_are_denied_with_generic_error(): void
    {
        $response = $this->post('/login', [
            'email' => 'active@handehigh.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email' => 'The provided credentials do not match our records.']);
        $this->assertGuest();
    }

    public function test_guest_cannot_access_protected_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_logout_invalidates_session_and_redirects_to_login(): void
    {
        $this->actingAs($this->activeUser);

        $response = $this->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_rate_limiter_blocks_after_5_failed_attempts_with_429(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'active@handehigh.test',
                'password' => 'wrong-password',
            ]);
            $response->assertSessionHasErrors('email');
        }

        // 6th attempt should be throttled
        $throttledResponse = $this->post('/login', [
            'email' => 'active@handehigh.test',
            'password' => 'password123',
        ]);

        $throttledResponse->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
        $this->assertGuest();

        // JSON requests receive HTTP 429
        $jsonThrottled = $this->postJson('/login', [
            'email' => 'active@handehigh.test',
            'password' => 'password123',
        ]);
        $jsonThrottled->assertStatus(429);
    }

    public function test_successful_login_clears_rate_limiter(): void
    {
        // 3 failed attempts
        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', [
                'email' => 'active@handehigh.test',
                'password' => 'wrong-password',
            ]);
        }

        // Successful attempt
        $response = $this->post('/login', [
            'email' => 'active@handehigh.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->activeUser);

        // Limiter should be cleared
        $this->assertEquals(0, RateLimiter::attempts('active@handehigh.test|127.0.0.1'));
    }

    public function test_rate_limiter_is_isolated_by_ip_address(): void
    {
        // 5 failed attempts from IP 192.168.1.50
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.50'])
                ->post('/login', [
                    'email' => 'active@handehigh.test',
                    'password' => 'wrong-password',
                ]);
        }

        // Attempt from IP 192.168.1.50 is throttled
        $throttled = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.50'])
            ->post('/login', [
                'email' => 'active@handehigh.test',
                'password' => 'password123',
            ]);
        $throttled->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));

        // Attempt from different IP (192.168.1.99) is NOT throttled and succeeds
        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.99'])
            ->post('/login', [
                'email' => 'active@handehigh.test',
                'password' => 'password123',
            ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_rate_limiter_is_isolated_by_account_email(): void
    {
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@handehigh.test',
            'password' => Hash::make('password123'),
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);

        // 5 failed attempts on active@handehigh.test
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'active@handehigh.test',
                'password' => 'wrong-password',
            ]);
        }

        // other@handehigh.test can still authenticate on the same IP
        $response = $this->post('/login', [
            'email' => 'other@handehigh.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_rate_limiter_resets_after_decay_window(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'active@handehigh.test',
                'password' => 'wrong-password',
            ]);
        }

        // Move time forward by 65 seconds
        $this->travel(65)->seconds();

        $response = $this->post('/login', [
            'email' => 'active@handehigh.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->activeUser);
    }
}
