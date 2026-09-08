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
        Schema::create('tl_input_sticker_set', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_animated_emoji', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_animat_7ff1565b3f75', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_dice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->text('emoticon');
            $table->timestamps();
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_emoji__d93dca74142c', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_emoji__f673730c96f9', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_emoji__e38d997c577e', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_emoji__d4587551ad4b', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_i_d', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->timestamps();
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_premium_gifts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_short_name', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->text('short_name');
            $table->timestamps();
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_ton_gifts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_sticker_set_input_sticker_set_ton_gifts');
        Schema::dropIfExists('tl_input_sticker_set_input_sticker_set_short_name');
        Schema::dropIfExists('tl_input_sticker_set_input_sticker_set_premium_gifts');
        Schema::dropIfExists('tl_input_sticker_set_input_sticker_set_i_d');
        Schema::dropIfExists('tl_input_sticker_set_input_sticker_set_empty');
        Schema::dropIfExists('tl_input_sticker_set_input_sticker_set_emoji__d4587551ad4b');
        Schema::dropIfExists('tl_input_sticker_set_input_sticker_set_emoji__e38d997c577e');
        Schema::dropIfExists('tl_input_sticker_set_input_sticker_set_emoji__f673730c96f9');
        Schema::dropIfExists('tl_input_sticker_set_input_sticker_set_emoji__d93dca74142c');
        Schema::dropIfExists('tl_input_sticker_set_input_sticker_set_dice');
        Schema::dropIfExists('tl_input_sticker_set_input_sticker_set_animat_7ff1565b3f75');
        Schema::dropIfExists('tl_input_sticker_set_input_sticker_set_animated_emoji');
        Schema::dropIfExists('tl_input_sticker_set');
    }
};
