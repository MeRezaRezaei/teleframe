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
        Schema::create('tl_photo_photo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_stickers')->default(false);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->binary('file_reference')->nullable();
            $table->integer('date')->nullable();
            $table->integer('dc_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_aad3f91e3a88e928a68dd037');
            $table->index('account_id', 'ix_e95437d8befc6299ab1d05ae');
        });
        Schema::create('tl_photo_photo__sizes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_a0f9142907816485a55c10ce')->references('id')->on('tl_photo_photo')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4d6adb44fa99462b6e26');
            $table->index('account_id', 'ix_7da4d13ddd67c56eb7cecc93');
        });
        Schema::create('tl_photo_photo__video_sizes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_ff9de6ff3a6a7e036f06773e')->references('id')->on('tl_photo_photo')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f59e9f56f1c3ebd38c8b');
            $table->index('account_id', 'ix_a9d8631f25625c033a0a22b3');
        });
        Schema::create('tl_photo_photo_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_36d8d2ee997fccca9df6de80');
            $table->index('account_id', 'ix_c0a55dc649a5f1ff8e909a08');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_photo_photo_empty');
        Schema::dropIfExists('tl_photo_photo__video_sizes');
        Schema::dropIfExists('tl_photo_photo__sizes');
        Schema::dropIfExists('tl_photo_photo');
    }
};
