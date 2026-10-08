<?php

declare(strict_types=1);

namespace App\Core\Extensions\Capabilities\Contracts;

/**
 * Declares backend route files owned by an extension.
 *
 * Core remains responsible for registration, API prefixing and the
 * authoritative Pixely API surface middleware.
 */
interface ExtensionRoutesInterface
{
    /**
     * @return list<array{file:string}>
     */
    public function routes(): array;
}
