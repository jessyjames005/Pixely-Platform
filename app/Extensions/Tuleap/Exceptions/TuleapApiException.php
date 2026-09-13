<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Exceptions;

use App\Core\Api\Error\ApiError;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Thrown when Tuleap responds but with a non-2xx HTTP status
 * (invalid token, missing permission, unknown resource, ...).
 */
final class TuleapApiException extends RuntimeException
{
    public function __construct(string $message, private readonly int $status = 500)
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json(
            (new ApiError('TULEAP_API_ERROR', $this->getMessage()))->toArray(),
            $this->status,
        );
    }
}
