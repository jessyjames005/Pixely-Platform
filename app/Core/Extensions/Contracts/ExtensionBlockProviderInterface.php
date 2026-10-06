<?php

declare(strict_types=1);

namespace App\Core\Extensions\Contracts;

/**
 * Optional contract for extensions that provide blocks per surface.
 *
 * An extension implements this only if it needs to contribute
 * dynamic block content (widgets, placeholders) for public/user/admin surfaces.
 */
interface ExtensionBlockProviderInterface
{
    /**
     * Return blocks for the given surface.
     *
     * @param string $surface One of: public, user, admin, api
     * @return array<int, array<string, mixed>> Block definitions
     */
    public function blocks(string $surface): array;
}