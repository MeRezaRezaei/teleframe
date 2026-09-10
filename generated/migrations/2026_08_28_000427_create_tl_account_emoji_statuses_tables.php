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
        Schema::create('tl_account_emoji_statuses_emoji_statuses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_80c4e767768846c448871dec');
            $table->index('account_id', 'ix_049ff509c0ff91e0cd1adb93');
        });
        Schema::create('tl_account_emoji_statuses_emoji_statuses__statuses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_account_emoji_statuses_emoji_statuses', 'id', 'fk_28d817116d415891a311849f')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9535b5c25ecf633e1c88');
            $table->index('account_id', 'ix_90274a56c87934487508df2c');
        });
        Schema::create('tl_account_emoji_statuses_emoji_statuses_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b031e8b1ce07332c755aed3a');
            $table->index('account_id', 'ix_8e14911ff95a85ccef29c075');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_emoji_statuses_emoji_statuses_not_modified');
        Schema::dropIfExists('tl_account_emoji_statuses_emoji_statuses__statuses');
        Schema::dropIfExists('tl_account_emoji_statuses_emoji_statuses');
    }
};
