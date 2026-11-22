<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Http\Controllers;

use App\Extensions\CinemaMovie\Services\CinemaMoviePublicPageProvider;
use App\Extensions\CinemaMovie\Services\TmdbImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Controller exposing public movie pages and TMDB trends for the Website Engine integration.
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

    /**
     * Get trending movies from TMDB for the TMDB-style landing page.
     *
     * @param TmdbImportService $tmdbService TMDB import service instance.
     * @return JsonResponse
     */
    public function trending(TmdbImportService $tmdbService): JsonResponse
    {
        $data = $tmdbService->getTrendingMovies();

        return response()->json($data);
    }
}
