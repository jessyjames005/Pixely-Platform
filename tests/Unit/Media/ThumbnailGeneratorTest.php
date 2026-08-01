<?php

declare(strict_types=1);

namespace Tests\Unit\Media;

use App\Media\Services\ThumbnailGenerator;
use Tests\TestCase;

/**
 * Tests ThumbnailGenerator.
 */
final class ThumbnailGeneratorTest extends TestCase
{
    /**
     * Ensure ThumbnailGenerator can be instantiated.
     */
    public function test_it_can_be_instantiated(): void
    {
        $generator = new ThumbnailGenerator();

        $this->assertInstanceOf(
            ThumbnailGenerator::class,
            $generator
        );
    }

    public function test_it_generates_a_thumbnail_path(): void
    {
        $generator = new ThumbnailGenerator();

        $this->assertSame(
            'photos/thumb_sunset.jpg',
            $generator->thumbnailPath('photos/sunset.jpg')
        );
    }
}
