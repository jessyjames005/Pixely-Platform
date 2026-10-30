<?php

declare(strict_types=1);

namespace App\Core\Updates\Contracts;

/**
 * Maps a legacy local extension id to its official registry package id.
 */
interface ExtensionPackageIdResolverInterface
{
    public function resolve(string $extensionId): string;
}
