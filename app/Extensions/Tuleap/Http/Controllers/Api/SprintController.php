<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Http\Controllers\Api;

use App\Extensions\Tuleap\Contracts\TuleapServiceInterface;
use App\Extensions\Tuleap\Http\Support\TuleapDocumentRequest;
use App\Extensions\Tuleap\Http\Support\TuleapJsonApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Responses\DataResponse;

/**
 * Sprint configuration and CAF (capacity) management for Tuleap sprints.
 */
#[Group('Tuleap', weight: 10)]
final class SprintController
{
    public function __construct(private TuleapServiceInterface $service)
    {
    }

    /**
     * Get a sprint's configuration (objective, confidence index, capacity distribution).
     */
    public function getConfig(int $sprintId, Server $server): DataResponse
    {
        $config = $this->service->getSprintConfig($sprintId);
        if (!$config) {
            $config = [
                'objective' => '',
                'confidence_index' => null,
                'pct_evolution' => 50,
                'pct_analysis' => 30,
                'pct_bug' => 20,
                'working_days' => 10,
                'velocity_per_day' => 1.0,
                'review_comment' => '',
            ];
        }

        return TuleapJsonApiResponse::one($server, 'tuleap-sprint-configs', $config, $sprintId);
    }

    /**
     * Update a sprint's configuration (objective, CAF distribution, velocity).
     */
    public function saveConfig(int $sprintId, Request $request, Server $server): DataResponse
    {
        $attributes = TuleapDocumentRequest::attributes(
            $request,
            'tuleap-sprint-configs',
            [
                'objective', 'confidence_index', 'pct_evolution', 'pct_analysis', 'pct_bug',
                'working_days', 'velocity_per_day', 'review_comment',
            ],
        );
        $config = $this->service->saveSprintConfig($sprintId, $attributes);

        return TuleapJsonApiResponse::one($server, 'tuleap-sprint-configs', $config, $sprintId);
    }

    /**
     * List CAF (capacity) records for a sprint.
     */
    public function getCaf(int $sprintId, Server $server): DataResponse
    {
        return TuleapJsonApiResponse::many(
            $server,
            'tuleap-caf-records',
            $this->service->getCaf($sprintId),
            [$sprintId],
        );
    }

    /**
     * Update a team member's CAF capacity value for a sprint.
     */
    public function saveCaf(int $sprintId, int $memberId, Request $request, Server $server): DataResponse
    {
        $attributes = TuleapDocumentRequest::attributes(
            $request,
            'tuleap-caf-records',
            ['value'],
            ['value' => ['required', 'numeric', 'min:0']],
        );
        $value = (float) $attributes['value'];
        $this->service->saveCaf($sprintId, $memberId, $value);

        return TuleapJsonApiResponse::one($server, 'tuleap-caf-records', [
            'sprint_id' => $sprintId,
            'member_id' => $memberId,
            'value' => $value,
        ], "{$sprintId}:{$memberId}");
    }

    /**
     * List CAF capacity history across sprints (for trend analysis).
     */
    public function getCafHistory(Server $server): DataResponse
    {
        $sprintIds = explode(',', request()->query('sprint_ids', ''));
        $ids = array_filter(array_map('intval', $sprintIds));
        return TuleapJsonApiResponse::many($server, 'tuleap-caf-history', $this->service->getCafHistory($ids));
    }

    /**
     * Get the cached burndown points for a sprint.
     */
    public function getBurndown(int $sprintId, Server $server): DataResponse
    {
        $points = [];
        foreach ($this->service->getBurndownCache($sprintId) as $row) {
            $row = is_object($row) ? get_object_vars($row) : $row;
            if (isset($row['day'], $row['remaining_points'])) {
                $points[(string) $row['day']] = (float) $row['remaining_points'];
            }
        }

        return TuleapJsonApiResponse::one($server, 'tuleap-burndown-caches', [
            'sprint_id' => $sprintId,
            'points' => $points,
        ], $sprintId);
    }

    /**
     * Update the burndown points for a sprint.
     */
    public function saveBurndown(int $sprintId, Request $request, Server $server): DataResponse
    {
        $attributes = TuleapDocumentRequest::attributes(
            $request,
            'tuleap-burndown-caches',
            ['points'],
            ['points' => ['required', 'array']],
        );
        $this->service->saveBurndownCache($sprintId, $attributes['points']);

        return TuleapJsonApiResponse::one($server, 'tuleap-burndown-caches', [
            'sprint_id' => $sprintId,
            'points' => $attributes['points'],
        ], $sprintId);
    }
}
