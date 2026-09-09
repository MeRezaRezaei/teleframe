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
        Schema::create('tl_stats_group_top_poster', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c75f3a0988555e3dd1ea97c6');
            $table->index('account_id', 'ix_ef94be02f6df9efd7b6289b9');
        });
        Schema::create('tl_stats_group_top_poster_stats_group_top_poster', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stats_group_top_poster')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_de00cfa7854855103b1a0d2b');
            $table->integer('messages');
            $table->integer('avg_chars');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4303b5cce09e4d498afc1e5c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_group_top_poster_stats_group_top_poster');
        Schema::dropIfExists('tl_stats_group_top_poster');
    }
};
