<?php

declare(strict_types=1);

namespace App\Core\Translations\Services;

use App\Core\Translations\Contracts\TranslationFileSystemInterface;
use Illuminate\Support\Arr;
use RuntimeException;

final class LocalTranslationFileSystem implements TranslationFileSystemInterface
{
    public function exists(string $path): bool
    {
        return is_file($path);
    }

    public function read(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        /** @var array<string, mixed> $data */
        $data = include $path;

        return Arr::dot($data);
    }

    public function write(string $path, array $translations): void
    {
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create translation directory: {$directory}");
        }

        $nested = Arr::undot($translations);
        $export = var_export($nested, true);

        $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn {$export};\n";

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException("Unable to write translation file: {$path}");
        }
    }

    public function directories(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        return array_values(array_filter(
            scandir($path) ?: [],
            static fn (string $entry): bool =>
                $entry !== '.'
                && $entry !== '..'
                && is_dir($path . '/' . $entry),
        ));
    }

    public function phpFiles(string $path): array
    {
        return glob($path . '/*.php') ?: [];
    }
}
