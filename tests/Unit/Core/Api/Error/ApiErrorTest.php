<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Api\Error;

use App\Core\Api\Error\ApiError;

it('creates an API error', function () {
    $error = new ApiError(
        code: 'RESOURCE_NOT_FOUND',
        message: 'The requested resource was not found.',
    );

    expect($error->code)
        ->toBe('RESOURCE_NOT_FOUND');

    expect($error->message)
        ->toBe('The requested resource was not found.');

    expect($error->details)
        ->toBeNull();
});

it('converts an API error to an array', function () {
    $error = new ApiError(
        code: 'RESOURCE_NOT_FOUND',
        message: 'The requested resource was not found.',
    );

    expect($error->toArray())
        ->toBe([
            'error' => [
                'code' => 'RESOURCE_NOT_FOUND',
                'message' => 'The requested resource was not found.',
            ],
        ]);
});

it('includes error details when provided', function () {
    $error = new ApiError(
        code: 'VALIDATION_ERROR',
        message: 'The given data was invalid.',
        details: [
            'title' => [
                'The title field is required.',
            ],
        ],
    );

    expect($error->toArray())
        ->toBe([
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The given data was invalid.',
                'details' => [
                    'title' => [
                        'The title field is required.',
                    ],
                ],
            ],
        ]);
});
