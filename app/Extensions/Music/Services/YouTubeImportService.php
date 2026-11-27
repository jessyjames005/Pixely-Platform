<?php

/**
 * YouTube import service for the Music extension.
 *
 * Pulls videos and playlists from the YouTube Data API v3 and delegates all
 * catalogue persistence to CatalogIngestionService, so the existing business
 * rules (artist/album/genre de-duplication and the rights gate) are never
 * bypassed. Imported videos are always stored with `rights_status = 'unknown'`
 * and `visibility = 'private'`; an administrator must review and license them
 * before they can ever become public.
 *
 * The API key is read from services.youtube.key (YOUTUBE_API_KEY in .env).
 */

declare(strict_types=1);

namespace App\Extensions\Music\Services;

use App\Extensions\Music\Models\Track;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class YouTubeImportService
{
    private const VIDEOS_ENDPOINT = 'https://www.googleapis.com/youtube/v3/videos';

    private const PLAYLIST_ITEMS_ENDPOINT = 'https://www.googleapis.com/youtube/v3/playlistItems';

    /**
     * Import a single YouTube video as a music track.
     *
     * @param array<string, mixed> $extra Extra track attributes (album_id, genre
     *        names, rights_status, visibility, ...). Rights default to `unknown`
     *        and visibility to `private`.
     */
    public function importVideo(string $videoId, array $extra = []): ?Track
    {
        $videos = $this->fetchVideos([$videoId]);

        if ($videos === []) {
            return null;
        }

        return $this->ingestFromVideo($videos[$videoId], $extra);
    }

    /**
     * Import every video of a YouTube playlist, optionally linking all imported
     * tracks to one album id supplied through $extra['album_id'].
     *
     * @param array<string, mixed> $extra Extra track attributes applied to every
     *        imported track.
     * @return array<int, Track>
     */
    public function importPlaylist(string $playlistId, array $extra = []): array
    {
        $videoIds = $this->fetchPlaylistVideoIds($playlistId);

        if ($videoIds === []) {
            return [];
        }

        $videos = $this->fetchVideos($videoIds);
        $tracks = [];

        foreach ($videoIds as $videoId) {
            if (! isset($videos[$videoId])) {
                continue;
            }

            $tracks[] = $this->ingestFromVideo($videos[$videoId], $extra);
        }

        return $tracks;
    }

    /**
     * Ingest a single "videos.list" item through the catalogue service.
     *
     * @param array<string, mixed> $video A raw item from videos.list.
     * @param array<string, mixed> $extra Extra track attributes.
     */
    private function ingestFromVideo(array $video, array $extra): Track
    {
        $snippet = $video['snippet'] ?? [];
        $contentDetails = $video['contentDetails'] ?? [];

        $title = (string) ($snippet['title'] ?? 'Untitled');
        $channelTitle = (string) ($snippet['channelTitle'] ?? 'Unknown Artist');
        $durationMs = isset($contentDetails['duration'])
            ? $this->parseIsoDuration((string) $contentDetails['duration'])
            : null;

        $artist = $this->catalog()->ingestArtist($channelTitle);

        $attributes = array_merge([
            // Imported media is unvetted by default: unknown rights, private.
            'rights_status' => 'unknown',
            'visibility' => 'private',
        ], $extra);

        if ($durationMs !== null) {
            $attributes['duration_ms'] = $attributes['duration_ms'] ?? $durationMs;
        }

        return $this->catalog()->ingestYoutube(
            $artist,
            $title,
            (string) $video['id'],
            $attributes,
        );
    }

    /**
     * Fetch full video resources for the given ids, batched 50 at a time as
     * required by the YouTube API. Returns a map keyed by video id.
     *
     * @param array<int, string> $videoIds
     * @return array<string, array<string, mixed>>
     */
    public function fetchVideos(array $videoIds): array
    {
        $videoIds = array_values(array_unique(array_filter($videoIds)));

        if ($videoIds === []) {
            return [];
        }

        $videos = [];

        foreach (array_chunk($videoIds, 50) as $chunk) {
            $response = $this->client()->get(self::VIDEOS_ENDPOINT, [
                'part' => 'snippet,contentDetails',
                'id' => implode(',', $chunk),
                'key' => $this->apiKey(),
            ]);

            if ($response->failed()) {
                throw new RuntimeException(sprintf(
                    'YouTube videos.list request failed with status %d.',
                    $response->status(),
                ));
            }

            foreach ($response->json('items', []) as $item) {
                $videos[(string) $item['id']] = $item;
            }
        }

        return $videos;
    }

    /**
     * Fetch every video id of a playlist, following pagination tokens.
     *
     * @return array<int, string>
     */
    public function fetchPlaylistVideoIds(string $playlistId): array
    {
        $videoIds = [];
        $pageToken = null;

        do {
            $query = [
                'part' => 'snippet',
                'playlistId' => $playlistId,
                'maxResults' => 50,
                'key' => $this->apiKey(),
            ];

            if ($pageToken !== null) {
                $query['pageToken'] = $pageToken;
            }

            $response = $this->client()->get(self::PLAYLIST_ITEMS_ENDPOINT, $query);

            if ($response->failed()) {
                throw new RuntimeException(sprintf(
                    'YouTube playlistItems.list request failed with status %d.',
                    $response->status(),
                ));
            }

            foreach ($response->json('items', []) as $item) {
                $videoId = $item['snippet']['resourceId']['videoId'] ?? null;
                if (is_string($videoId) && $videoId !== '') {
                    $videoIds[] = $videoId;
                }
            }

            $pageToken = $response->json('nextPageToken');
        } while (is_string($pageToken) && $pageToken !== '');

        return array_values(array_unique($videoIds));
    }

    /**
     * Convert an ISO 8601 duration (e.g. PT4M13S) to milliseconds.
     */
    private function parseIsoDuration(string $iso8601): ?int
    {
        if (! preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$/', $iso8601, $matches)) {
            return null;
        }

        $hours = (int) ($matches[1] ?? 0);
        $minutes = (int) ($matches[2] ?? 0);
        $seconds = (int) ($matches[3] ?? 0);
        $total = $hours * 3600 + $minutes * 60 + $seconds;

        return $total > 0 ? $total * 1000 : null;
    }

    /**
     * Build the HTTP client used for YouTube API calls (short timeout, JSON).
     */
    private function client(): PendingRequest
    {
        return Http::acceptJson()->timeout(15);
    }

    /**
     * Resolve the configured YouTube API key, failing fast when it is absent
     * so misconfiguration never silently produces empty imports.
     */
    private function apiKey(): string
    {
        $key = (string) config('services.youtube.key', '');

        if ($key === '') {
            throw new RuntimeException('The YouTube API key is not configured (services.youtube.key).');
        }

        return $key;
    }

    /**
     * Resolve the catalogue ingestion service from the container so callers can
     * instantiate this service without wiring dependencies manually.
     */
    private function catalog(): CatalogIngestionService
    {
        return app(CatalogIngestionService::class);
    }
}
