<?php

declare(strict_types=1);

$jsonApiHeaders = [
    'Accept' => 'application/vnd.api+json',
    'Content-Type' => 'application/vnd.api+json',
];

it('serves the merged translation catalog for a locale without authentication', function () use ($jsonApiHeaders) {
    $response = $this->json('GET', '/api/v1/translation-catalogs/en', [], $jsonApiHeaders);

    $response
        ->assertOk()
        ->assertJsonPath('data.type', 'translation-catalogs')
        ->assertJsonPath('data.id', 'en')
        ->assertJsonPath('data.attributes.locale', 'en')
        ->assertJsonPath('data.attributes.catalog.core.common.action.save', 'Save')
        ->assertJsonPath('data.attributes.catalog.core.entities.object.user.name.label', 'Name')
        ->assertJsonPath('data.attributes.catalog.core.roles.title.roles_list', 'Roles List');
});

it('serves French translations distinctly from English', function () use ($jsonApiHeaders) {
    $response = $this->json('GET', '/api/v1/translation-catalogs/fr', [], $jsonApiHeaders);

    $response
        ->assertOk()
        ->assertJsonPath('data.type', 'translation-catalogs')
        ->assertJsonPath('data.id', 'fr')
        ->assertJsonPath('data.attributes.locale', 'fr')
        ->assertJsonPath('data.attributes.catalog.core.common.action.save', 'Enregistrer')
        ->assertJsonPath('data.attributes.catalog.core.roles.title.roles_list', 'Liste des rôles');
});

it('returns an empty catalog for a locale with no lang files, rather than an error', function () use ($jsonApiHeaders) {
    $response = $this->json('GET', '/api/v1/translation-catalogs/de', [], $jsonApiHeaders);

    $response
        ->assertOk()
        ->assertJsonPath('data.type', 'translation-catalogs')
        ->assertJsonPath('data.id', 'de')
        ->assertJsonPath('data.attributes.catalog', []);
});
