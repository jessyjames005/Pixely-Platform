<?php

declare(strict_types=1);

namespace App\Extensions\Gallery;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;

/**
 * Gallery extension.
 */
final class GalleryExtension implements ExtensionInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            'gallery',
            '1.0.0',
            self::class
        );
    }

    public function register(): void {}
    public function boot(): void {}
}
