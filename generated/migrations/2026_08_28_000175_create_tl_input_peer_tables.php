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
        Schema::create('tl_input_peer_input_peer_channel', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('channel_id')->nullable();
            $table->index('channel_id', 'ix_00ee4d6ba0fff673434a7c6f');
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9f35f186b6e4ff72bcc39a7f');
            $table->index('account_id', 'ix_73fd72f0dcf28950e0d65650');
        });
        Schema::create('tl_input_peer_input_peer_channel_from_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_e1eb028fa28205aea9bb747f');
            $table->integer('msg_id')->nullable();
            $table->bigInteger('channel_id')->nullable();
            $table->index('channel_id', 'ix_7cf0cf2f91d87cb185e0e2f3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b1cb5d270516bece3e9a142d');
            $table->index('account_id', 'ix_0aa55c1815e3c3ac1f5321c6');
        });
        Schema::create('tl_input_peer_input_peer_chat', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('chat_id')->nullable();
            $table->index('chat_id', 'ix_5af0b08bb7512a4ab6338800');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_53f86fa0659560fce13259c2');
            $table->index('account_id', 'ix_5c75c0b3393d6bbae2066129');
        });
        Schema::create('tl_input_peer_input_peer_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_427ed9f2ba65c57cbe86b03c');
            $table->index('account_id', 'ix_3df46e1faa5fe577295c03df');
        });
        Schema::create('tl_input_peer_input_peer_self', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8acac3841fb387b8aaec9d38');
            $table->index('account_id', 'ix_6c6449aa4166ef3811f75c70');
        });
        Schema::create('tl_input_peer_input_peer_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_9f75060d174f3e7986a38095');
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_11a0a01d4bd80a201571f16e');
            $table->index('account_id', 'ix_6afe82ad253b84c800c80a6f');
        });
        Schema::create('tl_input_peer_input_peer_user_from_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_d5052be6198167074658f842');
            $table->integer('msg_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_c2ac10407a221212676dd3e5');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f9859960b62a05306e04e209');
            $table->index('account_id', 'ix_0ef3fa424211dcddbee077bf');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_peer_input_peer_user_from_message');
        Schema::dropIfExists('tl_input_peer_input_peer_user');
        Schema::dropIfExists('tl_input_peer_input_peer_self');
        Schema::dropIfExists('tl_input_peer_input_peer_empty');
        Schema::dropIfExists('tl_input_peer_input_peer_chat');
        Schema::dropIfExists('tl_input_peer_input_peer_channel_from_message');
        Schema::dropIfExists('tl_input_peer_input_peer_channel');
    }
};
