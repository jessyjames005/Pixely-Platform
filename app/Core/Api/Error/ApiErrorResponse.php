<?php

declare(strict_types=1);

namespace App\Core\Api\Error;

use Illuminate\Http\JsonResponse;

/**
 * Creates standard HTTP responses for API errors.
 */
final class ApiErrorResponse
{
    /**
     * Create a JSON response from an API error.
     */
    public function response(
        ApiError $error,
        int $status,
    ): JsonResponse {
        return response()->json(
            $error->toArray(),
            $status,
        );
    }
}
