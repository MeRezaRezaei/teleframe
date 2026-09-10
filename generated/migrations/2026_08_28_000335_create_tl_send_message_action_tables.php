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
        Schema::create('tl_send_message_action_input_send_message_ric_e3acb0a879b4', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('random_id')->nullable();
            $table->index('random_id', 'ix_ed29d1ab33af8914c133cd17');
            $table->bigInteger('rich_message')->nullable();
            $table->index('rich_message', 'ix_a7b7f781845c228af079f208');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a6ce752544266b54ac46a442');
            $table->index('account_id', 'ix_43199c0b7af9c8bf1cf82467');
        });
        Schema::create('tl_send_message_action_send_message_cancel_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7aa51abe06b477c2c1ab84a5');
            $table->index('account_id', 'ix_60acd138e2d7c506ab4debc1');
        });
        Schema::create('tl_send_message_action_send_message_choose_contact_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_81af71aa99aeec422b00d8b0');
            $table->index('account_id', 'ix_31e94f9f47765ef6198324c2');
        });
        Schema::create('tl_send_message_action_send_message_choose_sticker_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_544d97f9218b1ddd4820755c');
            $table->index('account_id', 'ix_39b77c289fab2406671b3244');
        });
        Schema::create('tl_send_message_action_send_message_emoji_interaction', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('emoticon')->nullable();
            $table->integer('msg_id')->nullable();
            $table->bigInteger('interaction')->nullable();
            $table->index('interaction', 'ix_a8668b1d02a0e52d9cdcfdfe');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a19a96b1e9b76dda7b10cb37');
            $table->index('account_id', 'ix_fcbd11c53e9e69f937a2c17a');
        });
        Schema::create('tl_send_message_action_send_message_emoji_interaction_seen', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('emoticon')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a39ad844553598256e91a147');
            $table->index('account_id', 'ix_a7da694c10c97864bccfde81');
        });
        Schema::create('tl_send_message_action_send_message_game_play_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_93dcf11d48a5ab68553bc845');
            $table->index('account_id', 'ix_4fe67a311457e535ab9a61c1');
        });
        Schema::create('tl_send_message_action_send_message_geo_location_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_95e03abadcfce0949bca6308');
            $table->index('account_id', 'ix_1f9c800ef3eec5d41efece5b');
        });
        Schema::create('tl_send_message_action_send_message_history_import_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('progress')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d7612e593500a8f992d4c46e');
            $table->index('account_id', 'ix_c82b4eebe5eacaa512fbc28e');
        });
        Schema::create('tl_send_message_action_send_message_record_audio_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_cc0ad7673b8d8d5072ea35af');
            $table->index('account_id', 'ix_036f75c031482857b3bfbb95');
        });
        Schema::create('tl_send_message_action_send_message_record_round_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_53088de5f61c6de89c338842');
            $table->index('account_id', 'ix_3ad8d44ba81a367c12644cd4');
        });
        Schema::create('tl_send_message_action_send_message_record_video_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ae4d3cd1739571703e45b2ad');
            $table->index('account_id', 'ix_25551faa15b446ce28bb4d54');
        });
        Schema::create('tl_send_message_action_send_message_rich_mess_c3c24446ab81', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('random_id')->nullable();
            $table->index('random_id', 'ix_381b1230597ed1c9a853544a');
            $table->bigInteger('rich_message')->nullable();
            $table->index('rich_message', 'ix_fc5c791dbd1100a8c5f60cc6');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5a874cea001720f6e5cbc740');
            $table->index('account_id', 'ix_2252b5ef7c792d125f0be9f4');
        });
        Schema::create('tl_send_message_action_send_message_text_draft_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('random_id')->nullable();
            $table->index('random_id', 'ix_2b361f6eff0006efa1d04937');
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_c26ebe2ffd874d31772b609d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a07316165602c02cc62a3d49');
            $table->index('account_id', 'ix_327969ea5ae0531df90294dc');
        });
        Schema::create('tl_send_message_action_send_message_typing_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bcf5f27ea6f209c9bb366bce');
            $table->index('account_id', 'ix_dd3c6856131d11cb844bca81');
        });
        Schema::create('tl_send_message_action_send_message_upload_audio_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('progress')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bd8d9c426f5feaa48c00f7a1');
            $table->index('account_id', 'ix_2a51b69e3e24562aa5b44be2');
        });
        Schema::create('tl_send_message_action_send_message_upload_document_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('progress')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4a8744f80b4ccb2f93b54076');
            $table->index('account_id', 'ix_8b6a1073e0ad216706c61a0f');
        });
        Schema::create('tl_send_message_action_send_message_upload_photo_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('progress')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6c21a3882a1b14dcb54eb3ff');
            $table->index('account_id', 'ix_31ea0709847a2e8499bc404a');
        });
        Schema::create('tl_send_message_action_send_message_upload_round_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('progress')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_cd944d2dbc0f26390bbefe0b');
            $table->index('account_id', 'ix_70f33a66544f4dd2a0e60357');
        });
        Schema::create('tl_send_message_action_send_message_upload_video_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('progress')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_67b0cd4f4f5b6f3728d135f9');
            $table->index('account_id', 'ix_92f9433a21dd841e5ebe4803');
        });
        Schema::create('tl_send_message_action_speaking_in_group_call_action', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1506fe7cffec42fda76d3dfc');
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
    }
};
