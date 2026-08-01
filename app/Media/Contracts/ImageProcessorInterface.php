<?php

declare(strict_types=1);

namespace App\Media\Contracts;

/**
 * Defines image processing operations.
 */
interface ImageProcessorInterface
{
    /**
     * Generate a thumbnail from an existing image.
     *
     * @param string $sourcePath Source image path.
     * @param string $targetPath Thumbnail destination path.
     * @param int $width Thumbnail width.
     * @param int $height Thumbnail height.
     */
    public function generateThumbnail(
        string $sourcePath,
        string $targetPath,
        int $width = 300,
        int $height = 300,
    ): void;
}
