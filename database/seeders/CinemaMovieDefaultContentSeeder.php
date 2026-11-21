<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Extensions\CinemaMovie\Models\Genre;
use App\Extensions\CinemaMovie\Models\GenreTranslation;
use App\Extensions\CinemaMovie\Models\Movie;
use App\Extensions\CinemaMovie\Models\MovieTranslation;
use Illuminate\Database\Seeder;

/**
 * Seeds sample movies and genres for the CinemaMovie extension.
 */
final class CinemaMovieDefaultContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $actionGenre = Genre::firstOrCreate(
            ['slug' => 'action'],
            ['slug' => 'action']
        );
        GenreTranslation::updateOrCreate(
            ['genre_id' => $actionGenre->id, 'locale' => 'en'],
            ['name' => 'Action']
        );
        GenreTranslation::updateOrCreate(
            ['genre_id' => $actionGenre->id, 'locale' => 'fr'],
            ['name' => 'Action']
        );

        $sciFiGenre = Genre::firstOrCreate(
            ['slug' => 'sci-fi'],
            ['slug' => 'sci-fi']
        );
        GenreTranslation::updateOrCreate(
            ['genre_id' => $sciFiGenre->id, 'locale' => 'en'],
            ['name' => 'Sci-Fi']
        );
        GenreTranslation::updateOrCreate(
            ['genre_id' => $sciFiGenre->id, 'locale' => 'fr'],
            ['name' => 'Science-Fiction']
        );

        $movie1 = Movie::firstOrCreate(
            ['slug' => 'inception'],
            [
                'original_title' => 'Inception',
                'original_language' => 'en',
                'release_date' => '2010-07-16',
                'runtime_minutes' => 148,
                'age_rating' => 'PG-13',
                'production_status' => 'released',
                'publication_status' => 'published',
                'vote_average' => 8.8,
                'vote_count' => 32000,
            ]
        );

        MovieTranslation::updateOrCreate(
            ['movie_id' => $movie1->id, 'locale' => 'en'],
            [
                'title' => 'Inception',
                'overview' => 'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.',
            ]
        );
        MovieTranslation::updateOrCreate(
            ['movie_id' => $movie1->id, 'locale' => 'fr'],
            [
                'title' => 'Inception',
                'overview' => 'Dom Cobb est un voleur expérimenté dans l art périlleux de l extraction : le vol de secrets d entreprise...',
            ]
        );

        $movie1->genres()->sync([$actionGenre->id, $sciFiGenre->id]);

        $movie2 = Movie::firstOrCreate(
            ['slug' => 'interstellar'],
            [
                'original_title' => 'Interstellar',
                'original_language' => 'en',
                'release_date' => '2014-11-07',
                'runtime_minutes' => 169,
                'age_rating' => 'PG-13',
                'production_status' => 'released',
                'publication_status' => 'published',
                'vote_average' => 8.7,
                'vote_count' => 30000,
            ]
        );

        MovieTranslation::updateOrCreate(
            ['movie_id' => $movie2->id, 'locale' => 'en'],
            [
                'title' => 'Interstellar',
                'overview' => 'When Earth becomes uninhabitable in the future, a farmer and ex-NASA pilot, Joseph Cooper, is tasked to pilot a spacecraft, along with a team of researchers, to find a new planet for humans.',
            ]
        );
        MovieTranslation::updateOrCreate(
            ['movie_id' => $movie2->id, 'locale' => 'fr'],
            [
                'title' => 'Interstellar',
                'overview' => 'Le film raconte les aventures d un groupe d explorateurs qui utilisent une faille dans l espace-temps afin de parcourir des distances incroyables...',
            ]
        );

        $movie2->genres()->sync([$sciFiGenre->id]);
    }
}
