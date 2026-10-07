<?php

declare(strict_types=1);

namespace App\Core\Surface\Services;

use App\Core\Surface\Enum\Surface;
use LogicException;

/**
 * Holds the surface resolved for the current HTTP request.
 *
 * The context is request-scoped and is therefore never a global source of
 * authorization. It only provides the current surface to policies/services;
 * authentication and permissions remain enforced independently.
 */
final class SurfaceContext
{
    private ?Surface $surface = null;

    public function set(Surface $surface): void
    {
        $this->surface = $surface;
    }

    public function current(): Surface
    {
        return $this->surface
            ?? throw new LogicException('No Pixely surface has been resolved for the current request.');
    }

    public function has(): bool
    {
        return $this->surface !== null;
    }

    public function is(Surface $surface): bool
    {
        return $this->surface === $surface;
    }
}
