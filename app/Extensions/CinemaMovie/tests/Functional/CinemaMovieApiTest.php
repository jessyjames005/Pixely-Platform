<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

it('requires authentication to list CinemaMovie items', function () {
    // Enable the cinema-movie extension before running this API feature test.
    $this->getJson('/api/v1/cinema-movie')->assertUnauthorized();
});
