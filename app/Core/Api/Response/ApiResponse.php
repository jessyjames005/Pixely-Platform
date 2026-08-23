<?php

declare(strict_types=1);

namespace App\Core\Api\Response;

use Illuminate\Http\JsonResponse;

/**
 * Creates a standard API success response.
 *
 * All successful single-resource API responses use the
 * following structure:
 *
 * {
 *     "data": {}
 * }
 */
final class ApiResponse
{
    /**
     * Create a JSON response containing a single resource.
     *
     * @param mixed $data Resource returned by the API.
     */
    public function response(
        mixed $data,
        int $status = 200,
    ): JsonResponse {
        return response()->json(
            [
                'data' => $data,
            ],
            $status,
        );
    }
}
