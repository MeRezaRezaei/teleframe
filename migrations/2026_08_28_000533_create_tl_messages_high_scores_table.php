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
        Schema::create('tl_messages_high_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_83fed70fa7d898f5d158d0af');
            $table->index('account_id', 'ix_3e9298b92d0678bef06c70fc');
        });
        Schema::create('tl_messages_high_scores_high_scores', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_high_scores')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f5d34beec745e5449db398d5');
        });
        Schema::create('tl_messages_high_scores_high_scores__scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_high_scores_high_scores')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b628aa4fe56d296c7b55');
            $table->index('account_id', 'ix_e3d490606af11e3423234502');
        });
        Schema::create('tl_messages_high_scores_high_scores__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_high_scores_high_scores')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8b78be3854d6c48cd875');
            $table->index('account_id', 'ix_e92938a10141d16a05895fb2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_high_scores_high_scores__users');
        Schema::dropIfExists('tl_messages_high_scores_high_scores__scores');
        Schema::dropIfExists('tl_messages_high_scores_high_scores');
        Schema::dropIfExists('tl_messages_high_scores');
    }
};
