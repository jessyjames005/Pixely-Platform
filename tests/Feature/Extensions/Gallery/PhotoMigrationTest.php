<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(
    TestCase::class,
    RefreshDatabase::class,
);

it('has the photos table', function () {
    expect(
        Schema::hasTable('photos'),
    )->toBeTrue();
});

it('has the expected photo columns', function () {
    expect(Schema::hasColumn(
        'photos',
        'title',
    ))->toBeTrue();

    expect(Schema::hasColumn(
        'photos',
        'filename',
    ))->toBeTrue();
});
