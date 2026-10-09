<?php

declare(strict_types=1);

namespace App\Core\Websites\Models;

/**
 * Immutable website menu DTO.
 */
final readonly class Menu implements \JsonSerializable
{
    /** @param MenuItem[] $items */
    public function __construct(
        public string $id,
        public string $name,
        public string $code,
        public array $items = [],
    ) {
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
