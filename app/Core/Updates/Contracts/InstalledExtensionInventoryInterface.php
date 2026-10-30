<?php

declare(strict_types=1);

namespace App\Core\Updates\Contracts;

use App\Core\Updates\DTO\InstalledExtension;

/**
 * Provides read-only inventory of installed Pixely extensions.
 */
interface InstalledExtensionInventoryInterface
{
    /** @return list<InstalledExtension> */
    public function all(): array;

    public function find(string $extensionId): ?InstalledExtension;
}
