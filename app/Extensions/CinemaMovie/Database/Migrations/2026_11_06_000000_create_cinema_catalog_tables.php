<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCinemaCatalogTables extends Migration
{
    public function up(): void
    {
        Schema::create('cinema_movies', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('original_title')->nullable();
            $table->string('original_language', 8)->nullable();
            $table->date('release_date')->nullable()->index();
            $table->unsignedSmallInteger('runtime_minutes')->nullable();
            $table->string('age_rating', 10)->nullable();
            $table->string('production_status', 20)->default('rumored');
            $table->string('publication_status', 20)->default('draft');
            $table->unsignedBigInteger('budget')->nullable();
            $table->unsignedBigInteger('revenue')->nullable();
            $table->decimal('vote_average', 3, 1)->nullable();
            $table->unsignedInteger('vote_count')->default(0);
            $table->timestamps();
            $table->index(['publication_status', 'release_date'], 'cinema_movies_publication_release_index');
        });

        Schema::create('cinema_genres', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('cinema_genre_movie', function (Blueprint $table): void {
            $table->foreignId('movie_id')->constrained('cinema_movies')->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained('cinema_genres')->cascadeOnDelete();
            $table->primary(['movie_id', 'genre_id']);
            $table->index('genre_id');
        });

        Schema::create('cinema_movie_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('movie_id')->constrained('cinema_movies')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title');
            $table->text('overview')->nullable();
            $table->string('tagline')->nullable();
            $table->timestamps();
            $table->unique(['movie_id', 'locale'], 'cinema_movie_translations_movie_locale_unique');
            $table->index(['locale', 'title'], 'cinema_movie_translations_locale_title_index');
        });

        Schema::create('cinema_genre_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('genre_id')->constrained('cinema_genres')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 100);
            $table->timestamps();
            $table->unique(['genre_id', 'locale'], 'cinema_genre_translations_genre_locale_unique');
            $table->unique(['locale', 'name'], 'cinema_genre_translations_locale_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cinema_genre_translations');
        Schema::dropIfExists('cinema_movie_translations');
        Schema::dropIfExists('cinema_genre_movie');
        Schema::dropIfExists('cinema_genres');
        Schema::dropIfExists('cinema_movies');
    }
}
