<?php

declare(strict_types=1);

namespace App\JsonApi\V1;

use JsonException;

final class DocumentId
{
    public static function encode(string ...$parts): string
    {
        return rtrim(strtr(base64_encode(json_encode($parts, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }

    /** @return list<string>|null */
    public static function decode(string $id, int $expectedParts): ?array
    {
        try {
            $json = base64_decode(strtr($id, '-_', '+/'), true);
            if ($json === false) {
                return null;
            }

            $parts = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($parts) || ! array_is_list($parts) || count($parts) !== $expectedParts) {
            return null;
        }

        foreach ($parts as $part) {
            if (! is_string($part) || $part === '') {
                return null;
            }
        }

        return self::encode(...$parts) === $id ? $parts : null;
    }
}
