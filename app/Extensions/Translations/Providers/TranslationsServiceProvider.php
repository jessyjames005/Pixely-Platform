<?php

declare(strict_types=1);

namespace App\Extensions\Translations\Providers;

use Illuminate\Support\ServiceProvider;

final class TranslationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->router
            ->middleware('api')
            ->prefix('api/v1')
            ->group(
                __DIR__ . '/../routes/api.php'
            );
    }
}
