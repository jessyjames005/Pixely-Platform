<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for interacting with the TMDB API to search and retrieve movie details and trends.
 */
final class TmdbImportService
{
    /**
     * Base URL for the TMDB v3 API.
     */
    private string $baseUrl = 'https://api.themoviedb.org/3';

    /**
     * Create a new TmdbImportService instance.
     */
    public function __construct()
    {
    }

    /**
     * Search movies on TMDB using the configured API key.
     *
     * @param string $query Search query string.
     * @param int $page Pagination page number.
     * @return array<string, mixed> Response data or error array.
     */
    public function searchMovies(string $query, int $page = 1): array
    {
        /** @var string|null $apiKey */
        $apiKey = config('services.tmdb.key');

        if (empty($apiKey)) {
            return ['error' => 'TMDB API key not configured.'];
        }

        $response = Http::get("{$this->baseUrl}/search/movie", [
            'api_key' => $apiKey,
            'query' => $query,
            'page' => $page,
            'language' => 'fr-FR',
        ]);

        if ($response->failed()) {
            Log::error('TMDB API search failed', ['status' => $response->status(), 'body' => $response->body()]);
            return ['error' => 'Failed to connect to TMDB API.'];
        }

        /** @var array<string, mixed>|null $data */
        $data = $response->json();

        return $data ?? [];
    }

    /**
     * Fetch detailed movie information from TMDB by movie ID.
     *
     * @param int $tmdbId TMDB movie identifier.
     * @return array<string, mixed> Movie details or error array.
     */
    public function getMovieDetails(int $tmdbId): array
    {
        /** @var string|null $apiKey */
        $apiKey = config('services.tmdb.key');

        if (empty($apiKey)) {
            return ['error' => 'TMDB API key not configured.'];
        }

        $response = Http::get("{$this->baseUrl}/movie/{$tmdbId}", [
            'api_key' => $apiKey,
            'language' => 'fr-FR',
        ]);

        if ($response->failed()) {
            Log::error('TMDB API movie details failed', ['id' => $tmdbId, 'status' => $response->status()]);
            return ['error' => 'Failed to fetch movie details from TMDB.'];
        }

        /** @var array<string, mixed>|null $data */
        $data = $response->json();

        return $data ?? [];
    }

    /**
     * Fetch trending movies of the day from TMDB.
     *
     * @return array<string, mixed> Trending movies list or error array.
     */
    public function getTrendingMovies(): array
    {
        /** @var string|null $apiKey */
        $apiKey = config('services.tmdb.key');

        if (empty($apiKey)) {
            return ['error' => 'TMDB API key not configured.', 'results' => []];
        }

        $response = Http::get("{$this->baseUrl}/trending/movie/day", [
            'api_key' => $apiKey,
            'language' => 'fr-FR',
        ]);

        if ($response->failed()) {
            Log::error('TMDB API trending movies failed', ['status' => $response->status()]);
            return ['error' => 'Failed to fetch trending movies from TMDB.', 'results' => []];
        }

        /** @var array<string, mixed>|null $data */
        $data = $response->json();

        return $data ?? ['results' => []];
    }
}
