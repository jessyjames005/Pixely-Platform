<?php


declare(strict_types=1);

namespace App\Core\Websites\Models;

/**
 * Base model for website pages.
 *
 * Represents a static or dynamic page on the public/user website.
 * Contains basic metadata, content (blocks) and SEO settings.
 */
final readonly class PageModel
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
}