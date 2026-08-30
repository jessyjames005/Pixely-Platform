<?php

declare(strict_types=1);

namespace App\Core\Extensions\Installer;

use ZipArchive;

/**
 * Validates an uploaded extension package before any file is
 * moved into the live app/Extensions directory.
 *
 * Every check here runs against a STAGING directory only —
 * nothing touches the real extension tree until validation passes.
 */
final class ExtensionPackageValidator
{
    /**
     * Opens the zip and extracts it to the given staging directory,
     * rejecting any entry that would escape it (zip-slip protection).
     *
     * @throws \RuntimeException
     */
    public function extractSafely(string $zipPath, string $stagingDir): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('The uploaded file is not a valid zip archive.');
        }

        $realStagingDir = realpath($stagingDir) ?: $stagingDir;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);

            if ($entryName === false) {
                continue;
            }

            // Reject absolute paths, parent-directory traversal, and
            // null bytes — the classic zip-slip attack vectors.
            if (
                str_contains($entryName, '..')
                || str_starts_with($entryName, '/')
                || str_contains($entryName, "\0")
                || preg_match('#^[a-zA-Z]:#', $entryName) === 1
            ) {
                $zip->close();
                throw new \RuntimeException("Unsafe path detected in archive: {$entryName}");
            }

            $destination = $realStagingDir . DIRECTORY_SEPARATOR . $entryName;
            $resolvedDestinationDir = dirname($destination);

            // Re-validate after path resolution: the target must stay
            // strictly inside the staging directory.
            if (! str_starts_with($resolvedDestinationDir . DIRECTORY_SEPARATOR, $realStagingDir . DIRECTORY_SEPARATOR)) {
                $zip->close();
                throw new \RuntimeException("Unsafe path detected in archive: {$entryName}");
            }
        }

        if (! $zip->extractTo($stagingDir)) {
            $zip->close();
            throw new \RuntimeException('Failed to extract the archive.');
        }

        $zip->close();
    }

    /**
     * Locates and validates the extension manifest inside the
     * extracted staging directory.
     *
     * The manifest may be at the staging root, or one level down if
     * the zip contains a single wrapping folder (common when zipping
     * a folder directly) — exactly one of the two is accepted.
     *
     * @return array{root: string, id: string, name: string, version: string, class: string}
     * @throws \RuntimeException
     */
    public function locateAndValidateManifest(string $stagingDir): array
    {
        $candidateRoots = [$stagingDir];

        $topLevelEntries = array_values(array_diff(scandir($stagingDir) ?: [], ['.', '..']));
        if (count($topLevelEntries) === 1 && is_dir($stagingDir . '/' . $topLevelEntries[0])) {
            $candidateRoots[] = $stagingDir . '/' . $topLevelEntries[0];
        }

        foreach ($candidateRoots as $root) {
            $manifestPath = $root . '/extension.php';

            if (is_file($manifestPath)) {
                return $this->validateManifestFile($manifestPath, $root);
            }
        }

        throw new \RuntimeException('No extension.php manifest found in the uploaded package.');
    }

    /**
     * @return array{root: string, id: string, name: string, version: string, class: string}
     */
    private function validateManifestFile(string $manifestPath, string $root): array
    {
        // The manifest is a plain PHP array return, evaluated in
        // isolation — it must not reference anything beyond this
        // file, since the extension's own classes aren't autoloaded
        // yet at this point (Composer hasn't seen these files).
        $manifest = include $manifestPath;

        if (! is_array($manifest)) {
            throw new \RuntimeException('extension.php must return an array.');
        }

        foreach (['id', 'name', 'version', 'class'] as $requiredKey) {
            if (empty($manifest[$requiredKey]) || ! is_string($manifest[$requiredKey])) {
                throw new \RuntimeException("extension.php is missing a valid '{$requiredKey}' field.");
            }
        }

        if (preg_match('/^[a-z0-9\-]+$/', $manifest['id']) !== 1) {
            throw new \RuntimeException('Extension id must contain only lowercase letters, digits, and hyphens.');
        }

        if (preg_match('/^\d+\.\d+\.\d+$/', $manifest['version']) !== 1) {
            throw new \RuntimeException('Extension version must follow semantic versioning (e.g. 1.0.0).');
        }

        return [
            'root' => $root,
            'id' => $manifest['id'],
            'name' => $manifest['name'],
            'version' => $manifest['version'],
            'class' => $manifest['class'],
        ];
    }
}
