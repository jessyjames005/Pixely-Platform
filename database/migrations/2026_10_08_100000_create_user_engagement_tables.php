<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create generic favorites and history tables shared by User Space and extensions.
     */
    public function up(): void
    {
        Schema::create('user_favorites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('resource_type', 100);
            $table->string('resource_id', 191);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'resource_type', 'resource_id']);
            $table->index(['resource_type', 'resource_id']);
        });

        Schema::create('user_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('resource_type', 100);
            $table->string('resource_id', 191);
            $table->string('action', 50)->default('view');
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['user_id', 'resource_type', 'resource_id']);
        });
    }

    /**
     * Drop generic user engagement tables.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_history');
        Schema::dropIfExists('user_favorites');
    }
};
