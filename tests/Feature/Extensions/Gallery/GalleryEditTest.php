<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions\Gallery;

use App\Extensions\Gallery\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests photo edition.
 */
final class GalleryEditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure a photo title can be updated.
     */
    public function test_it_updates_a_photo_title(): void
    {
        $photo = Photo::create([
            'title' => 'Old title',
            'filename' => 'sunset.jpg',
        ]);

        $response = $this->put(
            '/gallery/' . $photo->id,
            [
                'title' => 'New title',
            ]
        );

        $response->assertRedirect('/gallery');

        $this->assertDatabaseHas(
            'photos',
            [
                'id' => $photo->id,
                'title' => 'New title',
            ]
        );
    }
}
