<?php

declare(strict_types=1);

namespace App\Core\Websites\Models;

/**
 * Immutable website page DTO exposed by the Website Engine.
 *
 * Carries publication timestamps so callers can distinguish
 * draft, scheduled and published states.
 */
final readonly class PageModel implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $slug,
        public string $title,
        public string $status,
        public string $template,
        public \DateTimeInterface|string|null $publishedAt = null,
        public \DateTimeInterface|string|null $publishAt = null,
        public array $seo = [],
        public array $blocks = [],
    ) {
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
