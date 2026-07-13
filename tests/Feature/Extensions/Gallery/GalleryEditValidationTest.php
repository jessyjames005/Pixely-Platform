<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions\Gallery;

use App\Extensions\Gallery\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests gallery edit validation.
 */
final class GalleryEditValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure title is required when updating a photo.
     */
    public function test_title_is_required_for_update(): void
    {
        $photo = Photo::create([
            'title' => 'Old title',
            'filename' => 'sunset.jpg',
        ]);

        $response = $this->put(
            '/gallery/'.$photo->id,
            [
                'title' => '',
            ]
        );

        $response
            ->assertSessionHasErrors('title');

        $this->assertDatabaseHas(
            'photos',
            [
                'id' => $photo->id,
                'title' => 'Old title',
            ]
        );
    }
}
