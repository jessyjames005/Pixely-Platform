<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Http\Support\AuthApiError;
use App\Core\Auth\Http\Support\AuthUserResponse;
use App\Core\Auth\Services\TwoFactorService;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Responses\DataResponse;

/**
 * Second step of the login flow for accounts with two-factor enabled.
 */
#[Group('Authentication', weight: 2)]
final class TwoFactorChallengeController
{
    private const MAX_FAILED_ATTEMPTS = 5;

    private const THROTTLE_DECAY_SECONDS = 60;

    public function __construct(private readonly TwoFactorService $twoFactor)
    {
    }

    /**
     * Complete a pending login with an authenticator code or a recovery code.
     */
    public function store(Request $request, Server $server): DataResponse
    {
        $input = Validator::make(
            (array) $request->json()->all(),
            [
                'code' => ['nullable', 'string', 'max:32', 'required_without:recovery_code'],
                'recovery_code' => ['nullable', 'string', 'max:32', 'required_without:code'],
            ],
        )->validate();

        $pending = $request->session()->get(TwoFactorService::SESSION_KEY);
        $user = $this->resolvePendingUser($pending);

        if ($user === null) {
            $request->session()->forget(TwoFactorService::SESSION_KEY);

            throw AuthApiError::twoFactorSessionExpired();
        }

        $throttleKey = 'two-factor:' . $user->getKey() . '|' . (string) $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_FAILED_ATTEMPTS)) {
            $request->session()->forget(TwoFactorService::SESSION_KEY);

            throw AuthApiError::tooManyAttempts(RateLimiter::availableIn($throttleKey));
        }

        $code = isset($input['code']) ? (string) $input['code'] : '';
        $valid = $code !== ''
            ? $this->twoFactor->verifyChallenge($user, $code)
            : $this->twoFactor->consumeRecoveryCode($user, (string) ($input['recovery_code'] ?? ''));

        if (! $valid) {
            RateLimiter::hit($throttleKey, self::THROTTLE_DECAY_SECONDS);

            throw AuthApiError::invalidTwoFactorCode();
        }

        RateLimiter::clear($throttleKey);

        $remember = is_array($pending) && ($pending['remember'] ?? false) === true;
        $request->session()->forget(TwoFactorService::SESSION_KEY);

        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();

        return AuthUserResponse::make($server, $user);
    }

    /**
     * The user who passed the password step, if the pending login is still
     * valid (present, unexpired, and the account is still eligible).
     */
    private function resolvePendingUser(mixed $pending): ?User
    {
        if (
            ! is_array($pending)
            || ! isset($pending['id'], $pending['expires_at'])
            || (int) $pending['expires_at'] < time()
        ) {
            return null;
        }

        $user = User::query()->find($pending['id']);

        if (! $user instanceof User || ! $user->is_active || ! $user->hasEnabledTwoFactor()) {
            return null;
        }

        return $user;
    }
}
