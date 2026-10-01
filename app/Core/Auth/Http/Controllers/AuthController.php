<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\JsonApi\V1\Users\UserActionResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Responses\DataResponse;

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
        Server $server,
    ): DataResponse {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw JsonApiException::error([
                'status' => 401,
                'code' => 'INVALID_CREDENTIALS',
                'title' => 'Unauthorized',
                'detail' => 'The provided credentials are incorrect.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();

            throw JsonApiException::error([
                'status' => 403,
                'code' => 'ACCOUNT_DISABLED',
                'title' => 'Forbidden',
                'detail' => 'This account has been disabled.',
            ]);
        }

        $request->session()->regenerate();

        return $this->userResponse($server, $user);
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

        return $this->userResponse($server, $user);
    }

    /**
     * Shared user payload for login() and me() — they must always
     * return the same shape. login() used to return the raw User
     * model instead (no permissions/roles at all), which meant every
     * permission-gated nav item stayed hidden until the user reloaded
     * the page and a subsequent me() call populated the auth store
     * properly.
     *
    * @return DataResponse
     */
    private function userResponse(Server $server, User $user): DataResponse
    {
        return DataResponse::make(new UserActionResource(
            $server->schemas()->schemaFor('users'),
            $user,
            [
                'name' => $user->name,
                'email' => $user->email,
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
                'roles' => $user->getRoleNames()->values(),
            ],
        ))->withServer('v1');
    }
}
