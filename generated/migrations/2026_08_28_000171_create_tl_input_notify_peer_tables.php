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
        Schema::create('tl_input_notify_peer_input_notify_broadcasts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8b7ee624a9aa3a4631a950f3');
            $table->index('account_id', 'ix_11233e3c3474be46ec4a3239');
        });
        Schema::create('tl_input_notify_peer_input_notify_chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a70b7932ecdc1ff42183c473');
            $table->index('account_id', 'ix_962ca761cfb39fe649d53ddd');
        });
        Schema::create('tl_input_notify_peer_input_notify_forum_topic', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_f7d432ea39db2f8ca690f45a');
            $table->integer('top_msg_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_551df7a716e5165cf36c5e5d');
            $table->index('account_id', 'ix_d0ff612e80822878da7f03ee');
        });
        Schema::create('tl_input_notify_peer_input_notify_peer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_9450cf64e2d626401c8c153c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5deac5fae5019bb66a024fb8');
            $table->index('account_id', 'ix_86b29c8f5e9bcb65a13c99ba');
        });
        Schema::create('tl_input_notify_peer_input_notify_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fedbf745b48eb3d1b63f5c11');
            $table->index('account_id', 'ix_17f023050a37e5a430c38f00');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_notify_peer_input_notify_users');
        Schema::dropIfExists('tl_input_notify_peer_input_notify_peer');
        Schema::dropIfExists('tl_input_notify_peer_input_notify_forum_topic');
        Schema::dropIfExists('tl_input_notify_peer_input_notify_chats');
        Schema::dropIfExists('tl_input_notify_peer_input_notify_broadcasts');
    }
};
