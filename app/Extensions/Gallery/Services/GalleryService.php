<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Services;

use App\Extensions\Gallery\Models\Photo;
use App\Extensions\Gallery\Contracts\GalleryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Handles business operations related to the Gallery extension.
 *
 * This service acts as an intermediate layer between controllers
 * and repositories to keep business logic isolated.
 */
final class GalleryService
{
    /**
     * Create a new Gallery service instance.
     */
    public function __construct(
        private readonly GalleryRepositoryInterface $repository,
    ) {
    }

    /**
     * Retrieve all photos from the gallery.
     *
     * @return Collection<int, Photo>
     */
    public function all(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Create a new photo entry.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Photo
    {
        return $this->repository->create($attributes);
    }
}
