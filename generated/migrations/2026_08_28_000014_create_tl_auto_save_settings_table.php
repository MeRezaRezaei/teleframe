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
        Schema::create('tl_auto_save_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_auto_save_settings_auto_save_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auto_save_settings')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('photos')->default(false);
            $table->boolean('videos')->default(false);
            $table->bigInteger('video_max_size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auto_save_settings_auto_save_settings');
        Schema::dropIfExists('tl_auto_save_settings');
    }
};
