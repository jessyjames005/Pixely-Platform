<?php

declare(strict_types=1);

namespace Tests\Fixtures\Translations;

use App\Core\Translations\Contracts\TranslationFileSystemInterface;
use Illuminate\Support\Arr;

final class FakeTranslationFileSystem implements TranslationFileSystemInterface
{
    /**
     * Virtual translation files stored in memory.
     *
     * @var array<string, array<string, string>>
     */
    private array $files = [];

    public function exists(string $path): bool
    {
        return isset($this->files[$path]);
    }

    /**
     * Read a virtual translation file.
     *
     * The returned translations use dot notation,
     * exactly like the real filesystem implementation.
     *
     * @return array<string, string>
     */
    public function read(string $path): array
    {
        return $this->files[$path] ?? [];
    }

    /**
     * Write a virtual translation file.
     *
     * Nothing is written to the real filesystem.
     *
     * @param array<string, mixed> $translations
     */
    public function write(string $path, array $translations): void
    {
        $this->files[$path] = Arr::dot(Arr::undot($translations));
    }

    /**
     * Return virtual directories below a path.
     *
     * @return string[]
     */
    public function directories(string $path): array
    {
        $directories = [];

        foreach (array_keys($this->files) as $file) {
            if (! str_starts_with($file, $path . '/')) {
                continue;
            }

            $relative = substr($file, strlen($path) + 1);
            $parts = explode('/', $relative);

            if (count($parts) > 1) {
                $directories[] = $parts[0];
            }
        }

        return array_values(array_unique($directories));
    }

    /**
     * Return virtual PHP files below a path.
     *
     * @return string[]
     */
    public function phpFiles(string $path): array
    {
        return array_values(array_filter(
            array_keys($this->files),
            static fn (string $file): bool =>
                str_starts_with($file, $path . '/')
                && str_ends_with($file, '.php'),
        ));
    }

    /**
     * Seed a virtual translation file for a test.
     *
     * @param array<string, mixed> $translations
     */
    public function seed(string $path, array $translations): void
    {
        $this->files[$path] = Arr::dot(Arr::undot($translations));
    }

    /**
     * Return the content of a virtual file.
     *
     * Useful for asserting write operations.
     *
     * @return array<string, string>
     */
    public function file(string $path): array
    {
        return $this->files[$path] ?? [];
    }
}
