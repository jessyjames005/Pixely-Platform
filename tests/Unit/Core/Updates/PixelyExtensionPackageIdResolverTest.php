<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Updates;

use App\Core\Updates\Services\PixelyExtensionPackageIdResolver;
use PHPUnit\Framework\TestCase;

final class PixelyExtensionPackageIdResolverTest extends TestCase
{
    public function test_it_namespaces_local_extension_ids_for_the_official_registry(): void
    {
        self::assertSame('pixely/gallery', (new PixelyExtensionPackageIdResolver())->resolve('gallery'));
    }

    public function test_it_rejects_invalid_local_extension_ids(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new PixelyExtensionPackageIdResolver())->resolve('../gallery');
    }
}
