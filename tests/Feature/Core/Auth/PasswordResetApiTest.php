<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

$jsonApiHeaders = [
    'Accept' => 'application/vnd.api+json',
    'Content-Type' => 'application/json',
];

it('emails a reset link that points at the SPA reset screen', function () use ($jsonApiHeaders) {
    Notification::fake();
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $this->json('POST', '/api/v1/auth/forgot-password', ['email' => 'jane@example.com'], $jsonApiHeaders)
        ->assertNoContent();

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $url = (string) $notification->toMail($user)->actionUrl;

        return str_contains($url, '/reset-password?')
            && str_contains($url, 'token=')
            && str_contains($url, 'email=jane%40example.com');
    });
});

it('answers identically for an unknown address so emails cannot be enumerated', function () use ($jsonApiHeaders) {
    Notification::fake();

    $this->json('POST', '/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'], $jsonApiHeaders)
        ->assertNoContent();

    Notification::assertNothingSent();
});

it('validates the email when requesting a reset link', function () use ($jsonApiHeaders) {
    $this->json('POST', '/api/v1/auth/forgot-password', ['email' => 'not-an-email'], $jsonApiHeaders)
        ->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
});

it('rate limits reset link requests', function () use ($jsonApiHeaders) {
    Notification::fake();

    for ($i = 0; $i < 5; $i++) {
        $this->json('POST', '/api/v1/auth/forgot-password', ['email' => 'jane@example.com'], $jsonApiHeaders)
            ->assertNoContent();
    }

    $this->json('POST', '/api/v1/auth/forgot-password', ['email' => 'jane@example.com'], $jsonApiHeaders)
        ->assertStatus(429);
});

it('resets the password with a valid token and rotates the remember token', function () use ($jsonApiHeaders) {
    $user = User::factory()->create(['email' => 'jane@example.com']);
    $previousRememberToken = $user->remember_token;
    $token = Password::createToken($user);

    $this->json('POST', '/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => 'jane@example.com',
        'password' => 'a-brand-new-secret',
        'password_confirmation' => 'a-brand-new-secret',
    ], $jsonApiHeaders)->assertNoContent();

    $user->refresh();

    expect(Hash::check('a-brand-new-secret', $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe($previousRememberToken);

    // The new password works for login.
    $this->json('POST', '/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'a-brand-new-secret',
    ], $jsonApiHeaders)->assertOk();
});

it('makes a reset token single-use', function () use ($jsonApiHeaders) {
    $user = User::factory()->create(['email' => 'jane@example.com']);
    $token = Password::createToken($user);
    $payload = [
        'token' => $token,
        'email' => 'jane@example.com',
        'password' => 'a-brand-new-secret',
        'password_confirmation' => 'a-brand-new-secret',
    ];

    $this->json('POST', '/api/v1/auth/reset-password', $payload, $jsonApiHeaders)->assertNoContent();

    $this->json('POST', '/api/v1/auth/reset-password', $payload, $jsonApiHeaders)
        ->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'INVALID_RESET_TOKEN');
});

it('rejects an invalid reset token without revealing whether the account exists', function () use ($jsonApiHeaders) {
    User::factory()->create(['email' => 'jane@example.com']);

    $this->json('POST', '/api/v1/auth/reset-password', [
        'token' => 'not-a-real-token',
        'email' => 'jane@example.com',
        'password' => 'a-brand-new-secret',
        'password_confirmation' => 'a-brand-new-secret',
    ], $jsonApiHeaders)
        ->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'INVALID_RESET_TOKEN');

    $this->json('POST', '/api/v1/auth/reset-password', [
        'token' => 'not-a-real-token',
        'email' => 'nobody@example.com',
        'password' => 'a-brand-new-secret',
        'password_confirmation' => 'a-brand-new-secret',
    ], $jsonApiHeaders)
        ->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'INVALID_RESET_TOKEN');
});

it('enforces the password rules when resetting', function (array $override) use ($jsonApiHeaders) {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $this->json('POST', '/api/v1/auth/reset-password', array_merge([
        'token' => Password::createToken($user),
        'email' => 'jane@example.com',
        'password' => 'a-brand-new-secret',
        'password_confirmation' => 'a-brand-new-secret',
    ], $override), $jsonApiHeaders)
        ->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');

    expect(Hash::check('a-brand-new-secret', $user->refresh()->password))->toBeFalse();
})->with([
    'too short' => [['password' => 'short', 'password_confirmation' => 'short']],
    'confirmation mismatch' => [['password_confirmation' => 'something-else-entirely']],
]);
