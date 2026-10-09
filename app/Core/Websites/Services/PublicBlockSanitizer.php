<?php

declare(strict_types=1);

namespace App\Core\Websites\Services;

/**
 * Filters stored page blocks to the small, non-executable public block contract.
 *
 * Unknown block types and malformed data are omitted. URLs are restricted to
 * relative paths and safe web/mail schemes so persisted content cannot inject
 * javascript: or data: navigation into the public website.
 */
final class PublicBlockSanitizer
{
    private const ALLOWED_TYPES = ['heading', 'paragraph', 'cta'];
    private const MAX_TEXT_LENGTH = 10000;

    /**
     * @param array<mixed> $blocks
     * @return list<array{type:string,text:string,href?:string}>
     */
    public function sanitize(array $blocks): array
    {
        $result = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = $block['type'] ?? null;
            $text = $block['text'] ?? null;

            if (! is_string($type) || ! in_array($type, self::ALLOWED_TYPES, true) || ! is_string($text)) {
                continue;
            }

            $text = trim(mb_substr($text, 0, self::MAX_TEXT_LENGTH));
            if ($text === '') {
                continue;
            }

            if ($type === 'cta') {
                $href = $this->safeHref($block['href'] ?? null);
                if ($href === null) {
                    continue;
                }

                $result[] = ['type' => $type, 'text' => $text, 'href' => $href];
                continue;
            }

            $result[] = ['type' => $type, 'text' => $text];
        }

        return $result;
    }

    private function safeHref(mixed $href): ?string
    {
        if (! is_string($href)) {
            return null;
        }

        $href = trim($href);
        if ($href === '' || preg_match('/[\x00-\x20\\\\]/', $href)) {
            return null;
        }

        // Internal relative links are allowed; protocol-relative URLs are not.
        if (str_starts_with($href, '/') && ! str_starts_with($href, '//')) {
            return $href;
        }

        $scheme = parse_url($href, PHP_URL_SCHEME);
        if (! is_string($scheme) || ! in_array(strtolower($scheme), ['https', 'http', 'mailto'], true)) {
            return null;
        }

        return $href;
    }
}
