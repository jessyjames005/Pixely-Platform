<?php

declare(strict_types=1);

namespace App\Core\Websites\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for the website engine tables.
 */
class CreateWebsiteEngineTables extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        // Website pages
        Schema::create('website_pages', function (Blueprint $table) {
            $table->id();
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
        Schema::create('website_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        // Menu items
        Schema::create('website_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->onDelete('cascade');
            $table->string('type'); // page, extension, external
            $table->string('title');
            $table->string('target_url')->nullable();
            $table->string('page_id')->nullable(); // reference to website_pages.id
            $table->string('extension_id')->nullable(); // reference to extensions.id
            $table->string('slug')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['menu_id']);
            $table->index(['type']);
            $table->index(['page_id']);
            $table->index(['extension_id']);
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_menu_items');
        Schema::dropIfExists('website_menus');
        Schema::dropIfExists('website_pages');
    }
}