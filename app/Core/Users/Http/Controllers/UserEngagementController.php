<?php

declare(strict_types=1);

namespace App\Core\Users\Http\Controllers;

use App\Core\Users\Services\UserEngagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Exposes generic User Space favorites and history endpoints.
 */
final class UserEngagementController
{
    public function __construct(private readonly UserEngagementService $service)
    {
    }

    /**
     * List the current user's favorites.
     */
    public function favorites(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json($this->service->favorites(
            $request->user()->id,
            $validated['resource_type'] ?? null,
            $validated['per_page'] ?? 20,
        ));
    }

    /**
     * Add a resource to the current user's favorites.
     */
    public function storeFavorite(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => ['required', 'string', 'max:100'],
            'resource_id' => ['required', 'string', 'max:191'],
            'metadata' => ['nullable', 'array'],
        ]);

        $favorite = $this->service->addFavorite(
            $request->user()->id,
            $validated['resource_type'],
            $validated['resource_id'],
            $validated['metadata'] ?? null,
        );

        return response()->json($favorite, 201);
    }

    /**
     * Remove a resource from the current user's favorites.
     */
    public function destroyFavorite(Request $request, string $resourceType, string $resourceId): JsonResponse
    {
        $removed = $this->service->removeFavorite($request->user()->id, $resourceType, $resourceId);

        return response()->json(['removed' => $removed]);
    }

    /**
     * List the current user's history.
     */
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json($this->service->history(
            $request->user()->id,
            $validated['resource_type'] ?? null,
            $validated['per_page'] ?? 20,
        ));
    }

    /**
     * Record a history entry for the current user.
     */
    public function storeHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => ['required', 'string', 'max:100'],
            'resource_id' => ['required', 'string', 'max:191'],
            'action' => ['nullable', 'string', 'max:50'],
            'metadata' => ['nullable', 'array'],
        ]);

        $entry = $this->service->recordHistory(
            $request->user()->id,
            $validated['resource_type'],
            $validated['resource_id'],
            $validated['action'] ?? 'view',
            $validated['metadata'] ?? null,
        );

        return response()->json($entry, 201);
    }
}
