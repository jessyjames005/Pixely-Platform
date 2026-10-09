<?php

declare(strict_types=1);

namespace App\Core\Extensions\Capabilities\Contracts;

/**
 * Declares reusable block metadata for the surfaces supported by an extension.
 *
 * Each block must define a stable local `id` and non-empty `label`. Optional
 * `surfaces` restricts where it can be used (public, user, admin), and `schema`
 * describes the block's configurable fields. Block output must be rendered by
 * a trusted surface renderer; declarations must never contain executable HTML.
 */
interface ExtensionBlocksInterface
{
    /**
     * @return list<array{id:string, label:string, surfaces?:list<string>, schema?:array<string, mixed>}>
     */
    public function blocks(): array;
}
