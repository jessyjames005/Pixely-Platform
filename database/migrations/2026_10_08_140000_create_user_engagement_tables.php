<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_favorites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('resource_type', 64);
            $table->string('resource_id', 128);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'resource_type', 'resource_id']);
            $table->index(['user_id', 'resource_type']);
        });

        Schema::create('user_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('resource_type', 64);
            $table->string('resource_id', 128);
            $table->string('action', 64);
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at');
            $table->index(['user_id', 'occurred_at']);
            $table->index(['user_id', 'resource_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_history');
        Schema::dropIfExists('user_favorites');
    }
};
