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
        Schema::create('tl_messages_emoji_game_info_emoji_game_dice_info', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('game_hash')->nullable();
            $table->bigInteger('prev_stake')->nullable();
            $table->integer('current_streak')->nullable();
            $table->integer('plays_left')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_918fbbac95e964344b537f03');
            $table->index('account_id', 'ix_6bd0fe4795219e11df47efc2');
        });
        Schema::create('tl_messages_emoji_game_info_emoji_game_dice_info__params', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_emoji_game_info_emoji_game_dice_info', 'id', 'fk_c3d23fe2f04f9d261894897e')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d8ed97c6cf52f620a941');
            $table->index('account_id', 'ix_5096b2d95acb2bccf2b00cfa');
        });
        Schema::create('tl_messages_emoji_game_info_emoji_game_unavailable', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bb8712b44b242cbc875cc870');
            $table->index('account_id', 'ix_35cd82bb37e51dae32cb2d13');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_emoji_game_info_emoji_game_unavailable');
        Schema::dropIfExists('tl_messages_emoji_game_info_emoji_game_dice_info__params');
        Schema::dropIfExists('tl_messages_emoji_game_info_emoji_game_dice_info');
    }
};
