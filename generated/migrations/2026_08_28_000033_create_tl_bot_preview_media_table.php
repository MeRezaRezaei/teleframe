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
        Schema::create('tl_bot_preview_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_bed4f62d66ea4fb062093b9f');
            $table->index('account_id', 'ix_72fe3c6e570ab580cbd9a3e6');
        });
        Schema::create('tl_bot_preview_media_bot_preview_media', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_preview_media')->cascadeOnDelete();
            $table->integer('date');
            $table->uuid('media');
            $table->index('media', 'ix_4d8c020a44d561763ef4f01c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a0fe57d3dee44ff08ce009f9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_preview_media_bot_preview_media');
        Schema::dropIfExists('tl_bot_preview_media');
    }
};
