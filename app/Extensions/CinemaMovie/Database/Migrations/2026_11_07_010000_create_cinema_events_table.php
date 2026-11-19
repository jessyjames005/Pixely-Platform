<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCinemaEventsTable extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cinema_screenings');

        Schema::create('cinema_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('movie_id')->nullable()->constrained('cinema_movies')->nullOnDelete();
            $table->string('title');
            $table->string('event_type', 30)->default('screening'); // screening, meet_and_greet, autograph, masterclass, festival_day, qa
            $table->dateTime('start_at')->index();
            $table->dateTime('end_at')->index();
            $table->string('location', 150);
            $table->text('participants')->nullable(); // actor names, guests, etc.
            $table->string('status', 20)->default('scheduled'); // scheduled, confirmed, cancelled
            $table->string('color', 20)->default('#1976D2'); // calendar color
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['start_at', 'end_at', 'event_type'], 'cinema_events_schedule_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cinema_events');
    }
}
