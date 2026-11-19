<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Services;

use App\Extensions\CinemaMovie\Models\Genre;
use App\Extensions\CinemaMovie\Models\GenreTranslation;
use App\Extensions\CinemaMovie\Models\Movie;
use App\Extensions\CinemaMovie\Models\MovieTranslation;
use Illuminate\Database\Eloquent\Collection;

final class TranslationResolver
{
    public function forMovie(Movie $movie, ?string $requestedLocale = null): ?MovieTranslation
    {
        $translations = $this->movieTranslations($movie);
        $fallbacks = array_filter([
            $requestedLocale,
            app()->getLocale(),
            $movie->original_language,
        ]);

        foreach (array_unique($fallbacks) as $locale) {
            $translation = $translations->firstWhere('locale', $locale);
            if ($translation instanceof MovieTranslation) {
                return $translation;
            }
        }

        return $translations->first();
    }

    public function forGenre(Genre $genre, ?string $requestedLocale = null): ?GenreTranslation
    {
        $translations = $genre->relationLoaded('translations')
            ? $genre->getRelation('translations')
            : $genre->translations()->get();
        $fallbacks = array_filter([$requestedLocale, app()->getLocale()]);

        foreach (array_unique($fallbacks) as $locale) {
            $translation = $translations->firstWhere('locale', $locale);
            if ($translation instanceof GenreTranslation) {
                return $translation;
            }
        }

        return $translations->first();
    }

    /** @return Collection<int, MovieTranslation> */
    private function movieTranslations(Movie $movie): Collection
    {
        return $movie->relationLoaded('translations')
            ? $movie->getRelation('translations')
            : $movie->translations()->get();
    }
}
