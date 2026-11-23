<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\tests\Functional;

use App\Core\Extensions\Database\ExtensionMigrationRunner;
use App\Extensions\CinemaMovie\Models\Movie;
use App\Extensions\CinemaMovie\Models\MovieTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Functional test suite for public movie controller.
 */
final class PublicMovieControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (! \Illuminate\Support\Facades\Schema::hasTable('cinema_movies')) {
            app(ExtensionMigrationRunner::class)->migrate('cinema-movie');
        }
    }

    /**
     * Test that 404 is returned when public movie page is missing.
     */
    public function test_it_returns_404_when_public_movie_not_found(): void
    {
        $response = $this->getJson('/api/v1/public/movies/missing');

        $response->assertStatus(404);
    }

    /**
     * Test that public movie page is successfully retrieved.
     */
    public function test_it_returns_public_movie_page_successfully(): void
    {
        $movie = Movie::create([
            'slug' => 'avatar',
            'original_title' => 'Avatar',
            'publication_status' => 'published',
        ]);

        MovieTranslation::create([
            'movie_id' => $movie->id,
            'locale' => 'en',
            'title' => 'Avatar',
            'overview' => 'Sci-fi epic.',
        ]);

        $response = $this->getJson('/api/v1/public/movies/avatar');

        $response->assertStatus(200)
            ->assertJsonPath('data.slug', 'movies/avatar')
            ->assertJsonPath('data.title', 'Avatar');
    }

    /**
     * Test that trending movies endpoint returns TMDB trending movies.
     */
    public function test_it_returns_trending_movies(): void
    {
        config(['services.tmdb.key' => 'mock-api-key']);

        Http::fake([
            'api.themoviedb.org/3/trending/movie/day*' => Http::response([
                'results' => [
                    ['id' => 101, 'title' => 'Avengers', 'poster_path' => '/poster.jpg'],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/public/trending-movies');

        $response->assertStatus(200)
            ->assertJsonPath('results.0.title', 'Avengers');
    }

    /**
     * Test that a non-numeric movie identifier is rejected at the route level (404).
     */
    public function test_it_returns_404_for_non_numeric_movie_id(): void
    {
        $response = $this->getJson('/api/v1/public/tmdb/movie/not-a-number');

        $response->assertStatus(404);
    }

    /**
     * Test that a page number above the TMDB maximum is rejected server-side (422).
     */
    public function test_it_rejects_page_above_maximum(): void
    {
        config(['services.tmdb.key' => 'mock-api-key']);

        Http::fake();

        $response = $this->getJson('/api/v1/public/tmdb/popular-movies?page=501');

        $response->assertStatus(422)
            ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/page');

        Http::assertNothingSent();
    }

    /**
     * Test that an empty search query is rejected server-side (422).
     */
    public function test_it_requires_a_search_query(): void
    {
        config(['services.tmdb.key' => 'mock-api-key']);

        Http::fake();

        $response = $this->getJson('/api/v1/public/tmdb/search?query=');

        $response->assertStatus(422)
            ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/query');

        Http::assertNothingSent();
    }

    /**
     * Test that the public TMDB surface is read-only (POST is not allowed).
     */
    public function test_public_tmdb_surface_is_read_only(): void
    {
        $response = $this->postJson('/api/v1/public/tmdb/popular-movies');

        $response->assertStatus(405);
    }
}
