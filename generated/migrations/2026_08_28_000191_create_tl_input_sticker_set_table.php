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
            $table->index('constructor_id', 'ix_58a52fb997015a070dfa147c');
            $table->index('account_id', 'ix_7a77ef5ecaf0c7dc984b7f88');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_animated_emoji', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e142456a1513003c3caa3d41');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_animat_7ff1565b3f75', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6f82edff8f6a893c56cc6965');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_dice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->text('emoticon');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6a02ec72bdd8d2082c951c78');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_emoji__d93dca74142c', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5de645f10b04f8e1e885c0c3');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_emoji__f673730c96f9', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a20e80e22df66e9c1e0e366b');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_emoji__e38d997c577e', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4b43d9468f0cd6a085392021');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_emoji__d4587551ad4b', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e6d517c986f3e19d50a2b0b0');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f7ebb88948b3fdd669e801e5');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_i_d', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2f7b20421adc70a64797a649');
            $table->unique(['account_id', 'tl_id'], 'ux_080d56fcfbf2f5e2ac53');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_premium_gifts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6938f5525e0eb59184dbf38c');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_short_name', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->text('short_name');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f652aaa2c0bc826b4f0327bc');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_ton_gifts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c09a71e2f30e19cb568d7dc7');
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
