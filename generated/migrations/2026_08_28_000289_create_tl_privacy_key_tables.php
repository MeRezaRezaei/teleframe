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
        Schema::create('tl_privacy_key_privacy_key_about', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_15b1c7272bf1d51336bdf41e');
            $table->index('account_id', 'ix_1a9ffc27ef970fc817dbc811');
        });
        Schema::create('tl_privacy_key_privacy_key_added_by_phone', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e5505269553c0259d4ea91f4');
            $table->index('account_id', 'ix_c5028bd119a8cda39fddd3f3');
        });
        Schema::create('tl_privacy_key_privacy_key_birthday', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a041199956281b1f32e546d7');
            $table->index('account_id', 'ix_65e0b1462a99107cfd73dac4');
        });
        Schema::create('tl_privacy_key_privacy_key_chat_invite', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9c6e1fcd24b5c98a1a3cacd3');
            $table->index('account_id', 'ix_7dd1b393f7729a62b1f8317b');
        });
        Schema::create('tl_privacy_key_privacy_key_forwards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2c9c77d2063639a3b5c4168c');
            $table->index('account_id', 'ix_84dba4413aeeeb44cae30f77');
        });
        Schema::create('tl_privacy_key_privacy_key_no_paid_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_895de245b682ef0867b5a7c6');
            $table->index('account_id', 'ix_07739dce043407299e7fb47c');
        });
        Schema::create('tl_privacy_key_privacy_key_phone_call', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_052d18236bb594a0c4046af1');
            $table->index('account_id', 'ix_445b462330f2777545f8c81a');
        });
        Schema::create('tl_privacy_key_privacy_key_phone_number', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bacccf01cd482e1032f8715d');
            $table->index('account_id', 'ix_ebe1be26b73ed0827981f28a');
        });
        Schema::create('tl_privacy_key_privacy_key_phone_p2_p', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d770a0fd95091c608940ad83');
            $table->index('account_id', 'ix_2a455ca70239248c38c210f5');
        });
        Schema::create('tl_privacy_key_privacy_key_profile_photo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5762eeeebac268d8720562fd');
            $table->index('account_id', 'ix_26f4322152e4250f66bd0e6c');
        });
        Schema::create('tl_privacy_key_privacy_key_saved_music', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0fafe03c8068efd091296bbe');
            $table->index('account_id', 'ix_feddb95152f77d075a0041d2');
        });
        Schema::create('tl_privacy_key_privacy_key_star_gifts_auto_save', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6f2c223c4aaeb4899c4f5a60');
            $table->index('account_id', 'ix_9ee2fa5fcf245a5b858249e1');
        });
        Schema::create('tl_privacy_key_privacy_key_status_timestamp', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f3576eb099bbe3a2190b18b1');
            $table->index('account_id', 'ix_b0bf332c0d6bd8de15ce8e21');
        });
        Schema::create('tl_privacy_key_privacy_key_voice_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9428efadbf81a369f607c586');
            $table->index('account_id', 'ix_a576d478d34cc31538595601');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_privacy_key_privacy_key_voice_messages');
        Schema::dropIfExists('tl_privacy_key_privacy_key_status_timestamp');
        Schema::dropIfExists('tl_privacy_key_privacy_key_star_gifts_auto_save');
        Schema::dropIfExists('tl_privacy_key_privacy_key_saved_music');
        Schema::dropIfExists('tl_privacy_key_privacy_key_profile_photo');
        Schema::dropIfExists('tl_privacy_key_privacy_key_phone_p2_p');
        Schema::dropIfExists('tl_privacy_key_privacy_key_phone_number');
        Schema::dropIfExists('tl_privacy_key_privacy_key_phone_call');
        Schema::dropIfExists('tl_privacy_key_privacy_key_no_paid_messages');
        Schema::dropIfExists('tl_privacy_key_privacy_key_forwards');
        Schema::dropIfExists('tl_privacy_key_privacy_key_chat_invite');
        Schema::dropIfExists('tl_privacy_key_privacy_key_birthday');
        Schema::dropIfExists('tl_privacy_key_privacy_key_added_by_phone');
        Schema::dropIfExists('tl_privacy_key_privacy_key_about');
    }
};
