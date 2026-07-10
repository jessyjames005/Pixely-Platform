<?php

declare(strict_types=1);

namespace Tests\Unit\Extensions\Gallery\Repositories;

use App\Extensions\Gallery\Models\Photo;
use App\Extensions\Gallery\Repositories\GalleryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests the Eloquent Gallery repository.
 */
final class EloquentGalleryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure the repository returns every stored photo.
     */
    public function test_it_returns_all_photos(): void
    {
        // Arrange
        Photo::create([
            'title' => 'Sunset',
            'filename' => 'sunset.jpg',
        ]);

        Photo::create([
            'title' => 'Mountains',
            'filename' => 'mountains.jpg',
        ]);

        // Act
        $repository = new GalleryRepository();

        $photos = $repository->all();

        // Assert
        $this->assertCount(2, $photos);
        $this->assertSame('Sunset', $photos->first()->title);
        $this->assertSame('Mountains', $photos->last()->title);
    }
}
