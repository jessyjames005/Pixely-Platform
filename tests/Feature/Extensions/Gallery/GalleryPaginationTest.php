<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions\Gallery;

use App\Extensions\Gallery\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Tests gallery pagination.
 */
final class GalleryPaginationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure gallery is paginated.
     */
    public function test_gallery_is_paginated(): void
    {
        Photo::factory()
            ->count(15)
            ->create();

        $response = $this->get('/gallery');

        $response
            ->assertOk()
            ->assertViewHas(
                'photos',
                fn($photos) => $photos instanceof LengthAwarePaginator
                    && $photos->count() === 12
                    && $photos->total() === 15
            );
    }
}
