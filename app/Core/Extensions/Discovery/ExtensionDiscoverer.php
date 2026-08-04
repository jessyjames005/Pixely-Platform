<?php

declare(strict_types=1);

namespace App\Core\Extensions\Discovery;


/**
 * Discovers available extensions.
 */
final class ExtensionDiscoverer
{
    /**
     * Discover extension directories.
     *
     * @return array<int, string>
     */
    public function discover(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $directories = glob($path . '/*', GLOB_ONLYDIR);

        return $directories === false ? [] : $directories;
    }
}
