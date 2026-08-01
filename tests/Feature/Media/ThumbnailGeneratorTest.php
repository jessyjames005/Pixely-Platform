<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Media\Services\ThumbnailGenerator;

/**
 * Tests thumbnail generation.
 */
final class ThumbnailGenerationTest extends TestCase
{
    /**
     * Ensure thumbnail generation works.
     */
    public function test_it_generates_a_thumbnail(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image(
            'photo.jpg',
            800,
            600
        );

        $source = $file->store(
            'photos',
            'public'
        );

        $generator = app(
            ThumbnailGenerator::class
        );

        $thumbnail = $generator->generate(
            $source
        );

        Storage::disk('public')
            ->assertExists($thumbnail);
    }
}
