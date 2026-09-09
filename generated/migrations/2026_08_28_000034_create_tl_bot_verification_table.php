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
        Schema::create('tl_bot_verification', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_5b58869550d21cd070c65040');
            $table->index('account_id', 'ix_1ade5d2eab6961b86c8a8ec3');
        });
        Schema::create('tl_bot_verification_bot_verification', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_verification')->cascadeOnDelete();
            $table->bigInteger('bot_id');
            $table->index('bot_id', 'ix_37cf98ff58b66cafe719a5b9');
            $table->bigInteger('icon');
            $table->text('description');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c32987fc9ab37c9793f2ccd0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_verification_bot_verification');
        Schema::dropIfExists('tl_bot_verification');
    }
};
