<?php

declare(strict_types=1);

namespace App\Core\Websites\Providers;

use App\Core\Websites\Contracts\WebsiteEngineInterface;
use App\Core\Websites\Services\WebsiteEngine;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Website Engine.
 *
 * Registers the WebsiteEngine as a singleton in the Laravel
 * service container and exposes its management API routes
 * under api/v1/website, following the same per-module
 * routing convention as the other Core modules.
 */
final class WebsiteEngineServiceProvider extends ServiceProvider
{
    /**
     * Register services in the Laravel container.
     */
    public function register(): void
    {
        $this->app->singleton(
            WebsiteEngineInterface::class,
            WebsiteEngine::class,
        );
    }

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