<?php

declare(strict_types=1);

use App\Models\User;
use App\JsonApi\V1\DocumentId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'system.cache.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'system.cache.clear', 'guard_name' => 'web']);
    $this->jsonApiHeaders = [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
    ];
    Redis::connection('tooling')->flushdb();
});

afterEach(function () {
    Redis::connection('tooling')->flushdb();
});

it('requires the system.cache.view permission to list keys', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->json('GET', '/api/v1/system/cache', [], $this->jsonApiHeaders);

    $response->assertForbidden()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('lists matching keys with type and ttl', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.cache.view');
    $this->actingAs($user);

    Redis::connection('tooling')->set('pixely:test:one', 'value');

    $response = $this->json(
        'GET',
        '/api/v1/system/cache?pattern=pixely:test:*',
        [],
        $this->jsonApiHeaders,
    );

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.0.type', 'cache-keys')
        ->assertJsonPath('data.0.id', DocumentId::encode('cache-key', 'pixely:test:one'))
        ->assertJsonPath('data.0.attributes.key', 'pixely:test:one')
        ->assertJsonPath('data.0.attributes.type', 'string');
});

it('displays a string value', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.cache.view');
    $this->actingAs($user);

    Redis::connection('tooling')->set('pixely:test:value', 'hello');

    $response = $this->json(
        'GET',
        '/api/v1/system/cache/pixely:test:value',
        [],
        $this->jsonApiHeaders,
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.type', 'cache-keys')
        ->assertJsonPath('data.id', DocumentId::encode('cache-key', 'pixely:test:value'))
        ->assertJsonPath('data.attributes.value', 'hello');
});

it('returns 404 for a missing key', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.cache.view');
    $this->actingAs($user);

    $response = $this->json('GET', '/api/v1/system/cache/does-not-exist', [], $this->jsonApiHeaders);

    $response->assertNotFound()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'code', 'detail']]]);
});

it('requires system.cache.clear to delete a key, view alone is not enough', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.cache.view');
    $this->actingAs($user);

    Redis::connection('tooling')->set('pixely:test:one', 'value');

    $response = $this->json('DELETE', '/api/v1/system/cache/pixely:test:one', [], $this->jsonApiHeaders);

    $response->assertForbidden()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);

    $flushResponse = $this->json('DELETE', '/api/v1/system/cache', [], $this->jsonApiHeaders);
    $flushResponse->assertForbidden();
});

it('deletes a key with system.cache.clear', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.cache.clear');
    $this->actingAs($user);

    Redis::connection('tooling')->set('pixely:test:one', 'value');

    $response = $this->json('DELETE', '/api/v1/system/cache/pixely:test:one', [], $this->jsonApiHeaders);

    $response->assertNoContent();

    expect(Redis::connection('tooling')->exists('pixely:test:one'))->toBe(0);
});

it('flushes the whole cache with system.cache.clear', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.cache.clear');
    $this->actingAs($user);

    Redis::connection('tooling')->set('pixely:test:one', 'value');
    Redis::connection('tooling')->set('pixely:test:two', 'value');

    $response = $this->json('DELETE', '/api/v1/system/cache', [], $this->jsonApiHeaders);

    $response->assertNoContent();

    expect(Redis::connection('tooling')->keys('pixely:test:*'))->toBeEmpty();
});
