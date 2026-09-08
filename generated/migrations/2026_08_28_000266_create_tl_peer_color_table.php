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
        Schema::create('tl_peer_color', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_peer_color_input_peer_color_collectible', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_peer_color')->cascadeOnDelete();
            $table->bigInteger('collectible_id');
            $table->timestamps();
        });
        Schema::create('tl_peer_color_peer_color', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_peer_color')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('color')->nullable();
            $table->bigInteger('background_emoji_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_peer_color_peer_color_collectible', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_peer_color')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('collectible_id');
            $table->bigInteger('gift_emoji_id');
            $table->bigInteger('background_emoji_id');
            $table->integer('accent_color');
            $table->integer('dark_accent_color')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_peer_color_peer_color_collectible__colors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_peer_color_peer_color_collectible')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8fed6f30e87ca697f6c2');
        });
        Schema::create('tl_peer_color_peer_color_collectible__dark_colors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_peer_color_peer_color_collectible')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_971976d28a4ec3934dc6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_peer_color_peer_color_collectible__dark_colors');
        Schema::dropIfExists('tl_peer_color_peer_color_collectible__colors');
        Schema::dropIfExists('tl_peer_color_peer_color_collectible');
        Schema::dropIfExists('tl_peer_color_peer_color');
        Schema::dropIfExists('tl_peer_color_input_peer_color_collectible');
        Schema::dropIfExists('tl_peer_color');
    }
};
