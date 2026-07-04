<?php

declare(strict_types=1);

namespace App\Extensions\Gallery;

use App\Core\Extensions\ExtensionInterface;
use App\Core\Extensions\ExtensionManifest;

/**
 * Gallery extension.
 *
 * Demonstrates how an extension integrates with the Pixely platform.
 */
final class GalleryExtension implements ExtensionInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            name: 'gallery',
            version: '1.0.0',
            description: 'Pixely gallery extension.',
            author: 'Pixely Team',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        //
    }
}
