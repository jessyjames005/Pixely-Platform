<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Media\Contracts\StorageInterface;
use App\Media\Drivers\LocalStorage;
use App\Media\Contracts\ImageProcessorInterface;
use App\Media\Processors\InterventionImageProcessor;
use App\Core\Extensions\Configuration\DatabaseExtensionConfigurationRepository;
use App\Core\Extensions\Configuration\ExtensionConfigurationRepositoryInterface;

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

        $this->app->singleton(
            ExtensionConfigurationRepositoryInterface::class,
            DatabaseExtensionConfigurationRepository::class,
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
