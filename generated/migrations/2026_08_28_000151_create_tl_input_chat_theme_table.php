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
        Schema::create('tl_input_chat_theme', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_chat_theme_input_chat_theme', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_chat_theme')->cascadeOnDelete();
            $table->text('emoticon');
            $table->timestamps();
        });
        Schema::create('tl_input_chat_theme_input_chat_theme_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_chat_theme')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_chat_theme_input_chat_theme_unique_gift', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_chat_theme')->cascadeOnDelete();
            $table->text('slug');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_chat_theme_input_chat_theme_unique_gift');
        Schema::dropIfExists('tl_input_chat_theme_input_chat_theme_empty');
        Schema::dropIfExists('tl_input_chat_theme_input_chat_theme');
        Schema::dropIfExists('tl_input_chat_theme');
    }
};
