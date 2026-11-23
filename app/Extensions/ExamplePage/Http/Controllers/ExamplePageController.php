<?php

declare(strict_types=1);

namespace App\Extensions\ExamplePage\Http\Controllers;

use App\Extensions\ExamplePage\Services\ExamplePageProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Exposes the example page published through the Website Engine.
 */
final class ExamplePageController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly ExamplePageProvider $provider,
    ) {
    }

    /**
     * Return the published example page as a Website Engine page payload.
     *
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        $page = $this->provider->getPage();

        return response()->json(['data' => $page]);
    }
}