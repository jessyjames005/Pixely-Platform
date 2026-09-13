<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Services;

use App\Extensions\Tuleap\Contracts\TuleapRepositoryInterface;
use App\Extensions\Tuleap\Contracts\TuleapServiceInterface;
use App\Extensions\Tuleap\Exceptions\TuleapApiException;
use App\Extensions\Tuleap\Exceptions\TuleapUnavailableException;
use App\Extensions\Tuleap\Models\CafRecord;
use App\Extensions\Tuleap\Models\RetroAction;
use App\Extensions\Tuleap\Models\SprintConfig;
use App\Extensions\Tuleap\Models\TeamMember;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Orchestrates the Tuleap dashboard: proxies read calls to the remote
 * Tuleap REST API (server-side, token never exposed to the browser) and
 * delegates every local persistence concern to the Repository.
 *
 * Business rules (artifact type detection, "done" semantics, alerts,
 * capacity/predictability) mirror the original Node.js prototype
 * (backend/routes/tuleap.js) 1:1.
 */
final class TuleapService implements TuleapServiceInterface
{
    private const PROJECTS_CACHE_KEY = 'tuleap_projects';

    private const PROJECTS_CACHE_DAYS = 30;

    public function __construct(private readonly TuleapRepositoryInterface $repository) {}

    // ─── Connection helpers ─────────────────────────────────────────────

    private function getTuleapUrl(): ?string
    {
        return config('services.tuleap.url') ?? env('TULEAP_URL');
    }

    private function getTuleapToken(): ?string
    {
        return $this->repository->getAppConfig('tuleap_token');
    }

    private function getTuleapUserId(): ?string
    {
        return $this->repository->getAppConfig('tuleap_user_id');
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(): array
    {
        $token = $this->getTuleapToken();
        $userId = $this->getTuleapUserId();

        $headers = ['Content-Type' => 'application/json'];

        if ($token) {
            if (str_starts_with($token, 'tlp-k')) {
                $headers['X-Auth-AccessKey'] = $token;
            } else {
                $headers['X-Auth-Token'] = $token;
                if ($userId) {
                    $headers['X-Auth-UserId'] = $userId;
                }
            }
        }

        return $headers;
    }

    private function createHttpClient(): \Illuminate\Http\Client\PendingRequest
    {
        $url = $this->getTuleapUrl();

        return Http::baseUrl("{$url}/api/v1")
            ->withHeaders($this->buildHeaders())
            ->timeout(15)
            ->throw(false);
    }

    /**
     * GET against the Tuleap API. Throws TuleapUnavailableException on
     * network failure (VPN down, timeout) and TuleapApiException on a
     * non-2xx response, mirroring the original `tuleapGet()` helper.
     *
     * @param array<string, mixed> $params
     */
    private function tuleapRequest(string $path, array $params = []): Response
    {
        try {
            $response = $this->createHttpClient()->get($path, $params);
        } catch (ConnectionException $e) {
            throw new TuleapUnavailableException(previous: $e);
        }

        if (!$response->successful()) {
            $message = $response->json('error.message') ?? $response->reason() ?? 'Tuleap API error';
            throw new TuleapApiException((string) $message, $response->status());
        }

        return $response;
    }

    /**
     * Tuleap collection endpoints return either a raw array or an object
     * with a `collection` key depending on the endpoint. Normalize to a
     * plain list either way.
     *
     * @return array<int, mixed>
     */
    private function extractItems(mixed $data): array
    {
        if (!is_array($data)) {
            return [];
        }

        if (array_is_list($data)) {
            return $data;
        }

        return $data['collection'] ?? [];
    }

    // ─── Tuleap API Proxy ───────────────────────────────────────────────

    public function ping(): array
    {
        if (!$this->getTuleapUrl()) {
            return ['ok' => false, 'status' => 'not_configured', 'message' => 'TULEAP_URL non configuré'];
        }

        try {
            $response = $this->createHttpClient()->timeout(5)->get('/projects', ['limit' => 1, 'offset' => 0]);
        } catch (Throwable) {
            return ['ok' => false, 'status' => 'unreachable', 'message' => 'Tuleap inaccessible — vérifiez le VPN'];
        }

        if (!$response->successful()) {
            return ['ok' => false, 'status' => 'error', 'httpStatus' => $response->status()];
        }

        return ['ok' => true, 'status' => 'connected'];
    }

    public function getProjectsFromTuleap(): array
    {
        $cached = $this->repository->getCachedValue(self::PROJECTS_CACHE_KEY);
        if ($cached !== null) {
            return $cached;
        }

        $pageSize = 50;

        $first = $this->tuleapRequest('/projects', ['limit' => $pageSize, 'offset' => 0]);
        $total = (int) ($first->header('X-Pagination-Size') ?: 0);
        $allProjects = $this->extractItems($first->json());

        $offset = $pageSize;
        while ($offset < $total) {
            $page = $this->tuleapRequest('/projects', ['limit' => $pageSize, 'offset' => $offset]);
            $allProjects = array_merge($allProjects, $this->extractItems($page->json()));
            $offset += $pageSize;
        }

        usort($allProjects, function (array $a, array $b) {
            $aMember = $a['is_member_of'] ?? false;
            $bMember = $b['is_member_of'] ?? false;
            if ($aMember && !$bMember) {
                return -1;
            }
            if (!$aMember && $bMember) {
                return 1;
            }

            return strcasecmp($a['label'] ?? '', $b['label'] ?? '');
        });

        $this->repository->putCachedValue(
            self::PROJECTS_CACHE_KEY,
            $allProjects,
            now()->addDays(self::PROJECTS_CACHE_DAYS),
        );

        return $allProjects;
    }

    public function getProjectFromTuleap(int $projectId): array
    {
        return $this->tuleapRequest("/projects/{$projectId}")->json() ?? [];
    }

    public function getProjectMembersFromTuleap(int $projectId): array
    {
        // Members are derived from the assignees of the most recent sprint's
        // artifacts — Tuleap has no direct "project members" list endpoint
        // that reflects who is actually active on the team.
        $milestonesResponse = $this->tuleapRequest("/projects/{$projectId}/milestones", [
            'limit' => 1,
            'offset' => 0,
            'order' => 'desc',
        ]);
        $milestones = $this->extractItems($milestonesResponse->json());

        if (empty($milestones)) {
            return [];
        }

        $milestoneId = $milestones[0]['id'];
        $contentResponse = $this->tuleapRequest("/milestones/{$milestoneId}/content", ['limit' => 100, 'offset' => 0]);
        $items = $this->extractItems($contentResponse->json());

        $assignees = [];
        foreach ($items as $item) {
            $detail = $this->fetchArtifactDetailSafely((int) $item['id']);
            foreach (($detail['assignees'] ?? []) as $u) {
                $key = $u['username'] ?? $u['display_name'] ?? null;
                if ($key && !isset($assignees[$key])) {
                    $assignees[$key] = [
                        'id' => $u['id'] ?? null,
                        'display_name' => $u['display_name'] ?? $u['real_name'] ?? $u['username'] ?? '',
                        'username' => $u['username'] ?? '',
                    ];
                }
            }
        }

        return array_values($assignees);
    }

    public function getMilestonesFromTuleap(int $projectId): array
    {
        $response = $this->tuleapRequest("/projects/{$projectId}/milestones", [
            'limit' => 50,
            'offset' => 0,
            'order' => 'desc',
        ]);

        return $this->extractItems($response->json());
    }

    public function getMilestoneBurndown(int $milestoneId): array
    {
        $milestone = $this->tuleapRequest("/milestones/{$milestoneId}")->json() ?? [];
        $burndownData = $this->tuleapRequest("/milestones/{$milestoneId}/burndown")->json() ?? [];

        $capacity = (float) ($milestone['capacity'] ?? 0);
        $points = $burndownData['points_with_date'] ?? [];
        $startDate = isset($milestone['start_date']) ? substr((string) $milestone['start_date'], 0, 10) : null;
        $endDate = isset($milestone['end_date']) ? substr((string) $milestone['end_date'], 0, 10) : null;

        $now = Carbon::now();
        $todayStr = $now->format('Y-m-d');

        $actual = [];
        foreach ($points as $p) {
            if (empty($p['date'])) {
                continue;
            }
            $d = substr((string) $p['date'], 0, 10);
            if ($d > $todayStr) {
                continue;
            }
            $dow = Carbon::parse($p['date'])->dayOfWeek; // 0 = Sunday .. 6 = Saturday, same as JS getDay()
            if ($dow === 0 || $dow === 6) {
                continue;
            }
            $actual[] = ['date' => $d, 'remaining' => $p['remaining_effort'] ?? null];
        }

        $todayDow = $now->dayOfWeek;
        if (!empty($actual) && end($actual)['date'] !== $todayStr && $todayDow !== 0 && $todayDow !== 6) {
            $last = end($actual);
            $actual[] = ['date' => $todayStr, 'remaining' => $last['remaining']];
        }

        $idealDays = [];
        if ($startDate && $endDate) {
            $cur = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            while ($cur->lte($end)) {
                if (!$cur->isWeekend()) {
                    $idealDays[] = $cur->format('Y-m-d');
                }
                $cur->addDay();
            }
        }

        $n = max(count($idealDays) - 1, 1);
        $ideal = [];
        foreach ($idealDays as $i => $date) {
            $ideal[] = ['date' => $date, 'remaining' => max(0, $capacity - ($capacity / $n) * $i)];
        }

        return [
            'totalPoints' => $capacity,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'actual' => $actual,
            'ideal' => $ideal,
        ];
    }

    public function getMilestoneStats(int $milestoneId): array
    {
        $milestoneInfo = $this->tuleapRequest("/milestones/{$milestoneId}")->json() ?? [];
        $sprintStartDate = !empty($milestoneInfo['start_date']) ? Carbon::parse($milestoneInfo['start_date']) : null;

        $contentItems = $this->fetchAllContent($milestoneId);
        $enriched = $this->enrichArtifacts($contentItems);

        $childrenByParent = $this->groupChildrenByParent($contentItems);

        // Stories/defects with no realisation task in the sprint itself (or
        // only "analyse" children) may still have a linked _is_child task
        // living outside the sprint — fetch those too.
        $storiesWithoutChildren = array_filter($contentItems, function (array $item) use ($childrenByParent) {
            $type = $this->detectType($item['short_type'] ?? $this->getTrackerType($item), $item['label'] ?? '');
            if (!in_array($type, ['story', 'defect'], true)) {
                return false;
            }
            $sprintKids = $childrenByParent[$item['id']] ?? [];
            if (empty($sprintKids)) {
                return true;
            }
            foreach ($sprintKids as $c) {
                if ($this->detectType($c['short_type'] ?? $this->getTrackerType($c), $c['label'] ?? '') !== 'analyse') {
                    return false;
                }
            }

            return true;
        });

        $linkedChildrenByStory = [];
        foreach ($storiesWithoutChildren as $item) {
            try {
                $response = $this->tuleapRequest("/artifacts/{$item['id']}/linked_artifacts", [
                    'direction' => 'forward',
                    'nature' => '_is_child',
                    'limit' => 50,
                ]);
                $children = $this->extractItems($response->json());
                if (!empty($children)) {
                    $linkedChildrenByStory[$item['id']] = $children;
                }
            } catch (Throwable) {
                // No linked children available — continue without them.
            }
        }

        $linkedEnriched = [];
        $allLinkedChildren = empty($linkedChildrenByStory) ? [] : array_merge(...array_values($linkedChildrenByStory));
        $uniqLinkedIds = array_values(array_unique(array_filter(
            array_map(static fn (array $c) => $c['id'] ?? null, $allLinkedChildren),
            fn ($id) => $id !== null && !isset($enriched[$id]),
        )));
        foreach ($uniqLinkedIds as $childId) {
            $detail = $this->fetchArtifactDetailSafely((int) $childId);
            if ($detail !== null) {
                $linkedEnriched[$childId] = $detail;
            }
        }

        return $this->computeMilestoneStats(
            $contentItems,
            $enriched,
            $childrenByParent,
            $linkedChildrenByStory,
            $linkedEnriched,
            $sprintStartDate,
        );
    }

    public function getSprintHistory(int $projectId, string $range, bool $force): array
    {
        $rangeDays = match ($range) {
            '3m' => 92,
            '1y' => 366,
            default => 183, // '6m' and any unknown value
        };
        $cutoff = Carbon::now()->subDays($rangeDays);

        $limit = 50;
        $offset = 0;
        $milestones = [];
        while (true) {
            $response = $this->tuleapRequest("/projects/{$projectId}/milestones", [
                'limit' => $limit,
                'offset' => $offset,
                'order' => 'desc',
            ]);
            $items = $this->extractItems($response->json());
            $milestones = array_merge($milestones, $items);
            if (count($items) < $limit) {
                break;
            }
            $offset += $limit;

            $oldest = end($items);
            if ($oldest && !empty($oldest['start_date']) && Carbon::parse($oldest['start_date'])->lt(Carbon::now()->subDays(730))) {
                break;
            }
        }

        $filtered = array_values(array_filter($milestones, function (array $m) use ($cutoff) {
            if (empty($m['start_date'])) {
                return false;
            }
            $start = Carbon::parse($m['start_date']);

            return $start->gte($cutoff) && $start->lte(Carbon::now());
        }));

        $aggregates = [];
        foreach ($filtered as $milestone) {
            try {
                $aggregates[] = $this->getCachedSprintAggregate((int) $milestone['id'], $milestone, $force);
            } catch (Throwable) {
                // Skip sprints we fail to aggregate rather than fail the whole request.
                continue;
            }
        }

        $cafTotals = $this->computeCafTotalsPerSprint($projectId, $aggregates);

        $enrichedAgg = array_map(function (array $a) use ($cafTotals) {
            $cafTotal = $cafTotals[$a['id']] ?? 0;
            $capacity = $cafTotal > 0 ? round($a['donePoints'] / $cafTotal, 2) : null;
            $predictability = $a['totalPoints'] > 0 ? (int) round($a['donePoints'] / $a['totalPoints'] * 100) : null;
            $commitmentRespect = $a['commitmentPoints'] > 0
                ? (int) round($a['commitmentDone'] / $a['commitmentPoints'] * 100)
                : null;

            return array_merge($a, [
                'cafTotal' => $cafTotal,
                'capacity' => $capacity,
                'predictability' => $predictability,
                'commitmentRespect' => $commitmentRespect,
            ]);
        }, $aggregates);

        usort($enrichedAgg, static fn (array $a, array $b) => strcmp($a['start_date'] ?? '', $b['start_date'] ?? ''));

        return array_values($enrichedAgg);
    }

    // ─── Sprint aggregation helpers (used by getSprintHistory) ─────────

    /**
     * @param array<int, array<string, mixed>> $aggregates
     * @return array<int, float>
     */
    private function computeCafTotalsPerSprint(int $projectId, array $aggregates): array
    {
        $ids = array_map(static fn (array $a) => $a['id'], $aggregates);
        if (empty($ids)) {
            return [];
        }

        $cafBySprint = [];
        foreach (CafRecord::whereIn('sprint_id', $ids)->get(['sprint_id', 'member_id', 'value']) as $row) {
            $cafBySprint[$row->sprint_id][$row->member_id] = $row->value;
        }

        $workingDaysBySprint = SprintConfig::whereIn('id', $ids)->pluck('working_days', 'id');
        $projectMemberIds = TeamMember::where('project_id', $projectId)->pluck('id');

        $totals = [];
        foreach ($ids as $sprintId) {
            $cafMap = $cafBySprint[$sprintId] ?? [];
            $fallback = $workingDaysBySprint[$sprintId] ?? 10;

            if ($projectMemberIds->isNotEmpty()) {
                $total = 0.0;
                foreach ($projectMemberIds as $memberId) {
                    $total += $cafMap[$memberId] ?? $fallback;
                }
            } else {
                $total = array_sum($cafMap);
            }

            $totals[$sprintId] = $total;
        }

        return $totals;
    }

    private function getCachedSprintAggregate(int $milestoneId, array $milestoneFromList, bool $force): array
    {
        // v2: includes the `engagement` field — bump the key if the shape changes again.
        $cacheKey = "sprint_agg_v2_{$milestoneId}";

        if (!$force) {
            $cached = $this->repository->getCachedValue($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $milestoneInfo = $milestoneFromList;
        $contentItems = $this->fetchAllContent($milestoneId);
        $enriched = $this->enrichArtifacts($contentItems);

        $agg = $this->computeSprintAggregate($milestoneInfo, $contentItems, $enriched);

        $endDate = !empty($milestoneInfo['end_date']) ? Carbon::parse($milestoneInfo['end_date']) : null;
        $isPast = $endDate && $endDate->isPast();
        $ttl = $isPast ? now()->addDays(30) : now()->addHour();

        $this->repository->putCachedValue($cacheKey, $agg, $ttl);

        return $agg;
    }

    /**
     * @param array<int, array<string, mixed>> $contentItems
     * @param array<int, array<string, mixed>> $enriched
     */
    private function computeSprintAggregate(array $milestoneInfo, array $contentItems, array $enriched): array
    {
        $topLevelTypes = ['story', 'analyse', 'defect'];
        $childrenByParent = $this->groupChildrenByParent($contentItems);

        $totalPoints = 0;
        $donePoints = 0;
        $initialCommitment = 0;
        $commitmentPoints = 0;
        $commitmentDone = 0;
        $totalCount = 0;
        $doneCount = 0;
        $addedCount = 0;

        foreach ($contentItems as $item) {
            $type = $this->detectType($item['short_type'] ?? $this->getTrackerType($item), $item['label'] ?? '');
            if (!in_array($type, $topLevelTypes, true)) {
                continue;
            }

            $detail = $enriched[$item['id']] ?? null;
            $points = $item['initial_effort'] ?? 0;
            $isAdded = str_contains($item['label'] ?? '', '+') || str_contains($item['label'] ?? '', '➕');
            $remainingEffort = $this->findFieldValue($detail, ['Remaining Effort', 'Remaining effort']);

            $isDone = ($item['status'] ?? null) !== 'Open';
            if ($isDone && $remainingEffort !== null && $remainingEffort > 0) {
                $isDone = false;
            }
            if (!$isDone) {
                $children = $childrenByParent[$item['id']] ?? [];
                if (!empty($children) && $this->allClosed($children)) {
                    $isDone = true;
                }
            }

            $sprintPoints = $isDone ? $points : ($remainingEffort !== null ? $remainingEffort : $points);
            $dp = $isDone ? $sprintPoints : 0;

            $totalPoints += $sprintPoints;
            $donePoints += $dp;
            $totalCount++;
            if ($isDone) {
                $doneCount++;
            }
            if ($isAdded) {
                $addedCount++;
            }

            if (!$isAdded) {
                $initialCommitment += $points;
                $commitmentPoints += $sprintPoints;
                if ($isDone) {
                    $commitmentDone += $sprintPoints;
                }
            }
        }

        // `milestone.capacity` is locked when the sprint opens and does not
        // move when unfinished tasks are pushed to the next sprint — the
        // only stable denominator for commitment-respect.
        $engagement = (float) ($milestoneInfo['capacity'] ?? 0);

        return [
            'id' => (int) $milestoneInfo['id'],
            'label' => $milestoneInfo['label'] ?? null,
            'start_date' => $milestoneInfo['start_date'] ?? null,
            'end_date' => $milestoneInfo['end_date'] ?? null,
            'engagement' => $engagement,
            'totalPoints' => $totalPoints,
            'donePoints' => $donePoints,
            'initialCommitment' => $initialCommitment,
            'commitmentPoints' => $commitmentPoints,
            'commitmentDone' => $commitmentDone,
            'totalCount' => $totalCount,
            'doneCount' => $doneCount,
            'addedCount' => $addedCount,
        ];
    }

    // ─── Milestone stats (Dashboard) ────────────────────────────────────

    /**
     * @param array<int, array<string, mixed>> $contentItems
     * @param array<int, array<string, mixed>> $enriched
     * @param array<int, array<int, array<string, mixed>>> $childrenByParent
     * @param array<int, array<int, array<string, mixed>>> $linkedChildrenByStory
     * @param array<int, array<string, mixed>> $linkedEnriched
     */
    private function computeMilestoneStats(
        array $contentItems,
        array $enriched,
        array $childrenByParent,
        array $linkedChildrenByStory,
        array $linkedEnriched,
        ?Carbon $sprintStartDate,
    ): array {
        $now = Carbon::now();
        $topLevelTypes = ['story', 'analyse', 'defect'];

        $stats = [
            'total' => 0,
            'done' => 0,
            'devDone' => 0,
            'byType' => ['story' => 0, 'analyse' => 0, 'defect' => 0],
            'byTypeDone' => ['story' => 0, 'analyse' => 0, 'defect' => 0],
            'byPerson' => [],
            'alerts' => ['noPoints' => [], 'noAssignee' => [], 'stale' => [], 'noGitlab' => [], 'analyseOrpheline' => []],
            'artifacts' => [],
        ];

        foreach ($contentItems as $item) {
            $detail = $enriched[$item['id']] ?? null;
            $points = $item['initial_effort'] ?? 0;
            $type = $this->detectType($item['short_type'] ?? $this->getTrackerType($item), $item['label'] ?? '');

            [$assignees, $assigneeAvatars] = $this->extractAssignees($detail);

            $lastUpdate = $detail['last_modified_date'] ?? null;
            $isAdded = str_contains($item['label'] ?? '', '+') || str_contains($item['label'] ?? '', '➕');
            $remainingEffort = $this->findFieldValue($detail, ['Remaining Effort', 'Remaining effort']);

            $isDone = ($item['status'] ?? null) !== 'Open';
            if ($isDone && $remainingEffort !== null && $remainingEffort > 0) {
                $isDone = false;
            }
            if (!$isDone) {
                $children = $childrenByParent[$item['id']] ?? [];
                if (!empty($children) && $this->allClosed($children)) {
                    $isDone = true;
                }
            }

            $sprintPoints = $isDone ? $points : ($remainingEffort !== null ? $remainingEffort : $points);
            $donePoints = $isDone ? $sprintPoints : 0;

            if (in_array($type, $topLevelTypes, true)) {
                $stats['total'] += $sprintPoints;
                $stats['done'] += $donePoints;
                $stats['byType'][$type] = ($stats['byType'][$type] ?? 0) + $sprintPoints;
                $stats['byTypeDone'][$type] = ($stats['byTypeDone'][$type] ?? 0) + $donePoints;
            }

            $this->allocatePersonPoints(
                $stats['byPerson'],
                $type,
                $topLevelTypes,
                $assignees,
                $sprintPoints,
                $donePoints,
                $item,
                $childrenByParent,
                $linkedChildrenByStory,
                $enriched,
                $linkedEnriched,
            );

            $statusLabel = $this->findFieldSelectedLabel($detail, ['Status', 'Statut']) ?? '';
            $isNew = strtolower($statusLabel) === 'new';

            if (!$points && !$isDone && !$isNew && in_array($type, $topLevelTypes, true)) {
                $stats['alerts']['noPoints'][] = $this->alertEntry($item, $type, $assigneeAvatars);
            }

            if (in_array($type, $topLevelTypes, true) && preg_match('/verified|merge|ready|test/i', $statusLabel)) {
                $stats['devDone'] += $points;
            }

            if (empty($assignees) && !$isDone && !$isNew) {
                $childrenForAssignee = $type === 'story'
                    ? ($childrenByParent[$item['id']] ?? ($linkedChildrenByStory[$item['id']] ?? []))
                    : [];
                $childrenHaveAssignees = false;
                foreach ($childrenForAssignee as $child) {
                    $childDetail = $enriched[$child['id']] ?? ($linkedEnriched[$child['id']] ?? null);
                    if (!empty($childDetail['assignees'])) {
                        $childrenHaveAssignees = true;
                        break;
                    }
                }
                if (!$childrenHaveAssignees) {
                    $stats['alerts']['noAssignee'][] = $this->alertEntry($item, $type, []);
                }
            }

            // Stagnation counter: start from whichever is more recent between the
            // last update and the sprint start, so tasks modified before entering
            // the sprint don't immediately trigger the alert.
            $staleSinceDate = $lastUpdate
                ? (($sprintStartDate && Carbon::parse($lastUpdate)->lt($sprintStartDate)) ? $sprintStartDate : Carbon::parse($lastUpdate))
                : ($sprintStartDate ?? $now);
            $workingDaysSince = $this->workingDaysBetween($staleSinceDate, $now);

            if ($workingDaysSince >= 3 && !$isDone && !$isNew) {
                $allChildren = array_merge(
                    $childrenByParent[$item['id']] ?? [],
                    $linkedChildrenByStory[$item['id']] ?? [],
                );
                $hasRealisationTask = $type !== 'story' || $this->anyChildIsNotAnalysis($allChildren);
                if ($hasRealisationTask) {
                    $stats['alerts']['stale'][] = array_merge($this->alertEntry($item, $type, $assigneeAvatars), [
                        'lastUpdate' => $lastUpdate,
                        'daysSince' => $workingDaysSince,
                    ]);
                }
            }

            // Done without a GitLab link (branch or MR) — unless the status
            // says "without dev" or "cancelled". Analyses have no dev, so they
            // never trigger this alert.
            if ($isDone && ($type === 'story' || $type === 'defect')) {
                $isDoneWithoutDev = (bool) preg_match('/without\s*dev|sans\s*dev/i', $statusLabel);
                $isCancelled = (bool) preg_match('/cancel/i', $statusLabel);
                $isAppFine = (bool) preg_match('/\[AppFine\]/i', $item['label'] ?? '');

                if (!$isDoneWithoutDev && !$isCancelled && !$isAppFine) {
                    $children = array_merge(
                        $childrenByParent[$item['id']] ?? [],
                        $linkedChildrenByStory[$item['id']] ?? [],
                    );
                    $hasGitlabLink = $this->hasGitlabRefs($detail);
                    if (!$hasGitlabLink) {
                        foreach ($children as $c) {
                            $cDetail = $enriched[$c['id']] ?? ($linkedEnriched[$c['id']] ?? null);
                            if ($this->hasGitlabRefs($cDetail)) {
                                $hasGitlabLink = true;
                                break;
                            }
                        }
                    }
                    if (!$hasGitlabLink) {
                        $stats['alerts']['noGitlab'][] = $this->alertEntry($item, $type, $assigneeAvatars);
                    }
                }
            }

            // Story/defect with a completed analysis (🔍) but no realisation task.
            if ($type === 'story' || $type === 'defect') {
                if ($this->isAnalyseOrphanCandidate($item, $childrenByParent, $linkedChildrenByStory) && !$isNew) {
                    $stats['alerts']['analyseOrpheline'][] = $this->alertEntry($item, $type, $assigneeAvatars);
                }
            }

            $stats['artifacts'][] = [
                'id' => $item['id'],
                'title' => $item['label'] ?? null,
                'points' => $sprintPoints,
                'initialEffort' => $points,
                'donePoints' => $donePoints,
                'remainingEffort' => $remainingEffort,
                'status' => $item['status'] ?? null,
                'isDone' => $isDone,
                'trackerType' => $type,
                'assignees' => $assignees,
                'lastUpdate' => $lastUpdate,
                'isAddedDuringSprint' => $isAdded,
            ];
        }

        return $stats;
    }

    /**
     * @param array<string, mixed> $byPerson (passed by reference — mutated in place)
     * @param array<int, string> $assignees
     * @param array<string, mixed> $item
     */
    private function allocatePersonPoints(
        array &$byPerson,
        string $type,
        array $topLevelTypes,
        array $assignees,
        float|int $sprintPoints,
        float|int $donePoints,
        array $item,
        array $childrenByParent,
        array $linkedChildrenByStory,
        array $enriched,
        array $linkedEnriched,
    ): void {
        $personKeys = !empty($assignees) ? $assignees : ['Non attribué'];
        foreach ($personKeys as $person) {
            $byPerson[$person] ??= ['total' => 0, 'done' => 0];
        }

        if (!in_array($type, $topLevelTypes, true)) {
            return;
        }

        if (!empty($assignees)) {
            // Points split evenly between co-assignees so the sum of the
            // "per person" columns still equals the sprint total.
            $share = 1 / count($assignees);
            foreach ($assignees as $person) {
                $byPerson[$person]['total'] += $sprintPoints * $share;
                $byPerson[$person]['done'] += $donePoints * $share;
            }

            return;
        }

        if ($type !== 'story') {
            // Unassigned analysis or defect.
            $byPerson['Non attribué'] ??= ['total' => 0, 'done' => 0];
            $byPerson['Non attribué']['total'] += $sprintPoints;
            $byPerson['Non attribué']['done'] += $donePoints;

            return;
        }

        // Unassigned story: distribute points according to the sub-tasks' owners.
        $children = $childrenByParent[$item['id']] ?? ($linkedChildrenByStory[$item['id']] ?? []);
        $taskCount = count($children);

        if ($taskCount === 0) {
            $byPerson['Non attribué'] ??= ['total' => 0, 'done' => 0];
            $byPerson['Non attribué']['total'] += $sprintPoints;
            $byPerson['Non attribué']['done'] += $donePoints;

            return;
        }

        $pointsPerTask = $sprintPoints / $taskCount;
        $donePerTask = $donePoints / $taskCount;

        foreach ($children as $child) {
            $childDetail = $enriched[$child['id']] ?? ($linkedEnriched[$child['id']] ?? null);
            [$childPersons] = $this->extractAssignees($childDetail);
            $persons = !empty($childPersons) ? $childPersons : ['Non attribué'];
            $share = 1 / count($persons);
            foreach ($persons as $person) {
                $byPerson[$person] ??= ['total' => 0, 'done' => 0];
                $byPerson[$person]['total'] += $pointsPerTask * $share;
                $byPerson[$person]['done'] += $donePerTask * $share;
            }
        }
    }

    /**
     * @param array<string, mixed> $item
     * @param array<int, array<string, mixed>> $assigneeAvatars
     */
    private function alertEntry(array $item, string $type, array $assigneeAvatars): array
    {
        return [
            'id' => $item['id'],
            'title' => $item['label'] ?? null,
            'trackerType' => $type,
            'assignees' => $assigneeAvatars,
        ];
    }

    /**
     * @param array<string, mixed> $childrenByParent
     * @param array<string, mixed> $linkedChildrenByStory
     */
    private function isAnalyseOrphanCandidate(array $item, array $childrenByParent, array $linkedChildrenByStory): bool
    {
        $sprintChildren = $childrenByParent[$item['id']] ?? [];
        $linkedChildren = $linkedChildrenByStory[$item['id']] ?? [];

        $isAnalyseLabel = static fn (string $label) => str_contains($label, '🔍') || str_contains($label, '🔎');
        $isDoneChild = static fn (array $c) => ($c['status'] ?? '') !== 'Open';

        $hasDoneAnalyse = false;
        foreach ($sprintChildren as $c) {
            if ($this->detectType($c['short_type'] ?? $this->getTrackerType($c), $c['label'] ?? '') === 'analyse' && $isDoneChild($c)) {
                $hasDoneAnalyse = true;
                break;
            }
        }
        if (!$hasDoneAnalyse) {
            foreach ($linkedChildren as $c) {
                if ($isAnalyseLabel($c['title'] ?? '') && $isDoneChild($c)) {
                    $hasDoneAnalyse = true;
                    break;
                }
            }
        }

        if (!$hasDoneAnalyse) {
            return false;
        }

        $hasOtherTask = false;
        foreach ($sprintChildren as $c) {
            if ($this->detectType($c['short_type'] ?? $this->getTrackerType($c), $c['label'] ?? '') !== 'analyse') {
                $hasOtherTask = true;
                break;
            }
        }
        if (!$hasOtherTask) {
            foreach ($linkedChildren as $c) {
                if (!$isAnalyseLabel($c['title'] ?? '')) {
                    $hasOtherTask = true;
                    break;
                }
            }
        }

        return !$hasOtherTask;
    }

    private function hasGitlabRefs(?array $detail): bool
    {
        if (!$detail) {
            return false;
        }

        $refs = $detail['cross_references'] ?? [];
        foreach (($detail['values'] ?? []) as $v) {
            if (($v['type'] ?? null) === 'cross' || ($v['type'] ?? null) === 'art_link') {
                $vals = $v['value'] ?? $v['values'] ?? [];
                if (is_array($vals)) {
                    $refs = array_merge($refs, $vals);
                }
            }
        }

        foreach ($refs as $r) {
            $ref = $r['ref'] ?? $r['url'] ?? '';
            if (preg_match('/gitlab/i', (string) $ref)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, array<string, string>>}
     */
    private function extractAssignees(?array $detail): array
    {
        $names = [];
        $avatars = [];
        foreach (($detail['assignees'] ?? []) as $u) {
            $name = $u['display_name'] ?? $u['real_name'] ?? $u['username'] ?? null;
            if ($name) {
                $names[] = $name;
                $avatars[] = ['name' => $name];
            }
        }

        return [$names, $avatars];
    }

    /**
     * @param array<int, array<string, mixed>> $children
     */
    private function allClosed(array $children): bool
    {
        foreach ($children as $c) {
            if (($c['status'] ?? null) === 'Open') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $children
     */
    private function anyChildIsNotAnalysis(array $children): bool
    {
        foreach ($children as $c) {
            if (!str_contains($c['label'] ?? '', '🔍')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed>|null $detail
     * @param array<int, string> $labels
     */
    private function findFieldValue(?array $detail, array $labels): mixed
    {
        foreach (($detail['values'] ?? []) as $v) {
            if (in_array($v['label'] ?? null, $labels, true)) {
                return $v['value'] ?? null;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $detail
     * @param array<int, string> $labels
     */
    private function findFieldSelectedLabel(?array $detail, array $labels): ?string
    {
        foreach (($detail['values'] ?? []) as $v) {
            if (in_array($v['label'] ?? null, $labels, true)) {
                return $v['values'][0]['label'] ?? null;
            }
        }

        return null;
    }

    // ─── Shared fetch helpers ────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchAllContent(int $milestoneId): array
    {
        $limit = 100;
        $offset = 0;
        $items = [];
        while (true) {
            $response = $this->tuleapRequest("/milestones/{$milestoneId}/content", ['limit' => $limit, 'offset' => $offset]);
            $page = $this->extractItems($response->json());
            $items = array_merge($items, $page);
            if (count($page) < $limit) {
                break;
            }
            $offset += $limit;
        }

        return $items;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>> keyed by artifact id
     */
    private function enrichArtifacts(array $items): array
    {
        $enriched = [];
        foreach ($items as $item) {
            $detail = $this->fetchArtifactDetailSafely((int) $item['id']);
            if ($detail !== null) {
                $enriched[$item['id']] = $detail;
            }
        }

        return $enriched;
    }

    private function fetchArtifactDetailSafely(int $artifactId): ?array
    {
        try {
            return $this->tuleapRequest("/artifacts/{$artifactId}")->json();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<int, array<string, mixed>>> keyed by parent artifact id
     */
    private function groupChildrenByParent(array $items): array
    {
        $childrenByParent = [];
        foreach ($items as $item) {
            $parentId = $item['parent']['id'] ?? null;
            if ($parentId) {
                $childrenByParent[$parentId][] = $item;
            }
        }

        return $childrenByParent;
    }

    private function workingDaysBetween(Carbon $from, Carbon $to): int
    {
        $count = 0;
        $cur = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        while ($cur->lt($end)) {
            if (!$cur->isWeekend()) {
                $count++;
            }
            $cur->addDay();
        }

        return $count;
    }

    // ─── Artifact type detection ─────────────────────────────────────────

    private function detectType(?string $shortType, string $title): string
    {
        if (str_contains($title, '🔍') || str_contains($title, '🔎')) {
            return 'analyse';
        }

        return $this->normalizeType($shortType);
    }

    private function normalizeType(?string $shortType): string
    {
        if (!$shortType) {
            return 'story';
        }
        $t = strtolower($shortType);
        if (str_contains($t, 'story')) {
            return 'story';
        }
        if (str_contains($t, 'task')) {
            return 'task';
        }
        if (str_contains($t, 'defect') || str_contains($t, 'bug')) {
            return 'defect';
        }

        return 'story';
    }

    /**
     * @param array<string, mixed> $artifact
     */
    private function getTrackerType(array $artifact): string
    {
        $label = strtolower((string) ($artifact['artifact']['tracker']['label'] ?? $artifact['tracker']['label'] ?? ''));
        if (str_contains($label, 'story')) {
            return 'story';
        }
        if (str_contains($label, 'task')) {
            return 'task';
        }
        if (str_contains($label, 'defect') || str_contains($label, 'bug')) {
            return 'defect';
        }

        return 'story';
    }

    // ─── Local operations (thin delegation to the Repository) ──────────

    public function getMembers(?int $projectId): array
    {
        return $this->repository->getMembers($projectId);
    }

    public function addMember(array $data): TeamMember
    {
        return $this->repository->addMember($data);
    }

    public function deleteMember(int $id): void
    {
        $this->repository->deleteMember($id);
    }

    public function getSprintConfig(int $sprintId): ?SprintConfig
    {
        return $this->repository->getSprintConfig($sprintId);
    }

    public function saveSprintConfig(int $sprintId, array $data): SprintConfig
    {
        return $this->repository->saveSprintConfig($sprintId, $data);
    }

    public function getCaf(int $sprintId): array
    {
        return $this->repository->getCaf($sprintId);
    }

    public function saveCaf(int $sprintId, int $memberId, float $value): void
    {
        $this->repository->saveCaf($sprintId, $memberId, $value);
    }

    public function getCafHistory(array $sprintIds): array
    {
        return $this->repository->getCafHistory($sprintIds);
    }

    public function getBurndownCache(int $sprintId): array
    {
        return $this->repository->getBurndownCache($sprintId);
    }

    public function saveBurndownCache(int $sprintId, array $data): void
    {
        $this->repository->saveBurndownCache($sprintId, $data);
    }

    public function getAppConfig(string $key): ?string
    {
        return $this->repository->getAppConfig($key);
    }

    public function setAppConfig(string $key, string $value): void
    {
        $this->repository->setAppConfig($key, $value);
    }

    public function getRetroActions(int $sprintId): array
    {
        return $this->repository->getRetroActions($sprintId);
    }

    public function getRetroPlanActions(int $projectId): array
    {
        return $this->repository->getRetroPlanActions($projectId);
    }

    public function addRetroAction(array $data): RetroAction
    {
        return $this->repository->addRetroAction($data);
    }

    public function updateRetroAction(int $sprintId, int $id, array $data): ?RetroAction
    {
        return $this->repository->updateRetroAction($sprintId, $id, $data);
    }

    public function deleteRetroAction(int $sprintId, int $id): void
    {
        $this->repository->deleteRetroAction($sprintId, $id);
    }

    public function getCacheInfo(): array
    {
        return $this->repository->getCacheInfo();
    }

    public function clearCache(?string $key): void
    {
        $this->repository->clearCache($key);
    }
}
