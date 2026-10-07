<?php

declare(strict_types=1);

namespace App\Core\Surface\Http\Middleware;

use App\Core\Surface\Services\SurfaceContext;
use App\Core\Surface\Services\SurfaceResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Establishes the authoritative Pixely surface for an HTTP request.
 */
final class ResolveSurface
{
    public function __construct(
        private readonly SurfaceResolver $resolver,
        private readonly SurfaceContext $context,
    ) {
    }

    public function handle(Request $request, Closure $next, string $surface): Response
    {
        $resolved = $this->resolver->fromRouteValue($surface);

        $request->attributes->set('pixely.surface', $resolved);
        $this->context->set($resolved);

        return $next($request);
    }
}
