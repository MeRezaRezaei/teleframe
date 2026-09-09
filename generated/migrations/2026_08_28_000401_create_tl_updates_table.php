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
        Schema::create('tl_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_9ca189901e65c71a5411087f');
            $table->index('account_id', 'ix_dae29f5e0bd147610bbd8f04');
        });
        Schema::create('tl_updates_update_short', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates')->cascadeOnDelete();
            $table->uuid('update');
            $table->index('update', 'ix_7b9ea3500e0e55f3811df5e3');
            $table->integer('date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b4cf2a14eabfe743f550a5b3');
        });
        Schema::create('tl_updates_update_short_chat_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('out')->default(false);
            $table->boolean('mentioned')->default(false);
            $table->boolean('media_unread')->default(false);
            $table->boolean('silent')->default(false);
            $table->integer('tl_id');
            $table->bigInteger('from_id');
            $table->index('from_id', 'ix_3478418c1f9883393fa3f00f');
            $table->bigInteger('chat_id');
            $table->index('chat_id', 'ix_76529af66bddf5291f0da82a');
            $table->text('message');
            $table->integer('pts');
            $table->integer('pts_count');
            $table->integer('date');
            $table->uuid('fwd_from')->nullable();
            $table->index('fwd_from', 'ix_3c5912d72ff6029fc73e71e4');
            $table->bigInteger('via_bot_id')->nullable();
            $table->index('via_bot_id', 'ix_2e31ab2808010703c07b14ac');
            $table->uuid('reply_to')->nullable();
            $table->index('reply_to', 'ix_1db9797f8b36343286c502f5');
            $table->integer('ttl_period')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_73c0aa05e963a46e0eab7429');
            $table->unique(['account_id', 'tl_id'], 'ux_f7864523872e773861e6');
        });
        Schema::create('tl_updates_update_short_chat_message__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_update_short_chat_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0a4e729b3f356264a2b8');
            $table->index('account_id', 'ix_293e4269b6bca0ed9367311e');
        });
        Schema::create('tl_updates_update_short_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('out')->default(false);
            $table->boolean('mentioned')->default(false);
            $table->boolean('media_unread')->default(false);
            $table->boolean('silent')->default(false);
            $table->integer('tl_id');
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_5988f9d3b5f1f0ac7b468e52');
            $table->text('message');
            $table->integer('pts');
            $table->integer('pts_count');
            $table->integer('date');
            $table->uuid('fwd_from')->nullable();
            $table->index('fwd_from', 'ix_6083c80fb4e6f0ca9ff16161');
            $table->bigInteger('via_bot_id')->nullable();
            $table->index('via_bot_id', 'ix_8579e6c7b174b8e8abb9dd36');
            $table->uuid('reply_to')->nullable();
            $table->index('reply_to', 'ix_d991244b44fe97f50b41d8ba');
            $table->integer('ttl_period')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f414b22adb4ead91830759e6');
            $table->unique(['account_id', 'tl_id'], 'ux_a4005c0b30ed3cfad315');
        });
        Schema::create('tl_updates_update_short_message__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_update_short_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_16d2c9eb89082b8aae79');
            $table->index('account_id', 'ix_fff41611d1502d434eb5d6bf');
        });
        Schema::create('tl_updates_update_short_sent_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('out')->default(false);
            $table->integer('tl_id');
            $table->integer('pts');
            $table->integer('pts_count');
            $table->integer('date');
            $table->uuid('media')->nullable();
            $table->index('media', 'ix_8d4b8dabd160302c5039ddc8');
            $table->integer('ttl_period')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_53e781c61f6e20393a06c5db');
            $table->unique(['account_id', 'tl_id'], 'ux_34833dc88d93511a1863');
        });
        Schema::create('tl_updates_update_short_sent_message__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_update_short_sent_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_bd45d68cbc4f8afd9c34');
            $table->index('account_id', 'ix_cfaa5895aad9d78bd5af0054');
        });
        Schema::create('tl_updates_updates', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates')->cascadeOnDelete();
            $table->integer('date');
            $table->integer('seq');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5c46e86d078f7a8dfa21d795');
        });
        Schema::create('tl_updates_updates__updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_updates')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b6c0efd4e8d6b429e369');
            $table->index('account_id', 'ix_29cd5c1dbf599df2387a05c0');
        });
        Schema::create('tl_updates_updates__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_updates')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b87bc8d0093d34ee9243');
            $table->index('account_id', 'ix_b39234e261ba096db8f79df2');
        });
        Schema::create('tl_updates_updates__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_updates')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5b5732d34d391ad11799');
            $table->index('account_id', 'ix_059089fb5105a488e6ac21fa');
        });
        Schema::create('tl_updates_updates_combined', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates')->cascadeOnDelete();
            $table->integer('date');
            $table->integer('seq_start');
            $table->integer('seq');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c2c97482f7f5cbcdc7bc644f');
        });
        Schema::create('tl_updates_updates_combined__updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_updates_combined')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5945967c5e65c9c80268');
            $table->index('account_id', 'ix_574c2afd5b7cbde7906db413');
        });
        Schema::create('tl_updates_updates_combined__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_updates_combined')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_7347de1d490987ecf2af');
            $table->index('account_id', 'ix_8667555fd7edb7b34ff44873');
        });
        Schema::create('tl_updates_updates_combined__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_updates_combined')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5c640e1e6f07ffa44ca6');
            $table->index('account_id', 'ix_46dd44299f98dc76c4ab98d6');
        });
        Schema::create('tl_updates_updates_too_long', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e4bd8a32b01e79a6746e52e3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_updates_updates_too_long');
        Schema::dropIfExists('tl_updates_updates_combined__chats');
        Schema::dropIfExists('tl_updates_updates_combined__users');
        Schema::dropIfExists('tl_updates_updates_combined__updates');
        Schema::dropIfExists('tl_updates_updates_combined');
        Schema::dropIfExists('tl_updates_updates__chats');
        Schema::dropIfExists('tl_updates_updates__users');
        Schema::dropIfExists('tl_updates_updates__updates');
        Schema::dropIfExists('tl_updates_updates');
        Schema::dropIfExists('tl_updates_update_short_sent_message__entities');
        Schema::dropIfExists('tl_updates_update_short_sent_message');
        Schema::dropIfExists('tl_updates_update_short_message__entities');
        Schema::dropIfExists('tl_updates_update_short_message');
        Schema::dropIfExists('tl_updates_update_short_chat_message__entities');
        Schema::dropIfExists('tl_updates_update_short_chat_message');
        Schema::dropIfExists('tl_updates_update_short');
        Schema::dropIfExists('tl_updates');
    }
};
