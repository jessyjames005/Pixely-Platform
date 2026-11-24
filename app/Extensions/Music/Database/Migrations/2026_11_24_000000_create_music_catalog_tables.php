<?php

/**
 * Music catalogue schema.
 *
 * Creates the core catalogue tables for the Music extension: artists, albums,
 * tracks and genres. Playlists, listening statistics and favourites are added
 * in later lots (MU4/MU6) so this lot stays focused on the catalogue itself.
 *
 * A track is the central entity and supports three source types:
 * - `upload` : a file uploaded through the platform (stored on the private disk).
 * - `local`  : a file discovered in an administrator-shared local folder.
 * - `youtube`: a remote YouTube video/audio referenced by its video id
 *              (ZeffyrMusic-style video mode) with no local file at all.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMusicCatalogTables extends Migration
{
    public function up(): void
    {
        // Artists are the primary catalogue entity; `normalized_name` and
        // `slug` back de-duplication and stable public URLs respectively.
        Schema::create('music_artists', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('sort_name')->nullable();
            $table->string('slug')->unique();
            $table->string('normalized_name')->index();
            $table->text('bio')->nullable();
            $table->foreignId('image_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->timestamps();
        });

        // Albums belong to a primary artist; `type` distinguishes the release
        // kind (album, single, ep, compilation) used by the public catalogue.
        Schema::create('music_albums', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug');
            $table->foreignId('artist_id')->constrained('music_artists')->cascadeOnDelete();
            $table->unsignedSmallInteger('release_year')->nullable();
            $table->string('type', 20)->default('album');
            $table->foreignId('cover_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->timestamps();
            $table->unique(['artist_id', 'slug'], 'music_albums_artist_slug_unique');
        });

        // Tracks are the playable unit. `sha256` is a nullable unique digest so
        // duplicate uploads can be detected without blocking file-less tracks
        // (e.g. YouTube references) where the digest stays null.
        Schema::create('music_tracks', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->foreignId('artist_id')->constrained('music_artists')->cascadeOnDelete();
            $table->foreignId('album_id')->nullable()->constrained('music_albums')->nullOnDelete();
            $table->unsignedSmallInteger('track_number')->nullable();
            $table->unsignedSmallInteger('disc_number')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            // Source discriminator and the data each source type needs.
            $table->string('source_type', 20)->default('upload');
            $table->string('file_path')->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('bitrate')->nullable();
            $table->unsignedSmallInteger('sample_rate')->nullable();
            $table->unsignedSmallInteger('channels')->nullable();
            $table->string('sha256', 64)->nullable()->unique();
            $table->string('youtube_video_id', 32)->nullable();

            // Rights and visibility governance: a track whose rights are unknown
            // can never be made public (enforced in the service layer).
            $table->string('rights_status', 20)->default('unknown');
            $table->text('rights_note')->nullable();
            $table->string('visibility', 20)->default('private');
            $table->string('status', 20)->default('processing');
            $table->string('error_code')->nullable();

            // Owner of a personal-library track; null for admin-curated catalogue.
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['artist_id', 'album_id', 'track_number'], 'music_tracks_album_order_index');
            $table->index(['owner_id', 'status'], 'music_tracks_owner_status_index');
            $table->index(['visibility', 'rights_status'], 'music_tracks_visibility_rights_index');
        });

        // Genres are a flat, reusable taxonomy shared across tracks.
        Schema::create('music_genres', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Pivot linking tracks to genres.
        Schema::create('music_genre_track', function (Blueprint $table): void {
            $table->foreignId('track_id')->constrained('music_tracks')->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained('music_genres')->cascadeOnDelete();
            $table->primary(['track_id', 'genre_id']);
            $table->index('genre_id');
        });

        // Pivot linking tracks to featured/guest artists with a role label.
        Schema::create('music_track_artists', function (Blueprint $table): void {
            $table->foreignId('track_id')->constrained('music_tracks')->cascadeOnDelete();
            $table->foreignId('artist_id')->constrained('music_artists')->cascadeOnDelete();
            $table->string('role', 30)->default('featured');
            $table->primary(['track_id', 'artist_id']);
            $table->index('artist_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('music_track_artists');
        Schema::dropIfExists('music_genre_track');
        Schema::dropIfExists('music_genres');
        Schema::dropIfExists('music_tracks');
        Schema::dropIfExists('music_albums');
        Schema::dropIfExists('music_artists');
    }
}
