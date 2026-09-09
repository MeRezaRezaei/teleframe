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
        Schema::create('tl_bots_exported_bot_token', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_6c49b4335cf0cf8794a8d68b');
            $table->index('account_id', 'ix_33c60c5a3186d99ed621b5ed');
        });
        Schema::create('tl_bots_exported_bot_token_exported_bot_token', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bots_exported_bot_token')->cascadeOnDelete();
            $table->text('token');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a41b64f2d400e4c794e845ff');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bots_exported_bot_token_exported_bot_token');
        Schema::dropIfExists('tl_bots_exported_bot_token');
    }
};
