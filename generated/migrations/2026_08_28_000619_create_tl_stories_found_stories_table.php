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
        Schema::create('tl_stories_found_stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_stories_found_stories_found_stories', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stories_found_stories')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('count');
            $table->text('next_offset')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_stories_found_stories_found_stories__stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_found_stories_found_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_0982d6bf5b7f22dadc0a');
        });
        Schema::create('tl_stories_found_stories_found_stories__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_found_stories_found_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e0d669afb07458ff9c42');
        });
        Schema::create('tl_stories_found_stories_found_stories__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_found_stories_found_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d5b0e2e69a44a86d175a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stories_found_stories_found_stories__users');
        Schema::dropIfExists('tl_stories_found_stories_found_stories__chats');
        Schema::dropIfExists('tl_stories_found_stories_found_stories__stories');
        Schema::dropIfExists('tl_stories_found_stories_found_stories');
        Schema::dropIfExists('tl_stories_found_stories');
    }
};
