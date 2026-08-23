<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Api\Response;

use App\Core\Api\Response\ApiCollectionResponse;
use Tests\TestCase;

final class ApiCollectionResponseTest extends TestCase
{
    /**
     * Ensure a collection response contains data and metadata.
     */
    public function test_it_creates_a_standard_collection_response(): void
    {
        $response = (new ApiCollectionResponse())->response(
            [
                ['id' => 1],
                ['id' => 2],
            ],
            [
                'current_page' => 1,
                'last_page' => 5,
                'per_page' => 20,
                'total' => 95,
            ],
        );

        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame(
            [
                'data' => [
                    ['id' => 1],
                    ['id' => 2],
                ],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 5,
                    'per_page' => 20,
                    'total' => 95,
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
        $response = (new ApiCollectionResponse())->response(
            [],
            [],
            206,
        );

        $this->assertSame(206, $response->getStatusCode());
    }
}
