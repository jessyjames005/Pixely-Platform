<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Extensions\Manifest;

use App\Core\Extensions\Manifest\ExtensionManifest;
use PHPUnit\Framework\TestCase;

final class ExtensionManifestTest extends TestCase
{
    public function test_it_can_create_a_manifest_without_dependencies(): void
    {
        $manifest = new ExtensionManifest(
            id: 'gallery',
            name: 'Gallery',
            version: '1.0.0',
            class: 'App\\Extensions\\Gallery\\GalleryExtension',
        );

        $this->assertSame(
            [],
            $manifest->dependencies,
        );
    }

    public function test_it_can_define_extension_dependencies(): void
    {
        $manifest = new ExtensionManifest(
            id: 'gallery',
            name: 'Gallery',
            version: '1.0.0',
            class: 'App\\Extensions\\Gallery\\GalleryExtension',
            dependencies: [
                'media',
            ],
        );

        $this->assertSame(
            [
                'media',
            ],
            $manifest->dependencies,
        );
    }
}
