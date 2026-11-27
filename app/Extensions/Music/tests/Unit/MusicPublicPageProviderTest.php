<?php

declare(strict_types=1);

namespace App\Extensions\Music\tests\Unit;

use App\Core\Extensions\Database\ExtensionMigrationRunner;
use App\Extensions\Music\Models\Album;
use App\Extensions\Music\Models\Artist;
use App\Extensions\Music\Models\Track;
use App\Extensions\Music\Services\MusicPublicPageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Unit test suite for MusicPublicPageProvider.
 */
final class MusicPublicPageProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('files')) {
            app(ExtensionMigrationRunner::class)->migrate('files');
        }
        if (! Schema::hasTable('music_tracks')) {
            app(ExtensionMigrationRunner::class)->migrate('music');
        }
    }

    /**
     * Test that null is returned when the track does not exist.
     */
    public function test_it_returns_null_when_track_not_found(): void
    {
        $provider = new MusicPublicPageProvider();
        $page = $provider->getTrackPage(999999);

        $this->assertNull($page);
    }

    /**
     * Test that a valid PageModel is returned for an existing public ready track.
     */
    public function test_it_returns_page_model_for_public_track(): void
    {
        $artist = Artist::create(['name' => 'Solaris']);
        $album = Album::create(['title' => 'Nebula', 'artist_id' => $artist->id]);
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

        $provider = new MusicPublicPageProvider();
        $page = $provider->getTrackPage($track->id, 'en');

        $this->assertNotNull($page);
        $this->assertSame('music/tracks/' . $track->id, $page->slug);
        $this->assertSame('Starlight', $page->title);
        $this->assertSame('published', $page->status);
        $this->assertNotEmpty($page->blocks);
    }

    /**
     * Test that a private or unknown-rights track is never exposed as a public page.
     */
    public function test_it_returns_null_for_non_public_track(): void
    {
        $artist = Artist::create(['name' => 'Mystery Artist']);
        $track = Track::create([
            'title' => 'Hidden',
            'artist_id' => $artist->id,
            'source_type' => 'youtube',
            'youtube_video_id' => 'hidden123',
            'rights_status' => 'unknown',
            'visibility' => 'public',
            'status' => 'ready',
        ]);

        $provider = new MusicPublicPageProvider();
        $page = $provider->getTrackPage($track->id, 'en');

        $this->assertNull($page);
    }
}
