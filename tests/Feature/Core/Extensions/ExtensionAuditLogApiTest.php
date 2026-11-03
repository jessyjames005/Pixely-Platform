<?php

declare(strict_types=1);

use App\Core\Extensions\Audit\ExtensionAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->jsonApiHeaders = ['Accept' => 'application/vnd.api+json'];
    Permission::firstOrCreate(['name' => 'system.extensions.install', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'system.extensions.view', 'guard_name' => 'web']);
});

it('requires authentication and the install permission to read extension audit logs', function () {
    $this->get('/api/v1/extensions/audit-logs', $this->jsonApiHeaders)
        ->assertUnauthorized();

    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.view');
    $this->actingAs($user);

    $this->get('/api/v1/extensions/audit-logs', $this->jsonApiHeaders)
        ->assertForbidden();
});

it('returns paginated and filtered audit events', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.install');
    $this->actingAs($user);

    ExtensionAuditLog::forceCreate([
        'extension_id' => 'gallery',
        'action' => 'update',
        'version' => '2.0.0',
        'details' => ['outcome' => 'failure', 'stage' => 'upgrade'],
        'created_at' => now()->subMinute(),
    ]);
    ExtensionAuditLog::forceCreate([
        'extension_id' => 'gallery',
        'action' => 'update',
        'version' => '1.9.0',
        'details' => ['outcome' => 'failure', 'stage' => 'migrations'],
        'created_at' => now(),
    ]);
    ExtensionAuditLog::create([
        'extension_id' => 'other',
        'action' => 'install',
        'version' => '1.0.0',
        'details' => ['outcome' => 'success', 'stage' => 'install'],
    ]);

    $response = $this->get('/api/v1/extensions/audit-logs?page=2&per_page=1&extension_id=gallery&action=update&outcome=failure', $this->jsonApiHeaders);

    $response
        ->assertOk()
        ->assertJsonPath('data.0.type', 'extension-audit-logs')
        ->assertJsonPath('data.0.attributes.extension_id', 'gallery')
        ->assertJsonPath('data.0.attributes.action', 'update')
        ->assertJsonPath('data.0.attributes.version', '2.0.0')
        ->assertJsonPath('meta.pagination.current_page', 2)
        ->assertJsonPath('meta.pagination.last_page', 2)
        ->assertJsonPath('meta.pagination.per_page', 1)
        ->assertJsonPath('meta.pagination.total', 2)
        ->assertJsonPath('links.prev', fn (string $url): bool => str_contains($url, 'page=1'))
        ->assertJsonPath('links.next', null);
});

it('validates pagination and filter parameters', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.install');
    $this->actingAs($user);

    $this->get('/api/v1/extensions/audit-logs?page=0&per_page=101&extension_id=../&action=drop&outcome=unknown', $this->jsonApiHeaders)
        ->assertUnprocessable()
        ->assertJsonStructure(['errors' => [['code', 'detail']]]);
});

it('exposes only safe event details and does not expose package or exception data', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.install');
    $this->actingAs($user);

    ExtensionAuditLog::create([
        'extension_id' => 'gallery',
        'action' => 'preflight',
        'version' => '1.2.0',
        'details' => [
            'outcome' => 'failure',
            'stage' => 'preflight',
            'description' => 'untrusted package-provided text',
            'exception' => '/secret/path.zip: database credentials',
            'package_data' => ['token' => 'must-not-leak'],
        ],
    ]);

    $response = $this->get('/api/v1/extensions/audit-logs', $this->jsonApiHeaders);

    $response
        ->assertOk()
        ->assertJsonPath('data.0.attributes.outcome', 'failure')
        ->assertJsonPath('data.0.attributes.stage', 'preflight')
        ->assertJsonPath('data.0.attributes.details', [
            'outcome' => 'failure',
            'stage' => 'preflight',
        ])
        ->assertDontSee('untrusted package-provided text')
        ->assertDontSee('database credentials')
        ->assertDontSee('must-not-leak');
});
