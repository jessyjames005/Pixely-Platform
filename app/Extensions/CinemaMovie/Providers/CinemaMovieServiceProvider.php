<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers CinemaMovie translation resources.
 */
final class CinemaMovieServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'cinema-movie');
    }
}
