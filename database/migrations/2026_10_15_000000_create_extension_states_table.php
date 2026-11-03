<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /** Create the durable runtime state table for discovered extensions. */
    public function up(): void
    {
        Schema::create('extension_states', function (Blueprint $table): void {
            $table->string('extension_id')->primary();
            $table->string('extension_class');
            $table->string('status', 16)->default('enabled');
            $table->timestamps();

            $table->index('status');
        });
    }

    /** Remove the extension runtime state table on rollback. */
    public function down(): void
    {
        Schema::dropIfExists('extension_states');
    }
};
