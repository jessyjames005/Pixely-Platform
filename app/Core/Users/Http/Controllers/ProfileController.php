<?php

declare(strict_types=1);

namespace App\Core\Users\Http\Controllers;

use App\Extensions\Files\Services\FileUploadService;
use App\JsonApi\V1\Users\UserActionResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Responses\DataResponse;

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
    public function show(Request $request, Server $server): DataResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->profileResponse($server, $user);
    }

    /**
     * Update the current user's own profile fields.
     */
    public function update(Request $request, Server $server): DataResponse
    {
        $contentType = strtolower(trim(explode(';', (string) $request->header('Content-Type'), 2)[0]));
        if ($contentType !== 'application/vnd.api+json') {
            throw JsonApiException::error([
                'status' => 415,
                'code' => 'UNSUPPORTED_MEDIA_TYPE',
                'title' => 'Unsupported Media Type',
                'detail' => 'Profile updates require the application/vnd.api+json media type.',
            ]);
        }

        /** @var User $user */
        $user = $request->user();
        $validated = $request->validate([
            'data' => ['required', 'array'],
            'data.type' => ['required', Rule::in(['users'])],
            'data.id' => ['sometimes', 'string', Rule::in([(string) $user->getKey()])],
            'data.attributes' => ['required', 'array:name,bio,timezone', 'min:1'],
            'data.attributes.name' => ['sometimes', 'string', 'max:255'],
            'data.attributes.bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'data.attributes.timezone' => ['sometimes', 'string', 'timezone'],
        ]);

        $user->update($validated['data']['attributes']);

        return $this->profileResponse($server, $user->refresh());
    }

    /**
     * Upload (or replace) the current user's own avatar.
     */
    public function uploadAvatar(Request $request, Server $server): DataResponse
    {
        $validated = $request->validate([
            'avatar' => ['required', 'file'],
        ]);

        /** @var User $user */
        $user = $request->user();

        try {
            $result = $this->fileUploadService->upload($validated['avatar'], 'avatars', generateThumbnail: false);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'avatar' => [$exception->getMessage()],
            ]);
        }

        if ($user->avatar_filename) {
            $this->fileUploadService->delete($user->avatar_filename);
        }

        $user->update(['avatar_filename' => $result['path']]);

        return $this->profileResponse($server, $user->refresh());
    }

    private function profileResponse(Server $server, User $user): DataResponse
    {
        return DataResponse::make(new UserActionResource(
            $server->schemas()->schemaFor('users'),
            $user,
            [
                'name' => $user->name,
                'email' => $user->email,
                'bio' => $user->bio,
                'timezone' => $user->timezone,
                'avatar_url' => $user->avatar_url,
            ],
        ))->withServer('v1');
    }
}
