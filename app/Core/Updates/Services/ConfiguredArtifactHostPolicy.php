<?php

declare(strict_types=1);

namespace App\Core\Updates\Services;

use App\Core\Updates\Contracts\ArtifactHostPolicyInterface;
use App\Core\Updates\Enums\ArtifactProvider;

/** Checks HTTPS artifact URLs against a strict per-provider host allow-list. */
final readonly class ConfiguredArtifactHostPolicy implements ArtifactHostPolicyInterface
{
    /** @param array<string, list<string>> $hosts Provider value mapped to exact allowed hostnames. */
    public function __construct(private array $hosts)
    {
    }

    public function allows(ArtifactProvider $provider, string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        $host = strtolower(rtrim((string) $parts['host'], '.'));
        $allowed = array_map(static fn (string $item): string => strtolower(rtrim($item, '.')), $this->hosts[$provider->value] ?? []);

        return in_array($host, $allowed, true);
    }
}
