<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions\Gallery;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests gallery upload validation.
 */
final class GalleryUploadValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure image is required.
     */
    public function test_image_is_required_for_upload(): void
    {
        $response = $this->post('/gallery/upload', [
            'title' => 'Sunset',
        ]);

        $response->assertSessionHasErrors(['image']);
    }
}
