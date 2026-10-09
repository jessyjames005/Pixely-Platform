<?php

declare(strict_types=1);

namespace App\Core\Auth\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Registers the Core authentication API routes, the authentication rate
 * limiters and the password reset link target.
 *
 * Follows the same registration convention as extension
 * service providers (e.g. GalleryServiceProvider).
 */
final class AuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPasswordResetUrl();
        $this->registerRateLimiters();

        $this->app->router
            ->middleware('api')
            ->prefix('api/v1')
            ->group(
                __DIR__ . '/../routes/api.php'
            );
    }

    /**
     * Password reset emails link to the SPA's reset screen, not to a
     * server-rendered Laravel page.
     */
    private function registerPasswordResetUrl(): void
    {
        ResetPassword::createUrlUsing(
            static fn (CanResetPassword $notifiable, string $token): string => url('/reset-password') . '?' . http_build_query([
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]),
        );
    }

    private function registerRateLimiters(): void
    {
        // Requesting / using a reset link: keyed by client and target email so
        // one address cannot be mail-bombed from a single client.
        RateLimiter::for('auth-password-reset', static fn (Request $request): Limit => Limit::perMinute(5)->by(
            (string) $request->ip() . '|' . Str::lower((string) $request->json('email', '')),
        ));

        // Authenticated, security-sensitive actions (password change,
        // two-factor management).
        RateLimiter::for('auth-sensitive', static fn (Request $request): Limit => Limit::perMinute(10)->by(
            (string) ($request->user()?->getAuthIdentifier() ?? $request->ip()),
        ));
    }
}
