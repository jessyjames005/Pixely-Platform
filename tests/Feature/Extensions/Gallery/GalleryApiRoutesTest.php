<?php

declare(strict_types=1);

use App\Extensions\Gallery\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('filters photos by title case insensitively', function () {
    Photo::factory()->create([
        'title' => 'Sunset',
    ]);

    Photo::factory()->create([
        'title' => 'Mountain',
    ]);

    $response = $this->json(
        'GET',
        '/api/v1/photos?filter[title]=sunset',
        [],
        [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ],
    );

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.0.attributes.title', 'Sunset');
});

it('sorts photos by title descending', function () {
    Photo::factory()->create([
        'title' => 'Sunset',
    ]);

    Photo::factory()->create([
        'title' => 'Mountain',
    ]);

    Photo::factory()->create([
        'title' => 'Beach',
    ]);

    $response = $this->json(
        'GET',
        '/api/v1/photos?sort=-title',
        [],
        [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ],
    );

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.0.attributes.title', 'Sunset')
        ->assertJsonPath('data.1.attributes.title', 'Mountain')
        ->assertJsonPath('data.2.attributes.title', 'Beach');
});
