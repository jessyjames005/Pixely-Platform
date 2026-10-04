<?php

declare(strict_types=1);

namespace App\Extensions\Files\Http\Controllers\Api;

use App\Extensions\Files\Models\File;
use App\Extensions\Files\Services\FileUploadService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;

/**
 * Standalone Files API: a general-purpose upload/list/delete endpoint,
 * independent of any specific feature (unlike Gallery's photo upload or
 * the profile avatar upload, which each keep their own storage).
 */
#[Group('Files', weight: 2)]
final class FileController extends JsonApiController
{
    public function __construct(
        private readonly FileUploadService $fileUploadService,
    ) {
    }

    /**
     * Upload a new file.
     */
    public function upload(Request $request): DataResponse
    {
        $contentType = strtolower(trim(explode(';', (string) $request->header('Content-Type'), 2)[0]));

        if (in_array($contentType, ['application/json', 'application/vnd.api+json'], true)) {
            throw JsonApiException::error([
                'status' => 415,
                'code' => 'UNSUPPORTED_MEDIA_TYPE',
                'title' => 'Unsupported Media Type',
                'detail' => 'File uploads require multipart/form-data.',
            ]);
        }

        $validated = $request->validate([
            'file' => ['required', 'file'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $uploaded */
        $uploaded = $validated['file'];

        try {
            $result = $this->fileUploadService->upload($uploaded, 'files');
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['file' => [$exception->getMessage()]]);
        }

        $file = File::create([
            'disk' => 'public',
            'path' => $result['path'],
            'thumbnail_path' => $result['thumbnail_path'],
            'original_name' => $uploaded->getClientOriginalName(),
            'mime_type' => (string) $uploaded->getMimeType(),
            'size' => $uploaded->getSize(),
            'uploaded_by' => $request->user()?->id,
        ]);

        return DataResponse::make($file)->didCreate()->withServer('v1');
    }

    public function deleting(File $file, Request $request): void
    {
        $this->fileUploadService->delete($file->path, $file->thumbnail_path);
    }
}
