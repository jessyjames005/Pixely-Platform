<?php

declare(strict_types=1);

namespace App\Core\Roles\Http\Controllers;

use Illuminate\Validation\ValidationException;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Spatie\Permission\Models\Role;

/**
 * Handles JSON:API role management requests.
 */
final class RoleController extends JsonApiController
{
    public function created(Role $role): void
    {
        $role->guard_name = 'web';
        $role->save();
        $role->syncPermissions($role->permissions);
    }

    public function updated(Role $role): void
    {
        if ($role->relationLoaded('permissions')) {
            $role->syncPermissions($role->getRelation('permissions'));
        }
    }

    public function updatedPermissions(Role $role, iterable $permissions): void
    {
        $role->syncPermissions($permissions);
    }

    public function deleting(Role $role, ResourceRequest $request): void
    {
        if ($role->name === 'admin') {
            throw ValidationException::withMessages([
                'id' => ['The admin role cannot be deleted.'],
            ]);
        }
    }
}
