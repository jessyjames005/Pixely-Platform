<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Http\Controllers\Api;

use App\Extensions\Tuleap\Contracts\TuleapServiceInterface;
use App\Extensions\Tuleap\Exceptions\TuleapApiException;
use App\Extensions\Tuleap\Exceptions\TuleapUnavailableException;
use App\Extensions\Tuleap\Http\Support\TuleapJsonApiResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Responses\DataResponse;

final class TuleapController
{
    public function __construct(private TuleapServiceInterface $service)
    {
    }

    public function ping(Server $server): DataResponse
    {
        return TuleapJsonApiResponse::one($server, 'tuleap-ping-snapshots', $this->service->ping(), 'current');
    }

    public function getProjects(Server $server): DataResponse|JsonResponse
    {
        return $this->handle(
            $server,
            'tuleap-projects',
            fn () => $this->service->getProjectsFromTuleap(),
            true,
        );
    }

    public function getProject(int $projectId, Server $server): DataResponse|JsonResponse
    {
        return $this->handle(
            $server,
            'tuleap-projects',
            fn () => $this->service->getProjectFromTuleap($projectId),
            false,
            [$projectId],
        );
    }

    public function getProjectMembers(int $projectId, Server $server): DataResponse|JsonResponse
    {
        return $this->handle(
            $server,
            'tuleap-project-members',
            fn () => $this->service->getProjectMembersFromTuleap($projectId),
            true,
            [$projectId],
        );
    }

    public function getMilestones(int $projectId, Server $server): DataResponse|JsonResponse
    {
        return $this->handle(
            $server,
            'tuleap-milestones',
            fn () => $this->service->getMilestonesFromTuleap($projectId),
            true,
            [$projectId],
        );
    }

    public function getStats(int $milestoneId, Server $server): DataResponse|JsonResponse
    {
        return $this->handle(
            $server,
            'tuleap-milestone-stats',
            fn () => $this->service->getMilestoneStats($milestoneId),
            false,
            [$milestoneId],
        );
    }

    public function getBurndown(int $milestoneId, Server $server): DataResponse|JsonResponse
    {
        return $this->handle(
            $server,
            'tuleap-milestone-burndowns',
            fn () => $this->service->getMilestoneBurndown($milestoneId),
            false,
            [$milestoneId],
        );
    }

    public function getSprintHistory(int $projectId, Request $request, Server $server): DataResponse|JsonResponse
    {
        $range = (string) $request->query('range', '6m');
        $force = $request->boolean('force', false);

        return $this->handle(
            $server,
            'tuleap-sprint-history',
            fn () => $this->service->getSprintHistory($projectId, $range, $force),
            true,
            [$projectId],
        );
    }

    /**
     * Runs a proxy call and translates Tuleap-specific exceptions into
     * their own well-formed JSON response, regardless of how the
     * application's global exception handler is configured.
     */
    private function handle(Server $server, string $type, Closure $callback, bool $collection, array $context = []): DataResponse|JsonResponse
    {
        try {
            $result = $callback();

            return $collection
                ? TuleapJsonApiResponse::many($server, $type, $result, $context)
                : TuleapJsonApiResponse::one($server, $type, $result, $context[0], array_slice($context, 1));
        } catch (TuleapUnavailableException | TuleapApiException $e) {
            return $e->render();
        }
    }
}
