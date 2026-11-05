<?php

declare(strict_types=1);

use App\Extensions\CinemaMovie\CinemaMovieExtension;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\Extensions\ExtensionApiTestHelper;

uses(RefreshDatabase::class);

it('provides strict JSON:API request headers', function (): void {
    expect(ExtensionApiTestHelper::jsonApiHeaders())->toBe([
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
    ]);
});

it('creates and explicitly authenticates a user with the requested extension permission', function (): void {
    $permissionName = 'cinema-movie.items.view';

    $user = ExtensionApiTestHelper::createUserWithPermission(
        new CinemaMovieExtension(),
        $permissionName,
    );

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->can($permissionName))->toBeTrue()
        ->and(Permission::query()->where('name', $permissionName)->where('guard_name', 'web')->count())->toBe(1);

    $this->assertGuest();
    $this->actingAs($user);
    $this->assertAuthenticatedAs($user);
});

it('does not create state for a permission the extension has not declared', function (): void {
    expect(fn () => ExtensionApiTestHelper::createUserWithPermission(
        new CinemaMovieExtension(),
        'cinema-movie.unknown.view',
    ))->toThrow(InvalidArgumentException::class);

    expect(Permission::query()->where('name', 'cinema-movie.unknown.view')->exists())->toBeFalse();
});
