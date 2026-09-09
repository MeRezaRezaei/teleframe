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
        Schema::create('tl_stats_abs_value_and_prev', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_6b6020e6492d9e8c2a134862');
            $table->index('account_id', 'ix_324e2254955b5cd434f2faed');
        });
        Schema::create('tl_stats_abs_value_and_prev_stats_abs_value_and_prev', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stats_abs_value_and_prev')->cascadeOnDelete();
            $table->double('tl_current');
            $table->double('previous');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_530ba376bc6767d32b13fcbe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_abs_value_and_prev_stats_abs_value_and_prev');
        Schema::dropIfExists('tl_stats_abs_value_and_prev');
    }
};
