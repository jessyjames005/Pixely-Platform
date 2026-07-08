<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions\Gallery;

use App\Extensions\Gallery\Repositories\GalleryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GalleryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_photo(): void
    {
        $repository = new GalleryRepository();

        $photo = $repository->create([
            'title' => 'Forest',
            'filename' => 'forest.jpg',
        ]);

        $this->assertDatabaseHas('photos', [
            'title' => 'Forest',
        ]);

        $this->assertSame('Forest', $photo->title);
    }

    public function test_it_returns_all_photos(): void
    {
        $repository = new GalleryRepository();

        $repository->create([
            'title' => 'One',
            'filename' => 'one.jpg',
        ]);

        $repository->create([
            'title' => 'Two',
            'filename' => 'two.jpg',
        ]);

        $photos = $repository->all();

        $this->assertCount(2, $photos);
    }
}
