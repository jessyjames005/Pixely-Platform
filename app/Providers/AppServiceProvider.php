<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Extensions\Configuration\DatabaseExtensionConfigurationRepository;
use App\Core\Extensions\Configuration\ExtensionConfigurationRepositoryInterface;
use App\Core\Translations\Contracts\TranslationFileSystemInterface;
use App\Core\Translations\Services\LocalTranslationFileSystem;
use App\Media\Contracts\ImageProcessorInterface;
use App\Media\Contracts\StorageInterface;
use App\Media\Drivers\LocalStorage;
use App\Media\Processors\InterventionImageProcessor;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Scramble::ignoreDefaultRoutes();

        $this->app->bind(
            StorageInterface::class,
            LocalStorage::class
        );

        $this->app->bind(
            ImageProcessorInterface::class,
            InterventionImageProcessor::class
        );

        $this->app->bind(
            TranslationFileSystemInterface::class,
            LocalTranslationFileSystem::class,
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
