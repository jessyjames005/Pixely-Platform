<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions;

use Tests\TestCase;

final class GalleryRoutesTest extends TestCase
{
    public function test_gallery_route_is_loaded(): void
    {
        $this->get('/gallery')
            ->assertOk()
            ->assertSee('Gallery works!');
    }
}
