<?php

declare(strict_types=1);

namespace App\Media\Processors;

use App\Media\Contracts\ImageProcessorInterface;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;

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
        $manager = ImageManager::usingDriver(
            Driver::class
        );

        $image = $manager->decodePath(
            $sourcePath
        );

        $image
            ->scaleDown(
                width: $width,
                height: $height
            )
            ->save(
                $targetPath
            );
    }
}
