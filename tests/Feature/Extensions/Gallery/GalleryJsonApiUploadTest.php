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

    $this->post('/api/v1/photos/upload', [
        'title' => 'Sunset',
    ], [
        'Accept' => 'application/vnd.api+json',
    ])
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'source' => ['pointer']]]]);
});

it('uploads an image and returns its JSON:API photo resource', function () {
    $this->actingAs(User::factory()->create());
    Storage::fake('public');

    $response = $this->post('/api/v1/photos/upload', [
        'title' => 'Sunset',
        'image' => UploadedFile::fake()->image('sunset.jpg'),
    ], [
        'Accept' => 'application/vnd.api+json',
    ]);

    $response
        ->assertCreated()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'photos')
        ->assertJsonPath('data.attributes.title', 'Sunset')
        ->assertJsonStructure([
            'data' => [
                'id',
                'attributes' => ['title', 'filename', 'thumbnailFilename'],
            ],
        ]);

    expect(Photo::query()->count())->toBe(1);
    Storage::disk('public')->assertExists(Photo::first()->filename);
});
