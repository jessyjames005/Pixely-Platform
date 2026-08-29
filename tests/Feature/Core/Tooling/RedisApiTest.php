<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'system.cache.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'system.cache.clear', 'guard_name' => 'web']);
    Redis::connection('tooling')->flushdb();
});

afterEach(function () {
    Redis::connection('tooling')->flushdb();
});

it('requires the system.cache.view permission to list keys', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->getJson('/api/v1/system/cache');

    $response->assertStatus(403);
});

it('lists matching keys with type and ttl', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.cache.view');
    $this->actingAs($user);

    Redis::connection('tooling')->set('pixely:test:one', 'value');

    $response = $this->getJson('/api/v1/system/cache?pattern=pixely:test:*');

    $response
        ->assertOk()
        ->assertJsonPath('data.0.key', 'pixely:test:one')
        ->assertJsonPath('data.0.type', 'string');
});

it('displays a string value', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.cache.view');
    $this->actingAs($user);

    Redis::connection('tooling')->set('pixely:test:value', 'hello');

    $response = $this->getJson('/api/v1/system/cache/pixely:test:value');

    $response
        ->assertOk()
        ->assertJsonPath('data.value', 'hello');
});

it('returns 404 for a missing key', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.cache.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/system/cache/does-not-exist');

    $response->assertNotFound();
});

it('requires system.cache.clear to delete a key, view alone is not enough', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.cache.view');
    $this->actingAs($user);

    Redis::connection('tooling')->set('pixely:test:one', 'value');

    $response = $this->deleteJson('/api/v1/system/cache/pixely:test:one');

    $response->assertStatus(403);
});

it('deletes a key with system.cache.clear', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.cache.clear');
    $this->actingAs($user);

    Redis::connection('tooling')->set('pixely:test:one', 'value');

    $response = $this->deleteJson('/api/v1/system/cache/pixely:test:one');

    $response->assertNoContent();

    expect(Redis::connection('tooling')->exists('pixely:test:one'))->toBe(0);
});

it('flushes the whole cache with system.cache.clear', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.cache.clear');
    $this->actingAs($user);

    Redis::connection('tooling')->set('pixely:test:one', 'value');
    Redis::connection('tooling')->set('pixely:test:two', 'value');

    $response = $this->deleteJson('/api/v1/system/cache');

    $response->assertNoContent();

    expect(Redis::connection('tooling')->keys('pixely:test:*'))->toBeEmpty();
});
