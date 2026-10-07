<?php

declare(strict_types=1);

use App\Core\Websites\Persistence\Models\PageRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'website.pages.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'website.pages.manage', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'website.menus.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'website.menus.manage', 'guard_name' => 'web']);
});

it('requires the website page view permission', function (): void {
    $this->actingAs(User::factory()->create());

    $this->getJson('/api/v1/website/pages')->assertForbidden();
});

it('creates and reads a website page through the engine API', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('website.pages.view', 'website.pages.manage');
    $this->actingAs($user);

    $this->postJson('/api/v1/website/pages', [
        'title' => 'About Pixely',
        'status' => 'published',
        'blocks' => [['type' => 'hero', 'title' => 'Welcome']],
    ])->assertCreated()
        ->assertJsonPath('data.slug', 'about-pixely')
        ->assertJsonPath('data.status', 'published');

    $this->getJson('/api/v1/website/pages/about-pixely')
        ->assertOk()
        ->assertJsonPath('data.title', 'About Pixely');

    expect(PageRecord::query()->where('slug', 'about-pixely')->exists())->toBeTrue();
});

it('generates a unique slug when a page title already exists', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('website.pages.manage');
    $this->actingAs($user);

    $this->postJson('/api/v1/website/pages', ['title' => 'Home'])->assertCreated();
    $this->postJson('/api/v1/website/pages', ['title' => 'Home'])->assertCreated()
        ->assertJsonPath('data.slug', 'home-2');
});

it('validates page input', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('website.pages.manage');
    $this->actingAs($user);

    $this->postJson('/api/v1/website/pages', [
        'status' => 'invalid',
    ])->assertUnprocessable();
});
