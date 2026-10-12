<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('engagement endpoints are bound to the user surface', function (): void {
    $route = Route::getRoutes()->match(Request::create('/api/v1/me/favorites', 'GET'));

    expect($route->gatherMiddleware())->toContain('surface:user');
});

test('engagement endpoints are available to an authenticated user on the user surface', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/me/favorites')
        ->assertOk();
});
