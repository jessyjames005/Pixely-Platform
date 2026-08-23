<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Api\Response;

use App\Core\Api\Response\ApiResponse;
use Tests\TestCase;

final class ApiResponseTest extends TestCase
{
    /**
     * Ensure a standard API response contains the data key.
     */
    public function test_it_creates_a_standard_success_response(): void
    {
        $response = (new ApiResponse())->response([
            'id' => 1,
            'title' => 'Sunset',
        ]);

        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame(
            [
                'data' => [
                    'id' => 1,
                    'title' => 'Sunset',
                ],
            ],
            $response->getData(true),
        );
    }

    /**
     * Ensure a custom HTTP status can be provided.
     */
    public function test_it_accepts_a_custom_status_code(): void
    {
        $response = (new ApiResponse())->response(
            ['id' => 1],
            201,
        );

        $this->assertSame(201, $response->getStatusCode());
    }
}
