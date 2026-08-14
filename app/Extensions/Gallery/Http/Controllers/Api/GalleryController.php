<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Http\Controllers\Api;

use App\Extensions\Gallery\Models\Photo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Handles Gallery API requests.
 */
final class GalleryController
{
    /**
     * Display all gallery photos.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(
            max(
                (int) $request->input('per_page', 20),
                1,
            ),
            100,
        );

        $photos = Photo::query()
            ->paginate($perPage);

        return response()->json([
            'data' => $photos->items(),
            'meta' => [
                'current_page' => $photos->currentPage(),
                'last_page' => $photos->lastPage(),
                'per_page' => $photos->perPage(),
                'total' => $photos->total(),
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
