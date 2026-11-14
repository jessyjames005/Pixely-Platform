<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCinemaScreeningsTable extends Migration
{
    public function up(): void
    {
        Schema::create('cinema_screenings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('movie_id')->constrained('cinema_movies')->cascadeOnDelete();
            $table->dateTime('scheduled_at')->index();
            $table->string('room_name', 100);
            $table->string('status', 20)->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['scheduled_at', 'status'], 'cinema_screenings_scheduled_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cinema_screenings');
    }
}
