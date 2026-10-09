<?php

declare(strict_types=1);

namespace App\Core\Users\Http\Controllers;

use App\Core\Users\Http\Requests\StoreFavoriteRequest;
use App\Core\Users\Http\Requests\StoreHistoryRequest;
use App\Core\Users\Services\UserEngagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** HTTP boundary for user-owned favorites and history. */
final class UserEngagementController
{
    public function __construct(private readonly UserEngagementService $service)
    {
    }

    public function favorites(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, Response::HTTP_UNAUTHORIZED);

        return response()->json($this->service->favorites(
            $user,
            $request->string('resource_type')->toString() ?: null,
        ));
    }

    public function storeFavorite(StoreFavoriteRequest $request): JsonResponse
    {
        $favorite = $this->service->addFavorite(
            $request->user(),
            $request->string('resource_type')->toString(),
            $request->string('resource_id')->toString(),
            $request->input('metadata'),
        );

        return response()->json($favorite, Response::HTTP_CREATED);
    }

    public function destroyFavorite(Request $request, string $resourceType, string $resourceId): Response
    {
        $this->service->removeFavorite($request->user(), $resourceType, $resourceId);

        return response()->noContent();
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, Response::HTTP_UNAUTHORIZED);

        return response()->json($this->service->history(
            $user,
            $request->string('resource_type')->toString() ?: null,
        ));
    }

    public function storeHistory(StoreHistoryRequest $request): JsonResponse
    {
        $entry = $this->service->recordHistory(
            $request->user(),
            $request->string('resource_type')->toString(),
            $request->string('resource_id')->toString(),
            $request->string('action')->toString(),
            $request->input('metadata'),
        );

        return response()->json($entry, Response::HTTP_CREATED);
    }
}
