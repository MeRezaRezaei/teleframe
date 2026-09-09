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
            $table->index('constructor_id', 'ix_1ceef4011112ebfbef9fb82a');
            $table->index('account_id', 'ix_726314532d54569d50fa9bdc');
        });
        Schema::create('tl_stats_megagroup_stats_megagroup_stats', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stats_megagroup_stats')->cascadeOnDelete();
            $table->uuid('period');
            $table->index('period', 'ix_b040457e5d57e83519cff744');
            $table->uuid('members');
            $table->index('members', 'ix_7ad00d25f6ed45d4b73baff7');
            $table->uuid('messages');
            $table->index('messages', 'ix_776f4430166235813d12c8a3');
            $table->uuid('viewers');
            $table->index('viewers', 'ix_c5c0c7996e2a054ab41256a9');
            $table->uuid('posters');
            $table->index('posters', 'ix_36ba1e5ac256b31e112ba016');
            $table->uuid('growth_graph');
            $table->index('growth_graph', 'ix_93c3e17a7b349745644a444b');
            $table->uuid('members_graph');
            $table->index('members_graph', 'ix_478570888ef76f048a257011');
            $table->uuid('new_members_by_source_graph');
            $table->index('new_members_by_source_graph', 'ix_c8357b52d04a694d5de211e4');
            $table->uuid('languages_graph');
            $table->index('languages_graph', 'ix_e5e98328407929b5234e04c7');
            $table->uuid('messages_graph');
            $table->index('messages_graph', 'ix_83b043fd9a609f8a1478d450');
            $table->uuid('actions_graph');
            $table->index('actions_graph', 'ix_1f24672ea9d7b82573f06ff0');
            $table->uuid('top_hours_graph');
            $table->index('top_hours_graph', 'ix_7ddf32562a95894284aa5191');
            $table->uuid('weekdays_graph');
            $table->index('weekdays_graph', 'ix_17392ce580ad1ab33aaddc77');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3c57265e534e10d6f9d3247a');
        });
        Schema::create('tl_stats_megagroup_stats_megagroup_stats__top_posters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stats_megagroup_stats_megagroup_stats')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_28684d1ad63ffa037ed5');
            $table->index('account_id', 'ix_69dfb81911784aff44f45424');
        });
        Schema::create('tl_stats_megagroup_stats_megagroup_stats__top_admins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stats_megagroup_stats_megagroup_stats')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_3097fa9c3b1eeaf21122');
            $table->index('account_id', 'ix_8ef0b7b6d54f332f2b7f98f5');
        });
        Schema::create('tl_stats_megagroup_stats_megagroup_stats__top_inviters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stats_megagroup_stats_megagroup_stats')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_eae2e35ca2963419f4f4');
            $table->index('account_id', 'ix_8d1e56d70310bcca2668fe06');
        });
        Schema::create('tl_stats_megagroup_stats_megagroup_stats__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stats_megagroup_stats_megagroup_stats')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_05da8264ed0535ff28cf');
            $table->index('account_id', 'ix_f33c31001e45fb30ae37876e');
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
