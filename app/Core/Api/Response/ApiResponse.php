<?php

declare(strict_types=1);

namespace App\Core\Api\Response;

use Illuminate\Http\JsonResponse;

/**
 * Creates consistent Pixely API response envelopes.
 */
final class ApiResponse
{
    /**
     * Return a single-resource response.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     */
    public function response(array $data, array $meta = []): JsonResponse
    {
        $payload = ['data' => $data];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload);
    }
}
