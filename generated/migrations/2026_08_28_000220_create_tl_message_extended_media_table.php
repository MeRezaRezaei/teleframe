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
        Schema::create('tl_message_extended_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_message_extended_media_message_extended_media', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_extended_media')->cascadeOnDelete();
            $table->uuid('media');
            $table->timestamps();
        });
        Schema::create('tl_message_extended_media_message_extended_media_preview', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_extended_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->uuid('thumb')->nullable();
            $table->integer('video_duration')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_extended_media_message_extended_media_preview');
        Schema::dropIfExists('tl_message_extended_media_message_extended_media');
        Schema::dropIfExists('tl_message_extended_media');
    }
};
