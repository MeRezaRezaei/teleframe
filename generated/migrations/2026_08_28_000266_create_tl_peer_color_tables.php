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
        Schema::create('tl_peer_color_input_peer_color_collectible', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('collectible_id')->nullable();
            $table->index('collectible_id', 'ix_6df063b76bb33f057b052746');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a62e2140a4b92c9c1a79ae44');
            $table->index('account_id', 'ix_9818abf40d23a7516f7d943a');
        });
        Schema::create('tl_peer_color_peer_color', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('color')->nullable();
            $table->bigInteger('background_emoji_id')->nullable();
            $table->index('background_emoji_id', 'ix_48e9bd6f09ca32ca81e00f48');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7199470070de488189b4b10a');
            $table->index('account_id', 'ix_792c3bfe672c17a35c13e558');
        });
        Schema::create('tl_peer_color_peer_color_collectible', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('collectible_id')->nullable();
            $table->index('collectible_id', 'ix_9a3227d0a58c0fc65a5af0db');
            $table->bigInteger('gift_emoji_id')->nullable();
            $table->index('gift_emoji_id', 'ix_55521bdb4cb2fda712ffcf06');
            $table->bigInteger('background_emoji_id')->nullable();
            $table->index('background_emoji_id', 'ix_73d881e91acae88d7a8767ea');
            $table->integer('accent_color')->nullable();
            $table->integer('dark_accent_color')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8a7bede62ab6623db7df76dc');
            $table->index('account_id', 'ix_1efd00a8bcd2d9612e21c987');
        });
        Schema::create('tl_peer_color_peer_color_collectible__colors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_peer_color_peer_color_collectible', 'id', 'fk_b60cd72cb2d7bc18beac3355')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8fed6f30e87ca697f6c2');
            $table->index('account_id', 'ix_b0c136cfe6e0b6b76a1253b0');
        });
        Schema::create('tl_peer_color_peer_color_collectible__dark_colors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_peer_color_peer_color_collectible', 'id', 'fk_198d89e45c70b888daa9bda4')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_971976d28a4ec3934dc6');
            $table->index('account_id', 'ix_85d03b9f277b572bf2efae47');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_peer_color_peer_color_collectible__dark_colors');
        Schema::dropIfExists('tl_peer_color_peer_color_collectible__colors');
        Schema::dropIfExists('tl_peer_color_peer_color_collectible');
        Schema::dropIfExists('tl_peer_color_peer_color');
        Schema::dropIfExists('tl_peer_color_input_peer_color_collectible');
    }
};
