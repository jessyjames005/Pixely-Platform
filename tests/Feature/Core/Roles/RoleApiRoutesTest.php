<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('requires authentication to list roles', function () {
    $response = $this->getJson('/api/v1/roles');

    $response->assertStatus(401);
});

it('lists roles with their permissions', function () {
    $this->actingAs(User::factory()->create());

    Permission::create(['name' => 'gallery.manage', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $role->givePermissionTo('gallery.manage');

    $response = $this->getJson('/api/v1/roles');

    $response
        ->assertOk()
        ->assertJsonPath('data.0.name', 'editor')
        ->assertJsonPath('data.0.permissions.0.name', 'gallery.manage');
});

it('lists available permissions', function () {
    $this->actingAs(User::factory()->create());

    Permission::create(['name' => 'users.manage', 'guard_name' => 'web']);

    $response = $this->getJson('/api/v1/permissions');

    $response
        ->assertOk()
        ->assertJsonPath('data.0.name', 'users.manage');
});

it('creates a role with permissions', function () {
    $this->actingAs(User::factory()->create());

    Permission::create(['name' => 'gallery.manage', 'guard_name' => 'web']);

    $response = $this->postJson('/api/v1/roles', [
        'name' => 'editor',
        'permissions' => ['gallery.manage'],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'editor')
        ->assertJsonPath('data.permissions.0.name', 'gallery.manage');
});

it('rejects a duplicate role name', function () {
    $this->actingAs(User::factory()->create());

    Role::create(['name' => 'editor', 'guard_name' => 'web']);

    $response = $this->postJson('/api/v1/roles', [
        'name' => 'editor',
    ]);

    $response->assertStatus(422);
});

it('updates a role permissions', function () {
    $this->actingAs(User::factory()->create());

    Permission::create(['name' => 'gallery.manage', 'guard_name' => 'web']);
    Permission::create(['name' => 'users.manage', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $role->givePermissionTo('gallery.manage');

    $response = $this->putJson("/api/v1/roles/{$role->id}", [
        'permissions' => ['users.manage'],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.permissions.0.name', 'users.manage')
        ->assertJsonCount(1, 'data.permissions');
});

it('deletes a role', function () {
    $this->actingAs(User::factory()->create());

    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);

    $response = $this->deleteJson("/api/v1/roles/{$role->id}");

    $response->assertNoContent();

    expect(Role::find($role->id))->toBeNull();
});

it('prevents deleting the admin role', function () {
    $this->actingAs(User::factory()->create());

    $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);

    $response = $this->deleteJson("/api/v1/roles/{$role->id}");

    $response->assertStatus(422);

    expect(Role::find($role->id))->not->toBeNull();
});

it('assigns a role to a user', function () {
    $this->actingAs(User::factory()->create());

    $targetUser = User::factory()->create();
    Role::create(['name' => 'editor', 'guard_name' => 'web']);

    $response = $this->postJson('/api/v1/roles/assign', [
        'user_id' => $targetUser->id,
        'role' => 'editor',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.roles.0.name', 'editor');

    expect($targetUser->fresh()->hasRole('editor'))->toBeTrue();
});
