<?php

declare(strict_types=1);

namespace App\Core\Updates\DTO;

/**
 * Shared validation helpers for the signed release-manifest DTOs.
 */
final class ReleaseManifestValue
{
    /** @param array<string, mixed> $data @param string[] $allowed */
    public static function assertKeys(array $data, array $allowed, string $context): void
    {
        $unknown = array_diff(array_keys($data), $allowed);

        if ($unknown !== []) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown %s field(s): %s.',
                $context,
                implode(', ', $unknown),
            ));
        }
    }

    /** @param array<string, mixed> $data */
    public static function requiredString(array $data, string $key, string $context): string
    {
        $value = $data[$key] ?? null;

        if (!is_string($value) || trim($value) === '') {
            throw new \InvalidArgumentException(sprintf('%s.%s must be a non-empty string.', $context, $key));
        }

        return $value;
    }

    public static function assertPackageId(string $value, string $context): void
    {
        if (preg_match('/^[a-z0-9][a-z0-9._-]*(?:\/[a-z0-9][a-z0-9._-]*)*$/', $value) !== 1) {
            throw new \InvalidArgumentException(sprintf('%s must be a canonical lowercase package identifier.', $context));
        }
    }

    public static function assertSemver(string $value, string $context): void
    {
        $pattern = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)'
            . '(?:-((?:0|[1-9]\d*|\d*[A-Za-z-][0-9A-Za-z-]*)'
            . '(?:\.(?:0|[1-9]\d*|\d*[A-Za-z-][0-9A-Za-z-]*))*))?'
            . '(?:\+([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?$/';

        if (preg_match($pattern, $value) !== 1) {
            throw new \InvalidArgumentException(sprintf('%s must follow Semantic Versioning 2.0.0.', $context));
        }
    }

    public static function assertHttpsUrl(string $value, string $context): void
    {
        $scheme = parse_url($value, PHP_URL_SCHEME);
        $host = parse_url($value, PHP_URL_HOST);

        if (
            filter_var($value, FILTER_VALIDATE_URL) === false
            || strtolower((string) $scheme) !== 'https'
            || !is_string($host)
            || $host === ''
        ) {
            throw new \InvalidArgumentException(sprintf('%s must be an absolute HTTPS URL.', $context));
        }
    }

    /** @return array<string, string> */
    public static function stringMap(mixed $value, string $context, bool $packageIds = false): array
    {
        if (!is_array($value) || ($value !== [] && array_is_list($value))) {
            throw new \InvalidArgumentException(sprintf('%s must be an object map.', $context));
        }

        $result = [];
        foreach ($value as $key => $constraint) {
            if (!is_string($key) || trim($key) === '' || !is_string($constraint) || trim($constraint) === '') {
                throw new \InvalidArgumentException(sprintf('%s must contain non-empty string keys and values.', $context));
            }

            if ($packageIds) {
                self::assertPackageId($key, sprintf('%s key', $context));
            }

            $result[$key] = $constraint;
        }

        return $result;
    }
}
