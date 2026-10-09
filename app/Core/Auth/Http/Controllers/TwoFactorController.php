<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Http\Support\AuthApiError;
use App\Core\Auth\Services\TwoFactorService;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use LaravelJsonApi\Core\Responses\MetaResponse;

/**
 * Self-service management of the current user's two-factor authentication.
 *
 * Every action that changes the security posture re-checks the account
 * password, so a hijacked but idle session cannot silently disable the
 * second factor.
 */
#[Group('Authentication', weight: 2)]
final class TwoFactorController
{
    public function __construct(private readonly TwoFactorService $twoFactor)
    {
    }

    /**
     * Current two-factor status of the authenticated user.
     */
    public function show(Request $request): MetaResponse
    {
        $user = $this->user($request);

        return MetaResponse::make([
            'enabled' => $user->hasEnabledTwoFactor(),
            'pending_confirmation' => ! $user->hasEnabledTwoFactor() && $user->two_factor_secret !== null,
            'recovery_codes_remaining' => $this->twoFactor->remainingRecoveryCodes($user),
        ]);
    }

    /**
     * Start enrolment: returns the shared secret and the provisioning URI.
     * Two-factor stays inactive until confirmed with a valid code.
     */
    public function store(Request $request): MetaResponse
    {
        $user = $this->userWithConfirmedPassword($request);

        if ($user->hasEnabledTwoFactor()) {
            throw AuthApiError::twoFactorAlreadyEnabled();
        }

        return MetaResponse::make($this->twoFactor->beginEnrolment($user));
    }

    /**
     * Confirm enrolment with a code from the authenticator app. Returns the
     * recovery codes, which are shown only this once.
     */
    public function confirm(Request $request): MetaResponse
    {
        $input = Validator::make(
            (array) $request->json()->all(),
            ['code' => ['required', 'string', 'max:32']],
        )->validate();

        $user = $this->user($request);

        if ($user->hasEnabledTwoFactor()) {
            throw AuthApiError::twoFactorAlreadyEnabled();
        }

        $codes = $this->twoFactor->confirm($user, (string) $input['code']);

        if ($codes === null) {
            throw AuthApiError::invalidTwoFactorCode();
        }

        return MetaResponse::make(['recovery_codes' => $codes]);
    }

    /**
     * Turn two-factor authentication off.
     */
    public function destroy(Request $request): Response
    {
        $user = $this->userWithConfirmedPassword($request);

        $this->twoFactor->disable($user);

        return response()->noContent();
    }

    /**
     * Replace the recovery codes with a new set (the old ones stop working).
     */
    public function regenerateRecoveryCodes(Request $request): MetaResponse
    {
        $user = $this->userWithConfirmedPassword($request);

        $codes = $this->twoFactor->regenerateRecoveryCodes($user);

        if ($codes === null) {
            throw AuthApiError::twoFactorNotEnabled();
        }

        return MetaResponse::make(['recovery_codes' => $codes]);
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    /**
     * The authenticated user, once the `password` body field is verified.
     */
    private function userWithConfirmedPassword(Request $request): User
    {
        $input = Validator::make(
            (array) $request->json()->all(),
            ['password' => ['required', 'string']],
        )->validate();

        $user = $this->user($request);

        if (! Hash::check((string) $input['password'], $user->password)) {
            throw AuthApiError::invalidPassword();
        }

        return $user;
    }
}
