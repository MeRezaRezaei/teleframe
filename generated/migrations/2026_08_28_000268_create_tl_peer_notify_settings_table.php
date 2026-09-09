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
        Schema::create('tl_peer_notify_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_1dc25fbda1e3fe64932c50c1');
            $table->index('account_id', 'ix_441c8064d54a73626f7071e7');
        });
        Schema::create('tl_peer_notify_settings_peer_notify_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_peer_notify_settings')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('show_previews')->nullable();
            $table->index('show_previews', 'ix_31f1f1134495cc18fe67bcc7');
            $table->uuid('silent')->nullable();
            $table->index('silent', 'ix_d52573b6332b0026ee1d64ba');
            $table->integer('mute_until')->nullable();
            $table->uuid('ios_sound')->nullable();
            $table->index('ios_sound', 'ix_43dbbd26be8d02a76aefdbb3');
            $table->uuid('android_sound')->nullable();
            $table->index('android_sound', 'ix_47c6d758188e67d6fbf3f33b');
            $table->uuid('other_sound')->nullable();
            $table->index('other_sound', 'ix_35fe7c3a6fd63f36a9623c1b');
            $table->uuid('stories_muted')->nullable();
            $table->index('stories_muted', 'ix_39606f40c4636f4536069af1');
            $table->uuid('stories_hide_sender')->nullable();
            $table->index('stories_hide_sender', 'ix_93fc5e852a0aa548aeb59aa2');
            $table->uuid('stories_ios_sound')->nullable();
            $table->index('stories_ios_sound', 'ix_afae2523f43f0020af6564f4');
            $table->uuid('stories_android_sound')->nullable();
            $table->index('stories_android_sound', 'ix_e4d62fa50cb15c9ffbfb611f');
            $table->uuid('stories_other_sound')->nullable();
            $table->index('stories_other_sound', 'ix_6b4d37f8ec6964ef1d0d99a6');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1051a237c3916404111df233');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_peer_notify_settings_peer_notify_settings');
        Schema::dropIfExists('tl_peer_notify_settings');
    }
};
