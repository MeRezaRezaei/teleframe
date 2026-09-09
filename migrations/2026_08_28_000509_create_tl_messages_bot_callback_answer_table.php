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
        Schema::create('tl_messages_bot_callback_answer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_058eedf2eb89ac9c8888aa82');
            $table->index('account_id', 'ix_0bba3b2942eb7b69880585b4');
        });
        Schema::create('tl_messages_bot_callback_answer_bot_callback_answer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_bot_callback_answer')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('alert')->default(false);
            $table->boolean('has_url')->default(false);
            $table->boolean('native_ui')->default(false);
            $table->text('message')->nullable();
            $table->text('url')->nullable();
            $table->integer('cache_time');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_041cc0f64f614514e56bfbed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_bot_callback_answer_bot_callback_answer');
        Schema::dropIfExists('tl_messages_bot_callback_answer');
    }
};
