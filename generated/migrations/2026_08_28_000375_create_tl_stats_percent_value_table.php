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
        Schema::create('tl_stats_percent_value', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_bc1bd13cf47e8e6851881a8b');
            $table->index('account_id', 'ix_1ac2ab98c1af69d79d157f20');
        });
        Schema::create('tl_stats_percent_value_stats_percent_value', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stats_percent_value')->cascadeOnDelete();
            $table->double('part');
            $table->double('total');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_47b0d7edb140d950ebce62aa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_percent_value_stats_percent_value');
        Schema::dropIfExists('tl_stats_percent_value');
    }
};
