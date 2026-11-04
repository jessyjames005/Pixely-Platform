<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('registry_releases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('registry_package_id')->constrained()->cascadeOnDelete();
            $table->string('version', 128);
            $table->string('channel', 32);
            $table->timestamp('published_at');
            $table->string('package_name');
            $table->longText('manifest_json');
            $table->char('manifest_fingerprint', 64);
            $table->timestamps();

            $table->unique(['registry_package_id', 'version']);
            $table->index(['channel', 'version']);
            $table->index(['registry_package_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registry_releases');
    }
};
