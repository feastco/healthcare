<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class WebAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function superAdmin(): User
    {
        return User::where('email', 'superadmin@example.com')->firstOrFail();
    }

    private function loginPayload(array $overrides = []): array
    {
        return array_merge([
            '_token' => csrf_token(),
            'email' => 'superadmin@example.com',
            'password' => env('DEMO_ADMIN_PASSWORD'),
        ], $overrides);
    }

    public function test_guest_can_view_login_page(): void
    {
        $this->withoutVite();

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Sign in');
        $response->assertSee('Email');
        $response->assertSee('Password');
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="_token"', false);
    }

    public function test_authenticated_user_is_redirected_from_login_page(): void
    {
        $this->actingAs($this->superAdmin());

        $this->get('/login')->assertRedirect(route('home'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_protected_page(): void
    {
        $this->get('/home')->assertRedirect(route('login'));
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', $this->loginPayload());

        $response->assertRedirect(route('home'));
        $this->assertTrue(Auth::check());
        $this->assertSame('superadmin@example.com', Auth::user()->email);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->from('/login')->post('/login', $this->loginPayload([
            'password' => 'wrong-password',
        ]));

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::check());
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->post('/login', ['_token' => csrf_token()]);

        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertFalse(Auth::check());
    }

    public function test_login_regenerates_session_id(): void
    {
        $this->get('/login');
        $before = session()->getId();

        $this->post('/login', $this->loginPayload());

        $this->assertNotSame($before, session()->getId());
    }

    public function test_login_requires_csrf_token(): void
    {
        // The framework bypasses CSRF when running unit tests; flip the env
        // instance so VerifyCsrfToken enforcement actually runs.
        $this->app->instance('env', 'production');

        $response = $this->post('/login', [
            'email' => 'superadmin@example.com',
            'password' => env('DEMO_ADMIN_PASSWORD'),
        ]);

        $response->assertStatus(419);
    }

    public function test_logout_requires_csrf_token(): void
    {
        $this->app->instance('env', 'production');
        $this->actingAs($this->superAdmin());

        $this->post('/logout')->assertStatus(419);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $this->actingAs($this->superAdmin());

        $response = $this->post('/logout', ['_token' => csrf_token()]);

        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::check());
    }

    public function test_after_logout_user_is_guest_again(): void
    {
        $this->actingAs($this->superAdmin());
        $this->post('/logout', ['_token' => csrf_token()]);

        $this->get('/home')->assertRedirect(route('login'));
    }

    public function test_authenticated_home_renders_shell_with_user_and_role(): void
    {
        $this->withoutVite();
        $user = $this->superAdmin();

        $response = $this->actingAs($user)->get('/home');

        $response->assertStatus(200);
        $response->assertSee('PKU Healthcare Operations Management');
        $response->assertSee($user->name);
        $response->assertSee('Super Admin');
        $response->assertSee('href="/administration/users"', false);
    }

    public function test_role_aware_navigation_not_exposed_to_unauthorized_roles(): void
    {
        $this->withoutVite();
        $cashier = User::factory()->create()->assignRole('Cashier');

        $response = $this->actingAs($cashier)->get('/home');

        $response->assertStatus(200);
        $response->assertSee('href="/operations/invoices"', false);
        $response->assertDontSee('href="/administration/users"', false);
    }

    public function test_authenticated_shell_does_not_expose_sensitive_material(): void
    {
        $this->withoutVite();
        $user = $this->superAdmin();

        $html = $this->actingAs($user)->get('/home')->getContent();

        $this->assertStringNotContainsString($user->password, $html);
        $this->assertStringNotContainsString('plain-text-token', $html);
    }
}
