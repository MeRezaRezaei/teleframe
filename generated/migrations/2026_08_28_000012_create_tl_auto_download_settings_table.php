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
        Schema::create('tl_auto_download_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_auto_download_settings_auto_download_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auto_download_settings')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('disabled')->default(false);
            $table->boolean('video_preload_large')->default(false);
            $table->boolean('audio_preload_next')->default(false);
            $table->boolean('phonecalls_less_data')->default(false);
            $table->boolean('stories_preload')->default(false);
            $table->integer('photo_size_max');
            $table->bigInteger('video_size_max');
            $table->bigInteger('file_size_max');
            $table->integer('video_upload_maxbitrate');
            $table->integer('small_queue_active_operations_max');
            $table->integer('large_queue_active_operations_max');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auto_download_settings_auto_download_settings');
        Schema::dropIfExists('tl_auto_download_settings');
    }
};
