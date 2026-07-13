<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions\Gallery;

use App\Extensions\Gallery\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests gallery photo display.
 */
final class GalleryShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure a photo detail page is displayed.
     */
    public function test_it_displays_a_photo(): void
    {
        $photo = Photo::create([
            'title' => 'Sunset',
            'filename' => 'sunset.jpg',
        ]);

        $response = $this->get('/gallery/'.$photo->id);

        $response
            ->assertOk()
            ->assertSee('Sunset');
    }
}
