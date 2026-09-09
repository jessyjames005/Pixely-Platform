<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Http\Controllers\Api;

use App\Extensions\Tuleap\Contracts\TuleapServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SprintController
{
    public function __construct(private TuleapServiceInterface $service) {}

    public function getConfig(int $sprintId): JsonResponse
    {
        $config = $this->service->getSprintConfig($sprintId);
        if (!$config) {
            return response()->json([
                'sprint_id' => $sprintId,
                'objective' => '',
                'confidence_index' => null,
                'pct_evolution' => 50,
                'pct_analysis' => 30,
                'pct_bug' => 20,
                'working_days' => 10,
                'velocity_per_day' => 1.0,
                'review_comment' => '',
            ]);
        }
        return response()->json($config);
    }

    public function saveConfig(int $sprintId, Request $request): JsonResponse
    {
        $config = $this->service->saveSprintConfig($sprintId, $request->all());
        return response()->json($config);
    }

    public function getCaf(int $sprintId): JsonResponse
    {
        return response()->json($this->service->getCaf($sprintId));
    }

    public function saveCaf(int $sprintId, int $memberId, Request $request): JsonResponse
    {
        $this->service->saveCaf($sprintId, $memberId, $request->float('value'));
        return response()->json(['ok' => true]);
    }

    public function getCafHistory(): JsonResponse
    {
        $sprintIds = explode(',', request()->query('sprint_ids', ''));
        $ids = array_filter(array_map('intval', $sprintIds));
        return response()->json($this->service->getCafHistory($ids));
    }

    public function getBurndown(int $sprintId): JsonResponse
    {
        return response()->json($this->service->getBurndownCache($sprintId));
    }

    public function saveBurndown(int $sprintId, Request $request): JsonResponse
    {
        $this->service->saveBurndownCache($sprintId, $request->all());
        return response()->json(['ok' => true]);
    }
}