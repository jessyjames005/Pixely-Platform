<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers CinemaMovie extension API routes.
 */
final class CinemaMovieServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->router
            ->middleware('api')
            ->prefix('api/v1')
            ->group(
                __DIR__ . '/../routes/api.php'
            );
    }
}
