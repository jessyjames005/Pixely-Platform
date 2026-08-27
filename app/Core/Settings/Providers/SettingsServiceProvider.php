<?php

declare(strict_types=1);

namespace App\Core\Settings\Providers;

use App\Core\Settings\Http\Middleware\SetLocaleFromUser;
use Illuminate\Support\ServiceProvider;

/**
 * Registers Core settings/localization API routes and middleware.
 */
final class SettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->router
            ->middleware(['api', SetLocaleFromUser::class])
            ->prefix('api/v1')
            ->group(
                __DIR__ . '/../routes/api.php'
            );
    }
}
