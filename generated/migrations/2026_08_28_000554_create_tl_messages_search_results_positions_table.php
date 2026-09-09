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
        Schema::create('tl_messages_search_results_positions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_2a86b0241254e77a93e7270d');
            $table->index('account_id', 'ix_cdf09e6d864fec8460a63f23');
        });
        Schema::create('tl_messages_search_results_positions_search_r_d401856bd5e6', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_search_results_positions')->cascadeOnDelete();
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5a3c3207902d227a2ef20f48');
        });
        Schema::create('tl_messages_search_results_positions_search_r_88108eb29971', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_search_results_positions_search_r_d401856bd5e6')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_25c21f08bdacfa5d120f');
            $table->index('account_id', 'ix_976d3084775f880c4db76d10');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_search_results_positions_search_r_88108eb29971');
        Schema::dropIfExists('tl_messages_search_results_positions_search_r_d401856bd5e6');
        Schema::dropIfExists('tl_messages_search_results_positions');
    }
};
