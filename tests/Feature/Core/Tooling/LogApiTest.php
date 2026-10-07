<?php

declare(strict_types=1);

use App\Models\User;
use App\JsonApi\V1\DocumentId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'system.logs.view', 'guard_name' => 'web']);
    $this->jsonApiHeaders = [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
    ];
});

it('requires authentication to list log files', function () {
    $response = $this->json('GET', '/api/v1/system/logs', [], $this->jsonApiHeaders);

    $response->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('requires the system.logs.view permission', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->json('GET', '/api/v1/system/logs', [], $this->jsonApiHeaders);

    $response->assertForbidden()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('lists log files for a user with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.logs.view');
    $this->actingAs($user);

    Storage::fake();
    $logPath = storage_path('logs/test.log');
    @mkdir(dirname($logPath), 0755, true);
    file_put_contents($logPath, "[2026-08-29 10:00:00] local.INFO: Test message\n");

    $response = $this->json('GET', '/api/v1/system/logs', [], $this->jsonApiHeaders);

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json');

    $entry = collect($response->json('data'))->firstWhere('attributes.filename', 'test.log');

    expect($entry)->not->toBeNull()
        ->and($entry['type'])->toBe('log-files')
        ->and($entry['id'])->toBe(DocumentId::encode('log-file', 'test.log'));

    @unlink($logPath);
});

it('returns parsed entries filtered by level', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.logs.view');
    $this->actingAs($user);

    $logPath = storage_path('logs/filter-test.log');
    file_put_contents(
        $logPath,
        "[2026-08-29 10:00:00] local.INFO: Info message\n" .
            "[2026-08-29 10:00:01] local.ERROR: Error message\n" .
            "with a stack trace line\n",
    );

    $response = $this->json(
        'GET',
        '/api/v1/system/logs/filter-test.log?level=error',
        [],
        $this->jsonApiHeaders,
    );

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'log-entries')
        ->assertJsonPath('data.0.id', DocumentId::encode('log-entry', 'filter-test.log', '1'))
        ->assertJsonPath('data.0.attributes.level', 'error')
        ->assertJsonPath('data.0.attributes.message', "Error message\nwith a stack trace line");

    @unlink($logPath);
});

it('returns 404 for a non-existent log file', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.logs.view');
    $this->actingAs($user);

    $response = $this->json(
        'GET',
        '/api/v1/system/logs/does-not-exist.log',
        [],
        $this->jsonApiHeaders,
    );

    $response->assertNotFound()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'detail']]]);
});

it('prevents directory traversal in the filename', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.logs.view');
    $this->actingAs($user);

    $response = $this->json(
        'GET',
        '/api/v1/system/logs/' . urlencode('../../.env'),
        [],
        $this->jsonApiHeaders,
    );

    $response->assertNotFound();
});

it('enforces JSON:API Accept negotiation', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.logs.view');
    $this->actingAs($user);

    $this->json('GET', '/api/v1/system/logs', [], ['Accept' => 'application/json'])
        ->assertNotAcceptable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'code', 'detail']]]);
});
