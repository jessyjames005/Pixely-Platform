<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions;

use Tests\TestCase;

final class GalleryRoutesTest extends TestCase
{
    public function test_gallery_route_is_available(): void
    {
        $response = $this->get('/gallery');

        $response
            ->assertOk()
            ->assertSee('Gallery works!');
    }
}
