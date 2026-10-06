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
 * service container, making its page and menu management
 * capabilities available throughout the application.
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
        // Service provider boot logic can be added here
    }
}