<?php

declare(strict_types=1);

namespace App\Extensions\Music\tests\Functional;

use App\Core\Extensions\Database\ExtensionMigrationRunner;
use App\Extensions\Music\Models\Album;
use App\Extensions\Music\Models\Artist;
use App\Extensions\Music\Models\Genre;
use App\Extensions\Music\Models\Track;
use App\Extensions\Music\Providers\MusicServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Functional test for public music proxy endpoints.
 */
final class PublicMusicControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->register(MusicServiceProvider::class);

        if (! Schema::hasTable('files')) {
            app(ExtensionMigrationRunner::class)->migrate('files');
        }
        if (! Schema::hasTable('music_tracks')) {
            app(ExtensionMigrationRunner::class)->migrate('music');
        }
    }

    public function test_it_returns_public_tracks_and_excludes_private_or_unknown_rights(): void
    {
        $artist = Artist::create(['name' => 'Test Artist', 'slug' => 'test-artist']);
        $album = Album::create(['title' => 'Test Album', 'slug' => 'test-album', 'artist_id' => $artist->id]);

        // Public & ready & valid rights
        Track::create([
            'title' => 'Public Track',
            'artist_id' => $artist->id,
            'album_id' => $album->id,
            'source_type' => 'youtube',
            'youtube_video_id' => 'abc123xyz',
            'rights_status' => 'owned',
            'visibility' => 'public',
            'status' => 'ready',
        ]);

        // Private track
        Track::create([
            'title' => 'Private Track',
            'artist_id' => $artist->id,
            'album_id' => $album->id,
            'source_type' => 'youtube',
            'youtube_video_id' => 'priv456',
            'rights_status' => 'owned',
            'visibility' => 'private',
            'status' => 'ready',
        ]);

        // Unknown rights (should never be public anyway)
        Track::create([
            'title' => 'Unknown Rights Track',
            'artist_id' => $artist->id,
            'album_id' => $album->id,
            'source_type' => 'youtube',
            'youtube_video_id' => 'unk789',
            'rights_status' => 'unknown',
            'visibility' => 'public',
            'status' => 'ready',
        ]);

        $response = $this->getJson('/api/v1/public/music/tracks');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Public Track');
    }

    public function test_it_returns_404_for_non_numeric_track_id(): void
    {
        $response = $this->getJson('/api/v1/public/music/tracks/abc-not-an-id');

        $response->assertStatus(404);
    }

    public function test_it_returns_track_details_for_public_track(): void
    {
        $artist = Artist::create(['name' => 'Solaris', 'slug' => 'solaris']);
        $album = Album::create(['title' => 'Nebula', 'slug' => 'nebula', 'artist_id' => $artist->id]);

        $track = Track::create([
            'title' => 'Starlight',
            'artist_id' => $artist->id,
            'album_id' => $album->id,
            'source_type' => 'youtube',
            'youtube_video_id' => 'starlight123',
            'rights_status' => 'licensed',
            'visibility' => 'public',
            'status' => 'ready',
        ]);

        $response = $this->getJson("/api/v1/public/music/tracks/{$track->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Starlight')
            ->assertJsonPath('data.youtube_video_id', 'starlight123')
            ->assertJsonPath('data.artist.name', 'Solaris');
    }

    public function test_it_returns_albums_and_artists_lists(): void
    {
        $artist = Artist::create(['name' => 'Echo Band', 'slug' => 'echo-band']);
        $album = Album::create(['title' => 'First Echo', 'slug' => 'first-echo', 'artist_id' => $artist->id]);

        $response = $this->getJson('/api/v1/public/music/albums');
        $response->assertStatus(200)->assertJsonPath('data.0.title', 'First Echo');

        $responseArtist = $this->getJson('/api/v1/public/music/artists');
        $responseArtist->assertStatus(200)->assertJsonPath('data.0.name', 'Echo Band');
    }

    public function test_it_returns_genres(): void
    {
        Genre::create(['name' => 'Electronic', 'slug' => 'electronic']);

        $response = $this->getJson('/api/v1/public/music/genres');
        $response->assertStatus(200)->assertJsonPath('data.0.name', 'Electronic');
    }
}
