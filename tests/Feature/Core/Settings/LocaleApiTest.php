<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists available locales without authentication', function () {
    $response = $this->getJson('/api/v1/locales');

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['code', 'label']],
            'meta' => ['default'],
        ])
        ->assertJsonPath('meta.default', 'en');
});
