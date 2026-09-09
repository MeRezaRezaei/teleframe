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
        Schema::create('tl_account_emoji_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_a315a21cc3372df58d0f8516');
            $table->index('account_id', 'ix_76b00996763d392c57bf73ae');
        });
        Schema::create('tl_account_emoji_statuses_emoji_statuses', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_emoji_statuses')->cascadeOnDelete();
            $table->bigInteger('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_049ff509c0ff91e0cd1adb93');
        });
        Schema::create('tl_account_emoji_statuses_emoji_statuses__statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_emoji_statuses_emoji_statuses')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9535b5c25ecf633e1c88');
            $table->index('account_id', 'ix_90274a56c87934487508df2c');
        });
        Schema::create('tl_account_emoji_statuses_emoji_statuses_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_emoji_statuses')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8e14911ff95a85ccef29c075');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_emoji_statuses_emoji_statuses_not_modified');
        Schema::dropIfExists('tl_account_emoji_statuses_emoji_statuses__statuses');
        Schema::dropIfExists('tl_account_emoji_statuses_emoji_statuses');
        Schema::dropIfExists('tl_account_emoji_statuses');
    }
};
