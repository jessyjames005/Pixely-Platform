<?php

declare(strict_types=1);

use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\Enum\ExtensionStatus;
use App\Core\Extensions\Manager\ExtensionManager;
use App\Core\Extensions\Repositories\InMemoryExtensionStateRepository;
use App\Core\Extensions\State\ExtensionState;
use App\JsonApi\V1\DocumentId;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'system.extensions.manage', 'guard_name' => 'web']);

    $this->app->instance(
        ExtensionStateRepositoryInterface::class,
        new InMemoryExtensionStateRepository(),
    );
    $this->app->forgetInstance(ExtensionManager::class);

    $manager = $this->app->make(ExtensionManager::class);
    $galleryExtension = $manager->all()['gallery'];
    $filesExtension = $manager->all()['files'];
    $stateRepository = $this->app->make(ExtensionStateRepositoryInterface::class);

    $stateRepository->save(new ExtensionState(extension: $galleryExtension, status: ExtensionStatus::Disabled));
    $stateRepository->save(new ExtensionState(extension: $filesExtension, status: ExtensionStatus::Enabled));
    $this->galleryId = DocumentId::encode('extension', 'gallery');
});

it('syncs gallery permissions when the extension is enabled via the API', function () {
    // Simulate permissions never having existed for gallery
    Permission::where('name', 'like', 'gallery.%')->delete();

    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.manage');
    $this->actingAs($user);

    expect(Permission::where('name', 'gallery.photos.view')->exists())->toBeFalse();

    $response = $this->json(
        'POST',
        '/api/v1/extensions/' . $this->galleryId . '/enable',
        [],
        ['Accept' => 'application/vnd.api+json', 'Content-Type' => 'application/vnd.api+json'],
    );

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'extensions')
        ->assertJsonPath('data.attributes.enabled', true);

    expect(Permission::where('name', 'gallery.photos.view')->exists())->toBeTrue();
    expect(Permission::where('name', 'gallery.photos.manage')->exists())->toBeTrue();
    expect(Permission::where('name', 'gallery.photos.delete')->exists())->toBeTrue();
});
