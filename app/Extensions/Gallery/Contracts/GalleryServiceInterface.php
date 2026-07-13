<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Contracts;

use App\Extensions\Gallery\Models\Photo;
use Illuminate\Database\Eloquent\Collection;

interface GalleryServiceInterface
{
    /**
     * Retrieve all photos.
     *
     * @return Collection<int, Photo>
     */
    public function all(): Collection;

    /**
     * Create a photo.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Photo;

    /**
     * Delete a photo and its associated file.
     */
    public function delete(Photo $photo): void;
}
