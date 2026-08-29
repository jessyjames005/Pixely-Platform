<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'system.database.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'system.sql.query', 'guard_name' => 'web']);
});

it('requires the system.database.view permission to list tables', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->getJson('/api/v1/system/database/tables');

    $response->assertStatus(403);
});

it('lists tables for a user with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.database.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/system/database/tables');

    $response->assertOk();
});

it('previews table rows with sensitive columns redacted', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.database.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/system/database/tables/users/preview');

    $response
        ->assertOk()
        ->assertJsonMissingPath('data.0.password')
        ->assertJsonMissingPath('data.0.remember_token');
});

it('returns 404 for a non-existent table', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.database.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/system/database/tables/does_not_exist/preview');

    $response->assertNotFound();
});

it('requires system.sql.query, separate from system.database.view, to run ad-hoc queries', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.database.view');
    $this->actingAs($user);

    $response = $this->postJson('/api/v1/system/database/query', ['sql' => 'SELECT 1']);

    $response->assertStatus(403);
});

it('executes a safe SELECT query', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.sql.query');
    $this->actingAs($user);

    $response = $this->postJson('/api/v1/system/database/query', ['sql' => 'SELECT 1 AS value']);

    $response
        ->assertOk()
        ->assertJsonPath('data.0.value', 1);
});

it('rejects a non-SELECT query with a 422 and no execution', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.sql.query');
    $this->actingAs($user);

    $response = $this->postJson('/api/v1/system/database/query', ['sql' => 'DELETE FROM users']);

    $response
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'UNSAFE_QUERY');
});

it('rejects stacked statements attempting to smuggle a write', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.sql.query');
    $this->actingAs($user);

    $response = $this->postJson('/api/v1/system/database/query', [
        'sql' => 'SELECT 1; DELETE FROM users;',
    ]);

    $response->assertStatus(422);
});
