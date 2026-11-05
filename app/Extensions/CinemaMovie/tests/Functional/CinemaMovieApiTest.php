<?php

declare(strict_types=1);

use App\Core\Extensions\Database\ExtensionMigrationRunner;
use App\Extensions\CinemaMovie\CinemaMovieExtension;
use App\Extensions\CinemaMovie\Models\Genre;
use App\Extensions\CinemaMovie\Models\Movie;
use App\Extensions\CinemaMovie\Providers\CinemaMovieServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Extensions\ExtensionApiTestHelper;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ExtensionMigrationRunner::class)->migrate('cinema-movie');
});

it('publishes its English and French UI translations to the platform', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $extension = new CinemaMovieExtension();
    app()->register(CinemaMovieServiceProvider::class);

    expect($extension->translationsPath())->toBe(base_path('app/Extensions/CinemaMovie/lang'))
        ->and(trans('cinema-movie::cinema-movie.title.admin', [], 'en'))->toBe('Cinema movies')
        ->and(trans('cinema-movie::cinema-movie.title.admin', [], 'fr'))->toBe('Films');
});

it('requires authentication to list CinemaMovie movies', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $testCase->getJson('/api/v1/cinema-movies', ExtensionApiTestHelper::jsonApiHeaders())
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/vnd.api+json');
});

it('forbids authenticated users without the CinemaMovie view permission', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $testCase->actingAs(User::factory()->create())
        ->getJson('/api/v1/cinema-movies', ExtensionApiTestHelper::jsonApiHeaders())
        ->assertForbidden();
});

it('returns a paginated JSON:API movie collection', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $testCase->actingAs(ExtensionApiTestHelper::createUserWithPermission(
        new CinemaMovieExtension(),
        'cinema-movie.movies.view',
    ));

    $movie = Movie::query()->create([
        'original_title' => 'Arrival',
        'production_status' => 'released',
        'publication_status' => 'published',
    ]);
    $movie->translations()->create(['locale' => 'en', 'title' => 'Arrival']);
    $genre = Genre::query()->create(['slug' => 'science-fiction']);
    $genre->translations()->create(['locale' => 'en', 'name' => 'Science fiction']);
    $movie->genres()->attach($genre);

    $query = '?include=translations,genres,genres.translations';

    $response = $testCase->getJson('/api/v1/cinema-movies' . $query, ExtensionApiTestHelper::jsonApiHeaders());
    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.0.type', 'cinema-movies')
        ->assertJsonPath('data.0.attributes.slug', 'arrival')
        ->assertJsonPath('data.0.relationships.genres.data.0.type', 'cinema-genres')
        ->assertJsonFragment(['type' => 'cinema-genre-translations'])
        ->assertJsonPath('jsonapi.version', '1.0');
});

it('rejects unsupported production states when creating movies', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $testCase->actingAs(ExtensionApiTestHelper::createUserWithPermission(
        new CinemaMovieExtension(),
        'cinema-movie.movies.manage',
    ));

    $testCase->postJson('/api/v1/cinema-movies', [
        'data' => [
            'type' => 'cinema-movies',
            'attributes' => [
                'originalTitle' => 'Arrival',
                'productionStatus' => 'unknown',
            ],
        ],
    ], ExtensionApiTestHelper::jsonApiHeaders())
        ->assertUnprocessable();
});

it('requires a default-locale title before publishing a movie', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $testCase->actingAs(ExtensionApiTestHelper::createUserWithPermission(
        new CinemaMovieExtension(),
        'cinema-movie.movies.manage',
    ));

    $testCase->postJson('/api/v1/cinema-movies', [
        'data' => [
            'type' => 'cinema-movies',
            'attributes' => [
                'originalTitle' => 'Arrival',
                'publicationStatus' => 'published',
            ],
        ],
    ], ExtensionApiTestHelper::jsonApiHeaders())
        ->assertUnprocessable();
});

it('creates a movie translation before allowing the movie to be published', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $testCase->actingAs(ExtensionApiTestHelper::createUserWithPermission(
        new CinemaMovieExtension(),
        'cinema-movie.movies.manage',
    ));

    $movieResponse = $testCase->postJson('/api/v1/cinema-movies', [
        'data' => [
            'type' => 'cinema-movies',
            'attributes' => ['originalTitle' => 'Arrival'],
        ],
    ], ExtensionApiTestHelper::jsonApiHeaders())->assertCreated();
    $movieId = $movieResponse->json('data.id');

    $testCase->postJson('/api/v1/cinema-movie-translations', [
        'data' => [
            'type' => 'cinema-movie-translations',
            'attributes' => ['locale' => 'en', 'title' => 'Arrival'],
            'relationships' => [
                'movie' => ['data' => ['type' => 'cinema-movies', 'id' => $movieId]],
            ],
        ],
    ], ExtensionApiTestHelper::jsonApiHeaders())->assertCreated();

    $testCase->patchJson('/api/v1/cinema-movies/' . $movieId, [
        'data' => [
            'type' => 'cinema-movies',
            'id' => $movieId,
            'attributes' => ['publicationStatus' => 'published'],
        ],
    ], ExtensionApiTestHelper::jsonApiHeaders())
        ->assertOk()
        ->assertJsonPath('data.attributes.publicationStatus', 'published');
});

it('allows a movie manager to assign existing genres through the resource update', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $testCase->actingAs(ExtensionApiTestHelper::createUserWithPermission(
        new CinemaMovieExtension(),
        'cinema-movie.movies.manage',
    ));
    $movie = Movie::query()->create(['original_title' => 'Arrival']);
    $genre = Genre::query()->create(['slug' => 'science-fiction']);

    $testCase->patchJson('/api/v1/cinema-movies/' . $movie->getKey(), [
        'data' => [
            'type' => 'cinema-movies',
            'id' => (string) $movie->getKey(),
            'relationships' => [
                'genres' => [
                    'data' => [['type' => 'cinema-genres', 'id' => (string) $genre->getKey()]],
                ],
            ],
        ],
    ], ExtensionApiTestHelper::jsonApiHeaders())
        ->assertOk();

    expect($movie->fresh()->genres->modelKeys())->toBe([$genre->getKey()]);
});

it('creates a genre with a locale-specific name', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $testCase->actingAs(ExtensionApiTestHelper::createUserWithPermission(
        new CinemaMovieExtension(),
        'cinema-movie.movies.manage',
    ));

    $genreResponse = $testCase->postJson('/api/v1/cinema-genres', [
        'data' => [
            'type' => 'cinema-genres',
            'attributes' => ['slug' => 'science-fiction'],
        ],
    ], ExtensionApiTestHelper::jsonApiHeaders())->assertCreated();
    $genreId = $genreResponse->json('data.id');

    $testCase->postJson('/api/v1/cinema-genre-translations', [
        'data' => [
            'type' => 'cinema-genre-translations',
            'attributes' => ['locale' => 'en', 'name' => 'Science fiction'],
            'relationships' => [
                'genre' => ['data' => ['type' => 'cinema-genres', 'id' => $genreId]],
            ],
        ],
    ], ExtensionApiTestHelper::jsonApiHeaders())->assertCreated();

    expect(Genre::query()->findOrFail($genreId)->translations()->value('name'))
        ->toBe('Science fiction');
});
