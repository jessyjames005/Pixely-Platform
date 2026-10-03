<?php

declare(strict_types=1);

it('renders a strict JSON:API error envelope for unknown API routes', function () {
    $response = $this->get('/api/v1/gallery/this-photo-does-not-exist');

    $response->assertStatus(404);

    expect($response->headers->get('Content-Type'))->toContain('application/vnd.api+json')
        ->and($response->json('jsonapi'))->toBe('1.1')
        ->and($response->json('errors'))->toBeArray()
        ->and($response->json('errors'))->not->toBeEmpty()
        ->and($response->json('errors.0.status'))->toBe('404')
        ->and($response->json('errors.0.code'))->toBe('RESOURCE_NOT_FOUND')
        ->and($response->json('errors.0.title'))->toBe('The requested resource was not found.')
        ->and($response->json('errors.0.detail'))->toBe('The requested resource was not found.');
});
