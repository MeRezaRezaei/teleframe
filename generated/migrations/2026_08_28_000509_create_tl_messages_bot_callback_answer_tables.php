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
        Schema::create('tl_messages_bot_callback_answer_bot_callback_answer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('alert')->default(false);
            $table->boolean('has_url')->default(false);
            $table->boolean('native_ui')->default(false);
            $table->text('message')->nullable();
            $table->text('url')->nullable();
            $table->integer('cache_time')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b8442aae8705df3eab91b8de');
            $table->index('account_id', 'ix_041cc0f64f614514e56bfbed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_bot_callback_answer_bot_callback_answer');
    }
};
