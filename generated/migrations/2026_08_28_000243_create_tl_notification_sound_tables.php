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
        Schema::create('tl_notification_sound_notification_sound_default', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f5d91ca65247bfd718d921b7');
            $table->index('account_id', 'ix_c42f46aa8ba383f76b9f4dc8');
        });
        Schema::create('tl_notification_sound_notification_sound_local', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('title')->nullable();
            $table->text('data')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_523a340ad45915fedae0c1f7');
            $table->index('account_id', 'ix_2bbfdc0e22992bdb93837d6a');
        });
        Schema::create('tl_notification_sound_notification_sound_none', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bb29fb02dcbc75962766840b');
            $table->index('account_id', 'ix_eaa2899951a7722535cf1ee1');
        });
        Schema::create('tl_notification_sound_notification_sound_ringtone', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2af9ca9cdbae0fb57f49cd91');
            $table->index('account_id', 'ix_d3e0f477a6dacf80d18406dd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_notification_sound_notification_sound_ringtone');
        Schema::dropIfExists('tl_notification_sound_notification_sound_none');
        Schema::dropIfExists('tl_notification_sound_notification_sound_local');
        Schema::dropIfExists('tl_notification_sound_notification_sound_default');
    }
};
