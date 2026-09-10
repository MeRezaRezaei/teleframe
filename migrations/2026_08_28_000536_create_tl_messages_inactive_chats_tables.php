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
        Schema::create('tl_messages_inactive_chats_inactive_chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_043110c81c7784be4c7b0f8a');
            $table->index('account_id', 'ix_f53d01e5d473de794c6a639d');
        });
        Schema::create('tl_messages_inactive_chats_inactive_chats__dates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_6a6c3a5a5bfb324ca54f81c5')->references('id')->on('tl_messages_inactive_chats_inactive_chats')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6f2379d50488add092c4');
            $table->index('account_id', 'ix_d7ddba684e04ff61c9acb730');
        });
        Schema::create('tl_messages_inactive_chats_inactive_chats__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_a6f56c081ca664c10a8fbb56')->references('id')->on('tl_messages_inactive_chats_inactive_chats')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ae20b4033e4b1a288141');
            $table->index('account_id', 'ix_2c69c64bcbd8dc029432081e');
        });
        Schema::create('tl_messages_inactive_chats_inactive_chats__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_2da065693162f0ea860a9244')->references('id')->on('tl_messages_inactive_chats_inactive_chats')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_dd48b58e3ee86d26cdc7');
            $table->index('account_id', 'ix_c496698de09f52e49611a3a0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_inactive_chats_inactive_chats__users');
        Schema::dropIfExists('tl_messages_inactive_chats_inactive_chats__chats');
        Schema::dropIfExists('tl_messages_inactive_chats_inactive_chats__dates');
        Schema::dropIfExists('tl_messages_inactive_chats_inactive_chats');
    }
};
