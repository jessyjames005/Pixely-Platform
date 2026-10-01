<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

$jsonApiHeaders = [
    'Accept' => 'application/vnd.api+json',
    'Content-Type' => 'application/json',
];

it('logs in an active user with valid credentials', function () use ($jsonApiHeaders) {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'password' => bcrypt('password123'),
        'is_active' => true,
    ]);
    Permission::firstOrCreate(['name' => 'gallery.photos.view', 'guard_name' => 'web']);
    $user->givePermissionTo('gallery.photos.view');

    $response = $this->json('POST', '/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password123',
    ], $jsonApiHeaders);

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'users')
        ->assertJsonPath('data.attributes.email', 'jane@example.com')
        ->assertJsonStructure(['data' => ['id', 'type', 'attributes' => ['name', 'email', 'permissions', 'roles']]]);

    // Regression: login() used to return the raw User model with no
    // permissions/roles at all, so every permission-gated nav item
    // stayed hidden until the page was reloaded and a /auth/me call
    // populated the auth store properly.
    $response->assertJsonPath('data.attributes.permissions', ['gallery.photos.view']);
    expect($response->getContent())->not->toContain('password', 'remember_token');

    expect(auth()->check())->toBeTrue();

    $this->json('GET', '/api/v1/auth/me', [], $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.type', 'users')
        ->assertJsonPath('data.attributes.email', 'jane@example.com');
});

it('rejects invalid credentials with a JSON:API error', function () use ($jsonApiHeaders) {
    User::factory()->create(['email' => 'jane@example.com', 'password' => bcrypt('password123')]);

    $response = $this->json('POST', '/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'wrong-password',
    ], $jsonApiHeaders);

    $response
        ->assertStatus(401)
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('errors.0.code', 'INVALID_CREDENTIALS');
});

it('rejects login for a deactivated account with a JSON:API error', function () use ($jsonApiHeaders) {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => bcrypt('password123'),
        'is_active' => false,
    ]);

    $response = $this->json('POST', '/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password123',
    ], $jsonApiHeaders);

    $response
        ->assertStatus(403)
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('errors.0.code', 'ACCOUNT_DISABLED');

    $this->json('GET', '/api/v1/auth/me', [], $jsonApiHeaders)->assertUnauthorized();
});

it('returns the authenticated user as a JSON:API users resource from /auth/me', function () use ($jsonApiHeaders) {
    $user = User::factory()->create(['is_active' => true]);
    Permission::firstOrCreate(['name' => 'gallery.photos.view', 'guard_name' => 'web']);
    $user->givePermissionTo('gallery.photos.view');
    $this->actingAs($user);

    $response = $this->json('GET', '/api/v1/auth/me', [], $jsonApiHeaders);

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'users')
        ->assertJsonStructure(['data' => ['id', 'type', 'attributes' => ['name', 'email', 'permissions', 'roles']]])
        ->assertJsonPath('data.attributes.permissions', ['gallery.photos.view']);
});

it('logs out and invalidates the authenticated session', function () use ($jsonApiHeaders) {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => bcrypt('password123'),
        'is_active' => true,
    ]);

    $this->json('POST', '/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password123',
    ], $jsonApiHeaders)->assertOk();

    $this->json('POST', '/api/v1/auth/logout', [], $jsonApiHeaders)->assertNoContent();

    Auth::forgetGuards();

    $this->json('GET', '/api/v1/auth/me', [], $jsonApiHeaders)->assertUnauthorized();
});
