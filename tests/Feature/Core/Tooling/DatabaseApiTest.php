<?php

declare(strict_types=1);

use App\Models\User;
use App\JsonApi\V1\DocumentId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'system.database.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'system.sql.query', 'guard_name' => 'web']);
    $this->jsonApiHeaders = [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
    ];
});

it('requires the system.database.view permission to list tables', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->json('GET', '/api/v1/system/database/tables', [], $this->jsonApiHeaders);

    $response->assertForbidden()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('lists tables for a user with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.database.view');
    $this->actingAs($user);

    $response = $this->json('GET', '/api/v1/system/database/tables', [], $this->jsonApiHeaders);

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.0.type', 'database-tables')
        ->assertJsonPath('data.0.id', DocumentId::encode('database-table', $response->json('data.0.attributes.name')));
});

it('previews table rows with sensitive columns redacted', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.database.view');
    $this->actingAs($user);

    $response = $this->json(
        'GET',
        '/api/v1/system/database/tables/users/preview',
        [],
        $this->jsonApiHeaders,
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.0.type', 'database-rows')
        ->assertJsonPath('data.0.id', DocumentId::encode('database-table-row', 'users', '0'))
        ->assertJsonMissingPath('data.0.attributes.values.password')
        ->assertJsonMissingPath('data.0.attributes.values.remember_token');
});

it('returns 404 for a non-existent table', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.database.view');
    $this->actingAs($user);

    $response = $this->json(
        'GET',
        '/api/v1/system/database/tables/does_not_exist/preview',
        [],
        $this->jsonApiHeaders,
    );

    $response->assertNotFound()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'detail']]]);
});

it('requires system.sql.query, separate from system.database.view, to run ad-hoc queries', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.database.view');
    $this->actingAs($user);

    $response = $this->json('POST', '/api/v1/system/database/query', [
        'data' => ['type' => 'sql-queries', 'attributes' => ['sql' => 'SELECT 1']],
    ], $this->jsonApiHeaders);

    $response->assertForbidden()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('executes a safe SELECT query', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.sql.query');
    $this->actingAs($user);

    $response = $this->json('POST', '/api/v1/system/database/query', [
        'data' => ['type' => 'sql-queries', 'attributes' => ['sql' => 'SELECT 1 AS value']],
    ], $this->jsonApiHeaders);
    $expectedRowId = DocumentId::encode(
        'database-query-row',
        hash('sha256', 'SELECT 1 AS value LIMIT 500'),
        '0',
    );

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.0.type', 'database-rows')
        ->assertJsonPath('data.0.id', $expectedRowId)
        ->assertJsonPath('data.0.attributes.values.value', 1);
});

it('rejects a non-SELECT query with a 422 and no execution', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.sql.query');
    $this->actingAs($user);

    $response = $this->json('POST', '/api/v1/system/database/query', [
        'data' => ['type' => 'sql-queries', 'attributes' => ['sql' => 'DELETE FROM users']],
    ], $this->jsonApiHeaders);

    $response
        ->assertStatus(422)
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('errors.0.code', 'UNSAFE_QUERY');
});

it('rejects stacked statements attempting to smuggle a write', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.sql.query');
    $this->actingAs($user);

    $response = $this->json('POST', '/api/v1/system/database/query', [
        'data' => ['type' => 'sql-queries', 'attributes' => ['sql' => 'SELECT 1; DELETE FROM users;']],
    ], $this->jsonApiHeaders);

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'UNSAFE_QUERY');
});

it('returns canonical JSON:API validation errors for malformed query documents', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.sql.query');
    $this->actingAs($user);

    $this->json('POST', '/api/v1/system/database/query', [
        'data' => ['type' => 'sql-queries', 'attributes' => []],
    ], $this->jsonApiHeaders)
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'detail']]]);
});

it('enforces JSON:API content negotiation for SQL query requests', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.sql.query');
    $this->actingAs($user);

    $this->json('POST', '/api/v1/system/database/query', [
        'data' => ['type' => 'sql-queries', 'attributes' => ['sql' => 'SELECT 1']],
    ], ['Accept' => 'application/vnd.api+json', 'Content-Type' => 'application/json'])
        ->assertStatus(415)
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'code', 'detail']]]);
});
