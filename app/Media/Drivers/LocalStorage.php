<?php

declare(strict_types=1);

namespace App\Media\Drivers;

use App\Media\Contracts\StorageInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Local filesystem storage driver.
 */
final class LocalStorage implements StorageInterface
{
    /**
     * Store a file.
     */
     public function store(UploadedFile $file, string $directory = 'photos'): string
    {
        return $file->store($directory, 'public');
    }

    /**
     * Delete a file.
     */
    public function delete(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }

    /**
     * Get the public URL of a file.
     */
    public function url(string $path): string
    {
        return Storage::disk('public')->url($path);
    }
}
