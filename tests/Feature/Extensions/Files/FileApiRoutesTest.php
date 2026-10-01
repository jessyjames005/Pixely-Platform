<?php

declare(strict_types=1);

use App\Extensions\Files\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->jsonApiHeaders = [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
    ];
    $this->jsonApiAcceptHeaders = ['Accept' => 'application/vnd.api+json'];
});

it('requires authentication for every Files route', function () {
    $file = File::factory()->create();

    $this->json('GET', '/api/v1/files', [], $this->jsonApiHeaders)
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
    $this->post('/api/v1/files', [], $this->jsonApiAcceptHeaders)->assertUnauthorized();
    $this->json('GET', "/api/v1/files/{$file->id}", [], $this->jsonApiHeaders)->assertUnauthorized();
    $this->json('DELETE', "/api/v1/files/{$file->id}", [], $this->jsonApiHeaders)->assertUnauthorized();
});

it('requires a file for upload', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post('/api/v1/files', [], $this->jsonApiAcceptHeaders);

    $response
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'source' => ['pointer']]]]);
});

it('uploads a file and registers it', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Storage::fake('public');

    $upload = UploadedFile::fake()->image('invoice.jpg');

    $response = $this->post('/api/v1/files', ['file' => $upload], $this->jsonApiAcceptHeaders);

    $response
        ->assertCreated()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'files')
        ->assertJsonStructure([
            'data' => [
                'id',
                'attributes' => [
                    'path',
                    'thumbnail_path',
                    'original_name',
                    'mime_type',
                    'size',
                    'uploaded_by',
                    'url',
                    'thumbnail_url',
                ],
            ],
        ])
        ->assertJsonPath('data.attributes.original_name', 'invoice.jpg')
        ->assertJsonPath('data.attributes.uploaded_by', $user->id);

    expect($response->json('data.id'))->toBe((string) File::first()->id)
        ->and(array_keys($response->json('data.attributes')))->toEqual([
            'path',
            'thumbnail_path',
            'original_name',
            'mime_type',
            'size',
            'uploaded_by',
            'url',
            'thumbnail_url',
        ]);

    expect(File::query()->count())->toBe(1);

    Storage::disk('public')->assertExists(File::first()->path);
});

it('returns a consistent validation error response', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post('/api/v1/files', [], $this->jsonApiAcceptHeaders);

    $response
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'detail']]]);
});

it('lists uploaded files as paginated JSON:API resources', function () {
    $this->actingAs(User::factory()->create());

    File::factory()->count(3)->create();

    $response = $this->json('GET', '/api/v1/files?page[number]=2&page[size]=2', [], $this->jsonApiHeaders);

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'files')
        ->assertJsonStructure(['links' => ['first', 'last', 'prev']]);

    expect($response->json('data.0.id'))->toBeString();
});

it('shows a single file', function () {
    $this->actingAs(User::factory()->create());

    $file = File::factory()->create();

    $response = $this->json('GET', "/api/v1/files/{$file->id}", [], $this->jsonApiHeaders);

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'files')
        ->assertJsonPath('data.id', (string) $file->id)
        ->assertJsonPath('data.attributes.original_name', $file->original_name);
});

it('returns a canonical JSON:API not-found error for an unknown file', function () {
    $this->actingAs(User::factory()->create());

    $this->json('GET', '/api/v1/files/99999999', [], $this->jsonApiHeaders)
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('deletes a file from storage and the database', function () {
    $this->actingAs(User::factory()->create());

    Storage::fake('public');
    Storage::disk('public')->put('files/sample.jpg', 'contents');

    $file = File::factory()->create(['path' => 'files/sample.jpg', 'thumbnail_path' => null]);

    $response = $this->json('DELETE', "/api/v1/files/{$file->id}", [], $this->jsonApiHeaders);

    $response->assertNoContent();

    expect(File::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing('files/sample.jpg');
});

it('rejects unsupported JSON request media types with a JSON:API error', function () {
    $this->actingAs(User::factory()->create());

    $this->json('POST', '/api/v1/files', [], [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/json',
    ])
        ->assertStatus(415)
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('errors.0.code', 'UNSUPPORTED_MEDIA_TYPE');

    $this->get('/api/v1/files', ['Accept' => 'application/json'])
        ->assertStatus(406)
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'code', 'detail']]]);
});
