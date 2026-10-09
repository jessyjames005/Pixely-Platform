<?php

declare(strict_types=1);

namespace App\Core\Surface\Services;

use App\Core\Surface\Contracts\SurfaceAwarePolicyInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

/**
 * Central authorization entry point for the Surface → Permission → Policy → Resource chain.
 *
 * Route middleware establishes the surface and checks coarse-grained permissions.
 * Policies remain responsible for resource-level authorization. This service adds
 * the missing surface boundary when a policy opts into SurfaceAwarePolicyInterface.
 */
final class SurfaceAuthorizationService
{
    public function __construct(
        private readonly SurfaceContext $context,
    ) {
    }

    /**
     * Authorize an ability against a resource while enforcing its surface boundary.
     *
     * @param Authenticatable $user
     * @param string $ability
     * @param mixed $resource
     *
     * @throws AuthorizationException
     */
    public function authorize(Authenticatable $user, string $ability, mixed $resource = null): void
    {
        $surface = $this->context->current();

        if ($resource !== null && is_object($resource)) {
            $policy = Gate::getPolicyFor($resource);

            if (
                $policy instanceof SurfaceAwarePolicyInterface
                && ! in_array($surface, $policy->supportedSurfaces(), true)
            ) {
                throw new AuthorizationException(
                    "The ability [{$ability}] is not available on the [{$surface->value}] surface."
                );
            }
        }

        Gate::forUser($user)->authorize($ability, $resource);
    }
}
