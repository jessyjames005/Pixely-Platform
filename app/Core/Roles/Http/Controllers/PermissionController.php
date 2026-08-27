<?php

declare(strict_types=1);

namespace App\Core\Roles\Http\Controllers;

use App\Core\Api\Response\ApiCollectionResponse;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

/**
 * Handles read-only access to the list of available permissions.
 *
 * Permissions are seeded, not created through the API, to keep
 * the permission set stable and predictable.
 */
final class PermissionController
{
    /**
     * Display all available permissions.
     */
    public function index(ApiCollectionResponse $apiResponse): JsonResponse
    {
        $permissions = Permission::orderBy('name')->get();

        return $apiResponse->response(
            data: $permissions,
            meta: ['total' => $permissions->count()],
        );
    }
}
