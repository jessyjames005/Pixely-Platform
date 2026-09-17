<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Api\Error\ApiError;
use App\Core\Api\Error\ApiErrorResponse;
use App\Core\Api\Response\ApiResponse;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles session-based authentication for the administration SPA.
 */
#[Group('Authentication', weight: 2)]
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

        /** @var User $user */
        $user = Auth::user();
        if ($user->is_active) {
            Auth::logout();

            return $apiErrorResponse->response(
                new ApiError(
                    code: 'ACCOUNT_DISABLED',
                    message: 'This account has been disabled.',
                ),
                403,
            );
        }

        $request->session()->regenerate();

        return $apiResponse->response(
            data: $this->serializeUser($user),
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
            data: $this->serializeUser($request->user()),
        );
    }

    /**
     * Shared user payload for login() and me() — they must always
     * return the same shape. login() used to return the raw User
     * model instead (no permissions/roles at all), which meant every
     * permission-gated nav item stayed hidden until the user reloaded
     * the page and a subsequent me() call populated the auth store
     * properly.
     *
     * @return array<string, mixed>
     */
    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            'roles' => $user->getRoleNames()->values(),
        ];
    }
}
