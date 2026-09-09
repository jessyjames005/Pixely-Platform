<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tuleap_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('identifier')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('tuleap_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('tuleap_projects');
            $table->string('title');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('tuleap_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('tuleap_projects');
            $table->string('name');
            $table->string('tuleap_username')->nullable();
            $table->timestamps();
        });

        Schema::create('tuleap_sprint_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_id')->nullable()->constrained('tuleap_milestones');
            $table->text('objective')->default('');
            $table->integer('confidence_index')->nullable();
            $table->integer('pct_evolution')->default(50);
            $table->integer('pct_analysis')->default(30);
            $table->integer('pct_bug')->default(20);
            $table->integer('working_days')->default(10);
            $table->float('velocity_per_day')->default(1.0);
            $table->text('review_comment')->default('');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tuleap_caf_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sprint_id')->constrained('tuleap_sprint_configs');
            $table->foreignId('member_id')->constrained('tuleap_team_members');
            $table->float('value')->default(10.0);
            $table->timestamps();
            $table->unique(['sprint_id', 'member_id']);
        });

        Schema::create('tuleap_burndown_cache', function (Blueprint $table) {
            $table->foreignId('sprint_id')->constrained('tuleap_sprint_configs');
            $table->date('day');
            $table->float('remaining_points');
            $table->primary(['sprint_id', 'day']);
        });

        Schema::create('tuleap_app_configs', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value');
            $table->timestamps();
        });

        Schema::create('tuleap_cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value');
            $table->dateTime('cached_at')->useCurrent();
            $table->dateTime('expires_at');
        });

        Schema::create('tuleap_retro_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sprint_id')->constrained('tuleap_sprint_configs');
            $table->foreignId('project_id')->nullable()->constrained('tuleap_projects');
            $table->foreignId('member_id')->nullable()->constrained('tuleap_team_members');
            $table->string('category');
            $table->text('text');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuleap_retro_actions');
        Schema::dropIfExists('tuleap_app_configs');
        Schema::dropIfExists('tuleap_cache');
        Schema::dropIfExists('tuleap_burndown_cache');
        Schema::dropIfExists('tuleap_caf_records');
        Schema::dropIfExists('tuleap_sprint_configs');
        Schema::dropIfExists('tuleap_team_members');
        Schema::dropIfExists('tuleap_milestones');
        Schema::dropIfExists('tuleap_projects');
    }
};