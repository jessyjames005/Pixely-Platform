<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Repositories;

use App\Extensions\Gallery\Models\Photo;
use Illuminate\Database\Eloquent\Collection;

final class GalleryRepository
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
}
