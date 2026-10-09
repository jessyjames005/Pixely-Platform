<?php

declare(strict_types=1);

use App\Core\Auth\Services\TotpService;
use App\Core\Auth\Services\TwoFactorService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

$jsonApiHeaders = [
    'Accept' => 'application/vnd.api+json',
    'Content-Type' => 'application/json',
];

it('requires authentication to manage two-factor', function () use ($jsonApiHeaders) {
    $this->json('GET', '/api/v1/auth/two-factor', [], $jsonApiHeaders)->assertUnauthorized();
    $this->json('POST', '/api/v1/auth/two-factor', ['password' => 'password'], $jsonApiHeaders)->assertUnauthorized();
});

it('reports two-factor as disabled by default', function () use ($jsonApiHeaders) {
    $this->actingAs(User::factory()->create());

    $this->json('GET', '/api/v1/auth/two-factor', [], $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('meta.enabled', false)
        ->assertJsonPath('meta.pending_confirmation', false)
        ->assertJsonPath('meta.recovery_codes_remaining', 0);
});

it('requires the account password to start enrolment', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->json('POST', '/api/v1/auth/two-factor', ['password' => 'wrong'], $jsonApiHeaders)
        ->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'INVALID_PASSWORD');

    expect($user->refresh()->two_factor_secret)->toBeNull();
});

it('enrols, confirms with a valid code and returns recovery codes once', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $this->actingAs($user);
    $totp = new TotpService();

    $start = $this->json('POST', '/api/v1/auth/two-factor', ['password' => 'password'], $jsonApiHeaders)
        ->assertOk();

    $secret = (string) $start->json('meta.secret');
    expect($secret)->toMatch('/^[A-Z2-7]{32}$/')
        ->and($start->json('meta.otpauth_uri'))->toStartWith('otpauth://totp/')
        ->and($user->refresh()->hasEnabledTwoFactor())->toBeFalse();

    // The secret is encrypted at rest.
    $stored = DB::table('users')->where('id', $user->id)->value('two_factor_secret');
    expect($stored)->not->toBe($secret);

    $status = $this->json('GET', '/api/v1/auth/two-factor', [], $jsonApiHeaders);
    $status->assertJsonPath('meta.enabled', false)->assertJsonPath('meta.pending_confirmation', true);

    $confirm = $this->json('POST', '/api/v1/auth/two-factor/confirm', [
        'code' => $totp->codeAt($secret, $totp->timeStep()),
    ], $jsonApiHeaders)->assertOk();

    $codes = $confirm->json('meta.recovery_codes');
    expect($codes)->toHaveCount(8);
    foreach ($codes as $code) {
        expect($code)->toMatch('/^[a-z0-9]{5}-[a-z0-9]{5}$/');
    }

    expect($user->refresh()->hasEnabledTwoFactor())->toBeTrue();

    $this->json('GET', '/api/v1/auth/me', [], $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.attributes.two_factor_enabled', true);

    $this->json('GET', '/api/v1/auth/two-factor', [], $jsonApiHeaders)
        ->assertJsonPath('meta.enabled', true)
        ->assertJsonPath('meta.recovery_codes_remaining', 8);
});

it('never exposes the secret or recovery codes through the user payload', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $this->actingAs($user);
    $service = app(TwoFactorService::class);
    $totp = new TotpService();

    $secret = $service->beginEnrolment($user)['secret'];
    $service->confirm($user, $totp->codeAt($secret, $totp->timeStep()));

    $content = (string) $this->json('GET', '/api/v1/auth/me', [], $jsonApiHeaders)->getContent();

    expect($content)->not->toContain($secret)
        ->and($content)->not->toContain('two_factor_secret')
        ->and($content)->not->toContain('two_factor_recovery_codes')
        ->and(array_keys($user->refresh()->toArray()))->not->toContain('two_factor_secret', 'two_factor_recovery_codes');
});

it('rejects an invalid confirmation code and stays disabled', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->json('POST', '/api/v1/auth/two-factor', ['password' => 'password'], $jsonApiHeaders)->assertOk();

    $this->json('POST', '/api/v1/auth/two-factor/confirm', ['code' => '000000'], $jsonApiHeaders)
        ->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'INVALID_TWO_FACTOR_CODE');

    expect($user->refresh()->hasEnabledTwoFactor())->toBeFalse();
});

it('refuses to start enrolment when two-factor is already enabled', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $this->actingAs($user);
    $service = app(TwoFactorService::class);
    $totp = new TotpService();
    $secret = $service->beginEnrolment($user)['secret'];
    $service->confirm($user, $totp->codeAt($secret, $totp->timeStep()));

    $this->json('POST', '/api/v1/auth/two-factor', ['password' => 'password'], $jsonApiHeaders)
        ->assertStatus(409)
        ->assertJsonPath('errors.0.code', 'TWO_FACTOR_ALREADY_ENABLED');
});

it('regenerates recovery codes and invalidates the previous set', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $this->actingAs($user);
    $service = app(TwoFactorService::class);
    $totp = new TotpService();
    $secret = $service->beginEnrolment($user)['secret'];
    $oldCodes = $service->confirm($user, $totp->codeAt($secret, $totp->timeStep()));

    $response = $this->json('POST', '/api/v1/auth/two-factor/recovery-codes', ['password' => 'password'], $jsonApiHeaders)
        ->assertOk();

    $newCodes = $response->json('meta.recovery_codes');
    expect($newCodes)->toHaveCount(8)
        ->and($newCodes)->not->toContain($oldCodes[0]);

    $fresh = $user->refresh();
    expect($service->consumeRecoveryCode($fresh, $oldCodes[0]))->toBeFalse()
        ->and($service->consumeRecoveryCode($fresh, $newCodes[0]))->toBeTrue();
});

it('refuses to regenerate recovery codes when two-factor is disabled', function () use ($jsonApiHeaders) {
    $this->actingAs(User::factory()->create());

    $this->json('POST', '/api/v1/auth/two-factor/recovery-codes', ['password' => 'password'], $jsonApiHeaders)
        ->assertStatus(409)
        ->assertJsonPath('errors.0.code', 'TWO_FACTOR_NOT_ENABLED');
});

it('disables two-factor with the account password and discards every secret', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $this->actingAs($user);
    $service = app(TwoFactorService::class);
    $totp = new TotpService();
    $secret = $service->beginEnrolment($user)['secret'];
    $service->confirm($user, $totp->codeAt($secret, $totp->timeStep()));

    $this->json('DELETE', '/api/v1/auth/two-factor', ['password' => 'wrong'], $jsonApiHeaders)
        ->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'INVALID_PASSWORD');
    expect($user->refresh()->hasEnabledTwoFactor())->toBeTrue();

    $this->json('DELETE', '/api/v1/auth/two-factor', ['password' => 'password'], $jsonApiHeaders)
        ->assertNoContent();

    $user->refresh();
    expect($user->hasEnabledTwoFactor())->toBeFalse()
        ->and($user->two_factor_secret)->toBeNull()
        ->and($user->two_factor_recovery_codes)->toBeNull()
        ->and($user->two_factor_last_used_step)->toBeNull();
});
