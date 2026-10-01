<?php

declare(strict_types=1);

use App\Extensions\Tuleap\Contracts\TuleapServiceInterface;
use App\Extensions\Tuleap\Exceptions\TuleapApiException;
use App\Extensions\Tuleap\Models\TeamMember;
use App\JsonApi\V1\DocumentId;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

$jsonApiHeaders = [
    'Accept' => 'application/vnd.api+json',
    'Content-Type' => 'application/vnd.api+json',
];

it('serializes Tuleap proxy project data as allowlisted JSON:API resources', function () use ($jsonApiHeaders) {
    $service = \Mockery::mock(TuleapServiceInterface::class);
    $service->shouldReceive('getProjectsFromTuleap')->once()->andReturn([[
        'id' => 12,
        'label' => 'Platform',
        'shortname' => 'platform',
        'tuleap_token' => 'must-not-leak',
    ]]);
    app()->instance(TuleapServiceInterface::class, $service);
    $this->actingAs(User::factory()->create());

    $this->json('GET', '/api/v1/tuleap/projects', [], $jsonApiHeaders)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.0.type', 'tuleap-projects')
        ->assertJsonPath('data.0.id', DocumentId::encode('tuleap-projects', '12'))
        ->assertJsonPath('data.0.attributes.label', 'Platform')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonMissingPath('data.0.attributes.tuleap_token');
});

it('renders Tuleap proxy failures as JSON:API errors', function () use ($jsonApiHeaders) {
    $service = \Mockery::mock(TuleapServiceInterface::class);
    $service->shouldReceive('getProjectsFromTuleap')
        ->once()
        ->andThrow(new TuleapApiException('Upstream rejected the request.', 502));
    app()->instance(TuleapServiceInterface::class, $service);
    $this->actingAs(User::factory()->create());

    $this->json('GET', '/api/v1/tuleap/projects', [], $jsonApiHeaders)
        ->assertStatus(502)
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('errors.0.status', '502')
        ->assertJsonPath('errors.0.code', 'TULEAP_API_ERROR')
        ->assertJsonPath('errors.0.detail', 'Upstream rejected the request.');
});

it('serializes config without exposing the Tuleap token', function () use ($jsonApiHeaders) {
    $service = \Mockery::mock(TuleapServiceInterface::class);
    $service->shouldReceive('getAppConfig')->once()->with('tuleap_token')->andReturn('secret-token');
    $service->shouldReceive('getAppConfig')->once()->with('tuleap_user_id')->andReturn('user-8');
    app()->instance(TuleapServiceInterface::class, $service);
    $this->actingAs(User::factory()->create());

    $this->json('GET', '/api/v1/config', [], $jsonApiHeaders)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'tuleap-configs')
        ->assertJsonPath('data.attributes.tuleap_logged_in', true)
        ->assertJsonPath('data.attributes.tuleap_user_id', 'user-8')
        ->assertJsonMissing(['secret-token'])
        ->assertJsonMissingPath('data.attributes.tuleap_token');
});

it('requires JSON:API negotiation and validates typed mutation documents', function () use ($jsonApiHeaders) {
    $this->actingAs(User::factory()->create());

    $this->getJson('/api/v1/tuleap/projects', ['Accept' => 'application/json'])
        ->assertNotAcceptable()
        ->assertJsonStructure(['errors' => [['status', 'code', 'detail']]]);

    $this->json('POST', '/api/v1/team/members', [
        'data' => [
            'type' => 'tuleap-team-members',
            'attributes' => ['project_id' => 12],
        ],
    ], $jsonApiHeaders)
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR')
        ->assertJsonPath('errors.0.source.pointer', '/data/attributes/name');

    $this->json('POST', '/api/v1/team/members', [
        'data' => ['type' => 'tuleap-team-members', 'attributes' => ['name' => 'Analyst']],
    ], ['Accept' => 'application/vnd.api+json'])
        ->assertUnsupportedMediaType()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('errors.0.code', 'UNSUPPORTED_MEDIA_TYPE');
});

it('accepts the Tuleap token in a config document but only returns logged-in state', function () use ($jsonApiHeaders) {
    $service = \Mockery::mock(TuleapServiceInterface::class);
    $service->shouldReceive('setAppConfig')->once()->with('tuleap_token', 'secret-token');
    $service->shouldReceive('setAppConfig')->once()->with('tuleap_user_id', 'user-8');
    $service->shouldReceive('getAppConfig')->once()->with('tuleap_token')->andReturn('secret-token');
    $service->shouldReceive('getAppConfig')->once()->with('tuleap_user_id')->andReturn('user-8');
    app()->instance(TuleapServiceInterface::class, $service);
    $this->actingAs(User::factory()->create());

    $this->json('PUT', '/api/v1/config', [
        'data' => [
            'type' => 'tuleap-configs',
            'attributes' => ['tuleap_token' => 'secret-token', 'tuleap_user_id' => 'user-8'],
        ],
    ], $jsonApiHeaders)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.attributes.tuleap_logged_in', true)
        ->assertJsonPath('data.attributes.tuleap_user_id', 'user-8')
        ->assertJsonMissing(['secret-token'])
        ->assertJsonMissingPath('data.attributes.tuleap_token');
});

it('preserves authentication and accepts a JSON:API team-member mutation', function () use ($jsonApiHeaders) {
    $this->json('GET', '/api/v1/tuleap/projects', [], $jsonApiHeaders)->assertUnauthorized();

    $member = new TeamMember(['project_id' => 12, 'name' => 'Analyst', 'tuleap_username' => 'analyst']);
    $member->setAttribute('id', 45);
    $service = Mockery::mock(TuleapServiceInterface::class);
    $service->shouldReceive('addMember')->once()->with([
        'project_id' => 12,
        'name' => 'Analyst',
        'tuleap_username' => 'analyst',
    ])->andReturn($member);
    app()->instance(TuleapServiceInterface::class, $service);
    $this->actingAs(User::factory()->create());

    $this->json('POST', '/api/v1/team/members', [
        'data' => [
            'type' => 'tuleap-team-members',
            'attributes' => [
                'project_id' => 12,
                'name' => 'Analyst',
                'tuleap_username' => 'analyst',
                'ignored_field' => 'not accepted',
            ],
        ],
    ], $jsonApiHeaders)
        ->assertStatus(400)
        ->assertJsonPath('errors.0.code', 'UNKNOWN_ATTRIBUTE');

    $this->json('POST', '/api/v1/team/members', [
        'data' => [
            'type' => 'tuleap-team-members',
            'attributes' => [
                'project_id' => 12,
                'name' => 'Analyst',
                'tuleap_username' => 'analyst',
            ],
        ],
    ], $jsonApiHeaders)
        ->assertCreated()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'tuleap-team-members')
        ->assertJsonPath('data.id', DocumentId::encode('tuleap-team-members', '45'))
        ->assertJsonPath('data.attributes.name', 'Analyst');
});
