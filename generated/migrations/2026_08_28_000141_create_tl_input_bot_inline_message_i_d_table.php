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
        Schema::create('tl_input_bot_inline_message_i_d', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_bot_inline_message_i_d_input_bot_inl_65be0b9b7598', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message_i_d')->cascadeOnDelete();
            $table->integer('dc_id');
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->timestamps();
        });
        Schema::create('tl_input_bot_inline_message_i_d_input_bot_inl_d3b2ec5fd706', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message_i_d')->cascadeOnDelete();
            $table->integer('dc_id');
            $table->bigInteger('owner_id');
            $table->integer('tl_id');
            $table->bigInteger('access_hash');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_bot_inline_message_i_d_input_bot_inl_d3b2ec5fd706');
        Schema::dropIfExists('tl_input_bot_inline_message_i_d_input_bot_inl_65be0b9b7598');
        Schema::dropIfExists('tl_input_bot_inline_message_i_d');
    }
};
