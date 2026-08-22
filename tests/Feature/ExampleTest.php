<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_root_url_redirects_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirectToRoute('login');
    }

    public function test_the_login_page_is_reachable(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
    }

    public function test_protected_routes_redirect_guests_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirectToRoute('login');
    }
}
