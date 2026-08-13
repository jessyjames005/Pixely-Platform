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

it('requires an image for API upload', function () {
    $response = $this->postJson('/api/gallery/upload', [
        'title' => 'Sunset',
    ]);

    $response->assertStatus(422);
});

it('uploads an image and creates a photo', function () {
    Storage::fake('public');

    $image = UploadedFile::fake()->image('sunset.jpg');

    $response = $this->postJson('/api/gallery/upload', [
        'title' => 'Sunset',
        'image' => $image,
    ]);

    $response
    ->assertCreated()
    ->assertJsonStructure([
        'data' => [
            'id',
            'title',
            'filename',
        ],
    ]);

    expect(Photo::query()->count())
        ->toBe(1);

    expect(Photo::first())
        ->title
        ->toBe('Sunset');

    Storage::disk('public')->assertExists(
        Photo::first()->filename,
    );
});
