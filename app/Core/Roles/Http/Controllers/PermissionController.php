<?php

declare(strict_types=1);

namespace App\Core\Roles\Http\Controllers;

use Illuminate\Validation\ValidationException;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Spatie\Permission\Models\Permission;

/**
 * Handles JSON:API permission management requests.
 */
final class PermissionController extends JsonApiController
{
    public function created(Permission $permission): void
    {
        $permission->guard_name = 'web';
        $permission->save();
    }

    public function deleting(Permission $permission, ResourceRequest $request): void
    {
        if ($permission->is_core) {
            throw ValidationException::withMessages([
                'id' => ['Core permissions cannot be deleted.'],
            ]);
        }
    }
}
