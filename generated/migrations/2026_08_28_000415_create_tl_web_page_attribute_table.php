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
        Schema::create('tl_web_page_attribute', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_ai_compose_tone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_page_attribute')->cascadeOnDelete();
            $table->bigInteger('emoji_id');
            $table->timestamps();
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_star_gift_auction', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_page_attribute')->cascadeOnDelete();
            $table->uuid('gift');
            $table->integer('end_date');
            $table->timestamps();
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_star_0f76dd30baaf', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_page_attribute')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_star_17202adcc3bb', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_web_page_attribute_web_page_attribute_star_0f76dd30baaf')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_3174fdb2013fa3926a77');
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_sticker_set', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_page_attribute')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('emojis')->default(false);
            $table->boolean('text_color')->default(false);
            $table->timestamps();
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_stic_5d4fa9f0c49f', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_web_page_attribute_web_page_attribute_sticker_set')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e3b08dcbfabfde675ba7');
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_story', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_page_attribute')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('peer');
            $table->integer('tl_id');
            $table->uuid('story')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_theme', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_page_attribute')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('settings')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_theme__documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_web_page_attribute_web_page_attribute_theme')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_387b647caa8e1462fdc4');
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_unique_star_gift', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_page_attribute')->cascadeOnDelete();
            $table->uuid('gift');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_unique_star_gift');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_theme__documents');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_theme');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_story');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_stic_5d4fa9f0c49f');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_sticker_set');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_star_17202adcc3bb');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_star_0f76dd30baaf');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_star_gift_auction');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_ai_compose_tone');
        Schema::dropIfExists('tl_web_page_attribute');
    }
};
