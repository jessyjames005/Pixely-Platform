<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Http\Controllers\Api;

use App\Extensions\Gallery\Models\Photo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Core\Api\Query\ApiQueryParser;
use App\Core\Api\Query\ApiQueryApplier;
use OpenApi\Attributes as OA;

/**
 * Handles Gallery API requests.
 */
final class GalleryController
{
    /**
     * Display gallery photos.
     */
    #[OA\Get(
        path: '/api/v1/gallery',
        operationId: 'listGalleryPhotos',
        summary: 'List gallery photos',
        tags: ['Gallery'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number.',
                schema: new OA\Schema(
                    type: 'integer',
                    minimum: 1,
                    default: 1,
                ),
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Number of photos per page. Maximum 100.',
                schema: new OA\Schema(
                    type: 'integer',
                    minimum: 1,
                    maximum: 100,
                    default: 20,
                ),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated gallery photos.',
            ),
        ],
    )]
    public function index(
        Request $request,
        ApiQueryParser $queryParser,
        ApiQueryApplier $queryApplier,
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
            ? (int) ceil($total / $perPage)
            : 1;

        return response()->json([
            'data' => $photos,
            'meta' => [
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Store an uploaded gallery photo.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'image' => ['required', 'image'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $image */
        $image = $validated['image'];

        $filename = $image->store(
            'gallery',
            'public',
        );

        $photo = Photo::create([
            'title' => $validated['title'] ?? null,
            'filename' => $filename,
        ]);

        return response()->json([
            'data' => $photo,
        ], 201);
    }

    /**
     * Display a single gallery photo.
     */
    #[OA\Get(
        path: '/api/v1/gallery/{photo}',
        operationId: 'getGalleryPhoto',
        summary: 'Get a gallery photo',
        tags: ['Gallery'],
        parameters: [
            new OA\Parameter(
                name: 'photo',
                in: 'path',
                required: true,
                description: 'Photo identifier.',
                schema: new OA\Schema(
                    type: 'integer',
                    example: 1,
                ),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Gallery photo.',
            ),
            new OA\Response(
                response: 404,
                description: 'Photo not found.',
            ),
        ],
    )]
    public function show(Photo $photo): JsonResponse
    {
        return response()->json([
            'data' => $photo,
        ]);
    }

    /**
     * Update a gallery photo.
     */
    public function update(
        Request $request,
        Photo $photo,
    ): JsonResponse {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
        ]);

        $photo->update($validated);

        return response()->json([
            'data' => $photo->refresh(),
        ]);
    }

    /**
     * Delete a gallery photo.
     */
    public function destroy(Photo $photo): JsonResponse
    {
        Storage::disk('public')->delete($photo->filename);

        $photo->delete();

        return response()->json(
            status: 204,
        );
    }
}
