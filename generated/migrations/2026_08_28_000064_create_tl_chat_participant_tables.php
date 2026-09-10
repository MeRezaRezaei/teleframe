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
        Schema::create('tl_chat_participant_chat_participant', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_2ea2763e2b5087f0f6844a0b');
            $table->bigInteger('inviter_id')->nullable();
            $table->index('inviter_id', 'ix_4f36051d5014d891a227f751');
            $table->integer('date')->nullable();
            $table->text('rank')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b49f26a93b19de8dbf857940');
            $table->index('account_id', 'ix_a6f7c206c345aae647b3b84d');
        });
        Schema::create('tl_chat_participant_chat_participant_admin', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_2ac925479354845b8ddef1a1');
            $table->bigInteger('inviter_id')->nullable();
            $table->index('inviter_id', 'ix_a3279a566705388662bdf3b3');
            $table->integer('date')->nullable();
            $table->text('rank')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_938bd1340d13d94c265bf44f');
            $table->index('account_id', 'ix_5933775aa6f972c352b429d0');
        });
        Schema::create('tl_chat_participant_chat_participant_creator', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_10c9caba02d8766072d45284');
            $table->text('rank')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4a55ebd7082bfd3705e1ea6f');
            $table->index('account_id', 'ix_06f35dcd1b0cdf06cbbff045');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_participant_chat_participant_creator');
        Schema::dropIfExists('tl_chat_participant_chat_participant_admin');
        Schema::dropIfExists('tl_chat_participant_chat_participant');
    }
};
