<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\tests\Unit;

use App\Extensions\CinemaMovie\Services\TmdbImportService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Unit test suite for TmdbImportService covering TMDB v3 API integration.
 */
final class TmdbImportServiceTest extends TestCase
{
    /**
     * Test that an error is returned when the TMDB API key is missing.
     */
    public function test_it_returns_error_when_api_key_is_missing(): void
    {
        config(['services.tmdb.key' => null]);

        $service = new TmdbImportService();
        $result = $service->searchMovies('Test');

        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test that movies are successfully searched and returned from TMDB.
     */
    public function test_it_searches_movies_successfully(): void
    {
        config(['services.tmdb.key' => 'mock-api-key']);

        Http::fake([
            'api.themoviedb.org/3/search/movie*' => Http::response(['results' => [['id' => 1, 'title' => 'Test Movie']]], 200),
        ]);

        $service = new TmdbImportService();
        $result = $service->searchMovies('Test');

        $this->assertArrayHasKey('results', $result);
        $this->assertSame('Test Movie', $result['results'][0]['title']);
    }

    /**
     * Test that movie details are successfully fetched from TMDB.
     */
    public function test_it_fetches_movie_details_successfully(): void
    {
        config(['services.tmdb.key' => 'mock-api-key']);

        Http::fake([
            'api.themoviedb.org/3/movie/550*' => Http::response(['id' => 550, 'title' => 'Fight Club'], 200),
        ]);

        $service = new TmdbImportService();
        $result = $service->getMovieDetails(550);

        $this->assertSame(550, $result['id']);
        $this->assertSame('Fight Club', $result['title']);
    }

    /**
     * Test that account details are successfully fetched from TMDB.
     */
    public function test_it_fetches_account_details_successfully(): void
    {
        config(['services.tmdb.key' => 'mock-api-key']);

        Http::fake([
            'api.themoviedb.org/3/account*' => Http::response(['id' => 123, 'username' => 'testuser'], 200),
        ]);

        $service = new TmdbImportService();
        $result = $service->getAccount('123');

        $this->assertSame(123, $result['id']);
        $this->assertSame('testuser', $result['username']);
    }
}
