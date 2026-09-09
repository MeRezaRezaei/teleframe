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
        Schema::create('tl_ai_compose_tone', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_9b240b2f8eff05c7ca1e004b');
            $table->index('account_id', 'ix_e31f5d95e32b4b9920129ee9');
        });
        Schema::create('tl_ai_compose_tone_ai_compose_tone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_ai_compose_tone')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('creator')->default(false);
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->text('slug');
            $table->text('title');
            $table->bigInteger('emoji_id')->nullable();
            $table->index('emoji_id', 'ix_2eae98b2936df24902de67d6');
            $table->text('prompt')->nullable();
            $table->integer('installs_count')->nullable();
            $table->bigInteger('author_id')->nullable();
            $table->index('author_id', 'ix_d4ddf6203f186cd68daed742');
            $table->uuid('example_english')->nullable();
            $table->index('example_english', 'ix_31eb04e79d988b7d4a96c354');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fd9c29052ccf7c5df9c15734');
            $table->unique(['account_id', 'tl_id'], 'ux_9e3642fde4a5f6094545');
        });
        Schema::create('tl_ai_compose_tone_ai_compose_tone_default', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_ai_compose_tone')->cascadeOnDelete();
            $table->text('tone');
            $table->bigInteger('emoji_id');
            $table->index('emoji_id', 'ix_929e4f4e834ae57b867958ef');
            $table->text('title');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5c034269551514592960bdb0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_ai_compose_tone_ai_compose_tone_default');
        Schema::dropIfExists('tl_ai_compose_tone_ai_compose_tone');
        Schema::dropIfExists('tl_ai_compose_tone');
    }
};
