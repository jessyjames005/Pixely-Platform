<?php

declare(strict_types=1);

namespace App\Core\Extensions\Dependency;

use App\Core\Extensions\Exceptions\ExtensionDependencyCycleException;
use App\Core\Extensions\Exceptions\ExtensionDependencyException;
use App\Core\Extensions\Manifest\ExtensionManifest;

/**
 * Resolves extension dependencies and determines load order.
 */
final class ExtensionDependencyResolver
{
    /**
     * Resolve extensions in dependency order.
     *
     * @param ExtensionManifest[] $extensions
     *
     * @return ExtensionManifest[]
     */
    public function resolve(array $extensions): array
    {
        $byId = [];

        foreach ($extensions as $extension) {
            $byId[$extension->id] = $extension;
        }

        $resolved = [];
        $visiting = [];
        $visited = [];

        foreach ($extensions as $extension) {
            $this->visit(
                $extension,
                $byId,
                $resolved,
                $visiting,
                $visited,
            );
        }

        return $resolved;
    }

    /**
     * Visit an extension and resolve its dependencies.
     *
     * @param array<string, ExtensionManifest> $byId
     * @param ExtensionManifest[] $resolved
     * @param array<string, bool> $visiting
     * @param array<string, bool> $visited
     */
    private function visit(
        ExtensionManifest $extension,
        array $byId,
        array &$resolved,
        array &$visiting,
        array &$visited,
    ): void {
        $id = $extension->id;

        if (isset($visited[$id])) {
            return;
        }

        if (isset($visiting[$id])) {
            throw new ExtensionDependencyCycleException(
                "Circular extension dependency detected: [{$id}].",
            );
        }

        $visiting[$id] = true;

        foreach ($extension->dependencies as $dependencyId) {
            if (! isset($byId[$dependencyId])) {
                throw new ExtensionDependencyException(
                    "Extension [{$id}] requires missing extension [{$dependencyId}].",
                );
            }

            $this->visit(
                $byId[$dependencyId],
                $byId,
                $resolved,
                $visiting,
                $visited,
            );
        }

        unset($visiting[$id]);

        $visited[$id] = true;

        $resolved[] = $extension;
    }
}
