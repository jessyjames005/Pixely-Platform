<?php

/**
 * Functional tests for the Music administration API (lot MU3).
 *
 * These exercise the JSON:API catalogue resources (tracks, albums, artists,
 * genres): authentication, per-resource permission gating, CRUD, relationship
 * includes, and — critically — that the rights gate holds through the HTTP
 * layer (a public + unknown track is always persisted as private).
 */

declare(strict_types=1);

use App\Core\Extensions\Database\ExtensionMigrationRunner;
use App\Extensions\Music\Models\Album;
use App\Extensions\Music\Models\Artist;
use App\Extensions\Music\Models\Genre;
use App\Extensions\Music\Models\Track;
use App\Extensions\Music\MusicExtension;
use App\Extensions\Music\Providers\MusicServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Extensions\ExtensionApiTestHelper;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(MusicServiceProvider::class);

    // The catalogue references the Files extension `files` table for artwork,
    // so ensure both extensions are migrated before the API is exercised.
    if (! Schema::hasTable('files')) {
        app(ExtensionMigrationRunner::class)->migrate('files');
    }
    if (! Schema::hasTable('music_tracks')) {
        app(ExtensionMigrationRunner::class)->migrate('music');
    }

    // Sync the declared permissions so the middleware can resolve them.
    $extension = new MusicExtension();
    app(\App\Core\Extensions\Permissions\ExtensionPermissionSynchronizer::class)->sync($extension);
});

it('requires authentication to list Music tracks', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $testCase->getJson('/api/v1/music-tracks', ExtensionApiTestHelper::jsonApiHeaders())
        ->assertUnauthorized();
});

it('forbids authenticated users without the tracks view permission', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $testCase->actingAs(User::factory()->create())
        ->getJson('/api/v1/music-tracks', ExtensionApiTestHelper::jsonApiHeaders())
        ->assertForbidden();
});

it('returns a paginated JSON:API track collection with relationships', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $testCase->actingAs(ExtensionApiTestHelper::createUserWithPermission(
        new MusicExtension(),
        'music.tracks.view',
    ));

    $artist = Artist::create(['name' => 'Daft Punk']);
    $album = Album::create(['title' => 'Discovery', 'artist_id' => $artist->id]);
    $track = Track::create([
        'title' => 'One More Time',
        'artist_id' => $artist->id,
        'album_id' => $album->id,
        'source_type' => 'youtube',
        'youtube_video_id' => 'FGBh8G4erF4',
        'rights_status' => 'licensed',
        'visibility' => 'public',
        'status' => 'ready',
    ]);

    $response = $testCase->getJson(
        '/api/v1/music-tracks?include=artist,album',
        ExtensionApiTestHelper::jsonApiHeaders(),
    );

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.0.type', 'music-tracks')
        ->assertJsonPath('data.0.attributes.title', 'One More Time')
        ->assertJsonPath('data.0.relationships.artist.data.id', (string) $artist->id)
        ->assertJsonPath('data.0.relationships.album.data.id', (string) $album->id)
        ->assertJsonPath('jsonapi.version', '1.0');

    expect($track->isPubliclyAvailable())->toBeTrue();
});

it('creates an artist through the admin API', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $testCase->actingAs(ExtensionApiTestHelper::createUserWithPermission(
        new MusicExtension(),
        'music.artists.manage',
    ));

    $response = $testCase->postJson('/api/v1/music-artists', [
        'data' => [
            'type' => 'music-artists',
            'attributes' => ['name' => 'Beyoncé Knowles'],
        ],
    ], ExtensionApiTestHelper::jsonApiHeaders());

    $response->assertCreated();
    $id = $response->json('data.id');

    expect(Artist::query()->findOrFail($id)->normalized_name)->toBe('beyonce knowles');
});

it('creates a genre through the admin API', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $testCase->actingAs(ExtensionApiTestHelper::createUserWithPermission(
        new MusicExtension(),
        'music.genres.manage',
    ));

    $testCase->postJson('/api/v1/music-genres', [
        'data' => [
            'type' => 'music-genres',
            'attributes' => ['name' => 'Electronic'],
        ],
    ], ExtensionApiTestHelper::jsonApiHeaders())
        ->assertCreated()
        ->assertJsonPath('data.attributes.slug', 'electronic');
});

it('forces a public unknown-rights track to private through the API', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $testCase->actingAs(ExtensionApiTestHelper::createUserWithPermission(
        new MusicExtension(),
        'music.tracks.manage',
    ));
    $artist = Artist::create(['name' => 'Mystery Artist']);

    $response = $testCase->postJson('/api/v1/music-tracks', [
        'data' => [
            'type' => 'music-tracks',
            'attributes' => [
                'title' => 'Unknown',
                'sourceType' => 'youtube',
                'youtubeVideoId' => 'abc123',
                'rightsStatus' => 'unknown',
                'visibility' => 'public',
            ],
            'relationships' => [
                'artist' => ['data' => ['type' => 'music-artists', 'id' => (string) $artist->id]],
            ],
        ],
    ], ExtensionApiTestHelper::jsonApiHeaders());

    $response->assertCreated();
    $id = $response->json('data.id');

    $persisted = Track::query()->findOrFail($id);
    expect($persisted->visibility)->toBe('private')
        ->and($persisted->isPubliclyAvailable())->toBeFalse();
});

it('requires the delete permission to remove a track', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $artist = Artist::create(['name' => 'Artist']);
    $track = Track::create([
        'title' => 'Track',
        'artist_id' => $artist->id,
        'source_type' => 'youtube',
        'youtube_video_id' => 'vid1',
        'rights_status' => 'owned',
        'visibility' => 'private',
        'status' => 'ready',
    ]);

    $viewOnly = ExtensionApiTestHelper::createUserWithPermission(
        new MusicExtension(),
        'music.tracks.view',
    );
    $testCase->actingAs($viewOnly)
        ->deleteJson('/api/v1/music-tracks/' . $track->id, [], ExtensionApiTestHelper::jsonApiHeaders())
        ->assertForbidden();

    $deleter = ExtensionApiTestHelper::createUserWithPermission(
        new MusicExtension(),
        'music.tracks.delete',
    );
    $testCase->actingAs($deleter)
        ->deleteJson('/api/v1/music-tracks/' . $track->id, [], ExtensionApiTestHelper::jsonApiHeaders())
        ->assertNoContent();

    expect(Track::query()->find($track->id))->toBeNull();
});

it('lists genres for a track through the include parameter', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $testCase->actingAs(ExtensionApiTestHelper::createUserWithPermission(
        new MusicExtension(),
        'music.tracks.view',
    ));

    $artist = Artist::create(['name' => 'Genre Artist']);
    $track = Track::create([
        'title' => 'Track',
        'artist_id' => $artist->id,
        'source_type' => 'youtube',
        'youtube_video_id' => 'vid1',
        'rights_status' => 'owned',
        'visibility' => 'private',
        'status' => 'ready',
    ]);
    $genre = Genre::create(['name' => 'House']);
    $track->genres()->attach($genre->id);

    $testCase->getJson(
        '/api/v1/music-tracks/' . $track->id . '?include=genres',
        ExtensionApiTestHelper::jsonApiHeaders(),
    )
        ->assertOk()
        ->assertJsonPath('data.relationships.genres.data.0.type', 'music-genres')
        ->assertJsonPath('data.relationships.genres.data.0.id', (string) $genre->id);
});
