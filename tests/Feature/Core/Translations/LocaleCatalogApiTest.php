<?php

declare(strict_types=1);

it('serves the merged translation catalog for a locale without authentication', function () {
    $response = $this->getJson('/api/v1/locales/en');

    $response
        ->assertOk()
        ->assertJsonPath('data.core.common.action.save', 'Save')
        ->assertJsonPath('data.core.entities.object.user.name', 'Name')
        ->assertJsonPath('data.core.roles.title.roles_list', 'Roles List');
});

it('serves French translations distinctly from English', function () {
    $response = $this->getJson('/api/v1/locales/fr');

    $response
        ->assertOk()
        ->assertJsonPath('data.core.common.action.save', 'Enregistrer')
        ->assertJsonPath('data.core.roles.title.roles_list', 'Liste des rôles');
});

it('returns an empty catalog for a locale with no lang files, rather than an error', function () {
    $response = $this->getJson('/api/v1/locales/de');

    $response->assertOk();
});
