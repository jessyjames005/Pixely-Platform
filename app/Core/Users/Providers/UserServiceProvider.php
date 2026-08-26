<?php

declare(strict_types=1);

namespace App\Core\Users\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers the Core user management API routes.
 *
 * Follows the same registration convention as AuthServiceProvider
 * and extension service providers (e.g. GalleryServiceProvider).
 */
final class UserServiceProvider extends ServiceProvider
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
