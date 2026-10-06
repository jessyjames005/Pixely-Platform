<?php


declare(strict_types=1);

namespace App\Core\Websites\Models;

/**
 * Navigation menu item.
 *
 * Represents a single element in a menu, which can link to a page,
 * an extension route, or an external URL.
 */
final readonly class MenuItem
{
    public function __construct(
        public string $id,
        public string $type,
        public string $title,
        public ?string $targetUrl,
        public ?string $pageId,
        public ?string $extensionId,
        public ?string $slug,
        public int $sortOrder = 0,
        public bool $active = true,
    ) {}
}

/**
 * Navigation menu.
 *
 * Contains a collection of organized menu items.
 * Used for website menus: homepage, footer, sidebar, etc.
 */
final readonly class Menu
{
    public function __construct(
        public string $id,
        public string $name,
        public string $code,
        public array $items,
    ) {}
}