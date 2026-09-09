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
            $table->index('constructor_id', 'ix_34c238c76003fbe4346ea158');
            $table->index('account_id', 'ix_93fb9f91a390f676d7faec86');
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
            $table->index('thumb', 'ix_35e4a2a0b4a8b557a9d58a00');
            $table->uuid('content')->nullable();
            $table->index('content', 'ix_b0720f9798231bf4b46afa48');
            $table->uuid('send_message');
            $table->index('send_message', 'ix_9e1db7c2c0614b905229cc19');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a01a3d7bbbd30d2a6e24c504');
            $table->unique(['account_id', 'tl_id'], 'ux_2a008044caa2d07ac0d3');
        });
        Schema::create('tl_input_bot_inline_result_input_bot_inline_r_ddd2d6c152ff', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_result')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('tl_id');
            $table->text('tl_type');
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->uuid('document');
            $table->index('document', 'ix_b42002e4927275f827ff1fce');
            $table->uuid('send_message');
            $table->index('send_message', 'ix_9e7ef73e075c2a80a83d98ab');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_19ed3fc70c4db417c1300f5c');
            $table->unique(['account_id', 'tl_id'], 'ux_a5ddfa66ed2237a835a9');
        });
        Schema::create('tl_input_bot_inline_result_input_bot_inline_result_game', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_result')->cascadeOnDelete();
            $table->text('tl_id');
            $table->text('short_name');
            $table->uuid('send_message');
            $table->index('send_message', 'ix_b8c8dc532f372fa3f77c6ce4');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3724d0b654035c2df50613a4');
            $table->unique(['account_id', 'tl_id'], 'ux_4a97f05ab019a6c179c7');
        });
        Schema::create('tl_input_bot_inline_result_input_bot_inline_result_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_result')->cascadeOnDelete();
            $table->text('tl_id');
            $table->text('tl_type');
            $table->uuid('photo');
            $table->index('photo', 'ix_6e0c8a3fca82db0464dd1bc5');
            $table->uuid('send_message');
            $table->index('send_message', 'ix_78717efcff3a9bc31b84b33d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9dfa91c7fbd83cbe0f0650c1');
            $table->unique(['account_id', 'tl_id'], 'ux_4b401c5bb8edd050ec2a');
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
