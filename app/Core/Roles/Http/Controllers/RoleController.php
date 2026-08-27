<?php

declare(strict_types=1);

namespace App\Core\Roles\Http\Controllers;

use App\Core\Api\Response\ApiCollectionResponse;
use App\Core\Api\Response\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Dedoc\Scramble\Attributes\Group;

/**
 * Handles Core role management API requests.
 */
#[Group('Roles & Permissions', weight: 4)]
final class RoleController
{
    /**
     * Display all roles with their permissions.
     */
    public function index(ApiCollectionResponse $apiResponse): JsonResponse
    {
        $roles = Role::with('permissions')->orderBy('name')->get();

        return $apiResponse->response(
            data: $roles,
            meta: ['total' => $roles->count()],
        );
    }

    /**
     * Create a new role.
     */
    public function store(Request $request, ApiResponse $apiResponse): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        $role->syncPermissions($validated['permissions'] ?? []);

        return $apiResponse->response(
            data: $role->load('permissions'),
            status: 201,
        );
    }

    /**
     * Update a role's permissions.
     */
    public function update(Request $request, Role $role, ApiResponse $apiResponse): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($role->id),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if (isset($validated['name'])) {
            $role->update(['name' => $validated['name']]);
        }

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return $apiResponse->response(
            data: $role->refresh()->load('permissions'),
        );
    }

    /**
     * Delete a role.
     */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->name === 'admin') {
            return response()->json(
                [
                    'error' => [
                        'code' => 'CANNOT_DELETE_ADMIN_ROLE',
                        'message' => 'The admin role cannot be deleted.',
                    ],
                ],
                422,
            );
        }

        $role->delete();

        return response()->json(status: 204);
    }

    /**
     * Assign a role to a user.
     */
    public function assign(Request $request, ApiResponse $apiResponse): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = \App\Models\User::findOrFail($validated['user_id']);
        $user->syncRoles([$validated['role']]);

        return $apiResponse->response(
            data: $user->refresh()->load('roles'),
        );
    }
}
