<?php

declare(strict_types=1);

namespace App\Core\Registry\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the public Registry API routes.
 */
final class RegistryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('registry-public', static function (Request $request): array {
            $ip = (string) ($request->ip() ?? 'unknown');
            $clientHeader = $request->header('X-Pixely-Client');
            $clientId = is_string($clientHeader)
                && strlen($clientHeader) <= 128
                && preg_match('/^[A-Za-z0-9._:-]+$/D', $clientHeader) === 1
                ? hash('sha256', $clientHeader)
                : 'ip-' . hash('sha256', $ip);

            return [
                Limit::perMinute(60)
                    ->by('ip-' . hash('sha256', $ip)),
                Limit::perMinute(120)
                    ->by('client-' . $clientId),
            ];
        });

        Route::middleware('api')
            ->prefix('api/v1')
            ->group(__DIR__ . '/../routes/api.php');
    }
}
