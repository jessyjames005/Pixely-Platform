<?php

declare(strict_types=1);

namespace App\Media\Processors;

use App\Media\Contracts\ImageProcessorInterface;

/**
 * Image processor based on Intervention Image.
 */
final class InterventionImageProcessor implements ImageProcessorInterface
{
    /**
     * Generate a thumbnail from an image.
     */
    public function generateThumbnail(
        string $sourcePath,
        string $targetPath,
        int $width = 300,
        int $height = 300,
    ): void {
        // Implementation will come in the next step.
    }
}
