<?php

declare(strict_types=1);

namespace App\Core\Settings\Http\Middleware;

use App\Core\Settings\Models\PlatformSetting;
use App\Core\Settings\Models\UserSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves and applies the effective locale for the current request.
 *
 * Resolution order: authenticated user's own locale preference,
 * falling back to the platform-wide locale, falling back to the
 * application's configured default.
 */
final class SetLocaleFromUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()
            ? UserSetting::forUser($request->user()->id)->settings['locale'] ?? null
            : null;

        $locale ??= PlatformSetting::current()->settings['locale'] ?? null;
        $locale ??= config('pixely.default_locale');

        app()->setLocale($locale);

        return $next($request);
    }
}
