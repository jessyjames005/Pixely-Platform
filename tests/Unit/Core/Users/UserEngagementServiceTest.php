<?php

declare(strict_types=1);

use App\Core\Users\Models\UserFavorite;
use App\Core\Users\Models\UserHistoryEntry;
use App\Core\Users\Services\UserEngagementService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates idempotent favorites for a resource reference', function () {
    $user = User::factory()->create();
    $service = app(UserEngagementService::class);

    $first = $service->addFavorite($user, 'gallery.photo', '42', ['title' => 'Sunset']);
    $second = $service->addFavorite($user, 'gallery.photo', '42', ['title' => 'Updated']);

    expect($first->id)->toBe($second->id);
    expect(UserFavorite::query()->count())->toBe(1);
    expect($second->refresh()->metadata)->toBe(['title' => 'Updated']);
});

it('records chronological history entries without coupling to a model class', function () {
    $user = User::factory()->create();
    $service = app(UserEngagementService::class);

    $entry = $service->recordHistory($user, 'music.track', '123', 'play');

    expect($entry)->toBeInstanceOf(UserHistoryEntry::class);
    expect($entry->resource_type)->toBe('music.track');
    expect($entry->resource_id)->toBe('123');
    expect($entry->action)->toBe('play');
    expect(UserHistoryEntry::query()->count())->toBe(1);
});
