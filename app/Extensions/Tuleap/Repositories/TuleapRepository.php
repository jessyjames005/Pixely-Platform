<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Repositories;

use App\Extensions\Tuleap\Contracts\TuleapRepositoryInterface;
use App\Extensions\Tuleap\Models\TeamMember;
use App\Extensions\Tuleap\Models\SprintConfig;
use App\Extensions\Tuleap\Models\CafRecord;
use App\Extensions\Tuleap\Models\BurndownCache;
use App\Extensions\Tuleap\Models\AppConfig;
use App\Extensions\Tuleap\Models\RetroAction;
use App\Extensions\Tuleap\Models\Project;
use App\Extensions\Tuleap\Models\Milestone;
use Illuminate\Support\Facades\DB;

final class TuleapRepository implements TuleapRepositoryInterface
{
    public function getProjects(): array
    {
        return Project::orderBy('name')->get()->toArray();
    }

    public function saveProject(array $data): Project
    {
        return Project::updateOrCreate(
            ['project_id' => $data['project_id']],
            $data
        );
    }

    public function getProject(int $projectId): ?Project
    {
        return Project::find($projectId);
    }

    public function getMilestones(int $projectId): array
    {
        return Milestone::where('project_id', $projectId)
            ->orderByDesc('start_date')
            ->get()
            ->toArray();
    }

    public function saveMilestone(array $data): Milestone
    {
        return Milestone::updateOrCreate(
            ['milestone_id' => $data['milestone_id']],
            $data
        );
    }

    public function getMembers(?int $projectId): array
    {
        $query = TeamMember::orderBy('name');
        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        return $query->get()->toArray();
    }

    public function addMember(array $data): TeamMember
    {
        return TeamMember::create($data);
    }

    public function deleteMember(int $id): void
    {
        TeamMember::destroy($id);
    }

    public function getSprintConfig(int $sprintId): ?SprintConfig
    {
        return SprintConfig::find($sprintId);
    }

    public function saveSprintConfig(int $sprintId, array $data): SprintConfig
    {
        return SprintConfig::updateOrCreate(
            ['sprint_id' => $sprintId],
            array_merge($data, ['updated_at' => now()])
        );
    }

    public function getCaf(int $sprintId): array
    {
        return CafRecord::where('sprint_id', $sprintId)
            ->with('member')
            ->get()
            ->toArray();
    }

    public function saveCaf(int $sprintId, int $memberId, float $value): void
    {
        CafRecord::updateOrCreate(
            ['sprint_id' => $sprintId, 'member_id' => $memberId],
            ['value' => $value]
        );
    }

    public function getCafHistory(array $sprintIds): array
    {
        return DB::table('tuleap_caf_records as c')
            ->join('tuleap_team_members as m', 'm.id', '=', 'c.member_id')
            ->whereIn('c.sprint_id', $sprintIds)
            ->select('c.sprint_id', 'c.member_id', 'c.value', 'm.name')
            ->get()
            ->toArray();
    }

    public function getBurndownCache(int $sprintId): array
    {
        return BurndownCache::where('sprint_id', $sprintId)
            ->orderBy('day')
            ->get()
            ->toArray();
    }

    public function saveBurndownCache(int $sprintId, array $data): void
    {
        foreach ($data as $day => $points) {
            BurndownCache::updateOrCreate(
                ['sprint_id' => $sprintId, 'day' => $day],
                ['remaining_points' => $points]
            );
        }
    }

    public function getAppConfig(string $key): ?string
    {
        $row = AppConfig::find($key);
        return $row?->value;
    }

    public function setAppConfig(string $key, string $value): void
    {
        AppConfig::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public function getRetroActions(int $sprintId): array
    {
        return RetroAction::where('sprint_id', $sprintId)
            ->orderBy('category')
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }

    public function getRetroPlanActions(int $projectId): array
    {
        return RetroAction::where('project_id', $projectId)
            ->where('category', 'plan_action')
            ->orderByDesc('sprint_id')
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }

    public function addRetroAction(array $data): RetroAction
    {
        return RetroAction::create($data);
    }

    public function updateRetroAction(int $sprintId, int $id, array $data): ?RetroAction
    {
        $action = RetroAction::where('sprint_id', $sprintId)->find($id);
        if ($action) {
            $action->update($data);
        }
        return $action;
    }

    public function deleteRetroAction(int $sprintId, int $id): void
    {
        RetroAction::where('sprint_id', $sprintId)->where('id', $id)->delete();
    }

    public function getCacheInfo(): array
    {
        $rows = DB::table('tuleap_cache')
            ->orderByDesc('cached_at')
            ->get()
            ->toArray();

        return array_map(function ($r) {
            return [
                'key' => $r->key,
                'cached_at' => $r->cached_at,
                'expires_at' => $r->expires_at,
                'expired' => new \DateTime($r->expires_at) < new \DateTime(),
            ];
        }, $rows);
    }

    public function clearCache(?string $key): void
    {
        if ($key) {
            DB::table('tuleap_cache')->where('key', $key)->delete();
        } else {
            DB::table('tuleap_cache')->truncate();
        }
    }
}