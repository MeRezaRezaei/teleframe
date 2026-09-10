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
        Schema::create('tl_ai_compose_tone_ai_compose_tone', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('creator')->default(false);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->text('slug')->nullable();
            $table->text('title')->nullable();
            $table->bigInteger('emoji_id')->nullable();
            $table->index('emoji_id', 'ix_2eae98b2936df24902de67d6');
            $table->text('prompt')->nullable();
            $table->integer('installs_count')->nullable();
            $table->bigInteger('author_id')->nullable();
            $table->index('author_id', 'ix_d4ddf6203f186cd68daed742');
            $table->bigInteger('example_english')->nullable();
            $table->index('example_english', 'ix_31eb04e79d988b7d4a96c354');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f8a5c0079d9e4ad4597dec87');
            $table->index('account_id', 'ix_fd9c29052ccf7c5df9c15734');
        });
        Schema::create('tl_ai_compose_tone_ai_compose_tone_default', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tone')->nullable();
            $table->bigInteger('emoji_id')->nullable();
            $table->index('emoji_id', 'ix_929e4f4e834ae57b867958ef');
            $table->text('title')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7a9bf809da19c30f4e4bb0b9');
            $table->index('account_id', 'ix_5c034269551514592960bdb0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_ai_compose_tone_ai_compose_tone_default');
        Schema::dropIfExists('tl_ai_compose_tone_ai_compose_tone');
    }
};
