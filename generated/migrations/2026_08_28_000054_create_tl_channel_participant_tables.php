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
        Schema::create('tl_channel_participant_channel_participant', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_924b3c11a556c54fe309febd');
            $table->integer('date')->nullable();
            $table->integer('subscription_until_date')->nullable();
            $table->text('rank')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_836e16060d10414b054f94d2');
            $table->index('account_id', 'ix_f9004dd7e6902c544be8be73');
        });
        Schema::create('tl_channel_participant_channel_participant_admin', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('can_edit')->default(false);
            $table->boolean('self')->default(false);
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_3d0bd7503d45c2f7e882764c');
            $table->bigInteger('inviter_id')->nullable();
            $table->index('inviter_id', 'ix_5f7da3bf760792c3836b35a4');
            $table->bigInteger('promoted_by')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('admin_rights')->nullable();
            $table->index('admin_rights', 'ix_89ce22003de3e00acea5c258');
            $table->text('rank')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_39d06db4154e574d72ba7ef6');
            $table->index('account_id', 'ix_db647bfb45a940d98b9a635f');
        });
        Schema::create('tl_channel_participant_channel_participant_banned', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('left')->default(false);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_36e0d8cf18ecc3f5bbb022fc');
            $table->bigInteger('kicked_by')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('banned_rights')->nullable();
            $table->index('banned_rights', 'ix_d2e3ec1418cc4e5d582341da');
            $table->text('rank')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8c75468f43c60bf09cc6018b');
            $table->index('account_id', 'ix_d4548e6be28da5fc0bb83930');
        });
        Schema::create('tl_channel_participant_channel_participant_creator', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_dd7266ed50d5ce8b949ca1db');
            $table->bigInteger('admin_rights')->nullable();
            $table->index('admin_rights', 'ix_af02684d8fe626eabc5e56fe');
            $table->text('rank')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e7a29f25dbad29b30c948e86');
            $table->index('account_id', 'ix_1ffbbc1b06d653b209f42b72');
        });
        Schema::create('tl_channel_participant_channel_participant_left', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_a0d5fd90c3957cdff837770a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a6e61bc341a08ee594ccf87c');
            $table->index('account_id', 'ix_2b7a928eebf3d3e399ace7e3');
        });
        Schema::create('tl_channel_participant_channel_participant_self', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('via_request')->default(false);
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_aba6e0d9b33e6123b4076a03');
            $table->bigInteger('inviter_id')->nullable();
            $table->index('inviter_id', 'ix_2a9d12bb1458569aba619ed7');
            $table->integer('date')->nullable();
            $table->integer('subscription_until_date')->nullable();
            $table->text('rank')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_11e2c47890cf55cfe76cbb71');
            $table->index('account_id', 'ix_8c05307128786745958f43e7');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channel_participant_channel_participant_self');
        Schema::dropIfExists('tl_channel_participant_channel_participant_left');
        Schema::dropIfExists('tl_channel_participant_channel_participant_creator');
        Schema::dropIfExists('tl_channel_participant_channel_participant_banned');
        Schema::dropIfExists('tl_channel_participant_channel_participant_admin');
        Schema::dropIfExists('tl_channel_participant_channel_participant');
    }
};
