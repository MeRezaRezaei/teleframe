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
        Schema::create('tl_messages_faved_stickers_faved_stickers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_676de1070cbc6f38485a02d4');
            $table->index('account_id', 'ix_b876986e58dd53141e189f2c');
        });
        Schema::create('tl_messages_faved_stickers_faved_stickers__packs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_37a83d6b49398cbab6726ccb')->references('id')->on('tl_messages_faved_stickers_faved_stickers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0171a8bb49753fb105e8');
            $table->index('account_id', 'ix_dee070d979ac7883697c7cee');
        });
        Schema::create('tl_messages_faved_stickers_faved_stickers__stickers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_bec5f2b49f9851718dd2123e')->references('id')->on('tl_messages_faved_stickers_faved_stickers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e0313552ead6208831ba');
            $table->index('account_id', 'ix_19e97c76893f658b32fe85ee');
        });
        Schema::create('tl_messages_faved_stickers_faved_stickers_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9e4d17d45168e07ccabfa241');
            $table->index('account_id', 'ix_4465d07f61e65d8a81c1029e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_faved_stickers_faved_stickers_not_modified');
        Schema::dropIfExists('tl_messages_faved_stickers_faved_stickers__stickers');
        Schema::dropIfExists('tl_messages_faved_stickers_faved_stickers__packs');
        Schema::dropIfExists('tl_messages_faved_stickers_faved_stickers');
    }
};
