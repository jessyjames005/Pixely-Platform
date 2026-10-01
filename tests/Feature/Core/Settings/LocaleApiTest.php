<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

$jsonApiHeaders = [
    'Accept' => 'application/vnd.api+json',
    'Content-Type' => 'application/vnd.api+json',
];

it('lists available locales without authentication', function () use ($jsonApiHeaders) {
    $response = $this->json('GET', '/api/v1/locales', [], $jsonApiHeaders);

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['type', 'id', 'attributes' => ['code', 'label']]],
            'meta' => ['default'],
        ])
        ->assertJsonPath('data.0.type', 'locales')
        ->assertJsonPath('data.0.id', 'en')
        ->assertJsonPath('meta.default', 'en');
});
