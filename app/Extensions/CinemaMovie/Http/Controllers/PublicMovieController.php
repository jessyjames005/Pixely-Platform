<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Http\Controllers;

use App\Extensions\CinemaMovie\Services\CinemaMoviePublicPageProvider;
use App\Extensions\CinemaMovie\Services\TmdbImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Controller exposing public movie pages and TMDB v3 API proxy endpoints
 * for the Website Engine and frontend components.
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

    /**
     * Get popular movies from TMDB.
     *
     * @param Request $request HTTP request.
     * @param TmdbImportService $tmdbService TMDB import service instance.
     * @return JsonResponse
     */
    public function popularMovies(Request $request, TmdbImportService $tmdbService): JsonResponse
    {
        $page = $this->resolvePage($request);
        $data = $tmdbService->getPopularMovies($page);

        return response()->json($data);
    }

    /**
     * Get top-rated movies from TMDB.
     *
     * @param Request $request HTTP request.
     * @param TmdbImportService $tmdbService TMDB import service instance.
     * @return JsonResponse
     */
    public function topRatedMovies(Request $request, TmdbImportService $tmdbService): JsonResponse
    {
        $page = $this->resolvePage($request);
        $data = $tmdbService->getTopRatedMovies($page);

        return response()->json($data);
    }

    /**
     * Get upcoming movies from TMDB.
     *
     * @param Request $request HTTP request.
     * @param TmdbImportService $tmdbService TMDB import service instance.
     * @return JsonResponse
     */
    public function upcomingMovies(Request $request, TmdbImportService $tmdbService): JsonResponse
    {
        $page = $this->resolvePage($request);
        $data = $tmdbService->getUpcomingMovies($page);

        return response()->json($data);
    }

    /**
     * Get now-playing movies from TMDB.
     *
     * @param Request $request HTTP request.
     * @param TmdbImportService $tmdbService TMDB import service instance.
     * @return JsonResponse
     */
    public function nowPlayingMovies(Request $request, TmdbImportService $tmdbService): JsonResponse
    {
        $page = $this->resolvePage($request);
        $data = $tmdbService->getNowPlayingMovies($page);

        return response()->json($data);
    }

    /**
     * Get popular people/artists from TMDB.
     *
     * @param Request $request HTTP request.
     * @param TmdbImportService $tmdbService TMDB import service instance.
     * @return JsonResponse
     */
    public function popularPeople(Request $request, TmdbImportService $tmdbService): JsonResponse
    {
        $page = $this->resolvePage($request);
        $data = $tmdbService->getPopularPeople($page);

        return response()->json($data);
    }

    /**
     * Get person details by ID from TMDB.
     *
     * @param int $id Person TMDB identifier.
     * @param TmdbImportService $tmdbService TMDB import service instance.
     * @return JsonResponse
     */
    public function personDetails(int $id, TmdbImportService $tmdbService): JsonResponse
    {
        $data = $tmdbService->getPersonDetails($id);

        return response()->json($data);
    }

    /**
     * Get person movie credits by ID from TMDB.
     *
     * @param int $id Person TMDB identifier.
     * @param TmdbImportService $tmdbService TMDB import service instance.
     * @return JsonResponse
     */
    public function personMovieCredits(int $id, TmdbImportService $tmdbService): JsonResponse
    {
        $data = $tmdbService->getPersonMovieCredits($id);

        return response()->json($data);
    }

    /**
     * Get movie details by ID from TMDB.
     *
     * @param int $id Movie TMDB identifier.
     * @param TmdbImportService $tmdbService TMDB import service instance.
     * @return JsonResponse
     */
    public function movieDetails(int $id, TmdbImportService $tmdbService): JsonResponse
    {
        $data = $tmdbService->getMovieDetails($id);

        return response()->json($data);
    }

    /**
     * Get movie credits by ID from TMDB.
     *
     * @param int $id Movie TMDB identifier.
     * @param TmdbImportService $tmdbService TMDB import service instance.
     * @return JsonResponse
     */
    public function movieCredits(int $id, TmdbImportService $tmdbService): JsonResponse
    {
        $data = $tmdbService->getMovieCredits($id);

        return response()->json($data);
    }

    /**
     * Search movies on TMDB.
     *
     * @param Request $request HTTP request.
     * @param TmdbImportService $tmdbService TMDB import service instance.
     * @return JsonResponse
     */
    public function search(Request $request, TmdbImportService $tmdbService): JsonResponse
    {
        // Validate server-side: the search query is authoritative and must not be empty.
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:1', 'max:200'],
        ]);

        $page = $this->resolvePage($request);
        $data = $tmdbService->searchMovies($validated['query'], $page);

        return response()->json($data);
    }

    /**
     * Validate and resolve the requested TMDB page number.
     *
     * TMDB paginates results from page 1 to 500; anything outside that range
     * is rejected server-side so invalid input never reaches the upstream API.
     *
     * @param Request $request HTTP request.
     * @return int Validated page number (1–500).
     */
    private function resolvePage(Request $request): int
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1', 'max:500'],
        ]);

        return (int) ($validated['page'] ?? 1);
    }
}
