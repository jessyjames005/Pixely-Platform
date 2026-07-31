<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions\Gallery;

use App\Extensions\Gallery\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests gallery search.
 */
final class GallerySearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure photos can be searched by title.
     */
    public function test_gallery_can_search_by_title(): void
    {
        Photo::factory()->create([
            'title' => 'Sunset',
        ]);

        Photo::factory()->create([
            'title' => 'Mountain',
        ]);

        $response = $this->get('/gallery?search=Sun');

        $response
            ->assertOk()
            ->assertSee('Sunset')
            ->assertDontSee('Mountain');
    }
}
