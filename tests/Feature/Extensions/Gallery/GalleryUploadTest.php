<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions\Gallery;

use App\Extensions\Gallery\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests photo upload functionality.
 */
final class GalleryUploadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure a user can upload a photo.
     */
    public function test_it_uploads_a_photo(): void
    {
        // Arrange
        Storage::fake('public');

        $file = UploadedFile::fake()->create('sunset.jpg');

        // Act
        $response = $this->post('/gallery/upload', [
            'title' => 'Sunset',
            'image' => $file,
        ]);

        // Assert
        $response->assertRedirect();

        $this->assertDatabaseHas('photos', [
            'title' => 'Sunset',
        ]);

        Storage::disk('public')
            ->assertExists('photos/' . $file->hashName());
    }
}
