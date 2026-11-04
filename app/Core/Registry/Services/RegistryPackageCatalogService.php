<?php

declare(strict_types=1);

namespace App\Core\Registry\Services;

use App\Core\Registry\Exceptions\RegistryApiException;
use App\Core\Registry\Models\RegistryPackage;
use App\Core\Registry\Models\RegistryRelease;
use Composer\Semver\Comparator;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use JsonException;

/**
 * Searches the public package catalogue and summarizes its available releases.
 */
final class RegistryPackageCatalogService
{
    /**
     * @return array{
     *     packages: list<array{
     *         type: string,
     *         package_id: string,
     *         name: string,
     *         latest_release: array{version: string, channel: string, published_at: string}
     *     }>,
     *     per_page: int,
     *     next_cursor: string|null,
     *     has_more: bool
     * }
     */
    public function search(?string $query, ?string $packageType, int $perPage, ?string $cursor): array
    {
        $normalizedQuery = $query === null ? null : mb_strtolower($query);
        $filters = [
            'q' => $normalizedQuery,
            'package_type' => $packageType,
            'per_page' => $perPage,
        ];

        if ($cursor === null) {
            $snapshotPackageId = (int) (RegistryPackage::query()->max('id') ?? 0);
            $snapshotReleaseId = (int) (RegistryRelease::query()->max('id') ?? 0);
            $lastPackageId = 0;
        } else {
            $cursorData = $this->decodeCursor($cursor);
            if (($cursorData['filters'] ?? null) !== $filters) {
                throw RegistryApiException::invalidCursor();
            }

            $snapshotPackageId = $cursorData['snapshot_package_id'] ?? null;
            $snapshotReleaseId = $cursorData['snapshot_release_id'] ?? null;
            $lastPackageId = $cursorData['last_package_id'] ?? null;
            if (
                !is_int($snapshotPackageId)
                || !is_int($snapshotReleaseId)
                || !is_int($lastPackageId)
                || $snapshotPackageId < 1
                || $snapshotReleaseId < 1
                || $lastPackageId < 1
            ) {
                throw RegistryApiException::invalidCursor();
            }
        }

        if ($snapshotPackageId === 0 || $snapshotReleaseId === 0) {
            return $this->emptyPage($perPage);
        }

        $queryBuilder = RegistryPackage::query()
            ->where('id', '<=', $snapshotPackageId)
            ->where('id', '>', $lastPackageId)
            ->whereHas('releases', static fn ($releaseQuery) => $releaseQuery
                ->where('id', '<=', $snapshotReleaseId));

        if ($packageType !== null) {
            $queryBuilder->where('type', $packageType);
        }

        if ($normalizedQuery !== null) {
            $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $normalizedQuery) . '%';
            $queryBuilder->where(static function ($builder) use ($pattern): void {
                $builder->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("LOWER(package_id) LIKE ? ESCAPE '!'", [$pattern]);
            });
        }

        $candidatePackages = $queryBuilder
            ->orderBy('id')
            ->limit($perPage + 1)
            ->get();
        $hasMore = $candidatePackages->count() > $perPage;
        $pagePackages = $candidatePackages->take($perPage)->values();
        $summaries = $this->latestReleaseSummaries($pagePackages, $snapshotReleaseId);
        $packages = [];

        foreach ($pagePackages as $package) {
            $release = $summaries[$package->id] ?? null;
            if ($release === null) {
                continue;
            }

            $packages[] = [
                'type' => $package->type,
                'package_id' => $package->package_id,
                'name' => $package->name,
                'latest_release' => [
                    'version' => $release->version,
                    'channel' => $release->channel,
                    'published_at' => $release->published_at->toISOString(),
                ],
            ];
        }

        $nextCursor = null;
        if ($hasMore && $pagePackages->isNotEmpty()) {
            $nextCursor = $this->encodeCursor(
                $filters,
                $snapshotPackageId,
                $snapshotReleaseId,
                (int) $pagePackages->last()->id,
            );
        }

        return [
            'packages' => $packages,
            'per_page' => $perPage,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ];
    }

    /**
     * @param EloquentCollection<int, RegistryPackage> $packages
     * @return array<int, RegistryRelease>
     */
    private function latestReleaseSummaries(EloquentCollection $packages, int $snapshotReleaseId): array
    {
        if ($packages->isEmpty()) {
            return [];
        }

        $packageIds = $packages->modelKeys();
        $releases = RegistryRelease::query()
            ->whereIn('registry_package_id', $packageIds)
            ->where('id', '<=', $snapshotReleaseId)
            ->get()
            ->groupBy('registry_package_id');
        $summaries = [];

        foreach ($packages as $package) {
            /** @var Collection<int, RegistryRelease> $packageReleases */
            $packageReleases = $releases->get($package->id, collect());
            $stableReleases = $packageReleases->where('channel', 'stable');
            $candidates = $stableReleases->isNotEmpty() ? $stableReleases : $packageReleases;
            $latest = null;

            foreach ($candidates as $release) {
                if ($latest === null || $this->compareReleaseVersions($release, $latest) > 0) {
                    $latest = $release;
                }
            }

            if ($latest !== null) {
                $summaries[$package->id] = $latest;
            }
        }

        return $summaries;
    }

    private function compareReleaseVersions(RegistryRelease $left, RegistryRelease $right): int
    {
        if (Comparator::greaterThan($left->version, $right->version)) {
            return 1;
        }
        if (Comparator::lessThan($left->version, $right->version)) {
            return -1;
        }

        $versionTieBreak = strcmp($left->version, $right->version);

        return $versionTieBreak !== 0
            ? $versionTieBreak
            : ((int) $left->getKey() <=> (int) $right->getKey());
    }

    /**
     * @param array{q: string|null, package_type: string|null, per_page: int} $filters
     */
    private function encodeCursor(
        array $filters,
        int $snapshotPackageId,
        int $snapshotReleaseId,
        int $lastPackageId,
    ): string {
        try {
            $payload = json_encode([
                'filters' => $filters,
                'snapshot_package_id' => $snapshotPackageId,
                'snapshot_release_id' => $snapshotReleaseId,
                'last_package_id' => $lastPackageId,
            ], JSON_THROW_ON_ERROR);

            return Crypt::encryptString($payload);
        } catch (JsonException $exception) {
            throw new \LogicException('The Registry catalogue cursor could not be encoded.', previous: $exception);
        }
    }

    /** @return array<string, mixed> */
    private function decodeCursor(string $cursor): array
    {
        try {
            $data = json_decode(Crypt::decryptString($cursor), true, 16, JSON_THROW_ON_ERROR);
        } catch (DecryptException | JsonException) {
            throw RegistryApiException::invalidCursor();
        }

        if (!is_array($data)) {
            throw RegistryApiException::invalidCursor();
        }

        return $data;
    }

    /**
     * @return array{packages: list<never>, per_page: int, next_cursor: null, has_more: false}
     */
    private function emptyPage(int $perPage): array
    {
        return [
            'packages' => [],
            'per_page' => $perPage,
            'next_cursor' => null,
            'has_more' => false,
        ];
    }
}
