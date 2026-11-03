<?php

declare(strict_types=1);

namespace App\Core\Updates\Services;

use App\Core\Updates\Contracts\ExtensionPackageIdResolverInterface;

/**
 * Default official-registry namespace mapping: gallery -> pixely/gallery.
 */
final class PixelyExtensionPackageIdResolver implements ExtensionPackageIdResolverInterface
{
    public function resolve(string $extensionId): string
    {
        if (preg_match('/^[a-z0-9][a-z0-9-]*$/', $extensionId) !== 1) {
            throw new \InvalidArgumentException("Invalid local extension id [{$extensionId}].");
        }

        return 'pixely/' . $extensionId;
    }
}
