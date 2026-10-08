<?php

declare(strict_types=1);

namespace App\Core\Extensions\Capabilities\Contracts;

/**
 * Declares navigation entries contributed by an extension.
 */
interface ExtensionNavigationInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function navigation(): array;
}
