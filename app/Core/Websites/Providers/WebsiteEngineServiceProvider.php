<?php

declare(strict_types=1);

namespace App\Core\Websites\Providers;

use App\Core\Websites\Contracts\WebsiteEngineInterface;
use App\Core\Websites\Services\WebsiteEngine;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the Website Engine service and its API routes.
 */
final class WebsiteEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WebsiteEngineInterface::class, WebsiteEngine::class);
    }

    public function boot(): void
    {
        $this->app->router
            ->middleware('api')
            ->prefix('api/v1')
            ->group(__DIR__ . '/../routes/api.php');
    }
}
