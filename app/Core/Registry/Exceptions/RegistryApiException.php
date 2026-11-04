<?php

declare(strict_types=1);

namespace App\Core\Registry\Exceptions;

use RuntimeException;

/**
 * An expected Registry API error with its public Problem Details identity.
 */
final class RegistryApiException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $problemCode,
        public readonly string $problemTitle,
        string $detail,
    ) {
        parent::__construct($detail);
    }

    public static function invalidCursor(): self
    {
        return new self(
            400,
            'invalid_cursor',
            'Invalid cursor',
            'The cursor is malformed or does not match these filters.',
        );
    }

    public static function packageNotFound(): self
    {
        return new self(404, 'package_not_found', 'Package not found', 'No package matches the requested type and ID.');
    }

    public static function releaseNotFound(): self
    {
        return new self(
            404,
            'release_not_found',
            'Release not found',
            'No release matches the requested package and version.',
        );
    }
}
