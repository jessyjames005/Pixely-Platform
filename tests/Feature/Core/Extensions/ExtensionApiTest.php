<?php

declare(strict_types=1);

use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\Repositories\InMemoryExtensionStateRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'system.extensions.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'system.extensions.manage', 'guard_name' => 'web']);

    // Extension state is normally persisted to a real JSON file on disk.
    // Swap it for an in-memory repository during tests so enabling or
    // disabling an extension never leaks between test runs.
    $this->app->singleton(
        ExtensionStateRepositoryInterface::class,
        InMemoryExtensionStateRepository::class,
    );

    // The extension is already registered in the (unaffected) registry
    // from the initial Kernel boot. Only seed its *state* directly in
    // the fresh in-memory repository, without re-registering it.
    $manager = $this->app->make(\App\Core\Extensions\Manager\ExtensionManager::class);
    $galleryExtension = $manager->all()['gallery'];

    $this->app->make(ExtensionStateRepositoryInterface::class)->save(
        new \App\Core\Extensions\State\ExtensionState(
            extension: $galleryExtension,
            status: \App\Core\Extensions\Enum\ExtensionStatus::Enabled,
        ),
    );
});

it('requires authentication to list extensions', function () {
    $response = $this->getJson('/api/v1/extensions');

    $response->assertStatus(401);
});

it('requires system.extensions.view to list extensions', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->getJson('/api/v1/extensions');

    $response->assertStatus(403);
});

it('lists registered extensions with their state', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/extensions');

    $response
        ->assertOk()
        ->assertJsonFragment(['id' => 'gallery']);
});

it('displays a single extension detail', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/extensions/gallery');

    $response
        ->assertOk()
        ->assertJsonPath('data.id', 'gallery')
        ->assertJsonStructure(['data' => ['id', 'name', 'version', 'dependencies', 'enabled', 'path', 'providers']]);
});

it('returns 404 for an unknown extension', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/extensions/does-not-exist');

    $response->assertNotFound();
});

it('requires system.extensions.manage (not just view) to enable/disable', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.view');
    $this->actingAs($user);

    $response = $this->postJson('/api/v1/extensions/gallery/disable');

    $response->assertStatus(403);
});

it('disables and re-enables an extension', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.manage');
    $this->actingAs($user);

    $this->postJson('/api/v1/extensions/gallery/disable')
        ->assertOk()
        ->assertJsonPath('data.enabled', false);

    $this->postJson('/api/v1/extensions/gallery/enable')
        ->assertOk()
        ->assertJsonPath('data.enabled', true);
});

it('records an audit log entry when enabling/disabling', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.manage');
    $this->actingAs($user);

    $this->postJson('/api/v1/extensions/gallery/disable')->assertOk();

    $this->assertDatabaseHas('extension_audit_logs', [
        'extension_id' => 'gallery',
        'action' => 'disable',
        'user_id' => $user->id,
    ]);
});

it('reads and updates an extension configuration', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.view');
    $user->givePermissionTo('system.extensions.manage');
    $this->actingAs($user);

    $this->putJson('/api/v1/extensions/gallery/config', ['max_upload_size' => 5])
        ->assertOk()
        ->assertJsonPath('data.max_upload_size', 5);

    $this->getJson('/api/v1/extensions/gallery/config')
        ->assertOk()
        ->assertJsonPath('data.max_upload_size', 5);
});
