<?php

declare(strict_types=1);

namespace App\Core\Api\Response;

use Illuminate\Http\JsonResponse;

/**
 * Creates a standard API collection response.
 *
 * Collection responses use the following structure:
 *
 * {
 *     "data": [],
 *     "meta": {}
 * }
 */
final class ApiCollectionResponse
{
    /**
     * Create a JSON response containing a collection and metadata.
     *
     * @param iterable<mixed> $data Collection returned by the API.
     * @param array<string, mixed> $meta Pagination or collection metadata.
     */
    public function response(
        iterable $data,
        array $meta,
        int $status = 200,
    ): JsonResponse {
        return response()->json(
            [
                'data' => $data,
                'meta' => $meta,
            ],
            $status,
        );
    }
}
