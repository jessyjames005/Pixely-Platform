<?php

/**
 * Unit tests for the Music catalogue models (artists, albums, tracks, genres).
 *
 * These prove the schema relationships, the automatic slug/normalized-name
 * maintenance, and the public-availability rights gate before any HTTP layer
 * is built on top of them.
 */

declare(strict_types=1);

use App\Core\Extensions\Database\ExtensionMigrationRunner;
use App\Extensions\Music\Models\Album;
use App\Extensions\Music\Models\Artist;
use App\Extensions\Music\Models\Genre;
use App\Extensions\Music\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    // The catalogue references the Files extension `files` table for artwork,
    // so ensure both extensions are migrated before the catalogue is exercised.
    if (! \Illuminate\Support\Facades\Schema::hasTable('files')) {
        app(ExtensionMigrationRunner::class)->migrate('files');
    }
    if (! \Illuminate\Support\Facades\Schema::hasTable('music_tracks')) {
        app(ExtensionMigrationRunner::class)->migrate('music');
    }
});

it('normalizes artist names and keeps the slug in sync', function (): void {
    $artist = Artist::create(['name' => 'Beyoncé Knowles']);

    expect($artist->normalized_name)->toBe('beyonce knowles')
        ->and($artist->slug)->toBe('beyonce-knowles');
});

it('builds the artist to album to track hierarchy', function (): void {
    $artist = Artist::create(['name' => 'Daft Punk']);
    $album = Album::create([
        'title' => 'Discovery',
        'artist_id' => $artist->id,
        'release_year' => 2001,
        'type' => 'album',
    ]);
    $track = Track::create([
        'title' => 'One More Time',
        'artist_id' => $artist->id,
        'album_id' => $album->id,
        'track_number' => 1,
        'duration_ms' => 320000,
        'source_type' => 'youtube',
        'youtube_video_id' => 'FGBh8G4erF4',
        'rights_status' => 'unknown',
        'visibility' => 'private',
        'status' => 'ready',
    ]);

    expect($artist->albums)->toHaveCount(1)
        ->and($album->tracks)->toHaveCount(1)
        ->and($track->artist->is($artist))->toBeTrue()
        ->and($track->album->is($album))->toBeTrue();
});

it('attaches genres to tracks through the pivot', function (): void {
    $artist = Artist::create(['name' => 'Artist']);
    $track = Track::create([
        'title' => 'Track',
        'artist_id' => $artist->id,
        'source_type' => 'upload',
        'rights_status' => 'owned',
        'visibility' => 'private',
        'status' => 'ready',
    ]);
    $genre = Genre::create(['name' => 'Electronic']);

    $track->genres()->attach($genre->id);

    expect($track->genres)->toHaveCount(1)
        ->and($genre->tracks)->toHaveCount(1);
});

it('never exposes a track publicly when its rights are unknown', function (): void {
    $artist = Artist::create(['name' => 'Mystery Artist']);

    $unknownRights = Track::create([
        'title' => 'Unknown',
        'artist_id' => $artist->id,
        'source_type' => 'upload',
        'rights_status' => 'unknown',
        'visibility' => 'public',
        'status' => 'ready',
    ]);
    $licensedPublic = Track::create([
        'title' => 'Licensed',
        'artist_id' => $artist->id,
        'source_type' => 'upload',
        'rights_status' => 'licensed',
        'visibility' => 'public',
        'status' => 'ready',
    ]);
    $privateOwned = Track::create([
        'title' => 'Private',
        'artist_id' => $artist->id,
        'source_type' => 'upload',
        'rights_status' => 'owned',
        'visibility' => 'private',
        'status' => 'ready',
    ]);

    expect($unknownRights->isPubliclyAvailable())->toBeFalse()
        ->and($licensedPublic->isPubliclyAvailable())->toBeTrue()
        ->and($privateOwned->isPubliclyAvailable())->toBeFalse();
});
