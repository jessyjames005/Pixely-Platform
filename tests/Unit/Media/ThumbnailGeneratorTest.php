<?php

declare(strict_types=1);

namespace Tests\Unit\Media;

use App\Media\Services\ThumbnailGenerator;
use Tests\TestCase;
use App\Media\Contracts\ImageProcessorInterface;
use Mockery;

/**
 * Tests ThumbnailGenerator.
 */
final class ThumbnailGeneratorTest extends TestCase
{
    /**
     * Close Mockery after each test.
     */
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * Ensure ThumbnailGenerator can be instantiated.
     */
    public function test_it_can_be_instantiated(): void
    {
        $generator = $this->createGenerator();

        $this->assertInstanceOf(
            ThumbnailGenerator::class,
            $generator
        );
    }

    public function test_it_generates_a_thumbnail_path(): void
    {
        $generator = new ThumbnailGenerator(
            Mockery::mock(ImageProcessorInterface::class)
        );

        $this->assertSame(
            'photos/thumb_sunset.jpg',
            $generator->thumbnailPath('photos/sunset.jpg')
        );
    }

    /**
     * Ensure thumbnail generation uses the image processor.
     */
    public function test_it_generates_thumbnail_using_processor(): void
    {
        $processor = Mockery::mock(ImageProcessorInterface::class);

        $processor
            ->shouldReceive('generateThumbnail')
            ->once()
            ->with(
                storage_path('app/public/photos/photo.jpg'),
                storage_path('app/public/photos/thumb_photo.jpg')
            );

        $generator = new ThumbnailGenerator(
            $processor
        );

        $this->assertSame(
            'photos/thumb_photo.jpg',
            $generator->generate('photos/photo.jpg')
        );
    }

    /**
     * Create a ThumbnailGenerator instance.
     */
    private function createGenerator(): ThumbnailGenerator
    {
        return new ThumbnailGenerator(
            Mockery::mock(ImageProcessorInterface::class)
        );
    }
}
