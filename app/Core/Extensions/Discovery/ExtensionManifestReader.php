<?php

declare(strict_types=1);

namespace App\Core\Extensions\Discovery;

/**
 * Reads an extension manifest.
 */
final class ExtensionManifestReader
{
    /**
     * Read an extension manifest.
     *
     * @return array<string, mixed>
     */
    public function read(string $manifestPath): array
    {
        if (! is_file($manifestPath)) {
            return [];
        }

        $content = file_get_contents($manifestPath);

        if ($content === false) {
            return [];
        }

        $manifest = json_decode($content, true);

        return is_array($manifest) ? $manifest : [];
    }
}
