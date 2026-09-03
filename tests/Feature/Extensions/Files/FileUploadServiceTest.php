<?php

declare(strict_types=1);

use App\Extensions\Files\Services\FileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->service = app(FileUploadService::class);
});

it('stores a valid image and generates a thumbnail', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

    $result = $this->service->upload($file, 'test-photos');

    expect($result['path'])->toStartWith('test-photos/');
    expect($result['thumbnail_path'])->not->toBeNull();

    Storage::disk('public')->assertExists($result['path']);
});

it('rejects an invalid file before storing anything', function () {
    $file = UploadedFile::fake()->create('script.exe', 100);

    $this->service->upload($file, 'test-photos');
})->throws(InvalidArgumentException::class);

it('deletes a stored file and its thumbnail', function () {
    $file = UploadedFile::fake()->image('photo.jpg');
    $result = $this->service->upload($file, 'test-photos');

    $this->service->delete($result['path'], $result['thumbnail_path']);

    Storage::disk('public')->assertMissing($result['path']);
});

it('skips thumbnail generation when explicitly disabled', function () {
    $file = UploadedFile::fake()->image('photo.jpg');

    $result = $this->service->upload($file, 'test-photos', generateThumbnail: false);

    expect($result['thumbnail_path'])->toBeNull();
});
