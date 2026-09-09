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
            $table->index('constructor_id', 'ix_bed429af6044dab9f2bd18df');
            $table->index('account_id', 'ix_f600fd6d0809faf800ce9ffa');
        });
        Schema::create('tl_privacy_key_privacy_key_about', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1a9ffc27ef970fc817dbc811');
        });
        Schema::create('tl_privacy_key_privacy_key_added_by_phone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c5028bd119a8cda39fddd3f3');
        });
        Schema::create('tl_privacy_key_privacy_key_birthday', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_65e0b1462a99107cfd73dac4');
        });
        Schema::create('tl_privacy_key_privacy_key_chat_invite', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7dd1b393f7729a62b1f8317b');
        });
        Schema::create('tl_privacy_key_privacy_key_forwards', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_84dba4413aeeeb44cae30f77');
        });
        Schema::create('tl_privacy_key_privacy_key_no_paid_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_07739dce043407299e7fb47c');
        });
        Schema::create('tl_privacy_key_privacy_key_phone_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_445b462330f2777545f8c81a');
        });
        Schema::create('tl_privacy_key_privacy_key_phone_number', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ebe1be26b73ed0827981f28a');
        });
        Schema::create('tl_privacy_key_privacy_key_phone_p2_p', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2a455ca70239248c38c210f5');
        });
        Schema::create('tl_privacy_key_privacy_key_profile_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_26f4322152e4250f66bd0e6c');
        });
        Schema::create('tl_privacy_key_privacy_key_saved_music', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_feddb95152f77d075a0041d2');
        });
        Schema::create('tl_privacy_key_privacy_key_star_gifts_auto_save', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9ee2fa5fcf245a5b858249e1');
        });
        Schema::create('tl_privacy_key_privacy_key_status_timestamp', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b0bf332c0d6bd8de15ce8e21');
        });
        Schema::create('tl_privacy_key_privacy_key_voice_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_key')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
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
        Schema::dropIfExists('tl_privacy_key');
    }
};
