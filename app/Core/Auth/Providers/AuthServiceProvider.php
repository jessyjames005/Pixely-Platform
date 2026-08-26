<?php

declare(strict_types=1);

namespace App\Core\Auth\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers the Core authentication API routes.
 *
 * Follows the same registration convention as extension
 * service providers (e.g. GalleryServiceProvider).
 */
final class AuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
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
