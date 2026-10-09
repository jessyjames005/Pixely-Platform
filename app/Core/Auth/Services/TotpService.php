<?php

declare(strict_types=1);

namespace App\Core\Auth\Services;

use InvalidArgumentException;

/**
 * RFC 6238 time-based one-time passwords (HMAC-SHA1, 6 digits, 30 s period).
 *
 * These are the parameters every mainstream authenticator app supports, so
 * the provisioning URI does not need to advertise anything unusual. The class
 * is deliberately free of framework dependencies so it can be unit-tested
 * against the RFC test vectors.
 */
final class TotpService
{
    public const PERIOD = 30;

    public const DIGITS = 6;

    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const SECRET_BYTES = 20;

    /**
     * Generate a new random shared secret, base32 encoded (32 characters).
     */
    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(self::SECRET_BYTES));
    }

    /**
     * The one-time code for a given time step.
     */
    public function codeAt(string $secret, int $timeStep): string
    {
        $hash = hash_hmac('sha1', pack('J', $timeStep), $this->base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;

        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($binary % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * The time step (30 s window index) for a Unix timestamp.
     */
    public function timeStep(?int $timestamp = null): int
    {
        return intdiv($timestamp ?? time(), self::PERIOD);
    }

    /**
     * Check a code against the current time step, tolerating clock drift of
     * `$window` steps either side.
     *
     * Steps at or before `$lastUsedStep` are rejected so an intercepted code
     * cannot be replayed within its validity window.
     *
     * @return int|null The matched time step, or null when the code is invalid.
     */
    public function verify(
        string $secret,
        string $code,
        ?int $lastUsedStep = null,
        ?int $timestamp = null,
        int $window = 1,
    ): ?int {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (preg_match('/^\d{' . self::DIGITS . '}$/', $code) !== 1) {
            return null;
        }

        $current = $this->timeStep($timestamp);
        $matched = null;

        // Every candidate step is compared (no early exit) to keep timing flat.
        for ($offset = -$window; $offset <= $window; $offset++) {
            $step = $current + $offset;

            if ($step < 0 || ($lastUsedStep !== null && $step <= $lastUsedStep)) {
                continue;
            }

            if (hash_equals($this->codeAt($secret, $step), $code)) {
                $matched = $step;
            }
        }

        return $matched;
    }

    /**
     * The `otpauth://` URI understood by authenticator apps.
     */
    public function provisioningUri(string $secret, string $account, string $issuer): string
    {
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);

        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($account) . '?' . $query;
    }

    public function base32Encode(string $bytes): string
    {
        if ($bytes === '') {
            return '';
        }

        $bits = '';
        foreach (str_split($bytes) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            $encoded .= self::ALPHABET[(int) bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $encoded;
    }

    /**
     * @throws InvalidArgumentException When the input is not valid base32.
     */
    public function base32Decode(string $encoded): string
    {
        $encoded = strtoupper(rtrim((string) preg_replace('/\s+/', '', $encoded), '='));

        if ($encoded === '') {
            return '';
        }

        $bits = '';
        foreach (str_split($encoded) as $char) {
            $position = strpos(self::ALPHABET, $char);

            if ($position === false) {
                throw new InvalidArgumentException('The value is not valid base32.');
            }

            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr((int) bindec($byte));
            }
        }

        return $bytes;
    }
}
