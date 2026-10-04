<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Http\Controllers\Api;

use App\Extensions\Tuleap\Contracts\TuleapServiceInterface;
use App\Extensions\Tuleap\Exceptions\TuleapApiException;
use App\Extensions\Tuleap\Exceptions\TuleapUnavailableException;
use App\Extensions\Tuleap\Http\Support\TuleapJsonApiResponse;
use Closure;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Responses\DataResponse;

/**
 * Proxies a team's Tuleap instance (projects, milestones, burndown, sprint
 * history) and translates Tuleap-specific exceptions into JSON:API error
 * documents.
 */
#[Group('Tuleap', weight: 10)]
final class TuleapController
{
    public function __construct(private TuleapServiceInterface $service)
    {
    }

    /**
     * Check connectivity to the configured Tuleap instance.
     */
    public function ping(Server $server): DataResponse
    {
        return TuleapJsonApiResponse::one($server, 'tuleap-ping-snapshots', $this->service->ping(), 'current');
    }

    /**
     * List Tuleap projects visible to the configured token (cached).
     */
    public function getProjects(Server $server): DataResponse|JsonResponse
    {
        return $this->handle(
            $server,
            'tuleap-projects',
            fn () => $this->service->getProjectsFromTuleap(),
            true,
        );
    }

    /**
     * Get a single Tuleap project by its Tuleap identifier.
     */
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

    /**
     * List members of a Tuleap project.
     */
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

    /**
     * List milestones of a Tuleap project.
     */
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

    /**
     * Get quality/alert stats for a Tuleap milestone (stale, no points, etc.).
     */
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

    /**
     * Get the burndown series for a Tuleap milestone.
     */
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

    /**
     * Get sprint history (predictability, commitment, capacity) for a project.
     */
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
