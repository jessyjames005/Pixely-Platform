<?php

declare(strict_types=1);

namespace App\Core\Tooling\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers Core platform tooling API routes (System Observability).
 */
final class ToolingServiceProvider extends ServiceProvider
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
