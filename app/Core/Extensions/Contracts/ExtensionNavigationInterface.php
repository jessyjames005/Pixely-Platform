<?php

declare(strict_types=1);

namespace App\Core\Extensions\Contracts;

/**
 * Optional contract for extensions that provide navigation items for surfaces.
 *
 * An extension implements this only if it needs to contribute navigation
 * items to public, user, or admin surfaces.
 */
interface ExtensionNavigationInterface
{
    /**
     * Return navigation items for the given surface.
     *
     * @param string $surface One of: public, user, admin, api
     * @return array<int, array<string, mixed>> Navigation items compatible with NavItem type
     */
    public function navigation(string $surface): array;
}
