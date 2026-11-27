<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for interacting with the complete TMDB v3 API,
 * covering account, authentication, certifications, changes, collections,
 * companies, configuration, credits, discover, find, genres, guest sessions,
 * keywords, lists, movie lists, movies, networks, people lists, people,
 * reviews, search, trending, TV series lists, TV series, seasons, episodes,
 * episode groups, and watch providers.
 */
final class TmdbImportService
{
    /**
     * Base URL for the TMDB v3 API.
     */
    private string $baseUrl = 'https://api.themoviedb.org/3';

    /**
     * Create a new TmdbImportService instance.
     */
    public function __construct()
    {
    }

    /**
     * Send a GET request to the TMDB API.
     *
     * @param string $endpoint API endpoint path.
     * @param array<string, mixed> $params Query parameters.
     * @return array<string, mixed>
     */
    private function get(string $endpoint, array $params = []): array
    {
        /** @var string|null $apiKey */
        $apiKey = config('services.tmdb.key');

        if (empty($apiKey)) {
            return ['error' => 'TMDB API key not configured.'];
        }

        $params['api_key'] = $apiKey;
        if (!isset($params['language'])) {
            $params['language'] = 'fr-FR';
        }

        $response = Http::get("{$this->baseUrl}{$endpoint}", $params);

        if ($response->failed()) {
            Log::error("TMDB API GET failed: {$endpoint}", ['status' => $response->status(), 'body' => $response->body()]);
            return ['error' => "Failed to connect to TMDB API: {$endpoint}"];
        }

        /** @var array<string, mixed>|null $data */
        $data = $response->json();

        return $data ?? [];
    }

    /**
     * Send a POST request to the TMDB API.
     *
     * @param string $endpoint API endpoint path.
     * @param array<string, mixed> $data Request body data.
     * @param array<string, mixed> $params Query parameters.
     * @return array<string, mixed>
     */
    private function post(string $endpoint, array $data = [], array $params = []): array
    {
        /** @var string|null $apiKey */
        $apiKey = config('services.tmdb.key');

        if (empty($apiKey)) {
            return ['error' => 'TMDB API key not configured.'];
        }

        $params['api_key'] = $apiKey;

        $response = Http::withQueryParameters($params)->post("{$this->baseUrl}{$endpoint}", $data);

        if ($response->failed()) {
            Log::error("TMDB API POST failed: {$endpoint}", ['status' => $response->status(), 'body' => $response->body()]);
            return ['error' => "Failed to post to TMDB API: {$endpoint}"];
        }

        /** @var array<string, mixed>|null $responseData */
        $responseData = $response->json();

        return $responseData ?? [];
    }

    /**
     * Send a DELETE request to the TMDB API.
     *
     * @param string $endpoint API endpoint path.
     * @param array<string, mixed> $data Request body data.
     * @param array<string, mixed> $params Query parameters.
     * @return array<string, mixed>
     */
    private function delete(string $endpoint, array $data = [], array $params = []): array
    {
        /** @var string|null $apiKey */
        $apiKey = config('services.tmdb.key');

        if (empty($apiKey)) {
            return ['error' => 'TMDB API key not configured.'];
        }

        $params['api_key'] = $apiKey;

        $response = Http::withQueryParameters($params)->delete("{$this->baseUrl}{$endpoint}", $data);

        if ($response->failed()) {
            Log::error("TMDB API DELETE failed: {$endpoint}", ['status' => $response->status(), 'body' => $response->body()]);
            return ['error' => "Failed to delete from TMDB API: {$endpoint}"];
        }

        /** @var array<string, mixed>|null $responseData */
        $responseData = $response->json();

        return $responseData ?? [];
    }

    // ==========================================
    // ACCOUNT
    // ==========================================

    public function getAccount(string $accountId, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->get("/account/{$accountId}", $params);
    }

    public function markFavorite(string $accountId, string $mediaType, int $mediaId, bool $favorite, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->post("/account/{$accountId}/favorite", [
            'media_type' => $mediaType,
            'media_id' => $mediaId,
            'favorite' => $favorite,
        ], $params);
    }

    public function markWatchlist(string $accountId, string $mediaType, int $mediaId, bool $watchlist, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->post("/account/{$accountId}/watchlist", [
            'media_type' => $mediaType,
            'media_id' => $mediaId,
            'watchlist' => $watchlist,
        ], $params);
    }

    public function getFavoriteMovies(string $accountId, ?string $sessionId = null, int $page = 1): array
    {
        $params = ['page' => $page];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->get("/account/{$accountId}/favorite/movies", $params);
    }

    public function getFavoriteTv(string $accountId, ?string $sessionId = null, int $page = 1): array
    {
        $params = ['page' => $page];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->get("/account/{$accountId}/favorite/tv", $params);
    }

    public function getAccountLists(string $accountId, ?string $sessionId = null, int $page = 1): array
    {
        $params = ['page' => $page];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->get("/account/{$accountId}/lists", $params);
    }

    public function getRatedMovies(string $accountId, ?string $sessionId = null, int $page = 1): array
    {
        $params = ['page' => $page];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->get("/account/{$accountId}/rated/movies", $params);
    }

    public function getRatedTv(string $accountId, ?string $sessionId = null, int $page = 1): array
    {
        $params = ['page' => $page];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->get("/account/{$accountId}/rated/tv", $params);
    }

    public function getRatedTvEpisodes(string $accountId, ?string $sessionId = null, int $page = 1): array
    {
        $params = ['page' => $page];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->get("/account/{$accountId}/rated/tv/episodes", $params);
    }

    public function getWatchlistMovies(string $accountId, ?string $sessionId = null, int $page = 1): array
    {
        $params = ['page' => $page];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->get("/account/{$accountId}/watchlist/movies", $params);
    }

    public function getWatchlistTv(string $accountId, ?string $sessionId = null, int $page = 1): array
    {
        $params = ['page' => $page];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->get("/account/{$accountId}/watchlist/tv", $params);
    }

    // ==========================================
    // AUTHENTICATION
    // ==========================================

    public function getAuthentication(): array
    {
        return $this->get('/authentication');
    }

    public function createNewGuestSession(): array
    {
        return $this->get('/authentication/guest_session/new');
    }

    public function createNewToken(): array
    {
        return $this->get('/authentication/token/new');
    }

    public function createNewSession(array $data): array
    {
        return $this->post('/authentication/session/new', $data);
    }

    public function validateTokenWithLogin(array $data): array
    {
        return $this->post('/authentication/token/validate_with_login', $data);
    }

    public function convertSessionV4(array $data): array
    {
        return $this->post('/authentication/session/convert/4', $data);
    }

    public function deleteSession(array $data): array
    {
        return $this->delete('/authentication/session', $data);
    }

    // ==========================================
    // CERTIFICATIONS
    // ==========================================

    public function getMovieCertifications(): array
    {
        return $this->get('/certification/movie/list');
    }

    public function getTvCertifications(): array
    {
        return $this->get('/certification/tv/list');
    }

    // ==========================================
    // CHANGES
    // ==========================================

    public function getMovieChangesList(array $params = []): array
    {
        return $this->get('/movie/changes', $params);
    }

    public function getPersonChangesList(array $params = []): array
    {
        return $this->get('/person/changes', $params);
    }

    public function getTvChangesList(array $params = []): array
    {
        return $this->get('/tv/changes', $params);
    }

    // ==========================================
    // COLLECTIONS
    // ==========================================

    public function getCollection(int $collectionId): array
    {
        return $this->get("/collection/{$collectionId}");
    }

    public function getCollectionImages(int $collectionId): array
    {
        return $this->get("/collection/{$collectionId}/images");
    }

    public function getCollectionTranslations(int $collectionId): array
    {
        return $this->get("/collection/{$collectionId}/translations");
    }

    // ==========================================
    // COMPANIES
    // ==========================================

    public function getCompany(int $companyId): array
    {
        return $this->get("/company/{$companyId}");
    }

    public function getCompanyAlternativeNames(int $companyId): array
    {
        return $this->get("/company/{$companyId}/alternative_names");
    }

    public function getCompanyImages(int $companyId): array
    {
        return $this->get("/company/{$companyId}/images");
    }

    // ==========================================
    // CONFIGURATION
    // ==========================================

    public function getConfiguration(): array
    {
        return $this->get('/configuration');
    }

    public function getCountries(): array
    {
        return $this->get('/configuration/countries');
    }

    public function getJobs(): array
    {
        return $this->get('/configuration/jobs');
    }

    public function getLanguages(): array
    {
        return $this->get('/configuration/languages');
    }

    public function getPrimaryTranslations(): array
    {
        return $this->get('/configuration/primary_translations');
    }

    public function getTimezones(): array
    {
        return $this->get('/configuration/timezones');
    }

    // ==========================================
    // CREDITS
    // ==========================================

    public function getCredit(string $creditId): array
    {
        return $this->get("/credit/{$creditId}");
    }

    // ==========================================
    // DISCOVER
    // ==========================================

    public function discoverMovies(array $params = []): array
    {
        return $this->get('/discover/movie', $params);
    }

    public function discoverTv(array $params = []): array
    {
        return $this->get('/discover/tv', $params);
    }

    // ==========================================
    // FIND
    // ==========================================

    public function findExternal(string $externalId, array $params = []): array
    {
        return $this->get("/find/{$externalId}", $params);
    }

    // ==========================================
    // GENRES
    // ==========================================

    public function getMovieGenres(): array
    {
        return $this->get('/genre/movie/list');
    }

    public function getTvGenres(): array
    {
        return $this->get('/genre/tv/list');
    }

    // ==========================================
    // GUEST SESSIONS
    // ==========================================

    public function getGuestSessionRatedMovies(string $guestSessionId, int $page = 1): array
    {
        return $this->get("/guest_session/{$guestSessionId}/rated/movies", ['page' => $page]);
    }

    public function getGuestSessionRatedTv(string $guestSessionId, int $page = 1): array
    {
        return $this->get("/guest_session/{$guestSessionId}/rated/tv", ['page' => $page]);
    }

    public function getGuestSessionRatedTvEpisodes(string $guestSessionId, int $page = 1): array
    {
        return $this->get("/guest_session/{$guestSessionId}/rated/tv/episodes", ['page' => $page]);
    }

    // ==========================================
    // KEYWORDS
    // ==========================================

    public function getKeyword(int $keywordId): array
    {
        return $this->get("/keyword/{$keywordId}");
    }

    public function getKeywordMovies(int $keywordId, int $page = 1): array
    {
        return $this->get("/keyword/{$keywordId}/movies", ['page' => $page]);
    }

    // ==========================================
    // LISTS
    // ==========================================

    public function createList(array $data, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->post('/list', $data, $params);
    }

    public function getList(string $listId): array
    {
        return $this->get("/list/{$listId}");
    }

    public function getItemStatus(string $listId, array $params = []): array
    {
        return $this->get("/list/{$listId}/item_status", $params);
    }

    public function addListItem(string $listId, array $data, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->post("/list/{$listId}/add_item", $data, $params);
    }

    public function removeListItem(string $listId, array $data, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->post("/list/{$listId}/remove_item", $data, $params);
    }

    public function clearList(string $listId, array $data, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->post("/list/{$listId}/clear", $data, $params);
    }

    public function deleteList(string $listId, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->delete("/list/{$listId}", [], $params);
    }

    // ==========================================
    // MOVIE LISTS
    // ==========================================

    public function getNowPlayingMovies(int $page = 1): array
    {
        return $this->get('/movie/now_playing', ['page' => $page]);
    }

    public function getPopularMovies(int $page = 1): array
    {
        return $this->get('/movie/popular', ['page' => $page]);
    }

    public function getTopRatedMovies(int $page = 1): array
    {
        return $this->get('/movie/top_rated', ['page' => $page]);
    }

    public function getUpcomingMovies(int $page = 1): array
    {
        return $this->get('/movie/upcoming', ['page' => $page]);
    }

    // ==========================================
    // MOVIES
    // ==========================================

    public function getMovieDetails(int $movieId): array
    {
        return $this->get("/movie/{$movieId}");
    }

    public function getMovieAccountStates(int $movieId, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->get("/movie/{$movieId}/account_states", $params);
    }

    public function getMovieAlternativeTitles(int $movieId): array
    {
        return $this->get("/movie/{$movieId}/alternative_titles");
    }

    public function getMovieChanges(int $movieId, array $params = []): array
    {
        return $this->get("/movie/{$movieId}/changes", $params);
    }

    public function getMovieCredits(int $movieId): array
    {
        return $this->get("/movie/{$movieId}/credits");
    }

    public function getMovieExternalIds(int $movieId): array
    {
        return $this->get("/movie/{$movieId}/external_ids");
    }

    public function getMovieImages(int $movieId): array
    {
        return $this->get("/movie/{$movieId}/images");
    }

    public function getMovieKeywords(int $movieId): array
    {
        return $this->get("/movie/{$movieId}/keywords");
    }

    public function getLatestMovie(): array
    {
        return $this->get('/movie/latest');
    }

    public function getMovieLists(int $movieId, int $page = 1): array
    {
        return $this->get("/movie/{$movieId}/lists", ['page' => $page]);
    }

    public function getMovieRecommendations(int $movieId, int $page = 1): array
    {
        return $this->get("/movie/{$movieId}/recommendations", ['page' => $page]);
    }

    public function getMovieReleaseDates(int $movieId): array
    {
        return $this->get("/movie/{$movieId}/release_dates");
    }

    public function getMovieReviews(int $movieId, int $page = 1): array
    {
        return $this->get("/movie/{$movieId}/reviews", ['page' => $page]);
    }

    public function getMovieSimilar(int $movieId, int $page = 1): array
    {
        return $this->get("/movie/{$movieId}/similar", ['page' => $page]);
    }

    public function getMovieTranslations(int $movieId): array
    {
        return $this->get("/movie/{$movieId}/translations");
    }

    public function getMovieVideos(int $movieId): array
    {
        return $this->get("/movie/{$movieId}/videos");
    }

    public function getMovieWatchProviders(int $movieId): array
    {
        return $this->get("/movie/{$movieId}/watch/providers");
    }

    public function rateMovie(int $movieId, array $data, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->post("/movie/{$movieId}/rating", $data, $params);
    }

    public function deleteMovieRating(int $movieId, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->delete("/movie/{$movieId}/rating", [], $params);
    }

    // ==========================================
    // NETWORKS
    // ==========================================

    public function getNetwork(int $networkId): array
    {
        return $this->get("/network/{$networkId}");
    }

    public function getNetworkAlternativeNames(int $networkId): array
    {
        return $this->get("/network/{$networkId}/alternative_names");
    }

    public function getNetworkImages(int $networkId): array
    {
        return $this->get("/network/{$networkId}/images");
    }

    // ==========================================
    // PEOPLE LISTS
    // ==========================================

    public function getPopularPeople(int $page = 1): array
    {
        return $this->get('/person/popular', ['page' => $page]);
    }

    // ==========================================
    // PEOPLE
    // ==========================================

    public function getPersonDetails(int $personId): array
    {
        return $this->get("/person/{$personId}");
    }

    public function getPersonChanges(int $personId, array $params = []): array
    {
        return $this->get("/person/{$personId}/changes", $params);
    }

    public function getPersonCombinedCredits(int $personId): array
    {
        return $this->get("/person/{$personId}/combined_credits");
    }

    public function getPersonExternalIds(int $personId): array
    {
        return $this->get("/person/{$personId}/external_ids");
    }

    public function getPersonImages(int $personId): array
    {
        return $this->get("/person/{$personId}/images");
    }

    public function getLatestPerson(): array
    {
        return $this->get('/person/latest');
    }

    public function getPersonMovieCredits(int $personId): array
    {
        return $this->get("/person/{$personId}/movie_credits");
    }

    public function getPersonTvCredits(int $personId): array
    {
        return $this->get("/person/{$personId}/tv_credits");
    }

    public function getPersonTaggedImages(int $personId, int $page = 1): array
    {
        return $this->get("/person/{$personId}/tagged_images", ['page' => $page]);
    }

    public function getPersonTranslations(int $personId): array
    {
        return $this->get("/person/{$personId}/translations");
    }

    // ==========================================
    // REVIEWS
    // ==========================================

    public function getReview(string $reviewId): array
    {
        return $this->get("/review/{$reviewId}");
    }

    // ==========================================
    // SEARCH
    // ==========================================

    public function searchCollection(string $query, int $page = 1): array
    {
        return $this->get('/search/collection', ['query' => $query, 'page' => $page]);
    }

    public function searchCompany(string $query, int $page = 1): array
    {
        return $this->get('/search/company', ['query' => $query, 'page' => $page]);
    }

    public function searchKeyword(string $query, int $page = 1): array
    {
        return $this->get('/search/keyword', ['query' => $query, 'page' => $page]);
    }

    public function searchMovies(string $query, int $page = 1): array
    {
        return $this->get('/search/movie', ['query' => $query, 'page' => $page]);
    }

    public function searchMulti(string $query, int $page = 1): array
    {
        return $this->get('/search/multi', ['query' => $query, 'page' => $page]);
    }

    public function searchPerson(string $query, int $page = 1): array
    {
        return $this->get('/search/person', ['query' => $query, 'page' => $page]);
    }

    public function searchTv(string $query, int $page = 1): array
    {
        return $this->get('/search/tv', ['query' => $query, 'page' => $page]);
    }

    // ==========================================
    // TRENDING
    // ==========================================

    public function getTrendingAll(string $timeWindow = 'day'): array
    {
        return $this->get("/trending/all/{$timeWindow}");
    }

    public function getTrendingMovie(string $timeWindow = 'day'): array
    {
        return $this->get("/trending/movie/{$timeWindow}");
    }

    public function getTrendingMovies(): array
    {
        return $this->get('/trending/movie/day');
    }

    public function getTrendingPerson(string $timeWindow = 'day'): array
    {
        return $this->get("/trending/person/{$timeWindow}");
    }

    public function getTrendingTv(string $timeWindow = 'day'): array
    {
        return $this->get("/trending/tv/{$timeWindow}");
    }

    // ==========================================
    // TV SERIES LISTS
    // ==========================================

    public function getTvAiringToday(int $page = 1): array
    {
        return $this->get('/tv/airing_today', ['page' => $page]);
    }

    public function getTvOnTheAir(int $page = 1): array
    {
        return $this->get('/tv/on_the_air', ['page' => $page]);
    }

    public function getTvPopular(int $page = 1): array
    {
        return $this->get('/tv/popular', ['page' => $page]);
    }

    public function getTvTopRated(int $page = 1): array
    {
        return $this->get('/tv/top_rated', ['page' => $page]);
    }

    // ==========================================
    // TV SERIES
    // ==========================================

    public function getTvDetails(int $seriesId): array
    {
        return $this->get("/tv/{$seriesId}");
    }

    public function getTvAccountStates(int $seriesId, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->get("/tv/{$seriesId}/account_states", $params);
    }

    public function getTvAggregateCredits(int $seriesId): array
    {
        return $this->get("/tv/{$seriesId}/aggregate_credits");
    }

    public function getTvAlternativeTitles(int $seriesId): array
    {
        return $this->get("/tv/{$seriesId}/alternative_titles");
    }

    public function getTvChanges(int $seriesId, array $params = []): array
    {
        return $this->get("/tv/{$seriesId}/changes", $params);
    }

    public function getTvContentRatings(int $seriesId): array
    {
        return $this->get("/tv/{$seriesId}/content_ratings");
    }

    public function getTvCredits(int $seriesId): array
    {
        return $this->get("/tv/{$seriesId}/credits");
    }

    public function getTvEpisodeGroups(int $seriesId): array
    {
        return $this->get("/tv/{$seriesId}/episode_groups");
    }

    public function getTvExternalIds(int $seriesId): array
    {
        return $this->get("/tv/{$seriesId}/external_ids");
    }

    public function getTvImages(int $seriesId): array
    {
        return $this->get("/tv/{$seriesId}/images");
    }

    public function getTvKeywords(int $seriesId): array
    {
        return $this->get("/tv/{$seriesId}/keywords");
    }

    public function getLatestTv(): array
    {
        return $this->get('/tv/latest');
    }

    public function getTvLists(int $seriesId, int $page = 1): array
    {
        return $this->get("/tv/{$seriesId}/lists", ['page' => $page]);
    }

    public function getTvRecommendations(int $seriesId, int $page = 1): array
    {
        return $this->get("/tv/{$seriesId}/recommendations", ['page' => $page]);
    }

    public function getTvReviews(int $seriesId, int $page = 1): array
    {
        return $this->get("/tv/{$seriesId}/reviews", ['page' => $page]);
    }

    public function getTvScreenedTheatrically(int $seriesId): array
    {
        return $this->get("/tv/{$seriesId}/screened_theatrically");
    }

    public function getTvSimilar(int $seriesId, int $page = 1): array
    {
        return $this->get("/tv/{$seriesId}/similar", ['page' => $page]);
    }

    public function getTvTranslations(int $seriesId): array
    {
        return $this->get("/tv/{$seriesId}/translations");
    }

    public function getTvVideos(int $seriesId): array
    {
        return $this->get("/tv/{$seriesId}/videos");
    }

    public function getTvWatchProviders(int $seriesId): array
    {
        return $this->get("/tv/{$seriesId}/watch/providers");
    }

    public function rateTv(int $seriesId, array $data, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->post("/tv/{$seriesId}/rating", $data, $params);
    }

    public function deleteTvRating(int $seriesId, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->delete("/tv/{$seriesId}/rating", [], $params);
    }

    // ==========================================
    // TV SEASONS
    // ==========================================

    public function getTvSeasonDetails(int $seriesId, int $seasonNumber): array
    {
        return $this->get("/tv/{$seriesId}/season/{$seasonNumber}");
    }

    public function getTvSeasonAccountStates(int $seriesId, int $seasonNumber, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->get("/tv/{$seriesId}/season/{$seasonNumber}/account_states", $params);
    }

    public function getTvSeasonAggregateCredits(int $seriesId, int $seasonNumber): array
    {
        return $this->get("/tv/{$seriesId}/season/{$seasonNumber}/aggregate_credits");
    }

    public function getTvSeasonChanges(int $seasonId, array $params = []): array
    {
        return $this->get("/tv/season/{$seasonId}/changes", $params);
    }

    public function getTvSeasonCredits(int $seriesId, int $seasonNumber): array
    {
        return $this->get("/tv/{$seriesId}/season/{$seasonNumber}/credits");
    }

    public function getTvSeasonExternalIds(int $seriesId, int $seasonNumber): array
    {
        return $this->get("/tv/{$seriesId}/season/{$seasonNumber}/external_ids");
    }

    public function getTvSeasonImages(int $seriesId, int $seasonNumber): array
    {
        return $this->get("/tv/{$seriesId}/season/{$seasonNumber}/images");
    }

    public function getTvSeasonTranslations(int $seriesId, int $seasonNumber): array
    {
        return $this->get("/tv/{$seriesId}/season/{$seasonNumber}/translations");
    }

    public function getTvSeasonVideos(int $seriesId, int $seasonNumber): array
    {
        return $this->get("/tv/{$seriesId}/season/{$seasonNumber}/videos");
    }

    public function getTvSeasonWatchProviders(int $seriesId, int $seasonNumber): array
    {
        return $this->get("/tv/{$seriesId}/season/{$seasonNumber}/watch/providers");
    }

    // ==========================================
    // TV EPISODES
    // ==========================================

    public function getTvEpisodeDetails(int $seriesId, int $seasonNumber, int $episodeNumber): array
    {
        return $this->get("/tv/{$seriesId}/season/{$seasonNumber}/episode/{$episodeNumber}");
    }

    public function getTvEpisodeAccountStates(int $seriesId, int $seasonNumber, int $episodeNumber, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->get("/tv/{$seriesId}/season/{$seasonNumber}/episode/{$episodeNumber}/account_states", $params);
    }

    public function getTvEpisodeChanges(int $episodeId, array $params = []): array
    {
        return $this->get("/tv/episode/{$episodeId}/changes", $params);
    }

    public function getTvEpisodeCredits(int $seriesId, int $seasonNumber, int $episodeNumber): array
    {
        return $this->get("/tv/{$seriesId}/season/{$seasonNumber}/episode/{$episodeNumber}/credits");
    }

    public function getTvEpisodeExternalIds(int $seriesId, int $seasonNumber, int $episodeNumber): array
    {
        return $this->get("/tv/{$seriesId}/season/{$seasonNumber}/episode/{$episodeNumber}/external_ids");
    }

    public function getTvEpisodeImages(int $seriesId, int $seasonNumber, int $episodeNumber): array
    {
        return $this->get("/tv/{$seriesId}/season/{$seasonNumber}/episode/{$episodeNumber}/images");
    }

    public function getTvEpisodeTranslations(int $seriesId, int $seasonNumber, int $episodeNumber): array
    {
        return $this->get("/tv/{$seriesId}/season/{$seasonNumber}/episode/{$seasonNumber}/translations");
    }

    public function getTvEpisodeVideos(int $seriesId, int $seasonNumber, int $episodeNumber): array
    {
        return $this->get("/tv/{$seriesId}/season/{$seasonNumber}/episode/{$episodeNumber}/videos");
    }

    public function rateTvEpisode(int $seriesId, int $seasonNumber, int $episodeNumber, array $data, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->post("/tv/{$seriesId}/season/{$seasonNumber}/episode/{$episodeNumber}/rating", $data, $params);
    }

    public function deleteTvEpisodeRating(int $seriesId, int $seasonNumber, int $episodeNumber, ?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->delete("/tv/{$seriesId}/season/{$seasonNumber}/episode/{$episodeNumber}/rating", [], $params);
    }

    // ==========================================
    // TV EPISODE GROUPS
    // ==========================================

    public function getTvEpisodeGroup(string $tvEpisodeGroupId): array
    {
        return $this->get("/tv/episode_group/{$tvEpisodeGroupId}");
    }

    // ==========================================
    // WATCH PROVIDERS
    // ==========================================

    public function getWatchProviderRegions(array $params = []): array
    {
        return $this->get('/watch/providers/regions', $params);
    }

    public function getMovieWatchProvidersList(array $params = []): array
    {
        return $this->get('/watch/providers/movie', $params);
    }

    public function getTvWatchProvidersList(array $params = []): array
    {
        return $this->get('/watch/providers/tv', $params);
    }

    // ==========================================
    // ACCOUNT DETAILS (Legacy wrapper)
    // ==========================================

    public function getAccountDetails(?string $sessionId = null): array
    {
        $params = [];
        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        return $this->get('/account', $params);
    }
}
