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
        Schema::create('tl_updates_update_short', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('update')->nullable();
            $table->index('update', 'ix_7b9ea3500e0e55f3811df5e3');
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_82846d57ea8c2805d248e966');
            $table->index('account_id', 'ix_b4cf2a14eabfe743f550a5b3');
        });
        Schema::create('tl_updates_update_short_chat_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('out')->default(false);
            $table->boolean('mentioned')->default(false);
            $table->boolean('media_unread')->default(false);
            $table->boolean('silent')->default(false);
            $table->integer('tl_id')->nullable();
            $table->bigInteger('from_id')->nullable();
            $table->index('from_id', 'ix_3478418c1f9883393fa3f00f');
            $table->bigInteger('chat_id')->nullable();
            $table->index('chat_id', 'ix_76529af66bddf5291f0da82a');
            $table->text('message')->nullable();
            $table->integer('pts')->nullable();
            $table->integer('pts_count')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('fwd_from')->nullable();
            $table->index('fwd_from', 'ix_3c5912d72ff6029fc73e71e4');
            $table->bigInteger('via_bot_id')->nullable();
            $table->index('via_bot_id', 'ix_2e31ab2808010703c07b14ac');
            $table->bigInteger('reply_to')->nullable();
            $table->index('reply_to', 'ix_1db9797f8b36343286c502f5');
            $table->integer('ttl_period')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_257930aeb5850cf3496e1e8c');
            $table->index('account_id', 'ix_73c0aa05e963a46e0eab7429');
        });
        Schema::create('tl_updates_update_short_chat_message__entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_76199d1f809b2bd0b6dff662')->references('id')->on('tl_updates_update_short_chat_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0a4e729b3f356264a2b8');
            $table->index('account_id', 'ix_293e4269b6bca0ed9367311e');
        });
        Schema::create('tl_updates_update_short_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('out')->default(false);
            $table->boolean('mentioned')->default(false);
            $table->boolean('media_unread')->default(false);
            $table->boolean('silent')->default(false);
            $table->integer('tl_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_5988f9d3b5f1f0ac7b468e52');
            $table->text('message')->nullable();
            $table->integer('pts')->nullable();
            $table->integer('pts_count')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('fwd_from')->nullable();
            $table->index('fwd_from', 'ix_6083c80fb4e6f0ca9ff16161');
            $table->bigInteger('via_bot_id')->nullable();
            $table->index('via_bot_id', 'ix_8579e6c7b174b8e8abb9dd36');
            $table->bigInteger('reply_to')->nullable();
            $table->index('reply_to', 'ix_d991244b44fe97f50b41d8ba');
            $table->integer('ttl_period')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b7564df57e0507e95fb3bdce');
            $table->index('account_id', 'ix_f414b22adb4ead91830759e6');
        });
        Schema::create('tl_updates_update_short_message__entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_af42ccb3062fb8cea14410c9')->references('id')->on('tl_updates_update_short_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_16d2c9eb89082b8aae79');
            $table->index('account_id', 'ix_fff41611d1502d434eb5d6bf');
        });
        Schema::create('tl_updates_update_short_sent_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('out')->default(false);
            $table->integer('tl_id')->nullable();
            $table->integer('pts')->nullable();
            $table->integer('pts_count')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('media')->nullable();
            $table->index('media', 'ix_8d4b8dabd160302c5039ddc8');
            $table->integer('ttl_period')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_269afdfe5a5b39dd3f8b7a61');
            $table->index('account_id', 'ix_53e781c61f6e20393a06c5db');
        });
        Schema::create('tl_updates_update_short_sent_message__entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_55078cdc44b180dcd47c2a50')->references('id')->on('tl_updates_update_short_sent_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_bd45d68cbc4f8afd9c34');
            $table->index('account_id', 'ix_cfaa5895aad9d78bd5af0054');
        });
        Schema::create('tl_updates_updates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('date')->nullable();
            $table->integer('seq')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_33d21e7464e180002c85f662');
            $table->index('account_id', 'ix_5c46e86d078f7a8dfa21d795');
        });
        Schema::create('tl_updates_updates__updates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_bd18dae654a913e7a238df85')->references('id')->on('tl_updates_updates')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b6c0efd4e8d6b429e369');
            $table->index('account_id', 'ix_29cd5c1dbf599df2387a05c0');
        });
        Schema::create('tl_updates_updates__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_2a31c75472f1c7876d46584a')->references('id')->on('tl_updates_updates')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b87bc8d0093d34ee9243');
            $table->index('account_id', 'ix_b39234e261ba096db8f79df2');
        });
        Schema::create('tl_updates_updates__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_2463db9f514e0c6fe9653dd9')->references('id')->on('tl_updates_updates')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5b5732d34d391ad11799');
            $table->index('account_id', 'ix_059089fb5105a488e6ac21fa');
        });
        Schema::create('tl_updates_updates_combined', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('date')->nullable();
            $table->integer('seq_start')->nullable();
            $table->integer('seq')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_35987c7e16d486d50fa4418d');
            $table->index('account_id', 'ix_c2c97482f7f5cbcdc7bc644f');
        });
        Schema::create('tl_updates_updates_combined__updates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_233b26df15e13e2e443f29c6')->references('id')->on('tl_updates_updates_combined')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5945967c5e65c9c80268');
            $table->index('account_id', 'ix_574c2afd5b7cbde7906db413');
        });
        Schema::create('tl_updates_updates_combined__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_fb2d30e05ca9de47ce158fb4')->references('id')->on('tl_updates_updates_combined')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_7347de1d490987ecf2af');
            $table->index('account_id', 'ix_8667555fd7edb7b34ff44873');
        });
        Schema::create('tl_updates_updates_combined__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_620abba2ccb6e6166168224e')->references('id')->on('tl_updates_updates_combined')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5c640e1e6f07ffa44ca6');
            $table->index('account_id', 'ix_46dd44299f98dc76c4ab98d6');
        });
        Schema::create('tl_updates_updates_too_long', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bdb830ec805688b1d3dc0af8');
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
    }
};
