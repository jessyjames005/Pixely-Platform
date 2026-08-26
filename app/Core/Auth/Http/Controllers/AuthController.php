<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Api\Error\ApiError;
use App\Core\Api\Error\ApiErrorResponse;
use App\Core\Api\Response\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Handles session-based authentication for the administration SPA.
 */
final class AuthController
{
    /**
     * Authenticate a user and start a session.
     */
    public function login(
        Request $request,
        ApiResponse $apiResponse,
        ApiErrorResponse $apiErrorResponse,
    ): JsonResponse {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return $apiErrorResponse->response(
                new ApiError(
                    code: 'INVALID_CREDENTIALS',
                    message: 'The provided credentials are incorrect.',
                ),
                401,
            );
        }

        $request->session()->regenerate();

        return $apiResponse->response(
            data: $request->user(),
        );
    }

    /**
     * Log the current user out and invalidate the session.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(status: 204);
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(Request $request, ApiResponse $apiResponse): JsonResponse
    {
        return $apiResponse->response(
            data: $request->user(),
        );
    }
}
