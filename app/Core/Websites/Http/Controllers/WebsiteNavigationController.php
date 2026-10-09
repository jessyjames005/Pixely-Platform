<?php

declare(strict_types=1);

namespace App\Core\Websites\Http\Controllers;

use App\Core\Websites\Contracts\WebsiteNavigationProviderInterface;
use Illuminate\Http\JsonResponse;

/**
 * Public API endpoint for persisted website navigation.
 */
final class WebsiteNavigationController
{
    public function __construct(private WebsiteNavigationProviderInterface $provider)
    {
    }

    public function show(string $code): JsonResponse
    {
        return response()->json([
            'data' => $this->provider->navigation($code),
        ]);
    }
}
