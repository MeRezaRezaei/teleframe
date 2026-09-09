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
        Schema::create('tl_notification_sound', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_3554dd1cf0a2d720aff24dae');
            $table->index('account_id', 'ix_a4e7b362bcc4230445a77a79');
        });
        Schema::create('tl_notification_sound_notification_sound_default', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_notification_sound')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c42f46aa8ba383f76b9f4dc8');
        });
        Schema::create('tl_notification_sound_notification_sound_local', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_notification_sound')->cascadeOnDelete();
            $table->text('title');
            $table->text('data');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2bbfdc0e22992bdb93837d6a');
        });
        Schema::create('tl_notification_sound_notification_sound_none', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_notification_sound')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_eaa2899951a7722535cf1ee1');
        });
        Schema::create('tl_notification_sound_notification_sound_ringtone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_notification_sound')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d3e0f477a6dacf80d18406dd');
            $table->unique(['account_id', 'tl_id'], 'ux_74b7a1d272e8b83e0eef');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_notification_sound_notification_sound_ringtone');
        Schema::dropIfExists('tl_notification_sound_notification_sound_none');
        Schema::dropIfExists('tl_notification_sound_notification_sound_local');
        Schema::dropIfExists('tl_notification_sound_notification_sound_default');
        Schema::dropIfExists('tl_notification_sound');
    }
};
