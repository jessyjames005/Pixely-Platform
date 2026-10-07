<?php

declare(strict_types=1);

namespace App\Core\Surface\Providers;

use App\Core\Surface\Services\SurfaceAuthorizationService;
use App\Core\Surface\Services\SurfaceContext;
use App\Core\Surface\Services\SurfaceResolver;
use Illuminate\Support\ServiceProvider;

final class SurfaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(SurfaceContext::class);
        $this->app->singleton(SurfaceResolver::class);
        $this->app->singleton(SurfaceAuthorizationService::class);
    }
}
