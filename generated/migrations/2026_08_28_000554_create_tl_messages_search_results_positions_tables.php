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
        Schema::create('tl_messages_search_results_positions_search_r_d401856bd5e6', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_97a0c4a0528a6c5b9a79932e');
            $table->index('account_id', 'ix_5a3c3207902d227a2ef20f48');
        });
        Schema::create('tl_messages_search_results_positions_search_r_88108eb29971', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_search_results_positions_search_r_d401856bd5e6', 'id', 'fk_6498b7489358475445f45f26')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_25c21f08bdacfa5d120f');
            $table->index('account_id', 'ix_976d3084775f880c4db76d10');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_search_results_positions_search_r_88108eb29971');
        Schema::dropIfExists('tl_messages_search_results_positions_search_r_d401856bd5e6');
    }
};
