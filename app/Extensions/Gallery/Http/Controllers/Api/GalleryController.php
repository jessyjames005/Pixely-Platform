<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Http\Controllers\Api;

use App\Extensions\Gallery\Models\Photo;
use App\Extensions\Files\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Core\Api\Query\ApiQueryParser;
use App\Core\Api\Query\ApiQueryApplier;
use App\Core\Api\Response\ApiResponse;
use App\Core\Api\Response\ApiCollectionResponse;
use Dedoc\Scramble\Attributes\Group;

/**
 * Handles Gallery API requests.
 *
 * Upload/delete delegate to the Files extension (a declared
 * dependency) for validation, storage, and thumbnail generation —
 * Gallery no longer touches Storage/UploadedFile directly.
 */
#[Group('Gallery', weight: 1)]
final class GalleryController
{
    public function __construct(
        private readonly FileUploadService $fileUploadService,
    ) {
    }

    /**
     * Display gallery photos.
     */
    public function index(
        Request $request,
        ApiQueryParser $queryParser,
        ApiQueryApplier $queryApplier,
        ApiCollectionResponse $apiResponse,
    ): JsonResponse {
        $apiQuery = $queryParser->parse($request->query());
        $query = Photo::query();
        $queryApplier->apply($query, $apiQuery);
        $total = $query->toBase()->getCountForPagination();
        $photos = $query->get();
        $perPage = $apiQuery->limit();
        $currentPage = $perPage > 0
            ? (int) floor($apiQuery->offset() / $perPage) + 1
            : 1;
        $lastPage = $perPage > 0
            ? max(1, (int) ceil($total / $perPage))
            : 1;

        return $apiResponse->response(
            data: $photos,
            meta: [
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
        );
    }

    /**
     * Store an uploaded gallery photo.
     */
    public function store(
        Request $request,
        ApiResponse $apiResponse,
    ): JsonResponse {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'image' => ['required', 'file'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $image */
        $image = $validated['image'];

        try {
            $result = $this->fileUploadService->upload($image, 'gallery');
        } catch (\InvalidArgumentException $exception) {
            return response()->json(
                [
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'The given data was invalid.',
                        'details' => ['image' => [$exception->getMessage()]],
                    ],
                ],
                422,
            );
        }

        $photo = Photo::create([
            'title' => $validated['title'] ?? null,
            'filename' => $result['path'],
            'thumbnail_filename' => $result['thumbnail_path'],
        ]);

        return $apiResponse->response(
            data: $photo,
            status: 201,
        );
    }

    /**
     * Display a single gallery photo.
     */
    public function show(
        Photo $photo,
        ApiResponse $apiResponse,
    ): JsonResponse {
        return $apiResponse->response($photo);
    }

    /**
     * Update a gallery photo.
     */
    public function update(
        Request $request,
        Photo $photo,
        ApiResponse $apiResponse,
    ): JsonResponse {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
        ]);

        $photo->update($validated);

        return $apiResponse->response(
            data: $photo->refresh(),
        );
    }

    /**
     * Delete a gallery photo.
     */
    public function destroy(Photo $photo): JsonResponse
    {
        $this->fileUploadService->delete($photo->filename, $photo->thumbnail_filename);
        $photo->delete();

        return response()->json(status: 204);
    }
}
