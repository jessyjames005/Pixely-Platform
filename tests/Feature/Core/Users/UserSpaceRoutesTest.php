<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication for the user space', function (): void {
    $this->get('/account')->assertRedirect('/login');
});

it('allows an authenticated user to enter the user space', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/account')
        ->assertOk();
});

it('serves profile and preferences entry points through the user surface', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/account/profile')->assertOk();
    $this->actingAs($user)->get('/account/preferences')->assertOk();
});


it('serves favorites and history entry points through the user surface', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/account/favorites')->assertOk();
    $this->actingAs($user)->get('/account/history')->assertOk();
});
