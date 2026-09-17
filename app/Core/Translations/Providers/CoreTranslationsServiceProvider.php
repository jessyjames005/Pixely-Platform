<?php

declare(strict_types=1);

namespace App\Core\Translations\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers Core's public translation-catalog route.
 *
 * Distinct from the Translations extension's own service provider,
 * which registers the gated translation-management API instead.
 */
final class CoreTranslationsServiceProvider extends ServiceProvider
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
