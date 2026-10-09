<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Extensions\Capabilities;

use App\Core\Extensions\Capabilities\Contracts\ExtensionBlocksInterface;
use App\Core\Extensions\Capabilities\Registry\ExtensionBlockRegistry;
use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ExtensionBlockRegistryTest extends TestCase
{
    public function test_it_returns_namespaced_validated_block_definitions(): void
    {
        $extension = $this->extension([[
            'id' => 'hero',
            'label' => ' Hero block ',
            'schema' => ['title' => ['type' => 'string']],
            'surfaces' => ['public', 'public'],
        ]]);

        $blocks = (new ExtensionBlockRegistry())->for($extension);

        self::assertSame('hero', $blocks[0]['id']);
        self::assertSame('Hero block', $blocks[0]['label']);
        self::assertSame(['public'], $blocks[0]['surfaces']);
        self::assertSame('demo.hero', $blocks[0]['qualified_id']);
        self::assertSame('demo', $blocks[0]['extension_id']);
    }

    public function test_it_rejects_invalid_block_identifiers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ExtensionBlockRegistry())->for($this->extension([[
            'id' => '../hero',
            'label' => 'Hero',
        ]]));
    }

    public function test_it_rejects_invalid_surfaces(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ExtensionBlockRegistry())->for($this->extension([[
            'id' => 'hero',
            'label' => 'Hero',
            'surfaces' => ['internal'],
        ]]));
    }

    /**
     * @param list<array<string, mixed>> $blocks
     */
    private function extension(array $blocks): ExtensionInterface&ExtensionBlocksInterface
    {
        return new class($blocks) implements ExtensionInterface, ExtensionBlocksInterface {
            /** @param list<array<string, mixed>> $declaredBlocks */
            public function __construct(private readonly array $declaredBlocks)
            {
            }

            public function manifest(): ExtensionManifest
            {
                return new ExtensionManifest('demo', 'Demo', '1.0.0', '1.0.0', self::class, 'app/Extensions/Demo');
            }

            public function providers(): array
            {
                return [];
            }

            public function boot(): void
            {
            }

            public function blocks(): array
            {
                return $this->declaredBlocks;
            }
        };
    }
}
