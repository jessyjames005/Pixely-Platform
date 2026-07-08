<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions\Gallery;

use App\Extensions\Gallery\Services\GalleryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GalleryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_photo(): void
    {
        $service = app(GalleryService::class);

        $photo = $service->create([
            'title' => 'Mountain',
            'filename' => 'mountain.jpg',
        ]);

        $this->assertDatabaseHas('photos', [
            'title' => 'Mountain',
        ]);

        $this->assertSame('Mountain', $photo->title);
    }

    public function test_it_returns_all_photos(): void
    {
        $service = app(GalleryService::class);

        $service->create([
            'title' => 'One',
            'filename' => 'one.jpg',
        ]);

        $service->create([
            'title' => 'Two',
            'filename' => 'two.jpg',
        ]);

        $this->assertCount(
            2,
            $service->all()
        );
    }
}
