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
        Schema::create('tl_auto_download_settings_auto_download_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('disabled')->default(false);
            $table->boolean('video_preload_large')->default(false);
            $table->boolean('audio_preload_next')->default(false);
            $table->boolean('phonecalls_less_data')->default(false);
            $table->boolean('stories_preload')->default(false);
            $table->integer('photo_size_max')->nullable();
            $table->bigInteger('video_size_max')->nullable();
            $table->bigInteger('file_size_max')->nullable();
            $table->integer('video_upload_maxbitrate')->nullable();
            $table->integer('small_queue_active_operations_max')->nullable();
            $table->integer('large_queue_active_operations_max')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b958f8e4a3811a09fc78eed3');
            $table->index('account_id', 'ix_4bd92e79b7abd49497ac0486');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auto_download_settings_auto_download_settings');
    }
};
