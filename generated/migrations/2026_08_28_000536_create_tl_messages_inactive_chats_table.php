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
        Schema::create('tl_messages_inactive_chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_490730e6c3de6fc1a3db9e23');
            $table->index('account_id', 'ix_2bb75ce70d5623330a37f3ee');
        });
        Schema::create('tl_messages_inactive_chats_inactive_chats', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_inactive_chats')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f53d01e5d473de794c6a639d');
        });
        Schema::create('tl_messages_inactive_chats_inactive_chats__dates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_inactive_chats_inactive_chats')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6f2379d50488add092c4');
            $table->index('account_id', 'ix_d7ddba684e04ff61c9acb730');
        });
        Schema::create('tl_messages_inactive_chats_inactive_chats__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_inactive_chats_inactive_chats')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ae20b4033e4b1a288141');
            $table->index('account_id', 'ix_2c69c64bcbd8dc029432081e');
        });
        Schema::create('tl_messages_inactive_chats_inactive_chats__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_inactive_chats_inactive_chats')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
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
        Schema::dropIfExists('tl_messages_inactive_chats');
    }
};
