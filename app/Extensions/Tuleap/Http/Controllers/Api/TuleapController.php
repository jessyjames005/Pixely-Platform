<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Http\Controllers\Api;

use App\Extensions\Tuleap\Contracts\TuleapServiceInterface;
use Illuminate\Http\JsonResponse;

final class TuleapController
{
    public function __construct(private TuleapServiceInterface $service) {}

    public function ping(): JsonResponse
    {
        return response()->json($this->service->ping());
    }

    public function getProjects(): JsonResponse
    {
        return response()->json($this->service->getProjectsFromTuleap());
    }

    public function getProject(int $projectId): JsonResponse
    {
        return response()->json($this->service->getProjectFromTuleap($projectId));
    }

    public function getProjectMembers(int $projectId): JsonResponse
    {
        return response()->json($this->service->getProjectMembersFromTuleap($projectId));
    }

    public function getMilestones(int $projectId): JsonResponse
    {
        return response()->json($this->service->getMilestonesFromTuleap($projectId));
    }

    public function getStats(int $milestoneId): JsonResponse
    {
        return response()->json($this->service->getMilestoneStats($milestoneId));
    }

    public function getBurndown(int $milestoneId): JsonResponse
    {
        return response()->json($this->service->getMilestoneBurndown($milestoneId));
    }

    public function getSprintHistory(int $projectId): JsonResponse
    {
        $range = request()->query('range', '6m');
        $force = request()->boolean('force', false);
        return response()->json($this->service->getSprintHistory($projectId, $range, $force));
    }
}