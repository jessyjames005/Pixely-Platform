<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

$jsonApiHeaders = [
    'Accept' => 'application/vnd.api+json',
    'Content-Type' => 'application/json',
];

it('requires authentication to change the password', function () use ($jsonApiHeaders) {
    $this->json('PUT', '/api/v1/auth/password', [
        'current_password' => 'password',
        'password' => 'a-brand-new-secret',
        'password_confirmation' => 'a-brand-new-secret',
    ], $jsonApiHeaders)->assertUnauthorized();
});

it('changes the password and rotates the remember token', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $previousRememberToken = $user->remember_token;
    $this->actingAs($user);

    $this->json('PUT', '/api/v1/auth/password', [
        'current_password' => 'password',
        'password' => 'a-brand-new-secret',
        'password_confirmation' => 'a-brand-new-secret',
    ], $jsonApiHeaders)->assertNoContent();

    $user->refresh();

    expect(Hash::check('a-brand-new-secret', $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe($previousRememberToken);
});

it('rejects a wrong current password', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->json('PUT', '/api/v1/auth/password', [
        'current_password' => 'not-my-password',
        'password' => 'a-brand-new-secret',
        'password_confirmation' => 'a-brand-new-secret',
    ], $jsonApiHeaders)
        ->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'INVALID_PASSWORD')
        ->assertJsonPath('errors.0.source.pointer', '/data/attributes/current_password');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

it('enforces the new password rules', function (array $override) use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->json('PUT', '/api/v1/auth/password', array_merge([
        'current_password' => 'password',
        'password' => 'a-brand-new-secret',
        'password_confirmation' => 'a-brand-new-secret',
    ], $override), $jsonApiHeaders)
        ->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
})->with([
    'too short' => [['password' => 'short', 'password_confirmation' => 'short']],
    'confirmation mismatch' => [['password_confirmation' => 'something-else-entirely']],
    'same as current' => [['password' => 'password', 'password_confirmation' => 'password']],
]);
