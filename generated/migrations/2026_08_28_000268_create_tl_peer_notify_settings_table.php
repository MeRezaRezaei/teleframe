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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_peer_notify_settings_peer_notify_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_peer_notify_settings')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('show_previews')->nullable();
            $table->uuid('silent')->nullable();
            $table->integer('mute_until')->nullable();
            $table->uuid('ios_sound')->nullable();
            $table->uuid('android_sound')->nullable();
            $table->uuid('other_sound')->nullable();
            $table->uuid('stories_muted')->nullable();
            $table->uuid('stories_hide_sender')->nullable();
            $table->uuid('stories_ios_sound')->nullable();
            $table->uuid('stories_android_sound')->nullable();
            $table->uuid('stories_other_sound')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_peer_notify_settings_peer_notify_settings');
        Schema::dropIfExists('tl_peer_notify_settings');
    }
};
