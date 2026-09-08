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
        Schema::create('tl_input_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_media_input_media_contact', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->text('phone_number');
            $table->text('first_name');
            $table->text('last_name');
            $table->text('vcard');
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_dice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->text('emoticon');
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_document', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->uuid('tl_id');
            $table->uuid('video_cover')->nullable();
            $table->integer('video_timestamp')->nullable();
            $table->integer('ttl_seconds')->nullable();
            $table->text('query')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_document_external', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->text('url');
            $table->integer('ttl_seconds')->nullable();
            $table->uuid('video_cover')->nullable();
            $table->integer('video_timestamp')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_game', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->uuid('tl_id');
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_geo_live', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('stopped')->default(false);
            $table->uuid('geo_point');
            $table->integer('heading')->nullable();
            $table->integer('period')->nullable();
            $table->integer('proximity_notification_radius')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_geo_point', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->uuid('geo_point');
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_invoice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('title');
            $table->text('description');
            $table->uuid('photo')->nullable();
            $table->uuid('invoice');
            $table->binary('payload');
            $table->text('provider')->nullable();
            $table->uuid('provider_data');
            $table->text('start_param')->nullable();
            $table->uuid('extended_media')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_paid_media', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('stars_amount');
            $table->text('payload')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_paid_media__extended_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_media_input_media_paid_media')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ed7f8d09328ef8ba3879');
        });
        Schema::create('tl_input_media_input_media_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->boolean('live_photo')->default(false);
            $table->uuid('tl_id');
            $table->integer('ttl_seconds')->nullable();
            $table->uuid('video')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_photo_external', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->text('url');
            $table->integer('ttl_seconds')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_poll', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('poll');
            $table->uuid('attached_media')->nullable();
            $table->text('solution')->nullable();
            $table->uuid('solution_media')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_poll__correct_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_media_input_media_poll')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_78cba706c48a3dfe8d3c');
        });
        Schema::create('tl_input_media_input_media_poll__solution_entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_media_input_media_poll')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_188ba232fcc4a28f576a');
        });
        Schema::create('tl_input_media_input_media_stake_dice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->text('game_hash');
            $table->bigInteger('ton_amount');
            $table->binary('client_seed');
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_story', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->uuid('peer');
            $table->integer('tl_id');
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_todo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->uuid('todo');
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_uploaded_document', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('nosound_video')->default(false);
            $table->boolean('force_file')->default(false);
            $table->boolean('spoiler')->default(false);
            $table->uuid('file');
            $table->uuid('thumb')->nullable();
            $table->text('mime_type');
            $table->uuid('video_cover')->nullable();
            $table->integer('video_timestamp')->nullable();
            $table->integer('ttl_seconds')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_uploaded_document__attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_media_input_media_uploaded_document')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_477e84726d520584c1bb');
        });
        Schema::create('tl_input_media_input_media_uploaded_document__stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_media_input_media_uploaded_document')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_2d772bbcfebccc32b5b1');
        });
        Schema::create('tl_input_media_input_media_uploaded_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->boolean('live_photo')->default(false);
            $table->uuid('file');
            $table->integer('ttl_seconds')->nullable();
            $table->uuid('video')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_uploaded_photo__stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_media_input_media_uploaded_photo')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ee563364a426bb5de181');
        });
        Schema::create('tl_input_media_input_media_venue', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->uuid('geo_point');
            $table->text('title');
            $table->text('address');
            $table->text('provider');
            $table->text('venue_id');
            $table->text('venue_type');
            $table->timestamps();
        });
        Schema::create('tl_input_media_input_media_web_page', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('force_large_media')->default(false);
            $table->boolean('force_small_media')->default(false);
            $table->boolean('optional')->default(false);
            $table->text('url');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_media_input_media_web_page');
        Schema::dropIfExists('tl_input_media_input_media_venue');
        Schema::dropIfExists('tl_input_media_input_media_uploaded_photo__stickers');
        Schema::dropIfExists('tl_input_media_input_media_uploaded_photo');
        Schema::dropIfExists('tl_input_media_input_media_uploaded_document__stickers');
        Schema::dropIfExists('tl_input_media_input_media_uploaded_document__attributes');
        Schema::dropIfExists('tl_input_media_input_media_uploaded_document');
        Schema::dropIfExists('tl_input_media_input_media_todo');
        Schema::dropIfExists('tl_input_media_input_media_story');
        Schema::dropIfExists('tl_input_media_input_media_stake_dice');
        Schema::dropIfExists('tl_input_media_input_media_poll__solution_entities');
        Schema::dropIfExists('tl_input_media_input_media_poll__correct_answers');
        Schema::dropIfExists('tl_input_media_input_media_poll');
        Schema::dropIfExists('tl_input_media_input_media_photo_external');
        Schema::dropIfExists('tl_input_media_input_media_photo');
        Schema::dropIfExists('tl_input_media_input_media_paid_media__extended_media');
        Schema::dropIfExists('tl_input_media_input_media_paid_media');
        Schema::dropIfExists('tl_input_media_input_media_invoice');
        Schema::dropIfExists('tl_input_media_input_media_geo_point');
        Schema::dropIfExists('tl_input_media_input_media_geo_live');
        Schema::dropIfExists('tl_input_media_input_media_game');
        Schema::dropIfExists('tl_input_media_input_media_empty');
        Schema::dropIfExists('tl_input_media_input_media_document_external');
        Schema::dropIfExists('tl_input_media_input_media_document');
        Schema::dropIfExists('tl_input_media_input_media_dice');
        Schema::dropIfExists('tl_input_media_input_media_contact');
        Schema::dropIfExists('tl_input_media');
    }
};
