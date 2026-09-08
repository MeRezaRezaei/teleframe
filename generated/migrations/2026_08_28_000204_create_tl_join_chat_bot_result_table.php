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
        Schema::create('tl_join_chat_bot_result', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_join_chat_bot_result_join_chat_bot_result_approved', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_join_chat_bot_result')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_join_chat_bot_result_join_chat_bot_result_declined', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_join_chat_bot_result')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_join_chat_bot_result_join_chat_bot_result_queued', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_join_chat_bot_result')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_join_chat_bot_result_join_chat_bot_result_web_view', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_join_chat_bot_result')->cascadeOnDelete();
            $table->text('url');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_join_chat_bot_result_join_chat_bot_result_web_view');
        Schema::dropIfExists('tl_join_chat_bot_result_join_chat_bot_result_queued');
        Schema::dropIfExists('tl_join_chat_bot_result_join_chat_bot_result_declined');
        Schema::dropIfExists('tl_join_chat_bot_result_join_chat_bot_result_approved');
        Schema::dropIfExists('tl_join_chat_bot_result');
    }
};
