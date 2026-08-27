<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;

uses(RefreshDatabase::class);

it('applies the platform default locale for a guest request', function () {
    $this->getJson('/api/v1/locales');

    expect(App::getLocale())->toBe('en');
});

it('applies the user locale preference over the platform default', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->putJson('/api/v1/settings/platform', ['locale' => 'fr'])->assertOk();
    $this->putJson('/api/v1/settings/user', ['locale' => 'en'])->assertOk();

    $this->getJson('/api/v1/settings/user');

    expect(App::getLocale())->toBe('en');
});
