<?php

declare(strict_types=1);

use App\Core\Surface\Contracts\SurfaceAwarePolicyInterface;
use App\Core\Surface\Enum\Surface;
use App\Core\Surface\Services\SurfaceAuthorizationService;
use App\Core\Surface\Services\SurfaceContext;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

final class SurfaceAuthorizationResource
{
}

final class SurfaceAuthorizationPolicy implements SurfaceAwarePolicyInterface
{
    public function supportedSurfaces(): array
    {
        return [Surface::ADMIN];
    }

    public function view(User $user, SurfaceAuthorizationResource $resource): bool
    {
        return true;
    }
}

beforeEach(function (): void {
    Gate::policy(SurfaceAuthorizationResource::class, SurfaceAuthorizationPolicy::class);
});

it('enforces the surface before the resource policy', function () {
    $context = app(SurfaceContext::class);
    $context->set(Surface::PUBLIC);

    expect(fn () => app(SurfaceAuthorizationService::class)->authorize(
        new User(),
        'view',
        new SurfaceAuthorizationResource(),
    ))->toThrow(AuthorizationException::class);
});

it('delegates to the resource policy on a supported surface', function () {
    $context = app(SurfaceContext::class);
    $context->set(Surface::ADMIN);

    app(SurfaceAuthorizationService::class)->authorize(
        new User(),
        'view',
        new SurfaceAuthorizationResource(),
    );

    expect(true)->toBeTrue();
});
