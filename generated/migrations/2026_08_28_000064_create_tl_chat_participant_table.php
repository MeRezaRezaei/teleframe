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
        Schema::create('tl_chat_participant', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_efeffc1f9de76d81fab4befe');
            $table->index('account_id', 'ix_3f9e2d6de41aeadf0ea3e8e7');
        });
        Schema::create('tl_chat_participant_chat_participant', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_participant')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_2ea2763e2b5087f0f6844a0b');
            $table->bigInteger('inviter_id');
            $table->index('inviter_id', 'ix_4f36051d5014d891a227f751');
            $table->integer('date');
            $table->text('rank')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a6f7c206c345aae647b3b84d');
        });
        Schema::create('tl_chat_participant_chat_participant_admin', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_participant')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_2ac925479354845b8ddef1a1');
            $table->bigInteger('inviter_id');
            $table->index('inviter_id', 'ix_a3279a566705388662bdf3b3');
            $table->integer('date');
            $table->text('rank')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5933775aa6f972c352b429d0');
        });
        Schema::create('tl_chat_participant_chat_participant_creator', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_participant')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_10c9caba02d8766072d45284');
            $table->text('rank')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_06f35dcd1b0cdf06cbbff045');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_participant_chat_participant_creator');
        Schema::dropIfExists('tl_chat_participant_chat_participant_admin');
        Schema::dropIfExists('tl_chat_participant_chat_participant');
        Schema::dropIfExists('tl_chat_participant');
    }
};
