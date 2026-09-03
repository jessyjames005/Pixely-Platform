<?php

declare(strict_types=1);

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Permissions\ExtensionPermissionsInterface;
use App\Core\Extensions\Permissions\ExtensionPermissionSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

// A fake extension declaring permissions, for isolated unit testing —
// does not touch any real extension in app/Extensions.
final class FakePermissionDeclaringExtension implements ExtensionInterface, ExtensionPermissionsInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'fake-perm-ext',
            name: 'Fake',
            version: '1.0.0',
            class: self::class,
            path: 'tests/Fakes',
            dependencies: [],
        );
    }

    public function declaredPermissions(): array
    {
        return ['fake.widgets.view', 'fake.widgets.manage'];
    }

    public function providers(): array { return []; }
    public function boot(): void {}
}

final class FakeNonPermissionDeclaringExtension implements ExtensionInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'fake-no-perm-ext',
            name: 'Fake No Perm',
            version: '1.0.0',
            class: self::class,
            path: 'tests/Fakes',
            dependencies: [],
        );
    }

    public function providers(): array { return []; }
    public function boot(): void {}
}

it('creates permissions declared by an extension', function () {
    $synchronizer = new ExtensionPermissionSynchronizer();

    $created = $synchronizer->sync(new FakePermissionDeclaringExtension());

    expect($created)->toBe(['fake.widgets.view', 'fake.widgets.manage']);
    expect(Permission::where('name', 'fake.widgets.view')->exists())->toBeTrue();
    expect(Permission::where('name', 'fake.widgets.manage')->exists())->toBeTrue();
});

it('creates permissions with the web guard', function () {
    $synchronizer = new ExtensionPermissionSynchronizer();

    $synchronizer->sync(new FakePermissionDeclaringExtension());

    expect(Permission::where('name', 'fake.widgets.view')->first()->guard_name)->toBe('web');
});

it('does not report already-existing permissions as newly created', function () {
    $synchronizer = new ExtensionPermissionSynchronizer();

    $synchronizer->sync(new FakePermissionDeclaringExtension());
    $secondRun = $synchronizer->sync(new FakePermissionDeclaringExtension());

    expect($secondRun)->toBe([]);
    expect(Permission::where('name', 'fake.widgets.view')->count())->toBe(1);
});

it('does nothing for an extension that does not declare permissions', function () {
    $synchronizer = new ExtensionPermissionSynchronizer();

    $created = $synchronizer->sync(new FakeNonPermissionDeclaringExtension());

    expect($created)->toBe([]);
    expect(Permission::count())->toBe(0);
});
