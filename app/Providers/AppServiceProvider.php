<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Media\Contracts\StorageInterface;
use App\Media\Drivers\LocalStorage;
use App\Media\Contracts\ImageProcessorInterface;
use App\Media\Processors\InterventionImageProcessor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            StorageInterface::class,
            LocalStorage::class
        );

        $this->app->bind(
            ImageProcessorInterface::class,
            InterventionImageProcessor::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
