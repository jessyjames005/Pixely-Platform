<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\tests\Unit;

use App\Core\Extensions\Database\ExtensionMigrationRunner;
use App\Extensions\CinemaMovie\Models\Movie;
use App\Extensions\CinemaMovie\Models\MovieTranslation;
use App\Extensions\CinemaMovie\Services\CinemaMoviePublicPageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit test suite for CinemaMoviePublicPageProvider.
 */
final class CinemaMoviePublicPageProviderTest extends TestCase
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
     * Test that null is returned when the movie does not exist.
     */
    public function test_it_returns_null_when_movie_not_found(): void
    {
        $provider = new CinemaMoviePublicPageProvider();
        $page = $provider->getMoviePage('non-existent-slug');

        $this->assertNull($page);
    }

    /**
     * Test that a valid PageModel is returned for an existing movie with translation.
     */
    public function test_it_returns_page_model_for_existing_movie(): void
    {
        $movie = Movie::create([
            'slug' => 'test-movie',
            'original_title' => 'Test Movie Original',
            'publication_status' => 'published',
        ]);

        MovieTranslation::create([
            'movie_id' => $movie->id,
            'locale' => 'en',
            'title' => 'Test Movie',
            'overview' => 'An exciting test movie overview.',
        ]);

        $provider = new CinemaMoviePublicPageProvider();
        $page = $provider->getMoviePage('test-movie', 'en');

        $this->assertNotNull($page);
        $this->assertSame('movies/test-movie', $page->slug);
        $this->assertSame('Test Movie', $page->title);
        $this->assertSame('published', $page->status);
        $this->assertNotEmpty($page->blocks);
    }
}
