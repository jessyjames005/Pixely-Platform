<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Extensions\Discovery;

use App\Core\Extensions\Discovery\ExtensionDiscoverer;
use PHPUnit\Framework\TestCase;

/**
 * Tests the extension discoverer.
 */
final class ExtensionDiscovererTest extends TestCase
{
    /**
     * Ensure an empty array is returned when directory does not exist.
     */
    public function test_it_returns_an_empty_array_when_directory_does_not_exist(): void
    {
        $discoverer = new ExtensionDiscoverer();

        $this->assertSame(
            [],
            $discoverer->discover(
                __DIR__ . '/Fixtures/Unknown'
            )
        );
    }

    /**
     * Ensure extension directories can be discovered.
     */
    public function test_it_can_discover_extension_directories(): void
    {
        $discoverer = new ExtensionDiscoverer();

        $directories = $discoverer->discover(
            dirname(__DIR__, 5) . '/tests/Fixtures/Extensions'
        );

        $this->assertNotEmpty($directories);

        $this->assertContains(
            dirname(__DIR__, 5) . '/tests/Fixtures/Extensions/Gallery',
            $directories
        );
    }
}
