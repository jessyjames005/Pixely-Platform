<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Media\Processors\InterventionImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests InterventionImageProcessor.
 */
final class InterventionImageProcessorTest extends TestCase
{
    /**
     * Ensure a thumbnail is generated.
     */
    public function test_it_generates_a_thumbnail(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image(
            'photo.jpg',
            800,
            600
        );

        $source = $file->store('photos', 'public');
        $target = 'photos/thumb_photo.jpg';

        $processor = new InterventionImageProcessor();

        $processor->generateThumbnail(
            Storage::disk('public')->path($source),
            Storage::disk('public')->path($target),
        );

        Storage::disk('public')->assertExists($target);
    }
}
