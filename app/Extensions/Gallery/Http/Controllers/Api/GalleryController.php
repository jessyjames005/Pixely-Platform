<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Http\Controllers\Api;

use App\Extensions\Files\Services\FileUploadService;
use App\Extensions\Gallery\Models\Photo;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Exceptions\HttpNotAcceptableException;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

/**
 * Handles Gallery API requests.
 *
 * Upload/delete delegate to the Files extension (a declared
 * dependency) for validation, storage, and thumbnail generation —
 * Gallery no longer touches Storage/UploadedFile directly.
 */
#[Group('Gallery', weight: 1)]
final class GalleryController extends JsonApiController
{
    public function __construct(
        private readonly FileUploadService $fileUploadService,
    ) {
    }

    public function upload(Request $request): Responsable
    {
        if (! $request->accepts('application/vnd.api+json')) {
            throw new HttpNotAcceptableException();
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:1', 'max:255'],
            'image' => ['required', 'file'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $image */
        $image = $validated['image'];

        try {
            $result = $this->fileUploadService->upload($image, 'gallery');
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'image' => [$exception->getMessage()],
            ]);
        }

        $photo = Photo::create([
            'title' => $validated['title'] ?? null,
            'filename' => $result['path'],
            'thumbnail_filename' => $result['thumbnail_path'],
        ]);

        return DataResponse::make($photo)->didCreate();
    }

    public function deleting(Photo $photo, ResourceRequest $request): void
    {
        $this->fileUploadService->delete($photo->filename, $photo->thumbnail_filename);
    }
}
