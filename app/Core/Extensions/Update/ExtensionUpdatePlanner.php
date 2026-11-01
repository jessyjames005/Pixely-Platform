<?php

declare(strict_types=1);

namespace App\Core\Extensions\Update;

use App\Core\Extensions\Manifest\ExtensionManifest;

/**
 * Builds a deterministic update plan without changing installed files or data.
 *
 * The planner validates dependency presence, minimum versions and declared
 * conflicts before an installer is allowed to touch the live extension tree.
 */
final class ExtensionUpdatePlanner
{
    /**
     * @param array<string, string> $installedVersions Installed extension versions keyed by id.
     * @param array<string, string> $requiredVersions Minimum required versions keyed by dependency id.
     * @param string[] $conflicts Extension ids that cannot coexist with the candidate.
     * @param array<string, string[]> $installedDependencies Dependency graph for installed extensions.
     * @return array{extension: string, from: string, to: string, dependencies: array<string, string>, actions: array<int, string>}
     */
    public function plan(
        ExtensionManifest $candidate,
        array $installedVersions,
        array $requiredVersions = [],
        array $conflicts = [],
        array $installedDependencies = [],
    ): array {
        $from = $installedVersions[$candidate->id] ?? null;

        if ($from === null) {
            throw new \RuntimeException("Extension [{$candidate->id}] is not installed; an update plan cannot be created.");
        }

        if (version_compare($candidate->version, $from, '<=')) {
            throw new \RuntimeException("Candidate version [{$candidate->version}] must be newer than installed version [{$from}].");
        }

        $dependencies = [];
        foreach ($candidate->dependencies as $dependencyId) {
            $installed = $installedVersions[$dependencyId] ?? null;
            if ($installed === null) {
                throw new \RuntimeException("Extension [{$candidate->id}] requires missing extension [{$dependencyId}].");
            }

            $minimum = $requiredVersions[$dependencyId] ?? null;
            if ($minimum !== null && version_compare($installed, $minimum, '<')) {
                throw new \RuntimeException("Extension [{$candidate->id}] requires [{$dependencyId}] version [{$minimum}] or newer; [{$installed}] is installed.");
            }
            $dependencies[$dependencyId] = $installed;
        }

        foreach ($conflicts as $conflictId) {
            if (isset($installedVersions[$conflictId])) {
                throw new \RuntimeException("Extension [{$candidate->id}] conflicts with installed extension [{$conflictId}].");
            }
        }

        $graph = $installedDependencies;
        $graph[$candidate->id] = $candidate->dependencies;
        $this->assertAcyclic($candidate->id, $graph, []);

        return [
            'extension' => $candidate->id,
            'from' => $from,
            'to' => $candidate->version,
            'dependencies' => $dependencies,
            'actions' => [
                'Validate package and manifest',
                'Stage package in an isolated directory',
                'Back up the current extension files',
                'Replace extension files',
                'Run extension migrations and upgrade steps',
                'Verify installation and finalise the update',
            ],
        ];
    }

    /**
     * Reject dependency cycles reachable from the candidate extension.
     *
     * @param array<string, string[]> $graph
     * @param array<string, bool> $path Current recursion path.
     */
    private function assertAcyclic(string $id, array $graph, array $path): void
    {
        if (isset($path[$id])) {
            throw new \RuntimeException("Circular extension dependency detected while planning [{$id}].");
        }

        $path[$id] = true;
        foreach ($graph[$id] ?? [] as $dependencyId) {
            $this->assertAcyclic($dependencyId, $graph, $path);
        }
    }
}
