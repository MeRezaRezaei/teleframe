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
        Schema::create('tl_emoji_keyword', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_emoji_keyword_emoji_keyword', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_keyword')->cascadeOnDelete();
            $table->text('keyword');
            $table->timestamps();
        });
        Schema::create('tl_emoji_keyword_emoji_keyword__emoticons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_emoji_keyword_emoji_keyword')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d021a6ecd291dbe0348e');
        });
        Schema::create('tl_emoji_keyword_emoji_keyword_deleted', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_keyword')->cascadeOnDelete();
            $table->text('keyword');
            $table->timestamps();
        });
        Schema::create('tl_emoji_keyword_emoji_keyword_deleted__emoticons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_emoji_keyword_emoji_keyword_deleted')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_3b2875fde278defe7369');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_emoji_keyword_emoji_keyword_deleted__emoticons');
        Schema::dropIfExists('tl_emoji_keyword_emoji_keyword_deleted');
        Schema::dropIfExists('tl_emoji_keyword_emoji_keyword__emoticons');
        Schema::dropIfExists('tl_emoji_keyword_emoji_keyword');
        Schema::dropIfExists('tl_emoji_keyword');
    }
};
