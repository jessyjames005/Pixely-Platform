<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Http\Controllers\Api;

use App\Extensions\Tuleap\Contracts\TuleapServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RetroController
{
    public function __construct(private TuleapServiceInterface $service) {}

    public function getActions(int $sprintId): JsonResponse
    {
        return response()->json($this->service->getRetroActions($sprintId));
    }

    public function getPlanActions(int $projectId): JsonResponse
    {
        return response()->json($this->service->getRetroPlanActions($projectId));
    }

    public function addAction(int $sprintId, Request $request): JsonResponse
    {
        $action = $this->service->addRetroAction(array_merge($request->all(), ['sprint_id' => $sprintId]));
        return response()->json($action, 201);
    }

    public function updateAction(int $sprintId, int $id, Request $request): JsonResponse
    {
        $action = $this->service->updateRetroAction($sprintId, $id, $request->all());
        return response()->json($action);
    }

    public function deleteAction(int $sprintId, int $id): JsonResponse
    {
        $this->service->deleteRetroAction($sprintId, $id);
        return response()->json(['ok' => true]);
    }
}