<?php

declare(strict_types=1);

use App\Extensions\Gallery\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('exposes the photos JSON:API resource and removes the legacy gallery route', function () {
    $this->json('GET', '/api/v1/photos', [], [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
    ])
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json');

    $this->get('/api/v1/gallery')->assertNotFound();
});

it('serializes photo resources with the allowlisted JSON:API fields', function () {
    Photo::create([
        'title' => 'Sunset',
        'filename' => 'gallery/sunset.jpg',
    ]);

    $response = $this->json('GET', '/api/v1/photos', [], [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
    ]);

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.0.type', 'photos')
        ->assertJsonPath('data.0.attributes.title', 'Sunset')
        ->assertJsonPath('data.0.attributes.filename', 'gallery/sunset.jpg');

    expect($response->json('data.0.id'))->toBeString();
    expect(array_keys($response->json('data.0.attributes')))
        ->toEqual(['title', 'filename', 'thumbnailFilename']);
});

it('returns one photo as a JSON:API resource object', function () {
    $photo = Photo::create([
        'title' => 'Sunset',
        'filename' => 'gallery/sunset.jpg',
    ]);

    $this->json('GET', "/api/v1/photos/{$photo->id}", [], [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
    ])
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'photos')
        ->assertJsonPath('data.id', (string) $photo->id)
        ->assertJsonPath('data.attributes.title', 'Sunset');
});

it('creates a photo from a JSON:API resource document', function () {
    $this->actingAs(User::factory()->create());

    $this->json('POST', '/api/v1/photos', [
        'data' => [
            'type' => 'photos',
            'attributes' => [
                'title' => 'Sunset',
                'filename' => 'gallery/sunset.jpg',
            ],
        ],
        ], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])
        ->assertCreated()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'photos')
        ->assertJsonPath('data.attributes.title', 'Sunset')
        ->assertJsonPath('data.attributes.filename', 'gallery/sunset.jpg');

    expect(Photo::query()->count())->toBe(1);
});

it('returns JSON:API validation errors for invalid resource attributes', function () {
    $this->actingAs(User::factory()->create());

    $this->json('POST', '/api/v1/photos', [
        'data' => [
            'type' => 'photos',
            'attributes' => [
                'title' => '',
                'filename' => 'gallery/sunset.jpg',
            ],
        ],
    ], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure([
            'errors' => [
                ['status', 'title', 'detail', 'source' => ['pointer']],
            ],
        ])
        ->assertJsonPath('errors.0.source.pointer', '/data/attributes/title');
});

it('returns JSON:API document parsing errors for a mismatched resource type', function () {
    $this->actingAs(User::factory()->create());

    $this->json('POST', '/api/v1/photos', [
        'data' => [
            'type' => 'gallery',
            'attributes' => [
                'title' => 'Sunset',
                'filename' => 'gallery/sunset.jpg',
            ],
        ],
        ], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])
        ->assertStatus(409)
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'detail']]]);
});

it('updates a photo using a JSON:API resource document', function () {
    $this->actingAs(User::factory()->create());

    $photo = Photo::create([
        'title' => 'Sunset',
        'filename' => 'gallery/sunset.jpg',
    ]);

    $this->json('PATCH', "/api/v1/photos/{$photo->id}", [
        'data' => [
            'type' => 'photos',
            'id' => (string) $photo->id,
            'attributes' => ['title' => 'Beautiful Sunset'],
        ],
    ], [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
    ])
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'photos')
        ->assertJsonPath('data.attributes.title', 'Beautiful Sunset');

    expect($photo->refresh()->title)->toBe('Beautiful Sunset');
});

it('returns JSON:API not-found errors for an unknown photo', function () {
    $this->json('GET', '/api/v1/photos/999999', [], [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
    ])
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]])
        ->assertJsonPath('errors.0.status', '404');
});

it('deletes a photo and its stored files', function () {
    $this->actingAs(User::factory()->create());
    Storage::fake('public');

    $image = UploadedFile::fake()->image('sunset.jpg');
    $filename = $image->store('gallery', 'public');
    $thumbnail = 'gallery/thumbnails/sunset.jpg';
    Storage::disk('public')->put($thumbnail, 'thumbnail');

    $photo = Photo::create([
        'title' => 'Sunset',
        'filename' => $filename,
        'thumbnail_filename' => $thumbnail,
    ]);

    $this->json('DELETE', "/api/v1/photos/{$photo->id}", [], [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
    ])
        ->assertNoContent();

    expect(Photo::find($photo->id))->toBeNull();
    Storage::disk('public')->assertMissing($filename);
    Storage::disk('public')->assertMissing($thumbnail);
});

it('paginates photos with JSON:API metadata and navigation links', function () {
    Photo::factory()->count(25)->create();

    $response = $this->json(
        'GET',
        '/api/v1/photos?page[size]=20',
        [],
        [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ],
    );

    $response
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('meta.page.currentPage', 1)
        ->assertJsonPath('meta.page.lastPage', 2)
        ->assertJsonPath('meta.page.perPage', 20)
        ->assertJsonPath('meta.page.total', 25)
        ->assertJsonStructure([
            'links' => ['first', 'last', 'next'],
        ]);

    expect($response->json('links.next'))->toContain('/api/v1/photos');
});

it('rejects a JSON:API page size above the configured maximum', function () {
    Photo::factory()->count(2)->create();

    $this->json(
        'GET',
        '/api/v1/photos?page[size]=101',
        [],
        [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ],
    )
        ->assertBadRequest()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('filters photos by title and sorts by the allowlisted title field', function () {
    Photo::factory()->create(['title' => 'Sunset']);
    Photo::factory()->create(['title' => 'Mountain']);
    Photo::factory()->create(['title' => 'Sunset over the ocean']);

    $this->json(
        'GET',
        '/api/v1/photos?filter[title]=sunset&sort=title',
        [],
        [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ],
    )
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.attributes.title', 'Sunset')
        ->assertJsonPath('data.1.attributes.title', 'Sunset over the ocean');
});

it('rejects unsupported JSON:API Content-Type and Accept media types', function () {
    $this->actingAs(User::factory()->create());

    $this->json('POST', '/api/v1/photos', [
        'data' => [
            'type' => 'photos',
            'attributes' => [
                'title' => 'Sunset',
                'filename' => 'gallery/sunset.jpg',
            ],
        ],
    ], [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/json',
    ])
        ->assertStatus(415)
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);

    $this->json('GET', '/api/v1/photos', [], [
        'Accept' => 'application/json',
    ])
        ->assertStatus(406)
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('requires authentication for photo mutations while reads remain public', function () {
    $photo = Photo::factory()->create();

    $this->json('GET', '/api/v1/photos', [], [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
    ])->assertOk();

    $this->json('PATCH', "/api/v1/photos/{$photo->id}", [
        'data' => [
            'type' => 'photos',
            'id' => (string) $photo->id,
            'attributes' => ['title' => 'Updated'],
        ],
        ], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});
