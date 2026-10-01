<?php

declare(strict_types=1);

namespace App\Core\Users\Http\Controllers;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

/**
 * Handles JSON:API user management requests.
 */
final class UserController extends JsonApiController
{
    public function created(User $user): void
    {
        $user->syncRoles($user->roles);
    }

    public function updated(User $user): void
    {
        if ($user->relationLoaded('roles')) {
            $user->syncRoles($user->getRelation('roles'));
        }
    }

    public function updatedRoles(User $user, iterable $roles): void
    {
        $user->syncRoles($roles);
    }

    public function deleting(User $user, ResourceRequest $request): void
    {
        if ($request->user()?->is($user)) {
            throw ValidationException::withMessages([
                'id' => ['You cannot delete your own account.'],
            ]);
        }
    }
}
