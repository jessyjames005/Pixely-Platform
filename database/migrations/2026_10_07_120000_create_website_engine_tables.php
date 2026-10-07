<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('website_pages', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('status')->default('draft')->index();
            $table->string('template')->default('default');
            $table->json('seo')->nullable();
            $table->json('blocks')->nullable();
            $table->timestamps();
        });

        Schema::create('website_menus', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::create('website_menu_items', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('menu_id');
            $table->string('type');
            $table->string('title');
            $table->string('target_url', 2048)->nullable();
            $table->string('page_id')->nullable();
            $table->string('extension_id')->nullable();
            $table->string('slug')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('menu_id')->references('id')->on('website_menus')->cascadeOnDelete();
            $table->index(['menu_id', 'sort_order']);
            $table->index(['page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_menu_items');
        Schema::dropIfExists('website_menus');
        Schema::dropIfExists('website_pages');
    }
};
