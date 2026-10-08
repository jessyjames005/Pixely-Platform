<?php

declare(strict_types=1);

namespace App\Core\Users\Providers;

use Illuminate\Support\ServiceProvider;

/** Registers Core User Space API routes, including generic engagement endpoints. */
final class UserServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->router
            ->middleware('api')
            ->prefix('api/v1')
            ->group(__DIR__ . '/../routes/api.php');

        $this->app->router
            ->middleware('api')
            ->prefix('api/v1')
            ->group(__DIR__ . '/../routes/engagement.php');
    }
}
