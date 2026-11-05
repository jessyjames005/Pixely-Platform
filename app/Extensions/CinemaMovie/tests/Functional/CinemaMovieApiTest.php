<?php

declare(strict_types=1);

use App\Extensions\CinemaMovie\CinemaMovieExtension;
use App\Extensions\CinemaMovie\Models\Movie;
use App\Core\Extensions\Database\ExtensionMigrationRunner;
use App\Extensions\CinemaMovie\Providers\CinemaMovieServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Support\Extensions\ExtensionApiTestHelper;

uses(TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ExtensionMigrationRunner::class)->migrate('cinema-movie');
});

it('publishes its English and French UI translations to the platform', function (): void {
    $extension = new CinemaMovieExtension();
    $this->app->register(CinemaMovieServiceProvider::class);

    expect($extension->translationsPath())->toBe(base_path('app/Extensions/CinemaMovie/lang'))
        ->and(trans('cinema-movie::cinema-movie.title.admin', [], 'en'))->toBe('Cinema movies')
        ->and(trans('cinema-movie::cinema-movie.title.admin', [], 'fr'))->toBe('Films');
});

it('requires authentication to list CinemaMovie movies', function (): void {
    $this->getJson('/api/v1/cinema-movies', ExtensionApiTestHelper::jsonApiHeaders())
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/vnd.api+json');
});

it('forbids authenticated users without the CinemaMovie view permission', function (): void {
    $this->actingAs(User::factory()->create())
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

    Movie::query()->create([
        'original_title' => 'Arrival',
        'production_status' => 'released',
        'publication_status' => 'published',
    ]);

    $this->getJson('/api/v1/cinema-movies', ExtensionApiTestHelper::jsonApiHeaders())
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.0.type', 'cinema-movies')
        ->assertJsonPath('data.0.attributes.slug', 'arrival')
        ->assertJsonPath('jsonapi.version', '1.0');
});

it('rejects unsupported production states when creating movies', function (): void {
    $this->actingAs(ExtensionApiTestHelper::createUserWithPermission(
        new CinemaMovieExtension(),
        'cinema-movie.movies.manage',
    ));

    $this->postJson('/api/v1/cinema-movies', [
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
    $this->actingAs(ExtensionApiTestHelper::createUserWithPermission(
        new CinemaMovieExtension(),
        'cinema-movie.movies.manage',
    ));

    $this->postJson('/api/v1/cinema-movies', [
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
