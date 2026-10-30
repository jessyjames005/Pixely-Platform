<?php

declare(strict_types=1);

namespace App\Core\Updates\Services;

use App\Core\Updates\Contracts\InstalledExtensionInventoryInterface;
use App\Core\Updates\Contracts\UpdateRegistryInterface;
use App\Core\Updates\DTO\ExtensionUpdateAvailability;
use App\Core\Updates\DTO\InstalledExtension;
use App\Core\Updates\DTO\UpdateQuery;
use App\Core\Updates\Enums\UpdateChannel;
use App\Core\Updates\Enums\UpdateTargetType;

/**
 * Discovers newer releases for installed extensions through the official registry.
 *
 * This service is read-only: it never downloads artifacts or installs code.
 */
final class ExtensionUpdateDiscovery
{
    public function __construct(
        private readonly InstalledExtensionInventoryInterface $inventory,
        private readonly UpdateRegistryInterface $registry,
    ) {
    }

    /** @return list<ExtensionUpdateAvailability> */
    public function checkAll(
        UpdateChannel $channel = UpdateChannel::Stable,
        array $runtimeVersions = [],
    ): array {
        return array_map(
            fn ($extension) => $this->checkOne($extension, $channel, $runtimeVersions),
            $this->inventory->all(),
        );
    }

    public function check(string $extensionId, UpdateChannel $channel = UpdateChannel::Stable, array $runtimeVersions = []): ?ExtensionUpdateAvailability
    {
        $extension = $this->inventory->find($extensionId);

        return $extension === null
            ? null
            : $this->checkOne($extension, $channel, $runtimeVersions);
    }

    private function checkOne(InstalledExtension $extension, UpdateChannel $channel, array $runtimeVersions): ExtensionUpdateAvailability
    {
        $query = new UpdateQuery(
            type: UpdateTargetType::Extension,
            packageId: $extension->packageId,
            installedVersion: $extension->version,
            channel: $channel,
            runtimeVersions: $runtimeVersions,
        );

        $newer = [];

        foreach ($this->registry->findUpdates($query) as $candidate) {
            // Treat registry payloads as untrusted: only exact package/type/channel
            // matches and strictly newer semantic versions are eligible.
            if (
                $candidate->type !== UpdateTargetType::Extension
                || $candidate->packageId !== $extension->packageId
                || $candidate->channel !== $channel
                || version_compare($candidate->version, $extension->version, '<=')
            ) {
                continue;
            }

            $newer[] = $candidate;
        }

        usort($newer, static fn ($a, $b) => version_compare($b->version, $a->version));

        return new ExtensionUpdateAvailability(
            installed: $extension,
            candidate: $newer[0] ?? null,
            reason: $newer === [] ? 'No compatible newer release was returned by the official registry.' : null,
        );
    }
}
