<?php

declare(strict_types=1);

namespace App\Core\Extensions\Capabilities\Contracts;

/**
 * Declares routes owned by an extension.
 *
 * Route registration remains owned by Core; extensions only describe the
 * routes they expose. This keeps the Platform in control of middleware,
 * surfaces and route naming conventions.
 */
interface ExtensionRoutesInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function routes(): array;
}
