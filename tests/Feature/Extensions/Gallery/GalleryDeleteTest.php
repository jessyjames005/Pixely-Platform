<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions\Gallery;

use App\Extensions\Gallery\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests photo deletion.
 */
final class GalleryDeleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure a photo can be deleted.
     */
    public function test_it_deletes_a_photo(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put(
            'photos/sunset.jpg',
            'fake image'
        );

        $photo = Photo::create([
            'title' => 'Sunset',
            'filename' => 'sunset.jpg',
        ]);

        $response = $this->delete(
            '/gallery/' . $photo->id
        );

        $response->assertRedirect('/gallery');

        $this->assertDatabaseMissing(
            'photos',
            [
                'id' => $photo->id,
            ]
        );

        Storage::disk('public')
            ->assertMissing('photos/sunset.jpg');
    }
}
