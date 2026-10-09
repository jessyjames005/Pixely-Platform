<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

$jsonApiHeaders = [
    'Accept' => 'application/vnd.api+json',
    'Content-Type' => 'application/json',
];

$cookieNames = static fn ($response): array => collect($response->headers->getCookies())
    ->map(static fn ($cookie): string => $cookie->getName())
    ->all();

it('queues a persistent remember-me cookie when remember is true', function () use ($jsonApiHeaders, $cookieNames) {
    User::factory()->create(['email' => 'jane@example.com']);

    $response = $this->json('POST', '/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password',
        'remember' => true,
    ], $jsonApiHeaders)->assertOk();

    $remember = collect($cookieNames($response))->filter(
        static fn (string $name): bool => str_starts_with($name, 'remember_web_'),
    );

    expect($remember)->toHaveCount(1);
});

it('does not queue a remember-me cookie by default', function () use ($jsonApiHeaders, $cookieNames) {
    User::factory()->create(['email' => 'jane@example.com']);

    $response = $this->json('POST', '/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password',
    ], $jsonApiHeaders)->assertOk();

    $remember = collect($cookieNames($response))->filter(
        static fn (string $name): bool => str_starts_with($name, 'remember_web_'),
    );

    expect($remember)->toBeEmpty();
});

it('rejects a non-boolean remember flag', function () use ($jsonApiHeaders) {
    User::factory()->create(['email' => 'jane@example.com']);

    $this->json('POST', '/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password',
        'remember' => 'maybe',
    ], $jsonApiHeaders)
        ->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
});

it('throttles a login after repeated failures', function () use ($jsonApiHeaders) {
    User::factory()->create(['email' => 'jane@example.com']);

    for ($i = 0; $i < 5; $i++) {
        $this->json('POST', '/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ], $jsonApiHeaders)->assertStatus(401);
    }

    // Even the correct password is refused while the lock is active.
    $this->json('POST', '/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password',
    ], $jsonApiHeaders)
        ->assertStatus(429)
        ->assertJsonPath('errors.0.code', 'TOO_MANY_ATTEMPTS');
});

it('does not count successful logins towards the limit', function () use ($jsonApiHeaders) {
    User::factory()->create(['email' => 'jane@example.com']);

    for ($i = 0; $i < 7; $i++) {
        $this->json('POST', '/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'password',
        ], $jsonApiHeaders)->assertOk();
    }

    expect(Auth::guard('web')->check())->toBeTrue();
});

it('forgives earlier failures after a successful login', function () use ($jsonApiHeaders) {
    User::factory()->create(['email' => 'jane@example.com']);

    for ($i = 0; $i < 4; $i++) {
        $this->json('POST', '/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ], $jsonApiHeaders)->assertStatus(401);
    }

    $this->json('POST', '/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password',
    ], $jsonApiHeaders)->assertOk();

    // The counter was reset, so four more failures do not lock the account.
    for ($i = 0; $i < 4; $i++) {
        $this->json('POST', '/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ], $jsonApiHeaders)->assertStatus(401);
    }
});

it('does not reveal whether an email exists through the login error', function () use ($jsonApiHeaders) {
    User::factory()->create(['email' => 'jane@example.com']);

    $known = $this->json('POST', '/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'wrong-password',
    ], $jsonApiHeaders);

    $unknown = $this->json('POST', '/api/v1/auth/login', [
        'email' => 'nobody@example.com',
        'password' => 'wrong-password',
    ], $jsonApiHeaders);

    expect($known->status())->toBe($unknown->status())
        ->and($known->json('errors.0.code'))->toBe($unknown->json('errors.0.code'))
        ->and($known->json('errors.0.detail'))->toBe($unknown->json('errors.0.detail'));
});
