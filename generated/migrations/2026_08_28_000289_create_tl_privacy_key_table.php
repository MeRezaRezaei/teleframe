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
        Schema::create('tl_privacy_key', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_privacy_key_privacy_key_about', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_key_privacy_key_added_by_phone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_key_privacy_key_birthday', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_key_privacy_key_chat_invite', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_key_privacy_key_forwards', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_key_privacy_key_no_paid_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_key_privacy_key_phone_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_key_privacy_key_phone_number', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_key_privacy_key_phone_p2_p', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_key_privacy_key_profile_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_key_privacy_key_saved_music', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_key_privacy_key_star_gifts_auto_save', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_key_privacy_key_status_timestamp', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_key_privacy_key_voice_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->timestamps();
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
        Schema::dropIfExists('tl_privacy_key');
    }
};
