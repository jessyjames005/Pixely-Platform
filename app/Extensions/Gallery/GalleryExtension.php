<?php

declare(strict_types=1);

namespace App\Extensions\Gallery;

use App\Core\Extensions\ExtensionInterface;

/**
 * Gallery extension.
 *
 * Demonstrates how an extension integrates with the Pixely platform.
 */
final class GalleryExtension implements ExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'gallery';
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
