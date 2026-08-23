<?php

declare(strict_types=1);

use App\Core\Api\Error\ApiError;
use App\Core\Api\Error\ApiErrorResponse;
use Illuminate\Http\JsonResponse;

it('creates a JSON response from an API error', function () {
    $error = new ApiError(
        code: 'RESOURCE_NOT_FOUND',
        message: 'The requested resource was not found.',
    );

    $response = (new ApiErrorResponse())->response(
        error: $error,
        status: 404,
    );

    expect($response)
        ->toBeInstanceOf(JsonResponse::class);

    expect($response->getStatusCode())
        ->toBe(404);

    expect($response->getData(true))
        ->toBe([
            'error' => [
                'code' => 'RESOURCE_NOT_FOUND',
                'message' => 'The requested resource was not found.',
            ],
        ]);
});

it('returns the standard format for an API exception', function () {
    $response = $this->getJson('/api/non-existent-endpoint');

    $response
        ->assertStatus(404)
        ->assertJsonStructure([
            'error' => [
                'code',
                'message',
            ],
        ]);
});
