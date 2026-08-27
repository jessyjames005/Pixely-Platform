<?php

declare(strict_types=1);

namespace App\Core\Roles\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers the Core role and permission management API routes.
 */
final class RoleServiceProvider extends ServiceProvider
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
