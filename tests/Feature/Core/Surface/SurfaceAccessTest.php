<?php

declare(strict_types=1);

use App\Core\Surface\Services\SurfaceContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('allows guests to access the public surface', function () {
    $this->get('/')->assertOk();
});

it('requires authentication for the user surface', function () {
    $this->get('/account')->assertRedirect('/login');
});

it('allows authenticated users to access the user surface', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/account')->assertOk();
});

it('denies authenticated users without the admin access permission', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin')->assertForbidden();
});

it('allows users with the admin access permission to enter the admin surface', function () {
    Permission::firstOrCreate([
        'name' => 'system.admin.access',
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('system.admin.access');

    $this->actingAs($user);

    $this->get('/admin')->assertOk();
});

it('establishes the api surface for every api middleware group', function () {
    Route::middleware('api')->get('/api/__surface', function (SurfaceContext $context) {
        return response()->json(['surface' => $context->current()->value]);
    });

    $this->getJson('/api/__surface')
        ->assertOk()
        ->assertJsonPath('surface', 'api');
});
