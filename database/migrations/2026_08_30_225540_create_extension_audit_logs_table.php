<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extension_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('extension_id');
            $table->string('action'); // install | update | uninstall | enable | disable
            $table->string('version')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            // No update/delete route is ever exposed for this table —
            // it is intentionally append-only at the application level.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extension_audit_logs');
    }
};
