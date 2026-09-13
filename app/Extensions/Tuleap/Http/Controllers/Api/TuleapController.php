<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Http\Controllers\Api;

use App\Extensions\Tuleap\Contracts\TuleapServiceInterface;
use App\Extensions\Tuleap\Exceptions\TuleapApiException;
use App\Extensions\Tuleap\Exceptions\TuleapUnavailableException;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TuleapController
{
    public function __construct(private TuleapServiceInterface $service) {}

    public function ping(): JsonResponse
    {
        return response()->json($this->service->ping());
    }

    public function getProjects(): JsonResponse
    {
        return $this->handle(fn () => $this->service->getProjectsFromTuleap());
    }

    public function getProject(int $projectId): JsonResponse
    {
        return $this->handle(fn () => $this->service->getProjectFromTuleap($projectId));
    }

    public function getProjectMembers(int $projectId): JsonResponse
    {
        return $this->handle(fn () => $this->service->getProjectMembersFromTuleap($projectId));
    }

    public function getMilestones(int $projectId): JsonResponse
    {
        return $this->handle(fn () => $this->service->getMilestonesFromTuleap($projectId));
    }

    public function getStats(int $milestoneId): JsonResponse
    {
        return $this->handle(fn () => $this->service->getMilestoneStats($milestoneId));
    }

    public function getBurndown(int $milestoneId): JsonResponse
    {
        return $this->handle(fn () => $this->service->getMilestoneBurndown($milestoneId));
    }

    public function getSprintHistory(int $projectId, Request $request): JsonResponse
    {
        $range = (string) $request->query('range', '6m');
        $force = $request->boolean('force', false);

        return $this->handle(fn () => $this->service->getSprintHistory($projectId, $range, $force));
    }

    /**
     * Runs a proxy call and translates Tuleap-specific exceptions into
     * their own well-formed JSON response, regardless of how the
     * application's global exception handler is configured.
     */
    private function handle(Closure $callback): JsonResponse
    {
        try {
            return response()->json($callback());
        } catch (TuleapUnavailableException|TuleapApiException $e) {
            return $e->render();
        }
    }
}
