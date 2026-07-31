<?php

declare(strict_types=1);

namespace App\Media\Services;

use App\Media\Contracts\StorageInterface;
use Illuminate\Http\UploadedFile;

/**
 * Storage manager.
 *
 * Delegates storage operations to the configured driver.
 */
final readonly class StorageManager
{
    public function __construct(
        private StorageInterface $driver,
    ) {
    }

    /**
     * Store a file.
     */
    public function store(UploadedFile $file): string
    {
        return $this->driver->store($file);
    }

    /**
     * Delete a file.
     */
    public function delete(string $path): bool
    {
        return $this->driver->delete($path);
    }

    /**
     * Get a file URL.
     */
    public function url(string $path): string
    {
        return $this->driver->url($path);
    }
}
