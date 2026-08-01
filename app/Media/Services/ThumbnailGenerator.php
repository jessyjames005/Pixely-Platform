<?php

declare(strict_types=1);

namespace App\Media\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;

/**
 * Generates thumbnails for uploaded images.
 */
final class ThumbnailGenerator
{
    /**
     * Get the thumbnail path.
     */
    public function thumbnailPath(string $path): string
    {
        $directory = dirname($path);
        $filename = basename($path);

        return sprintf(
            '%s/thumb_%s',
            $directory,
            $filename
        );
    }

    /**
     * Generate a thumbnail for the given image.
     */
    public function generate(string $path): string
    {
        $thumbnail = $this->thumbnailPath($path);

        $manager = new ImageManager(new Driver());

        $image = $manager->read(
            Storage::disk('public')->path($path)
        );

        $image
            ->scaleDown(width: 300, height: 300)
            ->save(
                Storage::disk('public')->path($thumbnail)
            );

        return $thumbnail;
    }
}
