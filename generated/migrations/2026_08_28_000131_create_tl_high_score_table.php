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
        Schema::create('tl_high_score', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_2a24c64e3a150a8b698308f0');
            $table->index('account_id', 'ix_cb2230653224bf754665589c');
        });
        Schema::create('tl_high_score_high_score', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_high_score')->cascadeOnDelete();
            $table->integer('pos');
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_a2be66a275e53c80223cb325');
            $table->integer('score');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a3d98b351300b3b916309eb7');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_high_score_high_score');
        Schema::dropIfExists('tl_high_score');
    }
};
