<?php

declare(strict_types=1);

namespace App\Core\Users\Http\Controllers;

use App\Core\Api\Response\ApiResponse;
use App\Extensions\Files\Services\FileUploadService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Self-service profile management: the current user's own name,
 * bio, timezone, and avatar. Distinct from UserController, which is
 * admin-only management of other users.
 */
#[Group('Users', weight: 3)]
final class ProfileController
{
    public function __construct(
        private readonly FileUploadService $fileUploadService,
    ) {
    }

    /**
     * Display the current user's own profile.
     */
    public function show(Request $request, ApiResponse $apiResponse): JsonResponse
    {
        return $apiResponse->response(data: $request->user());
    }

    /**
     * Update the current user's own profile fields.
     */
    public function update(Request $request, ApiResponse $apiResponse): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'timezone' => ['sometimes', 'string', 'timezone'],
        ]);

        $user = $request->user();
        $user->update($validated);

        return $apiResponse->response(data: $user->refresh());
    }

    /**
     * Upload (or replace) the current user's own avatar.
     */
    public function uploadAvatar(Request $request, ApiResponse $apiResponse): JsonResponse
    {
        $validated = $request->validate([
            'avatar' => ['required', 'file'],
        ]);

        $user = $request->user();

        try {
            $result = $this->fileUploadService->upload($validated['avatar'], 'avatars', generateThumbnail: false);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(
                [
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'The given data was invalid.',
                        'details' => ['avatar' => [$exception->getMessage()]],
                    ],
                ],
                422,
            );
        }

        if ($user->avatar_filename) {
            $this->fileUploadService->delete($user->avatar_filename);
        }

        $user->update(['avatar_filename' => $result['path']]);

        return $apiResponse->response(data: $user->refresh());
    }
}
