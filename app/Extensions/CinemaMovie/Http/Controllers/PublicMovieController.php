<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Http\Controllers;

use App\Extensions\CinemaMovie\Services\CinemaMoviePublicPageProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Controller exposing public movie pages for the Website Engine integration.
 */
final class PublicMovieController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly CinemaMoviePublicPageProvider $pageProvider,
    ) {
    }

    /**
     * Show public page data for a movie slug.
     *
     * @param string $slug Movie slug.
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        $page = $this->pageProvider->getMoviePage($slug);

        if (!$page) {
            return response()->json(['error' => 'Movie page not found.'], 404);
        }

        return response()->json([
            'data' => $page,
        ]);
    }
}
