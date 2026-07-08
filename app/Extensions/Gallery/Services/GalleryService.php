<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Services;

use App\Extensions\Gallery\Models\Photo;
use App\Extensions\Gallery\Repositories\GalleryRepository;
use Illuminate\Database\Eloquent\Collection;

final class GalleryService
{
    public function __construct(
        private readonly GalleryRepository $repository,
    ) {
    }

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function create(array $attributes): Photo
    {
        return $this->repository->create($attributes);
    }
}
