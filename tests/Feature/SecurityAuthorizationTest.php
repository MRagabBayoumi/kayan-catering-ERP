<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $driver;

    protected User $storekeeper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
        $this->driver = User::where('role', User::ROLE_DRIVER)->first() ?? User::factory()->create([
            'role' => User::ROLE_DRIVER,
            'is_active' => true,
        ]);
        $this->storekeeper = User::where('role', User::ROLE_STOREKEEPER)->first() ?? User::factory()->create([
            'role' => User::ROLE_STOREKEEPER,
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_users_are_redirected_to_login_for_sensitive_endpoints(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('settings.index'))->assertRedirect(route('login'));
        $this->get(route('users.index'))->assertRedirect(route('login'));
        $this->get(route('roles.index'))->assertRedirect(route('login'));
        $this->postJson(route('ai.ask'), ['prompt' => 'test'])->assertStatus(401);
    }

    public function test_driver_cannot_access_administrative_settings_users_roles_or_dashboard(): void
    {
        $this->actingAs($this->driver);

        $this->get(route('dashboard'))->assertStatus(403);
        $this->get(route('settings.index'))->assertStatus(403);
        $this->get(route('users.index'))->assertStatus(403);
        $this->get(route('roles.index'))->assertStatus(403);
        $this->postJson(route('ai.ask'), ['prompt' => 'test'])->assertStatus(403);
    }

    public function test_storekeeper_cannot_access_settings_users_or_roles(): void
    {
        $this->actingAs($this->storekeeper);

        $this->get(route('settings.index'))->assertStatus(403);
        $this->get(route('users.index'))->assertStatus(403);
        $this->get(route('roles.index'))->assertStatus(403);
        $this->get(route('dashboard'))->assertStatus(403);
    }

    public function test_administrator_can_access_all_administrative_endpoints(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('dashboard'))->assertStatus(200);
        $this->get(route('settings.index'))->assertStatus(200);
        $this->get(route('users.index'))->assertStatus(200);
        $this->get(route('roles.index'))->assertStatus(200);
    }

    public function test_administrator_cannot_demote_or_deactivate_themselves(): void
    {
        $this->actingAs($this->admin);

        // Attempt self-demotion
        $response = $this->put(route('users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => User::ROLE_STOREKEEPER,
            'is_active' => true,
        ]);

        $response->assertSessionHas('status', 'error');
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'role' => User::ROLE_ADMIN,
        ]);

        // Attempt self-deactivation
        $response = $this->put(route('users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => User::ROLE_ADMIN,
            'is_active' => false,
        ]);

        $response->assertSessionHas('status', 'error');
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_create_users_via_post_endpoint(): void
    {
        $this->actingAs($this->storekeeper);

        $response = $this->post(route('users.store'), [
            'name' => 'Hacker Admin',
            'email' => 'hacker@test.com',
            'password' => 'secret123',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'hacker@test.com']);
    }
}
