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
     * Ensure the extension exposes a valid manifest.
     */
    public function test_it_returns_a_valid_manifest(): void
    {
        $extension = new GalleryExtension();

        $manifest = $extension->manifest();

        $this->assertSame('gallery', $manifest->name);
        $this->assertSame('1.0.0', $manifest->version);
        $this->assertSame(GalleryExtension::class, $manifest->class);
    }

    public function test_it_declares_the_gallery_service_provider(): void
    {
        $extension = new GalleryExtension();

        $this->assertContains(
            \App\Extensions\Gallery\Providers\GalleryServiceProvider::class,
            $extension->providers()
        );
    }
}
