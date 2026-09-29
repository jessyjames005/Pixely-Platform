<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extension_migrations', function (Blueprint $table): void {
            $table->id();
            $table->string('extension_id');
            $table->string('migration');
            $table->unsignedInteger('batch');
            $table->timestamps();

            $table->unique(
                ['extension_id', 'migration'],
                'extension_migrations_extension_migration_unique',
            );

            $table->index(
                ['extension_id', 'batch'],
                'extension_migrations_extension_batch_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extension_migrations');
    }
};
