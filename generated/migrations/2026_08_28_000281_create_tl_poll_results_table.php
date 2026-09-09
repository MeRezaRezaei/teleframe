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
        Schema::create('tl_poll_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_384e1ab672ea91a68b0b0cb5');
            $table->index('account_id', 'ix_12a5886cad9840713ffd0c32');
        });
        Schema::create('tl_poll_results_poll_results', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_poll_results')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('min')->default(false);
            $table->boolean('has_unread_votes')->default(false);
            $table->boolean('can_view_stats')->default(false);
            $table->integer('total_voters')->nullable();
            $table->text('solution')->nullable();
            $table->uuid('solution_media')->nullable();
            $table->index('solution_media', 'ix_c71466d487ad9296e90eaa0f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_88a31c1d6443f84709c4732a');
        });
        Schema::create('tl_poll_results_poll_results__results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_poll_results_poll_results')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e0a4c178a51bbdac0c3b');
            $table->index('account_id', 'ix_0c178e3e58e35242f2567fef');
        });
        Schema::create('tl_poll_results_poll_results__recent_voters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_poll_results_poll_results')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_69605c8b1272073288d0');
            $table->index('account_id', 'ix_8e466bf95b0722d39475bcf6');
        });
        Schema::create('tl_poll_results_poll_results__solution_entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_poll_results_poll_results')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ae059e706f0d064e36ab');
            $table->index('account_id', 'ix_b1f4fba907c79b80baa65034');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_poll_results_poll_results__solution_entities');
        Schema::dropIfExists('tl_poll_results_poll_results__recent_voters');
        Schema::dropIfExists('tl_poll_results_poll_results__results');
        Schema::dropIfExists('tl_poll_results_poll_results');
        Schema::dropIfExists('tl_poll_results');
    }
};
