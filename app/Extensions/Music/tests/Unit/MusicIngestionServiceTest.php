<?php

/**
 * Unit tests for the Music catalogue ingestion service (lot MU2).
 *
 * These prove the de-duplication rules (artists by normalized name, albums by
 * slug, genres by slug), the per-source ingestion paths, and — most importantly
 * — that the rights gate can never be bypassed: an `unknown` rights track is
 * always forced back to `private`, even when the caller asks for `public`.
 */

declare(strict_types=1);

use App\Core\Extensions\Database\ExtensionMigrationRunner;
use App\Extensions\Music\Models\Artist;
use App\Extensions\Music\Models\Track;
use App\Extensions\Music\Services\CatalogIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    // The catalogue references the Files extension `files` table for artwork,
    // so ensure both extensions are migrated before ingestion is exercised.
    if (! Schema::hasTable('files')) {
        app(ExtensionMigrationRunner::class)->migrate('files');
    }
    if (! Schema::hasTable('music_tracks')) {
        app(ExtensionMigrationRunner::class)->migrate('music');
    }
});

it('de-duplicates artists by normalized name', function (): void {
    $service = app(CatalogIngestionService::class);

    $first = $service->ingestArtist('Beyoncé Knowles');
    $second = $service->ingestArtist('beyonce knowles');

    expect($second->id)->toBe($first->id)
        ->and(Artist::query()->count())->toBe(1);
});

it('de-duplicates albums per artist by slug', function (): void {
    $service = app(CatalogIngestionService::class);
    $artist = $service->ingestArtist('Daft Punk');

    $first = $service->ingestAlbum($artist, 'Discovery', 2001);
    $second = $service->ingestAlbum($artist, 'discovery', 2001);

    expect($second->id)->toBe($first->id)
        ->and($artist->albums)->toHaveCount(1);
});

it('ingests an upload track as processing', function (): void {
    $service = app(CatalogIngestionService::class);
    $artist = $service->ingestArtist('Local Band');

    $track = $service->ingestUpload($artist, 'Song', '/tmp/song.mp3', 'audio/mpeg', 1024);

    expect($track->source_type)->toBe('upload')
        ->and($track->status)->toBe('processing')
        ->and($track->file_path)->toBe('/tmp/song.mp3')
        ->and($track->size)->toBe(1024);
});

it('ingests a youtube track as ready without a local file', function (): void {
    $service = app(CatalogIngestionService::class);
    $artist = $service->ingestArtist('Daft Punk');

    $track = $service->ingestYoutube($artist, 'One More Time', 'FGBh8G4erF4');

    expect($track->source_type)->toBe('youtube')
        ->and($track->status)->toBe('ready')
        ->and($track->youtube_video_id)->toBe('FGBh8G4erF4')
        ->and($track->file_path)->toBeNull();
});

it('forces an unknown-rights track back to private even when public is requested', function (): void {
    $service = app(CatalogIngestionService::class);
    $artist = $service->ingestArtist('Mystery');

    $track = $service->ingestYoutube($artist, 'Unknown', 'abc123', [
        'rights_status' => 'unknown',
        'visibility' => 'public',
    ]);

    expect($track->visibility)->toBe('private')
        ->and($track->isPubliclyAvailable())->toBeFalse();
});

it('keeps a licensed track public', function (): void {
    $service = app(CatalogIngestionService::class);
    $artist = $service->ingestArtist('Known');

    $track = $service->ingestYoutube($artist, 'Licensed', 'xyz789', [
        'rights_status' => 'licensed',
        'visibility' => 'public',
    ]);

    expect($track->visibility)->toBe('public')
        ->and($track->isPubliclyAvailable())->toBeTrue();
});

it('syncs genres, creating missing ones and detaching removed ones', function (): void {
    $service = app(CatalogIngestionService::class);
    $artist = $service->ingestArtist('Genre Artist');
    $track = $service->ingestYoutube($artist, 'Track', 'vid1');

    $service->syncGenres($track, ['Electronic', 'House']);
    expect($track->genres)->toHaveCount(2);

    // Reload the relation so the cached collection reflects the second sync.
    $service->syncGenres($track, ['Electronic']);
    $track->load('genres');
    expect($track->genres)->toHaveCount(1)
        ->and($track->genres->first()->name)->toBe('Electronic');
});

it('reuses an existing genre instead of creating a duplicate', function (): void {
    $service = app(CatalogIngestionService::class);

    $first = $service->ingestGenre('Jazz');
    $second = $service->ingestGenre('jazz');

    expect($second->id)->toBe($first->id);
});

it('links a track to an album and orders it', function (): void {
    $service = app(CatalogIngestionService::class);
    $artist = $service->ingestArtist('Album Artist');
    $album = $service->ingestAlbum($artist, 'Greatest Hits');

    $track = $service->ingestYoutube($artist, 'Hit', 'hit1', [
        'album_id' => $album->id,
        'track_number' => 1,
    ]);

    expect($track->album_id)->toBe($album->id)
        ->and($track->track_number)->toBe(1)
        ->and($album->tracks)->toHaveCount(1);
});

it('defaults a track to unknown rights and private visibility', function (): void {
    $service = app(CatalogIngestionService::class);
    $artist = $service->ingestArtist('Default Rights');

    $track = $service->ingestUpload($artist, 'No Rights Given', '/tmp/x.mp3', 'audio/mpeg', 10);

    expect($track->rights_status)->toBe('unknown')
        ->and($track->visibility)->toBe('private')
        ->and($track->isPubliclyAvailable())->toBeFalse();
});
