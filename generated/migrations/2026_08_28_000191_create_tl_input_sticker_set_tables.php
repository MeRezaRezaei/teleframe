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
        Schema::create('tl_input_sticker_set_input_sticker_set_animated_emoji', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_68813b1f82a68c77553e82e6');
            $table->index('account_id', 'ix_e142456a1513003c3caa3d41');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_animat_7ff1565b3f75', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_426570c7e440e421bff4bf57');
            $table->index('account_id', 'ix_6f82edff8f6a893c56cc6965');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_dice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('emoticon')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0a8e5a9e228b39abd4be091f');
            $table->index('account_id', 'ix_6a02ec72bdd8d2082c951c78');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_emoji__d93dca74142c', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ae04414aaf61b8c64f44937d');
            $table->index('account_id', 'ix_5de645f10b04f8e1e885c0c3');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_emoji__f673730c96f9', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_19a953b68eea212caae58313');
            $table->index('account_id', 'ix_a20e80e22df66e9c1e0e366b');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_emoji__e38d997c577e', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4abd887e7317588482df3c4a');
            $table->index('account_id', 'ix_4b43d9468f0cd6a085392021');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_emoji__d4587551ad4b', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5a64f437bb646090be5c82a6');
            $table->index('account_id', 'ix_e6d517c986f3e19d50a2b0b0');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1677b02056f21418cefe65fe');
            $table->index('account_id', 'ix_f7ebb88948b3fdd669e801e5');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_i_d', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9a7ed4128bebf74dadfe9f45');
            $table->index('account_id', 'ix_2f7b20421adc70a64797a649');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_premium_gifts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0dead23cc95d478b7f16019c');
            $table->index('account_id', 'ix_6938f5525e0eb59184dbf38c');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_short_name', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('short_name')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9dfae60d454985c4e6edc069');
            $table->index('account_id', 'ix_f652aaa2c0bc826b4f0327bc');
        });
        Schema::create('tl_input_sticker_set_input_sticker_set_ton_gifts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6ef7940f78ac5dd4698e2601');
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
    }
};
