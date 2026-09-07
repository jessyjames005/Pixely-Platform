<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

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
                'hint' => 'The photo\'s title',
            ],
        ],
    ],
    'title' => [
        'gallery_list' => 'Gallery',
    ],
    'action' => [
        'upload' => 'Upload a photo',
    ],
    'msg' => [
        'confirm_delete_photo' => 'Delete this photo? This cannot be undone.',
    ],
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

it('requires authentication to list translation modules', function () {
    $response = $this->getJson('/api/v1/translations/modules');

    $response->assertStatus(401);
});

it('requires translations.strings.view to list modules', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->getJson('/api/v1/translations/modules');

    $response->assertStatus(403);
});

it('lists translatable modules for a user with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/translations/modules');

    $response
        ->assertOk()
        ->assertJsonFragment(['id' => 'gallery']);
});

it('lists groups for the gallery module', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/translations/gallery/groups?locale=en');

    $response
        ->assertOk()
        ->assertJsonFragment(['gallery']);
});

it('shows translation entries flattened with the naming convention keys', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/translations/gallery/gallery?locale=fr&reference=en');

    $response
        ->assertOk()
        ->assertJsonPath('data.module', 'gallery')
        ->assertJsonPath('data.group', 'gallery')
        ->assertJsonStructure(['data' => ['entries', 'completion']]);

    $byKey = collect($response->json('data.entries'))->keyBy('key');

    expect($byKey->has('object.photo.title.label'))->toBeTrue();
    expect($byKey->has('title.gallery_list'))->toBeTrue();
});

it('flags a reference-only key (missing translation) as suspect', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/translations/gallery/gallery?locale=fr&reference=en');

    $byKey = collect($response->json('data.entries'))->keyBy('key');

    // 'action.upload' and 'object.photo.title.hint' exist in en but not fr
    expect($byKey->get('action.upload')['suspect'] ?? false)->toBeTrue();
    expect($byKey->get('object.photo.title.hint')['suspect'] ?? false)->toBeTrue();
});

it('returns 404 for an unknown module', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/translations/does-not-exist/gallery');

    $response->assertNotFound();
});

it('requires translations.strings.manage (not just view) to update a group', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.view');
    $this->actingAs($user);

    $response = $this->putJson('/api/v1/translations/gallery/gallery', [
        'locale' => 'fr',
        'translations' => ['object.photo.title.label' => 'Titre'],
    ]);

    $response->assertStatus(403);
});

it('updates a translation group using dot-notation keys', function () {
    $user = User::factory()->create();

    // ✅ Créer la permission et la donner à l'utilisateur
    $permission = Permission::firstOrCreate(['name' => 'translations.strings.manage', 'guard_name' => 'web']);
    $user->givePermissionTo($permission);

    $this->actingAs($user);

    $response = $this->putJson('/api/v1/translations/gallery/gallery', [
        'locale' => 'fr',
        'translations' => [
            'object.photo.title.label' => 'Titre Updated',
            'action.upload' => 'Envoyer une photo',
        ],
    ]);

    $response->assertOk();
});
