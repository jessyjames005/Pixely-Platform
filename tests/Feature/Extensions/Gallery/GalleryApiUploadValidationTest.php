<?php

declare(strict_types=1);

use App\Extensions\Gallery\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('returns JSON:API validation errors when an upload image is missing', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post('/api/v1/photos/upload', [
        'title' => 'Sunset',
    ], [
        'Accept' => 'application/vnd.api+json',
    ]);

    $response
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'source' => ['pointer']]]])
        ->assertJsonPath('errors.0.source.pointer', '/image');
});

it('uploads an image and creates a JSON:API photo resource', function () {
    $this->actingAs(User::factory()->create());

    Storage::fake('public');

    $image = UploadedFile::fake()->image('sunset.jpg');

    $response = $this->post('/api/v1/photos/upload', [
        'title' => 'Sunset',
        'image' => $image,
    ], [
        'Accept' => 'application/vnd.api+json',
    ]);

    $response
        ->assertCreated()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => ['title', 'filename', 'thumbnailFilename'],
            ],
        ])
        ->assertJsonPath('data.type', 'photos')
        ->assertJsonPath('data.attributes.title', 'Sunset');

    expect(Photo::query()->count())
        ->toBe(1);

    expect(Photo::first())
        ->title
        ->toBe('Sunset');

    Storage::disk('public')->assertExists(
        Photo::first()->filename,
    );
    Storage::disk('public')->assertExists(
        Photo::first()->thumbnail_filename,
    );
});

it('returns a JSON:API validation error when an upload title is missing', function () {
    $this->actingAs(User::factory()->create());
    Storage::fake('public');

    $response = $this->post('/api/v1/photos/upload', [
        'image' => UploadedFile::fake()->image('sunset.jpg'),
    ], [
        'Accept' => 'application/vnd.api+json',
    ]);

    $response
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'source' => ['pointer']]]])
        ->assertJsonPath('errors.0.source.pointer', '/title');
});
