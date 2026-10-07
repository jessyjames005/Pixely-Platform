<?php

declare(strict_types=1);

namespace App\Core\Surface\Enum;

use InvalidArgumentException;

enum Surface: string
{
    case PUBLIC = 'public';
    case USER = 'user';
    case ADMIN = 'admin';
    case API = 'api';

    /**
     * Create a surface from its route/manifest value.
     *
     * @throws InvalidArgumentException When the value is not a supported surface.
     */
    public static function fromValue(string $value): self
    {
        return self::tryFrom($value)
            ?? throw new InvalidArgumentException("Unsupported Pixely surface [{$value}].");
    }
}
