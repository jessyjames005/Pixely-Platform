<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('logs in an active user with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'password' => bcrypt('password123'),
        'is_active' => true,
    ]);
    Permission::firstOrCreate(['name' => 'gallery.photos.view', 'guard_name' => 'web']);
    $user->givePermissionTo('gallery.photos.view');

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.email', 'jane@example.com')
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'permissions', 'roles']]);

    // Regression: login() used to return the raw User model with no
    // permissions/roles at all, so every permission-gated nav item
    // stayed hidden until the page was reloaded and a /auth/me call
    // populated the auth store properly.
    $response->assertJsonPath('data.permissions', ['gallery.photos.view']);

    expect(auth()->check())->toBeTrue();
});

it('rejects invalid credentials', function () {
    User::factory()->create(['email' => 'jane@example.com', 'password' => bcrypt('password123')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401);
});

it('rejects login for a deactivated account even with valid credentials', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => bcrypt('password123'),
        'is_active' => false,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    $response
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'ACCOUNT_DISABLED');

    expect(auth()->check())->toBeFalse();
});

it('returns the same shape from /auth/me as from login', function () {
    $user = User::factory()->create(['is_active' => true]);
    Permission::firstOrCreate(['name' => 'gallery.photos.view', 'guard_name' => 'web']);
    $user->givePermissionTo('gallery.photos.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/auth/me');

    $response
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'permissions', 'roles']])
        ->assertJsonPath('data.permissions', ['gallery.photos.view']);
});
