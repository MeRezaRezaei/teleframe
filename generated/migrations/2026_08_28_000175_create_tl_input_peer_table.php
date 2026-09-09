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
        Schema::create('tl_input_peer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_3997a1fb93eacbe3129a908c');
            $table->index('account_id', 'ix_71282132804a82d70f15e91a');
        });
        Schema::create('tl_input_peer_input_peer_channel', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_peer')->cascadeOnDelete();
            $table->bigInteger('channel_id');
            $table->index('channel_id', 'ix_00ee4d6ba0fff673434a7c6f');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_73fd72f0dcf28950e0d65650');
        });
        Schema::create('tl_input_peer_input_peer_channel_from_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_peer')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_e1eb028fa28205aea9bb747f');
            $table->integer('msg_id');
            $table->bigInteger('channel_id');
            $table->index('channel_id', 'ix_7cf0cf2f91d87cb185e0e2f3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0aa55c1815e3c3ac1f5321c6');
        });
        Schema::create('tl_input_peer_input_peer_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_peer')->cascadeOnDelete();
            $table->bigInteger('chat_id');
            $table->index('chat_id', 'ix_5af0b08bb7512a4ab6338800');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5c75c0b3393d6bbae2066129');
        });
        Schema::create('tl_input_peer_input_peer_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_peer')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3df46e1faa5fe577295c03df');
        });
        Schema::create('tl_input_peer_input_peer_self', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_peer')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6c6449aa4166ef3811f75c70');
        });
        Schema::create('tl_input_peer_input_peer_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_peer')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_9f75060d174f3e7986a38095');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6afe82ad253b84c800c80a6f');
        });
        Schema::create('tl_input_peer_input_peer_user_from_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_peer')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_d5052be6198167074658f842');
            $table->integer('msg_id');
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_c2ac10407a221212676dd3e5');
            $table->bigInteger('account_id');
            $table->timestamps();
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
        Schema::dropIfExists('tl_input_peer');
    }
};
