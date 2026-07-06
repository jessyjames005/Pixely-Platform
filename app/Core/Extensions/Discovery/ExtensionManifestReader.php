<?php

declare(strict_types=1);

namespace App\Core\Extensions\Discovery;

/**
 * Reads PHP-based extension manifest files.
 */
final class ExtensionManifestReader
{
    /**
     * Read extension manifest file.
     *
     * @return array<string, mixed>
     */
    public function read(string $manifestPath): ?array
    {
        if (!is_file($manifestPath)) {
            return null;
        }

        $data = require $manifestPath;

        return is_array($data) ? $data : null;
    }
}
