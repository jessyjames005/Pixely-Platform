<?php

declare(strict_types=1);

namespace App\Core\Websites\Contracts;

use App\Core\Websites\Models\Menu;

/**
 * Resolves a persisted website menu into navigation data suitable for a surface.
 */
interface WebsiteNavigationProviderInterface
{
    /**
     * Return the active navigation items for a public-facing menu.
     *
     * @return array<int, array<string, mixed>>
     */
    public function navigation(string $code): array;
}
