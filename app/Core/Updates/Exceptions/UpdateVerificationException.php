<?php

declare(strict_types=1);

namespace App\Core\Updates\Exceptions;

/**
 * Raised when an update artifact fails integrity or authenticity verification.
 */
final class UpdateVerificationException extends \RuntimeException
{
}
