<?php

declare(strict_types=1);

namespace App\Core\Surface\Contracts;

use App\Core\Surface\Enum\Surface;

/**
 * Contract for policies that explicitly restrict an ability to surfaces.
 */
interface SurfaceAwarePolicyInterface
{
    /**
     * @return list<Surface>
     */
    public function supportedSurfaces(): array;
}
