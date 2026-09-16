<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthenticationApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const ORIGIN = 'http://localhost:5173';

    public function test_admin_can_log_in_with_valid_credentials(): void
    {
        $admin = User::factory()->create(['email' => 'admin@noure.local', 'password' => Hash::make('correct-password')]);

        $response = $this->withHeader('Origin', self::ORIGIN)->postJson('/api/v1/admin/auth/login', [
            'email' => 'admin@noure.local',
            'password' => 'correct-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', $admin->name)
            ->assertJsonPath('data.email', $admin->email)
            ->assertJsonPath('message', null)
            ->assertJsonMissing(['password' => $admin->password])
            ->assertJsonMissingPath('data.id');
        $this->assertAuthenticatedAs($admin, 'web');
    }

    public function test_incorrect_password_returns_unauthenticated(): void
    {
        User::factory()->create(['email' => 'admin@noure.local', 'password' => Hash::make('correct-password')]);

        $this->withHeader('Origin', self::ORIGIN)->postJson('/api/v1/admin/auth/login', [
            'email' => 'admin@noure.local',
            'password' => 'incorrect-password',
        ])->assertUnauthorized()->assertJsonPath('meta.errors.0.code', 'unauthenticated');

        $this->assertGuest('web');
    }

    public function test_unknown_email_returns_same_unauthenticated_response(): void
    {
        $this->withHeader('Origin', self::ORIGIN)->postJson('/api/v1/admin/auth/login', [
            'email' => 'missing@noure.local',
            'password' => 'incorrect-password',
        ])->assertUnauthorized()->assertJsonPath('meta.errors.0.code', 'unauthenticated');
    }

    public function test_login_validation_uses_documented_error_envelope(): void
    {
        $this->withHeader('Origin', self::ORIGIN)->postJson('/api/v1/admin/auth/login', [
            'email' => 'not-an-email',
        ])->assertUnprocessable()
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['meta' => ['errors' => [['code', 'field', 'message']]]]);
    }

    public function test_authenticated_admin_can_fetch_me(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->withHeader('Origin', self::ORIGIN)
            ->getJson('/api/v1/admin/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $admin->email)
            ->assertJsonMissingPath('data.id')
            ->assertJsonMissingPath('data.password');
    }

    public function test_unauthenticated_me_returns_documented_401(): void
    {
        $this->withHeader('Origin', self::ORIGIN)
            ->getJson('/api/v1/admin/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('data', null)
            ->assertJsonPath('meta.errors.0.code', 'unauthenticated')
            ->assertJsonPath('message', 'Authentication is required.');
    }

    public function test_logout_invalidates_authentication_and_me_then_fails(): void
    {
        User::factory()->create([
            'email' => 'admin@noure.local',
            'password' => Hash::make('correct-password'),
        ]);

        $this->withHeader('Origin', self::ORIGIN)->postJson('/api/v1/admin/auth/login', [
            'email' => 'admin@noure.local',
            'password' => 'correct-password',
        ])->assertOk();

        $this->withHeader('Origin', self::ORIGIN)
            ->postJson('/api/v1/admin/auth/logout')
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertGuest('web');
        $this->app['auth']->forgetGuards();
        $this->withHeader('Origin', self::ORIGIN)
            ->getJson('/api/v1/admin/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('meta.errors.0.code', 'unauthenticated');
    }

    public function test_public_catalog_endpoints_remain_accessible_without_authentication(): void
    {
        $this->getJson('/api/v1/categories')->assertOk();
        $this->getJson('/api/v1/products')->assertOk();
    }
}
