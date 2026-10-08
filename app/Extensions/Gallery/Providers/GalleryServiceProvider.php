<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Providers;

use App\Extensions\Gallery\Contracts\GalleryRepositoryInterface;
use App\Extensions\Gallery\Contracts\GalleryServiceInterface;
use App\Extensions\Gallery\Repositories\GalleryRepository;
use App\Extensions\Gallery\Services\GalleryService;
use Illuminate\Support\ServiceProvider;

final class GalleryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            GalleryRepositoryInterface::class,
            GalleryRepository::class,
        );

        $this->app->bind(
            GalleryServiceInterface::class,
            GalleryService::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

        $this->loadMigrationsFrom(
            __DIR__ . '/../Database/Migrations'
        );
    }
}
