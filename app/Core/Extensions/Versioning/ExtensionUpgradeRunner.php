<?php

declare(strict_types=1);

namespace App\Core\Extensions\Versioning;

use App\Core\Extensions\Audit\ExtensionAuditLogger;
use App\Core\Extensions\Contracts\ExtensionInterface;
use Illuminate\Support\Facades\DB;

/**
 * Applies an extension's declared upgrade steps in order, between
 * the currently installed version and a target version.
 *
 * Each step is applied individually and the installed version is
 * persisted after every successful step — a failure partway through
 * does not roll back steps that already succeeded.
 */
final class ExtensionUpgradeRunner
{
    public function __construct(
        private readonly ExtensionVersionRepository $versionRepository,
        private readonly ExtensionAuditLogger $auditLogger,
    ) {
    }

    /**
     * Records a fresh install directly at its manifest version — a
     * new install is not an upgrade, there are no prior steps to run.
     */
    public function recordFreshInstall(string $extensionId, string $version): void
    {
        $this->versionRepository->set($extensionId, $version);
    }

    /**
     * Applies pending steps to bring the extension from its
     * currently installed version up to $targetVersion.
     *
     * @return array<int, string> descriptions of the steps that were applied
     * @throws \RuntimeException if a step fails partway through
     */
    public function upgrade(ExtensionInterface $extension, string $targetVersion): array
    {
        $extensionId = $extension->manifest()->id;
        $installedVersion = $this->versionRepository->find($extensionId) ?? '0.0.0';

        if (! $extension instanceof ExtensionUpgradableInterface) {
            // No declared steps: just move the version marker forward.
            $this->versionRepository->set($extensionId, $targetVersion);
            return [];
        }

        $pendingSteps = $this->pendingSteps($extension, $installedVersion, $targetVersion);
        $applied = [];

        foreach ($pendingSteps as $step) {
            try {
                DB::transaction(fn () => $step->apply());
            } catch (\Throwable $exception) {
                throw new \RuntimeException(
                    "Upgrade step to version [{$step->version()}] failed for extension [{$extensionId}]: {$exception->getMessage()}. "
                    . "Steps successfully applied before the failure remain in effect (installed version is now [{$this->versionRepository->find($extensionId)}]).",
                    previous: $exception,
                );
            }

            $this->versionRepository->set($extensionId, $step->version());
            $this->auditLogger->log($extensionId, 'upgrade_step', $step->version(), ['description' => $step->description()]);
            $applied[] = "{$step->version()}: {$step->description()}";
        }

        // Ensure the marker reflects the target version even if the
        // last declared step doesn't exactly match it (e.g. target
        // is a patch release with no dedicated step).
        if (version_compare($this->versionRepository->find($extensionId) ?? '0.0.0', $targetVersion, '<')) {
            $this->versionRepository->set($extensionId, $targetVersion);
        }

        return $applied;
    }

    /**
     * @return ExtensionUpgradeStepInterface[] sorted ascending, filtered to (installed, target]
     */
    private function pendingSteps(ExtensionUpgradableInterface $extension, string $installedVersion, string $targetVersion): array
    {
        $steps = $extension->upgradeSteps();

        usort($steps, fn (ExtensionUpgradeStepInterface $a, ExtensionUpgradeStepInterface $b) => version_compare($a->version(), $b->version()));

        return array_values(array_filter(
            $steps,
            fn (ExtensionUpgradeStepInterface $step) => version_compare($step->version(), $installedVersion, '>')
                && version_compare($step->version(), $targetVersion, '<='),
        ));
    }
}
