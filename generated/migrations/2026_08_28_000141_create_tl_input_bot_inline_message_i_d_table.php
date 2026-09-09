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
            $table->index('constructor_id', 'ix_9753caaa1bd88f5fa86365bc');
            $table->index('account_id', 'ix_d23f28478f961acde13ffb4f');
        });
        Schema::create('tl_input_bot_inline_message_i_d_input_bot_inl_65be0b9b7598', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message_i_d')->cascadeOnDelete();
            $table->integer('dc_id');
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_43fc592b7828395cd2201ea7');
            $table->unique(['account_id', 'tl_id'], 'ux_b134797d560bfe5a2546');
        });
        Schema::create('tl_input_bot_inline_message_i_d_input_bot_inl_d3b2ec5fd706', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message_i_d')->cascadeOnDelete();
            $table->integer('dc_id');
            $table->bigInteger('owner_id');
            $table->index('owner_id', 'ix_f42b9edd4a439fefc1562fdb');
            $table->integer('tl_id');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_217bf9955bbea4ab0dc23735');
            $table->unique(['account_id', 'tl_id'], 'ux_247b8e4cd7f8a340cc74');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_bot_inline_message_i_d_input_bot_inl_d3b2ec5fd706');
        Schema::dropIfExists('tl_input_bot_inline_message_i_d_input_bot_inl_65be0b9b7598');
        Schema::dropIfExists('tl_input_bot_inline_message_i_d');
    }
};
