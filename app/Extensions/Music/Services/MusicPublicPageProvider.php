<?php

declare(strict_types=1);

namespace App\Extensions\Music\Services;

use App\Core\Websites\Models\PageModel;
use App\Extensions\Music\Models\Track;

/**
 * Provider that exposes Music catalog items as public website pages
 * through the Website Engine contract without leaking extension models into core.
 */
final class MusicPublicPageProvider
{
    /**
     * Get a public page representation for a music track by ID and locale.
     */
    public function getTrackPage(int|string $id, string $locale = 'en'): ?PageModel
    {
        /** @var Track|null $track */
        $track = Track::with(['artist', 'album', 'genres'])
            ->where('visibility', 'public')
            ->where('rights_status', '!=', 'unknown')
            ->where('status', 'ready')
            ->find($id);

        if (!$track) {
            return null;
        }

        $title = $track->title;
        $artistName = $track->artist?->name ?? 'Unknown Artist';

        return new PageModel(
            id: 'music_track_' . $track->id,
            slug: 'music/tracks/' . $track->id,
            locale: $locale,
            title: $title,
            status: 'published',
            template: 'track-detail',
            publishedAt: $track->created_at?->toIso8601String(),
            seo: [
                'title' => $title . ' - ' . $artistName,
                'description' => 'Listen to ' . $title . ' by ' . $artistName . ' on Pixely Music.',
            ],
            blocks: [
                [
                    'type' => 'heading',
                    'content' => $title,
                ],
                [
                    'type' => 'paragraph',
                    'content' => 'Artist: ' . $artistName,
                ],
            ],
        );
    }
}
