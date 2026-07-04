<?php

declare(strict_types=1);

namespace Tests\Unit\Extensions\Gallery;

use App\Extensions\Gallery\GalleryExtension;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Gallery extension.
 */
final class GalleryExtensionTest extends TestCase
{
    /**
     * Ensure the extension returns its unique name.
     */
    public function test_it_returns_the_extension_name(): void
    {
        $extension = new GalleryExtension();

        $this->assertSame('gallery', $extension->getName());
    }
}
