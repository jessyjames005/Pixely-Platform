<?php

declare(strict_types=1);

use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\Enum\ExtensionStatus;
use App\Core\Extensions\Manager\ExtensionManager;
use App\Core\Extensions\Repositories\InMemoryExtensionStateRepository;
use App\Core\Extensions\State\ExtensionState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'system.extensions.manage', 'guard_name' => 'web']);

    $this->app->singleton(
        ExtensionStateRepositoryInterface::class,
        InMemoryExtensionStateRepository::class,
    );

    $manager = $this->app->make(ExtensionManager::class);
    $galleryExtension = $manager->all()['gallery'];

    $this->app->make(ExtensionStateRepositoryInterface::class)->save(
        new ExtensionState(extension: $galleryExtension, status: ExtensionStatus::Disabled),
    );
});

it('syncs gallery permissions when the extension is enabled via the API', function () {
    // Simulate permissions never having existed for gallery
    Permission::where('name', 'like', 'gallery.%')->delete();

    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.manage');
    $this->actingAs($user);

    expect(Permission::where('name', 'gallery.photos.view')->exists())->toBeFalse();

    $response = $this->postJson('/api/v1/extensions/gallery/enable');

    $response->assertOk();

    expect(Permission::where('name', 'gallery.photos.view')->exists())->toBeTrue();
    expect(Permission::where('name', 'gallery.photos.manage')->exists())->toBeTrue();
    expect(Permission::where('name', 'gallery.photos.delete')->exists())->toBeTrue();
});
