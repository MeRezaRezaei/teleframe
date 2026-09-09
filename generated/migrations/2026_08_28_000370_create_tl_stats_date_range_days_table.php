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
        Schema::create('tl_stats_date_range_days', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_a2acb3669440b888ff22a32a');
            $table->index('account_id', 'ix_a7ca637b48f239236a3eb9dd');
        });
        Schema::create('tl_stats_date_range_days_stats_date_range_days', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stats_date_range_days')->cascadeOnDelete();
            $table->integer('min_date');
            $table->integer('max_date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_efa139d221a64ffeaa3feb79');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_date_range_days_stats_date_range_days');
        Schema::dropIfExists('tl_stats_date_range_days');
    }
};
