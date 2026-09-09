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
        Schema::create('tl_bot_inline_result', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_dca08696851ba0e0d0efb2ee');
            $table->index('account_id', 'ix_b8b1a8d4514adf515b6f7e45');
        });
        Schema::create('tl_bot_inline_result_bot_inline_media_result', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_inline_result')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('tl_id');
            $table->text('tl_type');
            $table->uuid('photo')->nullable();
            $table->index('photo', 'ix_861a1d620583adf89607f81e');
            $table->uuid('document')->nullable();
            $table->index('document', 'ix_fc37377a45150f12741a8704');
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->uuid('send_message');
            $table->index('send_message', 'ix_25cb1efda190a60d2543a297');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_061a5fb1d3a7a503bc813f0a');
            $table->unique(['account_id', 'tl_id'], 'ux_926317a8fdcbe45aefd3');
        });
        Schema::create('tl_bot_inline_result_bot_inline_result', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_inline_result')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('tl_id');
            $table->text('tl_type');
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->text('url')->nullable();
            $table->uuid('thumb')->nullable();
            $table->index('thumb', 'ix_bd419356eee87814b9a42ab0');
            $table->uuid('content')->nullable();
            $table->index('content', 'ix_988f3ae357589468a97a3055');
            $table->uuid('send_message');
            $table->index('send_message', 'ix_0a36f9d2cbcba9dc6658dc22');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f793d2013c96578363927c76');
            $table->unique(['account_id', 'tl_id'], 'ux_89785094f75b7d83114b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_inline_result_bot_inline_result');
        Schema::dropIfExists('tl_bot_inline_result_bot_inline_media_result');
        Schema::dropIfExists('tl_bot_inline_result');
    }
};
