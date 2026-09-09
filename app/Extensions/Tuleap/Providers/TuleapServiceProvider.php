<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Providers;

use App\Extensions\Tuleap\Contracts\TuleapRepositoryInterface;
use App\Extensions\Tuleap\Contracts\TuleapServiceInterface;
use App\Extensions\Tuleap\Repositories\TuleapRepository;
use App\Extensions\Tuleap\Services\TuleapService;
use Illuminate\Support\ServiceProvider;

final class TuleapServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TuleapRepositoryInterface::class,
            TuleapRepository::class,
        );

        $this->app->bind(
            TuleapServiceInterface::class,
            TuleapService::class,
        );
    }

    public function boot(): void
    {
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