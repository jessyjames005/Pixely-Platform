<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Providers;

use Illuminate\Support\ServiceProvider;
use App\Extensions\Gallery\Contracts\GalleryRepositoryInterface;
use App\Extensions\Gallery\Repositories\GalleryRepository;

final class GalleryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            GalleryRepositoryInterface::class,
            GalleryRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(
            __DIR__ . '/../Resources/Views',
            'gallery'
        );

        $this->loadRoutesFrom(
            __DIR__ . '/../routes/web.php'
        );

        $this->loadMigrationsFrom(
            __DIR__ . '/../Database/Migrations'
        );
    }
}
