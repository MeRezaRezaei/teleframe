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
        Schema::create('tl_inline_bot_web_view', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_73b57ce3972204d2275800b8');
            $table->index('account_id', 'ix_74bb5595e20f62ae40ed10df');
        });
        Schema::create('tl_inline_bot_web_view_inline_bot_web_view', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_inline_bot_web_view')->cascadeOnDelete();
            $table->text('text');
            $table->text('url');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b167356ed62b196b47e84b4f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_inline_bot_web_view_inline_bot_web_view');
        Schema::dropIfExists('tl_inline_bot_web_view');
    }
};
