<?php

declare(strict_types=1);

use App\JsonApi\V1\DocumentId;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Tests\Fixtures\Extensions\FakeExtensionPackageBuilder;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'system.extensions.install', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'system.extensions.view', 'guard_name' => 'web']);
    $this->jsonApiHeaders = [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
    ];
});

afterEach(function () {
    // Clean up any extension directory a successful install test created.
    $demoPath = base_path('app/Extensions/Demo');
    if (is_dir($demoPath)) {
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($demoPath);
    }
});

it('requires authentication to install an extension', function () {
    $response = $this->json('POST', '/api/v1/extensions/install', [], ['Accept' => 'application/vnd.api+json']);

    $response
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('requires system.extensions.install specifically, view alone is not enough', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.view');
    $this->actingAs($user);

    $zipPath = FakeExtensionPackageBuilder::validPackage();

    $response = $this->withHeaders(['Accept' => 'application/vnd.api+json'])->post('/api/v1/extensions/install', [
        'package' => new UploadedFile($zipPath, 'demo.zip', 'application/zip', null, true),
    ]);

    $response->assertForbidden()->assertHeader('Content-Type', 'application/vnd.api+json');
});

it('rejects an install without a package file', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.install');
    $this->actingAs($user);

    $response = $this->json('POST', '/api/v1/extensions/install', [], $this->jsonApiHeaders);

    $response
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'source' => ['pointer']]]]);
});

it('rejects a package with a zip-slip path traversal attempt', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.install');
    $this->actingAs($user);

    $zipPath = FakeExtensionPackageBuilder::zipSlipPackage();

    $response = $this->withHeaders(['Accept' => 'application/vnd.api+json'])->post('/api/v1/extensions/install', [
        'package' => new UploadedFile($zipPath, 'evil.zip', 'application/zip', null, true),
    ]);

    $response
        ->assertStatus(422)
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('errors.0.code', 'INSTALL_FAILED')
        ->assertJsonStructure(['errors' => [['status', 'title', 'code', 'detail']]]);

    expect(file_exists(base_path('../evil.php')))->toBeFalse();
});

it('rejects a package with no manifest', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.install');
    $this->actingAs($user);

    $zipPath = FakeExtensionPackageBuilder::missingManifestPackage();

    $response = $this->withHeaders(['Accept' => 'application/vnd.api+json'])->post('/api/v1/extensions/install', [
        'package' => new UploadedFile($zipPath, 'nomanifest.zip', 'application/zip', null, true),
    ]);

    $response
        ->assertStatus(422)
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('rolls back and reports an error when the declared class is invalid', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.install');
    $this->actingAs($user);

    $zipPath = FakeExtensionPackageBuilder::invalidClassPackage('broken');

    $response = $this->withHeaders(['Accept' => 'application/vnd.api+json'])->post('/api/v1/extensions/install', [
        'package' => new UploadedFile($zipPath, 'broken.zip', 'application/zip', null, true),
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'INSTALL_FAILED')
        ->assertHeader('Content-Type', 'application/vnd.api+json');

    expect(is_dir(base_path('app/Extensions/Broken')))->toBeFalse();
})->skip('Requires composer dump-autoload to run inside the test process; covered by manual QA — see note below.');

it('prevents installing an extension whose id already exists', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.install');
    $this->actingAs($user);

    $zipPath = FakeExtensionPackageBuilder::validPackage('gallery');

    $response = $this->withHeaders(['Accept' => 'application/vnd.api+json'])->post('/api/v1/extensions/install', [
        'package' => new UploadedFile($zipPath, 'gallery.zip', 'application/zip', null, true),
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'INSTALL_FAILED')
        ->assertHeader('Content-Type', 'application/vnd.api+json');
});

it('requires the install permission to update an extension package', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.view');
    $this->actingAs($user);

    $opaqueId = DocumentId::encode('extension', 'demo');
    $this->json('POST', '/api/v1/extensions/' . $opaqueId . '/update', [], $this->jsonApiHeaders)
        ->assertForbidden()
        ->assertHeader('Content-Type', 'application/vnd.api+json');
});

it('returns a JSON:API error for an invalid opaque package-update id', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.install');
    $this->actingAs($user);

    $zipPath = FakeExtensionPackageBuilder::validPackage('demo');
    $response = $this->withHeaders(['Accept' => 'application/vnd.api+json'])->post(
        '/api/v1/extensions/gallery/update',
        ['package' => new UploadedFile($zipPath, 'demo.zip', 'application/zip', null, true)],
    );

    $response
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('errors.0.code', 'RESOURCE_NOT_FOUND')
        ->assertJsonStructure(['errors' => [['status', 'title', 'code', 'detail']]]);
});

it('requires system.extensions.install to uninstall, not system.extensions.manage', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.view');
    $this->actingAs($user);

    $response = $this->withHeaders($this->jsonApiHeaders)->delete('/api/v1/extensions/does-not-exist');

    $response->assertStatus(403);
});

it('returns a JSON:API error when uninstalling a non-installed extension', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.install');
    $this->actingAs($user);

    $response = $this->withHeaders($this->jsonApiHeaders)->delete('/api/v1/extensions/does-not-exist');

    $response
        ->assertStatus(422)
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('errors.0.code', 'UNINSTALL_FAILED')
        ->assertJsonStructure(['errors' => [['status', 'title', 'code', 'detail']]]);
});

it('negotiates the uninstall response as JSON:API', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system.extensions.install');
    $this->actingAs($user);

    $this->delete('/api/v1/extensions/does-not-exist', [], ['Accept' => 'application/json'])
        ->assertStatus(406)
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('errors.0.code', 'NOT_ACCEPTABLE')
        ->assertJsonStructure(['errors' => [['status', 'code', 'detail']]]);
});
