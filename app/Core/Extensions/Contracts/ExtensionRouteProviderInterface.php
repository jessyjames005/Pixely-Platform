<?php

declare(strict_types=1);

namespace App\Core\Extensions\Contracts;

/**
 * Optional contract for extensions that provide routes per surface.
 *
 * An extension implements this only if it needs to register routes
 * for public, user, or admin surfaces beyond the default /admin prefix.
 */
interface ExtensionRouteProviderInterface
{
    /**
     * Return routes for the given surface.
     *
     * @param string $surface One of: public, user, admin, api
     * @return array<int, array<string, mixed>> Route definitions compatible with Vue Router
     */
    public function routes(string $surface): array;
}