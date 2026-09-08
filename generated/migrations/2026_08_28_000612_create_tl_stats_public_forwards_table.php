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
        Schema::create('tl_stats_public_forwards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_stats_public_forwards_public_forwards', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stats_public_forwards')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('count');
            $table->text('next_offset')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_stats_public_forwards_public_forwards__forwards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stats_public_forwards_public_forwards')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_a496ab46e594386b2cc9');
        });
        Schema::create('tl_stats_public_forwards_public_forwards__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stats_public_forwards_public_forwards')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_22aa0955bf91f8353779');
        });
        Schema::create('tl_stats_public_forwards_public_forwards__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stats_public_forwards_public_forwards')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_10517a2b4415a5ff6ce9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_public_forwards_public_forwards__users');
        Schema::dropIfExists('tl_stats_public_forwards_public_forwards__chats');
        Schema::dropIfExists('tl_stats_public_forwards_public_forwards__forwards');
        Schema::dropIfExists('tl_stats_public_forwards_public_forwards');
        Schema::dropIfExists('tl_stats_public_forwards');
    }
};
