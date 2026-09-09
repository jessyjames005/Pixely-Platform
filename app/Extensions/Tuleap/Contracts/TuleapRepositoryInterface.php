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

interface TuleapRepositoryInterface
{
    // Projects
    public function getProjects(): array;
    public function saveProject(array $data): Project;
    public function getProject(int $projectId): ?Project;

    // Milestones
    public function getMilestones(int $projectId): array;
    public function saveMilestone(array $data): Milestone;

    // Team Members
    public function getMembers(?int $projectId): array;
    public function addMember(array $data): TeamMember;
    public function deleteMember(int $id): void;

    // Sprint Config
    public function getSprintConfig(int $sprintId): ?SprintConfig;
    public function saveSprintConfig(int $sprintId, array $data): SprintConfig;

    // CAF
    public function getCaf(int $sprintId): array;
    public function saveCaf(int $sprintId, int $memberId, float $value): void;
    public function getCafHistory(array $sprintIds): array;

    // Burndown
    public function getBurndownCache(int $sprintId): array;
    public function saveBurndownCache(int $sprintId, array $data): void;

    // App Config
    public function getAppConfig(string $key): ?string;
    public function setAppConfig(string $key, string $value): void;

    // Retrospective
    public function getRetroActions(int $sprintId): array;
    public function getRetroPlanActions(int $projectId): array;
    public function addRetroAction(array $data): RetroAction;
    public function updateRetroAction(int $sprintId, int $id, array $data): ?RetroAction;
    public function deleteRetroAction(int $sprintId, int $id): void;

    // Cache
    public function getCacheInfo(): array;
    public function clearCache(?string $key): void;
}