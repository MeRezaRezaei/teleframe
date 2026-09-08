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
        Schema::create('tl_help_recent_me_urls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_help_recent_me_urls_recent_me_urls', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_recent_me_urls')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_help_recent_me_urls_recent_me_urls__urls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_recent_me_urls_recent_me_urls')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_4fd7142f344ee8c22d18');
        });
        Schema::create('tl_help_recent_me_urls_recent_me_urls__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_recent_me_urls_recent_me_urls')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d4e7488e8a15c9c973ac');
        });
        Schema::create('tl_help_recent_me_urls_recent_me_urls__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_recent_me_urls_recent_me_urls')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_0a4d3f559d3bbb916b62');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_recent_me_urls_recent_me_urls__users');
        Schema::dropIfExists('tl_help_recent_me_urls_recent_me_urls__chats');
        Schema::dropIfExists('tl_help_recent_me_urls_recent_me_urls__urls');
        Schema::dropIfExists('tl_help_recent_me_urls_recent_me_urls');
        Schema::dropIfExists('tl_help_recent_me_urls');
    }
};
