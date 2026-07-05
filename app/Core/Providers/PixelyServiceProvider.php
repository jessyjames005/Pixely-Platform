<?php

declare(strict_types=1);

namespace App\Core\Providers;

use Illuminate\Support\ServiceProvider;
use App\Core\Kernel\Kernel;

final class PixelyServiceProvider extends ServiceProvider
{
    /**
     * Register services into the Laravel container.
     */
    public function register(): void
    {
        $this->app->singleton(Kernel::class, function ($app) {
            return new Kernel(
                $app->make(\App\Core\Extensions\ExtensionManager::class)
            );
        });

        $this->app->singleton(\App\Core\Extensions\ExtensionManager::class, function ($app) {
            return new \App\Core\Extensions\ExtensionManager(
                new \App\Core\Extensions\ExtensionRegistry()
            );
        });
    }

    /**
     * Bootstrap services after all providers are registered.
     */
    public function boot(): void
    {
        /** @var Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);

        //  Boot automatique de Pixely
        $kernel->boot();
    }
}
