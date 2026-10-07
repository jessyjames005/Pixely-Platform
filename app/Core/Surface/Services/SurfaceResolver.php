<?php

declare(strict_types=1);

namespace App\Core\Surface\Services;

use App\Core\Surface\Enum\Surface;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class SurfaceResolver
{
    /**
     * Resolve the surface from the explicit route middleware value.
     *
     * Explicit route metadata keeps the backend authoritative and avoids
     * guessing a security boundary from URL prefixes alone.
     */
    public function fromRouteValue(string $value): Surface
    {
        return Surface::fromValue($value);
    }

    /**
     * Resolve a surface from a request attribute when one was already set.
     */
    public function fromRequest(Request $request): ?Surface
    {
        $value = $request->attributes->get('pixely.surface');

        if ($value instanceof Surface) {
            return $value;
        }

        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException('The Pixely surface request attribute must be a string or Surface.');
        }

        return Surface::fromValue($value);
    }
}
