<?php

declare(strict_types=1);

use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\Repositories\InMemoryExtensionStateRepository;
use App\JsonApi\V1\DocumentId;
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
    $this->app->instance(
        ExtensionStateRepositoryInterface::class,
        new InMemoryExtensionStateRepository(),
    );
    $this->app->forgetInstance(\App\Core\Extensions\Manager\ExtensionManager::class);

    // The extension is already registered in the (unaffected) registry
    // from the initial Kernel boot. Only seed its *state* directly in
    // the fresh in-memory repository, without re-registering it.
    $manager = $this->app->make(\App\Core\Extensions\Manager\ExtensionManager::class);
    $galleryExtension = $manager->all()['gallery'];
    $filesExtension = $manager->all()['files'];

    $stateRepository = $this->app->make(ExtensionStateRepositoryInterface::class);
    $stateRepository->save(
        new \App\Core\Extensions\State\ExtensionState(
            extension: $galleryExtension,
            status: \App\Core\Extensions\Enum\ExtensionStatus::Enabled,
        ),
    );
    $stateRepository->save(
        new \App\Core\Extensions\State\ExtensionState(
            extension: $filesExtension,
            status: \App\Core\Extensions\Enum\ExtensionStatus::Enabled,
        ),
    );

    $this->galleryId = DocumentId::encode('extension', 'gallery');
    $this->filesId = DocumentId::encode('extension', 'files');
    $this->galleryConfigurationId = DocumentId::encode('extension-configuration', 'gallery');
    $this->jsonApiHeaders = [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
    ];
});

it('requires authentication to list extensions', function () {
    $response = $this->json('GET', '/api/v1/extensions', [], $this->jsonApiHeaders);

    $response
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('requires system.extensions.view to list extensions', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->json('GET', '/api/v1/extensions', [], $this->jsonApiHeaders);

    $response
        ->assertForbidden()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('lists registered extensions with their state', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.view');
    $this->actingAs($user);

    $response = $this->json('GET', '/api/v1/extensions', [], $this->jsonApiHeaders);

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.0.type', 'extensions');

    $gallery = collect($response->json('data'))->firstWhere('id', $this->galleryId);
    expect($gallery)->not->toBeNull()
        ->and($gallery['attributes'])->toBe([
            'name' => 'Gallery',
            'version' => '1.0.0',
            'dependencies' => ['files'],
            'enabled' => true,
        ]);
});

it('displays a single extension detail', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.view');
    $this->actingAs($user);

    $response = $this->json('GET', '/api/v1/extensions/' . $this->galleryId, [], $this->jsonApiHeaders);

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'extensions')
        ->assertJsonPath('data.id', $this->galleryId)
        ->assertJsonStructure([
            'data' => ['id', 'type', 'attributes' => ['name', 'version', 'dependencies', 'enabled']],
        ]);

    expect($response->getContent())->not->toContain(base_path(), 'providers', 'GalleryExtension');
});

it('returns 404 for an unknown extension', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.view');
    $this->actingAs($user);

    $unknownId = DocumentId::encode('extension', 'does-not-exist');
    $response = $this->json('GET', '/api/v1/extensions/' . $unknownId, [], $this->jsonApiHeaders);

    $response
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'code', 'detail']]]);
});

it('requires system.extensions.manage (not just view) to enable/disable', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.view');
    $this->actingAs($user);

    $response = $this->json('POST', '/api/v1/extensions/' . $this->galleryId . '/disable', [], $this->jsonApiHeaders);

    $response->assertForbidden()->assertHeader('Content-Type', 'application/vnd.api+json');
});

it('disables and re-enables an extension', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.manage');
    $this->actingAs($user);

    $this->json('POST', '/api/v1/extensions/' . $this->galleryId . '/disable', [], $this->jsonApiHeaders)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'extensions')
        ->assertJsonPath('data.id', $this->galleryId)
        ->assertJsonPath('data.attributes.enabled', false);

    $this->json('POST', '/api/v1/extensions/' . $this->galleryId . '/enable', [], $this->jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.attributes.enabled', true);
});

it('records an audit log entry when enabling/disabling', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.manage');
    $this->actingAs($user);

    $this->json('POST', '/api/v1/extensions/' . $this->galleryId . '/disable', [], $this->jsonApiHeaders)->assertOk();

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

    $this->json('PUT', '/api/v1/extensions/' . $this->galleryId . '/config', [
        'data' => [
            'type' => 'extension-configurations',
            'id' => $this->galleryConfigurationId,
            'attributes' => ['values' => ['max_upload_size' => 5]],
        ],
    ], $this->jsonApiHeaders)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'extension-configurations')
        ->assertJsonPath('data.id', $this->galleryConfigurationId)
        ->assertJsonPath('data.attributes.values.max_upload_size', 5);

    $this->json('GET', '/api/v1/extensions/' . $this->galleryId . '/config', [], $this->jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.attributes.values.max_upload_size', 5)
        ->assertJsonStructure(['data' => ['id', 'type', 'attributes' => ['defaults', 'values']]]);
});

it('returns declared defaults for a never-configured extension, not an empty payload', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.view');
    $this->actingAs($user);

    $response = $this->json(
        'GET',
        '/api/v1/extensions/' . $this->filesId . '/config',
        [],
        $this->jsonApiHeaders,
    )->assertOk();

    $response->assertJsonPath('data.type', 'extension-configurations');
    $response->assertJsonPath('data.attributes.defaults.max_file_size_kb', 5120);
    $response->assertJsonPath('data.attributes.values.max_file_size_kb', 5120);
});

it('does not serialize extension configuration secrets', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.view');
    $this->actingAs($user);

    $this->app->make(\App\Core\Extensions\Configuration\ExtensionConfigurationRepositoryInterface::class)->save('gallery', [
        'max_upload_size' => 5,
        'api_token' => 'extension-api-secret',
        'nested' => ['private_key' => 'nested-private-secret'],
    ]);

    $response = $this->json('GET', '/api/v1/extensions/' . $this->galleryId . '/config', [], $this->jsonApiHeaders);

    $response->assertOk()->assertJsonPath('data.attributes.values.max_upload_size', 5);
    expect($response->getContent())->not->toContain(
        'api_token',
        'extension-api-secret',
        'private_key',
        'nested-private-secret',
    );
});

it('enforces JSON:API Accept negotiation on extension endpoints', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.view');
    $this->actingAs($user);

    $this->json(
        'GET',
        '/api/v1/extensions',
        [],
        ['Accept' => 'application/json'],
    )
        ->assertNotAcceptable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'code', 'detail']]]);
});
