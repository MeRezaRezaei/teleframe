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
            $table->index('constructor_id');
            $table->index('account_id');
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
            $table->text('prompt')->nullable();
            $table->integer('installs_count')->nullable();
            $table->bigInteger('author_id')->nullable();
            $table->uuid('example_english')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_ai_compose_tone_ai_compose_tone_default', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_ai_compose_tone')->cascadeOnDelete();
            $table->text('tone');
            $table->bigInteger('emoji_id');
            $table->text('title');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_ai_compose_tone_ai_compose_tone_default');
        Schema::dropIfExists('tl_ai_compose_tone_ai_compose_tone');
        Schema::dropIfExists('tl_ai_compose_tone');
    }
};
