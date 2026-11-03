<?php

declare(strict_types=1);

namespace App\Core\Updates\Services;

use App\Core\Extensions\Installer\ExtensionPackageValidator;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Update\ExtensionUpdatePlanner;
use App\Core\Updates\Contracts\InstalledExtensionInventoryInterface;
use Composer\Semver\Semver;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Performs non-executing checks on an authenticated extension archive.
 */
final class ExtensionReleasePreflight
{
    public function __construct(
        private readonly ExtensionPackageValidator $packages,
        private readonly InstalledExtensionInventoryInterface $inventory,
        private readonly ExtensionUpdatePlanner $planner,
        private readonly RuntimeCompatibilityChecker $compatibility,
    ) {
    }

    public function validate(string $zipPath, string $inspectionDirectory, string $packageId, string $version): void
    {
        $this->packages->extractSafely($zipPath, $inspectionDirectory);
        $manifest = $this->packages->locateAndValidateStaticManifest($inspectionDirectory);

        $packageParts = explode('/', $packageId);
        $expectedId = end($packageParts);
        if ($manifest['id'] !== $expectedId) {
            throw new RuntimeException(
                "Package extension id [{$manifest['id']}] does not match Registry package id [{$expectedId}].",
            );
        }
        if ($manifest['version'] !== $version) {
            throw new RuntimeException(
                "Package extension version [{$manifest['version']}] does not match requested release [{$version}].",
            );
        }

        $runtime = $this->compatibility->runtimeVersions();
        $kernelVersion = $runtime['pixely'] ?? '';
        $minimumKernelConstraint = '>=' . $manifest['minimum_kernel_version'];
        if (
            $kernelVersion === ''
            || ! Semver::satisfies($kernelVersion, $minimumKernelConstraint)
        ) {
            throw new RuntimeException(
                "Extension [{$manifest['id']}] requires Pixely kernel "
                . "[{$manifest['minimum_kernel_version']}] or newer.",
            );
        }

        $this->assertClassDeclaration($manifest['root'], $manifest['class']);
        $this->assertInstalledCompatibility($manifest);
    }

    /**
     * Verify the manifest entrypoint exists as a declaration in package source,
     * without loading or evaluating any extension code.
     */
    private function assertClassDeclaration(string $root, string $class): void
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)+$/', $class) !== 1) {
            throw new RuntimeException('Manifest class metadata must be a fully qualified class name.');
        }

        $expected = strtolower($class);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            if ($source === false) {
                continue;
            }

            $tokens = token_get_all($source);
            $namespace = '';
            for ($position = 0, $count = count($tokens); $position < $count; $position++) {
                $token = $tokens[$position];
                if (! is_array($token)) {
                    continue;
                }

                if ($token[0] === T_NAMESPACE) {
                    $namespace = $this->readNamespace($tokens, $position + 1);
                    continue;
                }

                if ($token[0] !== T_CLASS) {
                    continue;
                }

                $next = $this->nextSignificantToken($tokens, $position + 1);
                if (! is_array($next) || $next[0] !== T_STRING) {
                    continue;
                }

                $declared = strtolower(($namespace === '' ? '' : $namespace . '\\') . $next[1]);
                if ($declared === $expected) {
                    return;
                }
            }
        }

        throw new RuntimeException(
            "Manifest entrypoint class [{$class}] has no static class declaration in the package.",
        );
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     */
    private function readNamespace(array $tokens, int $position): string
    {
        $namespace = '';
        for ($count = count($tokens); $position < $count; $position++) {
            $token = $tokens[$position];
            $text = is_array($token) ? $token[1] : $token;
            if ($text === ';' || $text === '{') {
                return trim($namespace, " \\t\\n\\r\\0\\x0B\\\\");
            }
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $namespace .= $text;
        }

        return '';
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     */
    private function nextSignificantToken(array $tokens, int $position): array|string|null
    {
        for ($count = count($tokens); $position < $count; $position++) {
            $token = $tokens[$position];
            if (
                is_array($token)
                && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)
            ) {
                continue;
            }

            return $token;
        }

        return null;
    }

    /**
     * @param array{
     *     root: string,
     *     id: string,
     *     name: string,
     *     version: string,
     *     minimum_kernel_version: string,
     *     class: string,
     *     requires: array,
     *     require_versions: array,
     *     conflicts: array
     * } $manifest
     */
    private function assertInstalledCompatibility(array $manifest): void
    {
        $extensions = $this->inventory->all();
        $versions = [];
        $graph = [];
        foreach ($extensions as $extension) {
            $versions[$extension->id] = $extension->version;
            $graph[$extension->id] = $extension->dependencies;
        }

        foreach ($manifest['requires'] as $dependencyId) {
            if (! isset($versions[$dependencyId])) {
                throw new RuntimeException(
                    "Extension [{$manifest['id']}] requires missing extension [{$dependencyId}].",
                );
            }
            $minimum = $manifest['require_versions'][$dependencyId] ?? null;
            if ($minimum !== null && version_compare($versions[$dependencyId], $minimum, '<')) {
                throw new RuntimeException(
                    "Extension [{$manifest['id']}] requires [{$dependencyId}] version [{$minimum}] or newer; "
                    . "[{$versions[$dependencyId]}] is installed.",
                );
            }
        }

        foreach ($manifest['conflicts'] as $conflictId) {
            if (isset($versions[$conflictId])) {
                throw new RuntimeException(
                    "Extension [{$manifest['id']}] conflicts with installed extension [{$conflictId}].",
                );
            }
        }

        $candidate = new ExtensionManifest(
            id: $manifest['id'],
            name: $manifest['name'],
            version: $manifest['version'],
            minimum_kernel_version: $manifest['minimum_kernel_version'],
            class: $manifest['class'],
            path: $manifest['root'],
            dependencies: $manifest['requires'],
        );

        if (isset($versions[$manifest['id']])) {
            $this->planner->plan(
                $candidate,
                $versions,
                $manifest['require_versions'],
                $manifest['conflicts'],
                $graph,
            );

            return;
        }

        $graph[$manifest['id']] = $manifest['requires'];
        $this->assertAcyclic($manifest['id'], $graph, []);
    }

    /**
     * @param array<string, string[]> $graph
     * @param array<string, bool> $path
     */
    private function assertAcyclic(string $id, array $graph, array $path): void
    {
        if (isset($path[$id])) {
            throw new RuntimeException("Circular extension dependency detected while preparing [{$id}].");
        }

        $path[$id] = true;
        foreach ($graph[$id] ?? [] as $dependencyId) {
            $this->assertAcyclic($dependencyId, $graph, $path);
        }
    }
}
