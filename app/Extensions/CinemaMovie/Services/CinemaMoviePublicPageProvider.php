<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Services;

use App\Core\Websites\Models\PageModel;
use App\Extensions\CinemaMovie\Models\Movie;
use App\Extensions\CinemaMovie\Models\MovieTranslation;

/**
 * Provider that exposes CinemaMovie catalog items as public website pages
 * through the Website Engine contract without leaking extension models into core.
 */
final class CinemaMoviePublicPageProvider
{
    /**
     * Create a new CinemaMoviePublicPageProvider instance.
     */
    public function __construct()
    {
    }

    /**
     * Get a public page representation for a cinema movie by slug and locale.
     *
     * @param string $slug Movie slug.
     * @param string $locale Content locale (e.g. 'en', 'fr').
     * @return PageModel|null Page DTO if found.
     */
    public function getMoviePage(string $slug, string $locale = 'en'): ?PageModel
    {
        /** @var Movie|null $movie */
        $movie = Movie::with('translations')->where('slug', $slug)->first();

        if (!$movie) {
            return null;
        }

        $translation = $movie->translations->where('locale', $locale)->first()
            ?? $movie->translations->first();

        // Narrow the type explicitly so the localized fields are read safely
        // even when a movie has no translation row at all.
        $title = $movie->original_title ?? 'Movie';
        $overview = '';

        if ($translation instanceof MovieTranslation) {
            $title = $translation->title ?: $title;
            $overview = $translation->overview ?? '';
        }

        return new PageModel(
            id: 'cinema_' . $movie->id,
            slug: 'movies/' . $movie->slug,
            locale: $locale,
            title: $title,
            status: $movie->publication_status ?? 'published',
            template: 'movie-detail',
            publishedAt: $movie->created_at?->toIso8601String(),
            seo: [
                'title' => $title,
                'description' => $overview,
            ],
            blocks: [
                [
                    'type' => 'heading',
                    'content' => $title,
                ],
                [
                    'type' => 'paragraph',
                    'content' => $overview,
                ],
            ],
        );
    }
}
