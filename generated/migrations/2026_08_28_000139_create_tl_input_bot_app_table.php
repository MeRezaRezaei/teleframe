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
        Schema::create('tl_input_bot_app', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_bot_app_input_bot_app_i_d', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_app')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->timestamps();
        });
        Schema::create('tl_input_bot_app_input_bot_app_short_name', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_app')->cascadeOnDelete();
            $table->uuid('bot_id');
            $table->text('short_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_bot_app_input_bot_app_short_name');
        Schema::dropIfExists('tl_input_bot_app_input_bot_app_i_d');
        Schema::dropIfExists('tl_input_bot_app');
    }
};
