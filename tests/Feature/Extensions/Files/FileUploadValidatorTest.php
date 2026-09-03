<?php

declare(strict_types=1);

use App\Extensions\Files\Services\FileUploadValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->validator = app(FileUploadValidator::class);
});

it('returns default configuration when no override is stored', function () {
    $config = $this->validator->configuration();

    expect($config['max_file_size_kb'])->toBe(5120)
        ->and($config['allowed_mimes'])->toBe(['jpg', 'jpeg', 'png', 'gif', 'webp'])
        ->and($config['max_files_per_upload'])->toBe(5);
});

it('accepts a valid file within size and type limits', function () {
    $file = UploadedFile::fake()->image('photo.jpg')->size(1024);

    $this->validator->assertValid($file);
    expect(true)->toBeTrue();
});

it('rejects a file exceeding the max size', function () {
    $file = UploadedFile::fake()->image('photo.jpg')->size(6000);

    $this->validator->assertValid($file);
})->throws(InvalidArgumentException::class, 'exceeds the maximum allowed size');

it('rejects a file with a disallowed extension', function () {
    $file = UploadedFile::fake()->create('document.exe', 100);

    $this->validator->assertValid($file);
})->throws(InvalidArgumentException::class, "not allowed");

it('rejects a batch exceeding the max files per upload', function () {
    $files = [
        UploadedFile::fake()->image('a.jpg'),
        UploadedFile::fake()->image('b.jpg'),
        UploadedFile::fake()->image('c.jpg'),
        UploadedFile::fake()->image('d.jpg'),
        UploadedFile::fake()->image('e.jpg'),
        UploadedFile::fake()->image('f.jpg'),
    ];

    $this->validator->assertValidBatch($files);
})->throws(InvalidArgumentException::class, 'Too many files');

it('respects a stored configuration override', function () {
    app(\App\Core\Extensions\Configuration\ExtensionConfigurationRepositoryInterface::class)
        ->save('files', ['max_file_size_kb' => 100]);

    $file = UploadedFile::fake()->image('photo.jpg')->size(200);

    $this->validator->assertValid($file);
})->throws(InvalidArgumentException::class, 'exceeds the maximum allowed size of 100 KB');
