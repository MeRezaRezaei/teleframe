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
        Schema::create('tl_channel_participant', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_channel_participant_channel_participant', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_participant')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('user_id');
            $table->integer('date');
            $table->integer('subscription_until_date')->nullable();
            $table->text('rank')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_channel_participant_channel_participant_admin', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_participant')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('can_edit')->default(false);
            $table->boolean('self')->default(false);
            $table->bigInteger('user_id');
            $table->bigInteger('inviter_id')->nullable();
            $table->bigInteger('promoted_by');
            $table->integer('date');
            $table->uuid('admin_rights');
            $table->text('rank')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_channel_participant_channel_participant_banned', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_participant')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('left')->default(false);
            $table->uuid('peer');
            $table->bigInteger('kicked_by');
            $table->integer('date');
            $table->uuid('banned_rights');
            $table->text('rank')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_channel_participant_channel_participant_creator', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_participant')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('user_id');
            $table->uuid('admin_rights');
            $table->text('rank')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_channel_participant_channel_participant_left', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_participant')->cascadeOnDelete();
            $table->uuid('peer');
            $table->timestamps();
        });
        Schema::create('tl_channel_participant_channel_participant_self', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_participant')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('via_request')->default(false);
            $table->bigInteger('user_id');
            $table->bigInteger('inviter_id');
            $table->integer('date');
            $table->integer('subscription_until_date')->nullable();
            $table->text('rank')->nullable();
            $table->timestamps();
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
        Schema::dropIfExists('tl_channel_participant');
    }
};
