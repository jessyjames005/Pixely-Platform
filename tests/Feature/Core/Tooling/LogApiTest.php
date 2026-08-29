<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'system.logs.view', 'guard_name' => 'web']);
});

it('requires authentication to list log files', function () {
    $response = $this->getJson('/api/v1/system/logs');

    $response->assertStatus(401);
});

it('requires the system.logs.view permission', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->getJson('/api/v1/system/logs');

    $response->assertStatus(403);
});

it('lists log files for a user with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.logs.view');
    $this->actingAs($user);

    Storage::fake();
    $logPath = storage_path('logs/test.log');
    @mkdir(dirname($logPath), 0755, true);
    file_put_contents($logPath, "[2026-08-29 10:00:00] local.INFO: Test message\n");

    $response = $this->getJson('/api/v1/system/logs');

    $response->assertOk();

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

    $response = $this->getJson('/api/v1/system/logs/filter-test.log?level=error');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.level', 'error')
        ->assertJsonPath('data.0.message', "Error message\nwith a stack trace line");

    @unlink($logPath);
});

it('returns 404 for a non-existent log file', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.logs.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/system/logs/does-not-exist.log');

    $response->assertNotFound();
});

it('prevents directory traversal in the filename', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.logs.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/system/logs/' . urlencode('../../.env'));

    $response->assertNotFound();
});
