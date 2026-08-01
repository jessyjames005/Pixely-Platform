<?php

declare(strict_types=1);

namespace App\Media\Services;

use App\Media\Contracts\ImageProcessorInterface;
use Illuminate\Support\Facades\Storage;

/**
 * Generates thumbnails.
 */
final readonly class ThumbnailGenerator
{
    /**
     * Create a thumbnail generator.
     */
    public function __construct(
        private ImageProcessorInterface $processor,
    ) {
    }

    /**
     * Generate a thumbnail.
     */
    public function generate(string $path): string
    {
        $thumbnail = $this->thumbnailPath($path);

        $this->processor->generateThumbnail(
            Storage::disk('public')->path($path),
            Storage::disk('public')->path($thumbnail),
        );

        return $thumbnail;
    }

    /**
     * Build thumbnail path.
     */
    public function thumbnailPath(string $path): string
    {
        return sprintf(
            '%s/thumb_%s',
            dirname($path),
            basename($path)
        );
    }
}
