<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

/**
 * Thrown when the Tuleap server cannot be reached at all (network error,
 * timeout, VPN down). Renders as a 503 so the frontend can show a
 * dedicated "Tuleap unreachable" state instead of a generic error.
 */
final class TuleapUnavailableException extends RuntimeException
{
    public function __construct(string $message = 'Tuleap inaccessible — vérifiez le VPN', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'errors' => [[
                'status' => '503',
                'code' => 'TULEAP_UNAVAILABLE',
                'title' => 'Service Unavailable',
                'detail' => $this->getMessage(),
                'meta' => ['vpn' => true],
            ]],
        ], 503, ['Content-Type' => 'application/vnd.api+json']);
    }
}
