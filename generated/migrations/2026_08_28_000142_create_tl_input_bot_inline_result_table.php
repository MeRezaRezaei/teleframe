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
        Schema::create('tl_input_bot_inline_result', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_bot_inline_result_input_bot_inline_result', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_result')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('tl_id');
            $table->text('tl_type');
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->text('url')->nullable();
            $table->uuid('thumb')->nullable();
            $table->uuid('content')->nullable();
            $table->uuid('send_message');
            $table->timestamps();
        });
        Schema::create('tl_input_bot_inline_result_input_bot_inline_r_ddd2d6c152ff', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_result')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('tl_id');
            $table->text('tl_type');
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->uuid('document');
            $table->uuid('send_message');
            $table->timestamps();
        });
        Schema::create('tl_input_bot_inline_result_input_bot_inline_result_game', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_result')->cascadeOnDelete();
            $table->text('tl_id');
            $table->text('short_name');
            $table->uuid('send_message');
            $table->timestamps();
        });
        Schema::create('tl_input_bot_inline_result_input_bot_inline_result_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_result')->cascadeOnDelete();
            $table->text('tl_id');
            $table->text('tl_type');
            $table->uuid('photo');
            $table->uuid('send_message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_bot_inline_result_input_bot_inline_result_photo');
        Schema::dropIfExists('tl_input_bot_inline_result_input_bot_inline_result_game');
        Schema::dropIfExists('tl_input_bot_inline_result_input_bot_inline_r_ddd2d6c152ff');
        Schema::dropIfExists('tl_input_bot_inline_result_input_bot_inline_result');
        Schema::dropIfExists('tl_input_bot_inline_result');
    }
};
