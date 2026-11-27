<?php

declare(strict_types=1);

namespace App\Extensions\Music\Http\Controllers;

use App\Extensions\Music\Models\Album;
use App\Extensions\Music\Models\Artist;
use App\Extensions\Music\Models\Genre;
use App\Extensions\Music\Models\Track;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Public read-only controller for the Music extension.
 *
 * Exposes rights-cleared public tracks, albums, artists, and genres
 * under /api/v1/public/music/... with rate limiting and strict validation.
 */
final class PublicMusicController extends Controller
{
    /**
     * List public published tracks with optional filtering and search.
     */
    public function tracks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'search' => ['sometimes', 'string', 'max:200'],
            'artist_id' => ['sometimes', 'integer', 'exists:music_artists,id'],
            'album_id' => ['sometimes', 'integer', 'exists:music_albums,id'],
            'genre_id' => ['sometimes', 'integer', 'exists:music_genres,id'],
        ]);

        $query = Track::query()
            ->with(['artist', 'album', 'genres'])
            ->where('visibility', 'public')
            ->where('rights_status', '!=', 'unknown')
            ->where('status', 'ready');

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where('title', 'like', '%' . $search . '%');
        }

        if (!empty($validated['artist_id'])) {
            $query->where('artist_id', $validated['artist_id']);
        }

        if (!empty($validated['album_id'])) {
            $query->where('album_id', $validated['album_id']);
        }

        if (!empty($validated['genre_id'])) {
            $query->whereHas('genres', fn ($q) => $q->where('music_genres.id', $validated['genre_id']));
        }

        $perPage = 20;
        $tracks = $query->orderBy('title')->paginate($perPage);

        return response()->json($tracks);
    }

    /**
     * Show a public track detail.
     */
    public function trackDetails(int $id): JsonResponse
    {
        $track = Track::query()
            ->with(['artist', 'album', 'genres'])
            ->where('visibility', 'public')
            ->where('rights_status', '!=', 'unknown')
            ->where('status', 'ready')
            ->find($id);

        if (!$track) {
            return response()->json(['error' => 'Track not found or not available.'], 404);
        }

        return response()->json(['data' => $track]);
    }

    /**
     * List public albums.
     */
    public function albums(Request $request): JsonResponse
    {
        $albums = Album::query()
            ->with([
                'artist',
                'tracks' => fn ($q) => $q
                    ->where('visibility', 'public')
                    ->where('rights_status', '!=', 'unknown'),
            ])
            ->orderBy('title')
            ->paginate(20);

        return response()->json($albums);
    }

    /**
     * Show album detail with tracks.
     */
    public function albumDetails(int $id): JsonResponse
    {
        $album = Album::query()
            ->with([
                'artist',
                'tracks' => fn ($q) => $q
                    ->where('visibility', 'public')
                    ->where('rights_status', '!=', 'unknown'),
            ])
            ->find($id);

        if (!$album) {
            return response()->json(['error' => 'Album not found.'], 404);
        }

        return response()->json(['data' => $album]);
    }

    /**
     * List public artists.
     */
    public function artists(Request $request): JsonResponse
    {
        $artists = Artist::query()
            ->withCount([
                'tracks' => fn ($q) => $q
                    ->where('visibility', 'public')
                    ->where('rights_status', '!=', 'unknown'),
            ])
            ->orderBy('name')
            ->paginate(20);

        return response()->json($artists);
    }

    /**
     * Show artist detail with albums and tracks.
     */
    public function artistDetails(int $id): JsonResponse
    {
        $artist = Artist::query()
            ->with([
                'albums',
                'tracks' => fn ($q) => $q
                    ->where('visibility', 'public')
                    ->where('rights_status', '!=', 'unknown'),
            ])
            ->find($id);

        if (!$artist) {
            return response()->json(['error' => 'Artist not found.'], 404);
        }

        return response()->json(['data' => $artist]);
    }

    /**
     * List public genres.
     */
    public function genres(): JsonResponse
    {
        $genres = Genre::query()->orderBy('name')->get();

        return response()->json(['data' => $genres]);
    }
}
