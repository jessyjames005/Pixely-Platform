<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions\Gallery;

use App\Extensions\Gallery\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests the Gallery HTTP controller behaviour.
 */
final class GalleryControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure the gallery page displays photos.
     */
    public function test_it_displays_the_gallery_page(): void
    {
        Photo::create([
            'title' => 'Sunset',
            'filename' => 'sunset.jpg',
        ]);

        $response = $this->get('/gallery');

        $response
            ->assertOk()
            ->assertSee('Sunset');
    }
}
