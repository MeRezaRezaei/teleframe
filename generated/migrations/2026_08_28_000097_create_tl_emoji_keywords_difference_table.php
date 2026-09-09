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
        Schema::create('tl_emoji_keywords_difference', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8779d4428fcd485def64c06e');
            $table->index('account_id', 'ix_f42fff186ba2d3b13999cd97');
        });
        Schema::create('tl_emoji_keywords_difference_emoji_keywords_difference', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_keywords_difference')->cascadeOnDelete();
            $table->text('lang_code');
            $table->integer('from_version');
            $table->integer('version');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f145e462e4177f56cdb1cd6a');
        });
        Schema::create('tl_emoji_keywords_difference_emoji_keywords_d_8bb0c8308884', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_emoji_keywords_difference_emoji_keywords_difference')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8da1631f865daff3e450');
            $table->index('account_id', 'ix_5309969e6cb5f9b48af5554f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_emoji_keywords_difference_emoji_keywords_d_8bb0c8308884');
        Schema::dropIfExists('tl_emoji_keywords_difference_emoji_keywords_difference');
        Schema::dropIfExists('tl_emoji_keywords_difference');
    }
};
