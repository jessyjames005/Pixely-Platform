<?php

declare(strict_types=1);

namespace App\Core\Extensions\Database;

use RuntimeException;

final class ExtensionMigrationCompatibilityChecker
{
    public function check(array $manifest, string $kernelVersion): void
    {
        $minimumKernelVersion = $manifest['minimum_kernel_version'] ?? null;

        if ($minimumKernelVersion === null) {
            return;
        }

        if (! is_string($minimumKernelVersion) || ! preg_match(
            '/^\d+\.\d+\.\d+$/',
            $minimumKernelVersion
        )) {
            throw new RuntimeException(
                sprintf(
                    'Invalid minimum_kernel_version for extension "%s".',
                    $manifest['id'] ?? 'unknown',
                ),
            );
        }

        if (version_compare($kernelVersion, $minimumKernelVersion, '<')) {
            throw new RuntimeException(
                sprintf(
                    'Extension "%s" requires Pixely Kernel >= %s. Current version: %s.',
                    $manifest['id'] ?? 'unknown',
                    $minimumKernelVersion,
                    $kernelVersion,
                ),
            );
        }
    }
}
