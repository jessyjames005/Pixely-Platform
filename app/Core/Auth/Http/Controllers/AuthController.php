<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Http\Support\AuthApiError;
use App\Core\Auth\Http\Support\AuthUserResponse;
use App\Core\Auth\Services\TwoFactorService;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Responses\MetaResponse;

/**
 * Handles session-based authentication for the platform SPA.
 */
#[Group('Authentication', weight: 2)]
final class AuthController
{
    private const MAX_FAILED_ATTEMPTS = 5;

    private const THROTTLE_DECAY_SECONDS = 60;

    /**
     * Authenticate a user and start a session.
     *
     * Only failed attempts count towards the rate limit, so a successful
     * login (including automated test suites) is never throttled. When the
     * account has two-factor authentication enabled no session is started
     * yet: the response carries `meta.two_factor_required` and the client
     * must complete `POST /auth/two-factor-challenge`.
     */
    public function login(
        Request $request,
        Server $server,
    ): DataResponse|MetaResponse {
        $input = Validator::make(
            (array) $request->json()->all(),
            [
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
                'remember' => ['sometimes', 'boolean'],
            ],
        )->validate();

        $throttleKey = Str::transliterate(Str::lower((string) $input['email']) . '|' . (string) $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_FAILED_ATTEMPTS)) {
            throw AuthApiError::tooManyAttempts(RateLimiter::availableIn($throttleKey));
        }

        $user = $this->findUserWithCredentials((string) $input['email'], (string) $input['password']);

        if ($user === null) {
            RateLimiter::hit($throttleKey, self::THROTTLE_DECAY_SECONDS);

            throw AuthApiError::invalidCredentials();
        }

        RateLimiter::clear($throttleKey);

        if (! $user->is_active) {
            throw AuthApiError::accountDisabled();
        }

        $remember = (bool) ($input['remember'] ?? false);

        if ($user->hasEnabledTwoFactor()) {
            $request->session()->regenerate();
            $request->session()->put(TwoFactorService::SESSION_KEY, [
                'id' => $user->getKey(),
                'remember' => $remember,
                'expires_at' => time() + TwoFactorService::CHALLENGE_TTL,
            ]);

            return MetaResponse::make(['two_factor_required' => true]);
        }

        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();

        return AuthUserResponse::make($server, $user);
    }

    /**
     * Log the current user out and invalidate the session.
     */
    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(Request $request, Server $server): DataResponse
    {
        /** @var User $user */
        $user = $request->user();

        return AuthUserResponse::make($server, $user);
    }

    /**
     * Resolve a user from an email/password pair without starting a session.
     */
    private function findUserWithCredentials(string $email, string $password): ?User
    {
        $provider = Auth::createUserProvider('users');

        if ($provider === null) {
            return null;
        }

        $user = $provider->retrieveByCredentials(['email' => $email]);

        if (! $user instanceof User || ! $provider->validateCredentials($user, ['password' => $password])) {
            return null;
        }

        return $user;
    }
}
