<?php

declare(strict_types=1);

namespace Tests\Fakes\Translations;

use App\Core\Translations\Contracts\TranslationFileSystemInterface;
use Illuminate\Support\Arr;

final class FakeTranslationFileSystem implements TranslationFileSystemInterface
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $files = [];

    public function exists(string $path): bool
    {
        return isset($this->files[$path]);
    }

    public function read(string $path): array
    {
        return $this->files[$path] ?? [];
    }

    public function write(string $path, array $translations): void
    {
        $this->files[$path] = Arr::dot(Arr::undot($translations));
    }

    /**
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
     * @return array<string, string>
     */
    public function file(string $path): array
    {
        return $this->files[$path] ?? [];
    }
}
