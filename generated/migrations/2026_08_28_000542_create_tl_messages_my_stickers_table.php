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
        Schema::create('tl_messages_my_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_274ab56c209bf27b9489d6f2');
            $table->index('account_id', 'ix_acea4b1fdc2d7d11bfa3446b');
        });
        Schema::create('tl_messages_my_stickers_my_stickers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_my_stickers')->cascadeOnDelete();
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_722a0aad11340abe4e485662');
        });
        Schema::create('tl_messages_my_stickers_my_stickers__sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_my_stickers_my_stickers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d694c9065c8d40ac0ac9');
            $table->index('account_id', 'ix_9443583e368e9855078f92ff');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_my_stickers_my_stickers__sets');
        Schema::dropIfExists('tl_messages_my_stickers_my_stickers');
        Schema::dropIfExists('tl_messages_my_stickers');
    }
};
