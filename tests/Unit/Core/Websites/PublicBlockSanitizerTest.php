<?php

declare(strict_types=1);

use App\Core\Websites\Services\PublicBlockSanitizer;

it('keeps only supported public blocks with safe fields', function (): void {
    $sanitizer = new PublicBlockSanitizer();

    expect($sanitizer->sanitize([
        ['type' => 'heading', 'text' => ' Welcome '],
        ['type' => 'paragraph', 'text' => 'About us'],
        ['type' => 'cta', 'text' => 'Contact', 'href' => '/contact'],
        ['type' => 'custom-html', 'text' => '<script>alert(1)</script>'],
        ['type' => 'cta', 'text' => 'Unsafe', 'href' => 'javascript:alert(1)'],
        ['type' => 'cta', 'text' => 'External unsafe', 'href' => '//evil.example'],
        'malformed',
    ]))->toBe([
        ['type' => 'heading', 'text' => 'Welcome'],
        ['type' => 'paragraph', 'text' => 'About us'],
        ['type' => 'cta', 'text' => 'Contact', 'href' => '/contact'],
    ]);
});

it('allows absolute http and https URLs but rejects control characters', function (): void {
    $sanitizer = new PublicBlockSanitizer();

    expect($sanitizer->sanitize([
        ['type' => 'cta', 'text' => 'Secure', 'href' => 'https://example.com/path'],
        ['type' => 'cta', 'text' => 'Bad', 'href' => "https://example.com/\npath"],
    ]))->toBe([
        ['type' => 'cta', 'text' => 'Secure', 'href' => 'https://example.com/path'],
    ]);
});
