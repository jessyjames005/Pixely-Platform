<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_pages', function (Blueprint $table): void {
            $table->string('locale', 10)->nullable()->after('slug');
            $table->index(['slug', 'locale']);
        });
        Schema::table('website_menus', function (Blueprint $table): void {
            $table->string('locale', 10)->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('website_pages', function (Blueprint $table): void {
            $table->dropIndex(['slug', 'locale']);
            $table->dropColumn('locale');
        });
        Schema::table('website_menus', function (Blueprint $table): void {
            $table->dropColumn('locale');
        });
    }
};
