<?php

declare(strict_types=1);

namespace App\Core\Extensions\Capabilities\Contracts;

/**
 * Declares blocks that an extension can render on Platform surfaces.
 */
interface ExtensionBlocksInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function blocks(): array;
}
