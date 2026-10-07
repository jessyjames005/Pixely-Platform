<?php

declare(strict_types=1);

namespace App\Core\Websites\Models;

/**
 * Immutable website page DTO exposed by the Website Engine.
 */
final readonly class PageModel implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $slug,
        public string $title,
        public string $status,
        public string $template,
        public array $seo = [],
        public array $blocks = [],
    ) {}

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
