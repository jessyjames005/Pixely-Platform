<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('registry_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 32);
            $table->string('package_id', 255);
            $table->string('name');
            $table->timestamps();

            $table->unique(['type', 'package_id']);
            $table->index(['type', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registry_packages');
    }
};
