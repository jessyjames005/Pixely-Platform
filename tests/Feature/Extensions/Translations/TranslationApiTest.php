<?php

declare(strict_types=1);

use App\JsonApi\V1\DocumentId;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

$jsonApiHeaders = [
    'Accept' => 'application/vnd.api+json',
    'Content-Type' => 'application/vnd.api+json',
];

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'translations.strings.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'translations.strings.manage', 'guard_name' => 'web']);

    // Snapshot the real Gallery lang files so this test suite is
    // self-contained and never depends on their current on-disk
    // state (or a manual `git checkout` between runs).
    $this->enPath = base_path('app/Extensions/Gallery/lang/en/gallery.php');
    $this->frPath = base_path('app/Extensions/Gallery/lang/fr/gallery.php');

    $this->originalEn = file_get_contents($this->enPath);
    $this->originalFr = file_get_contents($this->frPath);

    file_put_contents($this->enPath, <<<'PHP'
<?php

declare(strict_types=1);

return [
    'object' => [
        'photo' => [
            'title' => [
                'label' => 'Title',
                'hint' => 'The photo title',
            ],
        ],
    ],
    'title' => [
        'gallery_list' => 'Gallery',
    ],
    'action' => [
        'upload' => 'Upload a photo',
    ],
    'msg' => ['confirm_delete_photo' => 'Delete this photo?'],
];

PHP);

    file_put_contents($this->frPath, <<<'PHP'
<?php

declare(strict_types=1);

return [
    'object' => [
        'photo' => [
            'title' => [
                'label' => 'Titre',
            ],
        ],
    ],
    'title' => [
        'gallery_list' => 'Galerie',
    ],
];

PHP);

    if (function_exists('opcache_invalidate')) {
        opcache_invalidate($this->enPath, true);
        opcache_invalidate($this->frPath, true);
    }
});

afterEach(function () {
    file_put_contents($this->enPath, $this->originalEn);
    file_put_contents($this->frPath, $this->originalFr);

    if (function_exists('opcache_invalidate')) {
        opcache_invalidate($this->enPath, true);
        opcache_invalidate($this->frPath, true);
    }
});

it('requires authentication and view permission to list translation modules', function () use ($jsonApiHeaders) {
    $this->json('GET', '/api/v1/translation-modules', [], $jsonApiHeaders)
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);

    $this->actingAs(User::factory()->create());
    $this->json('GET', '/api/v1/translation-modules', [], $jsonApiHeaders)
        ->assertForbidden()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('lists module resources with stable IDs and allowlisted attributes', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.view');
    $this->actingAs($user);

    $response = $this->json('GET', '/api/v1/translation-modules', [], $jsonApiHeaders);

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonFragment(['type' => 'translation-modules', 'id' => 'gallery'])
        ->assertJsonPath('meta.total', count($response->json('data')));

    $gallery = collect($response->json('data'))->firstWhere('id', 'gallery');
    expect($gallery['attributes'])->toHaveKeys(['locales'])
        ->and(array_keys($gallery['attributes']))->toBe(['locales']);
});

it('lists group resources using JSON:API filters and deterministic IDs', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.view');
    $this->actingAs($user);

    $this->json('GET', '/api/v1/translation-groups?filter[module]=gallery&filter[locale]=en', [], $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.0.type', 'translation-groups')
        ->assertJsonPath('data.0.id', DocumentId::encode('gallery', 'en', 'gallery'))
        ->assertJsonPath('data.0.attributes.module', 'gallery')
        ->assertJsonPath('data.0.attributes.group', 'gallery')
        ->assertJsonPath('data.0.attributes.locale', 'en');
});

it('returns translation strings as resource objects with comparison metadata', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.view');
    $this->actingAs($user);

    $stringsUrl = '/api/v1/translation-strings?filter[module]=gallery&filter[group]=gallery'
        . '&filter[locale]=fr&filter[reference]=en';
    $response = $this->json(
        'GET',
        $stringsUrl,
        [],
        $jsonApiHeaders,
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.0.type', 'translation-strings')
        ->assertJsonPath('meta.module', 'gallery')
        ->assertJsonPath('meta.group', 'gallery')
        ->assertJsonPath('meta.locale', 'fr')
        ->assertJsonPath('meta.reference', 'en')
        ->assertJsonPath('meta.completion', 40);

    $byKey = collect($response->json('data'))->keyBy('attributes.key');
    expect($byKey->has('object.photo.title.label'))->toBeTrue()
        ->and($byKey['object.photo.title.label']['attributes']['reference'])->toBe('Title')
        ->and($byKey['object.photo.title.label']['attributes']['target'])->toBe('Titre')
        ->and($byKey['object.photo.title.hint']['attributes']['suspect'])->toBeTrue()
        ->and($byKey->has('action.upload'))->toBeTrue();
});

it('paginates translation strings with JSON:API page parameters', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.view');
    $this->actingAs($user);

    $this->json(
        'GET',
        '/api/v1/translation-strings?filter[module]=gallery&filter[group]=gallery&page[size]=2&page[number]=1',
        [],
        $jsonApiHeaders,
    )
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.page.currentPage', 1)
        ->assertJsonPath('meta.page.perPage', 2)
        ->assertJsonPath('meta.page.total', 5)
        ->assertJsonStructure(['links' => ['first', 'last', 'next']]);
});

it('serves the public locale catalog as a JSON:API resource', function () use ($jsonApiHeaders) {
    $this->json('GET', '/api/v1/translation-catalogs/fr', [], $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.type', 'translation-catalogs')
        ->assertJsonPath('data.id', 'fr')
        ->assertJsonPath('data.attributes.locale', 'fr')
        ->assertJsonPath('data.attributes.catalog.gallery.gallery.title.gallery_list', 'Galerie');
});

it('returns canonical not-found errors for an unknown module', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.view');
    $this->actingAs($user);

    $this->json(
        'GET',
        '/api/v1/translation-strings?filter[module]=does-not-exist&filter[group]=gallery',
        [],
        $jsonApiHeaders,
    )
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('requires manage permission to update a translation group', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.view');
    $this->actingAs($user);
    $id = DocumentId::encode('gallery', 'fr', 'gallery');

    $this->json('PATCH', "/api/v1/translation-groups/{$id}", [
        'data' => [
            'type' => 'translation-groups',
            'id' => $id,
            'attributes' => ['locale' => 'fr', 'translations' => ['action.upload' => 'Envoyer']],
        ],
    ], $jsonApiHeaders)
        ->assertForbidden()
        ->assertHeader('Content-Type', 'application/vnd.api+json');
});

it('updates dotted translation keys and returns the updated group resource', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.manage');
    $this->actingAs($user);
    $id = DocumentId::encode('gallery', 'fr', 'gallery');

    $response = $this->json('PATCH', "/api/v1/translation-groups/{$id}", [
        'data' => [
            'type' => 'translation-groups',
            'id' => $id,
            'attributes' => [
                'locale' => 'fr',
                'translations' => [
                    'object.photo.title.label' => 'Titre mis a jour',
                    'action.upload' => 'Envoyer une photo',
                ],
            ],
        ],
    ], $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.type', 'translation-groups')
        ->assertJsonPath('data.id', $id)
        ->assertJsonPath('data.attributes.saved', true);

    expect($response->json('data.attributes.translations')['action.upload'])->toBe('Envoyer une photo');

    expect(file_get_contents($this->frPath))->toContain('Titre mis a jour')
        ->and(file_get_contents($this->frPath))->toContain('Envoyer une photo');
});

it('rejects non-string translation values using canonical JSON:API errors', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.manage');
    $this->actingAs($user);
    $id = DocumentId::encode('gallery', 'fr', 'gallery');

    $this->json('PATCH', "/api/v1/translation-groups/{$id}", [
        'data' => [
            'type' => 'translation-groups',
            'id' => $id,
            'attributes' => ['locale' => 'fr', 'translations' => ['action.upload' => 42]],
        ],
    ], $jsonApiHeaders)
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'detail', 'source' => ['pointer']]]]);
});

it('enforces JSON:API media negotiation and resource type matching', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $user->givePermissionTo(['translations.strings.view', 'translations.strings.manage']);
    $this->actingAs($user);

    $this->json('GET', '/api/v1/translation-modules', [], ['Accept' => 'application/json'])
        ->assertNotAcceptable()
        ->assertHeader('Content-Type', 'application/vnd.api+json');

    $id = DocumentId::encode('gallery', 'fr', 'gallery');
    $this->json('PATCH', "/api/v1/translation-groups/{$id}", [
        'data' => ['type' => 'wrong-type', 'id' => $id, 'attributes' => []],
    ], $jsonApiHeaders)
        ->assertStatus(409)
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'detail']]]);
});
