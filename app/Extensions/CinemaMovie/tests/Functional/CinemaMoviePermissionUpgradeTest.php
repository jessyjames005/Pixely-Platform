<?php

declare(strict_types=1);

use App\Core\Extensions\Versioning\ExtensionUpgradeRunner;
use App\Core\Extensions\Versioning\ExtensionVersionRepository;
use App\Extensions\CinemaMovie\CinemaMovieExtension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('renames legacy movie permissions and preserves role and direct-user assignments', function (): void {
    $legacy = Permission::query()->create([
        'name' => 'cinema-movie.items.view',
        'guard_name' => 'web',
    ]);
    $role = Role::query()->create(['name' => 'Cinema editor', 'guard_name' => 'web']);
    $role->givePermissionTo($legacy);
    $user = User::factory()->create();
    $user->givePermissionTo($legacy);

    app(ExtensionVersionRepository::class)->set('cinema-movie', '1.0.0');
    app(ExtensionUpgradeRunner::class)->upgrade(new CinemaMovieExtension(), '1.1.0');

    expect(Permission::query()->where('name', 'cinema-movie.items.view')->exists())->toBeFalse()
        ->and(Permission::query()->where('name', 'cinema-movie.movies.view')->exists())->toBeTrue()
        ->and($role->fresh()->hasPermissionTo('cinema-movie.movies.view'))->toBeTrue()
        ->and($user->fresh()->can('cinema-movie.movies.view'))->toBeTrue();
});

it('does not duplicate a role assignment when both permission names already exist', function (): void {
    $legacy = Permission::query()->create([
        'name' => 'cinema-movie.items.manage',
        'guard_name' => 'web',
    ]);
    $current = Permission::query()->create([
        'name' => 'cinema-movie.movies.manage',
        'guard_name' => 'web',
    ]);
    $role = Role::query()->create(['name' => 'Cinema manager', 'guard_name' => 'web']);
    $role->givePermissionTo([$legacy, $current]);

    app(ExtensionVersionRepository::class)->set('cinema-movie', '1.0.0');
    app(ExtensionUpgradeRunner::class)->upgrade(new CinemaMovieExtension(), '1.1.0');

    expect($role->fresh()->permissions()->where('name', 'cinema-movie.movies.manage')->count())->toBe(1)
        ->and(Permission::query()->whereKey($legacy->id)->exists())->toBeFalse();
});
