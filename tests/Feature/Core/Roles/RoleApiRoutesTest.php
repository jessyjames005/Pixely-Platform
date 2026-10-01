<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

$jsonApiHeaders = [
    'Accept' => 'application/vnd.api+json',
    'Content-Type' => 'application/vnd.api+json',
];

it('requires authentication for role and permission resources', function () use ($jsonApiHeaders) {
    $this->json('GET', '/api/v1/roles', [], $jsonApiHeaders)
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
    $this->json('GET', '/api/v1/permissions', [], $jsonApiHeaders)->assertUnauthorized();
});

it('lists roles and permissions as JSON:API resources with allowlisted fields', function () use ($jsonApiHeaders) {
    $this->actingAs(User::factory()->create());
    $permission = Permission::create(['name' => 'gallery.photos.manage', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);
    Role::create(['name' => 'viewer', 'guard_name' => 'web']);
    Permission::create(['name' => 'users.manage', 'guard_name' => 'web']);

    $roleResponse = $this->json('GET', '/api/v1/roles?include=permissions&page[size]=1', [], $jsonApiHeaders);
    $roleResponse
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'roles')
        ->assertJsonPath('data.0.attributes.name', 'editor')
        ->assertJsonPath('data.0.relationships.permissions.data.0.type', 'permissions')
        ->assertJsonPath('meta.page.lastPage', 2)
        ->assertJsonStructure(['links' => ['next']])
        ->assertJsonPath('included.0.attributes.name', 'gallery.photos.manage');
    expect(array_keys($roleResponse->json('data.0.attributes')))->toBe(['name']);

    $permissionResponse = $this->json('GET', '/api/v1/permissions?page[size]=1', [], $jsonApiHeaders);
    $permissionResponse
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'permissions')
        ->assertJsonPath('data.0.attributes.name', 'gallery.photos.manage')
        ->assertJsonPath('meta.page.lastPage', 2)
        ->assertJsonStructure(['links' => ['next']])
        ->assertJsonPath('data.0.attributes.isCore', false);
    expect(array_keys($permissionResponse->json('data.0.attributes')))->toBe(['name', 'isCore']);
});

it('creates, reads, updates, and deletes roles using JSON:API documents', function () use ($jsonApiHeaders) {
    $this->actingAs(User::factory()->create());
    $created = $this->json('POST', '/api/v1/roles', [
        'data' => ['type' => 'roles', 'attributes' => ['name' => 'editor']],
    ], $jsonApiHeaders)
        ->assertCreated()
        ->assertJsonPath('data.type', 'roles')
        ->assertJsonPath('data.attributes.name', 'editor');

    $roleId = $created->json('data.id');
    expect(Role::findOrFail($roleId)->guard_name)->toBe('web');

    $this->json('GET', "/api/v1/roles/{$roleId}", [], $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.id', (string) $roleId);

    $this->json('PATCH', "/api/v1/roles/{$roleId}", [
        'data' => [
            'type' => 'roles',
            'id' => (string) $roleId,
            'attributes' => ['name' => 'reviewer'],
        ],
    ], $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.attributes.name', 'reviewer');

    $this->json('DELETE', "/api/v1/roles/{$roleId}", [], $jsonApiHeaders)->assertNoContent();
});

it('rejects duplicate role names and protects the admin role', function () use ($jsonApiHeaders) {
    $this->actingAs(User::factory()->create());
    Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $admin = Role::create(['name' => 'admin', 'guard_name' => 'web']);

    $this->json('POST', '/api/v1/roles', [
        'data' => ['type' => 'roles', 'attributes' => ['name' => 'editor']],
    ], $jsonApiHeaders)
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'source' => ['pointer']]]]);

    $this->json('DELETE', "/api/v1/roles/{$admin->id}", [], $jsonApiHeaders)
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('syncs role permissions through the JSON:API relationship endpoint', function () use ($jsonApiHeaders) {
    $this->actingAs(User::factory()->create());
    $first = Permission::create(['name' => 'gallery.photos.manage', 'guard_name' => 'web']);
    $next = Permission::create(['name' => 'users.manage', 'guard_name' => 'web']);
    $created = $this->json('POST', '/api/v1/roles', [
        'data' => [
            'type' => 'roles',
            'attributes' => ['name' => 'editor'],
            'relationships' => [
                'permissions' => [
                    'data' => [['type' => 'permissions', 'id' => (string) $first->id]],
                ],
            ],
        ],
    ], $jsonApiHeaders)->assertCreated();
    $role = Role::findOrFail($created->json('data.id'));

    expect($role->permissions->modelKeys())->toBe([$first->id]);

    $this->json('PATCH', "/api/v1/roles/{$role->id}/relationships/permissions", [
        'data' => [['type' => 'permissions', 'id' => (string) $next->id]],
    ], $jsonApiHeaders)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.0.type', 'permissions')
        ->assertJsonPath('data.0.id', (string) $next->id);

    expect($role->fresh()->permissions->modelKeys())->toBe([$next->id]);
});

it('creates, reads, updates, and deletes permissions using JSON:API documents', function () use ($jsonApiHeaders) {
    $this->actingAs(User::factory()->create());
    $created = $this->json('POST', '/api/v1/permissions', [
        'data' => [
            'type' => 'permissions',
            'attributes' => ['name' => 'reports.view', 'isCore' => false],
        ],
    ], $jsonApiHeaders)
        ->assertCreated()
        ->assertJsonPath('data.type', 'permissions')
        ->assertJsonPath('data.attributes.name', 'reports.view');

    $permissionId = $created->json('data.id');
    expect(Permission::findOrFail($permissionId)->guard_name)->toBe('web');

    $this->json('GET', "/api/v1/permissions/{$permissionId}", [], $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.id', (string) $permissionId);

    $this->json('PATCH', "/api/v1/permissions/{$permissionId}", [
        'data' => [
            'type' => 'permissions',
            'id' => (string) $permissionId,
            'attributes' => ['name' => 'reports.manage'],
        ],
    ], $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.attributes.name', 'reports.manage');

    $this->json('DELETE', "/api/v1/permissions/{$permissionId}", [], $jsonApiHeaders)->assertNoContent();
});

it('protects core permissions and validates permission attributes', function () use ($jsonApiHeaders) {
    $this->actingAs(User::factory()->create());
    $corePermission = Permission::create([
        'name' => 'system.extensions.manage',
        'guard_name' => 'web',
        'is_core' => true,
    ]);

    $this->json('DELETE', "/api/v1/permissions/{$corePermission->id}", [], $jsonApiHeaders)
        ->assertUnprocessable()
        ->assertJsonStructure(['errors' => [['status', 'title']]]);

    $this->json('POST', '/api/v1/permissions', [
        'data' => ['type' => 'permissions', 'attributes' => ['name' => '']],
    ], $jsonApiHeaders)
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.source.pointer', '/data/attributes/name');
});

it('assigns user roles through JSON:API and removes the legacy action', function () use ($jsonApiHeaders) {
    $this->actingAs(User::factory()->create());
    $targetUser = User::factory()->create();
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);

    $this->json('PATCH', "/api/v1/users/{$targetUser->id}/relationships/roles", [
        'data' => [['type' => 'roles', 'id' => (string) $role->id]],
    ], $jsonApiHeaders)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.0.type', 'roles')
        ->assertJsonPath('data.0.id', (string) $role->id);

    expect($targetUser->fresh()->hasRole('editor', 'web'))->toBeTrue();
    $this->json('POST', '/api/v1/roles/assign', [], $jsonApiHeaders)->assertNotFound();
});

it('enforces JSON:API negotiation and returns canonical not-found errors', function () use ($jsonApiHeaders) {
    $this->actingAs(User::factory()->create());

    $this->json('GET', '/api/v1/permissions', [], ['Accept' => 'application/json'])
        ->assertNotAcceptable()
        ->assertHeader('Content-Type', 'application/vnd.api+json');

    $this->json('GET', '/api/v1/roles/999999', [], $jsonApiHeaders)
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});
