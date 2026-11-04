<?php

declare(strict_types=1);

namespace App\Core\Registry\Services;

use App\Core\Registry\Exceptions\RegistryApiException;
use App\Core\Registry\Models\RegistryPackage;
use App\Core\Registry\Models\RegistryRelease;
use Composer\Semver\Comparator;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use JsonException;

/**
 * Reads immutable Registry releases without changing or re-verifying manifests.
 */
final class RegistryReleaseReadService
{
    /**
     * @return array{
     *     releases: list<RegistryRelease>,
     *     per_page: int,
     *     next_cursor: string|null,
     *     has_more: bool
     * }
     */
    public function list(
        string $packageType,
        string $packageId,
        string $channel,
        ?string $afterVersion,
        int $perPage,
        array $runtimeFilters,
        ?string $cursor,
    ): array {
        $package = $this->findPackage($packageType, $packageId);

        if ($package === null) {
            throw RegistryApiException::packageNotFound();
        }

        $filters = [
            'package_type' => $packageType,
            'package_id' => $packageId,
            'channel' => $channel,
            'after_version' => $afterVersion,
            'per_page' => $perPage,
            'runtime' => $runtimeFilters,
        ];
        $boundaryVersion = '';
        $boundaryId = 0;
        $hasBoundary = $cursor !== null;

        if ($cursor === null) {
            $snapshotId = $package->releases()->max('id');
        } else {
            $cursorData = $this->decodeCursor($cursor);
            if (($cursorData['filters'] ?? null) !== $filters) {
                throw RegistryApiException::invalidCursor();
            }

            $snapshotId = $cursorData['snapshot_id'] ?? null;
            $boundaryVersion = $cursorData['last_version'] ?? '';
            $boundaryId = $cursorData['last_id'] ?? 0;

            if (
                !is_int($snapshotId)
                || !is_string($boundaryVersion)
                || !is_int($boundaryId)
                || $snapshotId < 1
                || $boundaryId < 1
            ) {
                throw RegistryApiException::invalidCursor();
            }
        }

        if (!is_int($snapshotId)) {
            return [
                'releases' => [],
                'per_page' => $perPage,
                'next_cursor' => null,
                'has_more' => false,
            ];
        }

        $releases = $package->releases()
            ->where('channel', $channel)
            ->where('id', '<=', $snapshotId)
            ->get()
            ->filter(function (RegistryRelease $release) use (
                $afterVersion,
                $boundaryVersion,
                $boundaryId,
                $hasBoundary,
            ): bool {
                if ($afterVersion !== null && !Comparator::greaterThan($release->version, $afterVersion)) {
                    return false;
                }

                if (!$hasBoundary) {
                    return true;
                }

                return $this->compareOrder(
                    $release->version,
                    (int) $release->getKey(),
                    $boundaryVersion,
                    $boundaryId,
                ) > 0;
            })
            ->all();

        usort(
            $releases,
            fn (RegistryRelease $left, RegistryRelease $right): int => $this->compareOrder(
                $left->version,
                (int) $left->getKey(),
                $right->version,
                (int) $right->getKey(),
            ),
        );

        $hasMore = count($releases) > $perPage;
        $page = array_slice($releases, 0, $perPage);
        $nextCursor = null;

        if ($hasMore) {
            $lastRelease = $page[array_key_last($page)];
            $nextCursor = $this->encodeCursor($filters, $snapshotId, $lastRelease);
        }

        return [
            'releases' => $page,
            'per_page' => $perPage,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ];
    }

    public function resolve(string $packageType, string $packageId, string $version): RegistryRelease
    {
        $package = $this->findPackage($packageType, $packageId);

        if ($package === null) {
            throw RegistryApiException::packageNotFound();
        }

        $releaseQuery = $package->releases();
        if (DB::connection()->getDriverName() === 'mysql') {
            $releaseQuery->whereRaw('BINARY version = ?', [$version]);
        } else {
            $releaseQuery->where('version', $version);
        }

        $release = $releaseQuery->first();

        if ($release === null) {
            throw RegistryApiException::releaseNotFound();
        }

        return $release;
    }

    private function findPackage(string $packageType, string $packageId): ?RegistryPackage
    {
        $packageQuery = RegistryPackage::query()->where('type', $packageType);

        if (DB::connection()->getDriverName() === 'mysql') {
            $packageQuery->whereRaw('BINARY package_id = ?', [$packageId]);
        } else {
            $packageQuery->where('package_id', $packageId);
        }

        return $packageQuery->first();
    }

    /**
     * A negative result means the left release precedes the right release.
     */
    private function compareOrder(string $leftVersion, int $leftId, string $rightVersion, int $rightId): int
    {
        if (Comparator::greaterThan($leftVersion, $rightVersion)) {
            return -1;
        }

        if (Comparator::lessThan($leftVersion, $rightVersion)) {
            return 1;
        }

        $versionTieBreak = strcmp($rightVersion, $leftVersion);

        return $versionTieBreak !== 0 ? $versionTieBreak : $rightId <=> $leftId;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function encodeCursor(array $filters, int $snapshotId, RegistryRelease $lastRelease): string
    {
        try {
            $payload = json_encode([
                'filters' => $filters,
                'snapshot_id' => $snapshotId,
                'last_version' => $lastRelease->version,
                'last_id' => (int) $lastRelease->getKey(),
            ], JSON_THROW_ON_ERROR);

            return Crypt::encryptString($payload);
        } catch (JsonException $exception) {
            throw new \LogicException('The Registry pagination cursor could not be encoded.', previous: $exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeCursor(string $cursor): array
    {
        try {
            $payload = Crypt::decryptString($cursor);
            $data = json_decode($payload, true, 16, JSON_THROW_ON_ERROR);
        } catch (DecryptException | JsonException $exception) {
            throw RegistryApiException::invalidCursor();
        }

        if (!is_array($data)) {
            throw RegistryApiException::invalidCursor();
        }

        return $data;
    }
}
