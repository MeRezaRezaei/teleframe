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
            $table->index('constructor_id', 'ix_2cf5b6702f14d7eb55ccb18a');
            $table->index('account_id', 'ix_d28c51ec208a781caf4dc439');
        });
        Schema::create('tl_send_message_action_input_send_message_ric_e3acb0a879b4', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->bigInteger('random_id');
            $table->index('random_id', 'ix_ed29d1ab33af8914c133cd17');
            $table->uuid('rich_message');
            $table->index('rich_message', 'ix_a7b7f781845c228af079f208');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_43199c0b7af9c8bf1cf82467');
        });
        Schema::create('tl_send_message_action_send_message_cancel_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_60acd138e2d7c506ab4debc1');
        });
        Schema::create('tl_send_message_action_send_message_choose_contact_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_31e94f9f47765ef6198324c2');
        });
        Schema::create('tl_send_message_action_send_message_choose_sticker_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_39b77c289fab2406671b3244');
        });
        Schema::create('tl_send_message_action_send_message_emoji_interaction', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->text('emoticon');
            $table->integer('msg_id');
            $table->uuid('interaction');
            $table->index('interaction', 'ix_a8668b1d02a0e52d9cdcfdfe');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fcbd11c53e9e69f937a2c17a');
        });
        Schema::create('tl_send_message_action_send_message_emoji_interaction_seen', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->text('emoticon');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a7da694c10c97864bccfde81');
        });
        Schema::create('tl_send_message_action_send_message_game_play_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4fe67a311457e535ab9a61c1');
        });
        Schema::create('tl_send_message_action_send_message_geo_location_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1f9c800ef3eec5d41efece5b');
        });
        Schema::create('tl_send_message_action_send_message_history_import_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->integer('progress');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c82b4eebe5eacaa512fbc28e');
        });
        Schema::create('tl_send_message_action_send_message_record_audio_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_036f75c031482857b3bfbb95');
        });
        Schema::create('tl_send_message_action_send_message_record_round_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3ad8d44ba81a367c12644cd4');
        });
        Schema::create('tl_send_message_action_send_message_record_video_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_25551faa15b446ce28bb4d54');
        });
        Schema::create('tl_send_message_action_send_message_rich_mess_c3c24446ab81', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->bigInteger('random_id');
            $table->index('random_id', 'ix_381b1230597ed1c9a853544a');
            $table->uuid('rich_message');
            $table->index('rich_message', 'ix_fc5c791dbd1100a8c5f60cc6');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2252b5ef7c792d125f0be9f4');
        });
        Schema::create('tl_send_message_action_send_message_text_draft_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->bigInteger('random_id');
            $table->index('random_id', 'ix_2b361f6eff0006efa1d04937');
            $table->uuid('text');
            $table->index('text', 'ix_c26ebe2ffd874d31772b609d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_327969ea5ae0531df90294dc');
        });
        Schema::create('tl_send_message_action_send_message_typing_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_dd3c6856131d11cb844bca81');
        });
        Schema::create('tl_send_message_action_send_message_upload_audio_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->integer('progress');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2a51b69e3e24562aa5b44be2');
        });
        Schema::create('tl_send_message_action_send_message_upload_document_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->integer('progress');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8b6a1073e0ad216706c61a0f');
        });
        Schema::create('tl_send_message_action_send_message_upload_photo_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->integer('progress');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_31ea0709847a2e8499bc404a');
        });
        Schema::create('tl_send_message_action_send_message_upload_round_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->integer('progress');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_70f33a66544f4dd2a0e60357');
        });
        Schema::create('tl_send_message_action_send_message_upload_video_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->integer('progress');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_92f9433a21dd841e5ebe4803');
        });
        Schema::create('tl_send_message_action_speaking_in_group_call_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_send_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7601c8b0ab6b92bde7ba5d9b');
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
