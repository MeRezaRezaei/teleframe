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
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('title_noanimate')->default(false);
            $table->bigInteger('title')->nullable();
            $table->index('title', 'ix_237ab880158eda4654483127');
            $table->text('emoticon')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_41f2ec5b05ce31202903c775');
            $table->index('account_id', 'ix_29fa27caa05c9c2a4fa0c254');
        });
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite__peers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_d24407edbf3291bbc22aaa99')->references('id')->on('tl_chatlists_chatlist_invite_chatlist_invite')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8a24b5683be2c9f3e0db');
            $table->index('account_id', 'ix_85519493d6642ac3c4396578');
        });
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_878f0f8ca40cdf7576e89e47')->references('id')->on('tl_chatlists_chatlist_invite_chatlist_invite')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9552067a2d3a31c0a605');
            $table->index('account_id', 'ix_22739d99814fa33f32d81840');
        });
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_485a83c385227c7e97741848')->references('id')->on('tl_chatlists_chatlist_invite_chatlist_invite')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f8740832af9426b344f4');
            $table->index('account_id', 'ix_fd5785bde67039fd41112d89');
        });
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite_already', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('filter_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_712c7652cbc62ab24787945a');
            $table->index('account_id', 'ix_16d84d61f3476eb4705c815f');
        });
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite__e88ac70d6871', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_8642ddc60715a5057b46b97b')->references('id')->on('tl_chatlists_chatlist_invite_chatlist_invite_already')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5b3ce2c728689e6a00c8');
            $table->index('account_id', 'ix_4ddd7b7af9347fee845eab91');
        });
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite__b7f3a8202539', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_a037c81b30390d22f2f3fb34')->references('id')->on('tl_chatlists_chatlist_invite_chatlist_invite_already')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6718b1fb3bf75c584b80');
            $table->index('account_id', 'ix_d3838d51446105e763436ea8');
        });
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite__1d33efbd497a', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_17171af632db98a98a876602')->references('id')->on('tl_chatlists_chatlist_invite_chatlist_invite_already')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_eb91a1204fcad1bd27ea');
            $table->index('account_id', 'ix_33410cca2205a6ea085d8265');
        });
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite__f31957d13fad', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_a1e691b5abb20e5f12538353')->references('id')->on('tl_chatlists_chatlist_invite_chatlist_invite_already')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e570a8ab4efec6e9cb3f');
            $table->index('account_id', 'ix_b05e118684b94375146b6300');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite__f31957d13fad');
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite__1d33efbd497a');
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite__b7f3a8202539');
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite__e88ac70d6871');
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite_already');
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite__users');
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite__chats');
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite__peers');
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite');
    }
};
