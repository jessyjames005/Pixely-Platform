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
     * Plan a complete set of locally available updates in deterministic
     * dependency-first order.
     *
     * @param array<int, array{
     *     manifest: ExtensionManifest,
     *     required_versions?: array<string, string>,
     *     conflicts?: string[]
     * }> $candidates
     * @param array<string, string> $installedVersions Installed extension versions keyed by id.
     * @param array<string, string[]> $installedDependencies Dependency graph for installed extensions.
     * @return array<int, array{
     *     extension: string,
     *     from: string,
     *     to: string,
     *     dependencies: array<string, string>,
     *     actions: array<int, string>
     * }>
     */
    public function planBatch(
        array $candidates,
        array $installedVersions,
        array $installedDependencies = [],
    ): array {
        $candidatesById = [];
        $candidateVersions = [];

        foreach ($candidates as $candidate) {
            $manifest = $candidate['manifest'] ?? null;
            if (! $manifest instanceof ExtensionManifest) {
                throw new \InvalidArgumentException(
                    'Every update candidate must include an extension manifest.',
                );
            }

            if (isset($candidatesById[$manifest->id])) {
                throw new \RuntimeException(
                    "More than one update candidate was provided for extension [{$manifest->id}].",
                );
            }

            $requiredVersions = $candidate['required_versions'] ?? [];
            $conflicts = $candidate['conflicts'] ?? [];
            if (! is_array($requiredVersions) || ! is_array($conflicts)) {
                throw new \InvalidArgumentException(
                    "Update candidate metadata for extension [{$manifest->id}] is invalid.",
                );
            }

            $candidatesById[$manifest->id] = [
                'manifest' => $manifest,
                'required_versions' => $requiredVersions,
                'conflicts' => $conflicts,
            ];
            $candidateVersions[$manifest->id] = $manifest->version;
        }

        foreach ($candidatesById as $id => $candidate) {
            $from = $installedVersions[$id] ?? null;
            if ($from === null) {
                throw new \RuntimeException(
                    "Extension [{$id}] is not installed; an update plan cannot be created.",
                );
            }

            $version = $candidate['manifest']->version;
            if (version_compare($version, $from, '<=')) {
                throw new \RuntimeException(
                    "Candidate version [{$version}] must be newer than installed version [{$from}].",
                );
            }
        }

        $plans = [];
        foreach ($candidatesById as $id => $candidate) {
            $manifest = $candidate['manifest'];
            $dependencies = [];

            foreach ($manifest->dependencies as $dependencyId) {
                $installed = $installedVersions[$dependencyId] ?? null;
                $candidateVersion = $candidateVersions[$dependencyId] ?? null;
                if ($installed === null && $candidateVersion === null) {
                    throw new \RuntimeException(
                        "Extension [{$id}] requires missing extension [{$dependencyId}].",
                    );
                }

                $minimum = $candidate['required_versions'][$dependencyId] ?? null;
                if (
                    $minimum !== null
                    && ($installed === null || version_compare($installed, $minimum, '<'))
                    && ($candidateVersion === null || version_compare($candidateVersion, $minimum, '<'))
                ) {
                    $available = $candidateVersion === null
                        ? $installed
                        : "{$installed} installed; {$candidateVersion} candidate";
                    throw new \RuntimeException(
                        sprintf(
                            'Extension [%s] requires [%s] version [%s] or newer; [%s] is available.',
                            $id,
                            $dependencyId,
                            $minimum,
                            $available,
                        ),
                    );
                }

                $dependencies[$dependencyId] = $candidateVersion ?? $installed;
            }

            foreach ($candidate['conflicts'] as $conflictId) {
                if (isset($installedVersions[$conflictId]) || isset($candidateVersions[$conflictId])) {
                    throw new \RuntimeException(
                        "Extension [{$id}] conflicts with installed extension [{$conflictId}].",
                    );
                }
            }

            $plans[$id] = [
                'extension' => $id,
                'from' => $installedVersions[$id],
                'to' => $manifest->version,
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

        $graph = $installedDependencies;
        foreach ($candidatesById as $id => $candidate) {
            $graph[$id] = $candidate['manifest']->dependencies;
        }
        foreach (array_keys($candidatesById) as $id) {
            $this->assertAcyclic($id, $graph, []);
        }

        return array_map(
            static fn (string $id): array => $plans[$id],
            $this->dependencyFirstOrder($candidatesById),
        );
    }

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

    /**
     * @param array<string, array{manifest: ExtensionManifest, required_versions: array, conflicts: array}> $candidates
     * @return string[]
     */
    private function dependencyFirstOrder(array $candidates): array
    {
        $remaining = array_fill_keys(array_keys($candidates), true);
        $order = [];

        while ($remaining !== []) {
            $ready = [];
            foreach (array_keys($remaining) as $id) {
                $candidateDependencies = array_intersect(
                    $candidates[$id]['manifest']->dependencies,
                    array_keys($remaining),
                );
                if ($candidateDependencies === []) {
                    $ready[] = $id;
                }
            }

            sort($ready, SORT_STRING);
            foreach ($ready as $id) {
                unset($remaining[$id]);
                $order[] = $id;
            }
        }

        return $order;
    }
}
