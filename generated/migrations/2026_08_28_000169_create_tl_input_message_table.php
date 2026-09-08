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
        Schema::create('tl_input_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_message_input_message_callback_query', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_message')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->bigInteger('query_id');
            $table->timestamps();
        });
        Schema::create('tl_input_message_input_message_i_d', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_message')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->timestamps();
        });
        Schema::create('tl_input_message_input_message_pinned', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_message')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_message_input_message_reply_to', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_message')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_message_input_message_reply_to');
        Schema::dropIfExists('tl_input_message_input_message_pinned');
        Schema::dropIfExists('tl_input_message_input_message_i_d');
        Schema::dropIfExists('tl_input_message_input_message_callback_query');
        Schema::dropIfExists('tl_input_message');
    }
};
