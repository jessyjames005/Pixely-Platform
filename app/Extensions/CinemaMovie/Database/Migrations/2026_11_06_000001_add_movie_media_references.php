<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMovieMediaReferences extends Migration
{
    public function up(): void
    {
        Schema::table('cinema_movies', function (Blueprint $table): void {
            $table->foreignId('poster_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->foreignId('backdrop_file_id')->nullable()->constrained('files')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cinema_movies', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('poster_file_id');
            $table->dropConstrainedForeignId('backdrop_file_id');
        });
    }
}
