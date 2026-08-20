<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Extensions\Gallery\Models\Photo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(
    TestCase::class,
    RefreshDatabase::class,
);

it('exposes the gallery API endpoint', function () {
    $response = $this->getJson('/api/v1/gallery');

    $response->assertOk();
});

it('returns gallery photos', function () {
    \App\Extensions\Gallery\Models\Photo::create([
        'title' => 'Sunset',
        'filename' => 'sunset.jpg',
    ]);

    $response = $this->getJson('/api/v1/gallery');

    $response
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Sunset')
        ->assertJsonPath('data.0.filename', 'sunset.jpg');
});

it('returns photos with the public gallery fields', function () {
    \App\Extensions\Gallery\Models\Photo::create([
        'title' => 'Sunset',
        'filename' => 'sunset.jpg',
    ]);

    $response = $this->getJson('/api/v1/gallery');

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'filename',
                ],
            ],
        ]);
});

it('returns a single gallery photo', function () {
    $photo = Photo::create([
        'title' => 'Sunset',
        'filename' => 'sunset.jpg',
    ]);

    $response = $this->getJson(
        "/api/v1/gallery/{$photo->id}",
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $photo->id)
        ->assertJsonPath('data.title', 'Sunset')
        ->assertJsonPath('data.filename', 'sunset.jpg');
});

it('returns 404 when the gallery photo does not exist', function () {
    $response = $this->getJson(
        '/api/v1/gallery/999999',
    );

    $response->assertNotFound();
});

it('updates a gallery photo', function () {
    $photo = Photo::create([
        'title' => 'Sunset',
        'filename' => 'sunset.jpg',
    ]);

    $response = $this->putJson(
        "/api/v1/gallery/{$photo->id}",
        [
            'title' => 'Beautiful Sunset',
        ],
    );

    $response
        ->assertOk()
        ->assertJsonPath(
            'data.title',
            'Beautiful Sunset',
        );

    expect($photo->refresh()->title)
        ->toBe('Beautiful Sunset');
});

it('deletes a gallery photo', function () {
    $photo = Photo::create([
        'title' => 'Sunset',
        'filename' => 'sunset.jpg',
    ]);

    $response = $this->deleteJson(
        "/api/v1/gallery/{$photo->id}",
    );

    $response->assertNoContent();

    expect(Photo::find($photo->id))
        ->toBeNull();
});

it('deletes the stored file when deleting a photo', function () {
    Storage::fake('public');

    $image = UploadedFile::fake()->image('sunset.jpg');

    $filename = $image->store('gallery', 'public');

    $photo = Photo::create([
        'title' => 'Sunset',
        'filename' => $filename,
    ]);

    Storage::disk('public')->assertExists($filename);

    $response = $this->deleteJson(
        "/api/v1/gallery/{$photo->id}",
    );

    $response->assertNoContent();

    Storage::disk('public')->assertMissing($filename);
});

it('paginates gallery photos', function () {
    Photo::factory()
        ->count(25)
        ->create();

    $response = $this->getJson(
        '/api/v1/gallery?per_page=20',
    );

    $response
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonStructure([
            'data',
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
});

it('limits gallery pagination to 100 photos per page', function () {
    Photo::factory()
        ->count(105)
        ->create();

    $response = $this->getJson(
        '/api/v1/gallery?per_page=500',
    );

    $response
        ->assertOk()
        ->assertJsonCount(100, 'data')
        ->assertJsonPath('meta.per_page', 100);
});

it('uses one photo as the minimum page size', function () {
    Photo::factory()
        ->count(5)
        ->create();

    $response = $this->getJson(
        '/api/v1/gallery?per_page=0',
    );

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.per_page', 1);
});

it('returns a JSON response when the gallery photo does not exist', function () {
    $response = $this->getJson('/api/v1/gallery/999999');

    $response
        ->assertNotFound()
        ->assertJsonStructure([
            'error' => [
                'code',
                'message',
            ],
        ]);
});

it('filters gallery photos by title', function () {
    Photo::factory()->create([
        'title' => 'Sunset',
    ]);

    Photo::factory()->create([
        'title' => 'Mountain',
    ]);

    Photo::factory()->create([
        'title' => 'Sunset over the ocean',
    ]);

    $response = $this->getJson(
        '/api/v1/gallery?filter[title]=Sunset',
    );

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', 'Sunset')
        ->assertJsonPath(
            'data.1.title',
            'Sunset over the ocean',
        );
});

it('filters gallery photos by title case insensitively', function () {
    Photo::factory()->create([
        'title' => 'Sunset',
    ]);

    Photo::factory()->create([
        'title' => 'Mountain',
    ]);

    $response = $this->getJson(
        '/api/v1/gallery?filter[title]=sunset',
    );

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Sunset');
});

it('sorts gallery photos by title ascending', function () {
    Photo::factory()->create([
        'title' => 'Sunset',
    ]);

    Photo::factory()->create([
        'title' => 'Mountain',
    ]);

    Photo::factory()->create([
        'title' => 'Beach',
    ]);

    $response = $this->getJson(
        '/api/v1/gallery?sort=title',
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Beach')
        ->assertJsonPath('data.1.title', 'Mountain')
        ->assertJsonPath('data.2.title', 'Sunset');
});

it('sorts gallery photos by title descending', function () {
    Photo::factory()->create([
        'title' => 'Sunset',
    ]);

    Photo::factory()->create([
        'title' => 'Mountain',
    ]);

    Photo::factory()->create([
        'title' => 'Beach',
    ]);

    $response = $this->getJson(
        '/api/v1/gallery?sort=-title',
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Sunset')
        ->assertJsonPath('data.1.title', 'Mountain')
        ->assertJsonPath('data.2.title', 'Beach');
});
