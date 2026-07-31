<?php

declare(strict_types=1);

namespace App\Media\Contracts;

use Illuminate\Http\UploadedFile;

interface StorageInterface
{
    public function store(UploadedFile $file): string;

    public function delete(string $path): bool;

    public function url(string $path): string;
}
