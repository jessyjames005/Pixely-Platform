<?php

declare(strict_types=1);

use App\Core\Auth\Services\TotpService;
use App\Core\Auth\Services\TwoFactorService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

$jsonApiHeaders = [
    'Accept' => 'application/vnd.api+json',
    'Content-Type' => 'application/json',
];

/**
 * A user with two-factor fully enabled.
 *
 * @return array{0: User, 1: string, 2: array<int, string>} user, secret, recovery codes
 */
$twoFactorUser = function (): array {
    $user = User::factory()->create(['email' => 'jane@example.com']);
    $service = app(TwoFactorService::class);
    $totp = new TotpService();

    $secret = $service->beginEnrolment($user)['secret'];
    $recoveryCodes = $service->confirm($user, $totp->codeAt($secret, $totp->timeStep()));

    // Enrolment consumed the current time step; clear it so the first login
    // in a test can use a fresh code without waiting for the next window.
    $user->forceFill(['two_factor_last_used_step' => null])->save();

    return [$user->refresh(), $secret, $recoveryCodes ?? []];
};

$password = static fn (): array => ['email' => 'jane@example.com', 'password' => 'password'];

it('does not start a session until the second factor is provided', function () use ($jsonApiHeaders, $twoFactorUser, $password) {
    $twoFactorUser();

    $this->json('POST', '/api/v1/auth/login', $password(), $jsonApiHeaders)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('meta.two_factor_required', true)
        ->assertJsonMissingPath('data');

    expect(Auth::guard('web')->check())->toBeFalse();
    $this->json('GET', '/api/v1/auth/me', [], $jsonApiHeaders)->assertUnauthorized();
});

it('completes the login with a valid authenticator code', function () use ($jsonApiHeaders, $twoFactorUser, $password) {
    [, $secret] = $twoFactorUser();
    $totp = new TotpService();

    $this->json('POST', '/api/v1/auth/login', $password(), $jsonApiHeaders)->assertOk();

    $this->json('POST', '/api/v1/auth/two-factor-challenge', [
        'code' => $totp->codeAt($secret, $totp->timeStep()),
    ], $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.type', 'users')
        ->assertJsonPath('data.attributes.email', 'jane@example.com')
        ->assertJsonPath('data.attributes.two_factor_enabled', true);

    $this->json('GET', '/api/v1/auth/me', [], $jsonApiHeaders)->assertOk();
});

it('rejects a wrong authenticator code', function () use ($jsonApiHeaders, $twoFactorUser, $password) {
    $twoFactorUser();

    $this->json('POST', '/api/v1/auth/login', $password(), $jsonApiHeaders)->assertOk();

    $this->json('POST', '/api/v1/auth/two-factor-challenge', ['code' => '000000'], $jsonApiHeaders)
        ->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'INVALID_TWO_FACTOR_CODE');

    $this->json('GET', '/api/v1/auth/me', [], $jsonApiHeaders)->assertUnauthorized();
});

it('does not accept the same authenticator code twice', function () use ($jsonApiHeaders, $twoFactorUser, $password) {
    [, $secret] = $twoFactorUser();
    $totp = new TotpService();
    $code = $totp->codeAt($secret, $totp->timeStep());

    $this->json('POST', '/api/v1/auth/login', $password(), $jsonApiHeaders)->assertOk();
    $this->json('POST', '/api/v1/auth/two-factor-challenge', ['code' => $code], $jsonApiHeaders)->assertOk();
    $this->json('POST', '/api/v1/auth/logout', [], $jsonApiHeaders)->assertNoContent();
    Auth::forgetGuards();

    $this->json('POST', '/api/v1/auth/login', $password(), $jsonApiHeaders)->assertOk();
    $this->json('POST', '/api/v1/auth/two-factor-challenge', ['code' => $code], $jsonApiHeaders)
        ->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'INVALID_TWO_FACTOR_CODE');
});

it('accepts a recovery code exactly once', function () use ($jsonApiHeaders, $twoFactorUser, $password) {
    [$user, , $recoveryCodes] = $twoFactorUser();

    $this->json('POST', '/api/v1/auth/login', $password(), $jsonApiHeaders)->assertOk();
    $this->json('POST', '/api/v1/auth/two-factor-challenge', ['recovery_code' => $recoveryCodes[0]], $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.type', 'users');

    expect(app(TwoFactorService::class)->remainingRecoveryCodes($user->refresh()))->toBe(7);

    $this->json('POST', '/api/v1/auth/logout', [], $jsonApiHeaders)->assertNoContent();
    Auth::forgetGuards();

    $this->json('POST', '/api/v1/auth/login', $password(), $jsonApiHeaders)->assertOk();
    $this->json('POST', '/api/v1/auth/two-factor-challenge', ['recovery_code' => $recoveryCodes[0]], $jsonApiHeaders)
        ->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'INVALID_TWO_FACTOR_CODE');
});

it('tolerates recovery code formatting differences', function () use ($jsonApiHeaders, $twoFactorUser, $password) {
    [, , $recoveryCodes] = $twoFactorUser();

    $this->json('POST', '/api/v1/auth/login', $password(), $jsonApiHeaders)->assertOk();
    $this->json('POST', '/api/v1/auth/two-factor-challenge', [
        'recovery_code' => '  ' . strtoupper(str_replace('-', ' ', $recoveryCodes[1])) . ' ',
    ], $jsonApiHeaders)->assertOk();
});

it('requires a pending login to attempt the challenge', function () use ($jsonApiHeaders) {
    $this->json('POST', '/api/v1/auth/two-factor-challenge', ['code' => '123456'], $jsonApiHeaders)
        ->assertStatus(401)
        ->assertJsonPath('errors.0.code', 'TWO_FACTOR_SESSION_EXPIRED');
});

it('requires a code or a recovery code', function () use ($jsonApiHeaders, $twoFactorUser, $password) {
    $twoFactorUser();
    $this->json('POST', '/api/v1/auth/login', $password(), $jsonApiHeaders)->assertOk();

    $this->json('POST', '/api/v1/auth/two-factor-challenge', [], $jsonApiHeaders)
        ->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
});

it('locks the challenge after repeated wrong codes', function () use ($jsonApiHeaders, $twoFactorUser, $password) {
    $twoFactorUser();
    $this->json('POST', '/api/v1/auth/login', $password(), $jsonApiHeaders)->assertOk();

    for ($i = 0; $i < 5; $i++) {
        $this->json('POST', '/api/v1/auth/two-factor-challenge', ['code' => '000000'], $jsonApiHeaders)
            ->assertStatus(422);
    }

    $this->json('POST', '/api/v1/auth/two-factor-challenge', ['code' => '000000'], $jsonApiHeaders)
        ->assertStatus(429)
        ->assertJsonPath('errors.0.code', 'TOO_MANY_ATTEMPTS');

    // The pending login was discarded: the password step must be repeated.
    $this->json('POST', '/api/v1/auth/two-factor-challenge', ['code' => '000000'], $jsonApiHeaders)
        ->assertStatus(401)
        ->assertJsonPath('errors.0.code', 'TWO_FACTOR_SESSION_EXPIRED');
});

it('does not require a second factor for accounts without two-factor', function () use ($jsonApiHeaders, $password) {
    User::factory()->create(['email' => 'jane@example.com']);

    $this->json('POST', '/api/v1/auth/login', $password(), $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.type', 'users')
        ->assertJsonPath('data.attributes.two_factor_enabled', false);
});
