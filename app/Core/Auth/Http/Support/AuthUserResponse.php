<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Support;

use App\JsonApi\V1\Users\UserActionResource;
use App\Models\User;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Responses\DataResponse;

/**
 * The authenticated-user payload shared by login, the two-factor challenge
 * and /auth/me — they must always return the same shape, otherwise the SPA's
 * permission-gated navigation stays empty until the next page reload.
 */
final class AuthUserResponse
{
    public static function make(Server $server, User $user): DataResponse
    {
        return DataResponse::make(new UserActionResource(
            $server->schemas()->schemaFor('users'),
            $user,
            [
                'name' => $user->name,
                'email' => $user->email,
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
                'roles' => $user->getRoleNames()->values(),
                'two_factor_enabled' => $user->hasEnabledTwoFactor(),
            ],
        ))->withServer('v1');
    }
}
