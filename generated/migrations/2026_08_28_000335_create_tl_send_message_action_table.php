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
        Schema::create('tl_send_message_action', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_send_message_action_input_send_message_ric_e3acb0a879b4', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->bigInteger('random_id');
            $table->uuid('rich_message');
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_cancel_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_choose_contact_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_choose_sticker_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_emoji_interaction', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->text('emoticon');
            $table->integer('msg_id');
            $table->uuid('interaction');
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_emoji_interaction_seen', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->text('emoticon');
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_game_play_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_geo_location_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_history_import_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->integer('progress');
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_record_audio_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_record_round_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_record_video_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_rich_mess_c3c24446ab81', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->bigInteger('random_id');
            $table->uuid('rich_message');
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_text_draft_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->bigInteger('random_id');
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_typing_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_upload_audio_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->integer('progress');
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_upload_document_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->integer('progress');
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_upload_photo_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->integer('progress');
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_upload_round_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->integer('progress');
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_send_message_upload_video_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->integer('progress');
            $table->timestamps();
        });
        Schema::create('tl_send_message_action_speaking_in_group_call_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_send_message_action_speaking_in_group_call_action');
        Schema::dropIfExists('tl_send_message_action_send_message_upload_video_action');
        Schema::dropIfExists('tl_send_message_action_send_message_upload_round_action');
        Schema::dropIfExists('tl_send_message_action_send_message_upload_photo_action');
        Schema::dropIfExists('tl_send_message_action_send_message_upload_document_action');
        Schema::dropIfExists('tl_send_message_action_send_message_upload_audio_action');
        Schema::dropIfExists('tl_send_message_action_send_message_typing_action');
        Schema::dropIfExists('tl_send_message_action_send_message_text_draft_action');
        Schema::dropIfExists('tl_send_message_action_send_message_rich_mess_c3c24446ab81');
        Schema::dropIfExists('tl_send_message_action_send_message_record_video_action');
        Schema::dropIfExists('tl_send_message_action_send_message_record_round_action');
        Schema::dropIfExists('tl_send_message_action_send_message_record_audio_action');
        Schema::dropIfExists('tl_send_message_action_send_message_history_import_action');
        Schema::dropIfExists('tl_send_message_action_send_message_geo_location_action');
        Schema::dropIfExists('tl_send_message_action_send_message_game_play_action');
        Schema::dropIfExists('tl_send_message_action_send_message_emoji_interaction_seen');
        Schema::dropIfExists('tl_send_message_action_send_message_emoji_interaction');
        Schema::dropIfExists('tl_send_message_action_send_message_choose_sticker_action');
        Schema::dropIfExists('tl_send_message_action_send_message_choose_contact_action');
        Schema::dropIfExists('tl_send_message_action_send_message_cancel_action');
        Schema::dropIfExists('tl_send_message_action_input_send_message_ric_e3acb0a879b4');
        Schema::dropIfExists('tl_send_message_action');
    }
};
