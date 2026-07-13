<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Repositories;

use App\Extensions\Gallery\Models\Photo;
use Illuminate\Database\Eloquent\Collection;
use App\Extensions\Gallery\Contracts\GalleryRepositoryInterface;

final class GalleryRepository implements GalleryRepositoryInterface
{
    /**
     * Return all photos.
     */
    public function all(): Collection
    {
        return Photo::query()->get();
    }

    /**
     * Create a photo.
     */
    public function create(array $attributes): Photo
    {
        return Photo::create($attributes);
    }

    /**
     * Delete a photo.
     */
    public function delete(Photo $photo): void
    {
        $photo->delete();
    }

    /**
     * Update a photo.
     *
     * @param array<string, mixed> $attributes
     */
    public function update(
        Photo $photo,
        array $attributes
    ): Photo {
        $photo->update($attributes);

        return $photo;
    }
}
