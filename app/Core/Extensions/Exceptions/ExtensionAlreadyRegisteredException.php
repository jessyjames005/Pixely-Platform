<?php

declare(strict_types=1);

namespace App\Core\Extensions\Exceptions;

use RuntimeException;

/**
 * Thrown when an extension is already registered.
 */
final class ExtensionAlreadyRegisteredException extends RuntimeException
{
}
