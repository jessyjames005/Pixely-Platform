<?php

/**
 * Unit tests for YouTubeImportService.
 *
 * Every YouTube Data API v3 call is faked so no network access or real API key
 * is required. These tests prove that videos and playlists are imported as
 * `youtube` tracks with unknown rights and private visibility, and that the
 * service fails fast when no API key is configured.
 */

declare(strict_types=1);

use App\Extensions\Music\Models\Track;
use App\Extensions\Music\Services\CatalogIngestionService;
use App\Extensions\Music\Services\YouTubeImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (! \Illuminate\Support\Facades\Schema::hasTable('files')) {
        app(\App\Core\Extensions\Database\ExtensionMigrationRunner::class)->migrate('files');
    }
    if (! \Illuminate\Support\Facades\Schema::hasTable('music_tracks')) {
        app(\App\Core\Extensions\Database\ExtensionMigrationRunner::class)->migrate('music');
    }
});

function videoFixture(string $id, string $title, string $channel, string $duration): array
{
    return [
        'kind' => 'youtube#video',
        'etag' => 'etag',
        'id' => $id,
        'snippet' => [
            'title' => $title,
            'channelTitle' => $channel,
            'publishedAt' => '2024-01-01T00:00:00Z',
        ],
        'contentDetails' => [
            'duration' => $duration,
        ],
    ];
}

it('imports a single video as a private track with unknown rights', function (): void {
    Http::fake(function ($request) {
        if (str_contains($request->url(), '/videos')) {
            return Http::response([
                'items' => [videoFixture('abc123', 'Test Track', 'Test Artist', 'PT4M13S')],
            ], 200);
        }

        return Http::response(['items' => [], 'nextPageToken' => null], 200);
    });

    $service = app(YouTubeImportService::class);
    $track = $service->importVideo('abc123');

    expect($track)
        ->not->toBeNull()
        ->and($track->source_type)->toBe('youtube')
        ->and($track->youtube_video_id)->toBe('abc123')
        ->and($track->title)->toBe('Test Track')
        ->and($track->artist->name)->toBe('Test Artist')
        ->and($track->rights_status)->toBe('unknown')
        ->and($track->visibility)->toBe('private')
        ->and($track->status)->toBe('ready')
        ->and($track->duration_ms)->toBe(253000)
        ->and($track->file_path)->toBeNull();
});

it('returns null when the video does not exist', function (): void {
    Http::fake(function () {
        return Http::response(['items' => []], 200);
    });

    $service = app(YouTubeImportService::class);
    $track = $service->importVideo('nonexistent');

    expect($track)->toBeNull();
});

it('imports every video of a playlist', function (): void {
    Http::fake(function ($request) {
        if (str_contains($request->url(), '/playlistItems')) {
            return Http::response([
                'items' => [
                    ['snippet' => ['resourceId' => ['videoId' => 'v1']]],
                    ['snippet' => ['resourceId' => ['videoId' => 'v2']]],
                ],
                'nextPageToken' => null,
            ], 200);
        }

        if (str_contains($request->url(), '/videos')) {
            return Http::response([
                'items' => [
                    videoFixture('v1', 'Track One', 'Artist One', 'PT3M0S'),
                    videoFixture('v2', 'Track Two', 'Artist Two', 'PT2M30S'),
                ],
            ], 200);
        }

        return Http::response([], 200);
    });

    $service = app(YouTubeImportService::class);
    $tracks = $service->importPlaylist('PLx123');

    expect($tracks)->toHaveCount(2)
        ->and($tracks[0]->youtube_video_id)->toBe('v1')
        ->and($tracks[0]->title)->toBe('Track One')
        ->and($tracks[0]->artist->name)->toBe('Artist One')
        ->and($tracks[0]->rights_status)->toBe('unknown')
        ->and($tracks[0]->visibility)->toBe('private')
        ->and($tracks[0]->duration_ms)->toBe(180000)
        ->and($tracks[1]->youtube_video_id)->toBe('v2')
        ->and($tracks[1]->title)->toBe('Track Two')
        ->and($tracks[1]->duration_ms)->toBe(150000);
});

it('applies extra attributes such as album_id to every imported track', function (): void {
    Http::fake(function ($request) {
        if (str_contains($request->url(), '/playlistItems')) {
            return Http::response([
                'items' => [
                    ['snippet' => ['resourceId' => ['videoId' => 'v1']]],
                ],
                'nextPageToken' => null,
            ], 200);
        }

        return Http::response([
            'items' => [videoFixture('v1', 'Album Track', 'Album Artist', 'PT1M0S')],
        ], 200);
    });

    $catalog = app(CatalogIngestionService::class);
    $artist = $catalog->ingestArtist('Album Artist');
    $album = $catalog->ingestAlbum($artist, 'Test Album');

    $service = app(YouTubeImportService::class);
    $tracks = $service->importPlaylist('PLx123', ['album_id' => $album->id]);

    expect($tracks)->toHaveCount(1)
        ->and($tracks[0]->album_id)->toBe($album->id);
});

it('fails fast when the YouTube API key is missing', function (): void {
    Config::set('services.youtube.key', null);

    $service = app(YouTubeImportService::class);

    expect(fn () => $service->importVideo('abc123'))->toThrow(\RuntimeException::class);
});
