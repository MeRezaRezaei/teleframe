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
        Schema::create('tl_messages_messages_channel_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('inexact')->default(false);
            $table->integer('pts')->nullable();
            $table->integer('count')->nullable();
            $table->integer('offset_id_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_456fc46da7f426f4c7fbabcc');
            $table->index('account_id', 'ix_047c1927f0f9045141853c95');
        });
        Schema::create('tl_messages_messages_channel_messages__messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_9f75528e581c5d9f1a41f09b')->references('id')->on('tl_messages_messages_channel_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c7f7ad0b4d0de4a9eae1');
            $table->index('account_id', 'ix_e3415ee5da95ea9bdc2150cd');
        });
        Schema::create('tl_messages_messages_channel_messages__topics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_99bba0d2ce41501bea4ec007')->references('id')->on('tl_messages_messages_channel_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c153c549b622b162d66f');
            $table->index('account_id', 'ix_a0cc2459652c61d74eacdb6d');
        });
        Schema::create('tl_messages_messages_channel_messages__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_c3902333a62d291dee21d33a')->references('id')->on('tl_messages_messages_channel_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d116205bf5be2a14114b');
            $table->index('account_id', 'ix_e4b23380f3f90b3f7d8b2cd3');
        });
        Schema::create('tl_messages_messages_channel_messages__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_66e2e524e326c86d2b271186')->references('id')->on('tl_messages_messages_channel_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_df059b3bc356deaae0b9');
            $table->index('account_id', 'ix_6e555f9a2e2745f7c460ae00');
        });
        Schema::create('tl_messages_messages_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b9b9c4ddab540d2c2f5bae15');
            $table->index('account_id', 'ix_3f0335f8e46caa17680bd30f');
        });
        Schema::create('tl_messages_messages_messages__messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_ec2498a1fe78993e2ef9b812')->references('id')->on('tl_messages_messages_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d3116c59aef2426bcaa5');
            $table->index('account_id', 'ix_e6813160f14c6e353f793327');
        });
        Schema::create('tl_messages_messages_messages__topics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_a112797e0398104cbbea2af7')->references('id')->on('tl_messages_messages_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_aaf8340bdb15711d53b2');
            $table->index('account_id', 'ix_e043d10aca59811100801468');
        });
        Schema::create('tl_messages_messages_messages__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_afa65b9c140016785bfc402c')->references('id')->on('tl_messages_messages_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_2f84f9693760818d8d7c');
            $table->index('account_id', 'ix_c59135460357d9c47f2ab51a');
        });
        Schema::create('tl_messages_messages_messages__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_3c1e26c30a97327780dc22f7')->references('id')->on('tl_messages_messages_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9973ff1293932d5bc53d');
            $table->index('account_id', 'ix_9e64ea5b991528117add55da');
        });
        Schema::create('tl_messages_messages_messages_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6dc15cdae0f814d97e9fc7d8');
            $table->index('account_id', 'ix_83aaf9de945ddd307b401cd0');
        });
        Schema::create('tl_messages_messages_messages_slice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('inexact')->default(false);
            $table->integer('count')->nullable();
            $table->integer('next_rate')->nullable();
            $table->integer('offset_id_offset')->nullable();
            $table->bigInteger('search_flood')->nullable();
            $table->index('search_flood', 'ix_dd99992332794ac0f579786d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_abaf7d9f2b39f7f2cbbf5db6');
            $table->index('account_id', 'ix_68707d4de551cac545b537a8');
        });
        Schema::create('tl_messages_messages_messages_slice__messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_7c755b23247396f062ed5e87')->references('id')->on('tl_messages_messages_messages_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8228322cf3e5e0eba04b');
            $table->index('account_id', 'ix_05a35e3ca4c98b4f854de4bb');
        });
        Schema::create('tl_messages_messages_messages_slice__topics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_7a983fb296a7b9d4b8ba51d6')->references('id')->on('tl_messages_messages_messages_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8c5e6b160eb71310139c');
            $table->index('account_id', 'ix_be3d53904ca2af6e5c036f02');
        });
        Schema::create('tl_messages_messages_messages_slice__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_3ede9697682390ed9a80a710')->references('id')->on('tl_messages_messages_messages_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_84f7212688708458c8ea');
            $table->index('account_id', 'ix_79ab46b2e04544929832e187');
        });
        Schema::create('tl_messages_messages_messages_slice__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_f4e548fb79baf45df350314e')->references('id')->on('tl_messages_messages_messages_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_52d3e392a396a4545b9b');
            $table->index('account_id', 'ix_c6974280ac6ea4548617d263');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_messages_messages_slice__users');
        Schema::dropIfExists('tl_messages_messages_messages_slice__chats');
        Schema::dropIfExists('tl_messages_messages_messages_slice__topics');
        Schema::dropIfExists('tl_messages_messages_messages_slice__messages');
        Schema::dropIfExists('tl_messages_messages_messages_slice');
        Schema::dropIfExists('tl_messages_messages_messages_not_modified');
        Schema::dropIfExists('tl_messages_messages_messages__users');
        Schema::dropIfExists('tl_messages_messages_messages__chats');
        Schema::dropIfExists('tl_messages_messages_messages__topics');
        Schema::dropIfExists('tl_messages_messages_messages__messages');
        Schema::dropIfExists('tl_messages_messages_messages');
        Schema::dropIfExists('tl_messages_messages_channel_messages__users');
        Schema::dropIfExists('tl_messages_messages_channel_messages__chats');
        Schema::dropIfExists('tl_messages_messages_channel_messages__topics');
        Schema::dropIfExists('tl_messages_messages_channel_messages__messages');
        Schema::dropIfExists('tl_messages_messages_channel_messages');
    }
};
