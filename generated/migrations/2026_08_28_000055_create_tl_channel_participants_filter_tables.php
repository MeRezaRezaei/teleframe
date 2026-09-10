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
        Schema::create('tl_channel_participants_filter_channel_participants_admins', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4a5968f3be9b0cdc576623b5');
            $table->index('account_id', 'ix_92d4940aa86873502dc82737');
        });
        Schema::create('tl_channel_participants_filter_channel_participants_banned', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('q')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_63f58ad18d63906cecd75cea');
            $table->index('account_id', 'ix_18021d52ea5cabb152ec7219');
        });
        Schema::create('tl_channel_participants_filter_channel_participants_bots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6691e88ac1c68d5bccf00c64');
            $table->index('account_id', 'ix_649477dd80f022028440c835');
        });
        Schema::create('tl_channel_participants_filter_channel_partic_c5e6fc6a843c', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('q')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e4cf33bca65b17063010cae2');
            $table->index('account_id', 'ix_4e4214b88e28326fe4826503');
        });
        Schema::create('tl_channel_participants_filter_channel_participants_kicked', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('q')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0d2a801b3acee285ef2b751f');
            $table->index('account_id', 'ix_7570343361eefb2dbb39cf40');
        });
        Schema::create('tl_channel_participants_filter_channel_partic_b9280c888c41', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('q')->nullable();
            $table->integer('top_msg_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c75736902a8f65556d06d857');
            $table->index('account_id', 'ix_f3806d685c26155e8d83e153');
        });
        Schema::create('tl_channel_participants_filter_channel_participants_recent', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_48af869722730675368a9f85');
            $table->index('account_id', 'ix_699aa4f3d4b692d73972a76a');
        });
        Schema::create('tl_channel_participants_filter_channel_participants_search', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('q')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ba523298a144993fb727c31c');
            $table->index('account_id', 'ix_5a1d78a40eda970e206b2010');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channel_participants_filter_channel_participants_search');
        Schema::dropIfExists('tl_channel_participants_filter_channel_participants_recent');
        Schema::dropIfExists('tl_channel_participants_filter_channel_partic_b9280c888c41');
        Schema::dropIfExists('tl_channel_participants_filter_channel_participants_kicked');
        Schema::dropIfExists('tl_channel_participants_filter_channel_partic_c5e6fc6a843c');
        Schema::dropIfExists('tl_channel_participants_filter_channel_participants_bots');
        Schema::dropIfExists('tl_channel_participants_filter_channel_participants_banned');
        Schema::dropIfExists('tl_channel_participants_filter_channel_participants_admins');
    }
};
