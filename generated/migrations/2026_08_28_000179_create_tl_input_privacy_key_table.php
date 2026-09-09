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
        Schema::create('tl_input_privacy_key', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_30915048e22355868daa443a');
            $table->index('account_id', 'ix_df49f0fa6e0766d3f6d7e440');
        });
        Schema::create('tl_input_privacy_key_input_privacy_key_about', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_774091e0e340554c50e423ba');
        });
        Schema::create('tl_input_privacy_key_input_privacy_key_added_by_phone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e53f7bc040b969d6d8c11869');
        });
        Schema::create('tl_input_privacy_key_input_privacy_key_birthday', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_efa82ec7ee0948563622cab6');
        });
        Schema::create('tl_input_privacy_key_input_privacy_key_chat_invite', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_711ddb2c515f0cc8f6f93d0c');
        });
        Schema::create('tl_input_privacy_key_input_privacy_key_forwards', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cbc8a74f8ffb40a0f0acf9eb');
        });
        Schema::create('tl_input_privacy_key_input_privacy_key_no_paid_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d9a4be57f9eed1a5f6aa4361');
        });
        Schema::create('tl_input_privacy_key_input_privacy_key_phone_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d22505277b73e55bc175e1ab');
        });
        Schema::create('tl_input_privacy_key_input_privacy_key_phone_number', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_66d6c92718de147b4bd737bb');
        });
        Schema::create('tl_input_privacy_key_input_privacy_key_phone_p2_p', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ab49b16ae10acf9cd8b5b41b');
        });
        Schema::create('tl_input_privacy_key_input_privacy_key_profile_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7789c003116dced92d0642d8');
        });
        Schema::create('tl_input_privacy_key_input_privacy_key_saved_music', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_28cc2a795df0f2afc335d3b8');
        });
        Schema::create('tl_input_privacy_key_input_privacy_key_star_g_6e9efbe835c3', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_83692a4e6a5fb8771efa915f');
        });
        Schema::create('tl_input_privacy_key_input_privacy_key_status_timestamp', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bec7e6276e673ff4c1cfc909');
        });
        Schema::create('tl_input_privacy_key_input_privacy_key_voice_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4891275ff159f6beabc2cf4d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_privacy_key_input_privacy_key_voice_messages');
        Schema::dropIfExists('tl_input_privacy_key_input_privacy_key_status_timestamp');
        Schema::dropIfExists('tl_input_privacy_key_input_privacy_key_star_g_6e9efbe835c3');
        Schema::dropIfExists('tl_input_privacy_key_input_privacy_key_saved_music');
        Schema::dropIfExists('tl_input_privacy_key_input_privacy_key_profile_photo');
        Schema::dropIfExists('tl_input_privacy_key_input_privacy_key_phone_p2_p');
        Schema::dropIfExists('tl_input_privacy_key_input_privacy_key_phone_number');
        Schema::dropIfExists('tl_input_privacy_key_input_privacy_key_phone_call');
        Schema::dropIfExists('tl_input_privacy_key_input_privacy_key_no_paid_messages');
        Schema::dropIfExists('tl_input_privacy_key_input_privacy_key_forwards');
        Schema::dropIfExists('tl_input_privacy_key_input_privacy_key_chat_invite');
        Schema::dropIfExists('tl_input_privacy_key_input_privacy_key_birthday');
        Schema::dropIfExists('tl_input_privacy_key_input_privacy_key_added_by_phone');
        Schema::dropIfExists('tl_input_privacy_key_input_privacy_key_about');
        Schema::dropIfExists('tl_input_privacy_key');
    }
};
