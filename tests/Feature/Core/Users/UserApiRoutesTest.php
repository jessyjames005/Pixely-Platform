<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

$jsonApiHeaders = [
    'Accept' => 'application/vnd.api+json',
    'Content-Type' => 'application/vnd.api+json',
];

it('requires authentication to access user resources', function () use ($jsonApiHeaders) {
    $this->json('GET', '/api/v1/users', [], $jsonApiHeaders)
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('lists paginated user resources with only allowlisted attributes', function () use ($jsonApiHeaders) {
    $this->actingAs(User::factory()->create());
    User::factory()->count(3)->create();

    $response = $this->json('GET', '/api/v1/users?page[size]=2', [], $jsonApiHeaders);
    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.type', 'users')
        ->assertJsonStructure([
            'data' => [['id', 'type', 'attributes' => ['name', 'email']]],
            'meta' => ['page' => ['currentPage', 'lastPage', 'perPage', 'total']],
            'links' => ['first', 'last', 'next'],
        ]);

    expect(array_keys($response->json('data.0.attributes')))->toBe(['name', 'email']);
    expect($response->json('data.0.id'))->toBeString();
    expect($response->getContent())->not->toContain('password');
});

it('creates users from a JSON:API document without exposing their password', function () use ($jsonApiHeaders) {
    $this->actingAs(User::factory()->create());

    $response = $this->json('POST', '/api/v1/users', [
        'data' => [
            'type' => 'users',
            'attributes' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'password123',
            ],
        ],
    ], $jsonApiHeaders);

    $response
        ->assertCreated()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'users')
        ->assertJsonPath('data.attributes.name', 'Jane Doe')
        ->assertJsonPath('data.attributes.email', 'jane@example.com');

    expect($response->getContent())->not->toContain('password');
    $passwordHash = User::query()->where('email', 'jane@example.com')->value('password');
    expect(Hash::check('password123', $passwordHash))->toBeTrue();
});

it('returns canonical errors for duplicate email and mismatched resource type', function () use ($jsonApiHeaders) {
    $this->actingAs(User::factory()->create());
    User::factory()->create(['email' => 'jane@example.com']);

    $this->json('POST', '/api/v1/users', [
        'data' => [
            'type' => 'users',
            'attributes' => ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'password' => 'password123'],
        ],
    ], $jsonApiHeaders)
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'detail', 'source' => ['pointer']]]]);

    $this->json('POST', '/api/v1/users', [
        'data' => ['type' => 'accounts', 'attributes' => []],
    ], $jsonApiHeaders)
        ->assertStatus(409)
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'detail']]]);
});

it('reads and updates one user without changing a missing password', function () use ($jsonApiHeaders) {
    $this->actingAs(User::factory()->create());
    $user = User::factory()->create(['name' => 'Old Name', 'password' => 'old-password']);
    $passwordHash = $user->password;

    $this->json('GET', "/api/v1/users/{$user->id}", [], $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.type', 'users')
        ->assertJsonPath('data.id', (string) $user->id)
        ->assertJsonPath('data.attributes.name', 'Old Name');

    $this->json('PATCH', "/api/v1/users/{$user->id}", [
        'data' => [
            'type' => 'users',
            'id' => (string) $user->id,
            'attributes' => ['name' => 'New Name'],
        ],
    ], $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.attributes.name', 'New Name');

    expect($user->refresh()->password)->toBe($passwordHash);
});

it('deletes other users and prevents self-deletion using JSON:API errors', function () use ($jsonApiHeaders) {
    $currentUser = User::factory()->create();
    $this->actingAs($currentUser);
    $otherUser = User::factory()->create();

    $this->json('DELETE', "/api/v1/users/{$otherUser->id}", [], $jsonApiHeaders)->assertNoContent();
    $this->json('DELETE', "/api/v1/users/{$currentUser->id}", [], $jsonApiHeaders)
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);

    expect(User::find($currentUser->id))->not->toBeNull();
});

it('enforces JSON:API media types for user resources', function () use ($jsonApiHeaders) {
    $this->actingAs(User::factory()->create());

    $this->json('GET', '/api/v1/users', [], ['Accept' => 'application/json'])
        ->assertNotAcceptable()
        ->assertHeader('Content-Type', 'application/vnd.api+json');

    $this->json('POST', '/api/v1/users', [
        'data' => ['type' => 'users', 'attributes' => []],
    ], [
        'Accept' => $jsonApiHeaders['Accept'],
        'Content-Type' => 'application/json',
    ])
        ->assertUnsupportedMediaType()
        ->assertHeader('Content-Type', 'application/vnd.api+json');
});

it('returns and updates the current profile as an allowlisted users resource', function () use ($jsonApiHeaders) {
    $user = User::factory()->create([
        'name' => 'Jane Doe',
        'bio' => 'Photographer',
        'timezone' => 'Europe/Paris',
        'password' => 'profile-secret',
        'remember_token' => 'remember-secret',
    ]);
    $this->actingAs($user);

    $response = $this->json('GET', '/api/v1/profile', [], $jsonApiHeaders);
    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'users')
        ->assertJsonPath('data.id', (string) $user->id)
        ->assertJsonPath('data.attributes.bio', 'Photographer')
        ->assertJsonPath('data.attributes.timezone', 'Europe/Paris');

    expect(array_keys($response->json('data.attributes')))
        ->toBe(['name', 'email', 'bio', 'timezone', 'avatar_url']);
    expect($response->getContent())
        ->not->toContain('profile-secret', 'remember-secret', 'password', 'remember_token', 'avatar_filename');

    $this->json('PUT', '/api/v1/profile', [
        'data' => [
            'type' => 'users',
            'id' => (string) $user->id,
            'attributes' => [
                'name' => 'Jane Updated',
                'bio' => null,
                'timezone' => 'Europe/London',
            ],
        ],
    ], $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.type', 'users')
        ->assertJsonPath('data.attributes.name', 'Jane Updated')
        ->assertJsonPath('data.attributes.bio', null)
        ->assertJsonPath('data.attributes.timezone', 'Europe/London');
});

it('protects profile routes and enforces JSON:API Accept negotiation', function () use ($jsonApiHeaders) {
    $this->json('GET', '/api/v1/profile', [], $jsonApiHeaders)
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);

    $this->actingAs(User::factory()->create());
    $this->json('GET', '/api/v1/profile', [], ['Accept' => 'application/json'])
        ->assertNotAcceptable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'code', 'detail']]]);
});

it('rejects profile attributes outside the allowlist with JSON:API errors', function () use ($jsonApiHeaders) {
    $user = User::factory()->create(['password' => 'unchanged-secret']);
    $this->actingAs($user);
    $passwordHash = $user->password;

    $this->json('PUT', '/api/v1/profile', [
        'data' => [
            'type' => 'users',
            'attributes' => ['password' => 'changed-secret'],
        ],
    ], $jsonApiHeaders)
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);

    expect($user->refresh()->password)->toBe($passwordHash);
});

it('requires JSON:API content negotiation for profile updates', function () use ($jsonApiHeaders) {
    $this->actingAs(User::factory()->create());

    $this->json('PUT', '/api/v1/profile', [
        'data' => [
            'type' => 'users',
            'attributes' => ['name' => 'Jane Doe'],
        ],
    ], ['Accept' => $jsonApiHeaders['Accept'], 'Content-Type' => 'application/json'])
        ->assertUnsupportedMediaType()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'code']]]);
});

it('uploads profile avatars as multipart and returns a JSON:API users resource', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Storage::fake('public');

    $response = $this->withHeaders(['Accept' => 'application/vnd.api+json'])
        ->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->image('avatar.jpg')]);

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'users')
        ->assertJsonStructure([
            'data' => ['id', 'type', 'attributes' => ['name', 'email', 'bio', 'timezone', 'avatar_url']],
        ]);

    expect($response->getContent())->not->toContain('avatar_filename', 'password', 'remember_token');
    Storage::disk('public')->assertExists($user->refresh()->avatar_filename);
});
