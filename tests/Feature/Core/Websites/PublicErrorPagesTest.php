<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the branded 403 page for an authenticated user without admin access', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get('/admin');

    $response
        ->assertForbidden()
        ->assertSee('Access denied')
        ->assertSee('Pixely Platform');
});

it('renders the branded 404 page for a non-platform route', function () {
    $response = $this->get('/this-laravel-route-does-not-exist');

    $response
        ->assertNotFound()
        ->assertSee('Page not found')
        ->assertSee('Pixely Platform');
});
