<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tl_stats_megagroup_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_stats_megagroup_stats_megagroup_stats', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stats_megagroup_stats')->cascadeOnDelete();
            $table->uuid('period');
            $table->uuid('members');
            $table->uuid('messages');
            $table->uuid('viewers');
            $table->uuid('posters');
            $table->uuid('growth_graph');
            $table->uuid('members_graph');
            $table->uuid('new_members_by_source_graph');
            $table->uuid('languages_graph');
            $table->uuid('messages_graph');
            $table->uuid('actions_graph');
            $table->uuid('top_hours_graph');
            $table->uuid('weekdays_graph');
            $table->timestamps();
        });
        Schema::create('tl_stats_megagroup_stats_megagroup_stats__top_posters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stats_megagroup_stats_megagroup_stats')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_28684d1ad63ffa037ed5');
        });
        Schema::create('tl_stats_megagroup_stats_megagroup_stats__top_admins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stats_megagroup_stats_megagroup_stats')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_3097fa9c3b1eeaf21122');
        });
        Schema::create('tl_stats_megagroup_stats_megagroup_stats__top_inviters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stats_megagroup_stats_megagroup_stats')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_eae2e35ca2963419f4f4');
        });
        Schema::create('tl_stats_megagroup_stats_megagroup_stats__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stats_megagroup_stats_megagroup_stats')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_05da8264ed0535ff28cf');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_megagroup_stats_megagroup_stats__users');
        Schema::dropIfExists('tl_stats_megagroup_stats_megagroup_stats__top_inviters');
        Schema::dropIfExists('tl_stats_megagroup_stats_megagroup_stats__top_admins');
        Schema::dropIfExists('tl_stats_megagroup_stats_megagroup_stats__top_posters');
        Schema::dropIfExists('tl_stats_megagroup_stats_megagroup_stats');
        Schema::dropIfExists('tl_stats_megagroup_stats');
    }
};
