<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests thumbnail generation.
 */
final class ThumbnailGenerationTest extends TestCase
{
    /**
     * Ensure a thumbnail is generated and stored.
     */
    public function test_it_generates_a_thumbnail(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image(
            'sunset.jpg',
            800,
            600
        );

        $path = $file->store('photos', 'public');

        $generator = new \App\Media\Services\ThumbnailGenerator();

        $thumbnail = $generator->generate($path);

        Storage::disk('public')->assertExists($thumbnail);
    }
}
