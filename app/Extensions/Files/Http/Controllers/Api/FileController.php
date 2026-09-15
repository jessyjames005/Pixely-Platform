<?php

declare(strict_types=1);

namespace App\Extensions\Files\Http\Controllers\Api;

use App\Core\Api\Query\ApiQueryApplier;
use App\Core\Api\Query\ApiQueryParser;
use App\Core\Api\Response\ApiCollectionResponse;
use App\Core\Api\Response\ApiResponse;
use App\Extensions\Files\Models\File;
use App\Extensions\Files\Services\FileUploadService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Standalone Files API: a general-purpose upload/list/delete endpoint,
 * independent of any specific feature (unlike Gallery's photo upload or
 * the profile avatar upload, which each keep their own storage).
 */
#[Group('Files', weight: 2)]
final class FileController
{
    public function __construct(
        private readonly FileUploadService $fileUploadService,
    ) {
    }

    /**
     * List uploaded files.
     */
    public function index(
        Request $request,
        ApiQueryParser $queryParser,
        ApiQueryApplier $queryApplier,
        ApiCollectionResponse $apiResponse,
    ): JsonResponse {
        $apiQuery = $queryParser->parse($request->query());
        $query = File::query();
        $queryApplier->apply($query, $apiQuery);
        $total = $query->toBase()->getCountForPagination();
        $files = $query->latest()->get();
        $perPage = $apiQuery->limit();
        $currentPage = $perPage > 0
            ? (int) floor($apiQuery->offset() / $perPage) + 1
            : 1;
        $lastPage = $perPage > 0
            ? max(1, (int) ceil($total / $perPage))
            : 1;

        return $apiResponse->response(
            data: $files,
            meta: [
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
        );
    }

    /**
     * Upload a new file.
     */
    public function store(Request $request, ApiResponse $apiResponse): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $uploaded */
        $uploaded = $validated['file'];

        try {
            $result = $this->fileUploadService->upload($uploaded, 'files');
        } catch (\InvalidArgumentException $exception) {
            return response()->json(
                [
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'The given data was invalid.',
                        'details' => ['file' => [$exception->getMessage()]],
                    ],
                ],
                422,
            );
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

        return $apiResponse->response(data: $file, status: 201);
    }

    /**
     * Display a single file's details.
     */
    public function show(File $file, ApiResponse $apiResponse): JsonResponse
    {
        return $apiResponse->response($file);
    }

    /**
     * Delete a file.
     */
    public function destroy(File $file): JsonResponse
    {
        $this->fileUploadService->delete($file->path, $file->thumbnail_path);
        $file->delete();

        return response()->json(status: 204);
    }
}
