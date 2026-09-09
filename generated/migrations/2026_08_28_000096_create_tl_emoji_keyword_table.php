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
            $table->index('constructor_id', 'ix_faccab19e5cffbbdc4b1be23');
            $table->index('account_id', 'ix_2bf09fde714a3a0522b17c08');
        });
        Schema::create('tl_emoji_keyword_emoji_keyword', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_keyword')->cascadeOnDelete();
            $table->text('keyword');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1e2e889662d7bb4567643319');
        });
        Schema::create('tl_emoji_keyword_emoji_keyword__emoticons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_emoji_keyword_emoji_keyword')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d021a6ecd291dbe0348e');
            $table->index('account_id', 'ix_0e1f92c04f237b70a282d540');
        });
        Schema::create('tl_emoji_keyword_emoji_keyword_deleted', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_keyword')->cascadeOnDelete();
            $table->text('keyword');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_dd095f4e34f623816360740a');
        });
        Schema::create('tl_emoji_keyword_emoji_keyword_deleted__emoticons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_emoji_keyword_emoji_keyword_deleted')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_3b2875fde278defe7369');
            $table->index('account_id', 'ix_e996b5a4714e732a6d5a3119');
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
