<?php

declare(strict_types=1);

namespace App\Core\Roles\Http\Controllers;

use App\Core\Api\Response\ApiCollectionResponse;
use App\Core\Api\Response\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

/**
 * Handles CRUD for permissions.
 *
 * Core permissions are seeded and managed via RolePermissionSeeder.
 * Administrators can extend the set through the API.
 */
#[Group('Roles & Permissions', weight: 4)]
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

    /**
     * Create a new permission.
     */
    public function store(Request $request, ApiResponse $apiResponse): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'guard_name' => ['sometimes', 'string', 'max:255'],
            'is_core' => ['sometimes', 'boolean'],
        ]);

        /** @var Permission $permission */
        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'web',
            'is_core' => $validated['is_core'] ?? false,
        ]);

        return $apiResponse->response(
            data: $permission,
            status: 201,
        );
    }

    /**
     * Update an existing permission.
     */
    public function update(Request $request, Permission $permission, ApiResponse $apiResponse): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')->ignore($permission->id),
            ],
            'is_core' => ['sometimes', 'boolean'],
        ]);

        $permission->update($validated);

        return $apiResponse->response(
            data: $permission->fresh(),
        );
    }

    /**
     * Delete a permission.
     */
    public function destroy(Permission $permission): JsonResponse
    {
        if ($permission->is_core ?? false) {
            return response()->json([
                'error' => [
                    'code' => 'CANNOT_DELETE_CORE_PERMISSION',
                    'message' => 'Core permissions cannot be deleted.',
                ],
            ], 422);
        }

        $permission->delete();

        return response()->json(status: 204);
    }
}
