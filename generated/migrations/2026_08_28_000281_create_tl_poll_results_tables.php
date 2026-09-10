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
        Schema::create('tl_poll_results_poll_results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('min')->default(false);
            $table->boolean('has_unread_votes')->default(false);
            $table->boolean('can_view_stats')->default(false);
            $table->integer('total_voters')->nullable();
            $table->text('solution')->nullable();
            $table->bigInteger('solution_media')->nullable();
            $table->index('solution_media', 'ix_c71466d487ad9296e90eaa0f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f636b4d52d1fdb04c81ef39d');
            $table->index('account_id', 'ix_88a31c1d6443f84709c4732a');
        });
        Schema::create('tl_poll_results_poll_results__results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_poll_results_poll_results', 'id', 'fk_e92900af84a2941a8dd8bfc5')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e0a4c178a51bbdac0c3b');
            $table->index('account_id', 'ix_0c178e3e58e35242f2567fef');
        });
        Schema::create('tl_poll_results_poll_results__recent_voters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_poll_results_poll_results', 'id', 'fk_60fb612fe63240194af6f358')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_69605c8b1272073288d0');
            $table->index('account_id', 'ix_8e466bf95b0722d39475bcf6');
        });
        Schema::create('tl_poll_results_poll_results__solution_entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_poll_results_poll_results', 'id', 'fk_a06df90074cb5a7bf10f847a')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
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
    }
};
