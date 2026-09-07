<?php

declare(strict_types=1);

namespace App\Core\Translations\Contracts;

interface TranslationFileSystemInterface
{
    public function exists(string $path): bool;

    public function read(string $path): array;

    public function write(string $path, array $translations): void;

    /**
     * @return string[]
     */
    public function directories(string $path): array;

    /**
     * @return string[]
     */
    public function phpFiles(string $path): array;
}
