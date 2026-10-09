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
     * Each definition is expected to be `['file' => '<route file path>']`.
     *
     * Typed loosely on purpose: the definitions come from extension code, so
     * Core validates every entry instead of trusting the shape.
     *
     * @return list<array<string, mixed>>
     */
    public function routes(): array;
}
