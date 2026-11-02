<?php

declare(strict_types=1);

namespace App\Core\Updates\Exceptions;

/** Raised when the official Pixely Registry cannot return a valid API response. */
final class RegistryApiException extends \RuntimeException
{
}
