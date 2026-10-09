<?php

declare(strict_types=1);

use App\Core\Extensions\Manager\ExtensionManager;
use App\Core\Extensions\Repositories\InMemoryExtensionStateRepository;
use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\Enum\ExtensionStatus;
use App\Core\Extensions\State\ExtensionState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->app->singleton(
        ExtensionStateRepositoryInterface::class,
        InMemoryExtensionStateRepository::class,
    );

    $manager = $this->app->make(ExtensionManager::class);

    foreach ($manager->all() as $extension) {
        $this->app->make(ExtensionStateRepositoryInterface::class)->save(
            new ExtensionState($extension, ExtensionStatus::Enabled),
        );
    }
});

it('registers extension API routes through the SDK v2 route registrar', function (): void {
    $route = collect(app('router')->getRoutes()->getRoutes())
        ->first(static fn ($route): bool => $route->uri() === 'api/v1/photos');

    expect($route)->not->toBeNull();
    expect($route->gatherMiddleware())->toContain('surface:api');
});

it('returns only authorized enabled extension navigation', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('gallery.photos.view');
    $user->givePermissionTo('files.view');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/extensions/navigation')
        ->assertOk()
        ->assertJsonFragment(['id' => 'gallery'])
        ->assertJsonFragment(['id' => 'files'])
        ->assertJsonMissing(['id' => 'tuleap']);
});

it('requires authentication for extension navigation', function (): void {
    $this->getJson('/api/v1/extensions/navigation')->assertUnauthorized();
});

it('does not expose disabled extensions through navigation', function (): void {
    $manager = $this->app->make(ExtensionManager::class);
    $manager->disable('gallery');

    $user = User::factory()->create();
    $user->givePermissionTo('gallery.photos.view');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/extensions/navigation')
        ->assertOk()
        ->assertJsonMissing(['id' => 'gallery']);
});


it('exposes the block registry through the protected extension API', function (): void {
    \Spatie\Permission\Models\Permission::firstOrCreate([
        'name' => 'system.extensions.view',
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.view');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/extensions/blocks')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});
