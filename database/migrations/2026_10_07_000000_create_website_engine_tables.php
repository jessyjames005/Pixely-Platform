<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Website Engine tables: pages, menus and menu items.
 *
 * Identifiers are strings to match the in-memory models
 * (PageModel, Menu, MenuItem generate string IDs such as
 * "page_..." / "menu_..."); persistence lands in a later
 * sprint and must keep this contract.
 */
return new class () extends Migration {
    public function up(): void
    {
        // Website pages
        Schema::create('website_pages', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('status')->default('draft'); // draft, published, archived
            $table->string('template')->default('default');
            $table->json('seo')->nullable();
            $table->json('blocks')->nullable();
            $table->timestamps();

            $table->index(['slug']);
            $table->index(['status']);
        });

        // Navigation menus
        Schema::create('website_menus', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        // Menu items
        Schema::create('website_menu_items', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('menu_id');
            $table->string('type'); // page, extension, external
            $table->string('title');
            $table->string('target_url')->nullable();
            $table->string('page_id')->nullable(); // reference to website_pages.id
            $table->string('extension_id')->nullable(); // reference to extensions.id
            $table->string('slug')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('menu_id')
                ->references('id')
                ->on('website_menus')
                ->cascadeOnDelete();

            $table->index(['menu_id']);
            $table->index(['type']);
            $table->index(['page_id']);
            $table->index(['extension_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_menu_items');
        Schema::dropIfExists('website_menus');
        Schema::dropIfExists('website_pages');
    }
};