<?php

declare(strict_types=1);

namespace App\Core\Websites\Models;

/**
 * Immutable website navigation item DTO.
 */
final readonly class MenuItem implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $type,
        public string $title,
        public ?string $targetUrl = null,
        public ?string $pageId = null,
        public ?string $extensionId = null,
        public ?string $slug = null,
        public int $sortOrder = 0,
        public bool $active = true,
    ) {
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
