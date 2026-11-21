<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\tests\Functional;

use App\Core\Extensions\Database\ExtensionMigrationRunner;
use App\Extensions\CinemaMovie\Models\Movie;
use App\Extensions\CinemaMovie\Models\MovieTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
