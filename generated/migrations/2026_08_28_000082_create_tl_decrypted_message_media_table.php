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
        Schema::create('tl_decrypted_message_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_5f28ec2a1e99bcae3eefb838');
            $table->index('account_id', 'ix_a864f8fe87d530b0afa69ee2');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message_media_audio', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_media')->cascadeOnDelete();
            $table->integer('duration');
            $table->text('mime_type');
            $table->integer('tl_size');
            $table->binary('tl_key');
            $table->binary('iv');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7f631b09179d20ce1ccdcf5a');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message_media_contact', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_media')->cascadeOnDelete();
            $table->text('phone_number');
            $table->text('first_name');
            $table->text('last_name');
            $table->integer('user_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_07719fbf8bed592c182ef5bf');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message__1652f9c81874', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_media')->cascadeOnDelete();
            $table->binary('thumb');
            $table->integer('thumb_w');
            $table->integer('thumb_h');
            $table->text('mime_type');
            $table->bigInteger('tl_size');
            $table->binary('tl_key');
            $table->binary('iv');
            $table->text('caption');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f0c155d16e4b1d9e787956c3');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message__fa113370e99a', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_decrypted_message_media_decrypted_message__1652f9c81874')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c8e475bd29abfccf575e');
            $table->index('account_id', 'ix_6f7b8619c0f4f21aeec7b1ff');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message_media_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_media')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_35c87a313923adfbe60a2f9d');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message__37e1a7328ec6', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_media')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->integer('date');
            $table->text('mime_type');
            $table->integer('tl_size');
            $table->uuid('thumb');
            $table->index('thumb', 'ix_f93fa97a88279ede0d3cc77c');
            $table->integer('dc_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3bde2034c1fd539a223ff8e8');
            $table->unique(['account_id', 'tl_id'], 'ux_b06151dce2c7016eb3c8');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message__39292adb140f', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_decrypted_message_media_decrypted_message__37e1a7328ec6')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_12da5ccb04219915c5a4');
            $table->index('account_id', 'ix_ac86c02c474f8c08f60f66f7');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message__a644abd2ed29', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_media')->cascadeOnDelete();
            $table->double('lat');
            $table->double('tl_long');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3228241cd4000d03d8a2d00d');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message_media_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_media')->cascadeOnDelete();
            $table->binary('thumb');
            $table->integer('thumb_w');
            $table->integer('thumb_h');
            $table->integer('w');
            $table->integer('h');
            $table->integer('tl_size');
            $table->binary('tl_key');
            $table->binary('iv');
            $table->text('caption');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c860e3e291f87de3439e72b2');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message_media_venue', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_media')->cascadeOnDelete();
            $table->double('lat');
            $table->double('tl_long');
            $table->text('title');
            $table->text('address');
            $table->text('provider');
            $table->text('venue_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6a8a999e5953ae846c118702');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message_media_video', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_media')->cascadeOnDelete();
            $table->binary('thumb');
            $table->integer('thumb_w');
            $table->integer('thumb_h');
            $table->integer('duration');
            $table->text('mime_type');
            $table->integer('w');
            $table->integer('h');
            $table->integer('tl_size');
            $table->binary('tl_key');
            $table->binary('iv');
            $table->text('caption');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fb3bdc7903af2c6cdd9b6cb5');
        });
        Schema::create('tl_decrypted_message_media_decrypted_message__8fed41c9f5cf', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_media')->cascadeOnDelete();
            $table->text('url');
            $table->bigInteger('account_id');
            $table->timestamps();
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
        Schema::dropIfExists('tl_decrypted_message_media');
    }
};
