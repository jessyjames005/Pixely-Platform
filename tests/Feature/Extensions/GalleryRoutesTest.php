<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions\Gallery;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests the Gallery extension routes.
 */
final class GalleryRoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure the gallery route is accessible.
     */
    public function test_gallery_route_is_available(): void
    {
        $response = $this->get('/gallery');

        $response
            ->assertOk()
            ->assertSee('Gallery');
    }
}
