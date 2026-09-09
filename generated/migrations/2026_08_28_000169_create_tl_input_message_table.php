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
            $table->index('constructor_id', 'ix_7add73981d5eb07ecc7f19f5');
            $table->index('account_id', 'ix_a7ea81875c4bf158f9a00f1b');
        });
        Schema::create('tl_input_message_input_message_callback_query', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_message')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->bigInteger('query_id');
            $table->index('query_id', 'ix_796e7a16e339b0ea2d616f81');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_70715fa3124f4f1da42f9caa');
            $table->unique(['account_id', 'tl_id'], 'ux_ca21104ebdf888614a42');
        });
        Schema::create('tl_input_message_input_message_i_d', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_message')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_792ba7f5d57d04326c7f0b1f');
            $table->unique(['account_id', 'tl_id'], 'ux_c606c8bfa4a4bb948667');
        });
        Schema::create('tl_input_message_input_message_pinned', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_message')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b405cd272d0b4c774e32f186');
        });
        Schema::create('tl_input_message_input_message_reply_to', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_message')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cd2b4f17ca442044f3ccbba7');
            $table->unique(['account_id', 'tl_id'], 'ux_e8bfc5d5c1bb3205f58e');
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
