<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Providers;

use Illuminate\Support\ServiceProvider;
use App\Extensions\Gallery\Contracts\GalleryRepositoryInterface;
use App\Extensions\Gallery\Repositories\GalleryRepository;
use App\Extensions\Gallery\Contracts\GalleryServiceInterface;
use App\Extensions\Gallery\Services\GalleryService;

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
        $this->loadViewsFrom(
            __DIR__ . '/../Resources/views',
            'gallery'
        );

        $this->app->router->middleware('web')
            ->group(
                __DIR__ . '/../routes/web.php'
            );

        $this->app->router
            ->middleware('api')
            ->prefix('api/v1')
            ->group(
                __DIR__ . '/../routes/api.php'
            );

        $this->loadMigrationsFrom(
            __DIR__ . '/../Database/Migrations'
        );
    }
}
