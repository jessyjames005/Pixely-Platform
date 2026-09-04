<?php

declare(strict_types=1);

namespace App\Core\Users\Http\Controllers;

use App\Core\Api\Response\ApiCollectionResponse;
use App\Core\Api\Response\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Dedoc\Scramble\Attributes\Group;

/**
 * Handles Core user management API requests.
 */
#[Group('Users', weight: 3)]
final class UserController
{
    /**
     * Display a paginated list of users.
     */
    public function index(
        Request $request,
        ApiCollectionResponse $apiResponse,
    ): JsonResponse {
        $perPage = (int) $request->integer('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $paginator = User::query()
            ->with('roles')
            ->orderBy('name')
            ->paginate($perPage);

        return $apiResponse->response(
            data: $paginator->getCollection()->map(fn(User $user) => $this->withRole($user)),
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => max(1, $paginator->lastPage()),
                'per_page' => $perPage,
                'total' => $paginator->total(),
            ],
        );
    }

    /**
     * Create a new user.
     */
    public function store(
        Request $request,
        ApiResponse $apiResponse,
    ): JsonResponse {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return $apiResponse->response(
            data: $user,
            status: 201,
        );
    }

    /**
     * Display a single user.
     */
    public function show(
        User $user,
        ApiResponse $apiResponse,
    ): JsonResponse {
        $user->load('roles');

        return $apiResponse->response($this->withRole($user));
    }

    /**
     * Update a user's profile.
     *
     * The password is only updated when explicitly provided.
     */
    public function update(
        Request $request,
        User $user,
        ApiResponse $apiResponse,
    ): JsonResponse {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['sometimes', 'string', 'min:8'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return $apiResponse->response(
            data: $user->refresh(),
        );
    }

    /**
     * Delete a user.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()?->id === $user->id) {
            return response()->json(
                [
                    'error' => [
                        'code' => 'CANNOT_DELETE_SELF',
                        'message' => 'You cannot delete your own account.',
                    ],
                ],
                422,
            );
        }

        $user->delete();

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function withRole(User $user): array
    {
        return [
            ...$user->toArray(),
            'role' => $user->roles->first()?->name,
        ];
    }
}
