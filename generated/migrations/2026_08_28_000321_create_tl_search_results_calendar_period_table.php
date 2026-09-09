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
        Schema::create('tl_search_results_calendar_period', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_3271f72ada9241d64037ada6');
            $table->index('account_id', 'ix_bd2f7408f5b76e0a5449358e');
        });
        Schema::create('tl_search_results_calendar_period_search_resu_9116af4a75f6', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_search_results_calendar_period')->cascadeOnDelete();
            $table->integer('date');
            $table->integer('min_msg_id');
            $table->integer('max_msg_id');
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_04c79d3ebebf4f8877ddd7f2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_search_results_calendar_period_search_resu_9116af4a75f6');
        Schema::dropIfExists('tl_search_results_calendar_period');
    }
};
