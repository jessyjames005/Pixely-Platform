<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Contracts;

use App\Extensions\Gallery\Models\Photo;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contract for gallery repositories.
 *
 * Defines every data access operation available for the Gallery.
 */
interface GalleryRepositoryInterface
{
    /**
     * Retrieve all photos.
     *
     * @return Collection<int, Photo>
     */
    public function all(): Collection;

    /**
     * Create a new photo.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Photo;

    /**
     * Delete a photo.
     */
    public function delete(Photo $photo): void;

    /**
     * Update a photo.
     *
     * @param array<string, mixed> $attributes
     */
    public function update(
        Photo $photo,
        array $attributes
    ): Photo;
}
