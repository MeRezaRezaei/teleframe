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
        Schema::create('tl_decrypted_message_media_decrypted_message_media_audio', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('duration')->nullable();
            $table->text('mime_type')->nullable();
            $table->integer('tl_size')->nullable();
            $table->binary('tl_key')->nullable();
            $table->binary('iv')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_41a565f6af84ed223b1a95d5');
            $table->index('account_id', 'ix_7f631b09179d20ce1ccdcf5a');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message_media_contact', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('phone_number')->nullable();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->integer('user_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_695859c5574bb4a808c19083');
            $table->index('account_id', 'ix_07719fbf8bed592c182ef5bf');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message__1652f9c81874', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->binary('thumb')->nullable();
            $table->integer('thumb_w')->nullable();
            $table->integer('thumb_h')->nullable();
            $table->text('mime_type')->nullable();
            $table->bigInteger('tl_size')->nullable();
            $table->binary('tl_key')->nullable();
            $table->binary('iv')->nullable();
            $table->text('caption')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2cd492dea95e90f86ee3d74e');
            $table->index('account_id', 'ix_f0c155d16e4b1d9e787956c3');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message__fa113370e99a', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_d00b451049fd434ded51f61a')->references('id')->on('tl_decrypted_message_media_decrypted_message__1652f9c81874')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c8e475bd29abfccf575e');
            $table->index('account_id', 'ix_6f7b8619c0f4f21aeec7b1ff');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message_media_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c789e4847a3f26676f65c9b9');
            $table->index('account_id', 'ix_35c87a313923adfbe60a2f9d');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message__37e1a7328ec6', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->integer('date')->nullable();
            $table->text('mime_type')->nullable();
            $table->integer('tl_size')->nullable();
            $table->bigInteger('thumb')->nullable();
            $table->index('thumb', 'ix_f93fa97a88279ede0d3cc77c');
            $table->integer('dc_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4276bb7f8e7cb1149026c20d');
            $table->index('account_id', 'ix_3bde2034c1fd539a223ff8e8');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message__39292adb140f', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_2aa9e18c0ec9ee9bee0b47ce')->references('id')->on('tl_decrypted_message_media_decrypted_message__37e1a7328ec6')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_12da5ccb04219915c5a4');
            $table->index('account_id', 'ix_ac86c02c474f8c08f60f66f7');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message__a644abd2ed29', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->double('lat')->nullable();
            $table->double('tl_long')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3be5bd8ee00e6bfe48d30e49');
            $table->index('account_id', 'ix_3228241cd4000d03d8a2d00d');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message_media_photo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->binary('thumb')->nullable();
            $table->integer('thumb_w')->nullable();
            $table->integer('thumb_h')->nullable();
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->integer('tl_size')->nullable();
            $table->binary('tl_key')->nullable();
            $table->binary('iv')->nullable();
            $table->text('caption')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2a89909e0f247b4c83f77d71');
            $table->index('account_id', 'ix_c860e3e291f87de3439e72b2');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message_media_venue', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->double('lat')->nullable();
            $table->double('tl_long')->nullable();
            $table->text('title')->nullable();
            $table->text('address')->nullable();
            $table->text('provider')->nullable();
            $table->text('venue_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fa3935d5a51432ea4c5e8395');
            $table->index('account_id', 'ix_6a8a999e5953ae846c118702');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message_media_video', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->binary('thumb')->nullable();
            $table->integer('thumb_w')->nullable();
            $table->integer('thumb_h')->nullable();
            $table->integer('duration')->nullable();
            $table->text('mime_type')->nullable();
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->integer('tl_size')->nullable();
            $table->binary('tl_key')->nullable();
            $table->binary('iv')->nullable();
            $table->text('caption')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0465488f6321dc0591d39841');
            $table->index('account_id', 'ix_fb3bdc7903af2c6cdd9b6cb5');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message__8fed41c9f5cf', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dc53507c830e4179df166af3');
            $table->index('account_id', 'ix_bd143730d4a01a5601a9b41a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_decrypted_message_media_decrypted_message__8fed41c9f5cf');
        Schema::dropIfExists('tl_decrypted_message_media_decrypted_message_media_video');
        Schema::dropIfExists('tl_decrypted_message_media_decrypted_message_media_venue');
        Schema::dropIfExists('tl_decrypted_message_media_decrypted_message_media_photo');
        Schema::dropIfExists('tl_decrypted_message_media_decrypted_message__a644abd2ed29');
        Schema::dropIfExists('tl_decrypted_message_media_decrypted_message__39292adb140f');
        Schema::dropIfExists('tl_decrypted_message_media_decrypted_message__37e1a7328ec6');
        Schema::dropIfExists('tl_decrypted_message_media_decrypted_message_media_empty');
        Schema::dropIfExists('tl_decrypted_message_media_decrypted_message__fa113370e99a');
        Schema::dropIfExists('tl_decrypted_message_media_decrypted_message__1652f9c81874');
        Schema::dropIfExists('tl_decrypted_message_media_decrypted_message_media_contact');
        Schema::dropIfExists('tl_decrypted_message_media_decrypted_message_media_audio');
    }
};
