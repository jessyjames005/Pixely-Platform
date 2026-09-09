<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Contracts;

use App\Extensions\Tuleap\Models\TeamMember;
use App\Extensions\Tuleap\Models\SprintConfig;
use App\Extensions\Tuleap\Models\CafRecord;
use App\Extensions\Tuleap\Models\BurndownCache;
use App\Extensions\Tuleap\Models\AppConfig;
use App\Extensions\Tuleap\Models\RetroAction;
use App\Extensions\Tuleap\Models\Project;
use App\Extensions\Tuleap\Models\Milestone;

interface TuleapServiceInterface
{
    // Tuleap API Proxy
    public function ping(): array;
    public function getProjectsFromTuleap(): array;
    public function getProjectFromTuleap(int $projectId): array;
    public function getProjectMembersFromTuleap(int $projectId): array;
    public function getMilestonesFromTuleap(int $projectId): array;
    public function getMilestoneStats(int $milestoneId): array;
    public function getMilestoneBurndown(int $milestoneId): array;
    public function getSprintHistory(int $projectId, string $range, bool $force): array;

    // Local operations (via Repository)
    public function getMembers(?int $projectId): array;
    public function addMember(array $data): TeamMember;
    public function deleteMember(int $id): void;

    public function getSprintConfig(int $sprintId): ?SprintConfig;
    public function saveSprintConfig(int $sprintId, array $data): SprintConfig;

    public function getCaf(int $sprintId): array;
    public function saveCaf(int $sprintId, int $memberId, float $value): void;
    public function getCafHistory(array $sprintIds): array;

    public function getBurndownCache(int $sprintId): array;
    public function saveBurndownCache(int $sprintId, array $data): void;

    public function getAppConfig(string $key): ?string;
    public function setAppConfig(string $key, string $value): void;

    public function getRetroActions(int $sprintId): array;
    public function getRetroPlanActions(int $projectId): array;
    public function addRetroAction(array $data): RetroAction;
    public function updateRetroAction(int $sprintId, int $id, array $data): ?RetroAction;
    public function deleteRetroAction(int $sprintId, int $id): void;

    public function getCacheInfo(): array;
    public function clearCache(?string $key): void;
}