<?php

declare(strict_types=1);

namespace App\Core\Auth\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Two-factor authentication lifecycle for a user: enrolment, confirmation,
 * login challenge verification and recovery codes.
 *
 * Enrolment is two-step. A secret is generated and stored as unconfirmed;
 * two-factor only becomes active once the user proves their authenticator app
 * produces valid codes (`two_factor_confirmed_at`). This prevents locking a
 * user out with a secret they never managed to register.
 */
final class TwoFactorService
{
    /**
     * Session key holding the half-authenticated user while the second
     * factor is pending.
     */
    public const SESSION_KEY = 'auth.two_factor_pending';

    /**
     * Seconds the user has to complete the challenge after the password step.
     */
    public const CHALLENGE_TTL = 300;

    private const RECOVERY_CODE_COUNT = 8;

    public function __construct(private readonly TotpService $totp)
    {
    }

    /**
     * Start (or restart) enrolment with a fresh, unconfirmed secret.
     *
     * @return array{secret: string, otpauth_uri: string}
     */
    public function beginEnrolment(User $user): array
    {
        $secret = $this->totp->generateSecret();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_step' => null,
        ])->save();

        return [
            'secret' => $secret,
            'otpauth_uri' => $this->totp->provisioningUri(
                $secret,
                $user->email,
                (string) config('app.name', 'Pixely'),
            ),
        ];
    }

    /**
     * Confirm enrolment with a code from the authenticator app.
     *
     * @return array<int, string>|null The plain recovery codes (shown once),
     *                                 or null when the code is invalid or
     *                                 there is no pending enrolment.
     */
    public function confirm(User $user, string $code): ?array
    {
        $secret = $user->two_factor_secret;

        if (! is_string($secret) || $user->hasEnabledTwoFactor()) {
            return null;
        }

        $step = $this->totp->verify($secret, $code);

        if ($step === null) {
            return null;
        }

        $codes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_last_used_step' => $step,
            'two_factor_recovery_codes' => $this->hashCodes($codes),
        ])->save();

        return $codes;
    }

    /**
     * Verify a login-challenge code. A step that was already accepted is
     * rejected, so a code is single-use.
     */
    public function verifyChallenge(User $user, string $code): bool
    {
        $secret = $user->two_factor_secret;

        if (! is_string($secret) || ! $user->hasEnabledTwoFactor()) {
            return false;
        }

        $step = $this->totp->verify($secret, $code, $user->two_factor_last_used_step);

        if ($step === null) {
            return false;
        }

        $user->forceFill(['two_factor_last_used_step' => $step])->save();

        return true;
    }

    /**
     * Consume a recovery code. Each code works once.
     */
    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $stored = $user->two_factor_recovery_codes;

        if (! $user->hasEnabledTwoFactor() || ! is_array($stored)) {
            return false;
        }

        $candidate = $this->hashCode($code);
        $remaining = [];
        $matched = false;

        foreach ($stored as $hash) {
            if (! $matched && is_string($hash) && hash_equals($hash, $candidate)) {
                $matched = true;

                continue;
            }

            $remaining[] = $hash;
        }

        if ($matched) {
            $user->forceFill(['two_factor_recovery_codes' => $remaining])->save();
        }

        return $matched;
    }

    /**
     * Replace the recovery codes with a new set.
     *
     * @return array<int, string>|null Null when two-factor is not enabled.
     */
    public function regenerateRecoveryCodes(User $user): ?array
    {
        if (! $user->hasEnabledTwoFactor()) {
            return null;
        }

        $codes = $this->generateRecoveryCodes();

        $user->forceFill(['two_factor_recovery_codes' => $this->hashCodes($codes)])->save();

        return $codes;
    }

    public function remainingRecoveryCodes(User $user): int
    {
        $stored = $user->two_factor_recovery_codes;

        return is_array($stored) ? count($stored) : 0;
    }

    /**
     * Turn two-factor off and discard every related secret.
     */
    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_step' => null,
        ])->save();
    }

    /**
     * @return array<int, string> Codes formatted as `xxxxx-xxxxx`.
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $raw = Str::lower(Str::random(10));
            $codes[] = substr($raw, 0, 5) . '-' . substr($raw, 5);
        }

        return $codes;
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<int, string>
     */
    private function hashCodes(array $codes): array
    {
        return array_map(fn (string $code): string => $this->hashCode($code), $codes);
    }

    /**
     * Recovery codes are high-entropy random values, so a keyed HMAC is
     * enough (and, unlike bcrypt, cheap to check against a whole set). The
     * hashes are additionally encrypted at rest by the model cast.
     */
    private function hashCode(string $code): string
    {
        $normalised = Str::lower((string) preg_replace('/[^A-Za-z0-9]/', '', $code));

        return hash_hmac('sha256', $normalised, (string) config('app.key'));
    }
}
