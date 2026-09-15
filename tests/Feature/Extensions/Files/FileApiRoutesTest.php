<?php

declare(strict_types=1);

use App\Extensions\Files\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('requires authentication for every Files route', function () {
    $this->getJson('/api/v1/files')->assertStatus(401);
    $this->postJson('/api/v1/files', [])->assertStatus(401);
});

it('requires a file for upload', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->postJson('/api/v1/files', []);

    $response->assertStatus(422);
});

it('uploads a file and registers it', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Storage::fake('public');

    $upload = UploadedFile::fake()->image('invoice.jpg');

    $response = $this->postJson('/api/v1/files', ['file' => $upload]);

    $response
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'path',
                'thumbnail_path',
                'original_name',
                'mime_type',
                'size',
                'uploaded_by',
                'url',
                'thumbnail_url',
            ],
        ])
        ->assertJsonPath('data.original_name', 'invoice.jpg')
        ->assertJsonPath('data.uploaded_by', $user->id);

    expect(File::query()->count())->toBe(1);

    Storage::disk('public')->assertExists(File::first()->path);
});

it('returns a consistent validation error response', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->postJson('/api/v1/files', []);

    $response
        ->assertStatus(422)
        ->assertJsonStructure([
            'error' => [
                'code',
                'message',
                'details' => ['file'],
            ],
        ]);
});

it('lists uploaded files with pagination metadata', function () {
    $this->actingAs(User::factory()->create());

    File::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/files');

    $response
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

it('shows a single file', function () {
    $this->actingAs(User::factory()->create());

    $file = File::factory()->create();

    $response = $this->getJson("/api/v1/files/{$file->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $file->id);
});

it('deletes a file from storage and the database', function () {
    $this->actingAs(User::factory()->create());

    Storage::fake('public');
    Storage::disk('public')->put('files/sample.jpg', 'contents');

    $file = File::factory()->create(['path' => 'files/sample.jpg', 'thumbnail_path' => null]);

    $response = $this->deleteJson("/api/v1/files/{$file->id}");

    $response->assertNoContent();

    expect(File::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing('files/sample.jpg');
});
