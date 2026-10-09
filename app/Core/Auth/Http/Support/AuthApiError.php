<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Support;

use LaravelJsonApi\Core\Exceptions\JsonApiException;

/**
 * Factory for the JSON:API errors shared by the authentication controllers.
 *
 * Codes are machine-readable and stable: the frontend maps them to
 * translated messages, so never reword a code.
 */
final class AuthApiError
{
    public static function invalidCredentials(): JsonApiException
    {
        return JsonApiException::error([
            'status' => 401,
            'code' => 'INVALID_CREDENTIALS',
            'title' => 'Unauthorized',
            'detail' => 'The provided credentials are incorrect.',
        ]);
    }

    public static function accountDisabled(): JsonApiException
    {
        return JsonApiException::error([
            'status' => 403,
            'code' => 'ACCOUNT_DISABLED',
            'title' => 'Forbidden',
            'detail' => 'This account has been disabled.',
        ]);
    }

    public static function tooManyAttempts(int $retryAfter): JsonApiException
    {
        return JsonApiException::error([
            'status' => 429,
            'code' => 'TOO_MANY_ATTEMPTS',
            'title' => 'Too Many Requests',
            'detail' => sprintf('Too many failed attempts. Try again in %d seconds.', $retryAfter),
            'meta' => ['retry_after' => $retryAfter],
        ]);
    }

    public static function invalidTwoFactorCode(): JsonApiException
    {
        return JsonApiException::error([
            'status' => 422,
            'code' => 'INVALID_TWO_FACTOR_CODE',
            'title' => 'Unprocessable Entity',
            'detail' => 'The authentication code is invalid or has already been used.',
        ]);
    }

    public static function twoFactorSessionExpired(): JsonApiException
    {
        return JsonApiException::error([
            'status' => 401,
            'code' => 'TWO_FACTOR_SESSION_EXPIRED',
            'title' => 'Unauthorized',
            'detail' => 'The sign-in session expired. Sign in again.',
        ]);
    }

    public static function twoFactorAlreadyEnabled(): JsonApiException
    {
        return JsonApiException::error([
            'status' => 409,
            'code' => 'TWO_FACTOR_ALREADY_ENABLED',
            'title' => 'Conflict',
            'detail' => 'Two-factor authentication is already enabled.',
        ]);
    }

    public static function twoFactorNotEnabled(): JsonApiException
    {
        return JsonApiException::error([
            'status' => 409,
            'code' => 'TWO_FACTOR_NOT_ENABLED',
            'title' => 'Conflict',
            'detail' => 'Two-factor authentication is not enabled.',
        ]);
    }

    public static function invalidPassword(string $field = 'password'): JsonApiException
    {
        return JsonApiException::error([
            'status' => 422,
            'code' => 'INVALID_PASSWORD',
            'title' => 'Unprocessable Entity',
            'detail' => 'The provided password is incorrect.',
            'source' => ['pointer' => '/data/attributes/' . $field],
        ]);
    }

    public static function invalidResetToken(): JsonApiException
    {
        return JsonApiException::error([
            'status' => 422,
            'code' => 'INVALID_RESET_TOKEN',
            'title' => 'Unprocessable Entity',
            'detail' => 'This password reset link is invalid or has expired.',
        ]);
    }
}
