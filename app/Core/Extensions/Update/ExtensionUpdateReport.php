<?php

declare(strict_types=1);

namespace App\Core\Extensions\Update;

use Illuminate\Support\Facades\File;

/**
 * Persists a machine-readable record of one pixely:update invocation.
 * Reports contain operational metadata and error messages, not package contents.
 */
final class ExtensionUpdateReport
{
    /**
     * Write a JSON report atomically so readers never see a partial document.
     *
     * @param array<string, mixed> $payload
     * @throws \RuntimeException when the destination cannot be written.
     */
    public function write(string $path, array $payload): string
    {
        $directory = dirname($path);
        File::ensureDirectoryExists($directory);

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
        $temporaryPath = $path . '.tmp-' . bin2hex(random_bytes(4));

        if (file_put_contents($temporaryPath, $json, LOCK_EX) === false) {
            throw new \RuntimeException("Unable to write temporary update report [{$temporaryPath}].");
        }

        if (! @rename($temporaryPath, $path)) {
            @unlink($temporaryPath);
            throw new \RuntimeException("Unable to finalise update report [{$path}].");
        }

        return $path;
    }
}
