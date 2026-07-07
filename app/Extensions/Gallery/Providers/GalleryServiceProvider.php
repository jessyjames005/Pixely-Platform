<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Providers;

use Illuminate\Support\ServiceProvider;

final class GalleryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(
            __DIR__ . '/../routes/web.php'
        );
    }
}
