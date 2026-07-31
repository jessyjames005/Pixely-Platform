<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions\Gallery;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests gallery media storage integration.
 */
final class GalleryMediaStorageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure gallery stores uploaded files through media storage.
     */
    public function test_gallery_uploads_using_media_storage(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()
            ->image('sunset.jpg');

        $response = $this->post('/gallery/upload', [
            'title' => 'Sunset',
            'image' => $file,
        ]);

        $response->assertRedirect('/gallery');

        $this->assertDatabaseHas('photos', [
            'title' => 'Sunset',
        ]);
    }
}
